<?php
// apotek/api/search_products.php
require_once '../config.php';

header('Content-Type: application/json');

$search = isset($_GET['q']) ? $_GET['q'] : '';

if (empty($search)) {
    echo json_encode(['success' => false, 'message' => 'Masukkan kata kunci pencarian']);
    exit;
}

$query = "SELECT id_obat, nama_obat, kategori, harga, stok 
          FROM obat 
          WHERE (nama_obat LIKE ? OR kategori LIKE ?) 
          AND stok > 0 
          LIMIT 10";
$stmt = mysqli_prepare($conn, $query);
$search_term = "%$search%";
mysqli_stmt_bind_param($stmt, 'ss', $search_term, $search_term);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$products = [];
while ($row = mysqli_fetch_assoc($result)) {
    $products[] = $row;
}

echo json_encode([
    'success' => true,
    'products' => $products
]);
?>