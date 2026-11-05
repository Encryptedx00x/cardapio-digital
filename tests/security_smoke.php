<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/security.php';

function check(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: $message\n");
        exit(1);
    }
    fwrite(STDOUT, "PASS: $message\n");
}

$token = csrf_token();
check(strlen($token) === 64, 'CSRF token is cryptographically sized');
$_POST['csrf_token'] = $token;
require_valid_csrf();
check(true, 'valid CSRF token accepted');
check(escape_html('<script>') === '&lt;script&gt;', 'HTML output is escaped');
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'customer';
require_customer();
check(true, 'customer authorization accepts customer role');
$_SESSION['role'] = 'admin';
require_admin();
check(true, 'admin authorization accepts admin role');
