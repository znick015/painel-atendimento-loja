<?php
require_once 'conexao.php';

// Recebe os dados enviados pelo JavaScript em formato JSON
$dados = json_decode(file_get_contents('php://input'), true);

if (isset($dados['contato_id']) && !empty(trim($dados['conteudo']))) {
    $contato_id = $dados['contato_id'];
    $conteudo = trim($dados['conteudo']);
    $remetente = 'vendedora'; // Como estamos no painel, quem envia é sempre a vendedora

    try {
        $stmt = $pdo->prepare("INSERT INTO mensagens (contato_id, remetente, conteudo) VALUES (?, ?, ?)");
        $stmt->execute([$contato_id, $remetente, $conteudo]);
        
        // Retorna sucesso
        echo json_encode(['sucesso' => true]);
    } catch (PDOException $e) {
        echo json_encode(['sucesso' => false, 'erro' => 'Erro no banco de dados']);
    }
} else {
    echo json_encode(['sucesso' => false, 'erro' => 'Dados inválidos']);
}
?>