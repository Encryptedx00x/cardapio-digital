<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../db.php';
// Buscar menus enviados
$sql = 'SELECT m.id, m.data_envio, u.nome as usuario_nome, u.email
        FROM menus m
        JOIN usuarios u ON m.usuario_id = u.id
        ORDER BY m.data_envio DESC';
$menus = $pdo->query($sql)->fetchAll();
// Buscar itens de cada menu
$menu_itens = [];
foreach ($menus as $menu) {
    $stmt = $pdo->prepare('SELECT i.nome, c.nome as categoria
        FROM menu_itens mi
        JOIN itens i ON mi.item_id = i.id
        JOIN categorias c ON i.categoria_id = c.id
        WHERE mi.menu_id = ?');
    $stmt->execute([$menu['id']]);
    $menu_itens[$menu['id']] = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - Cardápio</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-5xl mx-auto py-8">
        <h2 class="text-2xl font-bold mb-6 text-center">Menus Enviados</h2>
        <div class="mb-4 flex gap-4 justify-end">
            <a href="exportar.php?tipo=csv" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Exportar CSV</a>
            <a href="exportar.php?tipo=pdf" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Exportar PDF</a>
        </div>
        <div class="bg-white rounded shadow p-4 overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr>
                        <th class="px-4 py-2">Usuário</th>
                        <th class="px-4 py-2">E-mail</th>
                        <th class="px-4 py-2">Data</th>
                        <th class="px-4 py-2">Itens Selecionados</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($menus as $menu): ?>
                    <tr class="border-t">
                        <td class="px-4 py-2"><?php echo htmlspecialchars($menu['usuario_nome']); ?></td>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($menu['email']); ?></td>
                        <td class="px-4 py-2"><?php echo date('d/m/Y H:i', strtotime($menu['data_envio'])); ?></td>
                        <td class="px-4 py-2">
                            <ul>
                            <?php foreach ($menu_itens[$menu['id']] as $item): ?>
                                <li><?php echo htmlspecialchars($item['categoria'] . ': ' . $item['nome']); ?></li>
                            <?php endforeach; ?>
                            </ul>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="text-center mt-6">
            <a href="logout.php" class="text-gray-500 hover:underline">Sair</a>
        </div>
    </div>
</body>
</html> 