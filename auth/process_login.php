<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Metode request tidak valid!";
    header("Location: login.php");
    exit();
}

$username = mysqli_real_escape_string($conn, $_POST['username']);
$password = $_POST['password'];

if (empty($username) || empty($password)) {
    $_SESSION['error'] = "Username dan password harus diisi!";
    $_SESSION['login_username'] = $username;
    header("Location: login.php");
    exit();
}

$sql = "SELECT * FROM pegawai WHERE username = '$username' AND status = 'active'";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) == 1) {
    $user = mysqli_fetch_assoc($result);
    
    if (password_verify($password, $user['password'])) {
        $_SESSION['pegawai_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['status'] = $user['status'];
        $_SESSION['role'] = $user['role'];
        
        // Update last login
        $update_sql = "UPDATE pegawai SET last_login = NOW() WHERE id = {$user['id']}";
        mysqli_query($conn, $update_sql);
        
        // REDIRECT KE ADMIN DASHBOARD YANG BENAR
        header("Location: " . $base_url . "/screen/admin/");
        exit();
    }
}

$_SESSION['error'] = "Username atau password salah!";
$_SESSION['login_username'] = $username;
header("Location: login.php");
exit();
?>