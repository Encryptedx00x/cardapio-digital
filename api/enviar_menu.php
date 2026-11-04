<?php
require_once '../db.php';
session_start();
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo 'Não autorizado.';
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = $_SESSION['usuario_id'];
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('INSERT INTO menus (usuario_id, data_envio) VALUES (?, NOW())');
    $stmt->execute([$usuario_id]);
    $menu_id = $pdo->lastInsertId();
    $itens = $_POST['itens'] ?? [];
    foreach ($itens as $cat_itens) {
        foreach ($cat_itens as $item_id) {
            $stmt = $pdo->prepare('INSERT INTO menu_itens (menu_id, item_id) VALUES (?, ?)');
            $stmt->execute([$menu_id, $item_id]);
        }
    }
    $pdo->commit();
    echo 'Menu enviado com sucesso!';
} else {
    http_response_code(405);
    echo 'Método não permitido.';
} 