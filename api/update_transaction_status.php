<?php
// apotek/api/update_transaction_status.php
require_once '../config.php';

header('Content-Type: application/json');

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';

if ($id <= 0 || !in_array($status, ['pending', 'lunas', 'dibatalkan'])) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit;
}

$query = "UPDATE transaksi SET status_pembayaran = ? WHERE id_transaksi = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'si', $status, $id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Status berhasil diperbarui']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal memperbarui status']);
}
?>