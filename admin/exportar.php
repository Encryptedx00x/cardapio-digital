<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/security.php';
require_admin();
$rows = $pdo->query(
    'SELECT o.id,o.menu_type,o.status,o.created_at,u.name,u.email,u.phone,o.observations
     FROM orders o JOIN users u ON u.id=o.user_id
     ORDER BY o.created_at DESC,o.id DESC'
)->fetchAll();
$itemsQuery = $pdo->prepare('SELECT category_name,product_name FROM order_items WHERE order_id=? ORDER BY id');
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="pedidos.csv"');
$file = fopen('php://output', 'w');
fwrite($file, "\xEF\xBB\xBF");
fputcsv($file, ['Pedido','Tipo de menu','Status','Criado em','Nome','E-mail','Telefone','Itens','Observações'], ';', '"', '');
foreach ($rows as $row) {
    $itemsQuery->execute([$row['id']]);
    $items = array_map(static fn(array $item): string => $item['category_name'] . ': ' . $item['product_name'], $itemsQuery->fetchAll());
    $values = [$row['id'],$row['menu_type'],$row['status'],$row['created_at'],$row['name'],$row['email'],$row['phone'],implode(' | ', $items),$row['observations']];
    $safe = array_map(static function ($value): string {
        $text = (string)$value;
        return preg_match('/^[=+@\-\t\r]/u', $text) ? "'" . $text : $text;
    }, $values);
    fputcsv($file, $safe, ';', '"', '');
}
fclose($file);
