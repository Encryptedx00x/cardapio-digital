<?php
require_once '../db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    if (!$nome || !$email || !$senha) {
        http_response_code(400);
        echo 'Preencha todos os campos.';
        exit;
    }
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo 'E-mail já cadastrado.';
        exit;
    }
    $stmt = $pdo->prepare('INSERT INTO usuarios (nome, email, senha_hash) VALUES (?, ?, ?)');
    $stmt->execute([$nome, $email, $senha_hash]);
    echo 'Cadastro realizado com sucesso!';
} else {
    http_response_code(405);
    echo 'Método não permitido.';
} 