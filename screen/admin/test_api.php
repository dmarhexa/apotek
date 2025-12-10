<?php
require_once '../../config.php';

echo "<h1>Test Fix - Tabel transaksi_detail</h1>";

// Test koneksi
echo "Database: " . ($conn ? "✅ Connected" : "❌ Not connected") . "<br>";

// Test tabel transaksi
$result = mysqli_query($conn, "SHOW TABLES LIKE 'transaksi'");
echo "Tabel transaksi: " . (mysqli_num_rows($result) > 0 ? "✅ Ada" : "❌ Tidak ada") . "<br>";

// Test tabel transaksi_detail
$result = mysqli_query($conn, "SHOW TABLES LIKE 'transaksi_detail'");
echo "Tabel transaksi_detail: " . (mysqli_num_rows($result) > 0 ? "✅ Ada" : "❌ Tidak ada") . "<br>";

// Test join data
echo "<h2>Test Join Data</h2>";
$sql = "
    SELECT t.id_transaksi, t.nama_pembeli, t.total_harga,
           COUNT(td.id_detail) as jumlah_item,
           SUM(td.subtotal) as total_detail
    FROM transaksi t
    LEFT JOIN transaksi_detail td ON t.id_transaksi = td.id_transaksi
    GROUP BY t.id_transaksi
    LIMIT 5
";

$result = mysqli_query($conn, $sql);
if ($result && mysqli_num_rows($result) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID Transaksi</th><th>Nama Pembeli</th><th>Total Transaksi</th><th>Jumlah Item</th><th>Total Detail</th></tr>";
    while($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['id_transaksi'] . "</td>";
        echo "<td>" . $row['nama_pembeli'] . "</td>";
        echo "<td>Rp " . number_format($row['total_harga'], 0, ',', '.') . "</td>";
        echo "<td>" . $row['jumlah_item'] . "</td>";
        echo "<td>Rp " . number_format($row['total_detail'], 0, ',', '.') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ Error query: " . mysqli_error($conn);
}

// Test API endpoint
echo "<h2>Test API Endpoint</h2>";
echo "<button onclick=\"testAPI()\">Test API Get Detail</button>";
echo "<div id='api-result'></div>";

echo "<script>
function testAPI() {
    fetch('../../api/get_transaction_detail.php?id=4')
        .then(response => response.json())
        .then(data => {
            document.getElementById('api-result').innerHTML = 
                '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        })
        .catch(error => {
            document.getElementById('api-result').innerHTML = 
                '❌ Error: ' + error;
        });
}
</script>";
?>