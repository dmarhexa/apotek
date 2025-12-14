<?php
require_once 'config.php';

$query_drop = "DROP TABLE IF EXISTS pengingat_obat";
mysqli_query($conn, $query_drop);

$query = "CREATE TABLE pengingat_obat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_pengguna INT NOT NULL,
    nama_obat VARCHAR(255) NOT NULL,
    frekuensi VARCHAR(50) NOT NULL COMMENT 'e.g. 3x1',
    waktu TIME NOT NULL COMMENT 'e.g. 08:00:00',
    keterangan TEXT,
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $query)) {
    echo "Tabel pengingat_obat berhasil dibuat atau sudah ada.\n";
} else {
    echo "Error creating table: " . mysqli_error($conn) . "\n";
}
?>
