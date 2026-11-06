<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/security.php';
require_customer();
$menuTypes = [
    'agape_simples' => 'Ágape simples',
    'agape_iniciacao' => 'Ágape de iniciação',
    'jantar_ritualistico' => 'Jantar ritualístico',
];
$type = (string)($_GET['tipo'] ?? '');
if (!isset($menuTypes[$type])) {
    ?>
    <!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Escolha o menu</title><link rel="stylesheet" href="style.css"></head><body><main class="container"><div class="admin-nav"><a href="index.php">Cardápio</a><a href="logout.php">Sair</a></div><p class="eyebrow">Olá, <?= escape_html($_SESSION['user_name'] ?? '') ?></p><h1>Que tipo de menu deseja montar?</h1><div class="cards">
    <?php foreach ($menuTypes as $key => $label): ?><a class="card card-body" href="painel.php?tipo=<?= escape_html($key) ?>"><h2><?= escape_html($label) ?></h2><span>Ver opções →</span></a><?php endforeach; ?>
    </div></main></body></html>
    <?php exit;
}
$stmt = $pdo->query(
    'SELECT c.id category_id, c.name category_name, c.max_selection, p.id, p.name, p.description, p.ingredients, p.price
     FROM categories c LEFT JOIN products p ON p.category_id=c.id AND p.available=1
     ORDER BY c.sort_order, c.name, p.name'
);
$groups = [];
foreach ($stmt->fetchAll() as $row) {
    $groups[$row['category_id']]['name'] = $row['category_name'];
    $groups[$row['category_id']]['max'] = (int)$row['max_selection'];
    if ($row['id']) $groups[$row['category_id']]['products'][] = $row;
}
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Monte seu menu</title><link rel="stylesheet" href="style.css"></head><body><main class="container"><div class="admin-nav"><a href="painel.php">← Trocar tipo de menu</a><a href="index.php">Cardápio público</a><a href="logout.php">Sair</a></div><h1><?= escape_html($menuTypes[$type]) ?></h1><p class="muted">Selecione os itens desejados. Toque no nome para conferir os ingredientes.</p>
<form method="post" action="../api/enviar_menu.php" class="stack"><input type="hidden" name="csrf_token" value="<?= escape_html(csrf_token()) ?>"><input type="hidden" name="tipo" value="<?= escape_html($type) ?>">
<?php foreach ($groups as $categoryId => $group): ?><fieldset data-max="<?= $group['max'] ?>"><legend><strong><?= escape_html($group['name']) ?></strong><?php if ($group['max']): ?> <span class="muted">(máximo <?= $group['max'] ?>)</span><?php endif; ?></legend>
<?php foreach ($group['products'] ?? [] as $product): ?><label class="product-choice"><input type="checkbox" name="itens[]" value="<?= (int)$product['id'] ?>" data-category="<?= (int)$categoryId ?>"><span><button type="button" class="ingredient-button" data-name="<?= escape_html($product['name']) ?>" data-ingredients="<?= escape_html($product['ingredients']) ?>" style="background:none;color:#315c41!important;padding:0;text-align:left"><?= escape_html($product['name']) ?></button><small class="muted"> <?= escape_html($product['description']) ?></small></span><strong>R$ <?= number_format((float)$product['price'], 2, ',', '.') ?></strong></label><?php endforeach; ?></fieldset><?php endforeach; ?>
<label>Observações<textarea name="observacoes" rows="3" maxlength="2000"></textarea></label><button type="submit" onclick="return confirm('Enviar este menu para o administrador?')">Enviar menu</button></form>
</main><dialog id="ingredients"><form method="dialog"><h2 id="ingredient-name"></h2><p id="ingredient-list"></p><button>Fechar</button></form></dialog>
<script>
document.querySelectorAll('.ingredient-button').forEach(button=>button.addEventListener('click',event=>{event.preventDefault();event.stopPropagation();document.getElementById('ingredient-name').textContent=button.dataset.name;document.getElementById('ingredient-list').textContent=button.dataset.ingredients||'Ingredientes não informados';document.getElementById('ingredients').showModal()}));
document.querySelectorAll('input[type=checkbox][data-category]').forEach(box=>box.addEventListener('change',()=>{const group=box.closest('fieldset'),max=Number(group.dataset.max),checked=group.querySelectorAll('input:checked').length;if(max&&checked>max){box.checked=false;alert(`Selecione no máximo ${max} item(ns) nesta categoria.`)}}));
</script></body></html>
