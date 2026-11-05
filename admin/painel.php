<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/security.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'create_category') {
        $name = trim((string)($_POST['name'] ?? ''));
        $max = filter_var($_POST['max_selection'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]);
        if ($name === '' || strlen($name) > 100 || $max === false) exit('Dados de categoria inválidos.');
        $stmt = $pdo->prepare('INSERT INTO categories (name, max_selection) VALUES (?, ?)');
        $stmt->execute([$name, $max]);
    } elseif ($action === 'delete_category') {
        $check = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id=?');
        $check->execute([(int)($_POST['id'] ?? 0)]);
        if ((int)$check->fetchColumn() > 0) {
            http_response_code(409);
            exit('Mova ou exclua os produtos desta categoria antes de removê-la.');
        }
        $stmt = $pdo->prepare('DELETE FROM categories WHERE id=?');
        $stmt->execute([(int)($_POST['id'] ?? 0)]);
    } elseif ($action === 'update_category') {
        $name = trim((string)($_POST['name'] ?? ''));
        $max = filter_var($_POST['max_selection'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]);
        if ($name === '' || strlen($name) > 100 || $max === false) exit('Dados de categoria inválidos.');
        $stmt = $pdo->prepare('UPDATE categories SET name=?,max_selection=? WHERE id=?');
        $stmt->execute([$name,$max,(int)($_POST['id'] ?? 0)]);
    } elseif ($action === 'create_product' || $action === 'update_product') {
        $id = (int)($_POST['id'] ?? 0);
        $category = (int)($_POST['category_id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $ingredients = trim((string)($_POST['ingredients'] ?? ''));
        $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);
        $image = trim((string)($_POST['image_url'] ?? ''));
        $validImage = $image === '' || str_starts_with($image, '/uploads/') ||
            (filter_var($image, FILTER_VALIDATE_URL) && parse_url($image, PHP_URL_SCHEME) === 'https');
        if (!$category || $name === '' || strlen($name) > 150 || $price === false || $price < 0 || $price > 99999999.99 || !$validImage) {
            exit('Dados de produto inválidos. Use apenas imagem HTTPS ou caminho em /uploads/.');
        }
        $categoryCheck = $pdo->prepare('SELECT id FROM categories WHERE id=?');
        $categoryCheck->execute([$category]);
        if (!$categoryCheck->fetch()) {
            http_response_code(400);
            exit('A categoria selecionada não existe.');
        }
        if ($action === 'create_product') {
            $stmt = $pdo->prepare('INSERT INTO products (category_id,name,description,ingredients,price,image_url) VALUES (?,?,?,?,?,?)');
            $stmt->execute([$category,$name,$description,$ingredients,$price,$image]);
        } else {
            $stmt = $pdo->prepare('UPDATE products SET category_id=?,name=?,description=?,ingredients=?,price=?,image_url=? WHERE id=?');
            $stmt->execute([$category,$name,$description,$ingredients,$price,$image,$id]);
        }
    } elseif ($action === 'toggle_product') {
        $stmt = $pdo->prepare('UPDATE products SET available = 1 - available WHERE id=?');
        $stmt->execute([(int)($_POST['id'] ?? 0)]);
    } elseif ($action === 'delete_product') {
        $stmt = $pdo->prepare('DELETE FROM products WHERE id=?');
        $stmt->execute([(int)($_POST['id'] ?? 0)]);
    } elseif ($action === 'order_status') {
        $statuses = ['new','reviewed','accepted','rejected','completed'];
        $status = (string)($_POST['status'] ?? '');
        if (!in_array($status, $statuses, true)) exit('Status inválido.');
        $stmt = $pdo->prepare('UPDATE orders SET status=? WHERE id=?');
        $stmt->execute([$status,(int)($_POST['id'] ?? 0)]);
    } elseif ($action === 'delete_order') {
        $stmt = $pdo->prepare('DELETE FROM orders WHERE id=?');
        $stmt->execute([(int)($_POST['id'] ?? 0)]);
    }
    header('Location: painel.php');
    exit;
}

$categories = $pdo->query('SELECT id,name,max_selection FROM categories ORDER BY sort_order,name')->fetchAll();
$products = $pdo->query('SELECT p.*,c.name category_name FROM products p JOIN categories c ON c.id=p.category_id ORDER BY c.sort_order,c.name,p.name')->fetchAll();
$orders = $pdo->query('SELECT o.*,u.name user_name,u.email,u.phone FROM orders o JOIN users u ON u.id=o.user_id ORDER BY o.created_at DESC')->fetchAll();
$orderItemsStmt = $pdo->prepare('SELECT category_name,product_name,price_at_order FROM order_items WHERE order_id=? ORDER BY id');
$statuses = ['new'=>'Novo','reviewed'=>'Revisado','accepted'=>'Aceito','rejected'=>'Recusado','completed'=>'Concluído'];
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Administração</title><link rel="stylesheet" href="../public/style.css"></head><body><main class="container">
<div class="admin-nav"><a href="../public/index.php">Ver cardápio público</a><a href="exportar.php">Exportar pedidos CSV</a><a href="logout.php">Sair</a></div><h1>Painel administrativo</h1>
<section><h2>Categorias</h2><form method="post" class="stack"><input type="hidden" name="csrf_token" value="<?= escape_html(csrf_token()) ?>"><input type="hidden" name="action" value="create_category"><label>Nome<input name="name" maxlength="100" required></label><label>Máximo de itens por pedido (0 = sem limite)<input type="number" name="max_selection" min="0" max="100" value="0"></label><button>Adicionar categoria</button></form>
<div class="cards"><?php foreach ($categories as $category): ?><article class="card card-body"><form method="post" class="stack"><input type="hidden" name="csrf_token" value="<?= escape_html(csrf_token()) ?>"><input type="hidden" name="action" value="update_category"><input type="hidden" name="id" value="<?= (int)$category['id'] ?>"><label>Nome<input name="name" maxlength="100" value="<?= escape_html($category['name']) ?>" required></label><label>Máximo de itens (0 = sem limite)<input type="number" name="max_selection" min="0" max="100" value="<?= (int)$category['max_selection'] ?>"></label><button>Salvar categoria</button></form><form method="post" onsubmit="return confirm('Excluir esta categoria? Só é possível quando ela não tiver produtos.')"><input type="hidden" name="csrf_token" value="<?= escape_html(csrf_token()) ?>"><input type="hidden" name="action" value="delete_category"><input type="hidden" name="id" value="<?= (int)$category['id'] ?>"><button>Excluir categoria</button></form></article><?php endforeach; ?></div></section>
<section><h2>Adicionar produto</h2><?php if (!$categories): ?><p>Cadastre uma categoria antes de adicionar produtos.</p><?php else: ?><form method="post" class="stack"><input type="hidden" name="csrf_token" value="<?= escape_html(csrf_token()) ?>"><input type="hidden" name="action" value="create_product"><label>Categoria<select name="category_id" required><?php foreach($categories as $c): ?><option value="<?= (int)$c['id'] ?>"><?= escape_html($c['name']) ?></option><?php endforeach; ?></select></label><label>Nome<input name="name" maxlength="150" required></label><label>Descrição<textarea name="description" rows="2"></textarea></label><label>Ingredientes<textarea name="ingredients" rows="2"></textarea></label><label>Preço<input name="price" type="number" min="0" step="0.01" value="0.00" required></label><label>Imagem (URL HTTPS ou /uploads/)<input name="image_url" placeholder="https://… ou /uploads/…"></label><button>Adicionar produto</button></form><?php endif; ?></section>
<section><h2>Gerenciar produtos</h2><div class="cards"><?php foreach($products as $product): ?><article class="card card-body"><form method="post" class="stack"><input type="hidden" name="csrf_token" value="<?= escape_html(csrf_token()) ?>"><input type="hidden" name="action" value="update_product"><input type="hidden" name="id" value="<?= (int)$product['id'] ?>"><label>Categoria<select name="category_id"><?php foreach($categories as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)$c['id']===(int)$product['category_id']?'selected':'' ?>><?= escape_html($c['name']) ?></option><?php endforeach; ?></select></label><label>Nome<input name="name" maxlength="150" value="<?= escape_html($product['name']) ?>" required></label><label>Descrição<textarea name="description" rows="2"><?= escape_html($product['description']) ?></textarea></label><label>Ingredientes<textarea name="ingredients" rows="2"><?= escape_html($product['ingredients']) ?></textarea></label><label>Preço<input name="price" type="number" min="0" step="0.01" value="<?= escape_html($product['price']) ?>" required></label><label>Imagem<input name="image_url" value="<?= escape_html($product['image_url']) ?>"></label><button>Salvar alterações</button></form><p><?= $product['available']?'Disponível':'Indisponível' ?></p><div class="admin-nav"><form method="post"><input type="hidden" name="csrf_token" value="<?= escape_html(csrf_token()) ?>"><input type="hidden" name="action" value="toggle_product"><input type="hidden" name="id" value="<?= (int)$product['id'] ?>"><button>Alternar disponibilidade</button></form><form method="post" onsubmit="return confirm('Excluir este produto?')"><input type="hidden" name="csrf_token" value="<?= escape_html(csrf_token()) ?>"><input type="hidden" name="action" value="delete_product"><input type="hidden" name="id" value="<?= (int)$product['id'] ?>"><button>Excluir produto</button></form></div></article><?php endforeach; ?></div></section>
<section><h2>Pedidos recebidos</h2><div class="cards"><?php foreach($orders as $order): ?><article class="card card-body"><h3>Pedido #<?= (int)$order['id'] ?> — <?= escape_html(str_replace('_',' ',$order['menu_type'])) ?></h3><p><?= escape_html($order['user_name']) ?> · <?= escape_html($order['email']) ?> · <?= escape_html($order['phone']) ?></p><p><?= escape_html($order['created_at']) ?></p><?php $orderItemsStmt->execute([$order['id']]); ?><ul><?php foreach($orderItemsStmt->fetchAll() as $item): ?><li><?= escape_html($item['category_name']) ?>: <?= escape_html($item['product_name']) ?> — R$ <?= number_format((float)$item['price_at_order'],2,',','.') ?></li><?php endforeach; ?></ul><p>Observações: <?= escape_html($order['observations']) ?></p><form method="post" class="stack"><input type="hidden" name="csrf_token" value="<?= escape_html(csrf_token()) ?>"><input type="hidden" name="action" value="order_status"><input type="hidden" name="id" value="<?= (int)$order['id'] ?>"><label>Status<select name="status"><?php foreach($statuses as $value=>$label): ?><option value="<?= $value ?>" <?= $order['status']===$value?'selected':'' ?>><?= escape_html($label) ?></option><?php endforeach; ?></select></label><button>Atualizar status</button></form><form method="post" onsubmit="return confirm('Excluir o pedido #<?= (int)$order['id'] ?>?')"><input type="hidden" name="csrf_token" value="<?= escape_html(csrf_token()) ?>"><input type="hidden" name="action" value="delete_order"><input type="hidden" name="id" value="<?= (int)$order['id'] ?>"><button>Excluir pedido</button></form></article><?php endforeach; ?></div></section>
<section><h2>Usuários cadastrados</h2><div style="overflow-x:auto"><table><thead><tr><th>Nome</th><th>E-mail</th><th>Telefone</th><th>Restrições informadas</th><th>Cadastro</th></tr></thead><tbody><?php foreach($pdo->query("SELECT name,email,phone,dietary_restrictions,created_at FROM users WHERE role='customer' ORDER BY created_at DESC") as $user): ?><tr><td><?= escape_html($user['name']) ?></td><td><?= escape_html($user['email']) ?></td><td><?= escape_html($user['phone']) ?></td><td><?= escape_html($user['dietary_restrictions']) ?></td><td><?= escape_html($user['created_at']) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
</main></body></html>
