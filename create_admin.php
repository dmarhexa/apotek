<?php
// create_admin.php - Jalankan di browser, lalu hapus file ini
require_once 'config.php';

$username = 'admin';
$password = 'admin123'; // Password yang diinginkan
$nama_lengkap = 'Administrator Utama';

// Generate hash password yang benar
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

echo "<h2>Admin Account Generator</h2>";
echo "Username: $username<br>";
echo "Password: $password<br>";
echo "Generated Hash: <strong>$hashed_password</strong><br><br>";

// Hapus data lama
mysqli_query($conn, "DELETE FROM pegawai WHERE username = '$username'");

// Insert data baru
$sql = "INSERT INTO pegawai (username, password, nama_lengkap, status) 
        VALUES ('$username', '$hashed_password', '$nama_lengkap', 'active')";

if (mysqli_query($conn, $sql)) {
    echo "✅ Admin account created successfully!<br><br>";
    
    // Test verifikasi
    $test_sql = "SELECT password FROM pegawai WHERE username = '$username'";
    $result = mysqli_query($conn, $test_sql);
    $row = mysqli_fetch_assoc($result);
    
    if (password_verify($password, $row['password'])) {
        echo "✅ Password verification SUCCESS!<br>";
        echo "You can now login with:<br>";
        echo "- Username: <strong>admin</strong><br>";
        echo "- Password: <strong>admin123</strong><br>";
    } else {
        echo "❌ Password verification FAILED!<br>";
    }
} else {
    echo "❌ Error: " . mysqli_error($conn);
}

// Tampilkan SQL untuk copy-paste
echo "<hr><h3>SQL untuk phpMyAdmin:</h3>";
echo "<pre>";
echo "DELETE FROM pegawai WHERE username = 'admin';\n\n";
echo "INSERT INTO pegawai (username, password, nama_lengkap, status) VALUES\n";
echo "('admin', '$hashed_password', 'Administrator Utama', 'active');";
echo "</pre>";
?>