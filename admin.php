<?php
session_start();
require_once 'conexao.php';

// Bloqueia quem não está logado ou não é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['perfil'] != 'admin') {
    header("Location: login.php");
    exit;
}

$usuarioLogadoId = $_SESSION['usuario_id'];
$empresaId = $_SESSION['empresa_id'];

// Busca os dados da Admin
$stmtUsuario = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
$stmtUsuario->execute([$usuarioLogadoId]);
$admin = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

// Busca todas as vendedoras dessa empresa para o Filtro
$stmtVendedoras = $pdo->prepare("SELECT id, nome FROM usuarios WHERE empresa_id = ? AND perfil = 'vendedora'");
$stmtVendedoras->execute([$empresaId]);
$vendedoras = $stmtVendedoras->fetchAll(PDO::FETCH_ASSOC);

$filtroVendedoraId = isset($_GET['vendedora_id']) ? $_GET['vendedora_id'] : '';

// Busca contatos
if ($filtroVendedoraId) {
    $stmtContatos = $pdo->prepare("
        SELECT c.*, u.nome as vendedora_nome 
        FROM contatos c 
        LEFT JOIN usuarios u ON c.usuario_id = u.id 
        WHERE c.empresa_id = ? AND c.usuario_id = ? 
        ORDER BY c.ultimo_atendimento DESC
    ");
    $stmtContatos->execute([$empresaId, $filtroVendedoraId]);
} else {
    $stmtContatos = $pdo->prepare("
        SELECT c.*, u.nome as vendedora_nome 
        FROM contatos c 
        LEFT JOIN usuarios u ON c.usuario_id = u.id 
        WHERE c.empresa_id = ? 
        ORDER BY c.ultimo_atendimento DESC
    ");
    $stmtContatos->execute([$empresaId]);
}
$contatos = $stmtContatos->fetchAll(PDO::FETCH_ASSOC);

$contatoAtivoId = isset($_GET['chat']) ? $_GET['chat'] : null;
$contatoAtivoNome = 'Selecione uma conversa';
$mensagens = [];

if ($contatoAtivoId) {
    $stmtContatoAtivo = $pdo->prepare("SELECT nome FROM contatos WHERE id = ? AND empresa_id = ?");
    $stmtContatoAtivo->execute([$contatoAtivoId, $empresaId]);
    $contatoAtivo = $stmtContatoAtivo->fetch(PDO::FETCH_ASSOC);
    
    if ($contatoAtivo) {
        $contatoAtivoNome = $contatoAtivo['nome'];
        // Ordenação corrigida pelo ID
        $stmtMensagens = $pdo->prepare("SELECT * FROM mensagens WHERE contato_id = ? ORDER BY id ASC");
        $stmtMensagens->execute([$contatoAtivoId]);
        $mensagens = $stmtMensagens->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $contatoAtivoId = null; 
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Gerencial - Supervisão</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex overflow-hidden">

    <aside class="w-full md:w-1/3 bg-gray-800 flex flex-col h-full shadow-lg z-20">
        <div class="bg-gray-900 p-4 flex justify-between items-center border-b border-gray-700">
            <div class="font-bold text-white flex items-center">
                <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                <?php echo htmlspecialchars($admin['nome']); ?> (Supervisão)
            </div>
            <!-- Botão Sair atualizado -->
            <a href="logout.php" class="text-sm bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700">Sair</a>
        </div>
        
        <div class="p-4 bg-gray-800 border-b border-gray-700">
            <form action="admin.php" method="GET" id="formFiltro">
                <select name="vendedora_id" class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600 outline-none" onchange="document.getElementById('formFiltro').submit();">
                    <option value="">Todas as Conversas</option>
                    <?php foreach ($vendedoras as $vend): ?>
                        <option value="<?php echo $vend['id']; ?>" <?php echo ($filtroVendedoraId == $vend['id']) ? 'selected' : ''; ?>>
                            Atendimentos: <?php echo htmlspecialchars($vend['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if($contatoAtivoId): ?>
                    <input type="hidden" name="chat" value="<?php echo $contatoAtivoId; ?>">
                <?php endif; ?>
            </form>
        </div>
        
        <div class="flex-1 overflow-y-auto bg-gray-800">
            <?php foreach ($contatos as $contato): ?>
                <?php 
                    $url = "?chat=" . $contato['id'];
                    if ($filtroVendedoraId) $url .= "&vendedora_id=" . $filtroVendedoraId;
                ?>
                <a href="<?php echo $url; ?>" class="flex items-center p-4 border-b border-gray-700 hover:bg-gray-700 cursor-pointer <?php echo ($contatoAtivoId == $contato['id']) ? 'bg-gray-700' : ''; ?>">
                    <div class="w-12 h-12 bg-indigo-500 rounded-full flex items-center justify-center text-white font-bold text-xl">
                        <?php echo strtoupper(substr($contato['nome'], 0, 1)); ?>
                    </div>
                    <div class="ml-4 flex-1">
                        <div class="flex justify-between items-start">
                            <h3 class="font-semibold text-gray-200"><?php echo htmlspecialchars($contato['nome']); ?></h3>
                        </div>
                        <p class="text-sm text-gray-400 truncate"><?php echo htmlspecialchars($contato['numero_telefone']); ?></p>
                        <span class="inline-block mt-1 bg-gray-600 text-gray-200 text-[10px] px-2 py-0.5 rounded">
                            Resp: <?php echo htmlspecialchars($contato['vendedora_nome'] ?? 'Sem atribuição'); ?>
                        </span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </aside>

    <main class="<?php echo $contatoAtivoId ? 'flex' : 'hidden md:flex'; ?> w-full md:w-2/3 bg-[#efeae2] flex-col h-full relative">
        
        <?php if ($contatoAtivoId): ?>
            <div class="bg-gray-200 p-4 flex items-center border-b border-gray-300 shadow-sm z-10 justify-between">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-indigo-500 rounded-full flex items-center justify-center text-white font-bold">
                        <?php echo strtoupper(substr($contatoAtivoNome, 0, 1)); ?>
                    </div>
                    <h2 class="ml-4 font-semibold text-gray-800"><?php echo htmlspecialchars($contatoAtivoNome); ?></h2>
                </div>
                <div class="bg-yellow-100 text-yellow-800 text-xs px-3 py-1 rounded-full font-semibold border border-yellow-300">
                    Modo Supervisão
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-4" id="chat-container">
                <?php foreach ($mensagens as $msg): ?>
                    <?php if ($msg['remetente'] == 'cliente'): ?>
                        <div class="flex justify-start">
                            <div class="bg-white p-3 rounded-lg rounded-tl-none shadow max-w-md">
                                <p class="text-gray-800"><?php echo nl2br(htmlspecialchars($msg['conteudo'])); ?></p>
                                <span class="text-[10px] text-gray-500 block text-right mt-1"><?php echo date('H:i', strtotime($msg['timestamp_envio'])); ?></span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="flex justify-end">
                            <div class="bg-[#d9fdd3] p-3 rounded-lg rounded-tr-none shadow max-w-md">
                                <p class="text-gray-800"><?php echo nl2br(htmlspecialchars($msg['conteudo'])); ?></p>
                                <span class="text-[10px] text-gray-500 block text-right mt-1"><?php echo date('H:i', strtotime($msg['timestamp_envio'])); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="bg-gray-200 p-4 flex items-center justify-center text-gray-500 text-sm">
                Você está em modo de supervisão. Apenas a vendedora responsável pode enviar mensagens.
            </div>
            
        <?php else: ?>
            <div class="flex-1 flex flex-col items-center justify-center opacity-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <h2 class="text-xl font-medium text-gray-600">Selecione uma conversa para monitorar o atendimento</h2>
            </div>
        <?php endif; ?>

    </main>

    <script>
    window.onload = function() {
        const chatContainer = document.getElementById('chat-container');
        if(chatContainer) chatContainer.scrollTop = chatContainer.scrollHeight;
    }
    </script>
</body>
</html>