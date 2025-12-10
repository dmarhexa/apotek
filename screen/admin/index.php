<?php
// Gunakan path yang benar ke ROOT
require_once dirname(dirname(dirname(__FILE__))) . '/config.php';

// Include auth dari includes
$auth_path = dirname(dirname(dirname(__FILE__))) . '/includes/auth.php';
if (file_exists($auth_path)) {
    require_once $auth_path;
} else {
    // Fallback function
    function requireLogin() {
        if (!isset($_SESSION['pegawai_id']) || empty($_SESSION['pegawai_id'])) {
            header("Location: " . $GLOBALS['base_url'] . "/auth/login.php");
            exit();
        }
    }
}

// Cek login
requireLogin();

// Ambil statistik dasar dengan error handling
$total_obat = 0;
$total_dokter = 0;
$total_transaksi = 0;
$obat_habis = 0;

try {
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM obat");
    if ($result) $total_obat = $result->fetch_assoc()['total'];
    
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM dokter");
    if ($result) $total_dokter = $result->fetch_assoc()['total'];
    
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi");
    if ($result) $total_transaksi = $result->fetch_assoc()['total'];
    
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM obat WHERE stok = 0");
    if ($result) $obat_habis = $result->fetch_assoc()['total'];
} catch (Exception $e) {
    error_log("Error fetching statistics: " . $e->getMessage());
}

// Analisis Penjualan 30 Hari Terakhir
$chart_labels = [];
$chart_data = [];

for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('d M', strtotime($date));
    
    $query = "SELECT COUNT(*) as count FROM transaksi WHERE DATE(tanggal_transaksi) = '$date'";
    $result = mysqli_query($conn, $query);
    if ($result) {
        $data = mysqli_fetch_assoc($result);
        $chart_data[] = $data['count'] ?? 0;
    } else {
        $chart_data[] = 0;
    }
}

// Total pendapatan bulan ini - dengan error handling
$current_month = date('Y-m');
$pendapatan_bulanan = 0;

// Cek apakah tabel transaksi ada
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'transaksi'");
if (mysqli_num_rows($table_check) > 0) {
    $result = mysqli_query($conn, 
        "SELECT COALESCE(SUM(total_harga), 0) as total FROM transaksi 
         WHERE DATE_FORMAT(tanggal_transaksi, '%Y-%m') = '$current_month'
         AND (status_pembayaran = 'lunas' OR status_pembayaran IS NULL)"
    );
    if ($result) {
        $pendapatan_bulanan = $result->fetch_assoc()['total'] ?? 0;
    }
}

// Obat paling laris - dengan error handling
$obat_laris = [];
$kategori_populer = [];
$dokter_populer = [];
$stok_menipis = [];

try {
    // Obat paling laris (semua waktu) - DIPERBAIKI: pakai transaksi_detail
    $obat_laris_result = mysqli_query($conn, 
        "SELECT o.nama_obat, o.stok, o.harga, o.kategori,
                COALESCE(SUM(td.jumlah), 0) as total_terjual
         FROM obat o
         LEFT JOIN transaksi_detail td ON o.id_obat = td.id_obat
         GROUP BY o.id_obat
         ORDER BY total_terjual DESC, o.nama_obat ASC
         LIMIT 5"
    );
    if ($obat_laris_result) {
        while($row = mysqli_fetch_assoc($obat_laris_result)) {
            $obat_laris[] = $row;
        }
    }
    
    // Kategori obat terpopuler
    $kategori_result = mysqli_query($conn,
        "SELECT o.kategori, COUNT(o.id_obat) as jumlah_obat
         FROM obat o
         WHERE o.kategori IS NOT NULL AND o.kategori != ''
         GROUP BY o.kategori
         ORDER BY jumlah_obat DESC
         LIMIT 5"
    );
    if ($kategori_result) {
        while($row = mysqli_fetch_assoc($kategori_result)) {
            $kategori_populer[] = $row;
        }
    }
    
    // Dokter dengan data lengkap
    $dokter_result = mysqli_query($conn,
        "SELECT d.nama_dokter, d.spesialisasi, d.pengalaman, d.biaya_konsultasi
         FROM dokter d
         ORDER BY d.pengalaman DESC
         LIMIT 5"
    );
    if ($dokter_result) {
        while($row = mysqli_fetch_assoc($dokter_result)) {
            $dokter_populer[] = $row;
        }
    }
    
    // Prediksi stok habis (stok < 10)
    $stok_result = mysqli_query($conn,
        "SELECT nama_obat, stok, harga, kategori
         FROM obat 
         WHERE stok > 0 AND stok < 10
         ORDER BY stok ASC
         LIMIT 5"
    );
    if ($stok_result) {
        while($row = mysqli_fetch_assoc($stok_result)) {
            $stok_menipis[] = $row;
        }
    }
    
} catch (Exception $e) {
    error_log("Error fetching analysis data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Apotek Sehat</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

  <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="main-header">
            <div class="header-left">
                <h1><i class="fas fa-tachometer-alt"></i> Dashboard Admin</h1>
                <p>Analisis & Statistik Sistem Apotek Sehat</p>
            </div>
            <div class="header-right">
                <?php if($obat_habis > 0): ?>
                <div class="notification" id="notificationBtn">
                    <i class="fas fa-bell"></i>
                    <span class="badge" id="notificationCount"><?php echo $obat_habis; ?></span>
                </div>
                <?php endif; ?>
                <div class="current-time">
                    <i class="fas fa-clock"></i>
                    <span id="liveTime"></span>
                </div>
            </div>
        </header>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
            <div class="actions-grid">
                <a href="obat.php?action=tambah" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <div class="action-content">
                        <h4>Tambah Obat</h4>
                        <p>Tambah data obat baru ke sistem</p>
                    </div>
                </a>
                <a href="dokter.php?action=tambah" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="action-content">
                        <h4>Tambah Dokter</h4>
                        <p>Tambah data dokter konsultasi</p>
                    </div>
                </a>
                <a href="transaksi.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="action-content">
                        <h4>Lihat Transaksi</h4>
                        <p>Kelola semua transaksi pembelian</p>
                    </div>
                </a>
                <a href="obat.php?filter=stok_menipis" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="action-content">
                        <h4>Stok Menipis</h4>
                        <p>Obat yang perlu restock</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Analytics Overview -->
        <div class="analytics-overview">
            <div class="revenue-summary">
                <div class="revenue-card">
                    <div class="revenue-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="revenue-info">
                        <h3>Pendapatan Bulan Ini</h3>
                        <p class="revenue-amount">Rp <?php echo number_format($pendapatan_bulanan, 0, ',', '.'); ?></p>
                    </div>
                </div>
                
                <div class="stats-mini">
                    <div class="stat-mini">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);">
                            <i class="fas fa-pills"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $total_obat; ?></h3>
                            <p>Total Obat</p>
                        </div>
                    </div>
                    <div class="stat-mini">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $total_dokter; ?></h3>
                            <p>Total Dokter</p>
                        </div>
                    </div>
                    <div class="stat-mini">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $total_transaksi; ?></h3>
                            <p>Total Transaksi</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="charts-section">
                <div class="chart-container">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-line"></i> Transaksi 30 Hari Terakhir</h3>
                    </div>
                    <div class="chart-body">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Analysis Grid -->
        <div class="analysis-grid">
            <!-- Obat Paling Laris -->
            <div class="analysis-card">
                <div class="analysis-header">
                    <h3><i class="fas fa-star"></i> Obat Terlaris</h3>
                    <a href="obat.php?sort=terlaris" class="view-all">Lihat Semua →</a>
                </div>
                <div class="analysis-content">
                    <?php if(count($obat_laris) > 0): ?>
                        <div class="ranking-list">
                            <?php $rank = 1; foreach($obat_laris as $obat): ?>
                            <div class="ranking-item">
                                <div class="rank-number"><?php echo $rank; ?></div>
                                <div class="rank-details">
                                    <h4><?php echo htmlspecialchars($obat['nama_obat']); ?></h4>
                                    <p class="rank-meta"><?php echo ucfirst($obat['kategori']); ?> • 
                                        Rp <?php echo number_format($obat['harga'], 0, ',', '.'); ?>
                                    </p>
                                </div>
                                <div class="rank-stats">
                                    <span class="badge sales"><?php echo $obat['total_terjual'] ?? 0; ?> terjual</span>
                                    <span class="badge stock <?php echo $obat['stok'] > 10 ? 'success' : ($obat['stok'] > 0 ? 'warning' : 'danger'); ?>">
                                        Stok: <?php echo $obat['stok']; ?>
                                    </span>
                                </div>
                            </div>
                            <?php $rank++; endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-analysis">
                            <i class="fas fa-chart-bar"></i>
                            <p>Belum ada data penjualan</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Kategori Populer -->
            <div class="analysis-card">
                <div class="analysis-header">
                    <h3><i class="fas fa-tags"></i> Kategori Obat</h3>
                    <a href="obat.php" class="view-all">Lihat Semua →</a>
                </div>
                <div class="analysis-content">
                    <?php if(count($kategori_populer) > 0): ?>
                        <div class="category-list">
                            <?php foreach($kategori_populer as $kategori): ?>
                            <div class="category-item">
                                <div class="category-name">
                                    <i class="fas fa-tag"></i>
                                    <span><?php echo ucfirst($kategori['kategori']); ?></span>
                                </div>
                                <div class="category-stats">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo min(100, ($kategori['jumlah_obat'] / max(1, $total_obat)) * 100); ?>%"></div>
                                    </div>
                                    <span class="count"><?php echo $kategori['jumlah_obat']; ?> obat</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-analysis">
                            <i class="fas fa-tags"></i>
                            <p>Belum ada data kategori</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stok Menipis -->
            <div class="analysis-card warning">
                <div class="analysis-header">
                    <h3><i class="fas fa-exclamation-triangle"></i> Stok Hampir Habis</h3>
                    <a href="obat.php?filter=stok_kritis" class="view-all">Lihat Semua →</a>
                </div>
                <div class="analysis-content">
                    <?php if(count($stok_menipis) > 0): ?>
                        <div class="stock-alert-list">
                            <?php foreach($stok_menipis as $obat): ?>
                            <div class="stock-alert-item">
                                <div class="alert-info">
                                    <h4><?php echo htmlspecialchars($obat['nama_obat']); ?></h4>
                                    <p class="alert-meta"><?php echo ucfirst($obat['kategori']); ?></p>
                                </div>
                                <div class="alert-stats">
                                    <span class="badge danger">Stok: <?php echo $obat['stok']; ?></span>
                                    <a href="obat.php?action=tambah" class="btn-restock">
                                        <i class="fas fa-plus"></i> Restock
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-analysis">
                            <i class="fas fa-check-circle"></i>
                            <p>Semua stok dalam kondisi baik</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Dokter Populer -->
            <div class="analysis-card">
                <div class="analysis-header">
                    <h3><i class="fas fa-user-md"></i> Dokter Terpandai</h3>
                    <a href="dokter.php" class="view-all">Lihat Semua →</a>
                </div>
                <div class="analysis-content">
                    <?php if(count($dokter_populer) > 0): ?>
                        <div class="doctor-rank-list">
                            <?php foreach($dokter_populer as $dokter): ?>
                            <div class="doctor-rank-item">
                                <div class="doctor-avatar">
                                    <i class="fas fa-user-md"></i>
                                </div>
                                <div class="doctor-details">
                                    <h4>Dr. <?php echo htmlspecialchars($dokter['nama_dokter']); ?></h4>
                                    <p class="specialization"><?php echo htmlspecialchars($dokter['spesialisasi']); ?></p>
                                </div>
                                <div class="doctor-stats">
                                    <span class="consult-count">
                                        <i class="fas fa-briefcase"></i>
                                        <?php echo $dokter['pengalaman']; ?> tahun
                                    </span>
                                    <span class="consult-fee">
                                        Rp <?php echo number_format($dokter['biaya_konsultasi'], 0, ',', '.'); ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-analysis">
                            <i class="fas fa-user-md"></i>
                            <p>Belum ada data dokter</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
    <script>
    // Chart.js Implementation
    const salesCtx = document.getElementById('salesChart');
    if (salesCtx) {
        const ctx = salesCtx.getContext('2d');
        const salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'Jumlah Transaksi',
                    data: <?php echo json_encode($chart_data); ?>,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    // Live Time Update
    function updateLiveTime() {
        const now = new Date();
        const options = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        };
        const timeElement = document.getElementById('liveTime');
        if (timeElement) {
            timeElement.textContent = now.toLocaleDateString('id-ID', options);
        }
    }
    
    updateLiveTime();
    setInterval(updateLiveTime, 1000);
    </script>
</body>
</html>