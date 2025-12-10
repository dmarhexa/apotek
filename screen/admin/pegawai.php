<?php
// screen/admin/pegawai.php - VERSION WITH ROLE COLUMN

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
    
    function getCurrentUser($conn = null) {
        if (!isset($_SESSION['pegawai_id'])) {
            return null;
        }
        
        $user_id = $_SESSION['pegawai_id'];
        
        if ($conn) {
            $sql = "SELECT id, username, nama_lengkap, status, role FROM pegawai 
                    WHERE id = '$user_id'";
            $result = mysqli_query($conn, $sql);
            
            if ($row = mysqli_fetch_assoc($result)) {
                return $row;
            }
        }
        
        return [
            'id' => $_SESSION['pegawai_id'] ?? null,
            'username' => $_SESSION['username'] ?? '',
            'nama_lengkap' => $_SESSION['nama_lengkap'] ?? '',
            'status' => $_SESSION['status'] ?? 'active',
            'role' => $_SESSION['role'] ?? 'admin'
        ];
    }
}

// Cek login
requireLogin();

$current_user = getCurrentUser($conn);
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';

// Clear session messages
unset($_SESSION['success']);
unset($_SESSION['error']);

// Handle form actions
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// CRUD Operations
switch ($action) {
    case 'add':
        // Tampilkan form tambah pegawai
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = mysqli_real_escape_string($conn, $_POST['username']);
            $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            
            // Validasi
            $errors = [];
            
            if (empty($username)) $errors[] = "Username harus diisi";
            if (empty($nama_lengkap)) $errors[] = "Nama lengkap harus diisi";
            if (empty($password)) $errors[] = "Password harus diisi";
            if ($password !== $confirm_password) $errors[] = "Password tidak cocok";
            if (strlen($password) < 6) $errors[] = "Password minimal 6 karakter";
            
            // Cek username sudah ada
            $check_sql = "SELECT id FROM pegawai WHERE username = '$username'";
            $check_result = mysqli_query($conn, $check_sql);
            if (mysqli_num_rows($check_result) > 0) {
                $errors[] = "Username sudah digunakan";
            }
            
            if (empty($errors)) {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert ke database dengan role default 'admin'
                $insert_sql = "INSERT INTO pegawai (username, password, nama_lengkap, role, status) 
                              VALUES ('$username', '$hashed_password', '$nama_lengkap', 'admin', 'active')";
                
                if (mysqli_query($conn, $insert_sql)) {
                    $_SESSION['success'] = "Pegawai berhasil ditambahkan!";
                    header("Location: pegawai.php");
                    exit();
                } else {
                    $error = "Gagal menambahkan pegawai: " . mysqli_error($conn);
                }
            } else {
                $error = implode("<br>", $errors);
            }
        }
        break;
        
    case 'edit':
        // Tampilkan form edit pegawai
        if ($id > 0) {
            $sql = "SELECT * FROM pegawai WHERE id = $id";
            $result = mysqli_query($conn, $sql);
            $pegawai = mysqli_fetch_assoc($result);
            
            if (!$pegawai) {
                $_SESSION['error'] = "Pegawai tidak ditemukan!";
                header("Location: pegawai.php");
                exit();
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
                $status = $_POST['status'];
                $change_password = isset($_POST['change_password']) ? true : false;
                
                if ($change_password) {
                    $password = $_POST['password'];
                    $confirm_password = $_POST['confirm_password'];
                    
                    if ($password !== $confirm_password) {
                        $error = "Password tidak cocok";
                    } elseif (strlen($password) < 6) {
                        $error = "Password minimal 6 karakter";
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $update_sql = "UPDATE pegawai SET 
                                      nama_lengkap = '$nama_lengkap',
                                      status = '$status',
                                      password = '$hashed_password'
                                      WHERE id = $id";
                    }
                } else {
                    $update_sql = "UPDATE pegawai SET 
                                  nama_lengkap = '$nama_lengkap',
                                  status = '$status'
                                  WHERE id = $id";
                }
                
                if (empty($error) && mysqli_query($conn, $update_sql)) {
                    $_SESSION['success'] = "Data pegawai berhasil diperbarui!";
                    header("Location: pegawai.php");
                    exit();
                } elseif (empty($error)) {
                    $error = "Gagal update: " . mysqli_error($conn);
                }
            }
        }
        break;
        
    case 'delete':
        // Hapus pegawai
        if ($id > 0 && $id != $current_user['id']) { // Tidak bisa hapus diri sendiri
            $delete_sql = "DELETE FROM pegawai WHERE id = $id";
            if (mysqli_query($conn, $delete_sql)) {
                $_SESSION['success'] = "Pegawai berhasil dihapus!";
            } else {
                $_SESSION['error'] = "Gagal menghapus pegawai!";
            }
        } elseif ($id == $current_user['id']) {
            $_SESSION['error'] = "Tidak bisa menghapus akun sendiri!";
        }
        header("Location: pegawai.php");
        exit();
        break;
}

// Ambil daftar pegawai untuk tampilan list
$sql = "SELECT * FROM pegawai ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
$pegawai_list = [];
while ($row = mysqli_fetch_assoc($result)) {
    $pegawai_list[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pegawai - Admin Apotek</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --primary-light: #d1fae5;
            --secondary: #3b82f6;
            --secondary-light: #dbeafe;
            --success: #10b981;
            --success-light: #d1fae5;
            --warning: #f59e0b;
            --warning-light: #fef3c7;
            --danger: #ef4444;
            --danger-light: #fee2e2;
            --dark: #1f2937;
            --light: #f9fafb;
            --gray: #6b7280;
            --gray-light: #e5e7eb;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: var(--dark);
            overflow-x: hidden;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        .admin-main {
            flex: 1;
            margin-left: 250px;
            padding: 30px;
            background: #f8fafc;
            min-height: 100vh;
        }
        
        .card {
            border-radius: 12px;
            border: 1px solid var(--gray-light);
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            margin-bottom: 24px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 12px 12px 0 0 !important;
            border: none;
            padding: 20px 25px;
            font-weight: 600;
        }
        
        .card-header.bg-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            padding: 10px 20px;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            transform: translateY(-1px);
        }
        
        .table th {
            background: var(--light);
            color: var(--dark);
            font-weight: 600;
            padding: 15px;
            border-bottom: 2px solid var(--gray-light);
        }
        
        .table td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid var(--gray-light);
        }
        
        .table tbody tr:hover {
            background: var(--primary-light);
        }
        
        .badge-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .badge-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-role {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        /* PERUBAHAN WARNA ROLE: Admin hijau, Developer biru */
        .badge-admin {
            background: linear-gradient(135deg, #3bf3b6ff 0%, #10a97eff 100%);
            color: white;
        }
        
        .badge-developer {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }
        
        .badge-custom { 
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
        }
        
        .btn-action {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 3px;
            transition: all 0.2s;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
        }
        
        .admin-title {
            color: var(--dark);
            font-weight: 700;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary-light);
        }
        
        .admin-title i {
            color: var(--primary);
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(16, 185, 129, 0.25);
        }
        
        .form-text {
            color: var(--gray);
            font-size: 0.85rem;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid var(--primary);
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: var(--gray);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        .role-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 8px;
            font-size: 0.9rem;
        }
        
        /* PERUBAHAN WARNA ICON: Admin hijau, Developer biru */
        .icon-admin {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
        
        .icon-developer {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }
        
        .icon-custom {
            background: rgba(245, 158, 11, 0.1);
            color: #d97706;
        }

        /* Dropdown Action Styles - PERBAIKAN POSISI DENGAN z-index TINGGI */
        .dropdown-action {
            position: relative;
            display: inline-block;
        }

        .dropdown-toggle-action {
            background: none;
            border: 1px solid var(--gray-light);
            color: var(--gray);
            padding: 5px 10px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
            position: relative;
        }

        .dropdown-toggle-action:hover {
            background: var(--gray-light);
            color: var(--dark);
            border-color: var(--gray);
        }

        .dropdown-menu-action {
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            border-radius: 8px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            min-width: 160px;
            z-index: 9999; /* Z-INDEX SANGAT TINGGI UNTUK MUNCUL DI ATAS SEMUA */
            display: none;
            border: 1px solid var(--gray-light);
            margin-top: 5px;
            transform: translateY(5px);
            animation: dropdownFade 0.2s ease-out;
        }

        @keyframes dropdownFade {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(5px);
            }
        }

        .dropdown-menu-action.show {
            display: block;
        }

        .dropdown-item-action {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 15px;
            color: var(--dark);
            text-decoration: none;
            transition: all 0.2s;
            border-bottom: 1px solid var(--gray-light);
            background: none;
            border: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
        }

        .dropdown-item-action:last-child {
            border-bottom: none;
        }

        .dropdown-item-action:hover {
            background: var(--secondary-light);
            color: var(--secondary);
        }

        .dropdown-item-action.edit:hover {
            background: var(--warning-light);
            color: #d97706;
        }

        .dropdown-item-action.delete:hover {
            background: var(--danger-light);
            color: var(--danger);
        }
        
        .dropdown-item-action.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: none !important;
            color: var(--gray) !important;
        }

        /* Status indicator */
        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
        }
        
        .status-active {
            background: #10b981;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
        }
        
        .status-inactive {
            background: #ef4444;
            box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2);
        }
        
        /* Card footer styling */
        .card-footer {
            background: transparent !important;
            border-top: 1px solid var(--gray-light) !important;
            position: relative;
            z-index: 1; /* Footer di bawah dropdown */
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar Admin -->
        <?php include 'sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="admin-main">
            <!-- Alert Messages -->
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Content berdasarkan action -->
            <?php if ($action === 'list'): ?>
                <!-- List Pegawai -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="admin-title">
                        <i class="fas fa-users-cog me-3"></i>Kelola Pegawai
                    </h1>
                    <a href="?action=add" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Tambah Admin Baru
                    </a>
                </div>
                
                <div class="card">
                    <div class="card-body p-0" style="position: relative;">
                        <?php if (empty($pegawai_list)): ?>
                            <div class="empty-state">
                                <i class="fas fa-users-slash"></i>
                                <h4 class="mb-3">Belum ada data pegawai</h4>
                                <p class="mb-4">Mulai dengan menambahkan admin pertama</p>
                                <a href="?action=add" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Tambah Admin Pertama
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th width="50">#</th>
                                            <th>Username</th>
                                            <th>Nama Lengkap</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Terakhir Login</th>
                                            <th width="100" style="position: relative; z-index: 1;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pegawai_list as $index => $pegawai): ?>
                                            <tr>
                                                <td class="fw-semibold"><?php echo $index + 1; ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="me-2">
                                                            <i class="fas fa-user-circle text-primary"></i>
                                                        </div>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($pegawai['username']); ?></strong>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($pegawai['nama_lengkap']); ?></td>
                                                <td>
                                                    <?php 
                                                    $role = $pegawai['role'] ?? 'admin';
                                                    $role_class = 'badge-custom';
                                                    $icon_class = 'icon-custom';
                                                    $role_icon = 'fa-code';
                                                    
                                                    if ($role == 'admin') {
                                                        $role_class = 'badge-admin';
                                                        $icon_class = 'icon-admin';
                                                        $role_icon = 'fa-user-shield';
                                                    } elseif ($role == 'developer') {
                                                        $role_class = 'badge-developer';
                                                        $icon_class = 'icon-developer';
                                                        $role_icon = 'fa-laptop-code';
                                                    }
                                                    ?>
                                                    <div class="d-flex align-items-center">
                                                        <div class="role-icon <?php echo $icon_class; ?>">
                                                            <i class="fas <?php echo $role_icon; ?>"></i>
                                                        </div>
                                                        <div>
                                                            <span class="badge-role <?php echo $role_class; ?>">
                                                                <?php echo ucfirst($role); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $status = $pegawai['status'];
                                                    $status_color = ($status == 'active') ? 'success' : 'danger';
                                                    $status_icon = ($status == 'active') ? 'check-circle' : 'times-circle';
                                                    ?>
                                                    <div class="d-flex align-items-center">
                                                        <span class="status-indicator status-<?php echo $status; ?>"></span>
                                                        <span class="badge-status badge-<?php echo $status; ?>">
                                                            <i class="fas fa-<?php echo $status_icon; ?> me-1"></i>
                                                            <?php echo ucfirst($status); ?>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if ($pegawai['last_login']): ?>
                                                        <small class="text-muted">
                                                            <i class="far fa-clock me-1"></i>
                                                            <?php echo date('d/m/Y H:i', strtotime($pegawai['last_login'])); ?>
                                                        </small>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="position: relative;">
                                                    <div class="dropdown-action">
                                                        <button class="dropdown-toggle-action" onclick="toggleActionDropdown(this, event)">
                                                            <i class="fas fa-ellipsis-v"></i>
                                                        </button>
                                                        <div class="dropdown-menu-action">
                                                            <a href="?action=edit&id=<?php echo $pegawai['id']; ?>" 
                                                            class="dropdown-item-action edit">
                                                                <i class="fas fa-edit"></i>
                                                                <span>Edit</span>
                                                            </a>
                                                            <?php if ($pegawai['id'] != $current_user['id']): ?>
                                                                <a href="?action=delete&id=<?php echo $pegawai['id']; ?>" 
                                                                class="dropdown-item-action delete"
                                                                onclick="return confirm('Yakin hapus pegawai <?php echo htmlspecialchars($pegawai['nama_lengkap']); ?>?')">
                                                                    <i class="fas fa-trash"></i>
                                                                    <span>Hapus</span>
                                                                </a>
                                                            <?php else: ?>
                                                                <span class="dropdown-item-action disabled">
                                                                    <i class="fas fa-ban"></i>
                                                                    <span>Tidak bisa hapus diri sendiri</span>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Total <?php echo count($pegawai_list); ?> pegawai terdaftar
                                        <span class="mx-2">•</span>
                                        <i class="fas fa-user-shield me-1"></i>
                                        <?php 
                                        $admin_count = array_filter($pegawai_list, function($p) {
                                            return ($p['role'] ?? 'admin') == 'admin';
                                        });
                                        echo count($admin_count) . ' admin';
                                        ?>
                                        <?php 
                                        $developer_count = array_filter($pegawai_list, function($p) {
                                            return ($p['role'] ?? 'admin') == 'developer';
                                        });
                                        if (count($developer_count) > 0): ?>
                                            <span class="mx-2">•</span>
                                            <i class="fas fa-laptop-code me-1"></i>
                                            <?php echo count($developer_count) . ' developer'; ?>
                                        <?php endif; ?>
                                        <?php 
                                        $custom_count = array_filter($pegawai_list, function($p) {
                                            $role = $p['role'] ?? 'admin';
                                            return ($role != 'admin' && $role != 'developer');
                                        });
                                        if (count($custom_count) > 0): ?>
                                            <span class="mx-2">•</span>
                                            <i class="fas fa-code me-1"></i>
                                            <?php echo count($custom_count) . ' custom role'; ?>
                                        <?php endif; ?>
                                    </small>
                                    <small class="text-muted">
                                        <i class="fas fa-sync-alt me-1"></i>
                                        Diperbarui: <?php echo date('d/m/Y H:i'); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
            <?php elseif ($action === 'add'): ?>
                <!-- Form Tambah Pegawai -->
                <div class="row">
                    <div class="col-lg-8 mx-auto">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="mb-0">
                                    <i class="fas fa-user-plus me-2"></i>Tambah Admin Baru
                                </h4>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="?action=add">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="username" class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="username" 
                                                   name="username" required
                                                   placeholder="Contoh: budi.santoso">
                                            <div class="form-text mt-2">
                                                <i class="fas fa-info-circle me-1"></i> Username harus unik dan tidak bisa diubah nanti
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="nama_lengkap" class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="nama_lengkap" 
                                                   name="nama_lengkap" required
                                                   placeholder="Contoh: Budi Santoso">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="password" class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" id="password" 
                                                   name="password" required minlength="6"
                                                   placeholder="Minimal 6 karakter">
                                            <div class="form-text mt-2">
                                                <i class="fas fa-lock me-1"></i> Password akan dienkripsi secara aman
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="confirm_password" class="form-label fw-semibold">Konfirmasi Password <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" id="confirm_password" 
                                                   name="confirm_password" required minlength="6"
                                                   placeholder="Ulangi password">
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-info mt-4">
                                        <div class="d-flex">
                                            <i class="fas fa-lightbulb fa-2x me-3 text-warning"></i>
                                            <div>
                                                <h5 class="alert-heading mb-2">Informasi Penting</h5>
                                                <p class="mb-0">Pegawai baru akan otomatis mendapatkan:</p>
                                                <ul class="mb-0 mt-2">
                                                    <li>Role: <strong>Admin</strong></li>
                                                    <li>Status: <strong>Aktif</strong></li>
                                                    <li>Akses: <strong>Full Admin Panel</strong></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                                        <a href="pegawai.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-arrow-left me-2"></i>Kembali ke Daftar
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Simpan Admin
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($action === 'edit' && isset($pegawai)): ?>
                <!-- Form Edit Pegawai -->
                <div class="row">
                    <div class="col-lg-8 mx-auto">
                        <div class="card">
                            <div class="card-header bg-warning">
                                <h4 class="mb-0">
                                    <i class="fas fa-user-edit me-2"></i>Edit Data Pegawai
                                </h4>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="?action=edit&id=<?php echo $id; ?>">
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Username</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light">
                                                <i class="fas fa-user"></i>
                                            </span>
                                            <input type="text" class="form-control bg-light" 
                                                   value="<?php echo htmlspecialchars($pegawai['username']); ?>" 
                                                   readonly>
                                        </div>
                                        <div class="form-text mt-2">
                                            <i class="fas fa-lock me-1"></i> Username tidak dapat diubah untuk menjaga konsistensi sistem
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="nama_lengkap" class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-id-card"></i>
                                            </span>
                                            <input type="text" class="form-control" id="nama_lengkap" 
                                                   name="nama_lengkap" required
                                                   value="<?php echo htmlspecialchars($pegawai['nama_lengkap']); ?>">
                                        </div>
                                    </div>
                                    
                                    <!-- Role Display (Read-only) -->
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Role</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-user-tag"></i>
                                            </span>
                                            <?php 
                                            $role = $pegawai['role'] ?? 'admin';
                                            $role_badge = 'badge-custom';
                                            if ($role == 'admin') $role_badge = 'badge-admin';
                                            elseif ($role == 'developer') $role_badge = 'badge-developer';
                                            ?>
                                            <div class="form-control bg-light d-flex align-items-center">
                                                <span class="badge-role <?php echo $role_badge; ?>">
                                                    <?php echo ucfirst($role); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="form-text mt-2">
                                            <?php if ($role == 'admin'): ?>
                                                <i class="fas fa-user-shield me-1"></i> Role Admin memberikan akses penuh ke sistem
                                            <?php elseif ($role == 'developer'): ?>
                                                <i class="fas fa-laptop-code me-1"></i> Role Developer untuk pengembangan sistem
                                            <?php else: ?>
                                                <i class="fas fa-code me-1"></i> Custom role dapat diubah hanya melalui database
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="status" class="form-label fw-semibold">Status Akun</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-power-off"></i>
                                            </span>
                                            <select class="form-control" id="status" name="status" required>
                                                <option value="active" <?php echo $pegawai['status'] == 'active' ? 'selected' : ''; ?>>
                                                    Aktif - Dapat login ke sistem
                                                </option>
                                                <option value="inactive" <?php echo $pegawai['status'] == 'inactive' ? 'selected' : ''; ?>>
                                                    Non-Aktif - Tidak dapat login
                                                </option>
                                            </select>
                                        </div>
                                        <div class="form-text mt-2">
                                            Status "Non-Aktif" akan mencegah pegawai login ke sistem admin
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <div class="card border">
                                            <div class="card-body">
                                                <div class="form-check form-switch mb-3">
                                                    <input class="form-check-input" type="checkbox" 
                                                           id="change_password" name="change_password"
                                                           style="width: 3rem; height: 1.5rem;">
                                                    <label class="form-check-label fw-semibold" for="change_password">
                                                        <i class="fas fa-key me-2"></i>Ganti Password
                                                    </label>
                                                </div>
                                                
                                                <div id="password_fields" style="display: none;">
                                                    <div class="alert alert-warning">
                                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                                        Password baru akan mengganti password lama secara permanen
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label for="password" class="form-label">Password Baru</label>
                                                            <input type="password" class="form-control" id="password" 
                                                                   name="password" minlength="6"
                                                                   placeholder="Masukkan password baru">
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                                                            <input type="password" class="form-control" id="confirm_password" 
                                                                   name="confirm_password" minlength="6"
                                                                   placeholder="Ulangi password baru">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                                        <a href="pegawai.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-arrow-left me-2"></i>Batal
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Update Data Pegawai
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <script>
                    document.getElementById('change_password').addEventListener('change', function() {
                        var passwordFields = document.getElementById('password_fields');
                        var passwordInputs = passwordFields.querySelectorAll('input[type="password"]');
                        
                        if (this.checked) {
                            passwordFields.style.display = 'block';
                            passwordInputs.forEach(function(input) {
                                input.required = true;
                            });
                        } else {
                            passwordFields.style.display = 'none';
                            passwordInputs.forEach(function(input) {
                                input.required = false;
                                input.value = '';
                            });
                        }
                    });
                </script>
            <?php endif; ?>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fungsi untuk toggle dropdown action dengan event untuk mencegah bubbling
        function toggleActionDropdown(button, event) {
            if (event) {
                event.stopPropagation();
                event.preventDefault();
            }
            
            const dropdown = button.nextElementSibling;
            const isShowing = dropdown.classList.contains('show');
            
            // Close all other dropdowns
            document.querySelectorAll('.dropdown-menu-action.show').forEach(function(d) {
                if (d !== dropdown) {
                    d.classList.remove('show');
                }
            });
            
            // Toggle current dropdown
            if (!isShowing) {
                dropdown.classList.add('show');
                
                // Position dropdown - pastikan muncul di atas semua elemen
                const rect = button.getBoundingClientRect();
                const dropdownHeight = dropdown.offsetHeight;
                const spaceBelow = window.innerHeight - rect.bottom;
                
                // Cek apakah dropdown akan keluar dari viewport bawah
                if (spaceBelow < dropdownHeight + 50) { // +50 untuk margin
                    // Jika tidak cukup ruang di bawah, tampilkan di atas
                    dropdown.style.bottom = '100%';
                    dropdown.style.top = 'auto';
                    dropdown.style.transform = 'translateY(-5px)';
                } else {
                    // Jika cukup ruang, tampilkan di bawah
                    dropdown.style.top = '100%';
                    dropdown.style.bottom = 'auto';
                    dropdown.style.transform = 'translateY(5px)';
                }
                
                // Pastikan tetap di dalam container tabel
                dropdown.style.right = '0';
                dropdown.style.left = 'auto';
                dropdown.style.zIndex = '9999'; // Pastikan z-index tinggi
                
                // Tambahkan overflow visible ke parent
                const tableRow = button.closest('tr');
                if (tableRow) {
                    tableRow.style.position = 'relative';
                    tableRow.style.zIndex = 'auto';
                }
            } else {
                dropdown.classList.remove('show');
            }
        }
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown-action') && !e.target.closest('.dropdown-menu-action')) {
                document.querySelectorAll('.dropdown-menu-action.show').forEach(function(dropdown) {
                    dropdown.classList.remove('show');
                });
            }
        });
        
        // Close dropdowns when pressing ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.dropdown-menu-action.show').forEach(function(dropdown) {
                    dropdown.classList.remove('show');
                });
            }
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Password strength indicator (optional)
        document.getElementById('password')?.addEventListener('input', function() {
            var password = this.value;
            var strength = 0;
            
            if (password.length >= 6) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            // You can add visual feedback here
        });
    </script>
</body>
</html>