<?php
// apotek/api/get_product_detail.php
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID produk tidak valid']);
    exit();
}

$product_id = (int)$_GET['id'];
$query = "SELECT * FROM obat WHERE id_obat = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($product = mysqli_fetch_assoc($result)) {
    echo json_encode([
        'success' => true,
        'product' => $product
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Produk tidak ditemukan'
    ]);
}

mysqli_stmt_close($stmt);
?>