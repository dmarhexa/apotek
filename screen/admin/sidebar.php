<?php
// screen/admin/sidebar.php - FIXED PATH

// Gunakan require_once dengan path yang benar ke ROOT
require_once dirname(dirname(dirname(__FILE__))) . '/config.php';

// Include auth dari includes
$auth_path = dirname(dirname(dirname(__FILE__))) . '/includes/auth.php';
if (file_exists($auth_path)) {
    require_once $auth_path;
} else {
    // Fallback function
    function isLoggedIn()
    {
        return isset($_SESSION['pegawai_id']) && !empty($_SESSION['pegawai_id']);
    }

    function getCurrentUser($conn = null)
    {
        if (!isset($_SESSION['pegawai_id'])) return null;

        $user_id = $_SESSION['pegawai_id'];
        if ($conn) {
            // Ambil semua data termasuk role
            $sql = "SELECT id, username, nama_lengkap, status, role FROM pegawai WHERE id = '$user_id'";
            $result = mysqli_query($conn, $sql);
            if ($row = mysqli_fetch_assoc($result)) {
                // Simpan role ke session untuk digunakan nanti
                $_SESSION['user_role'] = $row['role'];
                return $row;
            }
        }
        return [
            'id' => $_SESSION['pegawai_id'] ?? null,
            'username' => $_SESSION['username'] ?? '',
            'nama_lengkap' => $_SESSION['nama_lengkap'] ?? 'Admin',
            'status' => $_SESSION['status'] ?? 'active',
            'role' => $_SESSION['user_role'] ?? 'admin' // PERBAIKAN: gunakan user_role
        ];
    }
}

// Cek login
if (!isLoggedIn()) {
    header("Location: " . $base_url . "/auth/login.php");
    exit();
}

$currentUser = getCurrentUser($conn);
$username = $currentUser ? $currentUser['nama_lengkap'] : 'Admin';

// DEBUG: Untuk testing - hapus setelah fix
// echo "<!-- DEBUG: ";
// print_r($currentUser);
// echo " -->";

// PERBAIKAN: Ambil role dengan prioritas yang benar
$userRole = 'admin'; // Default value

// 1. Cek dari currentUser array (langsung dari database)
if ($currentUser && isset($currentUser['role']) && !empty($currentUser['role'])) {
    $userRole = $currentUser['role'];
    $_SESSION['user_role'] = $userRole; // Update session
}
// 2. Cek dari session (jika ada)
elseif (isset($_SESSION['user_role']) && !empty($_SESSION['user_role'])) {
    $userRole = $_SESSION['user_role'];
}
// 3. Cek dari session lama (kompatibilitas)
elseif (isset($_SESSION['role']) && !empty($_SESSION['role'])) {
    $userRole = $_SESSION['role'];
    $_SESSION['user_role'] = $userRole; // Migrasi ke key baru
}

// Konversi role ke lowercase untuk konsistensi, tapi tampilkan dengan format asli
$userRoleDisplay = ucfirst(trim($userRole));
$userRoleKey = strtolower(trim($userRole));

// Determine role icon and color
$roleConfig = [
    'admin' => ['icon' => 'fa-user-shield', 'color' => '#10b981', 'title' => 'Admin'],
    'developer' => ['icon' => 'fa-laptop-code', 'color' => '#3b82f6', 'title' => 'Developer'],
    'superadmin' => ['icon' => 'fa-crown', 'color' => '#f59e0b', 'title' => 'Super Admin'],
    'super admin' => ['icon' => 'fa-crown', 'color' => '#f59e0b', 'title' => 'Super Admin'],
    'manager' => ['icon' => 'fa-user-tie', 'color' => '#8b5cf6', 'title' => 'Manager'],
    'kasir' => ['icon' => 'fa-cash-register', 'color' => '#ec4899', 'title' => 'Kasir'],
    'staff' => ['icon' => 'fa-user', 'color' => '#6366f1', 'title' => 'Staff'],
    'custom' => ['icon' => 'fa-user-tag', 'color' => '#6b7280', 'title' => 'Custom'],
    'default' => ['icon' => 'fa-user', 'color' => '#9ca3af', 'title' => 'User']
];

// Cari config yang cocok
$currentRoleConfig = $roleConfig['default'];
$foundConfig = false;

// Coba cari exact match dulu
if (isset($roleConfig[$userRoleKey])) {
    $currentRoleConfig = $roleConfig[$userRoleKey];
    $foundConfig = true;
} else {
    // Cari partial match atau custom role
    foreach ($roleConfig as $key => $config) {
        if (
            strpos(strtolower($userRoleKey), strtolower($key)) !== false ||
            strpos(strtolower($key), strtolower($userRoleKey)) !== false
        ) {
            $currentRoleConfig = $config;
            $foundConfig = true;
            break;
        }
    }

    // Jika tidak ditemukan, gunakan custom dengan icon default
    if (!$foundConfig) {
        $currentRoleConfig = [
            'icon' => 'fa-user-tag',
            'color' => $this->generateColorFromString($userRole),
            'title' => $userRoleDisplay
        ];
    }
}

// Jika custom role, generate warna unik
if (!$foundConfig) {
    function generateColorFromString($string)
    {
        $hash = md5($string);
        return '#' . substr($hash, 0, 6);
    }
    $currentRoleConfig['color'] = generateColorFromString($userRole);
}

$current_page = basename($_SERVER['PHP_SELF']);

// Helper function untuk darken color
function darkenColor($color, $amount = 30)
{
    if ($color[0] == '#') {
        $color = substr($color, 1);
        $r = hexdec(substr($color, 0, 2));
        $g = hexdec(substr($color, 2, 2));
        $b = hexdec(substr($color, 4, 2));

        $r = max(0, $r - $amount);
        $g = max(0, $g - $amount);
        $b = max(0, $b - $amount);

        return sprintf("#%02x%02x%02x", $r, $g, $b);
    }
    return $color;
}

$darkColor = darkenColor($currentRoleConfig['color'], 30);
?>

<!-- ADMIN SIDEBAR -->
<div class="admin-sidebar">
    <!-- Header -->
    <div class="admin-header">
        <div class="admin-logo">
            <i class="fas fa-shield-alt"></i>
            <h2>Admin<span>Panel</span></h2>
        </div>

        <!-- User Profile -->
        <div class="user-profile">
            <div class="user-avatar"
                style="background: linear-gradient(135deg, <?php echo $currentRoleConfig['color']; ?> 0%, <?php echo $darkColor; ?> 100%);">
                <i class="fas <?php echo $currentRoleConfig['icon']; ?>"></i>
                <div class="online-indicator"></div>
            </div>
            <div class="user-info">
                <h4 class="user-name"><?php echo htmlspecialchars($username); ?></h4>
                <div class="user-role-info">
                    <span class="role-badge"
                        style="background: <?php echo $currentRoleConfig['color']; ?>20; color: <?php echo $currentRoleConfig['color']; ?>;">
                        <i class="fas <?php echo $currentRoleConfig['icon']; ?>"></i>
                        <?php echo $userRoleDisplay; ?>
                    </span>
                </div>
                <div class="user-meta">
                    <span class="user-status active">
                        <i class="fas fa-circle"></i> Online
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation dengan scroll -->
    <div class="sidebar-scrollable">
        <!-- Menu Navigation -->
        <nav class="admin-nav">
            <div class="nav-section">
                <p class="nav-title">
                    <i class="fas fa-bars"></i> MAIN MENU
                </p>
                <a href="index.php" class="nav-item <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                    <div class="nav-icon">
                        <i class="fas fa-tachometer-alt"></i>
                    </div>
                    <span class="nav-text">Dashboard</span>
                    <div class="nav-indicator">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </a>
                <a href="obat.php" class="nav-item <?php echo $current_page == 'obat.php' ? 'active' : ''; ?>">
                    <div class="nav-icon">
                        <i class="fas fa-pills"></i>
                    </div>
                    <span class="nav-text">Kelola Obat</span>
                    <div class="nav-indicator">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </a>
                <a href="dokter.php" class="nav-item <?php echo $current_page == 'dokter.php' ? 'active' : ''; ?>">
                    <div class="nav-icon">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <span class="nav-text">Kelola Dokter</span>
                    <div class="nav-indicator">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </a>
                <a href="rating.php" class="nav-item <?php echo $current_page == 'rating.php' ? 'active' : ''; ?>">
                    <div class="nav-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <span class="nav-text">Kelola Rating</span>
                    <div class="nav-indicator">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </a>
                <a href="users.php" class="nav-item <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
                    <div class="nav-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <span class="nav-text">Kelola User</span>
                    <div class="nav-indicator">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </a>
            </div>

            <div class="nav-section">
                <p class="nav-title">
                    <i class="fas fa-exchange-alt"></i> TRANSACTIONS
                </p>
                <a href="transaksi.php"
                    class="nav-item <?php echo $current_page == 'transaksi.php' ? 'active' : ''; ?>">
                    <div class="nav-icon">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <span class="nav-text">Transaksi</span>
                    <div class="nav-indicator">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </a>
                <a href="laporan_bulanan.php"
                    class="nav-item <?php echo $current_page == 'laporan_bulanan.php' ? 'active' : ''; ?>">
                    <div class="nav-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <span class="nav-text">Laporan Bulanan</span>
                    <div class="nav-indicator">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </a>
            </div>

            <div class="nav-section">
                <p class="nav-title">
                    <i class="fas fa-cog"></i> SETTINGS
                </p>
                <a href="pegawai.php" class="nav-item <?php echo $current_page == 'pegawai.php' ? 'active' : ''; ?>">
                    <div class="nav-icon">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <span class="nav-text">Kelola Pegawai</span>
                    <div class="nav-indicator">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </a>
            </div>
        </nav>

        <!-- Footer Buttons - SEKARANG DALAM SCROLLABLE AREA -->
        <div class="admin-footer">
            <div class="footer-info">
                <div class="system-status">
                    <i class="fas fa-server"></i>
                    <div class="status-details">
                        <span>System Status</span>
                        <div class="status-bar">
                            <div class="status-progress" style="width: 95%; background: #10b981;"></div>
                        </div>
                    </div>
                </div>
                <div class="version-info">
                    <span class="version">v1.0.0</span>
                    <span class="update-status updated">
                        <i class="fas fa-check-circle"></i>
                    </span>
                </div>
            </div>

            <div class="footer-buttons">

                <a href="<?php echo $base_url; ?>/auth/logout.php" class="footer-btn btn-logout">
                    <div class="btn-icon">
                        <i class="fas fa-sign-out-alt"></i>
                    </div>
                    <div class="btn-text">
                        <span>Logout</span>
                        <small>End Session</small>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.admin-sidebar {
    width: 250px;
    height: 100vh;
    background: linear-gradient(180deg, #1a2231 0%, #0f172a 100%);
    color: white;
    position: fixed;
    left: 0;
    top: 0;
    display: flex;
    flex-direction: column;
    z-index: 1000;
    box-shadow: 4px 0 15px rgba(0, 0, 0, 0.15);
    border-right: 1px solid rgba(255, 255, 255, 0.07);
}

/* Header */
.admin-header {
    padding: 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    flex-shrink: 0;
    background: rgba(255, 255, 255, 0.02);
}

.admin-logo {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}

.admin-logo i {
    font-size: 1.8rem;
    color: #10b981;
    background: rgba(16, 185, 129, 0.1);
    padding: 10px;
    border-radius: 10px;
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.admin-logo h2 {
    margin: 0;
    font-size: 1.4rem;
    font-weight: 700;
    line-height: 1.2;
    color: white;
}

.admin-logo h2 span {
    color: #10b981;
}

/* User Profile */
.user-profile {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: rgba(255, 255, 255, 0.03);
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.05);
}

.user-avatar {
    width: 45px;
    height: 45px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    flex-shrink: 0;
}

.user-avatar i {
    font-size: 1.2rem;
    color: white;
}

.online-indicator {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 10px;
    height: 10px;
    background: #10b981;
    border-radius: 50%;
    border: 2px solid #1a2231;
}

.user-info {
    flex: 1;
    min-width: 0;
}

.user-name {
    margin: 0 0 5px 0;
    font-size: 0.9rem;
    font-weight: 600;
    color: white;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-role-info {
    margin-bottom: 5px;
}

.role-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 8px;
    border-radius: 15px;
    font-size: 0.75rem;
    font-weight: 500;
    border: 1px solid;
}

.role-badge i {
    font-size: 0.7rem;
}

.user-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.7rem;
}

.user-status {
    display: flex;
    align-items: center;
    gap: 4px;
    color: #10b981;
    font-weight: 500;
}

.user-status i {
    font-size: 0.5rem;
}

/* FULL SCROLLABLE AREA */
.sidebar-scrollable {
    flex: 1;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    min-height: 0;
}

/* Navigation */
.admin-nav {
    padding: 15px 0;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.nav-section {
    margin-bottom: 20px;
    padding: 0 20px;
}

.nav-title {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    color: #9ca3af;
    margin: 0 0 10px 5px;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.nav-title i {
    font-size: 0.7rem;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    color: #d1d5db;
    text-decoration: none;
    transition: all 0.3s;
    border-radius: 8px;
    margin: 3px 0;
    border: 1px solid transparent;
    background: rgba(255, 255, 255, 0.02);
}

.nav-item:hover {
    background: rgba(255, 255, 255, 0.05);
    color: white;
    border-color: rgba(255, 255, 255, 0.1);
    transform: translateX(3px);
}

.nav-item.active {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
    border-color: rgba(16, 185, 129, 0.2);
}

.nav-item.active .nav-icon {
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
}

.nav-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.05);
    transition: all 0.3s;
    flex-shrink: 0;
}

.nav-icon i {
    font-size: 0.9rem;
}

.nav-text {
    flex: 1;
    font-size: 0.85rem;
    font-weight: 500;
    white-space: nowrap;
}

.nav-indicator {
    opacity: 0;
    transform: translateX(-5px);
    transition: all 0.3s;
    color: #9ca3af;
}

.nav-item:hover .nav-indicator,
.nav-item.active .nav-indicator {
    opacity: 1;
    transform: translateX(0);
}

.nav-indicator i {
    font-size: 0.75rem;
}

/* Footer - DALAM SCROLLABLE AREA */
.admin-footer {
    padding: 15px 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
    background: rgba(255, 255, 255, 0.02);
    margin-top: auto;
    /* Tetap di bawah */
}

.footer-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding: 10px;
    background: rgba(255, 255, 255, 0.03);
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.05);
}

.system-status {
    display: flex;
    align-items: center;
    gap: 8px;
}

.system-status i {
    font-size: 1rem;
    color: #10b981;
}

.status-details {
    flex: 1;
}

.status-details span {
    display: block;
    font-size: 0.75rem;
    color: #9ca3af;
    margin-bottom: 3px;
}

.status-bar {
    width: 60px;
    height: 4px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 2px;
    overflow: hidden;
}

.status-progress {
    height: 100%;
    border-radius: 2px;
}

.version-info {
    text-align: right;
}

.version {
    display: block;
    font-size: 0.75rem;
    color: white;
    font-weight: 600;
    margin-bottom: 2px;
}

.update-status {
    font-size: 0.7rem;
    display: flex;
    align-items: center;
    gap: 3px;
}

.update-status.updated {
    color: #10b981;
}

.update-status i {
    font-size: 0.6rem;
}

.footer-buttons {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.footer-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.3s;
    border: 1px solid transparent;
}

.btn-back {
    background: rgba(59, 130, 246, 0.1);
    color: #93c5fd;
    border-color: rgba(59, 130, 246, 0.2);
}

.btn-back:hover {
    background: rgba(59, 130, 246, 0.15);
    color: #60a5fa;
    border-color: rgba(59, 130, 246, 0.3);
    transform: translateX(3px);
}

.btn-logout {
    background: rgba(239, 68, 68, 0.1);
    color: #f87171;
    border-color: rgba(239, 68, 68, 0.2);
}

.btn-logout:hover {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
    border-color: rgba(239, 68, 68, 0.3);
    transform: translateX(3px);
}

.btn-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.1);
    flex-shrink: 0;
}

.btn-back .btn-icon {
    background: rgba(59, 130, 246, 0.2);
}

.btn-logout .btn-icon {
    background: rgba(239, 68, 68, 0.2);
}

.btn-icon i {
    font-size: 0.9rem;
}

.btn-text {
    flex: 1;
    min-width: 0;
}

.btn-text span {
    display: block;
    font-size: 0.85rem;
    font-weight: 500;
    margin-bottom: 2px;
}

.btn-text small {
    display: block;
    font-size: 0.7rem;
    color: rgba(255, 255, 255, 0.6);
}

/* Scrollbar Styling */
.sidebar-scrollable::-webkit-scrollbar {
    width: 5px;
}

.sidebar-scrollable::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.02);
    border-radius: 3px;
}

.sidebar-scrollable::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 3px;
    border: 1px solid rgba(255, 255, 255, 0.05);
}

.sidebar-scrollable::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* Untuk Firefox */
.sidebar-scrollable {
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.1) rgba(255, 255, 255, 0.02);
}

/* Responsive */
@media (max-width: 768px) {
    .admin-sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }

    .admin-sidebar.active {
        transform: translateX(0);
    }
}
</style>