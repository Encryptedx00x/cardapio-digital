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

$name = trim((string)($_POST['nome'] ?? ''));
$email = filter_var(trim((string)($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
$phone = trim((string)($_POST['telefone'] ?? ''));
$password = (string)($_POST['senha'] ?? '');
$restrictions = trim((string)($_POST['restricoes'] ?? ''));
if ($name === '' || strlen($name) > 120 || !$email || strlen($email) > 190 || strlen($phone) > 30 || strlen($password) < 12) {
    http_response_code(400);
    exit('Revise os dados. A senha deve ter pelo menos 12 caracteres.');
}

try {
    $stmt = $pdo->prepare(
        "INSERT INTO users (name, email, phone, dietary_restrictions, password_hash, role)
         VALUES (?, ?, ?, ?, ?, 'customer')"
    );
    $stmt->execute([$name, $email, $phone, $restrictions, password_hash($password, PASSWORD_DEFAULT)]);
    header('Location: ../public/login.php?registered=1');
    exit;
} catch (PDOException $error) {
    if ((string)$error->getCode() === '23000') {
        http_response_code(409);
        exit('Este e-mail já possui cadastro.');
    }
    throw $error;
}
