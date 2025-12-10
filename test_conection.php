<?php
// test_connection.php
echo "<h2>Testing Koneksi Database</h2>";
echo "<hr>";

// Konfigurasi database
$host = "localhost";
$username = "root";
$password = "";
$database = "apotek_db";

echo "1. Menguji koneksi ke MySQL...<br>";
$conn = mysqli_connect($host, $username, $password);

if (!$conn) {
    die("<span style='color: red;'>❌ GAGAL: " . mysqli_connect_error() . "</span>");
}

echo "<span style='color: green;'>✔ BERHASIL terhubung ke MySQL server</span><br><br>";

echo "2. Memilih database '$database'...<br>";
$select_db = mysqli_select_db($conn, $database);

if (!$select_db) {
    echo "<span style='color: orange;'>⚠ Database belum ada. Mencoba membuat database...</span><br>";
    
    $create_db = "CREATE DATABASE IF NOT EXISTS $database";
    if (mysqli_query($conn, $create_db)) {
        echo "<span style='color: green;'>✔ Database '$database' berhasil dibuat</span><br>";
        
        // Pilih database yang baru dibuat
        mysqli_select_db($conn, $database);
        echo "<span style='color: green;'>✔ Database '$database' berhasil dipilih</span><br>";
    } else {
        die("<span style='color: red;'>❌ Gagal membuat database: " . mysqli_error($conn) . "</span>");
    }
} else {
    echo "<span style='color: green;'>✔ Database '$database' berhasil dipilih</span><br>";
}

echo "<br>3. Menampilkan tabel yang ada...<br>";
$result = mysqli_query($conn, "SHOW TABLES");
$table_count = mysqli_num_rows($result);

if ($table_count > 0) {
    echo "<span style='color: green;'>✔ Ditemukan $table_count tabel:</span><br>";
    echo "<ul>";
    while ($row = mysqli_fetch_array($result)) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";
} else {
    echo "<span style='color: orange;'>⚠ Tidak ada tabel dalam database</span><br>";
}

echo "<br>4. Menguji query sederhana...<br>";
$test_query = "SELECT 1 as test";
if (mysqli_query($conn, $test_query)) {
    echo "<span style='color: green;'>✔ Query berhasil dijalankan</span><br>";
} else {
    echo "<span style='color: red;'>❌ Query gagal: " . mysqli_error($conn) . "</span><br>";
}

echo "<br><hr>";
echo "<h3>Status Koneksi:</h3>";
echo "<div style='background-color: lightgreen; padding: 10px; border-radius: 5px;'>";
echo "✅ <strong>KONEKSI DATABASE BERHASIL!</strong><br>";
echo "Server: " . mysqli_get_host_info($conn) . "<br>";
echo "Versi MySQL: " . mysqli_get_server_info($conn);
echo "</div>";

// Tutup koneksi
mysqli_close($conn);

echo "<br><br>";
echo "<a href='index.php' style='background-color: #2c8c99; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Lanjut ke Home Page</a>";
echo " | ";
echo "<a href='create_tables.php' style='background-color: #ffd166; color: black; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Buat Tabel Database</a>";
?>