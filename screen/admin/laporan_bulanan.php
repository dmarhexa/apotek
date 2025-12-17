<?php
// screen/admin/laporan_bulanan.php
require_once '../../config.php';
require_once '../../includes/auth.php';

// Cek login admin
if (!isset($_SESSION['pegawai_id']) && !isset($_SESSION['user_role'])) {
    header("Location: " . $base_url . "/auth/login.php");
    exit();
}

// Set default month/year
$month = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('m');
$year = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');

// Array nama bulan
$bulan_indo = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

// 1. STATISTIK PENJUALAN
$revenue = 0;
$total_transaksi = 0;
$chart_labels = [];
$chart_data = [];

// Query Total Pendapatan Bulan Ini
// Query Total Pendapatan Bulan Ini
$date_col = 'created_at'; // Default
$sql_check = "SHOW COLUMNS FROM transaksi LIKE 'created_at'";
$check = @mysqli_query($conn, $sql_check);
if ($check && mysqli_num_rows($check) == 0) {
    // Jika tidak ada created_at, cek tanggal_transaksi
    $sql_check2 = "SHOW COLUMNS FROM transaksi LIKE 'tanggal_transaksi'";
    $check2 = @mysqli_query($conn, $sql_check2);
    if ($check2 && mysqli_num_rows($check2) > 0) {
        $date_col = 'tanggal_transaksi';
    }
}

$query_revenue = "SELECT SUM(total_harga) as total, COUNT(*) as jumlah 
                  FROM transaksi 
                  WHERE MONTH($date_col) = '$month' AND YEAR($date_col) = '$year'";

$result_revenue = @mysqli_query($conn, $query_revenue);
if ($result_revenue) {
    $row = mysqli_fetch_assoc($result_revenue);
    $revenue = $row['total'] ?? 0;
    $total_transaksi = $row['jumlah'] ?? 0;
}

// Data untuk Grafik Bulanan (Trend setahun)
$sql_chart = "SELECT MONTH($date_col) as bulan, SUM(total_harga) as total 
              FROM transaksi 
              WHERE YEAR($date_col) = '$year'
              GROUP BY MONTH($date_col)";
$result_chart = @mysqli_query($conn, $sql_chart);

// Init 12 bulan
$chart_labels = array_values($bulan_indo); // Jan - Des
$chart_data = array_fill(0, 12, 0); // Index 0-11

if ($result_chart) {
    while ($row = mysqli_fetch_assoc($result_chart)) {
        $m = (int)$row['bulan']; // 1-12
        if ($m >= 1 && $m <= 12) {
            $chart_data[$m - 1] = $row['total'];
        }
    }
}
$chart_data_values = array_values($chart_data);

// 2. PRODUK TERLARIS (New Feature)
// Join transaksi -> transaksi_detail -> obat
$query_best_selling = "SELECT o.nama_obat, o.kategori, SUM(td.jumlah) as total_terjual, SUM(td.subtotal) as total_pendapatan
                       FROM transaksi_detail td
                       JOIN transaksi t ON td.id_transaksi = t.id_transaksi
                       JOIN obat o ON td.id_obat = o.id_obat
                       WHERE MONTH(t.$date_col) = '$month' AND YEAR(t.$date_col) = '$year'
                       GROUP BY td.id_obat
                       ORDER BY total_terjual DESC
                       LIMIT 5";

$result_best_selling = @mysqli_query($conn, $query_best_selling);

// 3. STOK MENIPIS
$query_stok = "SELECT nama_obat, stok, harga FROM obat WHERE stok <= 10 ORDER BY stok ASC LIMIT 5";
$result_stok = mysqli_query($conn, $query_stok);

// 4. FEEDBACK PENGGUNA
$query_feedback = "SELECT nama_user, bintang, komentar, tanggal_rating FROM rating ORDER BY tanggal_rating DESC LIMIT 5";
$result_feedback = mysqli_query($conn, $query_feedback);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Bulanan - <?php echo $bulan_indo[$month] . ' ' . $year; ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 15px;
            border-left: 4px solid #10b981;
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            background: #f0fdf4;
            color: #10b981;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .stat-info h3 { margin: 0; font-size: 1.5rem; color: #1f2937; }
        .stat-info p { margin: 0; color: #6b7280; font-size: 0.9rem; }
        
        .charts-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .report-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 900px) {
            .report-grid { grid-template-columns: 1fr; }
        }

        .report-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            height: 100%;
        }
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 1px solid #f3f4f6;
            padding-bottom: 10px;
        }
        .stock-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .stock-critical { background: #fef2f2; color: #ef4444; }
        .stock-warning { background: #fffbeb; color: #f59e0b; }

        .filter-bar {
            background: white;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .filter-form { display: flex; gap: 10px; }
        
        /* Simple Download Button Style */
        .btn-download-wrapper {
            position: relative;
        }
        .btn-download {
            background: #ffffff;
            color: #374151;
            padding: 8px 16px;
            border-radius: 6px;
            border: 1px solid #d1d5db;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.9rem;
        }
        .btn-download:hover {
            background: #f3f4f6;
            border-color: #9ca3af;
            color: #111827;
        }
        .btn-download i { color: #dc2626; /* PDF Red icon */ }
        
        .btn-download.loading {
            opacity: 0.7;
            cursor: not-allowed;
            background: #f3f4f6;
        }

        /* Helpers for PDF Generation */
        .pdf-header {
            display: none;
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        .pdf-header h2 { 
            margin: 0 0 5px 0; 
            color: #000; 
            font-size: 20px;
        }
        .pdf-header p { 
            margin: 0; 
            color: #555;
            font-size: 14px; 
        }

        /* PDF Specific Fixes */
        .html2pdf__page-break {
            height: 0;
            page-break-after: always;
            margin: 0;
            border: none;
        }

        /* Report Table Styling for PDF - Clean & Simple */
        .table-pdf {
            width: 100%;
            border-collapse: collapse;
            font-size: 10pt; /* Slightly larger for readability */
            margin-bottom: 20px;
        }
        .table-pdf th {
            border-bottom: 2px solid #333; /* Only bottom border for header */
            border-top: 1px solid #ddd;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            color: #000;
        }
        .table-pdf td {
            padding: 10px 8px;
            border-bottom: 1px solid #eee; /* Light horizontal lines only */
            color: #333;
        }
        
        /* Signature Section for PDF */
        .pdf-footer {
            display: none;
            margin-top: 40px;
            page-break-inside: avoid;
        }
        .signature-box {
            float: right;
            width: 200px;
            text-align: center;
        }
        .signature-line {
            margin-top: 70px;
            border-bottom: 1px solid #000;
        }

        /* Print Styling (Fallback) */
        @media print {
            .admin-sidebar, .card-actions, .filter-bar, .btn-download { display: none !important; }
            .main-content { margin-left: 0 !important; width: 100% !important; padding: 0cm !important; }
            body { background: white; font-size: 11pt; -webkit-print-color-adjust: exact; }
            .stat-card, .charts-section, .report-section { 
                box-shadow: none !important; 
                border: none !important; /* Remove borders for cleaner look */
                break-inside: avoid;
            }
        }

        /* Compact Mode for Single Page PDF */
        /* FORCE FLEXBOX instead of Grid for PDF stability */
        .compact-mode .stats-grid {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
        }
        .compact-mode .stat-card {
            flex: 1; /* Equal width */
            padding: 8px 10px;
            box-shadow: none;
            border: 1px solid #ddd;
            display: flex; /* Keep internal flex */
            align-items: center;
        }
        .compact-mode .stat-icon {
            width: 30px;
            height: 30px;
            font-size: 0.9rem;
            margin-right: 8px;
        }
        
        .compact-mode .charts-section {
            padding: 5px;
            margin-bottom: 10px;
            box-shadow: none;
            border: 1px solid #ddd;
        }
        
        /* Change Report Grid to Flex Row for side-by-side */
        .compact-mode .report-grid {
            display: flex;
            gap: 15px;
            margin-bottom: 10px;
        }
        .compact-mode .report-section {
            flex: 1; /* Side by side equal width */
            width: 48%; /* Force width */
            padding: 5px;
            box-shadow: none;
            border: 1px solid #ddd;
        }
        
        .compact-mode .table-pdf {
             margin-bottom: 5px;
        }
        .compact-mode .table-pdf th,
        .compact-mode .table-pdf td {
            padding: 3px 5px; 
            font-size: 8pt;
            border-bottom: 1px solid #eee;
        }
        
        /* Feedback Grid - 2 columns flex */
        .compact-mode .feedback-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .compact-mode .feedback-grid > div {
            flex: 1 1 30%; /* 3 per row roughly */
            min-width: 150px;
            border: 1px solid #eee;
            background: none !important;
        }

        .compact-mode .pdf-header {
            margin-bottom: 5px;
            padding: 2px;
        }
        
        /* Layout Fixes */
        .compact-mode .main-content {
            width: 100% !important;
            max-width: 100% !important;
        }
    </style>
</head>
<body>
    
    <?php include 'sidebar.php'; ?>

    <div class="main-content" id="mainContent">
        <!-- Special Header for PDF only - Simple & Text Based -->
        <div class="pdf-header" id="pdfHeader" style="text-align: center; margin-bottom: 30px;">
            <h2 style="margin: 0 0 5px 0; font-size: 22px; color: #000; letter-spacing: 1px; text-transform: uppercase;">Apotek Sehat</h2>
            <p style="margin: 0 0 20px 0; font-size: 12px; color: #555;">Jl. Raya Kesehatan No. 123, Jakarta | Telp: (021) 123-4567</p>
            <hr style="border: none; border-top: 2px solid #000; margin-bottom: 2px;">
            <hr style="border: none; border-top: 1px solid #000; margin-top: 0; margin-bottom: 20px;">
            
            <h3 style="margin: 0; font-size: 16px; font-weight: bold; text-decoration: underline;">LAPORAN BULANAN</h3>
            <p style="margin: 5px 0 0; font-size: 12px;">Periode: <?php echo $bulan_indo[$month] . ' ' . $year; ?></p>
        </div>

        <header class="main-header" data-html2pdf-ignore="true">
            <div class="header-left">
                <h1><i class="fas fa-chart-line"></i> Laporan Bulanan</h1>
                <p>Ringkasan kinerja toko untuk <?php echo $bulan_indo[$month] . ' ' . $year; ?></p>
            </div>
            <div class="header-right">
                <button onclick="downloadPDF()" class="btn-download" id="btnDownload">
                    <i class="fas fa-file-pdf"></i> Download PDF
                </button>
            </div>
        </header>

        <div class="filter-bar" data-html2pdf-ignore="true">
            <form method="GET" class="filter-form">
                <select name="bulan" class="form-control" style="padding: 8px; border-radius: 6px; border: 1px solid #ddd;">
                    <?php foreach($bulan_indo as $k => $v): ?>
                        <option value="<?php echo $k; ?>" <?php echo $k == $month ? 'selected' : ''; ?>><?php echo $v; ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="tahun" class="form-control" style="padding: 8px; border-radius: 6px; border: 1px solid #ddd;">
                    <?php for($t = 2023; $t <= date('Y'); $t++): ?>
                        <option value="<?php echo $t; ?>" <?php echo $t == $year ? 'selected' : ''; ?>><?php echo $t; ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit" style="padding: 8px 15px; background: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer;">
                    <i class="fas fa-filter"></i> Tampilkan
                </button>
            </form>
        </div>

        <div class="content-wrapper" style="grid-template-columns: 1fr;">
            
            <!-- 1. KARTU STATISTIK -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                    <div class="stat-info">
                        <p>Total Pendapatan (<?php echo $bulan_indo[$month]; ?>)</p>
                        <h3>Rp <?php echo number_format($revenue, 0, ',', '.'); ?></h3>
                    </div>
                </div>
                <div class="stat-card" style="border-left-color: #3b82f6;">
                    <div class="stat-icon" style="background: #eff6ff; color: #3b82f6;"><i class="fas fa-shopping-cart"></i></div>
                    <div class="stat-info">
                        <p>Total Transaksi</p>
                        <h3><?php echo number_format($total_transaksi); ?></h3>
                    </div>
                </div>
                
                <?php 
                // Kalkulasi rata-rata
                $avg_transaksi = $total_transaksi > 0 ? $revenue / $total_transaksi : 0;
                ?>
                <div class="stat-card" style="border-left-color: #8b5cf6;">
                    <div class="stat-icon" style="background: #f5f3ff; color: #8b5cf6;"><i class="fas fa-chart-pie"></i></div>
                    <div class="stat-info">
                        <p>Rata-rata Transaksi</p>
                        <h3>Rp <?php echo number_format($avg_transaksi, 0, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>

            <!-- 2. GRAFIK PENJUALAN -->
            <div class="charts-section">
                <div class="report-header">
                    <h3><i class="fas fa-chart-area" style="color: #10b981;"></i> Tren Penjualan Bulanan (Tahun <?php echo $year; ?>)</h3>
                </div>
                <div style="position: relative; height: 300px; width: 100%;">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>

            <!-- 3. BEST SELLING & STOCK GRID -->
             <div class="report-grid">
                 <!-- 3A. PRODUK TERLARIS -->
                 <div class="report-section">
                    <div class="report-header">
                        <h3><i class="fas fa-trophy" style="color: #eab308;"></i> Top 5 Produk (<?php echo $bulan_indo[$month]; ?>)</h3>
                    </div>
                    <?php if($result_best_selling && mysqli_num_rows($result_best_selling) > 0): ?>
                        <table class="table-pdf">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th style="text-align: center;">Terjual</th>
                                    <th style="text-align: right;">Pendapatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank = 1; while($item = mysqli_fetch_assoc($result_best_selling)): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['nama_obat']); ?></strong><br>
                                        <small style="color: #6b7280;"><?php echo htmlspecialchars($item['kategori']); ?></small>
                                    </td>
                                    <td style="text-align: center; font-weight: bold;"><?php echo $item['total_terjual']; ?></td>
                                    <td style="text-align: right;">Rp <?php echo number_format($item['total_pendapatan'], 0, ',', '.'); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div style="text-align: center; padding: 30px; color: #9ca3af;">
                            <i class="fas fa-box-open" style="font-size: 2em; margin-bottom: 10px;"></i>
                            <p>Belum ada data penjualan bulan ini.</p>
                        </div>
                    <?php endif; ?>
                 </div>

                 <!-- 3B. STOK MENIPIS -->
                <div class="report-section">
                    <div class="report-header">
                        <h3><i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i> Stok Menipis</h3>
                    </div>
                    <?php if(mysqli_num_rows($result_stok) > 0): ?>
                        <table class="table" style="width: 100%;">
                            <thead>
                                <tr style="text-align: left; border-bottom: 2px solid #f3f4f6;">
                                    <th style="padding: 10px;">Nama Produk</th>
                                    <th style="padding: 10px;">Sisa</th>
                                    <th style="padding: 10px;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($item = mysqli_fetch_assoc($result_stok)): ?>
                                <tr style="border-bottom: 1px solid #f9fafb;">
                                    <td style="padding: 10px;"><?php echo htmlspecialchars($item['nama_obat']); ?></td>
                                    <td style="padding: 10px; font-weight: bold;"><?php echo $item['stok']; ?></td>
                                    <td style="padding: 10px;">
                                        <span class="stock-badge <?php echo $item['stok'] == 0 ? 'stock-critical' : 'stock-warning'; ?>">
                                            <?php echo $item['stok'] == 0 ? 'Habis' : 'Kritis'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="text-align: center; color: #9ca3af; padding: 20px;">Stok aman. Tidak ada produk kritis.</p>
                    <?php endif; ?>
                </div>
             </div>
            
            <!-- 4. FEEDBACK (Full Width) -->
            <div class="report-section" style="margin-bottom: 30px;">
                <div class="report-header">
                    <h3><i class="fas fa-star" style="color: #f59e0b;"></i> Feedback Terbaru</h3>
                </div>
                <div class="feedback-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px;">
                    <?php if(mysqli_num_rows($result_feedback) > 0): ?>
                        <?php while($feed = mysqli_fetch_assoc($result_feedback)): ?>
                        <div style="padding: 15px; border: 1px solid #f3f4f6; border-radius: 8px; background: #fafafa;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <strong style="color: #374151;"><?php echo htmlspecialchars($feed['nama_user']); ?></strong>
                                <span style="color: #f59e0b; font-size: 0.9em;">
                                    <?php for($i=0;$i<5;$i++) echo ($i < $feed['bintang']) ? '★' : '☆'; ?>
                                </span>
                            </div>
                            <p style="margin: 0 0 10px 0; color: #6b7280; font-size: 0.9rem; font-style: italic;">"<?php echo htmlspecialchars($feed['komentar']); ?>"</p>
                            <small style="color: #d1d5db; font-size: 0.75rem;"><i class="far fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($feed['tanggal_rating'])); ?></small>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: #9ca3af; padding: 20px; width: 100%;">Belum ada ulasan baru.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>
        
        <!-- Signature Section -->
        <div class="pdf-footer" id="pdfFooter">
            <div class="signature-box">
                <p>Jakarta, <?php echo date('d') . ' ' . $bulan_indo[(int)date('m')] . ' ' . date('Y'); ?></p>
                <div style="margin-bottom: 5px;">Mengetahui,</div>
                <div>Kepala Apotek</div>
                <div class="signature-line"></div>
                <div style="margin-top: 5px;">( ........................... )</div>
            </div>
        </div>

    </div>

    <script src="script.js"></script>
    <script>
        // PDF Download Logic
        function downloadPDF() {
            const element = document.getElementById('mainContent');
            const btn = document.getElementById('btnDownload');
            const pdfHeader = document.getElementById('pdfHeader');
            const pdfFooter = document.getElementById('pdfFooter');
            const originalText = btn.innerHTML;
            
            // 1. Enter Compact PDF Mode
            element.classList.add('compact-mode');
            
            // 2. Loading State
            btn.classList.add('loading');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses PDF...';
            
            // 3. Show custom header & footer
            pdfHeader.style.display = 'block';
            pdfFooter.style.display = 'block';
            
            // 4. Options for html2pdf
            const opt = {
                margin:       [10, 10, 10, 10], // Tight margins
                filename:     'Laporan_Bulanan_<?php echo $bulan_indo[$month] . '_' . $year; ?>.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { 
                    scale: 2, 
                    useCORS: true,
                    logging: false,
                    scrollY: 0
                },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' },
                pagebreak:    { mode: ['avoid-all', 'css', 'legacy'] }
            };

            // 5. Generate with delay
            setTimeout(() => {
                html2pdf().set(opt).from(element).save().then(function(){
                    // 6. Restore State
                    pdfHeader.style.display = 'none';
                    pdfFooter.style.display = 'none';
                    element.classList.remove('compact-mode');
                    btn.classList.remove('loading');
                    btn.innerHTML = originalText;
                }).catch(function(error) {
                    element.classList.remove('compact-mode');
                    console.error('PDF Error:', error);
                    alert('Gagal mengunduh PDF.');
                    btn.classList.remove('loading');
                    btn.innerHTML = originalText;
                });
            }, 500);
        }

        // Inisialisasi Chart.js
        const ctx = document.getElementById('salesChart').getContext('2d');
        
        // Gradient
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(59, 130, 246, 0.2)'); // Blue tint
        gradient.addColorStop(1, 'rgba(59, 130, 246, 0.0)');

        new Chart(ctx, {
            type: 'bar', // Changed to Bar for monthly comparison
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'Pendapatan',
                    data: <?php echo json_encode($chart_data_values); ?>,
                    borderColor: '#3b82f6',
                    backgroundColor: gradient,
                    borderWidth: 2,
                    borderRadius: 4,
                    barPercentage: 0.6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: true
                    }
                },
                scales: {
                    y: { 
                        beginAtZero: true,
                        grid: { borderDash: [2, 4], color: '#f3f4f6' },
                        ticks: {
                            font: { size: 10 },
                            callback: function(value, index, values) {
                                return (value / 1000) + 'k';
                            }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 9 } }
                    }
                }
            }
        });
    </script>
</body>
</html>
