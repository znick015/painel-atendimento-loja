<?php
require_once 'conexao.php';

// Simula que a Vendedora A (ID 2) está logada no sistema
$usuarioLogadoId = 2; 

// Busca o nome da vendedora logada
$stmtUsuario = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
$stmtUsuario->execute([$usuarioLogadoId]);
$vendedora = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

// Busca os contatos que caíram na roleta para esta vendedora
$stmtContatos = $pdo->prepare("SELECT * FROM contatos WHERE usuario_id = ? ORDER BY ultimo_atendimento DESC");
$stmtContatos->execute([$usuarioLogadoId]);
$contatos = $stmtContatos->fetchAll(PDO::FETCH_ASSOC);

$contatoAtivoId = isset($_GET['chat']) ? $_GET['chat'] : null;
$contatoAtivoNome = 'Selecione uma conversa';
$mensagens = [];
$ultimoIdMensagem = 0; // Prepara a variável do último ID

if ($contatoAtivoId) {
    // Busca os detalhes do contato clicado
    $stmtContatoAtivo = $pdo->prepare("SELECT nome FROM contatos WHERE id = ? AND usuario_id = ?");
    $stmtContatoAtivo->execute([$contatoAtivoId, $usuarioLogadoId]);
    $contatoAtivo = $stmtContatoAtivo->fetch(PDO::FETCH_ASSOC);
    
    if ($contatoAtivo) {
        $contatoAtivoNome = $contatoAtivo['nome'];
        
        // Busca o histórico de mensagens dessa conversa
        $stmtMensagens = $pdo->prepare("SELECT * FROM mensagens WHERE contato_id = ? ORDER BY id ASC");
        $stmtMensagens->execute([$contatoAtivoId]);
        $mensagens = $stmtMensagens->fetchAll(PDO::FETCH_ASSOC);
        
        // Pega o ID da última mensagem que carregou na tela para passar pro radar
        if (count($mensagens) > 0) {
            $ultimoIdMensagem = end($mensagens)['id'];
        }
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
    <title>Painel de Atendimento</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex overflow-hidden">

    <!-- BARRA LATERAL -->
    <aside class="w-full md:w-1/3 bg-white border-r border-gray-300 flex flex-col h-full">
        <div class="bg-gray-200 p-4 flex justify-between items-center border-b">
            <div class="font-bold text-gray-700"><?php echo htmlspecialchars($vendedora['nome']); ?></div>
            <button class="text-sm bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">Sair</button>
        </div>
        
        <div class="flex-1 overflow-y-auto">
            <?php foreach ($contatos as $contato): ?>
                <a href="?chat=<?php echo $contato['id']; ?>" class="flex items-center p-4 border-b hover:bg-gray-50 cursor-pointer <?php echo ($contatoAtivoId == $contato['id']) ? 'bg-gray-100' : ''; ?>">
                    <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold text-xl">
                        <?php echo strtoupper(substr($contato['nome'], 0, 1)); ?>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($contato['nome']); ?></h3>
                        <p class="text-sm text-gray-600 truncate"><?php echo htmlspecialchars($contato['numero_telefone']); ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
            
            <?php if (count($contatos) === 0): ?>
                <div class="p-4 text-center text-gray-500 text-sm">Nenhum cliente atribuído ainda.</div>
            <?php endif; ?>
        </div>
    </aside>

    <!-- ÁREA DO CHAT -->
    <main class="<?php echo $contatoAtivoId ? 'flex' : 'hidden md:flex'; ?> w-full md:w-2/3 bg-[#efeae2] flex-col h-full relative">
        
        <?php if ($contatoAtivoId): ?>
            <!-- Cabeçalho -->
            <div class="bg-gray-200 p-4 flex items-center border-b border-gray-300 shadow-sm z-10">
                <a href="index.php" class="md:hidden mr-4 text-gray-600 hover:text-gray-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                </a>
                <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                    <?php echo strtoupper(substr($contatoAtivoNome, 0, 1)); ?>
                </div>
                <h2 class="ml-4 font-semibold text-gray-800"><?php echo htmlspecialchars($contatoAtivoNome); ?></h2>
            </div>

            <!-- Mensagens -->
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
                
                <?php if (count($mensagens) === 0): ?>
                    <div class="text-center text-gray-500 text-sm mt-10 bg-white/50 p-2 rounded inline-block mx-auto">Nenhuma mensagem ainda.</div>
                <?php endif; ?>
            </div>

            <!-- Campo de Digitação -->
            <div class="bg-gray-200 p-4 flex items-center">
                <input type="hidden" id="contato_id" value="<?php echo $contatoAtivoId; ?>">
                <input type="text" id="mensagem_input" placeholder="Digite uma mensagem..." class="flex-1 p-3 rounded-full border-none focus:ring-2 focus:ring-green-400 outline-none shadow-sm" onkeypress="if(event.key === 'Enter') enviarMensagem()">
                <button onclick="enviarMensagem()" class="ml-3 bg-green-500 text-white p-3 rounded-full hover:bg-green-600 shadow">
                    Enviar
                </button>
            </div>
            
        <?php else: ?>
            <div class="flex-1 flex flex-col items-center justify-center opacity-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <h2 class="text-xl font-medium text-gray-600">Selecione uma conversa para iniciar o atendimento</h2>
            </div>
        <?php endif; ?>

    </main>

    <script>
    // Inicia a variável com o ID da última mensagem carregada no PHP
    let ultimoIdMensagem = <?php echo $ultimoIdMensagem; ?>;

    // Rola o chat para o final logo que a página carrega
    window.onload = function() {
        const chatContainer = document.getElementById('chat-container');
        if(chatContainer) chatContainer.scrollTop = chatContainer.scrollHeight;
    }

    // Envia a mensagem para o banco
    function enviarMensagem() {
        const input = document.getElementById('mensagem_input');
        const conteudo = input.value.trim();
        const contatoId = document.getElementById('contato_id')?.value;

        if (conteudo === '' || !contatoId) return; 

        // Limpa o input para o usuário já digitar a próxima
        input.value = '';

        fetch('enviar_mensagem.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ contato_id: contatoId, conteudo: conteudo })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.sucesso) alert('Erro ao enviar mensagem.');
            // Após enviar, o radar (setInterval) abaixo vai puxar a mensagem do banco e exibir na tela
        })
        .catch(error => console.error('Erro:', error));
    }

    // O "Radar": Roda a cada 2 segundos para buscar novas mensagens (do cliente ou as que você acabou de enviar)
    setInterval(() => {
        const contatoId = document.getElementById('contato_id')?.value;
        if (!contatoId) return; 

        fetch(`buscar_novas_mensagens.php?contato_id=${contatoId}&ultimo_id=${ultimoIdMensagem}`)
        .then(response => response.json())
        .then(data => {
            if (data.sucesso && data.mensagens.length > 0) {
                const chatContainer = document.getElementById('chat-container');
                
                data.mensagens.forEach(msg => {
                    let balaoHTML = '';
                    
                    if (msg.remetente === 'cliente') {
                        balaoHTML = `
                            <div class="flex justify-start">
                                <div class="bg-white p-3 rounded-lg rounded-tl-none shadow max-w-md">
                                    <p class="text-gray-800">${msg.conteudo}</p>
                                    <span class="text-[10px] text-gray-500 block text-right mt-1">${msg.hora}</span>
                                </div>
                            </div>
                        `;
                    } else {
                        balaoHTML = `
                            <div class="flex justify-end">
                                <div class="bg-[#d9fdd3] p-3 rounded-lg rounded-tr-none shadow max-w-md">
                                    <p class="text-gray-800">${msg.conteudo}</p>
                                    <span class="text-[10px] text-gray-500 block text-right mt-1">${msg.hora}</span>
                                </div>
                            </div>
                        `;
                    }
                    
                    chatContainer.insertAdjacentHTML('beforeend', balaoHTML);
                    ultimoIdMensagem = msg.id; // Atualiza o ID
                });
                
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }
        })
        .catch(error => console.error('Erro no radar:', error));
    }, 2000); 
    </script>
</body>
</html>