<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/security.php';
require_customer();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}
require_valid_csrf();
$types = ['agape_simples', 'agape_iniciacao', 'jantar_ritualistico'];
$type = (string)($_POST['tipo'] ?? '');
$ids = $_POST['itens'] ?? [];
if (!in_array($type, $types, true) || !is_array($ids) || count($ids) > 100 || count($ids) === 0) {
    http_response_code(400);
    exit('Escolha um tipo de menu e ao menos um item.');
}
$ids = array_values(array_unique(array_filter(array_map(
    static fn($id) => filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]),
    $ids
))));
if (!$ids) {
    http_response_code(400);
    exit('Seleção inválida.');
}
$marks = implode(',', array_fill(0, count($ids), '?'));
$query = $pdo->prepare(
    "SELECT p.id, p.name, p.price, c.id category_id, c.name category_name, c.max_selection
     FROM products p JOIN categories c ON c.id=p.category_id
     WHERE p.available=1 AND p.id IN ($marks)"
);
$query->execute($ids);
$products = $query->fetchAll();
if (count($products) !== count($ids)) {
    http_response_code(400);
    exit('Um ou mais itens foram removidos ou estão indisponíveis. Atualize a página.');
}
$counts = [];
foreach ($products as $product) $counts[$product['category_id']] = ($counts[$product['category_id']] ?? 0) + 1;
foreach ($products as $product) {
    $max = (int)$product['max_selection'];
    if ($max && $counts[$product['category_id']] > $max) {
        http_response_code(400);
        exit('A seleção ultrapassa o limite de uma categoria.');
    }
}

try {
    $pdo->beginTransaction();
    $order = $pdo->prepare('INSERT INTO orders (user_id, menu_type, observations) VALUES (?, ?, ?)');
    $order->execute([(int)$_SESSION['user_id'], $type, trim((string)($_POST['observacoes'] ?? ''))]);
    $orderId = (int)$pdo->lastInsertId();
    $item = $pdo->prepare(
        'INSERT INTO order_items (order_id, product_id, product_name, category_name, price_at_order)
         VALUES (?, ?, ?, ?, ?)'
    );
    foreach ($products as $product) {
        $item->execute([$orderId, $product['id'], $product['name'], $product['category_name'], $product['price']]);
    }
    $pdo->commit();
} catch (Throwable $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $error;
}
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Menu enviado</title><link rel="stylesheet" href="../public/style.css"></head><body><main class="container narrow"><h1>Menu enviado</h1><p>Seu menu #<?= $orderId ?> foi recebido para análise.</p><a class="button-link" href="../public/painel.php">Voltar</a></main></body></html>
