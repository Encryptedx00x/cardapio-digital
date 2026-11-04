<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}
require_once '../db.php';
// Buscar categorias
$categorias = $pdo->query('SELECT * FROM categorias')->fetchAll();
// Buscar itens agrupados por categoria
$itens = $pdo->query('SELECT * FROM itens')->fetchAll();
$itens_por_categoria = [];
foreach ($itens as $item) {
    $itens_por_categoria[$item['categoria_id']][] = $item;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Cardápio</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css"/>
    <style>
        .card-selecionado {
            border: 2px solid #2563eb;
            box-shadow: 0 0 10px #2563eb;
            position: relative;
        }
        .check-icone {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #2563eb;
            color: #fff;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-3xl mx-auto py-8">
        <h2 class="text-2xl font-bold mb-6 text-center">Olá, <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>! Monte seu cardápio:</h2>
        <form id="menuForm" method="POST" action="../api/enviar_menu.php">
            <?php foreach ($categorias as $cat): ?>
                <div class="mb-8">
                    <h3 class="text-xl font-semibold mb-2"><?php echo htmlspecialchars($cat['nome']); ?></h3>
                    <div class="swiper-container swiper-<?php echo $cat['id']; ?>">
                        <div class="swiper-wrapper">
                            <?php if (!empty($itens_por_categoria[$cat['id']])): foreach ($itens_por_categoria[$cat['id']] as $item): ?>
                                <div class="swiper-slide">
                                    <div class="item-card bg-white rounded-lg shadow p-4 m-2 cursor-pointer transition relative" data-item-id="<?php echo $item['id']; ?>" data-categoria-id="<?php echo $cat['id']; ?>">
                                        <img src="../assets/<?php echo htmlspecialchars($item['imagem']); ?>" alt="<?php echo htmlspecialchars($item['nome']); ?>" class="w-32 h-32 object-cover rounded mb-2 mx-auto">
                                        <div class="text-center font-medium"><?php echo htmlspecialchars($item['nome']); ?></div>
                                        <input type="checkbox" name="itens[<?php echo $cat['id']; ?>][]" value="<?php echo $item['id']; ?>" class="hidden">
                                    </div>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                        <div class="swiper-button-next"></div>
                        <div class="swiper-button-prev"></div>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="text-center">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 text-lg">Enviar Menu</button>
            </div>
        </form>
        <div class="text-center mt-6">
            <a href="logout.php" class="text-gray-500 hover:underline">Sair</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
    <script>
        // Inicializar Swiper para cada categoria
        <?php foreach ($categorias as $cat): ?>
        new Swiper('.swiper-<?php echo $cat['id']; ?>', {
            slidesPerView: 2.2,
            spaceBetween: 16,
            navigation: {
                nextEl: '.swiper-<?php echo $cat['id']; ?> .swiper-button-next',
                prevEl: '.swiper-<?php echo $cat['id']; ?> .swiper-button-prev',
            },
            breakpoints: {
                640: { slidesPerView: 3.2 },
                1024: { slidesPerView: 4.2 },
            }
        });
        <?php endforeach; ?>
        // Seleção visual dos cards
        document.querySelectorAll('.item-card').forEach(card => {
            card.addEventListener('click', function() {
                const checkbox = this.querySelector('input[type=checkbox]');
                checkbox.checked = !checkbox.checked;
                this.classList.toggle('card-selecionado', checkbox.checked);
                if (checkbox.checked) {
                    if (!this.querySelector('.check-icone')) {
                        const check = document.createElement('span');
                        check.className = 'check-icone';
                        check.innerHTML = '✔';
                        this.appendChild(check);
                    }
                } else {
                    const check = this.querySelector('.check-icone');
                    if (check) check.remove();
                }
            });
        });
    </script>
</body>
</html> 