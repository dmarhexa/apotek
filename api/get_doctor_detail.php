<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID dokter tidak ditemukan']);
    exit;
}

$doctor_id = mysqli_real_escape_string($conn, $_GET['id']);

$query = "SELECT * FROM dokter WHERE id_dokter = '$doctor_id'";
$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    exit;
}

if (mysqli_num_rows($result) > 0) {
    $doctor = mysqli_fetch_assoc($result);
    echo json_encode(['success' => true, 'doctor' => $doctor]);
} else {
    echo json_encode(['success' => false, 'message' => 'Dokter tidak ditemukan']);
}

mysqli_close($conn);
?>