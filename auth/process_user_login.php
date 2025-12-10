<?php
require_once '../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Metode request tidak valid!";
    header("Location: user_login.php");
    exit();
}

$email = mysqli_real_escape_string($conn, $_POST['email']);
$password = $_POST['password'];

if (empty($email) || empty($password)) {
    $_SESSION['error'] = "Email dan password harus diisi!";
    $_SESSION['login_email'] = $email;
    header("Location: user_login.php");
    exit();
}

// Cek user di database
$sql = "SELECT * FROM users WHERE email = '$email'";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) === 1) {
    $user = mysqli_fetch_assoc($result);
    
    if (password_verify($password, $user['password'])) {
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nama'] = $user['nama_lengkap'];
        $_SESSION['user_email'] = $user['email'];
        
        // Redirect ke dashboard atau home
        header("Location: " . $base_url . "/screen/dashboard/");
        exit();
    }
}

$_SESSION['error'] = "Email atau password salah!";
$_SESSION['login_email'] = $email;
header("Location: user_login.php");
exit();
?>
