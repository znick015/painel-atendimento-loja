<?php
require_once 'conexao.php';

// A Evolution API envia os dados via POST (JSON), mas para testarmos agora no navegador, vamos aceitar GET também.
$dados = json_decode(file_get_contents('php://input'), true);

$telefone = $_GET['telefone'] ?? $dados['data']['key']['remoteJid'] ?? '';
$mensagem = $_GET['mensagem'] ?? $dados['data']['message']['conversation'] ?? '';
$empresa_id = 1; // Fixo por enquanto, simulando a loja da sua vizinha

if (empty($telefone) || empty($mensagem)) {
    die("Nenhum dado recebido. Passe ?telefone=XXX&mensagem=YYY na URL para testar.");
}

// Limpa o número (remove espaços, traços, etc)
$numero_limpo = preg_replace('/\D/', '', $telefone);

// 1. Verifica se o cliente já existe no banco
$stmt = $pdo->prepare("SELECT id, usuario_id FROM contatos WHERE numero_telefone = ? AND empresa_id = ?");
$stmt->execute([$numero_limpo, $empresa_id]);
$contato = $stmt->fetch(PDO::FETCH_ASSOC);

if ($contato) {
    // Cliente antigo: já tem uma vendedora dona dele
    $contato_id = $contato['id'];
    $vendedora_id = $contato['usuario_id'];
    $status_roleta = "Cliente Antigo - Mantido com a Vendedora ID $vendedora_id";
} else {
    // 2. Cliente Novo: A MÁGICA DA ROLETA ACONTECE AQUI
    
    // Pega todas as vendedoras ativas ordenadas pelo ID
    $stmtVend = $pdo->prepare("SELECT id FROM usuarios WHERE empresa_id = ? AND perfil = 'vendedora' AND ativo = 1 ORDER BY id ASC");
    $stmtVend->execute([$empresa_id]);
    $vendedoras = $stmtVend->fetchAll(PDO::FETCH_COLUMN);

    if (empty($vendedoras)) {
        die("Erro: Nenhuma vendedora ativa no sistema.");
    }

    // Busca quem foi a ÚLTIMA vendedora a receber um contato
    $stmtUltimo = $pdo->prepare("SELECT usuario_id FROM contatos WHERE empresa_id = ? AND usuario_id IS NOT NULL ORDER BY id DESC LIMIT 1");
    $stmtUltimo->execute([$empresa_id]);
    $ultimo_id = $stmtUltimo->fetchColumn();

    $proxima_vendedora = $vendedoras[0]; // Se for o primeiríssimo cliente da loja, vai pra primeira da lista

    if ($ultimo_id) {
        $posicao_atual = array_search($ultimo_id, $vendedoras);
        
        // Se achou a última vendedora e existe uma próxima na lista
        if ($posicao_atual !== false && isset($vendedoras[$posicao_atual + 1])) {
            $proxima_vendedora = $vendedoras[$posicao_atual + 1];
        } 
        // Se ela era a última da lista (ou foi excluída), o if acima falha e a roleta volta automaticamente para o índice 0 ($vendedoras[0])
    }

    $vendedora_id = $proxima_vendedora;

    // Cria o novo contato no banco amarrado à vendedora sorteada
    $stmtNovo = $pdo->prepare("INSERT INTO contatos (empresa_id, usuario_id, numero_telefone, nome) VALUES (?, ?, ?, ?)");
    // Como ainda não sabemos o nome do cliente que chamou no WhatsApp, colocamos o próprio número temporariamente
    $stmtNovo->execute([$empresa_id, $vendedora_id, $numero_limpo, "Novo ($numero_limpo)"]);
    $contato_id = $pdo->lastInsertId();
    
    $status_roleta = "Novo Cliente - Distribuído para a Vendedora ID $vendedora_id";
}

// 3. Salva a mensagem que o cliente mandou no histórico do chat
$stmtMsg = $pdo->prepare("INSERT INTO mensagens (contato_id, remetente, conteudo) VALUES (?, 'cliente', ?)");
$stmtMsg->execute([$contato_id, $mensagem]);

// Atualiza a hora do último contato para ele subir para o topo da lista no painel
$stmtUpdate = $pdo->prepare("UPDATE contatos SET ultimo_atendimento = NOW() WHERE id = ?");
$stmtUpdate->execute([$contato_id]);

echo "Sucesso! $status_roleta | Mensagem salva.";
?>