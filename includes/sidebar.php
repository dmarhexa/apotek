<?php
// apotek/includes/sidebar.php - IMPROVED VERSION

// Gunakan require_once dari ROOT
require_once dirname(__DIR__) . '/config.php';

// Start session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login status
$isLoggedIn = isset($_SESSION['pegawai_id']) && !empty($_SESSION['pegawai_id']);
$isUserLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']); // Tambahkan cek user login
$currentUser = null;
$userRole = 'guest';

if ($isLoggedIn) {
    $user_id = $_SESSION['pegawai_id'];
    // Ambil data lengkap termasuk role
    $sql = "SELECT id, nama_lengkap, username, status, role FROM pegawai WHERE id = '$user_id'";
    $result = mysqli_query($conn, $sql);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $currentUser = $row;
        $userRole = $row['role'] ?? 'staff';
    }
}

$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Role config untuk public sidebar
$roleConfig = [
    'admin' => ['icon' => 'fa-user-shield', 'color' => '#10b981', 'title' => 'Admin'],
    'developer' => ['icon' => 'fa-laptop-code', 'color' => '#3b82f6', 'title' => 'Developer'],
    'superadmin' => ['icon' => 'fa-crown', 'color' => '#f59e0b', 'title' => 'Super Admin'],
    'super admin' => ['icon' => 'fa-crown', 'color' => '#f59e0b', 'title' => 'Super Admin'],
    'manager' => ['icon' => 'fa-user-tie', 'color' => '#8b5cf6', 'title' => 'Manager'],
    'kasir' => ['icon' => 'fa-cash-register', 'color' => '#ec4899', 'title' => 'Kasir'],
    'staff' => ['icon' => 'fa-user', 'color' => '#6366f1', 'title' => 'Staff'],
    'default' => ['icon' => 'fa-user-tag', 'color' => '#6b7280', 'title' => 'Pegawai']
];

// Tentukan config role
$userRoleKey = strtolower(trim($userRole));
$currentRoleConfig = $roleConfig['default'];

foreach ($roleConfig as $key => $config) {
    if (strtolower($key) === $userRoleKey) {
        $currentRoleConfig = $config;
        break;
    }
}

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

<!-- SIDEBAR PUBLIC IMPROVED -->
<div class="public-sidebar">
    <!-- Header - DIKECILKAN -->
    <div class="sidebar-header">
        <div class="logo-compact">
            <div class="logo-icon">
                <i class="fas fa-heartbeat"></i>
            </div>
            <div class="logo-text">
                <h2>Apotek<span>Sehat</span></h2>
                <p class="logo-subtitle">Kesehatan Prioritas Anda</p>
            </div>
        </div>
    </div>

    <!-- User Profile Card (Hanya tampil jika login) -->
    <?php if ($isLoggedIn && $currentUser): ?>
    <div class="user-profile-card">
        <div class="profile-avatar"
            style="background: linear-gradient(135deg, <?php echo $currentRoleConfig['color']; ?> 0%, <?php echo $darkColor; ?> 100%);">
            <i class="fas <?php echo $currentRoleConfig['icon']; ?>"></i>
            <div class="online-indicator"></div>
        </div>
        <div class="profile-info">
            <h4 class="profile-name"><?php echo htmlspecialchars($currentUser['nama_lengkap']); ?></h4>
            <div class="profile-role">
                <span class="role-badge"
                    style="background: <?php echo $currentRoleConfig['color']; ?>20; color: <?php echo $currentRoleConfig['color']; ?>;">
                    <i class="fas <?php echo $currentRoleConfig['icon']; ?>"></i>
                    <?php echo htmlspecialchars(ucfirst($userRole)); ?>
                </span>
            </div>
            <div class="profile-meta">
                <span class="user-status active">
                    <i class="fas fa-circle"></i> Online
                </span>
                <span class="user-id">
                    ID: <?php echo htmlspecialchars($currentUser['id'] ?? '000'); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Quick Admin Access -->
    <div class="quick-admin-access">
        <a href="<?php echo $base_url; ?>/screen/admin/" class="admin-access-btn">
            <div class="access-icon">
                <i class="fas fa-tachometer-alt"></i>
            </div>
            <div class="access-text">
                <span>Admin Dashboard</span>
                <small>Control Panel</small>
            </div>
            <div class="access-arrow">
                <i class="fas fa-arrow-right"></i>
            </div>
        </a>
    </div>
    <?php endif; ?>

    <!-- FULL SCROLLABLE AREA -->
    <div class="sidebar-scrollable">
        <!-- Main Navigation -->
        <nav class="sidebar-nav">
            <div class="nav-section">
                <p class="nav-title">
                    <i class="fas fa-home"></i> BERANDA
                </p>
                <a href="<?php echo $base_url; ?>/screen/dashboard/"
                    class="nav-item <?php echo ($current_dir == 'dashboard') ? 'active' : ''; ?>">
                    <div class="nav-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <span class="nav-text">Dashboard</span>
                    <div class="nav-indicator">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </a>
            </div>

            <div class="nav-section">
                <p class="nav-title">
                    <i class="fas fa-pills"></i> PRODUK
                </p>
                <a href="<?php echo $base_url; ?>/screen/obat/"
                    class="nav-item <?php echo ($current_dir == 'obat') ? 'active' : ''; ?>">
                    <div class="nav-icon">
                        <i class="fas fa-pills"></i>
                    </div>
                    <span class="nav-text">Daftar Obat</span>
                    <div class="nav-indicator">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </a>

                <!-- <a href="#" class="nav-item">
                    <div class="nav-icon">
                        <i class="fas fa-prescription-bottle"></i>
                    </div>
                    <span class="nav-text">Resep Dokter</span>
                    <div class="nav-indicator">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </a> -->
            </div>

            <div class="nav-section">
                <p class="nav-title">
                    <i class="fas fa-stethoscope"></i> LAYANAN
                </p>
                <a href="<?php echo $base_url; ?>/screen/konsultasi/"
                    class="nav-item <?php echo ($current_dir == 'konsultasi') ? 'active' : ''; ?>">
                    <div class="nav-icon">
                        <i class="fas fa-stethoscope"></i>
                    </div>
                    <span class="nav-text">Konsultasi</span>
                    <div class="nav-indicator">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </a>

                <!-- <a href="<?php echo $base_url; ?>/screen/dokter/"
                    class="nav-item <?php echo ($current_dir == 'dokter') ? 'active' : ''; ?>">
                    <div class="nav-icon">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <span class="nav-text">Dokter</span>
                    <div class="nav-indicator">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </a> -->
            </div>

            <div class="nav-section">
                 <?php if ($isUserLoggedIn): ?>
                <p class="nav-title">
                    <i class="fas fa-shopping-cart"></i> TRANSAKSI
                </p>
               
                <a href="<?php echo $base_url; ?>/screen/riwayat/"
                    class="nav-item <?php echo ($current_dir == 'riwayat') ? 'active' : ''; ?>">
                    <div class="nav-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <span class="nav-text">Riwayat Belanja</span>
                    <div class="nav-indicator">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </a>
                <?php endif; ?>
            </div>

            <div class="nav-section">
                <p class="nav-title">
                    <i class="fas fa-calendar-alt"></i> LAINNYA
                </p>
                <?php if ($isUserLoggedIn): ?>
                <a href="<?php echo $base_url; ?>/screen/pengingat/"
                    class="nav-item <?php echo ($current_dir == 'pengingat') ? 'active' : ''; ?>">
                    <div class="nav-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <span class="nav-text">Pengingat Obat</span>
                    <div class="nav-indicator">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </a>
                <?php endif; ?>
                <!-- <a href="#" class="nav-item">
                    <div class="nav-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <span class="nav-text">Jadwal</span>
                    <div class="nav-indicator">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </a> -->

                <a href="<?php echo $base_url; ?>/screen/rating/"
                    class="nav-item <?php echo ($current_dir == 'rating') ? 'active' : ''; ?>">
                    <div class="nav-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <span class="nav-text">Rating</span>
                    <div class="nav-indicator">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </a>

                <!-- <a href="#" class="nav-item">
                    <div class="nav-icon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <span class="nav-text">Bantuan</span>
                    <div class="nav-indicator">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </a> -->
            </div>
        </nav>

        <!-- Login Button (Hanya untuk guest atau user) -->
        <?php 
        // $isUserLoggedIn sudah didefinisikan di atas
        if (!$isLoggedIn): 
        ?>
        <div class="guest-section">
            <div class="guest-info">
                <div class="guest-icon">
                    <i class="fas fa-user"></i>
                </div>
                <div class="guest-text">
                    <?php if ($isUserLoggedIn): ?>
                        <h4><?php echo htmlspecialchars($_SESSION['user_nama']); ?></h4>
                        <p>User Terdaftar</p>
                    <?php else: ?>
                        <h4>Pengunjung</h4>
                        <p>Selamat datang di Apotek Sehat</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($isUserLoggedIn): ?>
                <a href="<?php echo $base_url; ?>/auth/logout.php" class="logout-btn">
                    <div class="btn-icon">
                        <i class="fas fa-sign-out-alt"></i>
                    </div>
                    <div class="btn-text">
                        <span>Log Out</span>
                    </div>
                    <div class="btn-arrow">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </a>
            <?php else: ?>
                <a href="<?php echo $base_url; ?>/auth/login.php" class="login-btn">
                    <div class="btn-icon">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <div class="btn-text">
                        <span>Login Pegawai</span>
                        <small>Akses khusus staff</small>
                    </div>
                    <div class="btn-arrow">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </a>
                
                <!-- Added User Login Link -->
                <a href="<?php echo $base_url; ?>/auth/user_login.php" class="login-btn" style="margin-top: 10px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border-color: rgba(59, 130, 246, 0.2);">
                    <div class="btn-icon" style="background: rgba(255, 255, 255, 0.2);">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="btn-text">
                        <span>Login Pelanggan</span>
                        <small>Akun User</small>
                    </div>
                    <div class="btn-arrow">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </a>
            <?php endif; ?>

        </div>
        <?php endif; ?>

        <!-- Footer - DALAM SCROLLABLE AREA -->
        <div class="sidebar-footer">
            <div class="footer-info">
                <div class="hours-info">
                    <i class="far fa-clock"></i>
                    <div class="hours-details">
                        <span>Jam Operasional</span>
                        <div class="hours">08:00 - 22:00</div>
                    </div>
                </div>
                <div class="contact-info">
                    <i class="fas fa-phone-alt"></i>
                    <div class="contact-details">
                        <span>Hotline</span>
                        <div class="phone">(021) 1234-5678</div>
                    </div>
                </div>
            </div>

            <div class="copyright">
                <p>&copy; 2024 Apotek Sehat. All rights reserved.</p>
                <p class="version">v1.0.0</p>
            </div>
        </div>
    </div>
</div>

<style>
/* ===== SIDEBAR CONTAINER ===== */
.public-sidebar {
    width: 280px;
    height: 100vh;
    background: white;
    position: fixed;
    left: 0;
    top: 0;
    display: flex;
    flex-direction: column;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border-right: 1px solid #e5e7eb;
    font-family: 'Inter', sans-serif;
    z-index: 1000;
}

/* ===== HEADER - DIKECILKAN ===== */
.sidebar-header {
    padding: 15px 20px;
    border-bottom: 1px solid #e5e7eb;
    flex-shrink: 0;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.logo-compact {
    display: flex;
    align-items: center;
    gap: 12px;
}

.logo-icon {
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.logo-icon i {
    font-size: 1.5rem;
    color: white;
}

.logo-text h2 {
    margin: 0;
    font-size: 1.3rem;
    font-weight: 700;
    line-height: 1.2;
    color: white;
}

.logo-text h2 span {
    color: #ffd700;
}

.logo-subtitle {
    margin: 2px 0 0 0;
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 400;
}

/* ===== USER PROFILE CARD ===== */
.user-profile-card {
    padding: 15px 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    gap: 12px;
    background: #f9fafb;
}

.profile-avatar {
    width: 45px;
    height: 45px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    flex-shrink: 0;
}

.profile-avatar i {
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
    border: 2px solid white;
}

.profile-info {
    flex: 1;
    min-width: 0;
}

.profile-name {
    margin: 0 0 5px 0;
    font-size: 0.9rem;
    font-weight: 600;
    color: #1f2937;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.profile-role {
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

.profile-meta {
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

.user-id {
    color: #6b7280;
    font-size: 0.65rem;
    background: rgba(0, 0, 0, 0.05);
    padding: 2px 6px;
    border-radius: 8px;
}

/* ===== QUICK ADMIN ACCESS ===== */
.quick-admin-access {
    padding: 10px 20px;
    border-bottom: 1px solid #e5e7eb;
    background: #f9fafb;
}

.admin-access-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 15px;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.3s;
    border: 1px solid rgba(59, 130, 246, 0.2);
}

.admin-access-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.access-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.2);
}

.access-icon i {
    font-size: 0.9rem;
}

.access-text {
    flex: 1;
    min-width: 0;
}

.access-text span {
    display: block;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 2px;
}

.access-text small {
    display: block;
    font-size: 0.7rem;
    color: rgba(255, 255, 255, 0.8);
}

.access-arrow {
    opacity: 0.8;
}

.access-arrow i {
    font-size: 0.8rem;
}

/* ===== FULL SCROLLABLE AREA ===== */
.sidebar-scrollable {
    flex: 1;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    min-height: 0;
}

/* ===== NAVIGATION ===== */
.sidebar-nav {
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
    color: #6b7280;
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
    color: #4b5563;
    text-decoration: none;
    transition: all 0.3s;
    border-radius: 8px;
    margin: 3px 0;
    border: 1px solid transparent;
    background: #f9fafb;
}

.nav-item:hover {
    background: #e5e7eb;
    color: #1f2937;
    transform: translateX(3px);
    border-color: #d1d5db;
}

.nav-item.active {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border-color: rgba(16, 185, 129, 0.2);
}

.nav-item.active .nav-icon {
    background: rgba(255, 255, 255, 0.2);
    color: white;
}

.nav-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e5e7eb;
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

/* ===== GUEST SECTION ===== */
.guest-section {
    padding: 20px;
    border-top: 1px solid #e5e7eb;
    background: #f9fafb;
}

.guest-info {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 15px;
    padding: 12px;
    background: white;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.guest-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
}

.guest-icon i {
    font-size: 1.2rem;
    color: white;
}

.guest-text h4 {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 600;
    color: #1f2937;
}

.guest-text p {
    margin: 3px 0 0 0;
    font-size: 0.8rem;
    color: #6b7280;
}

.login-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 15px;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.3s;
    border: 1px solid rgba(59, 130, 246, 0.2);
}

.login-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.logout-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 15px;
    margin-top: 10px;
    background: linear-gradient(135deg, #ff3333ff 0%, #ff4557ff 100%);
    color: white;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.3s;
    border: 1px solid rgba(59, 130, 246, 0.2);
}

.logout-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.2);

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
    font-weight: 600;
    margin-bottom: 2px;
}

.btn-text small {
    display: block;
    font-size: 0.7rem;
    color: rgba(255, 255, 255, 0.8);
}

.btn-arrow {
    opacity: 0.8;
}

.btn-arrow i {
    font-size: 0.8rem;
}

/* ===== FOOTER - DALAM SCROLLABLE AREA ===== */
.sidebar-footer {
    padding: 15px 20px;
    border-top: 1px solid #e5e7eb;
    background: #f9fafb;
    margin-top: auto;
}

.footer-info {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    margin-bottom: 15px;
}

.hours-info,
.contact-info {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
}

.hours-info i,
.contact-info i {
    font-size: 0.9rem;
    color: #10b981;
}

.hours-details span,
.contact-details span {
    display: block;
    font-size: 0.7rem;
    color: #6b7280;
    margin-bottom: 2px;
}

.hours,
.phone {
    font-size: 0.8rem;
    font-weight: 600;
    color: #1f2937;
}

.copyright {
    text-align: center;
    padding-top: 15px;
    border-top: 1px solid #e5e7eb;
}

.copyright p {
    margin: 3px 0;
    font-size: 0.75rem;
    color: #6b7280;
}

.copyright .version {
    font-size: 0.7rem;
    color: #9ca3af;
    font-weight: 500;
}

/* ===== SCROLLBAR ===== */
.sidebar-scrollable::-webkit-scrollbar {
    width: 5px;
}

.sidebar-scrollable::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.sidebar-scrollable::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.sidebar-scrollable::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) {
    .public-sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }

    .public-sidebar.active {
        transform: translateX(0);
    }
}
</style>