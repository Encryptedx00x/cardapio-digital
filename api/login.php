<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/security.php';
start_app_session();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}
require_valid_csrf();

$email = filter_var(trim((string)($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
$password = (string)($_POST['senha'] ?? '');
$stmt = $pdo->prepare('SELECT id, name, password_hash, role FROM users WHERE email = ?');
$stmt->execute([$email ?: '']);
$user = $stmt->fetch();
if (!$user || $user['role'] !== 'customer' || !password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    exit('E-mail ou senha inválidos.');
}
session_regenerate_id(true);
$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['role'] = 'customer';
header('Location: ../public/painel.php');
exit;
