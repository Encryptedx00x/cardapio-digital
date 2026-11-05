<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/security.php';
require_admin();
$rows = $pdo->query(
    'SELECT o.id,o.menu_type,o.status,o.created_at,u.name,u.email,u.phone,o.observations,
            GROUP_CONCAT(CONCAT(oi.category_name, ": ", oi.product_name) SEPARATOR " | ") items
     FROM orders o JOIN users u ON u.id=o.user_id
     LEFT JOIN order_items oi ON oi.order_id=o.id
     GROUP BY o.id ORDER BY o.created_at DESC'
)->fetchAll();
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="pedidos.csv"');
$file = fopen('php://output', 'w');
fwrite($file, "\xEF\xBB\xBF");
fputcsv($file, ['Pedido','Tipo de menu','Status','Criado em','Nome','E-mail','Telefone','Itens','Observações'], ';');
foreach ($rows as $row) {
    $safe = array_map(static function ($value): string {
        $text = (string)$value;
        return preg_match('/^[=+@\-\t\r]/u', $text) ? "'" . $text : $text;
    }, array_values($row));
    fputcsv($file, $safe, ';');
}
fclose($file);
