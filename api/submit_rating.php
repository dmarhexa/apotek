<?php
header('Content-Type: application/json');
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$nama_user = isset($_POST['nama_user']) ? trim($_POST['nama_user']) : '';
$bintang = isset($_POST['bintang']) ? intval($_POST['bintang']) : 0;
$komentar = isset($_POST['komentar']) ? trim($_POST['komentar']) : '';

// Validation
if (empty($nama_user)) {
    echo json_encode(['status' => 'error', 'message' => 'Nama tidak boleh kosong']);
    exit;
}

if ($bintang < 1 || $bintang > 5) {
    echo json_encode(['status' => 'error', 'message' => 'Rating bintang harus antara 1 dan 5']);
    exit;
}

// Insert to database
$stmt = $conn->prepare("INSERT INTO rating (nama_user, bintang, komentar) VALUES (?, ?, ?)");
$stmt->bind_param("sis", $nama_user, $bintang, $komentar);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Rating berhasil dikirim! Terima kasih atas masukan Anda.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan rating: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
