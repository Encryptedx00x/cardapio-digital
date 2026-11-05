<?php
declare(strict_types=1);

function start_app_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

function csrf_token(): string
{
    start_app_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function require_valid_csrf(): void
{
    $provided = (string)($_POST['csrf_token'] ?? '');
    if ($provided === '' || !hash_equals(csrf_token(), $provided)) {
        http_response_code(403);
        exit('Solicitação inválida. Atualize a página e tente novamente.');
    }
}

function require_customer(): void
{
    start_app_session();
    if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
        $base = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        header('Location: ' . $base . '/public/login.php');
        exit;
    }
}

function require_admin(): void
{
    start_app_session();
    if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
        $base = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        header('Location: ' . $base . '/admin/login.php');
        exit;
    }
}

function escape_html(string|int|float|null $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
