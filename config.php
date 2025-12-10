<?php
// apotek/config.php - di root folder
$host = "localhost";
$username = "root";
$password = "";
$database = "apotek_db";

// Buat koneksi
$conn = mysqli_connect($host, $username, $password, $database);

// Cek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, "utf8");

// Mulai session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set base URL untuk memudahkan
$base_url = "http://" . $_SERVER['HTTP_HOST'] . "/apotek";

// Set base path
$base_path = $_SERVER['DOCUMENT_ROOT'] . "/apotek";

// Fungsi untuk redirect - dengan cek jika belum ada
if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: " . $url);
        exit();
    }
}

// Fungsi untuk menampilkan pesan alert - dengan cek jika belum ada
if (!function_exists('set_message')) {
    function set_message($message, $type = "success") {
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $type;
    }
}

// Fungsi untuk mendapatkan pesan - dengan cek jika belum ada
if (!function_exists('get_message')) {
    function get_message() {
        if (isset($_SESSION['message'])) {
            $message = $_SESSION['message'];
            $type = $_SESSION['message_type'];
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            return "<div class='alert alert-$type'>$message</div>";
        }
        return "";
    }
}

// Function to get correct file path - dengan cek jika belum ada
if (!function_exists('get_asset_path')) {
    function get_asset_path($path) {
        global $base_path;
        return $base_path . ltrim($path, '/');
    }
}

// Fungsi untuk format uang - fungsi tambahan
if (!function_exists('format_rupiah')) {
    function format_rupiah($number) {
        return 'Rp ' . number_format($number, 0, ',', '.');
    }
}
?>