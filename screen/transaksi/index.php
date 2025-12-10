<?php
// apotek/screens/transaksi/index.php
require_once '../../config.php';

// ==================== KONFIGURASI MODE ====================
$is_admin_mode = false;

// Cek apakah diakses dari admin panel - TANPA PENGE CEKAN ROLE
if (isset($_GET['admin']) && $_GET['admin'] == 'true') {
    $is_admin_mode = true;
}

// Tentukan title berdasarkan mode
$page_title = $is_admin_mode ? 'Admin - Transaksi' : 'Transaksi - Apotek Sehat';


// Get parameters for filtering
$status = isset($_GET['status']) ? $_GET['status'] : 'semua';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'terbaru';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query for transactions
$query = "SELECT t.* FROM transaksi t WHERE 1=1";
$count_query = "SELECT COUNT(*) as total FROM transaksi t WHERE 1=1";

// Filter berdasarkan pencarian
if (!empty($search)) {
    $search_term = mysqli_real_escape_string($conn, $search);
    $query .= " AND (t.id_transaksi LIKE '%$search_term%' 
                    OR t.nama_pembeli LIKE '%$search_term%'
                    OR t.metode_pembayaran LIKE '%$search_term%')";
    $count_query .= " AND (t.id_transaksi LIKE '%$search_term%' 
                         OR t.nama_pembeli LIKE '%$search_term%'
                         OR t.metode_pembayaran LIKE '%$search_term%')";
}

// Sorting
switch ($sort) {
    case 'terlama': $query .= " ORDER BY t.tanggal_transaksi ASC"; break;
    case 'total_tertinggi': $query .= " ORDER BY t.total_harga DESC"; break;
    case 'total_terendah': $query .= " ORDER BY t.total_harga ASC"; break;
    case 'terbaru': default: $query .= " ORDER BY t.tanggal_transaksi DESC"; break;
}

$query .= " LIMIT $limit OFFSET $offset";

// Execute queries
$result = mysqli_query($conn, $query);
$count_result = mysqli_query($conn, $count_query);
$total_items = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_items / $limit);

// Get transaction statistics
$stats_query = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total_transaksi,
        SUM(CASE WHEN status_pembayaran = 'lunas' THEN 1 ELSE 0 END) as lunas,
        SUM(CASE WHEN status_pembayaran = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status_pembayaran = 'dibatalkan' THEN 1 ELSE 0 END) as dibatalkan,
        SUM(total_harga) as total_pendapatan
    FROM transaksi
");

$stats = mysqli_fetch_assoc($stats_query);

// Get cart from session for new transaction
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
if (isset($_SESSION['cart'])) {
    unset($_SESSION['cart']); // Clear cart after showing
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Pilih stylesheet berdasarkan mode -->
    <?php if($is_admin_mode): ?>
        <!-- HAPUS link ke admin style.css -->
        <!-- <link rel="stylesheet" href="../admin/style.css"> -->
        
        <!-- GUNAKAN style transaksi biasa + style khusus admin -->
        <link rel="stylesheet" href="style.css">
        <style>
            /* Reset untuk admin container */
            .admin-container {
                display: flex;
                min-height: 100vh;
                background: #f8fafc;
            }
            
            /* Sidebar Admin */
            .sidebar {
                width: 280px;
                background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
                color: white;
                padding: 20px;
                position: fixed;
                height: 100vh;
                overflow-y: auto;
                box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            }
            
            .sidebar-header {
                padding-bottom: 20px;
                margin-bottom: 20px;
                border-bottom: 1px solid rgba(255,255,255,0.1);
            }
            
            .logo {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            
            .logo i {
                font-size: 28px;
                color: #10b981;
            }
            
            .logo h2 {
                font-size: 20px;
                font-weight: 700;
                margin: 0;
            }
            
            .logo span {
                color: #10b981;
            }
            
            .sidebar-menu {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
            
            .sidebar-menu a {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 12px 15px;
                color: #cbd5e1;
                text-decoration: none;
                border-radius: 8px;
                transition: all 0.3s;
                font-weight: 500;
            }
            
            .sidebar-menu a:hover {
                background: rgba(255,255,255,0.1);
                color: white;
            }
            
            .sidebar-menu a.active {
                background: #10b981;
                color: white;
            }
            
            .sidebar-menu a i {
                width: 20px;
                text-align: center;
                font-size: 18px;
            }
            
            /* Main Content Area */
            .main-content {
                flex: 1;
                margin-left: 280px;
                background: #f8fafc;
                min-height: 100vh;
            }
            
            /* Header khusus untuk admin */
            .admin-transaction-header {
                background: white;
                padding: 25px 40px;
                border-bottom: 1px solid #e2e8f0;
                display: flex;
                justify-content: space-between;
                align-items: center;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            }
            
            .admin-transaction-header h1 {
                font-size: 1.8rem;
                color: #1e293b;
                margin: 0;
                display: flex;
                align-items: center;
                gap: 12px;
            }
            
            .admin-transaction-header h1 i {
                color: #10b981;
            }
            
            .admin-transaction-header p {
                color: #64748b;
                margin: 5px 0 0 0;
                font-size: 0.95rem;
            }
            
            .btn-back-admin {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 12px 24px;
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                color: white;
                border: none;
                border-radius: 8px;
                font-size: 0.95rem;
                font-weight: 600;
                text-decoration: none;
                cursor: pointer;
                transition: all 0.3s;
            }
            
            .btn-back-admin:hover {
                background: linear-gradient(135deg, #059669 0%, #10b981 100%);
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            }
            
            /* Container untuk konten transaksi */
            .admin-transaction-wrapper {
                padding: 20px 40px;
            }
            
            /* Override untuk konten transaksi */
            .admin-transaction-wrapper .content {
                padding: 0;
                max-width: 100%;
                margin: 0;
            }
            
            /* Responsive */
            @media (max-width: 768px) {
                .sidebar {
                    width: 100%;
                    height: auto;
                    position: relative;
                }
                
                .main-content {
                    margin-left: 0;
                }
                
                .admin-transaction-header {
                    flex-direction: column;
                    gap: 15px;
                    align-items: flex-start;
                    padding: 20px;
                }
                
                .admin-transaction-wrapper {
                    padding: 15px;
                }
            }
        </style>
    <?php else: ?>
        <link rel="stylesheet" href="style.css">
    <?php endif; ?>
    
    <!-- Library tetap sama -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php if($is_admin_mode): ?>
        <!-- ========== TAMPILAN ADMIN MODE ========== -->
        <div class="admin-container">
            <!-- Sidebar Admin -->
            <div class="sidebar">
                <div class="sidebar-header">
                    <div class="logo">
                        <i class="fas fa-heartbeat"></i>
                        <h2>Admin<span>Panel</span></h2>
                    </div>
                </div>
                
                <nav class="sidebar-menu">
                    <a href="../admin/index.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="../admin/obat.php">
                        <i class="fas fa-pills"></i>
                        <span>Kelola Obat</span>
                    </a>
                    <a href="../admin/dokter.php">
                        <i class="fas fa-user-md"></i>
                        <span>Kelola Dokter</span>
                    </a>
                    <a href="index.php?admin=true" class="active">
                        <i class="fas fa-receipt"></i>
                        <span>Transaksi</span>
                    </a>
                    <a href="../admin/users.php">
                        <i class="fas fa-users"></i>
                        <span>Pengguna</span>
                    </a>
                    <a href="../admin/settings.php">
                        <i class="fas fa-cog"></i>
                        <span>Pengaturan</span>
                    </a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="main-content">
                <div class="admin-transaction-wrapper">
                    <!-- Header -->
                    <div class="admin-transaction-header">
                        <div>
                            <h1><i class="fas fa-receipt"></i> Manajemen Transaksi</h1>
                            <p>Kelola semua transaksi sistem Apotek Sehat</p>
                        </div>
                        <div class="header-actions">
                            <a href="../admin/index.php" class="btn-back-admin">
                                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                            </a>
                            <?php if(!$is_admin_mode): ?>
                            <button class="btn-new-transaction" onclick="showNewTransactionModal()">
                                <i class="fas fa-plus-circle"></i>
                                Transaksi Baru
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Statistics Cards -->
                    <section class="statistics-section">
                        <div class="statistics-grid">
                            <div class="stat-card">
                                <div class="stat-icon total">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="stat-info">
                                    <h3>Total Transaksi</h3>
                                    <p class="stat-number"><?php echo $stats['total_transaksi'] ?? 0; ?></p>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon success">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="stat-info">
                                    <h3>Lunas</h3>
                                    <p class="stat-number"><?php echo $stats['lunas'] ?? 0; ?></p>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon warning">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="stat-info">
                                    <h3>Pending</h3>
                                    <p class="stat-number"><?php echo $stats['pending'] ?? 0; ?></p>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon danger">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                                <div class="stat-info">
                                    <h3>Dibatalkan</h3>
                                    <p class="stat-number"><?php echo $stats['dibatalkan'] ?? 0; ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <?php if(isset($stats['total_pendapatan']) && $stats['total_pendapatan'] > 0): ?>
                        <div class="revenue-card">
                            <div class="revenue-icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div class="revenue-info">
                                <h3>Total Pendapatan</h3>
                                <p class="revenue-amount">Rp <?php echo number_format($stats['total_pendapatan'], 0, ',', '.'); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </section>
                    
                    <!-- Filter Section -->
                    <section class="filter-section">
                        <div class="search-container">
                            <form method="GET" action="" class="search-form" id="searchForm">
                                <input type="hidden" name="admin" value="true">
                                <div class="search-box">
                                    <i class="fas fa-search"></i>
                                    <input type="text" 
                                           name="search" 
                                           id="searchInput"
                                           placeholder="Cari transaksi (ID, nama pembeli, metode)..."
                                           value="<?php echo htmlspecialchars($search); ?>"
                                           autocomplete="off">
                                    <button type="submit" class="btn-search">
                                        <i class="fas fa-arrow-right"></i>
                                    </button>
                                </div>
                            </form>
                            
                            <div class="filter-controls">
                                <div class="filter-group">
                                    <label for="statusFilter"><i class="fas fa-filter"></i> Status:</label>
                                    <select name="status" id="statusFilter" onchange="updateStatusFilter(this.value)">
                                        <option value="semua" <?php echo $status == 'semua' ? 'selected' : ''; ?>>Semua Status</option>
                                        <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="lunas" <?php echo $status == 'lunas' ? 'selected' : ''; ?>>Lunas</option>
                                        <option value="dibatalkan" <?php echo $status == 'dibatalkan' ? 'selected' : ''; ?>>Dibatalkan</option>
                                    </select>
                                </div>
                                
                                <div class="filter-group">
                                    <label for="sortFilter"><i class="fas fa-sort"></i> Urutkan:</label>
                                    <select name="sort" id="sortFilter" onchange="updateSortFilter(this.value)">
                                        <option value="terbaru" <?php echo $sort == 'terbaru' ? 'selected' : ''; ?>>Terbaru</option>
                                        <option value="terlama" <?php echo $sort == 'terlama' ? 'selected' : ''; ?>>Terlama</option>
                                        <option value="total_tertinggi" <?php echo $sort == 'total_tertinggi' ? 'selected' : ''; ?>>Total Tertinggi</option>
                                        <option value="total_terendah" <?php echo $sort == 'total_terendah' ? 'selected' : ''; ?>>Total Terendah</option>
                                    </select>
                                </div>
                                
                                <div class="filter-actions">
                                    <a href="?admin=true" class="btn-clear-filters">
                                        <i class="fas fa-times"></i>
                                        Hapus Filter
                                    </a>
                                    <button class="btn-export" onclick="exportToExcel()">
                                        <i class="fas fa-file-export"></i>
                                        Export Excel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>
                    
                    <!-- Results Info -->
                    <div class="results-info">
                        <div class="results-summary">
                            <p>
                                Menampilkan <strong><?php echo mysqli_num_rows($result); ?></strong> dari 
                                <strong><?php echo $total_items; ?></strong> transaksi
                                <?php if(!empty($search)): ?>
                                    untuk "<strong><?php echo htmlspecialchars($search); ?></strong>"
                                <?php endif; ?>
                                <?php if($status !== 'semua'): ?>
                                    dengan status <strong><?php echo ucfirst($status); ?></strong>
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <?php if(mysqli_num_rows($result) == 0): ?>
                        <div class="no-results">
                            <i class="fas fa-receipt"></i>
                            <h3>Tidak ada transaksi ditemukan</h3>
                            <p>Mulai transaksi baru atau coba filter yang berbeda.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Transactions Table -->
                    <div class="transactions-container">
                        <div class="transactions-table">
                            <div class="table-header">
                                <div class="table-row">
                                    <div class="table-cell">ID Transaksi</div>
                                    <div class="table-cell">Pembeli</div>
                                    <div class="table-cell">Tanggal</div>
                                    <div class="table-cell">Total</div>
                                    <div class="table-cell">Status</div>
                                    <div class="table-cell">Metode</div>
                                    <div class="table-cell">Aksi</div>
                                </div>
                            </div>
                            
                            <div class="table-body">
                                <?php while($transaksi = mysqli_fetch_assoc($result)): 
                                    $status_class = '';
                                    $status_icon = '';
                                    switch($transaksi['status_pembayaran']) {
                                        case 'lunas':
                                            $status_class = 'status-success';
                                            $status_icon = 'fas fa-check-circle';
                                            break;
                                        case 'pending':
                                            $status_class = 'status-warning';
                                            $status_icon = 'fas fa-clock';
                                            break;
                                        case 'dibatalkan':
                                            $status_class = 'status-danger';
                                            $status_icon = 'fas fa-times-circle';
                                            break;
                                    }
                                ?>
                                <div class="table-row transaction-row" data-id="<?php echo $transaksi['id_transaksi']; ?>">
                                    <div class="table-cell">
                                        <span class="transaction-id">#<?php echo str_pad($transaksi['id_transaksi'], 6, '0', STR_PAD_LEFT); ?></span>
                                    </div>
                                    
                                    <div class="table-cell">
                                        <div class="customer-info">
                                            <i class="fas fa-user"></i>
                                            <span><?php echo htmlspecialchars($transaksi['nama_pembeli']); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="table-cell">
                                        <?php 
                                        $date = new DateTime($transaksi['tanggal_transaksi']);
                                        echo $date->format('d/m/Y');
                                        ?>
                                        <br>
                                        <small class="time"><?php echo $date->format('H:i'); ?></small>
                                    </div>
                                    
                                    <div class="table-cell">
                                        <div class="total-amount">
                                            Rp <?php echo number_format($transaksi['total_harga'], 0, ',', '.'); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="table-cell">
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <i class="<?php echo $status_icon; ?>"></i>
                                            <?php echo ucfirst($transaksi['status_pembayaran']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="table-cell">
                                        <span class="payment-method">
                                            <?php 
                                            $method_icons = [
                                                'tunai' => 'fa-money-bill',
                                                'transfer_bank' => 'fa-university',
                                                'kartu_kredit' => 'fa-credit-card',
                                                'e-wallet' => 'fa-mobile-alt'
                                            ];
                                            $icon = $method_icons[strtolower($transaksi['metode_pembayaran'])] ?? 'fa-money-bill';
                                            ?>
                                            <i class="fas <?php echo $icon; ?>"></i>
                                            <?php echo ucfirst(str_replace('_', ' ', $transaksi['metode_pembayaran'])); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="table-cell">
                                        <div class="action-buttons">
                                            <button class="btn-action view-detail" onclick="viewTransactionDetail(<?php echo $transaksi['id_transaksi']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <?php if($transaksi['status_pembayaran'] == 'pending'): ?>
                                            <button class="btn-action mark-paid" onclick="markAsPaid(<?php echo $transaksi['id_transaksi']; ?>)">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <?php if($transaksi['status_pembayaran'] != 'dibatalkan'): ?>
                                            <button class="btn-action cancel-transaction" onclick="cancelTransaction(<?php echo $transaksi['id_transaksi']; ?>)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <button class="btn-action print-receipt" onclick="printReceipt(<?php echo $transaksi['id_transaksi']; ?>)">
                                                <i class="fas fa-print"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if($page > 1): ?>
                        <a href="<?php echo buildPageUrl($page - 1, $is_admin_mode); ?>" class="page-link">
                            <i class="fas fa-chevron-left"></i> Sebelumnya
                        </a>
                        <?php endif; ?>
                        
                        <div class="page-numbers">
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $start_page + 4);
                            
                            if($start_page > 1) {
                                echo '<a href="' . buildPageUrl(1, $is_admin_mode) . '" class="page-number">1</a>';
                                if($start_page > 2) echo '<span class="page-dots">...</span>';
                            }
                            
                            for($i = $start_page; $i <= $end_page; $i++):
                            ?>
                            <a href="<?php echo buildPageUrl($i, $is_admin_mode); ?>" 
                               class="page-number <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                            <?php endfor; ?>
                            
                            <?php
                            if($end_page < $total_pages) {
                                if($end_page < $total_pages - 1) echo '<span class="page-dots">...</span>';
                                echo '<a href="' . buildPageUrl($total_pages, $is_admin_mode) . '" class="page-number">' . $total_pages . '</a>';
                            }
                            ?>
                        </div>
                        
                        <?php if($page < $total_pages): ?>
                        <a href="<?php echo buildPageUrl($page + 1, $is_admin_mode); ?>" class="page-link">
                            Selanjutnya <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <!-- ========== TAMPILAN USER MODE (BIASA) ========== -->
        <div class="container">
            <?php include '../../includes/sidebar.php'; ?>
            
            <main class="main-content">
                <!-- Header -->
                <header class="page-header">
                    <div class="header-content">
                        <h1><i class="fas fa-receipt"></i> Transaksi</h1>
                        <p class="subtitle">Kelola dan pantau semua transaksi pembelian obat</p>
                    </div>
                    <div class="header-actions">
                        <button class="btn-new-transaction" onclick="showNewTransactionModal()">
                            <i class="fas fa-plus-circle"></i>
                            Transaksi Baru
                        </button>
                    </div>
                </header>
                
                <div class="content">
                    <!-- Statistics Cards -->
                    <section class="statistics-section">
                        <div class="statistics-grid">
                            <div class="stat-card">
                                <div class="stat-icon total">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="stat-info">
                                    <h3>Total Transaksi</h3>
                                    <p class="stat-number"><?php echo $stats['total_transaksi'] ?? 0; ?></p>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon success">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="stat-info">
                                    <h3>Lunas</h3>
                                    <p class="stat-number"><?php echo $stats['lunas'] ?? 0; ?></p>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon warning">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="stat-info">
                                    <h3>Pending</h3>
                                    <p class="stat-number"><?php echo $stats['pending'] ?? 0; ?></p>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon danger">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                                <div class="stat-info">
                                    <h3>Dibatalkan</h3>
                                    <p class="stat-number"><?php echo $stats['dibatalkan'] ?? 0; ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <?php if(isset($stats['total_pendapatan']) && $stats['total_pendapatan'] > 0): ?>
                        <div class="revenue-card">
                            <div class="revenue-icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div class="revenue-info">
                                <h3>Total Pendapatan</h3>
                                <p class="revenue-amount">Rp <?php echo number_format($stats['total_pendapatan'], 0, ',', '.'); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </section>
                    
                    <!-- Filter Section -->
                    <section class="filter-section">
                        <div class="search-container">
                            <form method="GET" action="" class="search-form" id="searchForm">
                                <div class="search-box">
                                    <i class="fas fa-search"></i>
                                    <input type="text" 
                                           name="search" 
                                           id="searchInput"
                                           placeholder="Cari transaksi (ID, nama pembeli, metode)..."
                                           value="<?php echo htmlspecialchars($search); ?>"
                                           autocomplete="off">
                                    <button type="submit" class="btn-search">
                                        <i class="fas fa-arrow-right"></i>
                                    </button>
                                </div>
                            </form>
                            
                            <div class="filter-controls">
                                <div class="filter-group">
                                    <label for="statusFilter"><i class="fas fa-filter"></i> Status:</label>
                                    <select name="status" id="statusFilter" onchange="updateStatusFilter(this.value)">
                                        <option value="semua" <?php echo $status == 'semua' ? 'selected' : ''; ?>>Semua Status</option>
                                        <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="lunas" <?php echo $status == 'lunas' ? 'selected' : ''; ?>>Lunas</option>
                                        <option value="dibatalkan" <?php echo $status == 'dibatalkan' ? 'selected' : ''; ?>>Dibatalkan</option>
                                    </select>
                                </div>
                                
                                <div class="filter-group">
                                    <label for="sortFilter"><i class="fas fa-sort"></i> Urutkan:</label>
                                    <select name="sort" id="sortFilter" onchange="updateSortFilter(this.value)">
                                        <option value="terbaru" <?php echo $sort == 'terbaru' ? 'selected' : ''; ?>>Terbaru</option>
                                        <option value="terlama" <?php echo $sort == 'terlama' ? 'selected' : ''; ?>>Terlama</option>
                                        <option value="total_tertinggi" <?php echo $sort == 'total_tertinggi' ? 'selected' : ''; ?>>Total Tertinggi</option>
                                        <option value="total_terendah" <?php echo $sort == 'total_terendah' ? 'selected' : ''; ?>>Total Terendah</option>
                                    </select>
                                </div>
                                
                                <div class="filter-actions">
                                    <a href="?" class="btn-clear-filters">
                                        <i class="fas fa-times"></i>
                                        Hapus Filter
                                    </a>
                                    <button class="btn-export" onclick="exportToExcel()">
                                        <i class="fas fa-file-export"></i>
                                        Export Excel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>
                    
                    <!-- New Transaction from Cart (if exists) -->
                    <?php if(!empty($cart)): ?>
                    <section class="cart-notification">
                        <div class="cart-notification-content">
                            <i class="fas fa-shopping-cart"></i>
                            <div>
                                <h4>Transaksi dari Keranjang</h4>
                                <p>Anda memiliki <?php echo count($cart); ?> item di keranjang yang belum diproses.</p>
                            </div>
                        </div>
                        <button class="btn-process-cart" onclick="processCartTransaction()">
                            <i class="fas fa-check"></i>
                            Proses Sekarang
                        </button>
                    </section>
                    <?php endif; ?>
                    
                    <!-- Results Info -->
                    <div class="results-info">
                        <div class="results-summary">
                            <p>
                                Menampilkan <strong><?php echo mysqli_num_rows($result); ?></strong> dari 
                                <strong><?php echo $total_items; ?></strong> transaksi
                                <?php if(!empty($search)): ?>
                                    untuk "<strong><?php echo htmlspecialchars($search); ?></strong>"
                                <?php endif; ?>
                                <?php if($status !== 'semua'): ?>
                                    dengan status <strong><?php echo ucfirst($status); ?></strong>
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <?php if(mysqli_num_rows($result) == 0): ?>
                        <div class="no-results">
                            <i class="fas fa-receipt"></i>
                            <h3>Tidak ada transaksi ditemukan</h3>
                            <p>Mulai transaksi baru atau coba filter yang berbeda.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Transactions Table -->
                    <div class="transactions-container">
                        <div class="transactions-table">
                            <div class="table-header">
                                <div class="table-row">
                                    <div class="table-cell">ID Transaksi</div>
                                    <div class="table-cell">Pembeli</div>
                                    <div class="table-cell">Tanggal</div>
                                    <div class="table-cell">Total</div>
                                    <div class="table-cell">Status</div>
                                    <div class="table-cell">Metode</div>
                                    <div class="table-cell">Aksi</div>
                                </div>
                            </div>
                            
                            <div class="table-body">
                                <?php while($transaksi = mysqli_fetch_assoc($result)): 
                                    $status_class = '';
                                    $status_icon = '';
                                    switch($transaksi['status_pembayaran']) {
                                        case 'lunas':
                                            $status_class = 'status-success';
                                            $status_icon = 'fas fa-check-circle';
                                            break;
                                        case 'pending':
                                            $status_class = 'status-warning';
                                            $status_icon = 'fas fa-clock';
                                            break;
                                        case 'dibatalkan':
                                            $status_class = 'status-danger';
                                            $status_icon = 'fas fa-times-circle';
                                            break;
                                    }
                                ?>
                                <div class="table-row transaction-row" data-id="<?php echo $transaksi['id_transaksi']; ?>">
                                    <div class="table-cell">
                                        <span class="transaction-id">#<?php echo str_pad($transaksi['id_transaksi'], 6, '0', STR_PAD_LEFT); ?></span>
                                    </div>
                                    
                                    <div class="table-cell">
                                        <div class="customer-info">
                                            <i class="fas fa-user"></i>
                                            <span><?php echo htmlspecialchars($transaksi['nama_pembeli']); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="table-cell">
                                        <?php 
                                        $date = new DateTime($transaksi['tanggal_transaksi']);
                                        echo $date->format('d/m/Y');
                                        ?>
                                        <br>
                                        <small class="time"><?php echo $date->format('H:i'); ?></small>
                                    </div>
                                    
                                    <div class="table-cell">
                                        <div class="total-amount">
                                            Rp <?php echo number_format($transaksi['total_harga'], 0, ',', '.'); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="table-cell">
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <i class="<?php echo $status_icon; ?>"></i>
                                            <?php echo ucfirst($transaksi['status_pembayaran']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="table-cell">
                                        <span class="payment-method">
                                            <?php 
                                            $method_icons = [
                                                'tunai' => 'fa-money-bill',
                                                'transfer_bank' => 'fa-university',
                                                'kartu_kredit' => 'fa-credit-card',
                                                'e-wallet' => 'fa-mobile-alt'
                                            ];
                                            $icon = $method_icons[strtolower($transaksi['metode_pembayaran'])] ?? 'fa-money-bill';
                                            ?>
                                            <i class="fas <?php echo $icon; ?>"></i>
                                            <?php echo ucfirst(str_replace('_', ' ', $transaksi['metode_pembayaran'])); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="table-cell">
                                        <div class="action-buttons">
                                            <button class="btn-action view-detail" onclick="viewTransactionDetail(<?php echo $transaksi['id_transaksi']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <?php if($transaksi['status_pembayaran'] == 'pending'): ?>
                                            <button class="btn-action mark-paid" onclick="markAsPaid(<?php echo $transaksi['id_transaksi']; ?>)">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <?php if($transaksi['status_pembayaran'] != 'dibatalkan'): ?>
                                            <button class="btn-action cancel-transaction" onclick="cancelTransaction(<?php echo $transaksi['id_transaksi']; ?>)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <button class="btn-action print-receipt" onclick="printReceipt(<?php echo $transaksi['id_transaksi']; ?>)">
                                                <i class="fas fa-print"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if($page > 1): ?>
                        <a href="<?php echo buildPageUrl($page - 1, $is_admin_mode); ?>" class="page-link">
                            <i class="fas fa-chevron-left"></i> Sebelumnya
                        </a>
                        <?php endif; ?>
                        
                        <div class="page-numbers">
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $start_page + 4);
                            
                            if($start_page > 1) {
                                echo '<a href="' . buildPageUrl(1, $is_admin_mode) . '" class="page-number">1</a>';
                                if($start_page > 2) echo '<span class="page-dots">...</span>';
                            }
                            
                            for($i = $start_page; $i <= $end_page; $i++):
                            ?>
                            <a href="<?php echo buildPageUrl($i, $is_admin_mode); ?>" 
                               class="page-number <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                            <?php endfor; ?>
                            
                            <?php
                            if($end_page < $total_pages) {
                                if($end_page < $total_pages - 1) echo '<span class="page-dots">...</span>';
                                echo '<a href="' . buildPageUrl($total_pages, $is_admin_mode) . '" class="page-number">' . $total_pages . '</a>';
                            }
                            ?>
                        </div>
                        
                        <?php if($page < $total_pages): ?>
                        <a href="<?php echo buildPageUrl($page + 1, $is_admin_mode); ?>" class="page-link">
                            Selanjutnya <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    <?php endif; ?>
    
    <!-- Modal Detail Transaksi -->
    <div id="detailModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-receipt"></i> Detail Transaksi</h3>
                <button class="modal-close" onclick="closeDetailModal()">&times;</button>
            </div>
            <div class="modal-body" id="detailModalBody">
                <!-- Detail akan diisi via JavaScript -->
            </div>
        </div>
    </div>
    
    <!-- Modal Transaksi Baru -->
    <div id="newTransactionModal" class="modal-overlay" style="display: none;">
        <div class="modal-content wide">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Transaksi Baru</h3>
                <button class="modal-close" onclick="closeNewTransactionModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="newTransactionForm" method="POST" action="../../api/create_transaksi.php">
                    <div class="form-section">
                        <h4><i class="fas fa-user"></i> Data Pembeli</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_nama_pembeli">Nama Pembeli *</label>
                                <input type="text" id="new_nama_pembeli" name="nama_pembeli" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="new_metode_pembayaran">Metode Pembayaran *</label>
                                <select id="new_metode_pembayaran" name="metode_pembayaran" class="form-control" required>
                                    <option value="">Pilih metode</option>
                                    <option value="tunai">Tunai</option>
                                    <option value="transfer_bank">Transfer Bank</option>
                                    <option value="kartu_kredit">Kartu Kredit</option>
                                    <option value="e-wallet">E-Wallet</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h4><i class="fas fa-pills"></i> Daftar Obat</h4>
                        <div class="products-search">
                            <input type="text" id="productSearch" placeholder="Cari obat..." class="form-control">
                            <button type="button" class="btn-add-product" onclick="searchProduct()">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        
                        <div class="products-list" id="productsList">
                            <!-- Daftar obat akan ditampilkan di sini -->
                        </div>
                        
                        <div class="selected-products" id="selectedProducts">
                            <!-- Obat yang dipilih akan ditampilkan di sini -->
                        </div>
                        
                        <div class="total-summary">
                            <div class="total-row">
                                <span>Subtotal:</span>
                                <span id="subtotalAmount">Rp 0</span>
                            </div>
                            <div class="total-row">
                                <span>Total:</span>
                                <span class="total-amount" id="totalAmount">Rp 0</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h4><i class="fas fa-map-marker-alt"></i> Informasi Tambahan</h4>
                        <div class="form-group">
                            <label for="new_alamat_pengiriman">Alamat Pengiriman</label>
                            <textarea id="new_alamat_pengiriman" name="alamat_pengiriman" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="new_catatan">Catatan</label>
                            <textarea id="new_catatan" name="catatan" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn-cancel" onclick="closeNewTransactionModal()">Batal</button>
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-check"></i> Buat Transaksi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Script -->
    <script src="script.js"></script>
    <script>
    // Variabel global untuk mode admin
    const isAdminMode = <?php echo $is_admin_mode ? 'true' : 'false'; ?>;
    
    // Update filters
    function updateStatusFilter(value) {
        const url = new URL(window.location);
        url.searchParams.set('status', value);
        url.searchParams.set('page', '1');
        if (isAdminMode) {
            url.searchParams.set('admin', 'true');
        }
        window.location.href = url.toString();
    }
    
    function updateSortFilter(value) {
        const url = new URL(window.location);
        url.searchParams.set('sort', value);
        url.searchParams.set('page', '1');
        if (isAdminMode) {
            url.searchParams.set('admin', 'true');
        }
        window.location.href = url.toString();
    }
    
    // Modal functions
    function showNewTransactionModal() {
        document.getElementById('newTransactionModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function closeNewTransactionModal() {
        document.getElementById('newTransactionModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    function viewTransactionDetail(transactionId) {
        fetch(`../../api/get_transaction_detail.php?id=${transactionId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showDetailModal(data.transaction, data.items || []);
                } else {
                    alert('Gagal memuat detail transaksi: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat memuat detail transaksi');
            });
    }
    
    function showDetailModal(transaction, items) {
        const modalBody = document.getElementById('detailModalBody');
        
        let itemsHtml = '';
        if (items && items.length > 0) {
            itemsHtml = `
                <div class="detail-section">
                    <h4><i class="fas fa-pills"></i> Item Transaksi</h4>
                    <div class="items-list">
            `;
            
            items.forEach(item => {
                itemsHtml += `
                    <div class="item-row">
                        <div class="item-name">${item.nama_obat || 'Produk'}</div>
                        <div class="item-qty">${item.jumlah || 1} x Rp ${formatNumber(item.harga_satuan || item.price || 0)}</div>
                        <div class="item-total">Rp ${formatNumber(item.subtotal || (item.jumlah * item.harga_satuan) || 0)}</div>
                    </div>
                `;
            });
            
            itemsHtml += `
                    </div>
                </div>
            `;
        } else {
            itemsHtml = `
                <div class="detail-section">
                    <h4><i class="fas fa-pills"></i> Item Transaksi</h4>
                    <p>Tidak ada detail item tersedia</p>
                </div>
            `;
        }
        
        modalBody.innerHTML = `
            <div class="detail-section">
                <div class="detail-row">
                    <span class="detail-label">ID Transaksi:</span>
                    <span class="detail-value">#${transaction.id_transaksi.toString().padStart(6, '0')}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Nama Pembeli:</span>
                    <span class="detail-value">${transaction.nama_pembeli}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Tanggal:</span>
                    <span class="detail-value">${new Date(transaction.tanggal_transaksi).toLocaleString('id-ID')}</span>
                </div>
            </div>
            
            ${itemsHtml}
            
            <div class="detail-section">
                <h4><i class="fas fa-money-bill-wave"></i> Informasi Pembayaran</h4>
                <div class="detail-row">
                    <span class="detail-label">Total Harga:</span>
                    <span class="detail-value total">Rp ${formatNumber(transaction.total_harga)}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value status ${getStatusClass(transaction.status_pembayaran)}">
                        <i class="${getStatusIcon(transaction.status_pembayaran)}"></i>
                        ${transaction.status_pembayaran}
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Metode Pembayaran:</span>
                    <span class="detail-value">${transaction.metode_pembayaran}</span>
                </div>
            </div>
            
            ${transaction.alamat_pengiriman ? `
            <div class="detail-section">
                <h4><i class="fas fa-map-marker-alt"></i> Alamat Pengiriman</h4>
                <p>${transaction.alamat_pengiriman}</p>
            </div>
            ` : ''}
            
            ${transaction.catatan ? `
            <div class="detail-section">
                <h4><i class="fas fa-sticky-note"></i> Catatan</h4>
                <p>${transaction.catatan}</p>
            </div>
            ` : ''}
        `;
        
        document.getElementById('detailModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function closeDetailModal() {
        document.getElementById('detailModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    function getStatusClass(status) {
        switch(status) {
            case 'lunas': return 'status-success';
            case 'pending': return 'status-warning';
            case 'dibatalkan': return 'status-danger';
            default: return '';
        }
    }
    
    function getStatusIcon(status) {
        switch(status) {
            case 'lunas': return 'fas fa-check-circle';
            case 'pending': return 'fas fa-clock';
            case 'dibatalkan': return 'fas fa-times-circle';
            default: return '';
        }
    }
    
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }
    
    // Action functions
    function markAsPaid(transactionId) {
        if (confirm('Apakah Anda yakin ingin menandai transaksi ini sebagai lunas?')) {
            fetch(`../../api/update_transaction_status.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${transactionId}&status=lunas`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Transaksi berhasil ditandai sebagai lunas');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat memperbarui status');
            });
        }
    }
    
    function cancelTransaction(transactionId) {
        if (confirm('Apakah Anda yakin ingin membatalkan transaksi ini?')) {
            fetch(`../../api/update_transaction_status.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${transactionId}&status=dibatalkan`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Transaksi berhasil dibatalkan');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat membatalkan transaksi');
            });
        }
    }
    
    function printReceipt(transactionId) {
        window.open(`../../api/print_receipt.php?id=${transactionId}`, '_blank');
    }
    
    function exportToExcel() {
        const url = new URL(window.location);
        const params = new URLSearchParams(url.search);
        if (isAdminMode) {
            params.set('admin', 'true');
        }
        window.open(`../../api/export_transactions.php?${params.toString()}`, '_blank');
    }
    
    function processCartTransaction() {
        // Redirect to new transaction with cart data
        let url = '?action=new_from_cart';
        if (isAdminMode) {
            url += '&admin=true';
        }
        window.location.href = url;
    }
    
    // Close modals on outside click
    document.getElementById('detailModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDetailModal();
        }
    });
    
    document.getElementById('newTransactionModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeNewTransactionModal();
        }
    });
    </script>
</body>
</html>

<?php
function buildPageUrl($page, $is_admin_mode = false) {
    global $status, $search, $sort;
    $params = [];
    if ($status != 'semua') $params['status'] = $status;
    if (!empty($search)) $params['search'] = $search;
    if ($sort != 'terbaru') $params['sort'] = $sort;
    if ($is_admin_mode) $params['admin'] = 'true';
    $params['page'] = $page;
    return '?' . http_build_query($params);
}
?>