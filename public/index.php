<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/security.php';
$categories = $pdo->query(
    'SELECT c.id, c.name, p.id product_id, p.name product_name, p.description, p.ingredients, p.price, p.image_url
     FROM categories c LEFT JOIN products p ON p.category_id = c.id AND p.available = 1
     ORDER BY c.sort_order, c.name, p.name'
)->fetchAll();
$grouped = [];
foreach ($categories as $row) {
    $grouped[$row['name']][] = $row;
}
start_app_session();
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Cardápio Digital</title><link rel="stylesheet" href="style.css"></head><body>
<header class="hero"><nav><a class="brand" href="index.php">Cardápio Digital</a><div><a href="login.php">Entrar</a><a class="button-link" href="register.php">Criar conta</a></div></nav><div class="hero-copy"><p class="eyebrow">Feito para compartilhar</p><h1>Nosso cardápio</h1><p>Conheça as opções disponíveis e monte seu menu.</p><a class="button-link" href="<?= !empty($_SESSION['user_id']) ? 'painel.php' : 'login.php' ?>">Montar um menu</a></div></header>
<main class="container menu-list">
<?php if (!$grouped): ?><p class="notice">O cardápio está sendo preparado.</p><?php endif; ?>
<?php foreach ($grouped as $category => $products): ?><section><h2><?= escape_html($category) ?></h2><div class="cards">
<?php foreach ($products as $product): if (!$product['product_id']) continue; ?><article class="card">
<?php if ($product['image_url'] && (str_starts_with($product['image_url'], 'https://') || str_starts_with($product['image_url'], '/uploads/'))): ?><img src="<?= escape_html($product['image_url']) ?>" alt="<?= escape_html($product['product_name']) ?>" loading="lazy"><?php endif; ?>
<div class="card-body"><h3><?= escape_html($product['product_name']) ?></h3><p><?= escape_html($product['description']) ?></p><strong>R$ <?= number_format((float)$product['price'], 2, ',', '.') ?></strong></div></article><?php endforeach; ?></div></section><?php endforeach; ?>
</main><footer><a href="../admin/login.php">Acesso administrativo</a></footer></body></html>
