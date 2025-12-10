<?php
require_once '../config.php';

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id']) || !is_numeric($input['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID transaksi tidak valid']);
    exit();
}

$transactionId = (int)$input['id'];

// Start transaction
$conn->begin_transaction();

try {
    // Delete transaction items first
    $stmt = $conn->prepare("DELETE FROM transaksi_detail WHERE id_transaksi = ?"); 
    $stmt->bind_param("i", $transactionId);
    $stmt->execute();
    $stmt->close();
    
    // Delete transaction
    $stmt = $conn->prepare("DELETE FROM transaksi WHERE id_transaksi = ?");
    $stmt->bind_param("i", $transactionId);
    $stmt->execute();
    $affectedRows = $stmt->affected_rows;
    $stmt->close();
    
    if ($affectedRows > 0) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Transaksi berhasil dihapus']);
    } else {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Transaksi tidak ditemukan']);
    }
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>