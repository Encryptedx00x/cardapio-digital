<?php
require_once '../db.php';

// Inserir categorias
$categorias = ['Entrada', 'Prato Principal', 'Sobremesa', 'Bebida'];
foreach ($categorias as $cat) {
    $stmt = $pdo->prepare('INSERT IGNORE INTO categorias (nome) VALUES (?)');
    $stmt->execute([$cat]);
}

// Buscar IDs das categorias
$stmt = $pdo->query('SELECT id, nome FROM categorias');
$cat_ids = [];
foreach ($stmt->fetchAll() as $row) {
    $cat_ids[$row['nome']] = $row['id'];
}

// Inserir itens de exemplo
$itens = [
    ['Salada', 'img/salada.jpg', 'Entrada'],
    ['Bruschetta', 'img/bruschetta.jpg', 'Entrada'],
    ['Lasanha', 'img/lasanha.jpg', 'Prato Principal'],
    ['Frango Grelhado', 'img/frango.jpg', 'Prato Principal'],
    ['Pudim', 'img/pudim.jpg', 'Sobremesa'],
    ['Mousse de Chocolate', 'img/mousse.jpg', 'Sobremesa'],
    ['Suco de Laranja', 'img/suco.jpg', 'Bebida'],
    ['Refrigerante', 'img/refrigerante.jpg', 'Bebida'],
];
foreach ($itens as $item) {
    $stmt = $pdo->prepare('INSERT IGNORE INTO itens (nome, imagem, categoria_id) VALUES (?, ?, ?)');
    $stmt->execute([$item[0], $item[1], $cat_ids[$item[2]]]);
}
echo 'Categorias e itens inseridos com sucesso!'; 