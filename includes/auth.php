<?php
// Check jika user sudah login
function isLoggedIn() {
    return isset($_SESSION['pegawai_id']) && !empty($_SESSION['pegawai_id']);
}

// Redirect jika belum login (KE FOLDER /auth/login.php)
function requireLogin() {
    if (!isLoggedIn()) {
        // Simpan URL saat ini untuk redirect setelah login
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        $_SESSION['error'] = "Anda harus login terlebih dahulu!";
        header("Location: /apotek/auth/login.php"); 
        exit();
    }
}

// Redirect jika sudah login (untuk halaman login)
function requireGuest() {
    if (isLoggedIn()) {
        header("Location: /apotek/admin/"); 
        exit();
    }
}

// Get current user data - **PERBAIKAN DISINI**
function getCurrentUser($conn = null) {
    if (!isLoggedIn()) {
        return null;
    }
    
    $user_id = $_SESSION['pegawai_id'];
    
    // Jika koneksi database diberikan, ambil data terbaru
    if ($conn) {
        // PERBAIKAN: Tambahkan field 'role' dalam query
        $sql = "SELECT id, username, nama_lengkap, status, role, created_at, last_login 
                FROM pegawai 
                WHERE id = '$user_id' AND status = 'active'";
        $result = mysqli_query($conn, $sql);
        
        if ($row = mysqli_fetch_assoc($result)) {
            // PERBAIKAN: Update session dengan role dari database
            $_SESSION['role'] = $row['role'];
            return $row;
        } else {
            // User tidak ditemukan atau tidak aktif, logout
            session_destroy();
            return null;
        }
    }
    
    // Return data dari session - PERBAIKAN: tambahkan role
    return [
        'id' => $_SESSION['pegawai_id'],
        'username' => $_SESSION['username'],
        'nama_lengkap' => $_SESSION['nama_lengkap'],
        'status' => $_SESSION['status'] ?? 'active',
        'role' => $_SESSION['role'] ?? 'admin' // PERBAIKAN: tambahkan ini
    ];
}

// Update last login time
function updateLastLogin($conn, $user_id) {
    $sql = "UPDATE pegawai SET last_login = NOW() WHERE id = '$user_id'";
    return mysqli_query($conn, $sql);
}

// Validate password strength
function validatePassword($password) {
    $errors = [];
    
    if (strlen($password) < 6) {
        $errors[] = "Password minimal 6 karakter";
    }
    
    return $errors; // Sederhanakan, hanya minimal 6 karakter
}

// Generate random password
function generateRandomPassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    
    return $password;
}

// Check if user can access admin panel
function canAccessAdmin() {
    return isLoggedIn() && ($_SESSION['status'] ?? 'active') === 'active';
}