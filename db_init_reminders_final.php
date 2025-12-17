<?php
require_once 'config.php';

function createTable($conn, $sql, $tableName) {
    if (mysqli_query($conn, $sql)) {
        echo "Table '$tableName' created successfully or already exists.\n";
    } else {
        echo "Error creating table '$tableName': " . mysqli_error($conn) . "\n";
    }
}

// 1. Table: pengingat
$sql_pengingat = "CREATE TABLE IF NOT EXISTS pengingat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    nama_obat VARCHAR(100) NOT NULL,
    dosis VARCHAR(50),
    catatan TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE
)";
createTable($conn, $sql_pengingat, 'pengingat');

// 2. Table: pengingat_waktu
$sql_waktu = "CREATE TABLE IF NOT EXISTS pengingat_waktu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_pengingat INT NOT NULL,
    waktu TIME NOT NULL,
    FOREIGN KEY (id_pengingat) REFERENCES pengingat(id) ON DELETE CASCADE
)";
createTable($conn, $sql_waktu, 'pengingat_waktu');

// 3. Table: riwayat_minum
$sql_riwayat = "CREATE TABLE IF NOT EXISTS riwayat_minum (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_pengingat INT NOT NULL,
    waktu_dijadwalkan TIME, 
    waktu_diminum DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pengingat) REFERENCES pengingat(id) ON DELETE CASCADE
)";
createTable($conn, $sql_riwayat, 'riwayat_minum');

echo "Database initialization for 'Pengingat' feature completed.";
?>
