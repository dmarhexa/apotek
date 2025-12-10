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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['tambah_dokter'])) {
        tambahDokter($conn);
    } elseif (isset($_POST['edit_dokter'])) {
        editDokter($conn);
    } elseif (isset($_POST['hapus_dokter'])) {
        hapusDokter($conn);
    }
}

// Fungsi tambah dokter
function tambahDokter($conn) {
    $nama_dokter = mysqli_real_escape_string($conn, $_POST['nama_dokter']);
    $spesialisasi = mysqli_real_escape_string($conn, $_POST['spesialisasi']);
    $pengalaman = mysqli_real_escape_string($conn, $_POST['pengalaman']);
    $nomor_telepon = mysqli_real_escape_string($conn, $_POST['nomor_telepon']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $jadwal_praktek = mysqli_real_escape_string($conn, $_POST['jadwal_praktek']);
    $biaya_konsultasi = mysqli_real_escape_string($conn, $_POST['biaya_konsultasi']);
    
    // Upload foto
    $foto = uploadGambar('dokter', $conn);
    
    $query = "INSERT INTO dokter (nama_dokter, spesialisasi, pengalaman, nomor_telepon, email, jadwal_praktek, biaya_konsultasi, foto) 
              VALUES ('$nama_dokter', '$spesialisasi', '$pengalaman', '$nomor_telepon', '$email', '$jadwal_praktek', '$biaya_konsultasi', '$foto')";
    
    if (mysqli_query($conn, $query)) {
        set_message('Dokter berhasil ditambahkan!', 'success');
        header('Location: dokter.php');
        exit();
    } else {
        set_message('Gagal menambahkan dokter: ' . mysqli_error($conn), 'error');
    }
}

// Fungsi edit dokter
function editDokter($conn) {
    $id_dokter = (int)$_POST['id_dokter'];
    $nama_dokter = mysqli_real_escape_string($conn, $_POST['nama_dokter']);
    $spesialisasi = mysqli_real_escape_string($conn, $_POST['spesialisasi']);
    $pengalaman = mysqli_real_escape_string($conn, $_POST['pengalaman']);
    $nomor_telepon = mysqli_real_escape_string($conn, $_POST['nomor_telepon']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $jadwal_praktek = mysqli_real_escape_string($conn, $_POST['jadwal_praktek']);
    $biaya_konsultasi = mysqli_real_escape_string($conn, $_POST['biaya_konsultasi']);
    
    // Jika ada foto baru, upload
    $foto_query = "";
    if ($_FILES['foto']['error'] === 0) {
        $foto = uploadGambar('dokter', $conn);
        $foto_query = ", foto = '$foto'";
    }
    
    $query = "UPDATE dokter SET 
              nama_dokter = '$nama_dokter',
              spesialisasi = '$spesialisasi',
              pengalaman = '$pengalaman',
              nomor_telepon = '$nomor_telepon',
              email = '$email',
              jadwal_praktek = '$jadwal_praktek',
              biaya_konsultasi = '$biaya_konsultasi'
              $foto_query
              WHERE id_dokter = $id_dokter";
    
    if (mysqli_query($conn, $query)) {
        set_message('Dokter berhasil diupdate!', 'success');
        header('Location: dokter.php');
        exit();
    } else {
        set_message('Gagal mengupdate dokter: ' . mysqli_error($conn), 'error');
    }
}

// Fungsi hapus dokter
function hapusDokter($conn) {
    $id_dokter = (int)$_POST['id_dokter'];
    
    // Hapus foto jika ada
    $query = "SELECT foto FROM dokter WHERE id_dokter = $id_dokter";
    $result = mysqli_query($conn, $query);
    if ($row = mysqli_fetch_assoc($result)) {
        if ($row['foto'] && $row['foto'] != 'default.png' && file_exists('../../uploads/dokter/' . $row['foto'])) {
            unlink('../../uploads/dokter/' . $row['foto']);
        }
    }
    
    $query = "DELETE FROM dokter WHERE id_dokter = $id_dokter";
    if (mysqli_query($conn, $query)) {
        set_message('Dokter berhasil dihapus!', 'success');
        header('Location: dokter.php');
        exit();
    } else {
        set_message('Gagal menghapus dokter: ' . mysqli_error($conn), 'error');
    }
}

// Fungsi upload gambar
function uploadGambar($type = 'dokter', $conn) {
    $target_dir = "../../uploads/$type/";
    
    // Buat folder jika belum ada
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Jika tidak ada file yang diupload
    if (!isset($_FILES['foto']) || $_FILES['foto']['error'] === 4) {
        return $type == 'dokter' ? 'default.png' : 'default.jpg';
    }
    
    $file_name = time() . '_' . uniqid() . '_' . basename($_FILES["foto"]["name"]);
    $target_file = $target_dir . $file_name;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Cek jika file adalah gambar
    $check = getimagesize($_FILES["foto"]["tmp_name"]);
    if ($check === false) {
        set_message('File bukan gambar.', 'error');
        return $type == 'dokter' ? 'default.png' : 'default.jpg';
    }
    
    // Cek ukuran file (max 2MB)
    if ($_FILES["foto"]["size"] > 2097152) {
        set_message('Ukuran gambar terlalu besar (max 2MB).', 'error');
        return $type == 'dokter' ? 'default.png' : 'default.jpg';
    }
    
    // Format yang diizinkan
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($imageFileType, $allowed_types)) {
        set_message('Hanya file JPG, JPEG, PNG, GIF, dan WebP yang diizinkan.', 'error');
        return $type == 'dokter' ? 'default.png' : 'default.jpg';
    }
    
    // Upload file
    if (move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file)) {
        return $file_name;
    } else {
        set_message('Terjadi kesalahan saat upload gambar.', 'error');
        return $type == 'dokter' ? 'default.png' : 'default.jpg';
    }
}

// Ambil data dokter
$dokter_result = mysqli_query($conn, "SELECT * FROM dokter ORDER BY created_at DESC");

// Data untuk edit
$edit_dokter = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_query = "SELECT * FROM dokter WHERE id_dokter = $edit_id";
    $edit_result = mysqli_query($conn, $edit_query);
    if (mysqli_num_rows($edit_result) > 0) {
        $edit_dokter = mysqli_fetch_assoc($edit_result);
    }
}

// Spesialisasi options
$spesialisasi_options = [
    'Dokter Umum', 'Dokter Anak', 'Dokter Kandungan', 'Dokter Bedah', 
    'Dokter Jantung', 'Dokter Kulit', 'Dokter Saraf', 'Dokter THT',
    'Dokter Gigi', 'Dokter Mata', 'Dokter Paru', 'Dokter Jiwa',
    'Dokter Rehabilitasi Medik', 'Dokter Radiologi'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Kelola Dokter</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="main-header">
            <div class="header-left">
                <h1><i class="fas fa-user-md"></i> Kelola Data Dokter</h1>
                <p>Tambah, edit, atau hapus data dokter konsultasi</p>
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
            <!-- Form Tambah/Edit Dokter -->
            <div class="form-section">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-<?php echo $edit_dokter ? 'edit' : 'user-plus'; ?>"></i> 
                            <?php echo $edit_dokter ? 'Edit Dokter' : 'Tambah Dokter Baru'; ?>
                        </h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" id="dokterForm">
                            <?php if($edit_dokter): ?>
                                <input type="hidden" name="id_dokter" value="<?php echo $edit_dokter['id_dokter']; ?>">
                            <?php endif; ?>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="nama_dokter"><i class="fas fa-user-md"></i> Nama Dokter</label>
                                    <input type="text" id="nama_dokter" name="nama_dokter" 
                                           value="<?php echo $edit_dokter ? htmlspecialchars($edit_dokter['nama_dokter']) : ''; ?>"
                                           required placeholder="Masukkan nama lengkap dokter">
                                </div>
                                
                                <div class="form-group">
                                    <label for="spesialisasi"><i class="fas fa-stethoscope"></i> Spesialisasi</label>
                                    <select id="spesialisasi" name="spesialisasi" required>
                                        <option value="">Pilih Spesialisasi</option>
                                        <?php foreach($spesialisasi_options as $spesialisasi): ?>
                                            <option value="<?php echo $spesialisasi; ?>" 
                                                <?php echo ($edit_dokter && $edit_dokter['spesialisasi'] == $spesialisasi) ? 'selected' : ''; ?>>
                                                <?php echo $spesialisasi; ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <option value="Lainnya" <?php echo ($edit_dokter && !in_array($edit_dokter['spesialisasi'], $spesialisasi_options)) ? 'selected' : ''; ?>>Lainnya</option>
                                    </select>
                                    <input type="text" id="spesialisasi_lainnya" name="spesialisasi_lainnya" 
                                           value="<?php echo ($edit_dokter && !in_array($edit_dokter['spesialisasi'], $spesialisasi_options)) ? htmlspecialchars($edit_dokter['spesialisasi']) : ''; ?>"
                                           placeholder="Tulis spesialisasi lain" 
                                           style="display: <?php echo ($edit_dokter && !in_array($edit_dokter['spesialisasi'], $spesialisasi_options)) ? 'block' : 'none'; ?>; margin-top: 8px;">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="pengalaman"><i class="fas fa-briefcase"></i> Pengalaman (Tahun)</label>
                                    <input type="number" id="pengalaman" name="pengalaman" 
                                           value="<?php echo $edit_dokter ? $edit_dokter['pengalaman'] : '1'; ?>"
                                           min="1" max="50" required placeholder="Jumlah tahun pengalaman">
                                </div>
                                
                                <div class="form-group">
                                    <label for="biaya_konsultasi"><i class="fas fa-money-bill-wave"></i> Biaya Konsultasi (Rp)</label>
                                    <input type="number" id="biaya_konsultasi" name="biaya_konsultasi" 
                                           value="<?php echo $edit_dokter ? $edit_dokter['biaya_konsultasi'] : '100000'; ?>"
                                           min="0" step="5000" required placeholder="Contoh: 150000">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="nomor_telepon"><i class="fas fa-phone"></i> Nomor Telepon/WhatsApp</label>
                                    <input type="tel" id="nomor_telepon" name="nomor_telepon" 
                                           value="<?php echo $edit_dokter ? htmlspecialchars($edit_dokter['nomor_telepon']) : ''; ?>"
                                           required placeholder="Contoh: 081234567890">
                                </div>
                                
                                <div class="form-group">
                                    <label for="email"><i class="fas fa-envelope"></i> Email</label>
                                    <input type="email" id="email" name="email" 
                                           value="<?php echo $edit_dokter ? htmlspecialchars($edit_dokter['email']) : ''; ?>"
                                           placeholder="email@example.com">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="jadwal_praktek"><i class="fas fa-calendar-alt"></i> Jadwal Praktek</label>
                                <textarea id="jadwal_praktek" name="jadwal_praktek" rows="4" 
                                          placeholder="Contoh:
Senin-Jumat: 08:00 - 16:00
Sabtu: 08:00 - 12:00
Minggu: Libur"><?php echo $edit_dokter ? htmlspecialchars($edit_dokter['jadwal_praktek']) : ''; ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="foto"><i class="fas fa-camera"></i> Foto Profil</label>
                                <input type="file" id="foto" name="foto" accept="image/*" class="file-input">
                                <?php if($edit_dokter && $edit_dokter['foto'] && $edit_dokter['foto'] != 'default.png'): ?>
                                    <div class="current-image">
                                        <small>Foto saat ini:</small>
                                        <img src="../../uploads/dokter/<?php echo $edit_dokter['foto']; ?>" 
                                             alt="Current photo" class="preview-img">
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="<?php echo $edit_dokter ? 'edit_dokter' : 'tambah_dokter'; ?>" class="btn btn-primary">
                                    <i class="fas fa-save"></i> 
                                    <?php echo $edit_dokter ? 'Update Dokter' : 'Simpan Dokter'; ?>
                                </button>
                                
                                <?php if($edit_dokter): ?>
                                <a href="dokter.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Batalkan
                                </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Tabel Daftar Dokter -->
            <div class="table-section">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-list"></i> Daftar Dokter</h3>
                        <div class="card-actions">
                            <input type="text" id="searchDokter" placeholder="Cari dokter..." class="search-input">
                            <button class="btn-refresh" onclick="location.reload()">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="dokterTable">
                                <thead>
                                    <tr>
                                        <th>Foto</th>
                                        <th>Nama Dokter</th>
                                        <th>Spesialisasi</th>
                                        <th>Pengalaman</th>
                                        <th>Kontak</th>
                                        <th>Biaya</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(mysqli_num_rows($dokter_result) > 0): ?>
                                        <?php while($dokter = mysqli_fetch_assoc($dokter_result)): ?>
                                        <tr>
                                            <td>
                                                <div class="table-image">
                                                    <img src="../../uploads/dokter/<?php echo $dokter['foto'] ?: 'default.png'; ?>" 
                                                         alt="<?php echo htmlspecialchars($dokter['nama_dokter']); ?>"
                                                         onerror="this.src='../../assets/images/dokter/default.png'">
                                                </div>
                                            </td>
                                            <td>
                                                <strong>Dr. <?php echo htmlspecialchars($dokter['nama_dokter']); ?></strong><br>
                                                <small class="text-muted"><?php echo $dokter['email']; ?></small>
                                            </td>
                                            <td>
                                                <span class="specialization-badge"><?php echo htmlspecialchars($dokter['spesialisasi']); ?></span>
                                            </td>
                                            <td>
                                                <span class="experience-badge">
                                                    <i class="fas fa-briefcase"></i> <?php echo $dokter['pengalaman']; ?> Tahun
                                                </span>
                                            </td>
                                            <td>
                                                <small><?php echo htmlspecialchars($dokter['nomor_telepon']); ?></small>
                                            </td>
                                            <td>
                                                <span class="price">Rp <?php echo number_format($dokter['biaya_konsultasi'], 0, ',', '.'); ?></span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="?edit=<?php echo $dokter['id_dokter']; ?>" class="btn-action btn-edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form method="POST" class="delete-form" onsubmit="return confirm('Yakin ingin menghapus dokter ini?')">
                                                        <input type="hidden" name="id_dokter" value="<?php echo $dokter['id_dokter']; ?>">
                                                        <button type="submit" name="hapus_dokter" class="btn-action btn-delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                    <a href="../../screen/konsultasi/?view=<?php echo $dokter['id_dokter']; ?>" target="_blank" class="btn-action btn-view">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">
                                                <div class="empty-state">
                                                    <i class="fas fa-user-md fa-2x"></i>
                                                    <h4>Tidak ada data dokter</h4>
                                                    <p>Mulai dengan menambahkan dokter baru</p>
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