<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID transaksi tidak valid']);
    exit();
}

$transactionId = (int)$_GET['id'];

// Get transaction details
$stmt = $conn->prepare("SELECT t.* FROM transaksi t WHERE t.id_transaksi = ?");
$stmt->bind_param("i", $transactionId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Transaksi tidak ditemukan']);
    exit();
}

$transaction = $result->fetch_assoc();

// Get transaction items 
$stmt = $conn->prepare("
    SELECT td.*, o.nama_obat, o.harga as harga_asli,
           (td.jumlah * td.harga_satuan) as subtotal
    FROM transaksi_detail td 
    JOIN obat o ON td.id_obat = o.id_obat
    WHERE td.id_transaksi = ?
");
$stmt->bind_param("i", $transactionId);
$stmt->execute();
$itemsResult = $stmt->get_result();
$items = [];

while ($item = $itemsResult->fetch_assoc()) {
    // Format item sesuai dengan yang diharapkan JavaScript
    $items[] = [
        'nama_obat' => $item['nama_obat'],
        'jumlah' => $item['jumlah'],
        'harga' => $item['harga_satuan'], 
        'subtotal' => $item['subtotal']
    ];
}

echo json_encode([
    'success' => true,
    'transaction' => $transaction,
    'items' => $items
]);

$stmt->close();
$conn->close();
?>