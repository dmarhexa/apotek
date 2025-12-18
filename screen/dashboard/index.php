<?php
// apotek/screen/dashboard/index.php
require_once '../../config.php';

// Query untuk data dashboard
$total_obat = mysqli_query($conn, "SELECT COUNT(*) as total FROM obat");
$total_obat = mysqli_fetch_assoc($total_obat)['total'];

// Dokter terbaik (ambil 4 dokter)
$dokter_terbaik = mysqli_query($conn, "
    SELECT d.*, 
           COALESCE((SELECT AVG(bintang) FROM rating WHERE id_dokter = d.id_dokter), 4.5) as rating_avg
    FROM dokter d 
    ORDER BY rating_avg DESC, d.nama_dokter ASC
    LIMIT 4
");

// Obat terlaris (ambil 6 obat)
$obat_terlaris = mysqli_query($conn, "
    SELECT o.*, COALESCE(SUM(dt.jumlah), 0) as total_terjual
    FROM obat o
    LEFT JOIN transaksi_detail dt ON o.id_obat = dt.id_obat
    LEFT JOIN transaksi t ON dt.id_transaksi = t.id_transaksi AND t.status_pembayaran = 'lunas'
    GROUP BY o.id_obat
    ORDER BY total_terjual DESC 
    LIMIT 6
");

// Obat baru
$obat_baru = mysqli_query($conn, "SELECT * FROM obat ORDER BY created_at DESC LIMIT 6");

// Promo obat
$promo_obat = mysqli_query($conn, "SELECT * FROM obat WHERE stok > 50 ORDER BY RAND() LIMIT 4");

// Kategori obat
$kategori_obat = mysqli_query($conn, "
    SELECT kategori, COUNT(*) as jumlah, 
           GROUP_CONCAT(DISTINCT jenis_obat) as jenis_list
    FROM obat 
    GROUP BY kategori
");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda - Apotek Sehat</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css">
</head>

<body>
    <div class="container">
        <?php include '../../includes/sidebar.php'; ?>

        <main class="main-content">
            <!-- Header dengan Welcome Banner -->
            <header class="dashboard-header">
                <div class="welcome-banner">
                    <div class="welcome-text">
                        <h1>Selamat Datang di Apotek<span>Sehat</span></h1>
                        <p>Solusi kesehatan keluarga Anda. Temukan obat dan konsultasi dengan dokter terbaik.</p>
                        <a href="<?php echo isset($_SESSION['user_id']) ? '../obat/' : '../../auth/user_login.php'; ?>" class="btn-explore">
                            <i class="fas fa-shopping-bag"></i>
                            Belanja Sekarang
                        </a>
                    </div>
                    <div class="welcome-image">
                        <img src="<?php echo $base_url; ?>/assets/logo/logo_apotek.png" alt="Apotek Sehat" style="width: 150px; height: auto; border-radius: 15px; object-fit: cover;">
                    </div>
                </div>
            </header>

            <div class="content">
                <?php echo get_message(); ?>

                <!-- Fitur Utama -->
                <section class="features-section">
                    <h2 class="section-title">
                        <i class="fas fa-star"></i>
                        Layanan Unggulan
                    </h2>
                    <div class="features-grid">
                        <!-- Konsultasi Dokter -->
                        <a href="../konsultasi/" class="feature-card feature-card-link">
                            <div class="feature-icon green">
                                <i class="fas fa-user-md"></i>
                            </div>
                            <h3>Konsultasi Dokter</h3>
                            <p>Online 24/7</p>
                        </a>
                        
                        <!-- Obat Lengkap -->
                        <a href="../obat/" class="feature-card feature-card-link">
                            <div class="feature-icon orange">
                                <i class="fas fa-pills"></i>
                            </div>
                            <h3>Obat Lengkap</h3>
                            <p>1000+ jenis obat</p>
                        </a>
                        
                        <!-- Pengingat Obat -->
                        <a href="../pengingat/" class="feature-card feature-card-link">
                            <div class="feature-icon blue">
                                <i class="fas fa-bell"></i>
                            </div>
                            <h3>Pengingat Obat</h3>
                            <p>Jadwal minum obat</p>
                        </a>
                        
                        <!-- 24 Jam -->
                        <div class="feature-card">
                            <div class="feature-icon purple">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h3>24 Jam</h3>
                            <p>Layanan non-stop</p>
                        </div>
                    </div>
                </section>

                <!-- Obat Terlaris -->
                <section class="best-selling-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-fire"></i>
                            Obat Terlaris
                        </h2>
                        <a href="../obat/" class="view-all">Lihat Semua <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="products-grid">
                        <?php while ($obat = mysqli_fetch_assoc($obat_terlaris)): 
                            // Tentukan path gambar
                            $gambar_obat = !empty($obat['gambar']) && file_exists("../../assets/images/obat/{$obat['gambar']}") 
                                ? "../../assets/images/obat/{$obat['gambar']}" 
                                : "../../assets/images/obat/default.png";
                        ?>
                        <div class="product-card">
                            <div class="product-badge">Terlaris</div>
                            <div class="product-image">
                                <img src="<?php echo $gambar_obat; ?>" alt="<?php echo htmlspecialchars($obat['nama_obat']); ?>">
                            </div>
                            <div class="product-info">
                                <span class="product-category"><?php echo htmlspecialchars($obat['kategori']); ?></span>
                                <h3 class="product-title"><?php echo htmlspecialchars($obat['nama_obat']); ?></h3>
                                <p class="product-desc"><?php echo substr(htmlspecialchars($obat['deskripsi']), 0, 60) . '...'; ?></p>
                                <div class="product-footer">
                                    <div class="product-price">
                                        <span class="price">Rp <?php echo number_format($obat['harga'], 0, ',', '.'); ?></span>
                                        <span class="stock">Stok: <?php echo $obat['stok']; ?></span>
                                    </div>
                                    <a href="../obat/?purchase_id=<?php echo $obat['id_obat']; ?>" class="btn-buy-now">
                                        Beli Sekarang
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </section>

                <!-- Dokter Terbaik -->
                <section class="doctors-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-stethoscope"></i>
                            Dokter Terbaik
                        </h2>
                        <a href="../dokter/" class="view-all">Lihat Semua <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="doctors-grid">
                        <?php while ($dokter = mysqli_fetch_assoc($dokter_terbaik)): 
                            // Tentukan path gambar dokter
                            $gambar_dokter = !empty($dokter['foto']) && file_exists("../../assets/images/dokter/{$dokter['foto']}") 
                                ? "../../assets/images/dokter/{$dokter['foto']}" 
                                : "../../assets/images/dokter/default.png";
                        ?>
                        <div class="doctor-card">
                            <div class="doctor-image">
                                <img src="<?php echo $gambar_dokter; ?>" alt="<?php echo htmlspecialchars($dokter['nama_dokter']); ?>">
                                <div class="doctor-rating">
                                    <i class="fas fa-star"></i>
                                    <span><?php echo number_format($dokter['rating_avg'] ?? 4.5, 1); ?></span>
                                </div>
                            </div>
                            <div class="doctor-info">
                                <h3><?php echo htmlspecialchars($dokter['nama_dokter']); ?></h3>
                                <p class="doctor-specialty"><?php echo htmlspecialchars($dokter['spesialisasi']); ?></p>
                                <p class="doctor-experience">
                                    <i class="fas fa-briefcase"></i>
                                    <?php echo htmlspecialchars($dokter['pengalaman']); ?> pengalaman
                                </p>
                                <div class="doctor-schedule">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span><?php echo substr(htmlspecialchars($dokter['jadwal_praktek']), 0, 20); ?>...</span>
                                </div>
                                <div class="doctor-footer">
                                    <span class="consultation-fee">
                                        Rp <?php echo number_format($dokter['biaya_konsultasi'], 0, ',', '.'); ?>
                                    </span>
                                    <a href="../konsultasi/?wa_id=<?php echo $dokter['id_dokter']; ?>" class="btn-consult">
                                        Konsultasi
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </section>

                <!-- Kategori Obat -->
                <section class="categories-section">
                    <h2 class="section-title">
                        <i class="fas fa-tags"></i>
                        Kategori Obat
                    </h2>
                    <div class="categories-grid">
                        <?php while ($kategori = mysqli_fetch_assoc($kategori_obat)): ?>
                        <a href="../obat/?kategori=<?php echo urlencode($kategori['kategori']); ?>" class="category-card">
                            <div class="category-icon">
                                <?php
                                    $icons = [
                                        'antibiotik' => 'fa-bacteria',
                                        'analgesik' => 'fa-head-side-virus',
                                        'vitamin' => 'fa-capsules',
                                        'topikal' => 'fa-spray-can',
                                        'cough' => 'fa-lungs-virus',
                                        'antihistamin' => 'fa-allergies',
                                        'gastrointestinal' => 'fa-stomach',
                                        'jantung' => 'fa-heartbeat',
                                        'psikotropika' => 'fa-brain',
                                        'hormon' => 'fa-flask'
                                    ];
                                    $icon = $icons[strtolower($kategori['kategori'])] ?? 'fa-pills';
                                    ?>
                                <i class="fas <?php echo $icon; ?>"></i>
                            </div>
                            <h3><?php echo htmlspecialchars($kategori['kategori']); ?></h3>
                            <p><?php echo $kategori['jumlah']; ?> jenis obat</p>
                        </a>
                        <?php endwhile; ?>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <!-- Modal Quick View -->
    <div id="quickViewModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div id="quickViewContent"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    <script src="dashboard.js"></script>
</body>

</html>