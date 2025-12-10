<?php
// Gunakan path yang benar ke ROOT
require_once dirname(dirname(dirname(__FILE__))) . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beri Rating - Apotek Sehat</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <?php include '../../includes/sidebar.php'; ?>

    <div class="main-content">
        <!-- HEADER -->
        <div class="content-header">
            <div class="header-title">
                <h1><i class="fas fa-star"></i> Beri Ulasan</h1>
                <p>Bagikan pengalaman belanja Anda di Apotek Sehat</p>
            </div>
        </div>

        <!-- MAIN CONTENT -->
        <div class="rating-content">
            <div id="alert-box" class="alert"></div>

            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- FORM FOR LOGGED IN USERS -->
                <div class="rating-card">
                    <h2 class="form-title">Seberapa puas Anda?</h2>
                    
                    <form id="ratingForm">
                        <div class="star-container">
                            <div class="star-widget">
                                <input type="radio" name="bintang" id="rate-5" value="5">
                                <label for="rate-5" title="Sangat Puas"><i class="fas fa-star"></i></label>
                                
                                <input type="radio" name="bintang" id="rate-4" value="4">
                                <label for="rate-4" title="Puas"><i class="fas fa-star"></i></label>
                                
                                <input type="radio" name="bintang" id="rate-3" value="3">
                                <label for="rate-3" title="Biasa"><i class="fas fa-star"></i></label>
                                
                                <input type="radio" name="bintang" id="rate-2" value="2">
                                <label for="rate-2" title="Kurang"><i class="fas fa-star"></i></label>
                                
                                <input type="radio" name="bintang" id="rate-1" value="1">
                                <label for="rate-1" title="Sangat Buruk"><i class="fas fa-star"></i></label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="nama_user" class="form-label">Nama Lengkap</label>
                            <input type="text" id="nama_user" name="nama_user" class="form-control" 
                                   value="<?php echo htmlspecialchars($_SESSION['user_nama']); ?>"
                                   readonly>
                        </div>

                        <div class="form-group">
                            <label for="komentar" class="form-label">Komentar / Saran</label>
                            <textarea id="komentar" name="komentar" class="form-control" placeholder="Ceritakan pengalaman Anda di sini (opsional)..."></textarea>
                        </div>

                        <button type="submit" class="btn-submit">
                            <i class="fas fa-paper-plane"></i> Kirim Ulasan
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <!-- LOGIN PROMPT FOR GUESTS -->
                <div class="rating-card" style="padding: 60px 30px;">
                    <div style="font-size: 4rem; color: #d1d5db; margin-bottom: 20px;">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h2 class="form-title" style="margin-bottom: 15px;">Login Diperlukan</h2>
                    <p style="color: #6b7280; margin-bottom: 30px; font-size: 1.1rem;">
                        Maaf, Anda harus login sebagai pelanggan terdaftar untuk memberikan ulasan.
                    </p>
                    <a href="../../auth/user_login.php" class="btn-submit" style="text-decoration: none; width: auto; display: inline-flex; padding: 12px 30px;">
                        <i class="fas fa-sign-in-alt"></i> Login Sekarang
                    </a>
                    <p style="margin-top: 20px; color: #6b7280;">
                        Belum punya akun? <a href="../../auth/user_register.php" style="color: #10b981; font-weight: 600; text-decoration: none;">Daftar disini</a>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="script.js"></script>
</body>
</html>
