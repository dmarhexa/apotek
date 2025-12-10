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
    if (isset($_POST['tambah_obat'])) {
        tambahObat($conn);
    } elseif (isset($_POST['edit_obat'])) {
        editObat($conn);
    } elseif (isset($_POST['hapus_obat'])) {
        hapusObat($conn);
    }
}

// Fungsi tambah obat
function tambahObat($conn) {
    $nama_obat = mysqli_real_escape_string($conn, $_POST['nama_obat']);
    $jenis_obat = mysqli_real_escape_string($conn, $_POST['jenis_obat']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $harga = mysqli_real_escape_string($conn, $_POST['harga']);
    $stok = mysqli_real_escape_string($conn, $_POST['stok']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $indikasi = mysqli_real_escape_string($conn, $_POST['indikasi']);
    $cara_pakai = mysqli_real_escape_string($conn, $_POST['cara_pakai']);
    $efek_samping = mysqli_real_escape_string($conn, $_POST['efek_samping']);
    
    // Upload gambar
    $gambar = uploadGambar('obat', $conn);
    
    $query = "INSERT INTO obat (nama_obat, jenis_obat, kategori, harga, stok, deskripsi, indikasi, cara_pakai, efek_samping, gambar) 
              VALUES ('$nama_obat', '$jenis_obat', '$kategori', '$harga', '$stok', '$deskripsi', '$indikasi', '$cara_pakai', '$efek_samping', '$gambar')";
    
    if (mysqli_query($conn, $query)) {
        set_message('Obat berhasil ditambahkan!', 'success');
        header('Location: obat.php');
        exit();
    } else {
        set_message('Gagal menambahkan obat: ' . mysqli_error($conn), 'error');
    }
}

// Fungsi edit obat
function editObat($conn) {
    $id_obat = (int)$_POST['id_obat'];
    $nama_obat = mysqli_real_escape_string($conn, $_POST['nama_obat']);
    $jenis_obat = mysqli_real_escape_string($conn, $_POST['jenis_obat']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $harga = mysqli_real_escape_string($conn, $_POST['harga']);
    $stok = mysqli_real_escape_string($conn, $_POST['stok']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $indikasi = mysqli_real_escape_string($conn, $_POST['indikasi']);
    $cara_pakai = mysqli_real_escape_string($conn, $_POST['cara_pakai']);
    $efek_samping = mysqli_real_escape_string($conn, $_POST['efek_samping']);
    
    // Jika ada gambar baru, upload
    $gambar_query = "";
    if ($_FILES['gambar']['error'] === 0) {
        $gambar = uploadGambar('obat', $conn);
        $gambar_query = ", gambar = '$gambar'";
    }
    
    $query = "UPDATE obat SET 
              nama_obat = '$nama_obat',
              jenis_obat = '$jenis_obat',
              kategori = '$kategori',
              harga = '$harga',
              stok = '$stok',
              deskripsi = '$deskripsi',
              indikasi = '$indikasi',
              cara_pakai = '$cara_pakai',
              efek_samping = '$efek_samping'
              $gambar_query
              WHERE id_obat = $id_obat";
    
    if (mysqli_query($conn, $query)) {
        set_message('Obat berhasil diupdate!', 'success');
        header('Location: obat.php');
        exit();
    } else {
        set_message('Gagal mengupdate obat: ' . mysqli_error($conn), 'error');
    }
}

// Fungsi hapus obat
function hapusObat($conn) {
    $id_obat = (int)$_POST['id_obat'];
    
    // Hapus gambar jika ada
    $query = "SELECT gambar FROM obat WHERE id_obat = $id_obat";
    $result = mysqli_query($conn, $query);
    if ($row = mysqli_fetch_assoc($result)) {
        if ($row['gambar'] && $row['gambar'] != 'default.png' && file_exists('../../uploads/obat/' . $row['gambar'])) {
            unlink('../../uploads/obat/' . $row['gambar']);
        }
    }
    
    $query = "DELETE FROM obat WHERE id_obat = $id_obat";
    if (mysqli_query($conn, $query)) {
        set_message('Obat berhasil dihapus!', 'success');
        header('Location: obat.php');
        exit();
    } else {
        set_message('Gagal menghapus obat: ' . mysqli_error($conn), 'error');
    }
}

// Fungsi upload gambar
function uploadGambar($type = 'obat', $conn) {
    $target_dir = "../../uploads/$type/";
    
    // Buat folder jika belum ada
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Jika tidak ada file yang diupload
    if (!isset($_FILES['gambar']) || $_FILES['gambar']['error'] === 4) {
        return $type == 'obat' ? 'default.png' : 'default.png';
    }
    
    $file_name = time() . '_' . uniqid() . '_' . basename($_FILES["gambar"]["name"]);
    $target_file = $target_dir . $file_name;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Cek jika file adalah gambar
    $check = getimagesize($_FILES["gambar"]["tmp_name"]);
    if ($check === false) {
        set_message('File bukan gambar.', 'error');
        return $type == 'obat' ? 'default.png' : 'default.png';
    }
    
    // Cek ukuran file (max 2MB)
    if ($_FILES["gambar"]["size"] > 2097152) {
        set_message('Ukuran gambar terlalu besar (max 2MB).', 'error');
        return $type == 'obat' ? 'default.png' : 'default.png';
    }
    
    // Format yang diizinkan
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($imageFileType, $allowed_types)) {
        set_message('Hanya file JPG, JPEG, PNG, GIF, dan WebP yang diizinkan.', 'error');
        return $type == 'obat' ? 'default.png' : 'default.png';
    }
    
    // Upload file
    if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_file)) {
        return $file_name;
    } else {
        set_message('Terjadi kesalahan saat upload gambar.', 'error');
        return $type == 'obat' ? 'default.png' : 'default.png';
    }
}

// Ambil data untuk form
$obat_result = mysqli_query($conn, "SELECT * FROM obat ORDER BY created_at DESC");
$categories = mysqli_query($conn, "SELECT DISTINCT kategori FROM obat WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori");

// Reset pointer untuk kategori
$categories_for_form = mysqli_query($conn, "SELECT DISTINCT kategori FROM obat WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori");

// Data untuk edit
$edit_obat = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_query = "SELECT * FROM obat WHERE id_obat = $edit_id";
    $edit_result = mysqli_query($conn, $edit_query);
    if (mysqli_num_rows($edit_result) > 0) {
        $edit_obat = mysqli_fetch_assoc($edit_result);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Kelola Obat</title>
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
                <h1><i class="fas fa-pills"></i> Kelola Data Obat</h1>
                <p>Tambah, edit, atau hapus data obat di apotek</p>
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
            <!-- Form Tambah/Edit Obat -->
            <div class="form-section">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-<?php echo $edit_obat ? 'edit' : 'plus-circle'; ?>"></i> 
                            <?php echo $edit_obat ? 'Edit Obat' : 'Tambah Obat Baru'; ?>
                        </h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" id="obatForm">
                            <?php if($edit_obat): ?>
                                <input type="hidden" name="id_obat" value="<?php echo $edit_obat['id_obat']; ?>">
                            <?php endif; ?>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="nama_obat"><i class="fas fa-medkit"></i> Nama Obat</label>
                                    <input type="text" id="nama_obat" name="nama_obat" 
                                           value="<?php echo $edit_obat ? htmlspecialchars($edit_obat['nama_obat']) : ''; ?>"
                                           required placeholder="Masukkan nama obat">
                                </div>
                                
                                <div class="form-group">
                                    <label for="jenis_obat"><i class="fas fa-capsules"></i> Jenis Obat</label>
                                    <select id="jenis_obat" name="jenis_obat" required>
                                        <option value="">Pilih Jenis</option>
                                        <option value="tablet" <?php echo ($edit_obat && $edit_obat['jenis_obat'] == 'tablet') ? 'selected' : ''; ?>>Tablet</option>
                                        <option value="kapsul" <?php echo ($edit_obat && $edit_obat['jenis_obat'] == 'kapsul') ? 'selected' : ''; ?>>Kapsul</option>
                                        <option value="sirup" <?php echo ($edit_obat && $edit_obat['jenis_obat'] == 'sirup') ? 'selected' : ''; ?>>Sirup</option>
                                        <option value="salep" <?php echo ($edit_obat && $edit_obat['jenis_obat'] == 'salep') ? 'selected' : ''; ?>>Salep</option>
                                        <option value="inhaler" <?php echo ($edit_obat && $edit_obat['jenis_obat'] == 'inhaler') ? 'selected' : ''; ?>>Inhaler</option>
                                        <option value="tetes" <?php echo ($edit_obat && $edit_obat['jenis_obat'] == 'tetes') ? 'selected' : ''; ?>>Tetes</option>
                                        <option value="softgel" <?php echo ($edit_obat && $edit_obat['jenis_obat'] == 'softgel') ? 'selected' : ''; ?>>Softgel</option>
                                        <option value="inject" <?php echo ($edit_obat && $edit_obat['jenis_obat'] == 'inject') ? 'selected' : ''; ?>>Suntikan</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="kategori"><i class="fas fa-tags"></i> Kategori</label>
                                    <input type="text" id="kategori" name="kategori" 
                                           value="<?php echo $edit_obat ? htmlspecialchars($edit_obat['kategori']) : ''; ?>"
                                           required placeholder="Contoh: Antibiotik, Analgesik" list="kategori-list">
                                    <datalist id="kategori-list">
                                        <?php while($cat = mysqli_fetch_assoc($categories_for_form)): ?>
                                            <option value="<?php echo htmlspecialchars($cat['kategori']); ?>">
                                        <?php endwhile; ?>
                                    </datalist>
                                </div>
                                
                                <div class="form-group">
                                    <label for="harga"><i class="fas fa-money-bill-wave"></i> Harga (Rp)</label>
                                    <input type="number" id="harga" name="harga" 
                                           value="<?php echo $edit_obat ? $edit_obat['harga'] : ''; ?>"
                                           min="0" step="500" required placeholder="Contoh: 15000">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="stok"><i class="fas fa-box"></i> Stok</label>
                                    <input type="number" id="stok" name="stok" 
                                           value="<?php echo $edit_obat ? $edit_obat['stok'] : '0'; ?>"
                                           min="0" required placeholder="Jumlah stok">
                                </div>
                                
                                <div class="form-group">
                                    <label for="gambar"><i class="fas fa-image"></i> Gambar Obat</label>
                                    <input type="file" id="gambar" name="gambar" accept="image/*" class="file-input">
                                    <?php if($edit_obat && $edit_obat['gambar'] && $edit_obat['gambar'] != 'default.png'): ?>
                                        <div class="current-image">
                                            <small>Gambar saat ini:</small>
                                            <img src="../../uploads/obat/<?php echo $edit_obat['gambar']; ?>" 
                                                 alt="Current image" class="preview-img">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="deskripsi"><i class="fas fa-align-left"></i> Deskripsi</label>
                                <textarea id="deskripsi" name="deskripsi" rows="3" 
                                          placeholder="Deskripsi lengkap tentang obat"><?php echo $edit_obat ? htmlspecialchars($edit_obat['deskripsi']) : ''; ?></textarea>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="indikasi"><i class="fas fa-stethoscope"></i> Indikasi</label>
                                    <textarea id="indikasi" name="indikasi" rows="3" 
                                              placeholder="Untuk penyakit apa obat ini digunakan"><?php echo $edit_obat ? htmlspecialchars($edit_obat['indikasi']) : ''; ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="cara_pakai"><i class="fas fa-prescription"></i> Cara Pakai</label>
                                    <textarea id="cara_pakai" name="cara_pakai" rows="3" 
                                              placeholder="Petunjuk penggunaan obat"><?php echo $edit_obat ? htmlspecialchars($edit_obat['cara_pakai']) : ''; ?></textarea>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="efek_samping"><i class="fas fa-exclamation-triangle"></i> Efek Samping</label>
                                <textarea id="efek_samping" name="efek_samping" rows="3" 
                                          placeholder="Efek samping yang mungkin timbul"><?php echo $edit_obat ? htmlspecialchars($edit_obat['efek_samping']) : ''; ?></textarea>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="<?php echo $edit_obat ? 'edit_obat' : 'tambah_obat'; ?>" class="btn btn-primary">
                                    <i class="fas fa-save"></i> 
                                    <?php echo $edit_obat ? 'Update Obat' : 'Simpan Obat'; ?>
                                </button>
                                
                                <?php if($edit_obat): ?>
                                <a href="obat.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Batalkan
                                </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Tabel Daftar Obat -->
            <div class="table-section">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-list"></i> Daftar Obat</h3>
                        <div class="card-actions">
                            <input type="text" id="searchObat" placeholder="Cari obat..." class="search-input">
                            <button class="btn-refresh" onclick="location.reload()">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="obatTable">
                                <thead>
                                    <tr>
                                        <th>Gambar</th>
                                        <th>Nama Obat</th>
                                        <th>Kategori</th>
                                        <th>Harga</th>
                                        <th>Stok</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(mysqli_num_rows($obat_result) > 0): ?>
                                        <?php while($obat = mysqli_fetch_assoc($obat_result)): ?>
                                        <tr>
                                            <td>
                                                <div class="table-image">
                                                    <img src="../../uploads/obat/<?php echo $obat['gambar'] ?: 'default.png'; ?>" 
                                                         alt="<?php echo htmlspecialchars($obat['nama_obat']); ?>"
                                                         onerror="this.src='../../assets/images/obat/default.png'">
                                                </div>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($obat['nama_obat']); ?></strong><br>
                                                <small class="text-muted"><?php echo ucfirst($obat['jenis_obat']); ?></small>
                                            </td>
                                            <td><?php echo ucfirst(str_replace('_', ' ', $obat['kategori'])); ?></td>
                                            <td>
                                                <span class="price">Rp <?php echo number_format($obat['harga'], 0, ',', '.'); ?></span>
                                            </td>
                                            <td>
                                                <span class="stock-badge <?php echo $obat['stok'] == 0 ? 'danger' : ($obat['stok'] < 10 ? 'warning' : 'success'); ?>">
                                                    <?php echo $obat['stok']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="?edit=<?php echo $obat['id_obat']; ?>" class="btn-action btn-edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form method="POST" class="delete-form" onsubmit="return confirm('Yakin ingin menghapus obat ini?')">
                                                        <input type="hidden" name="id_obat" value="<?php echo $obat['id_obat']; ?>">
                                                        <button type="submit" name="hapus_obat" class="btn-action btn-delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                    <a href="../../screen/obat/?id=<?php echo $obat['id_obat']; ?>" target="_blank" class="btn-action btn-view">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">
                                                <div class="empty-state">
                                                    <i class="fas fa-pills fa-2x"></i>
                                                    <h4>Tidak ada data obat</h4>
                                                    <p>Mulai dengan menambahkan obat baru</p>
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