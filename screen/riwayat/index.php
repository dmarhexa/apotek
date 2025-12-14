<?php
// apotek/screen/riwayat/index.php
require_once '../../config.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect jika belum login
if (!isset($_SESSION['user_id']) && !isset($_SESSION['pegawai_id'])) {
    header("Location: ../../auth/user_login.php");
    exit();
}

$id_pengguna = $_SESSION['user_id'] ?? $_SESSION['pegawai_id'];
$userType = isset($_SESSION['user_id']) ? 'user' : 'pegawai';

// Query Transaksi
$query = "SELECT t.*, 
          (SELECT COUNT(*) FROM transaksi_detail WHERE id_transaksi = t.id_transaksi) as total_items
          FROM transaksi t 
          WHERE t.id_pengguna = ? 
          ORDER BY t.tanggal_transaksi DESC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $id_pengguna);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi - Apotek Sehat</title>
    <link rel="stylesheet" href="../../screen/obat/style.css"> <!-- Reuse style -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Custom Styles for History */
        .history-container {
            padding: 30px;
        }
        .transaction-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            transition: transform 0.2s;
        }
        .transaction-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .card-header {
            padding: 15px 20px;
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .date {
            color: #64748b;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-lunas {
            background: #dcfce7;
            color: #166534;
        }
        .card-body {
            padding: 20px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .total-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: #10b981;
        }
        .item-count {
            color: #64748b;
        }
        .action-area {
            display: flex;
            justify-content: flex-end;
            margin-top: 15px;
        }
        .btn-view {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.9rem;
        }
        .btn-view:hover {
            background: #2563eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include '../../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <header class="page-header">
                <div class="header-content">
                    <h1><i class="fas fa-history"></i> Riwayat Transaksi</h1>
                    <p class="subtitle">Daftar pembelian obat Anda</p>
                </div>
            </header>

            <div class="history-container">
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <div class="transaction-card">
                            <div class="card-header">
                                <div class="date">
                                    <i class="far fa-calendar-alt"></i>
                                    <?php echo date('d F Y, H:i', strtotime($row['tanggal_transaksi'])); ?>
                                </div>
                                <span class="status-badge status-lunas">
                                    <?php echo htmlspecialchars($row['status_pembayaran']); ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="info-row">
                                    <span>Total Belanja</span>
                                    <span class="total-price">Rp <?php echo number_format($row['total_harga'], 0, ',', '.'); ?></span>
                                </div>
                                <div class="info-row">
                                    <span>Jumlah Item</span>
                                    <span class="item-count"><?php echo $row['total_items']; ?> item</span>
                                </div>
                                <div class="info-row">
                                    <span>Metode</span>
                                    <span><?php echo ucwords(str_replace('_', ' ', $row['metode_pembayaran'])); ?></span>
                                </div>
                                
                                <div class="action-area">
                                    <!-- Placeholder detail link -->
                                    <button class="btn-view" onclick="alert('Fitur detail sedang dalam pengembangan')">
                                        <i class="fas fa-eye"></i> Lihat Detail
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-results" style="text-align: center; padding: 50px;">
                        <i class="fas fa-shopping-basket" style="font-size: 4rem; color: #e2e8f0; margin-bottom: 20px;"></i>
                        <h3 style="color: #64748b;">Belum ada transaksi</h3>
                        <p style="color: #94a3b8; margin-bottom: 20px;">Anda belum melakukan pembelian obat apapun.</p>
                        <a href="../obat/" class="btn-view" style="background: #10b981;">
                            <i class="fas fa-plus"></i> Mulai Belanja
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
