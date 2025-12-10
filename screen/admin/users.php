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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_user'])) {
    $id_user = (int)$_POST['id_user'];
    
    $query = "DELETE FROM users WHERE id = $id_user";
    if (mysqli_query($conn, $query)) {
        set_message('User berhasil dihapus!', 'success');
        header('Location: users.php');
        exit();
    } else {
        set_message('Gagal menghapus user: ' . mysqli_error($conn), 'error');
    }
}

// Fetch users
$query = "SELECT * FROM users ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Kelola User</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
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
                <h1><i class="fas fa-users"></i> Kelola User</h1>
                <p>Lihat daftar pengguna yang terdaftar</p>
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
            
            <!-- Tabel Daftar User -->
            <div class="table-section">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-list"></i> Daftar Pengguna</h3>
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
                                        <th>Nama Lengkap</th>
                                        <th>Email</th>
                                        <th>No. HP</th>
                                        <th>Tanggal Daftar</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(mysqli_num_rows($result) > 0): ?>
                                        <?php while($user = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($user['nama_lengkap']); ?></strong>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($user['email']); ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($user['no_hp']); ?>
                                            </td>
                                            <td>
                                                <?php echo date('d M Y H:i', strtotime($user['created_at'])); ?>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <form method="POST" class="delete-form" onsubmit="return confirm('Yakin ingin menghapus user ini?')">
                                                        <input type="hidden" name="id_user" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" name="hapus_user" class="btn-action btn-delete" title="Hapus">
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
                                                    <i class="fas fa-users fa-2x"></i>
                                                    <h4>Belum ada user</h4>
                                                    <p>User yang mendaftar akan muncul disini</p>
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
