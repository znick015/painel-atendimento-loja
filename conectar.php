<?php
// Configurações da Evolution API
$apiUrl = "http://localhost:8080/instance/create";
$apiKey = "vizinha_secreta_123"; // A mesma senha que colocamos no docker-compose.yml
$nomeInstancia = "loja_vizinha";

// Dados que vamos enviar para a API criar a conexão
$dados = [
    "instanceName" => $nomeInstancia,
    "qrcode" => true,
    "integration" => "WHATSAPP-BAILEYS"
];

// Inicia a requisição (cURL) para a API
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "apikey: " . $apiKey
]);

$resposta = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$resultado = json_decode($resposta, true);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Conectar WhatsApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-lg text-center max-w-md w-full">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Conectar WhatsApp</h1>
        
        <?php if ($httpCode == 201 && isset($resultado['qrcode']['base64'])): ?>
            <!-- Deu certo, a API devolveu o QR Code -->
            <p class="text-gray-600 mb-6">Abra o WhatsApp no celular, vá em Aparelhos Conectados e escaneie o código abaixo:</p>
            <div class="flex justify-center mb-4">
                <img src="<?php echo $resultado['qrcode']['base64']; ?>" alt="QR Code WhatsApp" class="border p-2 rounded">
            </div>
            <p class="text-sm text-green-600 font-semibold">Aguardando leitura...</p>

        <?php elseif (isset($resultado['response']['message']) && is_array($resultado['response']['message']) && in_array("Instance loja_vizinha already exists", $resultado['response']['message'])): ?>
            <!-- A instância já foi criada antes -->
            <div class="bg-yellow-100 text-yellow-800 p-4 rounded mb-4">
                A conexão "<?php echo $nomeInstancia; ?>" já existe no servidor. 
                Se o celular desconectou, precisamos de uma rota para buscar o QR Code novamente.
            </div>
        <?php else: ?>
            <!-- Algum erro ocorreu -->
            <div class="bg-red-100 text-red-800 p-4 rounded mb-4">
                <strong>Erro ao conectar com a API:</strong><br>
                <code class="text-xs"><?php echo htmlspecialchars($resposta); ?></code>
            </div>
        <?php endif; ?>
        
        <div class="mt-6">
            <a href="admin.php" class="text-blue-500 hover:underline">Voltar ao Painel Gerencial</a>
        </div>
    </div>
</body>
</html>