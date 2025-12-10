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

// ===== DEFINISI FUNGSI PHP HARUS DI ATAS =====
function buildPageUrl($page) {
    global $status, $search, $sort;
    $params = [];
    if ($status != 'semua') $params['status'] = $status;
    if (!empty($search)) $params['search'] = $search;
    if ($sort != 'terbaru') $params['sort'] = $sort;
    $params['page'] = $page;
    return 'transaksi.php?' . http_build_query($params);
}

// Definisikan page title
$page_title = "Manajemen Transaksi - Admin Apotek Sehat";

// Get parameters for filtering
$status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : 'semua';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$sort = isset($_GET['sort']) ? mysqli_real_escape_string($conn, $_GET['sort']) : 'terbaru';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query dengan prepared statement
$query = "SELECT t.* FROM transaksi t WHERE 1=1";
$params = [];
$types = "";

// Filter berdasarkan status
if ($status !== 'semua') {
    $query .= " AND t.status_pembayaran = ?";
    $params[] = $status;
    $types .= "s";
}

// Filter berdasarkan pencarian
if (!empty($search)) {
    $query .= " AND (t.id_transaksi LIKE ? 
                    OR t.nama_pembeli LIKE ?
                    OR t.metode_pembayaran LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

// Sorting
switch ($sort) {
    case 'terlama': $query .= " ORDER BY t.tanggal_transaksi ASC"; break;
    case 'total_tertinggi': $query .= " ORDER BY t.total_harga DESC"; break;
    case 'total_terendah': $query .= " ORDER BY t.total_harga ASC"; break;
    case 'terbaru': default: $query .= " ORDER BY t.tanggal_transaksi DESC"; break;
}

// Count query dengan prepared statement (FIXED)
$count_query = "SELECT COUNT(*) as total FROM transaksi t WHERE 1=1";
$count_params = [];
$count_types = "";

if ($status !== 'semua') {
    $count_query .= " AND t.status_pembayaran = ?";
    $count_params[] = $status;
    $count_types .= "s";
}

if (!empty($search)) {
    $count_query .= " AND (t.id_transaksi LIKE ? 
                         OR t.nama_pembeli LIKE ?
                         OR t.metode_pembayaran LIKE ?)";
    $search_param = "%{$search}%";
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_types .= "sss";
}

$stmt_count = mysqli_prepare($conn, $count_query);
if ($count_params) {
    mysqli_stmt_bind_param($stmt_count, $count_types, ...$count_params);
}
mysqli_stmt_execute($stmt_count);
$count_result = mysqli_stmt_get_result($stmt_count);
$total_items = mysqli_fetch_assoc($count_result)['total'] ?? 0;
$total_pages = ceil($total_items / $limit);
if (isset($stmt_count)) {
    mysqli_stmt_close($stmt_count);
}

$query .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

// Execute with prepared statement
$stmt = mysqli_prepare($conn, $query);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get total pendapatan bulan ini
$current_month = date('Y-m');
$pendapatan_bulanan_query = mysqli_query($conn, 
    "SELECT COALESCE(SUM(total_harga), 0) as total 
     FROM transaksi 
     WHERE DATE_FORMAT(tanggal_transaksi, '%Y-%m') = '$current_month'
     AND status_pembayaran = 'lunas'"
);

$pendapatan_bulanan = 0;
if ($pendapatan_bulanan_query) {
    $row = mysqli_fetch_assoc($pendapatan_bulanan_query);
    $pendapatan_bulanan = $row['total'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Tambahan kecil untuk transaksi page */
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .content-title h2 {
            font-size: 1.5rem;
            color: #1f2937;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .content-title h2 i {
            color: #10b981;
        }
        
        .content-title p {
            color: #6b7280;
            font-size: 0.95rem;
        }
        
        /* Filter card yang lebih kecil */
        .filter-card-compact {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            margin-bottom: 25px;
        }
        
        .filter-form-compact {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        .filter-group-compact {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .filter-group-compact label {
            font-weight: 500;
            color: #374151;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .filter-group-compact label i {
            color: #6b7280;
        }
        
        .filter-group-compact input,
        .filter-group-compact select {
            padding: 10px 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.9rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
        }
        
        .filter-group-compact input:focus,
        .filter-group-compact select:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        
        .filter-actions-compact {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .btn-filter-compact {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-filter-compact:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
        }
        
        .btn-secondary-compact {
            background: #f9fafb;
            color: #374151;
            border: 2px solid #e5e7eb;
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-secondary-compact:hover {
            background: #f3f4f6;
        }
        
        /* Results info */
        .results-info-compact {
            background: #f8fafc;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 20px;
            color: #6b7280;
            font-size: 0.9rem;
            border: 1px solid #e5e7eb;
        }
        
        .results-info-compact i {
            color: #10b981;
            margin-right: 8px;
        }
        
        .results-info-compact strong {
            color: #1f2937;
        }
        
        /* Table tanpa search di header */
        .table-header-compact {
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-header-compact h3 {
            font-size: 1.1rem;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .table-header-compact h3 i {
            color: #10b981;
        }
        
        /* Modal detail yang lebih menarik */
        .modal-detail-enhanced {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .modal-header-enhanced {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 20px 25px;
            border-radius: 12px 12px 0 0;
            border-bottom: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header-enhanced h3 {
            color: white;
            font-size: 1.3rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-close-enhanced {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }
        
        .modal-close-enhanced:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }
        
        .modal-body-enhanced {
            padding: 25px;
        }
        
        .detail-section-enhanced {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb;
        }
        
        .detail-section-enhanced:last-child {
            margin-bottom: 0;
        }
        
        .detail-section-enhanced h4 {
            font-size: 1.1rem;
            color: #1f2937;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f3f4f6;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .detail-section-enhanced h4 i {
            color: #10b981;
            width: 20px;
        }
        
        .detail-grid-enhanced {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .detail-item-enhanced {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .detail-label-enhanced {
            font-size: 0.85rem;
            color: #6b7280;
            font-weight: 500;
        }
        
        .detail-value-enhanced {
            font-weight: 500;
            color: #1f2937;
            font-size: 0.95rem;
        }
        
        .detail-value-enhanced.total {
            font-size: 1.2rem;
            font-weight: 700;
            color: #10b981;
        }
        
        .items-list-enhanced {
            background: #f8fafc;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
        }
        
        .item-row-enhanced {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
            align-items: center;
        }
        
        .item-row-enhanced:last-child {
            border-bottom: none;
        }
        
        .item-name-enhanced {
            font-weight: 500;
            color: #1f2937;
        }
        
        .item-qty-enhanced {
            color: #6b7280;
            font-size: 0.9rem;
            text-align: center;
            background: #f3f4f6;
            padding: 4px 8px;
            border-radius: 6px;
        }
        
        .item-total-enhanced {
            font-weight: 600;
            color: #10b981;
            text-align: right;
        }
        
        /* Loading overlay */
        #globalLoading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 3000;
        }
        
        #globalLoading .spinner {
            width: 50px;
            height: 50px;
            border: 3px solid #e5e7eb;
            border-top-color: #10b981;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .filter-form-compact {
                grid-template-columns: 1fr;
            }
            
            .filter-actions-compact {
                justify-content: flex-end;
            }
            
            .detail-grid-enhanced {
                grid-template-columns: 1fr;
            }
            
            .item-row-enhanced {
                grid-template-columns: 1fr;
                gap: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">

    <?php include 'sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="main-header">
                <div class="header-left">
                    <h1><i class="fas fa-receipt"></i> Manajemen Transaksi</h1>
                    <p>Kelola semua transaksi sistem Apotek Sehat</p>
                </div>
                <div class="header-right">
                    <div class="income-display">
                        <div class="income-title">
                            <p>Pendapatan Bulan Ini</p>
                        </div>
                        <div class="income-details">
                            <span class="income-icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </span>
                            <span class="income-amount"> Rp <?php echo number_format($pendapatan_bulanan, 0, ',', '.'); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Content Area -->
            <div class="content">
                <!-- Filter Section -->
                <div class="filter-card-compact">
                    <form method="GET" action="" class="filter-form-compact">
                        <div class="filter-group-compact">
                            <label><i class="fas fa-search"></i> Cari Transaksi</label>
                            <input type="text" name="search" 
                                   placeholder="Cari ID, nama, atau metode..."
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <div class="filter-group-compact">
                            <label><i class="fas fa-filter"></i> Status</label>
                            <select name="status" onchange="this.form.submit()">
                                <option value="semua" <?php echo $status == 'semua' ? 'selected' : ''; ?>>Semua Status</option>
                                <option value="lunas" <?php echo $status == 'lunas' ? 'selected' : ''; ?>>Lunas</option>
                                <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="dibatalkan" <?php echo $status == 'dibatalkan' ? 'selected' : ''; ?>>Dibatalkan</option>
                            </select>
                        </div>
                        
                        <div class="filter-group-compact">
                            <label><i class="fas fa-sort"></i> Urutkan</label>
                            <select name="sort" onchange="this.form.submit()">
                                <option value="terbaru" <?php echo $sort == 'terbaru' ? 'selected' : ''; ?>>Terbaru</option>
                                <option value="terlama" <?php echo $sort == 'terlama' ? 'selected' : ''; ?>>Terlama</option>
                                <option value="total_tertinggi" <?php echo $sort == 'total_tertinggi' ? 'selected' : ''; ?>>Total Tertinggi</option>
                                <option value="total_terendah" <?php echo $sort == 'total_terendah' ? 'selected' : ''; ?>>Total Terendah</option>
                            </select>
                        </div>
                        
                        <div class="filter-group-compact">
                            <div class="filter-actions-compact">
                                <button type="submit" class="btn-filter-compact">
                                    <i class="fas fa-filter"></i> Terapkan Filter
                                </button>
                                <a href="transaksi.php" class="btn-secondary-compact">
                                    <i class="fas fa-times"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Results Info -->
                <div class="results-info-compact">
                    <p>
                        <i class="fas fa-info-circle"></i>
                        Menampilkan <strong><?php echo mysqli_num_rows($result); ?></strong> dari 
                        <strong><?php echo $total_items; ?></strong> transaksi
                        <?php if(!empty($search)): ?>
                            untuk "<strong><?php echo htmlspecialchars($search); ?></strong>"
                        <?php endif; ?>
                        <?php if($status !== 'semua'): ?>
                            dengan status <span class="status-badge <?php echo $status; ?>"><?php echo ucfirst($status); ?></span>
                        <?php endif; ?>
                    </p>
                </div>
                
                <!-- Transactions Table -->
                <div class="table-section">
                    <div class="card">
                        <div class="table-header-compact">
                            <h3><i class="fas fa-list"></i> Daftar Transaksi</h3>
                            <button class="btn-refresh" onclick="location.reload()" title="Refresh">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        
                        <div class="card-body">
                            <?php if(mysqli_num_rows($result) == 0): ?>
                            <div class="empty-state">
                                <i class="fas fa-receipt"></i>
                                <h4>Tidak ada transaksi ditemukan</h4>
                                <p>Mulai transaksi baru atau coba filter yang berbeda.</p>
                            </div>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table id="transactionsTable">
                                    <thead>
                                        <tr>
                                            <th>ID Transaksi</th>
                                            <th>Pembeli</th>
                                            <th>Tanggal</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Metode</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($transaksi = mysqli_fetch_assoc($result)): 
                                            $status_class = $transaksi['status_pembayaran'] == 'lunas' ? 'success' : 
                                                           ($transaksi['status_pembayaran'] == 'pending' ? 'warning' : 'danger');
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="transaction-id">
                                                    <strong>#TRX<?php echo str_pad($transaksi['id_transaksi'], 6, '0', STR_PAD_LEFT); ?></strong>
                                                </div>
                                            </td>
                                            
                                            <td>
                                                <div class="customer-info">
                                                    <div class="customer-avatar">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                    <div class="customer-details">
                                                        <span class="customer-name"><?php echo htmlspecialchars($transaksi['nama_pembeli']); ?></span>
                                                        <?php if(!empty($transaksi['alamat_pengiriman'])): ?>
                                                        <small class="customer-address">
                                                            <i class="fas fa-map-marker-alt"></i>
                                                            <?php echo htmlspecialchars(substr($transaksi['alamat_pengiriman'], 0, 30)) . '...'; ?>
                                                        </small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            
                                            <td>
                                                <div class="transaction-date">
                                                    <span class="date">
                                                        <?php echo date('d M Y', strtotime($transaksi['tanggal_transaksi'])); ?>
                                                    </span>
                                                    <small class="time"><?php echo date('H:i', strtotime($transaksi['tanggal_transaksi'])); ?></small>
                                                </div>
                                            </td>
                                            
                                            <td>
                                                <div class="transaction-total">
                                                    <strong>Rp <?php echo number_format($transaksi['total_harga'], 0, ',', '.'); ?></strong>
                                                </div>
                                            </td>
                                            
                                            <td>
                                                <span class="status-badge <?php echo $status_class; ?>">
                                                    <i class="fas fa-<?php echo $status_class == 'success' ? 'check-circle' : 
                                                                       ($status_class == 'warning' ? 'exclamation-circle' : 'times-circle'); ?>"></i>
                                                    <?php echo ucfirst($transaksi['status_pembayaran']); ?>
                                                </span>
                                            </td>
                                            
                                            <td>
                                                <span class="payment-method">
                                                    <?php 
                                                    $method_icons = [
                                                        'tunai' => 'fa-money-bill',
                                                        'transfer_bank' => 'fa-university',
                                                        'kartu_kredit' => 'fa-credit-card',
                                                        'e-wallet' => 'fa-wallet'
                                                    ];
                                                    $icon = $method_icons[strtolower($transaksi['metode_pembayaran'])] ?? 'fa-money-bill';
                                                    ?>
                                                    <i class="fas <?php echo $icon; ?>"></i>
                                                    <?php echo ucfirst(str_replace('_', ' ', $transaksi['metode_pembayaran'])); ?>
                                                </span>
                                            </td>
                                            
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn-action btn-view" onclick="Transaksi.viewDetail(<?php echo $transaksi['id_transaksi']; ?>)" title="Lihat Detail">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    
                                                    <button class="btn-action btn-delete" onclick="Transaksi.confirmDelete(<?php echo $transaksi['id_transaksi']; ?>)" title="Hapus Transaksi">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if($total_pages > 1): ?>
                        <div class="card-footer">
                            <div class="pagination">
                                <?php if($page > 1): ?>
                                <a href="<?php echo buildPageUrl($page - 1); ?>" class="page-link prev">
                                    <i class="fas fa-chevron-left"></i> Sebelumnya
                                </a>
                                <?php endif; ?>
                                
                                <div class="page-numbers">
                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $start_page + 4);
                                    
                                    if($start_page > 1) {
                                        echo '<a href="' . buildPageUrl(1) . '" class="page-number">1</a>';
                                        if($start_page > 2) echo '<span class="page-dots">...</span>';
                                    }
                                    
                                    for($i = $start_page; $i <= $end_page; $i++):
                                    ?>
                                    <a href="<?php echo buildPageUrl($i); ?>" 
                                       class="page-number <?php echo $i == $page ? 'active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                    <?php endfor; ?>
                                    
                                    <?php
                                    if($end_page < $total_pages) {
                                        if($end_page < $total_pages - 1) echo '<span class="page-dots">...</span>';
                                        echo '<a href="' . buildPageUrl($total_pages) . '" class="page-number">' . $total_pages . '</a>';
                                    }
                                    ?>
                                </div>
                                
                                <?php if($page < $total_pages): ?>
                                <a href="<?php echo buildPageUrl($page + 1); ?>" class="page-link next">
                                    Selanjutnya <i class="fas fa-chevron-right"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Detail Transaksi -->
    <div id="detailModal" class="modal-overlay">
        <div class="modal-content modal-detail-enhanced">
            <div class="modal-header-enhanced">
                <h3><i class="fas fa-receipt"></i> Detail Transaksi</h3>
                <button class="modal-close-enhanced" onclick="Transaksi.closeDetail()">&times;</button>
            </div>
            <div class="modal-body-enhanced" id="detailModalBody">
                Loading...
            </div>
        </div>
    </div>
    
    <!-- Modal Konfirmasi Hapus -->
    <div id="deleteModal" class="modal-overlay">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Konfirmasi Hapus</h3>
                <button class="modal-close" onclick="Transaksi.closeDelete()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus transaksi ini?</p>
                <p class="text-muted">Data yang sudah dihapus tidak dapat dikembalikan.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="Transaksi.closeDelete()">Batal</button>
                <button class="btn btn-danger" id="confirmDeleteBtn">Hapus</button>
            </div>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div id="globalLoading" class="loading-overlay">
        <div class="spinner"></div>
    </div>
    
    <script src="script.js"></script>
    <script>
    // Namespace untuk fungsi transaksi agar tidak konflik dengan script.js
    const Transaksi = {
        currentId: null,
        
        // Format number helper
        formatNumber: function(num) {
            if (!num) return '0';
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        },
        
        // Format date helper
        formatDate: function(dateString) {
            try {
                const date = new Date(dateString);
                return date.toLocaleDateString('id-ID', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } catch (e) {
                return dateString;
            }
        },
        
        // Escape HTML helper
        escapeHTML: function(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },
        
        // Show loading
        showLoading: function() {
            const loading = document.getElementById('globalLoading');
            if (loading) loading.style.display = 'flex';
        },
        
        // Hide loading
        hideLoading: function() {
            const loading = document.getElementById('globalLoading');
            if (loading) loading.style.display = 'none';
        },
        
        // Show notification (gunakan dari script.js jika ada)
        showNotification: function(message, type = 'info') {
            if (window.AdminPanel && typeof window.AdminPanel.showNotification === 'function') {
                window.AdminPanel.showNotification(message, type);
            } else if (window.showNotification && typeof window.showNotification === 'function') {
                window.showNotification(message, type);
            } else {
                alert(type.toUpperCase() + ': ' + message);
            }
        },
        
        // View transaction detail
        viewDetail: async function(transactionId) {
            console.log('Viewing transaction:', transactionId);
            
            try {
                this.showLoading();
                
                // Coba beberapa path API yang mungkin
                const apiPaths = [
                    `../../api/get_transaction_detail.php?id=${transactionId}`,
                    `../api/get_transaction_detail.php?id=${transactionId}`,
                    `api/get_transaction_detail.php?id=${transactionId}`
                ];
                
                let response = null;
                let data = null;
                
                // Coba semua path sampai berhasil
                for (const path of apiPaths) {
                    try {
                        console.log('Trying API path:', path);
                        response = await fetch(path);
                        if (response.ok) {
                            data = await response.json();
                            console.log('API Response:', data);
                            break;
                        }
                    } catch (e) {
                        console.log('Path failed:', path, e);
                        continue;
                    }
                }
                
                if (!response || !response.ok) {
                    throw new Error('Tidak dapat menghubungi API');
                }
                
                if (data.success) {
                    this.showDetailModal(data.transaction, data.items || []);
                } else {
                    this.showNotification('Gagal memuat detail: ' + (data.message || 'Unknown error'), 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                this.showNotification('Terjadi kesalahan saat memuat detail: ' + error.message, 'error');
            } finally {
                this.hideLoading();
            }
        },
        
        // Show detail modal
        showDetailModal: function(transaction, items) {
            const modal = document.getElementById('detailModal');
            const modalBody = document.getElementById('detailModalBody');
            
            if (!modal || !modalBody) {
                this.showNotification('Modal tidak ditemukan', 'error');
                return;
            }
            
            // Format items
            let itemsHtml = '';
            if (items && items.length > 0) {
                itemsHtml = `
                    <div class="detail-section-enhanced">
                        <h4><i class="fas fa-shopping-cart"></i> Item Pembelian</h4>
                        <div class="items-list-enhanced">
                            ${items.map(item => `
                                <div class="item-row-enhanced">
                                    <div class="item-name-enhanced">${this.escapeHTML(item.nama_obat)}</div>
                                    <div class="item-qty-enhanced">${item.jumlah} x Rp ${this.formatNumber(item.harga)}</div>
                                    <div class="item-total-enhanced">Rp ${this.formatNumber(item.subtotal)}</div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            }
            
            // Format status class untuk modal
            const statusClass = transaction.status_pembayaran === 'lunas' ? 'success' : 
                              (transaction.status_pembayaran === 'pending' ? 'warning' : 'danger');
            
            // Status badge color mapping
            const statusColors = {
                'lunas': '#10b981',
                'pending': '#f59e0b',
                'dibatalkan': '#ef4444'
            };
            
            const statusColor = statusColors[transaction.status_pembayaran] || '#6b7280';
            
            // Set content
            modalBody.innerHTML = `
                <div class="detail-section-enhanced">
                    <h4><i class="fas fa-info-circle"></i> Informasi Transaksi</h4>
                    <div class="detail-grid-enhanced">
                        <div class="detail-item-enhanced">
                            <span class="detail-label-enhanced">ID Transaksi</span>
                            <span class="detail-value-enhanced">#TRX${transaction.id_transaksi.toString().padStart(6, '0')}</span>
                        </div>
                        <div class="detail-item-enhanced">
                            <span class="detail-label-enhanced">Tanggal</span>
                            <span class="detail-value-enhanced">${this.formatDate(transaction.tanggal_transaksi)}</span>
                        </div>
                        <div class="detail-item-enhanced">
                            <span class="detail-label-enhanced">Nama Pembeli</span>
                            <span class="detail-value-enhanced">${this.escapeHTML(transaction.nama_pembeli)}</span>
                        </div>
                        <div class="detail-item-enhanced">
                            <span class="detail-label-enhanced">Status</span>
                            <span class="detail-value-enhanced">
                                <span class="status-badge" style="background: ${statusColor}15; color: ${statusColor}; border: 1px solid ${statusColor}30;">
                                    <i class="fas fa-${statusClass === 'success' ? 'check-circle' : 
                                                     (statusClass === 'warning' ? 'exclamation-circle' : 'times-circle')}"></i>
                                    ${this.escapeHTML(transaction.status_pembayaran)}
                                </span>
                            </span>
                        </div>
                    </div>
                </div>
                
                ${itemsHtml}
                
                <div class="detail-section-enhanced">
                    <h4><i class="fas fa-money-bill-wave"></i> Informasi Pembayaran</h4>
                    <div class="detail-grid-enhanced">
                        <div class="detail-item-enhanced">
                            <span class="detail-label-enhanced">Metode Pembayaran</span>
                            <span class="detail-value-enhanced">${this.escapeHTML(transaction.metode_pembayaran)}</span>
                        </div>
                        <div class="detail-item-enhanced">
                            <span class="detail-label-enhanced">Total Pembayaran</span>
                            <span class="detail-value-enhanced total">Rp ${this.formatNumber(transaction.total_harga)}</span>
                        </div>
                    </div>
                </div>
                
                ${transaction.alamat_pengiriman ? `
                <div class="detail-section-enhanced">
                    <h4><i class="fas fa-map-marker-alt"></i> Alamat Pengiriman</h4>
                    <div style="background: #f8fafc; padding: 15px; border-radius: 8px; margin-top: 10px;">
                        <p style="color: #1f2937; line-height: 1.6;">${this.escapeHTML(transaction.alamat_pengiriman)}</p>
                    </div>
                </div>
                ` : ''}
                
                ${transaction.catatan ? `
                <div class="detail-section-enhanced">
                    <h4><i class="fas fa-sticky-note"></i> Catatan</h4>
                    <div style="background: #f8fafc; padding: 15px; border-radius: 8px; margin-top: 10px;">
                        <p style="color: #1f2937; line-height: 1.6;">${this.escapeHTML(transaction.catatan)}</p>
                    </div>
                </div>
                ` : ''}
            `;
            
            // Show modal
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        },
        
        // Confirm delete
        confirmDelete: function(transactionId) {
            console.log('Confirm delete:', transactionId);
            this.currentId = transactionId;
            const modal = document.getElementById('deleteModal');
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        },
        
        // Delete transaction
        deleteTransaction: async function() {
            if (!this.currentId) return;
            
            try {
                this.showLoading();
                
                const response = await fetch('../../api/delete_transaksi.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: this.currentId })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.showNotification('Transaksi berhasil dihapus', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    this.showNotification('Gagal menghapus: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                this.showNotification('Terjadi kesalahan saat menghapus', 'error');
            } finally {
                this.hideLoading();
                this.closeDelete();
            }
        },
        
        // Close detail modal
        closeDetail: function() {
            const modal = document.getElementById('detailModal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        },
        
        // Close delete modal
        closeDelete: function() {
            const modal = document.getElementById('deleteModal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
            this.currentId = null;
        }
    };
    
    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Transaksi module initialized');
        
        // Setup delete button
        const deleteBtn = document.getElementById('confirmDeleteBtn');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', function() {
                Transaksi.deleteTransaction();
            });
        }
        
        // Close modal on outside click
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    if (this.id === 'detailModal') Transaksi.closeDetail();
                    if (this.id === 'deleteModal') Transaksi.closeDelete();
                }
            });
        });
        
        // Escape key to close modal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                Transaksi.closeDetail();
                Transaksi.closeDelete();
            }
        });
    });
    </script>
</body>
</html>