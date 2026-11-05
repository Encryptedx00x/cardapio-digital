<?php
// db.php - conexão centralizada com o banco de dados.
// Credenciais vêm de variáveis de ambiente (não versionadas). Veja db.example.php / README.
$host    = getenv('DB_HOST');
$db      = getenv('DB_NAME');
$user    = getenv('DB_USER');
$pass    = getenv('DB_PASS');
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';
$port = filter_var(getenv('DB_PORT') ?: '3306', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);

if (!$host || !$db || !$user || $pass === false || $pass === '' || $port === false) {
    throw new RuntimeException('Configure DB_HOST, DB_NAME, DB_USER e DB_PASS.');
}

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    throw new RuntimeException('Não foi possível conectar ao banco de dados.');
}
