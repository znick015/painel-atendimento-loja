<?php
session_start();
require_once 'conexao.php';

// Recebe os dados do JavaScript
$dados = json_decode(file_get_contents('php://input'), true);

if (isset($dados['contato_id']) && !empty(trim($dados['novo_nome']))) {
    $contato_id = $dados['contato_id'];
    $novo_nome = trim($dados['novo_nome']);
    
    // Pega o ID da empresa da vendedora logada por segurança
    $empresa_id = $_SESSION['empresa_id'];

    try {
        // Atualiza o nome do contato garantindo que pertence à mesma empresa
        $stmt = $pdo->prepare("UPDATE contatos SET nome = ? WHERE id = ? AND empresa_id = ?");
        $stmt->execute([$novo_nome, $contato_id, $empresa_id]);
        
        echo json_encode(['sucesso' => true]);
    } catch (PDOException $e) {
        echo json_encode(['sucesso' => false, 'erro' => 'Erro ao atualizar no banco']);
    }
} else {
    echo json_encode(['sucesso' => false, 'erro' => 'Dados inválidos']);
}
?>