<?php
// apotek/screens/obat/index.php
require_once '../../config.php';

// Start session JIKA BELUM
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include auth functions
require_once '../../includes/auth.php';

// Debug session untuk troubleshooting
// echo "<pre>Session Debug: ";
// print_r($_SESSION);
// echo "</pre>";

// Cek apakah user login (admin/user) atau guest
$isLoggedIn = isLoggedIn();
$currentUser = getCurrentUser($conn);

// Dapatkan role dari session atau database
$userRole = $_SESSION['role'] ?? 'guest';
// Jika tidak ada di session, coba ambil dari currentUser
if ($userRole === 'guest' && $currentUser && isset($currentUser['role'])) {
    $userRole = $currentUser['role'];
    $_SESSION['role'] = $userRole; // Simpan ke session
}

// Debug info untuk admin
// echo "isLoggedIn: " . ($isLoggedIn ? 'true' : 'false') . "<br>";
// echo "User role: " . $userRole . "<br>";
// echo "Current user: ";
// print_r($currentUser);

// Get parameters
$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : 'semua';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'terbaru';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Build query - TAMPILKAN SEMUA OBAT untuk SEMUA user (admin, user, guest)
$query = "SELECT * FROM obat WHERE 1=1";
$count_query = "SELECT COUNT(*) as total FROM obat WHERE 1=1";

if ($kategori !== 'semua') {
    $kategori_clean = mysqli_real_escape_string($conn, $kategori);
    $query .= " AND kategori = '$kategori_clean'";
    $count_query .= " AND kategori = '$kategori_clean'";
}

if (!empty($search)) {
    $search_term = mysqli_real_escape_string($conn, $search);
    $query .= " AND (nama_obat LIKE '%$search_term%' 
                    OR deskripsi LIKE '%$search_term%' 
                    OR indikasi LIKE '%$search_term%')";
    $count_query .= " AND (nama_obat LIKE '%$search_term%' 
                         OR deskripsi LIKE '%$search_term%' 
                         OR indikasi LIKE '%$search_term%')";
}

switch ($sort) {
    case 'termurah':
        $query .= " ORDER BY harga ASC";
        break;
    case 'termahal':
        $query .= " ORDER BY harga DESC";
        break;
    case 'stok_terbanyak':
        $query .= " ORDER BY stok DESC";
        break;
    case 'nama_az':
        $query .= " ORDER BY nama_obat ASC";
        break;
    case 'terbaru':
    default:
        $query .= " ORDER BY created_at DESC";
        break;
}

$query .= " LIMIT $limit OFFSET $offset";

// Execute queries
$result = mysqli_query($conn, $query);
if (!$result) {
    die("Query error: " . mysqli_error($conn));
}

$count_result = mysqli_query($conn, $count_query);
if (!$count_result) {
    die("Count query error: " . mysqli_error($conn));
}

$total_items = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_items / $limit);

// Get categories
$categories_result = mysqli_query($conn, "
    SELECT DISTINCT kategori, COUNT(*) as jumlah
    FROM obat 
    GROUP BY kategori 
    ORDER BY kategori ASC
");

$categories = [];
while ($cat = mysqli_fetch_assoc($categories_result)) {
    $categories[] = $cat;
}

// Get popular categories
$popular_categories = mysqli_query($conn, "
    SELECT kategori, COUNT(*) as jumlah
    FROM obat 
    GROUP BY kategori 
    ORDER BY jumlah DESC 
    LIMIT 5
");

// FUNGSI: Build URL untuk pagination
function buildPageUrl($page)
{
    global $kategori, $search, $sort;
    $params = [];
    if ($kategori != 'semua') $params['kategori'] = $kategori;
    if (!empty($search)) $params['search'] = $search;
    if ($sort != 'terbaru') $params['sort'] = $sort;
    $params['page'] = $page;
    return '?' . http_build_query($params);
}

// FUNGSI: Safe escape untuk output
function safeOutput($string)
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Obat - Apotek Sehat</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="container">
        <?php
        // Tampilkan sidebar berbeda untuk admin/user
        // PERBAIKAN: Gunakan pengecekan yang lebih tepat
        $showAdminSidebar = false;

        // Cek beberapa kondisi untuk admin
        if ($isLoggedIn) {
            // 1. Cek session role
            if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
                $showAdminSidebar = true;
            }
            // 2. Cek currentUser role
            elseif ($currentUser && isset($currentUser['role']) && $currentUser['role'] === 'admin') {
                $showAdminSidebar = true;
                $_SESSION['role'] = 'admin'; // Update session
            }
            // 3. Cek username (alternatif)
            elseif (isset($_SESSION['username']) && ($_SESSION['username'] === 'admin' || strpos($_SESSION['username'], 'admin') !== false)) {
                $showAdminSidebar = true;
                $_SESSION['role'] = 'admin'; // Set role
            }
        }

        // Debug info sidebar
        // echo "<!-- DEBUG: showAdminSidebar = " . ($showAdminSidebar ? 'true' : 'false') . " -->";
        // echo "<!-- DEBUG: userRole = $userRole -->";

        if ($showAdminSidebar) {
            include '../../includes/sidebar.php';
        } else {
            include '../../includes/sidebar.php';
        }
        ?>

        <main class="main-content">
            <!-- Header -->
            <header class="page-header">
                <div class="header-content">
                    <h1><i class="fas fa-prescription-bottle"></i> Daftar Obat</h1>
                    <p class="subtitle">Temukan obat yang Anda butuhkan dengan mudah</p>

                    <!-- User Info jika login -->
                    <?php if ($isLoggedIn): ?>
                    <div class="user-info">
                        <span class="user-name">
                            <i class="fas fa-user"></i>
                            <?php echo safeOutput($currentUser['nama_lengkap'] ?? $_SESSION['nama_lengkap'] ?? 'User'); ?>
                            <span class="user-role">(<?php echo safeOutput($userRole); ?>)</span>
                        </span>
                        <?php if ($userRole === 'admin'): ?>
                        <span class="admin-badge">
                            <i class="fas fa-shield-alt"></i> Admin Mode
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </header>

            <div class="content">
                <!-- Admin Action Buttons (hanya untuk admin) -->
                <?php if ($userRole === 'admin'): ?>
                <div class="admin-actions" style="
                    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
                    padding: 15px 20px;
                    border-radius: 12px;
                    margin-bottom: 20px;
                    border: 1px solid #bae6fd;
                    display: flex;
                    gap: 10px;
                    flex-wrap: wrap;
                ">
                    <a href="../../admin/obat/tambah.php" class="admin-action-btn" style="
                        background: #10b981;
                        color: white;
                        padding: 8px 16px;
                        border-radius: 8px;
                        text-decoration: none;
                        display: flex;
                        align-items: center;
                        gap: 8px;
                        font-weight: 500;
                    ">
                        <i class="fas fa-plus"></i> Tambah Obat Baru
                    </a>
                    <a href="../../admin/obat/kelola.php" class="admin-action-btn" style="
                        background: #3b82f6;
                        color: white;
                        padding: 8px 16px;
                        border-radius: 8px;
                        text-decoration: none;
                        display: flex;
                        align-items: center;
                        gap: 8px;
                        font-weight: 500;
                    ">
                        <i class="fas fa-edit"></i> Kelola Obat
                    </a>
                    <a href="../../admin/transaksi/" class="admin-action-btn" style="
                        background: #8b5cf6;
                        color: white;
                        padding: 8px 16px;
                        border-radius: 8px;
                        text-decoration: none;
                        display: flex;
                        align-items: center;
                        gap: 8px;
                        font-weight: 500;
                    ">
                        <i class="fas fa-receipt"></i> Lihat Transaksi
                    </a>
                </div>
                <?php endif; ?>

                <!-- Search and Filter -->
                <section class="filter-section">
                    <div class="search-container">
                        <form method="GET" action="" class="search-form" id="searchForm">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" name="search" id="searchInput"
                                    placeholder="Cari obat (nama, indikasi, deskripsi)..."
                                    value="<?php echo safeOutput($search); ?>" autocomplete="off">
                                <button type="submit" class="btn-search">
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                            <input type="hidden" name="kategori" value="<?php echo safeOutput($kategori); ?>">
                            <input type="hidden" name="sort" value="<?php echo safeOutput($sort); ?>">
                            <input type="hidden" name="page" value="1">
                        </form>

                        <div class="filter-controls">
                            <div class="sort-dropdown">
                                <select name="sort" id="sortSelect" onchange="updateSort(this.value)">
                                    <option value="terbaru" <?php echo $sort == 'terbaru' ? 'selected' : ''; ?>>Terbaru
                                    </option>
                                    <option value="termurah" <?php echo $sort == 'termurah' ? 'selected' : ''; ?>>Harga:
                                        Termurah</option>
                                    <option value="termahal" <?php echo $sort == 'termahal' ? 'selected' : ''; ?>>Harga:
                                        Termahal</option>
                                    <option value="stok_terbanyak"
                                        <?php echo $sort == 'stok_terbanyak' ? 'selected' : ''; ?>>Stok Terbanyak
                                    </option>
                                    <option value="nama_az" <?php echo $sort == 'nama_az' ? 'selected' : ''; ?>>Nama:
                                        A-Z</option>
                                </select>
                                <i class="fas fa-sort"></i>
                            </div>

                            <div class="view-toggle">
                                <button class="view-btn active" data-view="grid">
                                    <i class="fas fa-th-large"></i>
                                </button>
                                <button class="view-btn" data-view="list">
                                    <i class="fas fa-list"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Categories Filter -->
                <section class="categories-section">
                    <h3 class="section-title">
                        <i class="fas fa-filter"></i>
                        Filter Kategori
                    </h3>

                    <div class="categories-filter">
                        <a href="?kategori=semua&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>"
                            class="category-filter-btn <?php echo $kategori == 'semua' ? 'active' : ''; ?>">
                            <i class="fas fa-th"></i>
                            <span>Semua</span>
                            <span class="badge"><?php echo $total_items; ?></span>
                        </a>

                        <?php foreach ($categories as $cat):
                            $icon_map = [
                                'vitamin' => 'fa-capsules',
                                'antibiotik' => 'fa-bacteria',
                                'analgesik' => 'fa-head-side-virus',
                                'suplemen' => 'fa-heart',
                                'obat_batuk' => 'fa-lungs',
                                'obat_luar' => 'fa-spray-can',
                                'obat_mata' => 'fa-eye',
                                'obat_lambung' => 'fa-stomach',
                                'obat_asma' => 'fa-wind',
                                'antihistamin' => 'fa-allergies',
                                'steroid' => 'fa-prescription-bottle-alt'
                            ];
                            $cat_lower = strtolower($cat['kategori']);
                            $icon = isset($icon_map[$cat_lower]) ? $icon_map[$cat_lower] : 'fa-pills';
                            $cat_display = ucwords(str_replace('_', ' ', $cat['kategori']));
                        ?>
                        <a href="?kategori=<?php echo urlencode($cat['kategori']); ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>"
                            class="category-filter-btn <?php echo $kategori == $cat['kategori'] ? 'active' : ''; ?>">
                            <i class="fas <?php echo $icon; ?>"></i>
                            <span><?php echo safeOutput($cat_display); ?></span>
                            <span class="badge"><?php echo $cat['jumlah']; ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>

                    <!-- Popular Categories -->
                    <div class="popular-categories">
                        <h4><i class="fas fa-fire"></i> Kategori Populer</h4>
                        <div class="popular-tags">
                            <?php
                            mysqli_data_seek($popular_categories, 0);
                            while ($pop = mysqli_fetch_assoc($popular_categories)):
                                $pop_display = ucwords(str_replace('_', ' ', $pop['kategori']));
                            ?>
                            <a href="?kategori=<?php echo urlencode($pop['kategori']); ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>"
                                class="popular-tag">
                                <?php echo safeOutput($pop_display); ?>
                                <span class="tag-count"><?php echo $pop['jumlah']; ?></span>
                            </a>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </section>

                <!-- Results Info -->
                <div class="results-info">
                    <div class="results-summary">
                        <p>
                            Menampilkan <strong><?php echo mysqli_num_rows($result); ?></strong> dari
                            <strong><?php echo $total_items; ?></strong> obat
                            <?php if (!empty($search)): ?>
                            untuk "<strong><?php echo safeOutput($search); ?></strong>"
                            <?php endif; ?>
                            <?php if ($kategori !== 'semua'): ?>
                            dalam kategori
                            <strong><?php echo safeOutput(ucwords(str_replace('_', ' ', $kategori))); ?></strong>
                            <?php endif; ?>
                        </p>
                    </div>

                    <?php if (mysqli_num_rows($result) == 0): ?>
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h3>Obat tidak ditemukan</h3>
                        <p>Coba gunakan kata kunci lain atau pilih kategori yang berbeda.</p>
                        <a href="?" class="btn-clear-filters">
                            <i class="fas fa-times"></i>
                            Hapus Filter
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Products Grid -->
                <div class="products-container" id="productsContainer">
                    <div class="products-grid" id="productsView">
                        <?php
                        $row_count = 0;
                        while ($obat = mysqli_fetch_assoc($result)):
                            $row_count++;
                            $stock_class = $obat['stok'] == 0 ? 'out-of-stock' : ($obat['stok'] < 10 ? 'low-stock' : 'in-stock');
                            $stock_status = $obat['stok'] == 0 ? 'Stok Habis' : ($obat['stok'] < 10 ? 'Stok Terbatas' : 'Tersedia');

                            // Path gambar
                            $gambar_path = '../../uploads/obat/' . ($obat['gambar'] ?: 'default.png');
                            if (!file_exists($gambar_path) || !$obat['gambar']) {
                                $gambar_path = '../../assets/images/obat/default.png';
                            }

                            // Escape semua data
                            $obat_nama = safeOutput($obat['nama_obat']);
                            $obat_deskripsi = safeOutput(substr(strip_tags($obat['deskripsi']), 0, 80));
                            $obat_indikasi = safeOutput(substr(strip_tags($obat['indikasi']), 0, 60));
                            $obat_kategori = safeOutput(ucwords(str_replace('_', ' ', $obat['kategori'])));
                            $obat_jenis = safeOutput($obat['jenis_obat']);
                            $obat_harga = number_format($obat['harga'], 0, ',', '.');
                        ?>
                        <div class="product-card <?php echo $stock_class; ?>">
                            <div class="product-image">
                                <img src="<?php echo $gambar_path; ?>" alt="<?php echo $obat_nama; ?>"
                                    onerror="this.src='../../assets/images/obat/default.png'">
                                <?php if ($userRole === 'admin'): ?>
                                <div class="admin-product-actions">
                                    <a href="../../admin/obat/edit.php?id=<?php echo $obat['id_obat']; ?>"
                                        class="admin-edit-btn" title="Edit Obat">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="../../admin/obat/hapus.php?id=<?php echo $obat['id_obat']; ?>"
                                        class="admin-delete-btn"
                                        onclick="return confirm('Yakin ingin menghapus obat ini?')" title="Hapus Obat">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                                <?php endif; ?>
                                <div class="product-actions">
                                    <button class="action-btn quick-view" data-id="<?php echo $obat['id_obat']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn add-to-wishlist"
                                        data-id="<?php echo $obat['id_obat']; ?>">
                                        <i class="far fa-heart"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="product-info">
                                <span class="product-category"><?php echo $obat_kategori; ?></span>

                                <h3 class="product-title"><?php echo $obat_nama; ?></h3>

                                <p class="product-description">
                                    <?php echo $obat_deskripsi; ?>...
                                </p>

                                <div class="product-indication">
                                    <i class="fas fa-info-circle"></i>
                                    <span><?php echo $obat_indikasi; ?>...</span>
                                </div>

                                <div class="product-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-capsules"></i>
                                        <span><?php echo $obat_jenis; ?></span>
                                    </div>
                                    <div class="meta-item <?php echo $stock_class; ?>">
                                        <i class="fas fa-box"></i>
                                        <span><?php echo $stock_status; ?> (<?php echo $obat['stok']; ?>)</span>
                                    </div>
                                    <?php if ($userRole === 'admin'): ?>
                                    <div class="meta-item admin-info">
                                        <i class="fas fa-info-circle"></i>
                                        <span>ID: <?php echo $obat['id_obat']; ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div class="product-footer">
                                    <div class="price-container">
                                        <div class="current-price">
                                            Rp <?php echo $obat_harga; ?>
                                        </div>
                                    </div>
                                    <div class="action-buttons">
                                        <button class="btn-detail"
                                            onclick="showProductDetail(<?php echo $obat['id_obat']; ?>)">
                                            <i class="fas fa-info-circle"></i>
                                            Detail
                                        </button>
                                        <?php if ($userRole !== 'admin'): ?>
                                        <!-- Tombol Beli hanya untuk non-admin -->
                                        <button class="btn-buy <?php echo $obat['stok'] == 0 ? 'disabled' : ''; ?>"
                                            onclick="showPurchaseForm(<?php echo $obat['id_obat']; ?>, '<?php echo addslashes($obat_nama); ?>', <?php echo $obat['harga']; ?>, <?php echo $obat['stok']; ?>)"
                                            <?php echo $obat['stok'] == 0 ? 'disabled' : ''; ?>>
                                            <i class="fas fa-shopping-cart"></i>
                                            Beli Sekarang
                                        </button>
                                        <?php else: ?>
                                        <!-- Tombol Edit untuk admin -->
                                        <a href="../../admin/obat/edit.php?id=<?php echo $obat['id_obat']; ?>"
                                            class="btn-edit">
                                            <i class="fas fa-edit"></i>
                                            Edit
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>

                        <?php if ($row_count === 0): ?>
                        <div class="no-products-message">
                            <i class="fas fa-box-open" style="font-size: 3rem; color: #ccc; margin-bottom: 15px;"></i>
                            <h3>Tidak ada obat ditemukan</h3>
                            <p>Coba ubah filter pencarian Anda atau hubungi administrator.</p>
                            <?php if ($userRole === 'admin'): ?>
                            <a href="../../admin/obat/tambah.php" class="btn-clear-filters" style="margin-top: 15px;">
                                <i class="fas fa-plus"></i> Tambah Obat Baru
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                    <a href="<?php echo buildPageUrl($page - 1); ?>" class="page-link">
                        <i class="fas fa-chevron-left"></i> Sebelumnya
                    </a>
                    <?php endif; ?>

                    <div class="page-numbers">
                        <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $start_page + 4);

                            if ($start_page > 1) {
                                echo '<a href="' . buildPageUrl(1) . '" class="page-number">1</a>';
                                if ($start_page > 2) echo '<span class="page-dots">...</span>';
                            }

                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                        <a href="<?php echo buildPageUrl($i); ?>"
                            class="page-number <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>

                        <?php
                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) echo '<span class="page-dots">...</span>';
                                echo '<a href="' . buildPageUrl($total_pages) . '" class="page-number">' . $total_pages . '</a>';
                            }
                            ?>
                    </div>

                    <?php if ($page < $total_pages): ?>
                    <a href="<?php echo buildPageUrl($page + 1); ?>" class="page-link">
                        Selanjutnya <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Purchase Form Modal (Hanya untuk non-admin) -->
    <?php if ($userRole !== 'admin'): ?>
    <div id="purchaseModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-shopping-cart"></i> Form Pembelian</h3>
                <button class="modal-close" onclick="closePurchaseForm()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="purchaseForm" method="POST" action="../../api/create_transaksi.php">
                    <input type="hidden" name="status_pembayaran" value="lunas">

                    <div class="form-group">
                        <label for="product_name"><i class="fas fa-pills"></i> Nama Obat</label>
                        <input type="text" id="product_name" class="form-control" readonly>
                        <input type="hidden" id="product_id" name="product_id">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="nama_pembeli"><i class="fas fa-user"></i> Nama Pembeli *</label>
                            <?php if ($isLoggedIn): ?>
                            <input type="text" id="nama_pembeli" name="nama_pembeli" class="form-control"
                                value="<?php echo safeOutput($currentUser['nama_lengkap'] ?? $_SESSION['nama_lengkap'] ?? ''); ?>"
                                required>
                            <?php else: ?>
                            <input type="text" id="nama_pembeli" name="nama_pembeli" class="form-control" required
                                placeholder="Masukkan nama lengkap">
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="quantity"><i class="fas fa-box"></i> Jumlah *</label>
                            <input type="number" id="quantity" name="quantity" class="form-control" min="1" value="1"
                                required onchange="calculateTotal()">
                            <small class="stock-info">Stok tersedia: <span id="available_stock">0</span></small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="unit_price"><i class="fas fa-tag"></i> Harga Satuan</label>
                            <input type="text" id="unit_price" class="form-control" readonly>
                        </div>

                        <div class="form-group">
                            <label for="total_harga"><i class="fas fa-calculator"></i> Total Harga</label>
                            <input type="text" id="total_harga" name="total_harga" class="form-control" readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="metode_pembayaran"><i class="fas fa-credit-card"></i> Metode Pembayaran *</label>
                        <select id="metode_pembayaran" name="metode_pembayaran" class="form-control" required>
                            <option value="">Pilih metode pembayaran</option>
                            <option value="tunai">Tunai</option>
                            <option value="transfer_bank">Transfer Bank</option>
                            <option value="kartu_kredit">Kartu Kredit</option>
                            <option value="e-wallet">E-Wallet</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="alamat_pengiriman"><i class="fas fa-map-marker-alt"></i> Alamat Pengiriman</label>
                        <textarea id="alamat_pengiriman" name="alamat_pengiriman" class="form-control" rows="3"
                            placeholder="Masukkan alamat lengkap untuk pengiriman"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="catatan"><i class="fas fa-sticky-note"></i> Catatan</label>
                        <textarea id="catatan" name="catatan" class="form-control" rows="2"
                            placeholder="Catatan tambahan (opsional)"></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-cancel" onclick="closePurchaseForm()">Batal</button>
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-check"></i> Proses Pembelian
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="script.js"></script>
    <script>
    // Search with debounce
    let searchTimeout;
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                document.getElementById('searchForm').submit();
            }, 500);
        });
    }

    function updateSort(value) {
        const url = new URL(window.location);
        url.searchParams.set('sort', value);
        url.searchParams.set('page', '1');
        window.location.href = url.toString();
    }

    // Debug info di console
    console.log("User logged in: <?php echo $isLoggedIn ? 'true' : 'false'; ?>");
    console.log("User role: <?php echo $userRole; ?>");
    console.log("Total items: <?php echo $total_items; ?>");
    </script>
</body>

</html>