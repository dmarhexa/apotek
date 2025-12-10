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

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_rating'])) {
    $id_rating = (int)$_POST['id_rating'];
    
    $query = "DELETE FROM rating WHERE id_rating = $id_rating";
    if (mysqli_query($conn, $query)) {
        set_message('Rating berhasil dihapus!', 'success');
        header('Location: rating.php');
        exit();
    } else {
        set_message('Gagal menghapus rating: ' . mysqli_error($conn), 'error');
    }
}

// Fetch ratings
$query = "SELECT * FROM rating ORDER BY tanggal_rating DESC";
$rating_result = mysqli_query($conn, $query);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Kelola Rating</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .star-rating {
            color: #f1c40f;
        }
        .text-muted {
            color: #6c757d;
            font-size: 0.85em;
        }
        .comment-text {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        /* Override grid layout for full width */
        .content-wrapper {
            grid-template-columns: 1fr !important;
        }
    </style>
</head>
<body>
    
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="main-header">
            <div class="header-left">
                <h1><i class="fas fa-star"></i> Kelola Rating</h1>
                <p>Lihat ulasan dan rating dari pelanggan</p>
            </div>
        </header>

        <?php if(isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
            <?php 
            echo $_SESSION['message'];
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            ?>
        </div>
        <?php endif; ?>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            
            <!-- Tabel Daftar Rating -->
            <div class="table-section">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-list"></i> Daftar Ulasan Masuk</h3>
                        <div class="card-actions">
                            <button class="btn-refresh" onclick="location.reload()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Nama User</th>
                                        <th>Rating</th>
                                        <th>Komentar</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(mysqli_num_rows($rating_result) > 0): ?>
                                        <?php while($rating = mysqli_fetch_assoc($rating_result)): ?>
                                        <tr>
                                            <td>
                                                <?php echo date('d M Y', strtotime($rating['tanggal_rating'])); ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($rating['nama_user']); ?></strong>
                                            </td>
                                            <td>
                                                <div class="star-rating">
                                                    <?php 
                                                    for($i=1; $i<=5; $i++) {
                                                        if($i <= $rating['bintang']) {
                                                            echo '<i class="fas fa-star"></i>';
                                                        } else {
                                                            echo '<i class="far fa-star"></i>';
                                                        }
                                                    }
                                                    ?>
                                                </div>
                                            </td>
                                            <td class="comment-text" title="<?php echo htmlspecialchars($rating['komentar']); ?>">
                                                <?php echo htmlspecialchars($rating['komentar']); ?>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <form method="POST" class="delete-form" onsubmit="return confirm('Yakin ingin menghapus rating ini?')">
                                                        <input type="hidden" name="id_rating" value="<?php echo $rating['id_rating']; ?>">
                                                        <button type="submit" name="hapus_rating" class="btn-action btn-delete" title="Hapus">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">
                                                <div class="empty-state">
                                                    <i class="far fa-star fa-2x"></i>
                                                    <h4>Belum ada rating</h4>
                                                    <p>Rating dari user akan muncul disini</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
