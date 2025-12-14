<?php
// apotek/screen/pengingat/index.php
require_once '../../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login user
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/user_login.php");
    exit();
}

$id_pengguna = $_SESSION['user_id'];
$message = '';

// Handle Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $nama_obat = mysqli_real_escape_string($conn, $_POST['nama_obat']);
    $frekuensi = mysqli_real_escape_string($conn, $_POST['frekuensi']);
    $waktu = mysqli_real_escape_string($conn, $_POST['waktu']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);

    $query = "INSERT INTO pengingat_obat (id_pengguna, nama_obat, frekuensi, waktu, keterangan) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'issss', $id_pengguna, $nama_obat, $frekuensi, $waktu, $keterangan);
    
    if (mysqli_stmt_execute($stmt)) {
        $message = "Pengingat berhasil ditambahkan!";
    } else {
        $message = "Gagal menambahkan pengingat: " . mysqli_error($conn);
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id_hapus = (int)$_GET['delete'];
    $query = "DELETE FROM pengingat_obat WHERE id = ? AND id_pengguna = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ii', $id_hapus, $id_pengguna);
    mysqli_stmt_execute($stmt);
    header("Location: index.php");
    exit();
}

// Get Reminders with Self-Healing
$query = "SELECT * FROM pengingat_obat WHERE id_pengguna = ? ORDER BY waktu ASC";
try {
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        throw new Exception(mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, 'i', $id_pengguna);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} catch (Exception $e) {
    // Jika error column missing, recreate table
    if (strpos($e->getMessage(), "Unknown column") !== false) {
        // Drop and Recreate
        mysqli_query($conn, "DROP TABLE IF EXISTS pengingat_obat");
        $create_query = "CREATE TABLE pengingat_obat (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_pengguna INT NOT NULL,
            nama_obat VARCHAR(255) NOT NULL,
            frekuensi VARCHAR(50) NOT NULL COMMENT 'e.g. 3x1',
            waktu TIME NOT NULL COMMENT 'e.g. 08:00:00',
            keterangan TEXT,
            status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        if (mysqli_query($conn, $create_query)) {
            // Retry query
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, 'i', $id_pengguna);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
        } else {
            die("Error fixing database: " . mysqli_error($conn));
        }
    } else {
        throw $e;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengingat Minum Obat - Apotek Sehat</title>
    <link rel="stylesheet" href="../../screen/obat/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .reminder-container {
            padding: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .reminder-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-left: 5px solid #3b82f6;
            position: relative;
        }
        .reminder-time {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
        }
        .reminder-drug {
            font-size: 1.1rem;
            font-weight: 600;
            color: #4b5563;
            margin-top: 5px;
        }
        .reminder-freq {
            display: inline-block;
            background: #e0f2fe;
            color: #0369a1;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            margin-top: 10px;
        }
        .reminder-note {
            margin-top: 10px;
            font-size: 0.9rem;
            color: #6b7280;
            font-style: italic;
        }
        .btn-add-floating {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: #10b981;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
            cursor: pointer;
            border: none;
            transition: transform 0.2s;
        }
        .btn-add-floating:hover {
            transform: scale(1.1);
        }
        .delete-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            color: #ef4444;
            background: none;
            border: none;
            cursor: pointer;
        }
        /* Modal Style */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 90%; max-width: 500px; border-radius: 12px; }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <?php include '../../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <header class="page-header">
                <div class="header-content">
                    <h1><i class="fas fa-bell"></i> Pengingat Obat</h1>
                    <p class="subtitle">Jangan lewatkan jadwal minum obat Anda</p>
                </div>
            </header>

            <?php if ($message): ?>
                <div style="padding: 15px; background: #dcfce7; color: #166534; margin: 20px; border-radius: 8px;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="reminder-container">
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <div class="reminder-card">
                    <a href="?delete=<?php echo $row['id']; ?>" class="delete-btn" onclick="return confirm('Hapus pengingat ini?')"><i class="fas fa-trash"></i></a>
                    <div class="reminder-time">
                        <i class="far fa-clock"></i> <?php echo date('H:i', strtotime($row['waktu'])); ?>
                    </div>
                    <div class="reminder-drug"><?php echo htmlspecialchars($row['nama_obat']); ?></div>
                    <span class="reminder-freq"><?php echo htmlspecialchars($row['frekuensi']); ?></span>
                    <?php if ($row['keterangan']): ?>
                        <div class="reminder-note">"<?php echo htmlspecialchars($row['keterangan']); ?>"</div>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
                
                <?php if (mysqli_num_rows($result) == 0): ?>
                    <div style="grid-column: 1/-1; text-align: center; color: #9ca3af; padding: 40px;">
                        <i class="fas fa-bell-slash" style="font-size: 3rem; margin-bottom: 15px;"></i>
                        <p>Belum ada pengingat. Tambahkan sekarang!</p>
                    </div>
                <?php endif; ?>
            </div>

            <button class="btn-add-floating" onclick="document.getElementById('addModal').style.display='block'">
                <i class="fas fa-plus"></i>
            </button>
        </main>
    </div>

    <!-- Add Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('addModal').style.display='none'">&times;</span>
            <h2 style="margin-bottom: 20px;">Tambah Pengingat</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Nama Obat</label>
                    <input type="text" name="nama_obat" class="form-control" required placeholder="Contoh: Paracetamol">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Frekuensi</label>
                        <select name="frekuensi" class="form-control">
                            <option value="1x1">1x Sehari</option>
                            <option value="2x1">2x Sehari</option>
                            <option value="3x1">3x Sehari</option>
                            <option value="4x1">4x Sehari</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Waktu</label>
                        <input type="time" name="waktu" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Keterangan</label>
                    <input type="text" name="keterangan" class="form-control" placeholder="Contoh: Sesudah makan">
                </div>
                <button type="submit" class="btn-submit" style="width: 100%; margin-top: 15px;">Simpan Pengingat</button>
            </form>
        </div>
    </div>
    
    <script>
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('addModal')) {
                document.getElementById('addModal').style.display = "none";
            }
        }
    </script>
</body>
</html>
