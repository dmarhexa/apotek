<?php
// apotek/api/create_transaksi.php - FIXED VERSION (Update nama tabel)
require_once '../config.php';

// Start session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Debug log
$debug_log = [];
$debug_log[] = "=== CREATE TRANSAKSI DEBUG ===";
$debug_log[] = "POST data: " . print_r($_POST, true);
$debug_log[] = "SESSION data: " . print_r($_SESSION, true);

// Get data from form
$nama_pembeli = isset($_POST['nama_pembeli']) ? trim(mysqli_real_escape_string($conn, $_POST['nama_pembeli'])) : '';
$metode_pembayaran = isset($_POST['metode_pembayaran']) ? trim(mysqli_real_escape_string($conn, $_POST['metode_pembayaran'])) : '';
$alamat_pengiriman = isset($_POST['alamat_pengiriman']) ? trim(mysqli_real_escape_string($conn, $_POST['alamat_pengiriman'])) : '';
$catatan = isset($_POST['catatan']) ? trim(mysqli_real_escape_string($conn, $_POST['catatan'])) : '';
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

// Hapus field total_harga dari POST karena akan dihitung ulang
unset($_POST['total_harga']);

// Get user_id from session (jika login)
$id_pengguna = null;
if (isset($_SESSION['pegawai_id'])) {
    $id_pengguna = $_SESSION['pegawai_id'];
}

$debug_log[] = "id_pengguna: " . ($id_pengguna ? $id_pengguna : 'null (guest)');
$debug_log[] = "product_id: $product_id, quantity: $quantity";

// Validation
$errors = [];
if (empty($nama_pembeli)) {
    $errors[] = 'Nama pembeli harus diisi';
}

if (empty($metode_pembayaran)) {
    $errors[] = 'Metode pembayaran harus dipilih';
}

if ($product_id <= 0) {
    $errors[] = 'ID produk tidak valid';
}

if ($quantity <= 0) {
    $errors[] = 'Jumlah tidak valid';
}

if (!empty($errors)) {
    $debug_log[] = "Validation errors: " . implode(', ', $errors);
    file_put_contents('debug.log', implode("\n", $debug_log) . "\n", FILE_APPEND);
    
    echo json_encode([
        'success' => false, 
        'message' => implode(', ', $errors)
    ]);
    exit;
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // 1. Get product details and calculate total
    $product_query = "SELECT harga, stok, nama_obat FROM obat WHERE id_obat = ? FOR UPDATE";
    $product_stmt = mysqli_prepare($conn, $product_query);
    
    if (!$product_stmt) {
        throw new Exception('Prepare product query failed: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($product_stmt, 'i', $product_id);
    
    if (!mysqli_stmt_execute($product_stmt)) {
        throw new Exception('Execute product query failed: ' . mysqli_error($conn));
    }
    
    $product_result = mysqli_stmt_get_result($product_stmt);
    $product = mysqli_fetch_assoc($product_result);
    
    if (!$product) {
        throw new Exception('Produk tidak ditemukan dengan ID: ' . $product_id);
    }
    
    $debug_log[] = "Product found: {$product['nama_obat']}, Price: {$product['harga']}, Stock: {$product['stok']}";
    
    // Check stock
    if ($product['stok'] < $quantity) {
        throw new Exception('Stok tidak mencukupi. Stok tersedia: ' . $product['stok']);
    }
    
    // Calculate total
    $total_harga = $product['harga'] * $quantity;
    $harga_satuan = $product['harga'];
    
    $debug_log[] = "Calculated: harga_satuan=$harga_satuan, total_harga=$total_harga, quantity=$quantity";
    
    // 2. Insert into transaksi table - SELALU LUNAS
    $query = "INSERT INTO transaksi (
                id_pengguna, 
                nama_pembeli, 
                total_harga, 
                status_pembayaran, 
                metode_pembayaran, 
                alamat_pengiriman, 
                catatan
              ) VALUES (?, ?, ?, 'lunas', ?, ?, ?)";
    
    $debug_log[] = "Transaction SQL: $query";
    
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        throw new Exception('Prepare statement failed: ' . mysqli_error($conn));
    }
    
    // Bind parameters
    mysqli_stmt_bind_param($stmt, 'isdsss', 
        $id_pengguna, 
        $nama_pembeli, 
        $total_harga, 
        $metode_pembayaran, 
        $alamat_pengiriman, 
        $catatan
    );
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Execute transaksi failed: ' . mysqli_error($conn));
    }
    
    $transaction_id = mysqli_insert_id($conn);
    $debug_log[] = "Transaction created with ID: $transaction_id";
    
    // 3. Insert into transaksi_detail (sesuai nama tabel di database)
    $detail_query = "INSERT INTO transaksi_detail (
                      id_transaksi, 
                      id_obat, 
                      jumlah, 
                      harga_satuan, 
                      subtotal
                    ) VALUES (?, ?, ?, ?, ?)";
    
    $debug_log[] = "Detail SQL: $detail_query";
    
    $detail_stmt = mysqli_prepare($conn, $detail_query);
    if (!$detail_stmt) {
        throw new Exception('Prepare detail statement failed: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($detail_stmt, 'iiidd', 
        $transaction_id, 
        $product_id, 
        $quantity, 
        $harga_satuan, 
        $total_harga
    );
    
    if (!mysqli_stmt_execute($detail_stmt)) {
        throw new Exception('Execute detail failed: ' . mysqli_error($conn));
    }
    
    $debug_log[] = "Detail transaksi saved";
    
    // 4. Update product stock
    $update_stock_query = "UPDATE obat SET stok = stok - ? WHERE id_obat = ?";
    $update_stmt = mysqli_prepare($conn, $update_stock_query);
    
    if (!$update_stmt) {
        throw new Exception('Prepare update stock failed: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($update_stmt, 'ii', $quantity, $product_id);
    
    if (!mysqli_stmt_execute($update_stmt)) {
        throw new Exception('Update stock failed: ' . mysqli_error($conn));
    }
    
    $debug_log[] = "Product stock updated";
    
    // Commit transaction
    mysqli_commit($conn);
    $debug_log[] = "Transaction committed successfully";
    
    // 5. Get complete transaction details for response
    // PERBAIKAN: Gunakan transaksi_detail (bukan detail_transaksi)
    $details_query = "SELECT 
        t.id_transaksi,
        t.nama_pembeli,
        t.total_harga,
        t.metode_pembayaran,
        t.alamat_pengiriman,
        t.catatan,
        t.tanggal_transaksi,
        t.status_pembayaran,
        td.jumlah,
        td.harga_satuan,
        td.subtotal,
        o.nama_obat,
        o.kategori
    FROM transaksi t
    LEFT JOIN transaksi_detail td ON t.id_transaksi = td.id_transaksi
    LEFT JOIN obat o ON td.id_obat = o.id_obat
    WHERE t.id_transaksi = ?";
    
    $details_stmt = mysqli_prepare($conn, $details_query);
    mysqli_stmt_bind_param($details_stmt, 'i', $transaction_id);
    mysqli_stmt_execute($details_stmt);
    $details_result = mysqli_stmt_get_result($details_stmt);
    
    $transaction_details = [];
    while ($detail = mysqli_fetch_assoc($details_result)) {
        $transaction_details[] = $detail;
    }
    
    $debug_log[] = "Transaction details fetched: " . count($transaction_details) . " items";
    
    // Save debug log
    file_put_contents('debug_transaksi.log', implode("\n", $debug_log) . "\n\n", FILE_APPEND);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Transaksi berhasil dibuat',
        'transaction_id' => $transaction_id,
        'transaction_details' => $transaction_details
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    
    $debug_log[] = "Transaction ERROR: " . $e->getMessage();
    file_put_contents('debug_transaksi_error.log', implode("\n", $debug_log) . "\n\n", FILE_APPEND);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>