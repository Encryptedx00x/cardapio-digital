<?php
declare(strict_types=1);

require_once __DIR__ . '/../db.php';

if (PHP_SAPI !== 'cli' || $argc !== 3) {
    fwrite(STDERR, "Uso: ADMIN_PASSWORD='senha forte' php bin/create_admin.php \"Nome\" email\n");
    exit(2);
}

[$script, $name, $email] = $argv;
$password = (string)getenv('ADMIN_PASSWORD');
$email = filter_var(trim($email), FILTER_VALIDATE_EMAIL);
if (trim($name) === '' || strlen($name) > 120 || !$email || strlen($password) < 12) {
    fwrite(STDERR, "Configure ADMIN_PASSWORD com pelo menos 12 caracteres e informe um e-mail válido.\n");
    exit(2);
}

$statement = $pdo->prepare(
    "INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'admin')"
);
try {
    $statement->execute([trim($name), $email, password_hash($password, PASSWORD_DEFAULT)]);
    fwrite(STDOUT, "Administrador criado.\n");
} catch (PDOException $error) {
    if ((string)$error->getCode() === '23000') {
        fwrite(STDERR, "Esse e-mail já está cadastrado.\n");
        exit(1);
    }
    throw $error;
}
