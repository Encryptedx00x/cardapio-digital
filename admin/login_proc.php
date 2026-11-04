<?php
require_once '../db.php';
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    // Apenas emails terminando com @admin.com são administradores
    if (!str_ends_with($email, '@admin.com')) {
        echo 'Acesso restrito a administradores.';
        exit;
    }
    $stmt = $pdo->prepare('SELECT id, nome, senha_hash FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($senha, $user['senha_hash'])) {
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_nome'] = $user['nome'];
        header('Location: painel.php');
        exit;
    } else {
        echo 'E-mail ou senha inválidos.';
    }
} else {
    http_response_code(405);
    echo 'Método não permitido.';
} 