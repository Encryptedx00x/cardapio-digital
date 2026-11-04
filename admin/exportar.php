<?php
require_once '../db.php';
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
$tipo = $_GET['tipo'] ?? 'csv';
$sql = 'SELECT m.id, m.data_envio, u.nome as usuario_nome, u.email
        FROM menus m
        JOIN usuarios u ON m.usuario_id = u.id
        ORDER BY m.data_envio DESC';
$menus = $pdo->query($sql)->fetchAll();
$dados = [];
foreach ($menus as $menu) {
    $stmt = $pdo->prepare('SELECT i.nome, c.nome as categoria
        FROM menu_itens mi
        JOIN itens i ON mi.item_id = i.id
        JOIN categorias c ON i.categoria_id = c.id
        WHERE mi.menu_id = ?');
    $stmt->execute([$menu['id']]);
    $itens = $stmt->fetchAll();
    $itens_str = [];
    foreach ($itens as $item) {
        $itens_str[] = $item['categoria'] . ': ' . $item['nome'];
    }
    $dados[] = [
        $menu['usuario_nome'],
        $menu['email'],
        date('d/m/Y H:i', strtotime($menu['data_envio'])),
        implode(' | ', $itens_str)
    ];
}
if ($tipo === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="menus.csv"');
    $f = fopen('php://output', 'w');
    fputcsv($f, ['Usuário', 'E-mail', 'Data', 'Itens Selecionados']);
    foreach ($dados as $linha) {
        fputcsv($f, $linha);
    }
    fclose($f);
    exit;
} elseif ($tipo === 'pdf') {
    require_once '../assets/fpdf/fpdf.php';
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,10,'Menus Enviados',0,1,'C');
    $pdf->SetFont('Arial','',10);
    foreach ($dados as $linha) {
        $pdf->Cell(45,8,$linha[0],1);
        $pdf->Cell(55,8,$linha[1],1);
        $pdf->Cell(35,8,$linha[2],1);
        $pdf->Cell(55,8,mb_convert_encoding($linha[3],'ISO-8859-1','UTF-8'),1);
        $pdf->Ln();
    }
    $pdf->Output('D','menus.pdf');
    exit;
} else {
    echo 'Tipo de exportação inválido.';
} 