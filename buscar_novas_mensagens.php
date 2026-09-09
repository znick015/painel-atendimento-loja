<?php
require_once 'conexao.php';

$contato_id = isset($_GET['contato_id']) ? (int)$_GET['contato_id'] : 0;
$ultimo_id = isset($_GET['ultimo_id']) ? (int)$_GET['ultimo_id'] : 0;

if ($contato_id > 0) {
    // Busca mensagens do contato atual que tenham ID maior que o último ID carregado na tela
    $stmt = $pdo->prepare("SELECT * FROM mensagens WHERE contato_id = ? AND id > ? ORDER BY id ASC");
    $stmt->execute([$contato_id, $ultimo_id]);
    $mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formata a hora para o JavaScript não precisar calcular
    foreach ($mensagens as &$msg) {
        $msg['hora'] = date('H:i', strtotime($msg['timestamp_envio']));
    }

    echo json_encode(['sucesso' => true, 'mensagens' => $mensagens]);
} else {
    echo json_encode(['sucesso' => false, 'erro' => 'Parâmetros inválidos']);
}
?>