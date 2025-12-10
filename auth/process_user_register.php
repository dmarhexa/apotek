<?php
require_once '../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Metode request tidak valid!";
    header("Location: user_register.php");
    exit();
}

$nama_lengkap = mysqli_real_escape_string($conn, trim($_POST['nama_lengkap']));
$email = mysqli_real_escape_string($conn, trim($_POST['email']));
$no_hp = mysqli_real_escape_string($conn, trim($_POST['no_hp']));
$password = $_POST['password'];

// Validasi sederhana
if (empty($nama_lengkap) || empty($email) || empty($password)) {
    $_SESSION['error'] = "Data wajib diisi!";
    header("Location: user_register.php");
    exit();
}

// Cek email duplikat
$check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
if (mysqli_num_rows($check) > 0) {
    $_SESSION['error'] = "Email sudah terdaftar!";
    header("Location: user_register.php");
    exit();
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO users (nama_lengkap, email, no_hp, password) VALUES ('$nama_lengkap', '$email', '$no_hp', '$hashed_password')";

if (mysqli_query($conn, $sql)) {
    $_SESSION['success'] = "Registrasi berhasil! Silakan login.";
    header("Location: user_login.php");
    exit();
} else {
    $_SESSION['error'] = "Gagal mendaftar: " . mysqli_error($conn);
    header("Location: user_register.php");
    exit();
}
?>
