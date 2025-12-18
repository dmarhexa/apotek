<?php
session_start();
require_once '../../config.php';

// Set base URL untuk sidebar
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/apotek';

// Query untuk mengambil data dokter
$query = "SELECT * FROM dokter ORDER BY nama_dokter ASC";
$result = mysqli_query($conn, $query);

// Jumlah dokter tersedia
$total_dokters = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konsultasi Dokter - Apotek Sehat</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <!-- Include Sidebar -->
    <?php include '../../includes/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="main-content">
        <!-- Main Content Header -->
        <div class="content-header">
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <div class="header-title">
                <h1><i class="fas fa-user-md"></i> Konsultasi Dokter</h1>
                <p>Konsultasikan kesehatan Anda dengan dokter spesialis kami</p>
            </div>
            <div class="header-actions">
                <button class="btn-refresh" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-container">
            <div class="stats-card primary">
                <div class="stats-icon">
                    <i class="fas fa-user-md"></i>
                </div>
                <div class="stats-content">
                    <h3><?php echo $total_dokters; ?></h3>
                    <p>Dokter Tersedia</p>
                </div>
            </div>
            <div class="stats-card success">
                <div class="stats-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stats-content">
                    <h3>24/7</h3>
                    <p>Konsultasi Online</p>
                </div>
            </div>
            <div class="stats-card warning">
                <div class="stats-icon">
                    <i class="fas fa-phone"></i>
                </div>
                <div class="stats-content">
                    <h3>100%</h3>
                    <p>Respon Cepat</p>
                </div>
            </div>
            <div class="stats-card info">
                <div class="stats-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stats-content">
                    <h3>4.9</h3>
                    <p>Rating Dokter</p>
                </div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="filter-section">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Cari dokter berdasarkan nama atau spesialisasi...">
                <button class="search-btn" onclick="searchDoctors()">
                    <i class="fas fa-search"></i>
                </button>
            </div>
            <div class="filter-controls">
                <div class="filter-group">
                    <label for="specializationFilter"><i class="fas fa-filter"></i> Filter Spesialisasi</label>
                    <select id="specializationFilter" onchange="filterDoctors()">
                        <option value="">Semua Spesialisasi</option>
                        <?php
                        // Query untuk mendapatkan spesialisasi unik
                        $spesialisasi_query = "SELECT DISTINCT spesialisasi FROM dokter ORDER BY spesialisasi";
                        $spesialisasi_result = mysqli_query($conn, $spesialisasi_query);
                        
                        while ($spesialisasi = mysqli_fetch_assoc($spesialisasi_result)):
                        ?>
                            <option value="<?php echo htmlspecialchars($spesialisasi['spesialisasi']); ?>">
                                <?php echo htmlspecialchars($spesialisasi['spesialisasi']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="experienceFilter"><i class="fas fa-briefcase"></i> Pengalaman</label>
                    <select id="experienceFilter" onchange="filterDoctors()">
                        <option value="">Semua Pengalaman</option>
                        <option value="0-5">0-5 Tahun</option>
                        <option value="5-10">5-10 Tahun</option>
                        <option value="10-15">10-15 Tahun</option>
                        <option value="15+">> 15 Tahun</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Doctors Grid -->
        <div class="doctors-container" id="doctorsContainer">
            <?php if ($total_dokters > 0): ?>
                <div class="doctors-grid">
                    <?php while ($dokter = mysqli_fetch_assoc($result)): ?>
                        <div class="doctor-card" 
                             data-specialization="<?php echo htmlspecialchars($dokter['spesialisasi']); ?>"
                             data-experience="<?php echo (int)$dokter['pengalaman']; ?>"
                             data-name="<?php echo htmlspecialchars(strtolower($dokter['nama_dokter'])); ?>">
                            
                            <!-- Doctor Header -->
                            <div class="doctor-header">
                                <div class="doctor-avatar">
                                    <?php if (!empty($dokter['foto'])): ?>
                                        <img src="../../uploads/dokter/<?php echo htmlspecialchars($dokter['foto']); ?>" 
                                             alt="<?php echo htmlspecialchars($dokter['nama_dokter']); ?>"
                                             onerror="this.src='../../assets/images/dokter/default.png'">
                                    <?php else: ?>
                                        <div class="avatar-placeholder">
                                            <i class="fas fa-user-md"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="online-status <?php echo (rand(0,1) ? 'online' : 'offline'); ?>"></div>
                                </div>
                                <div class="doctor-badges">
                                    <span class="specialization-badge"><?php echo htmlspecialchars($dokter['spesialisasi']); ?></span>
                                    <span class="experience-badge">
                                        <i class="fas fa-briefcase"></i>
                                        <?php echo htmlspecialchars($dokter['pengalaman']); ?> Tahun
                                    </span>
                                </div>
                            </div>

                            <!-- Doctor Info -->
                            <div class="doctor-info">
                                <h3>Dr. <?php echo htmlspecialchars($dokter['nama_dokter']); ?></h3>
                                <div class="doctor-rating">
                                    <?php
                                    $rating = rand(40, 50) / 10; // Simulasi rating 4.0-5.0
                                    $stars = floor($rating);
                                    $hasHalf = ($rating - $stars) >= 0.5;
                                    
                                    for ($i = 1; $i <= 5; $i++):
                                        if ($i <= $stars): ?>
                                            <i class="fas fa-star"></i>
                                        <?php elseif ($i == $stars + 1 && $hasHalf): ?>
                                            <i class="fas fa-star-half-alt"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif;
                                    endfor; ?>
                                    <span class="rating-text"><?php echo number_format($rating, 1); ?></span>
                                </div>
                            </div>

                            <!-- Doctor Details -->
                            <div class="doctor-details">
                                <div class="detail-item">
                                    <i class="fas fa-phone"></i>
                                    <span><?php echo formatPhoneNumber(htmlspecialchars($dokter['nomor_telepon'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-envelope"></i>
                                    <span><?php echo htmlspecialchars($dokter['email']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span class="consultation-fee">Rp <?php echo number_format($dokter['biaya_konsultasi'], 0, ',', '.'); ?></span>
                                </div>
                            </div>

                            <!-- Schedule -->
                            <div class="doctor-schedule">
                                <h4><i class="fas fa-calendar-alt"></i> Jadwal Praktek</h4>
                                <div class="schedule-content">
                                    <?php echo nl2br(htmlspecialchars($dokter['jadwal_praktek'])); ?>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="doctor-actions">

                                <?php if (!empty($dokter['nomor_telepon'])): ?>
                                    <button class="btn-consult" 
                                            onclick="startWhatsAppConsult('<?php echo htmlspecialchars($dokter['nama_dokter']); ?>', '<?php echo htmlspecialchars($dokter['nomor_telepon']); ?>')">
                                        <i class="fab fa-whatsapp"></i> Konsultasi
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <h3>Belum ada dokter tersedia</h3>
                    <p>Silakan hubungi administrator untuk menambahkan data dokter</p>
                    <button class="btn-primary" onclick="location.reload()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tips Section -->
        <div class="tips-section">
            <h3><i class="fas fa-lightbulb"></i> Tips Konsultasi Dokter Online</h3>
            <div class="tips-grid">
                <div class="tip-card">
                    <i class="fas fa-comments"></i>
                    <h4>Siapkan Pertanyaan</h4>
                    <p>Tulis pertanyaan Anda sebelum konsultasi agar lebih efektif</p>
                </div>
                <div class="tip-card">
                    <i class="fas fa-history"></i>
                    <h4>Riwayat Medis</h4>
                    <p>Siapkan riwayat penyakit dan obat yang sedang dikonsumsi</p>
                </div>
                <div class="tip-card">
                    <i class="fas fa-camera"></i>
                    <h4>Foto Kondisi</h4>
                    <p>Ambil foto kondisi yang ingin dikonsultasikan (jika perlu)</p>
                </div>
                <div class="tip-card">
                    <i class="fas fa-clock"></i>
                    <h4>Waktu Tepat</h4>
                    <p>Pilih waktu konsultasi saat Anda bisa fokus berbicara</p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="page-footer">
            <div class="footer-content">
                <div class="footer-section">
                    <h4><i class="fas fa-info-circle"></i> Informasi</h4>
                    <p>Konsultasi dokter online tersedia 24/7 melalui WhatsApp</p>
                    <p>Respon dokter dalam 1-3 jam pada jam kerja</p>
                </div>
                <div class="footer-section">
                    <h4><i class="fas fa-shield-alt"></i> Keamanan Data</h4>
                    <p>Data pasien dijamin kerahasiaannya</p>
                    <p>Konsultasi dilakukan secara private</p>
                </div>
                <div class="footer-section">
                    <h4><i class="fas fa-phone-alt"></i> Kontak Darurat</h4>
                    <p>Hubungi 112 untuk keadaan darurat</p>
                    <p>RS Terdekat: 119</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Apotek Sehat - Konsultasi Dokter Online</p>
                <p class="footer-note">Layanan ini tidak menggantikan pemeriksaan fisik oleh dokter</p>
            </div>
        </footer>
    </div>

    <!-- Doctor Detail Modal -->
    <div id="doctorDetailModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h3><i class="fas fa-user-md"></i> Detail Dokter</h3>
                <button class="modal-close" onclick="closeDoctorDetail()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="doctorDetailContent">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>

    <!-- WhatsApp Confirmation Modal -->
    <div id="whatsappModal" class="modal-overlay">
        <div class="modal-container small">
            <div class="modal-header">
                <h3><i class="fab fa-whatsapp"></i> Konfirmasi Konsultasi</h3>
                <button class="modal-close" onclick="closeWhatsAppModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="whatsapp-confirm">
                    <i class="fab fa-whatsapp fa-3x whatsapp-icon"></i>
                    <h4 id="doctorNameConfirm">Dr. Ahmad Setiawan</h4>
                    <p>Anda akan diarahkan ke WhatsApp untuk memulai konsultasi.</p>
                    <div class="whatsapp-actions">
                        <button class="btn-cancel" onclick="closeWhatsAppModal()">
                            <i class="fas fa-times"></i> Batal
                        </button>
                        <button class="btn-confirm" onclick="proceedToWhatsApp()">
                            <i class="fab fa-whatsapp"></i> Lanjut ke WhatsApp
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
    <script>
    // Auto-trigger doctor detail if consult_id is present
    <?php if (isset($_GET['consult_id'])): 
        $cid = (int)$_GET['consult_id'];
    ?>
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            showDoctorDetail(<?php echo $cid; ?>);
        }, 500);
    });
    <?php endif; ?>
    // Auto-trigger WhatsApp consultation if wa_id is present
    <?php if (isset($_GET['wa_id'])): 
        $wid = (int)$_GET['wa_id'];
        $w_query = mysqli_query($conn, "SELECT nama_dokter, nomor_telepon FROM dokter WHERE id_dokter = $wid");
        if ($w_row = mysqli_fetch_assoc($w_query)):
    ?>
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            startWhatsAppConsult('<?php echo addslashes($w_row['nama_dokter']); ?>', '<?php echo $w_row['nomor_telepon']; ?>');
        }, 500);
    });
    <?php endif; endif; ?>
    </script>
</body>
</html>

<?php 
// Helper function untuk format nomor telepon
function formatPhoneNumber($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) == 12) {
        return '+62 ' . substr($phone, 2, 3) . '-' . substr($phone, 5, 4) . '-' . substr($phone, 9, 4);
    } elseif (strlen($phone) == 11) {
        return '+62 ' . substr($phone, 1, 3) . '-' . substr($phone, 4, 4) . '-' . substr($phone, 8, 4);
    } elseif (strlen($phone) == 10) {
        return '+62 ' . substr($phone, 0, 3) . '-' . substr($phone, 3, 4) . '-' . substr($phone, 7, 4);
    }
    return $phone;
}

mysqli_close($conn); 
?>