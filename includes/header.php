<!-- includes/header.php
<header class="global-header">
    <nav class="nav-container">
        <div class="nav-brand">
            <a href="index.php" class="logo-link">
                <i class="fas fa-clinic-medical"></i>
                <span>Apotek Sehat</span>
            </a>
        </div>
        
        <div class="nav-menu">
            <ul class="nav-links">
                <li><a href="index.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> Home
                </a></li>
                <li><a href="dashboard/" class="<?php echo (strpos($_SERVER['PHP_SELF'], 'dashboard') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i> Dashboard
                </a></li>
                <li><a href="obat/" class="<?php echo (strpos($_SERVER['PHP_SELF'], 'obat') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-pills"></i> Obat
                </a></li>
                <li><a href="dokter/" class="<?php echo (strpos($_SERVER['PHP_SELF'], 'dokter') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-user-md"></i> Dokter
                </a></li>
                <li><a href="transaksi/" class="<?php echo (strpos($_SERVER['PHP_SELF'], 'transaksi') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i> Transaksi
                </a></li>
            </ul>
        </div>
        
        <div class="nav-actions">
            <a href="login.php" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Login
            </a>
        </div>
        
        <button class="mobile-menu-toggle">
            <i class="fas fa-bars"></i>
        </button>
    </nav>
</header> -->