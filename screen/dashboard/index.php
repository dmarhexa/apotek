<?php
// apotek/screen/dashboard/index.php
require_once '../../config.php'; // Kembali 2 level ke config.php

// Query untuk data dashboard
$total_obat = mysqli_query($conn, "SELECT COUNT(*) as total FROM obat");
$total_obat = mysqli_fetch_assoc($total_obat)['total'];

// Dokter terbaik
$dokter_terbaik = mysqli_query($conn, "
    SELECT d.*, 
           (SELECT AVG(bintang) FROM rating WHERE id_dokter = d.id_dokter) as rating_avg
    FROM dokter d 
    ORDER BY rating_avg DESC 
    LIMIT 3
");

// Obat terlaris
$obat_terlaris = mysqli_query($conn, "
    SELECT o.*, SUM(dt.jumlah) as total_terjual
    FROM obat o
    LEFT JOIN transaksi_detail dt ON o.id_obat = dt.id_obat
    LEFT JOIN transaksi t ON dt.id_transaksi = t.id_transaksi
    WHERE t.status_pembayaran = 'lunas'
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

// Artikel kesehatan
$artikel_kesehatan = [
    ['judul' => 'Cara Menjaga Daya Tahan Tubuh di Musim Hujan', 'gambar' => 'artikel1.jpg', 'kategori' => 'Tips Sehat'],
    ['judul' => 'Pentingnya Minum Air Putih yang Cukup', 'gambar' => 'artikel2.jpg', 'kategori' => 'Hidup Sehat'],
    ['judul' => 'Mengenal Gejala Flu dan Pencegahannya', 'gambar' => 'artikel3.jpg', 'kategori' => 'Penyakit Umum'],
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda - Apotek Sehat</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css">
</head>

<body>
    <div class="container">
        <?php include '../../includes/sidebar.php'; ?>
        <!-- Kembali 2 level -->

        <main class="main-content">
            <!-- Header dengan Welcome Banner -->
            <header class="dashboard-header">
                <div class="welcome-banner">
                    <div class="welcome-text">
                        <h1>Selamat Datang di Apotek<span>Sehat</span></h1>
                        <p>Solusi kesehatan keluarga Anda. Temukan obat dan konsultasi dengan dokter terbaik.</p>
                        <a href="../obat/" class="btn-explore">
                            <i class="fas fa-shopping-bag"></i>
                            Belanja Sekarang
                        </a>
                    </div>
                    <div class="welcome-image">
                        <img src="<?php echo $base_url; ?>/assets/logo/logo_apotek.png" alt="Apotek Sehat"
                            style="width: 150px; height: auto; border-radius: 15px; object-fit: cover;">
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
                        <div class="feature-card">
                            <div class="feature-icon green">
                                <i class="fas fa-user-md"></i>
                            </div>
                            <h3>Konsultasi Dokter</h3>
                            <p>Online 24/7</p>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon orange">
                                <i class="fas fa-pills"></i>
                            </div>
                            <h3>Obat Lengkap</h3>
                            <p>1000+ jenis obat</p>
                        </div>
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
                        <?php while ($obat = mysqli_fetch_assoc($obat_terlaris)): ?>
                        <div class="product-card">
                            <div class="product-badge">Terlaris</div>
                            <div class="product-image">
                                <img src="../../assets/images/obat/default.jpg?php echo $obat['gambar'] ?: 'default.jpg'; ?>"
                                    alt="<?php echo $obat['nama_obat']; ?>">
                            </div>
                            <div class="product-info">
                                <span class="product-category"><?php echo $obat['kategori']; ?></span>
                                <h3 class="product-title"><?php echo htmlspecialchars($obat['nama_obat']); ?></h3>
                                <p class="product-desc"><?php echo substr($obat['deskripsi'], 0, 60) . '...'; ?></p>
                                <div class="product-footer">
                                    <div class="product-price">
                                        <span class="price">Rp
                                            <?php echo number_format($obat['harga'], 0, ',', '.'); ?></span>
                                        <span class="stock">Stok: <?php echo $obat['stok']; ?></span>
                                    </div>
                                    <button class="btn-add-cart" data-id="<?php echo $obat['id_obat']; ?>">
                                        <i class="fas fa-cart-plus"></i>
                                    </button>
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
                        <?php while ($dokter = mysqli_fetch_assoc($dokter_terbaik)): ?>
                        <div class="doctor-card">
                            <div class="doctor-image">
                                <img src="../../assets/dokter/default_dokter.png?php echo $dokter['foto'] ?: 'default.jpg'; ?>"
                                    alt="<?php echo $dokter['nama_dokter']; ?>">
                                <div class="doctor-rating">
                                    <i class="fas fa-star"></i>
                                    <span><?php echo number_format($dokter['rating_avg'] ?? 4.5, 1); ?></span>
                                </div>
                            </div>
                            <div class="doctor-info">
                                <h3><?php echo $dokter['nama_dokter']; ?></h3>
                                <p class="doctor-specialty"><?php echo $dokter['spesialisasi']; ?></p>
                                <p class="doctor-experience">
                                    <i class="fas fa-briefcase"></i>
                                    <?php echo $dokter['pengalaman']; ?> pengalaman
                                </p>
                                <div class="doctor-schedule">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span><?php echo substr($dokter['jadwal_praktek'], 0, 20); ?>...</span>
                                </div>
                                <div class="doctor-footer">
                                    <span class="consultation-fee">
                                        Rp <?php echo number_format($dokter['biaya_konsultasi'], 0, ',', '.'); ?>
                                    </span>
                                    <a href="../dokter/?id=<?php echo $dokter['id_dokter']; ?>" class="btn-consult">
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
                        <a href="../obat/?kategori=<?php echo urlencode($kategori['kategori']); ?>"
                            class="category-card">
                            <div class="category-icon">
                                <?php
                                    $icons = [
                                        'antibiotik' => 'fa-bacteria',
                                        'analgesik' => 'fa-head-side-virus',
                                        'vitamin' => 'fa-capsules',
                                        'topikal' => 'fa-spray-can',
                                        'cough' => 'fa-lungs-virus'
                                    ];
                                    $icon = $icons[strtolower($kategori['kategori'])] ?? 'fa-pills';
                                    ?>
                                <i class="fas <?php echo $icon; ?>"></i>
                            </div>
                            <h3><?php echo $kategori['kategori']; ?></h3>
                            <p><?php echo $kategori['jumlah']; ?> jenis obat</p>
                        </a>
                        <?php endwhile; ?>
                    </div>
                </section>

                <!-- Promo Banner -->
                <!-- <div class="promo-banner">
                    <div class="promo-content">
                        <h2>Diskon 30% untuk Pembelian Pertama!</h2>
                        <p>Gunakan kode: <strong>APOTEK30</strong> untuk mendapatkan diskon khusus.</p>
                        <a href="../obat/" class="btn-promo">Klaim Sekarang</a>
                    </div>
                    <div class="promo-image">
                        <img src="https://cdn.pixabay.com/photo/2017/08/30/17/25/medicine-2698576_1280.png" alt="Promo">
                    </div>
                </div> -->
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