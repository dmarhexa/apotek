<?php
// screen/admin/laporan_bulanan.php
require_once '../../config.php';
require_once '../../includes/auth.php';

// Cek login admin
if (!isset($_SESSION['pegawai_id'])) {
    header("Location: " . $base_url . "/auth/login.php");
    exit();
}

// Set default month/year
$month = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('m');
$year = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');

// Validasi
$month = max(1, min(12, $month));
$year = max(2020, min(date('Y'), $year));

// Array nama bulan
$bulan_indo = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

// 1. STATISTIK PENJUALAN
$revenue = 0;
$total_transaksi = 0;
$avg_transaksi = 0;

// Deteksi kolom tanggal
$date_col = 'tanggal_transaksi';
$sql_check = "SHOW COLUMNS FROM transaksi";
$check = mysqli_query($conn, $sql_check);
$columns = [];
if ($check) {
    while($row = mysqli_fetch_assoc($check)) {
        $columns[] = $row['Field'];
    }
    
    if (in_array('tanggal_transaksi', $columns)) {
        $date_col = 'tanggal_transaksi';
    } elseif (in_array('created_at', $columns)) {
        $date_col = 'created_at';
    }
}

// Query Total Pendapatan Bulan Ini
$query_revenue = "SELECT COALESCE(SUM(total_harga), 0) as total, COUNT(*) as jumlah 
                  FROM transaksi 
                  WHERE MONTH($date_col) = ? AND YEAR($date_col) = ?";
                  
$stmt = mysqli_prepare($conn, $query_revenue);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ii", $month, $year);
    mysqli_stmt_execute($stmt);
    $result_revenue = mysqli_stmt_get_result($stmt);
    
    if ($result_revenue && $row = mysqli_fetch_assoc($result_revenue)) {
        $revenue = $row['total'] ?? 0;
        $total_transaksi = $row['jumlah'] ?? 0;
        $avg_transaksi = $total_transaksi > 0 ? $revenue / $total_transaksi : 0;
    }
    mysqli_stmt_close($stmt);
}

// GRAFIK TREND LINE 30 HARI - Data untuk chart
$chart_labels = [];
$chart_data = [];

for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('d M', strtotime($date));
    
    $query = "SELECT COUNT(*) as count FROM transaksi WHERE DATE($date_col) = ?";
    $day_stmt = mysqli_prepare($conn, $query);
    if ($day_stmt) {
        mysqli_stmt_bind_param($day_stmt, "s", $date);
        mysqli_stmt_execute($day_stmt);
        $result = mysqli_stmt_get_result($day_stmt);
        
        if ($result && $data = mysqli_fetch_assoc($result)) {
            $chart_data[] = $data['count'] ?? 0;
        } else {
            $chart_data[] = 0;
        }
        mysqli_stmt_close($day_stmt);
    } else {
        $chart_data[] = 0;
    }
}

// 2. PRODUK TERLARIS
$query_best_selling = "SELECT 
    o.nama_obat, 
    o.kategori, 
    COALESCE(SUM(td.jumlah), 0) as total_terjual, 
    COALESCE(SUM(td.subtotal), 0) as total_pendapatan
    FROM obat o
    LEFT JOIN transaksi_detail td ON o.id_obat = td.id_obat
    LEFT JOIN transaksi t ON td.id_transaksi = t.id_transaksi 
        AND MONTH(t.$date_col) = ? AND YEAR(t.$date_col) = ?
    GROUP BY o.id_obat
    ORDER BY total_terjual DESC
    LIMIT 5";

$stmt_best = mysqli_prepare($conn, $query_best_selling);
$result_best_selling = null;
if ($stmt_best) {
    mysqli_stmt_bind_param($stmt_best, "ii", $month, $year);
    mysqli_stmt_execute($stmt_best);
    $result_best_selling = mysqli_stmt_get_result($stmt_best);
}

// 3. STOK MENIPIS
$query_stok = "SELECT nama_obat, stok, harga, kategori FROM obat WHERE stok <= 10 ORDER BY stok ASC LIMIT 5";
$result_stok = mysqli_query($conn, $query_stok);

// 4. FEEDBACK PENGGUNA
$query_feedback = "SELECT nama_user, bintang, komentar, tanggal_rating FROM rating ORDER BY tanggal_rating DESC LIMIT 5";
$result_feedback = mysqli_query($conn, $query_feedback);

// Total obat hampir habis untuk notifikasi
$query_stok_kritis = "SELECT COUNT(*) as total FROM obat WHERE stok <= 10";
$result_kritis = mysqli_query($conn, $query_stok_kritis);
$stok_kritis = $result_kritis ? mysqli_fetch_assoc($result_kritis)['total'] : 0;
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
        /* ==============================
           RESET DAN BASE STYLES
           ============================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f9fafb;
            color: #333;
            line-height: 1.5;
        }
        
        /* ==============================
           SIDEBAR STYLING
           ============================== */
        .admin-sidebar {
            width: 250px;
            background: linear-gradient(180deg, #1e3a8a 0%, #1e40af 100%);
            color: white;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            overflow-y: auto;
        }
        
        /* ==============================
           MAIN CONTENT AREA (BROWSER)
           ============================== */
        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
        }
        
        /* ==============================
        WEB HEADER (hanya untuk browser)
        ============================== */
        .web-header {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .web-header .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .web-header .header-left h1 {
            font-size: 1.8rem;
            color: #1f2937;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .web-header .header-left p {
            color: #6b7280;
            font-size: 0.95rem;
            margin: 0;
        }

        .web-header .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* ==============================
           FILTER BAR (hanya untuk browser)
           ============================== */
        .filter-bar {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .filter-form {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .form-control {
            padding: 10px 15px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.95rem;
            color: #374151;
            background: white;
            min-width: 140px;
            transition: all 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        
        .btn-filter {
            padding: 10px 20px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        
        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
        }
        
        /* ==============================
           STATS CARDS (BROWSER)
           ============================== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 20px;
            border-left: 4px solid;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }
        
        .stat-card:nth-child(1) { border-left-color: #10b981; }
        .stat-card:nth-child(2) { border-left-color: #3b82f6; }
        .stat-card:nth-child(3) { border-left-color: #8b5cf6; }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .stat-card:nth-child(1) .stat-icon {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #10b981;
        }
        
        .stat-card:nth-child(2) .stat-icon {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #3b82f6;
        }
        
        .stat-card:nth-child(3) .stat-icon {
            background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
            color: #8b5cf6;
        }
        
        .stat-info h3 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 5px;
        }
        
        .stat-info p {
            font-size: 0.9rem;
            color: #6b7280;
            margin: 0;
        }
        
        /* ==============================
           CHART SECTION (BROWSER)
           ============================== */
        .chart-container {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .chart-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f3f4f6;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .chart-title i {
            color: #10b981;
        }
        
        .chart-wrapper {
            height: 300px;
            position: relative;
        }
        
        /* ==============================
           REPORT GRID (BROWSER)
           ============================== */
        .report-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 1100px) {
            .report-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* ==============================
           REPORT SECTIONS (BROWSER)
           ============================== */
        .report-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f3f4f6;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            font-size: 1.1rem;
        }
        
        /* ==============================
           TABLES (BROWSER)
           ============================== */
        .table-report {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        
        .table-report thead {
            background: #f9fafb;
        }
        
        .table-report th {
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
            font-size: 0.9rem;
        }
        
        .table-report td {
            padding: 15px 12px;
            border-bottom: 1px solid #f3f4f6;
            color: #4b5563;
            font-size: 0.9rem;
        }
        
        .table-report tbody tr:hover {
            background: #f9fafb;
        }
        
        .table-report tbody tr:last-child td {
            border-bottom: none;
        }
        
        /* ==============================
           BADGES (BROWSER)
           ============================== */
        .badge {
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        .badge-warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border: 1px solid #fcd34d;
        }
        
        /* ==============================
           FEEDBACK CARDS (BROWSER)
           ============================== */
        .feedback-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }
        
        .feedback-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .feedback-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .feedback-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .feedback-user {
            font-weight: 600;
            font-size: 1rem;
            color: #1f2937;
        }
        
        .feedback-stars {
            color: #f59e0b;
            font-size: 0.9rem;
        }
        
        .feedback-comment {
            font-size: 0.95rem;
            color: #4b5563;
            font-style: italic;
            line-height: 1.6;
            margin-bottom: 15px;
            padding: 15px;
            background: #fafafa;
            border-radius: 8px;
            border-left: 4px solid #e5e7eb;
        }
        
        .feedback-date {
            font-size: 0.85rem;
            color: #9ca3af;
            text-align: right;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 5px;
        }
        
        /* ==============================
           EMPTY STATE
           ============================== */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #9ca3af;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #d1d5db;
            display: block;
        }
        
        .empty-state p {
            font-size: 1rem;
            margin: 0;
        }
        
        /* ==============================
           DOWNLOAD BUTTON (BROWSER)
           ============================== */
        .btn-download {
            background: white;
            color: #374151;
            padding: 10px 20px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }
        
        .btn-download:hover {
            background: #f3f4f6;
            border-color: #9ca3af;
            color: #111827;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .btn-download i { 
            color: #dc2626;
        }
        
        .btn-download.loading {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }
        
        .btn-download.loading i {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* ==============================
           PDF SPECIFIC STYLES (HIDDEN IN BROWSER)
           ============================== */
        .pdf-header {
            display: none;
        }
        
        .pdf-header-content {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #000;
        }
        
        .company-name-pdf {
            font-size: 24px;
            font-weight: bold;
            color: #000;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .company-info-pdf {
            font-size: 11px;
            color: #555;
            margin-bottom: 15px;
            line-height: 1.4;
        }
        
        .report-title-pdf {
            font-size: 18px;
            font-weight: bold;
            color: #000;
            margin: 15px 0 8px 0;
            text-decoration: underline;
        }
        
        .period-pdf {
            font-size: 14px;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .print-date-pdf {
            font-size: 11px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
            margin-top: 12px;
        }
        
        /* PDF FOOTER */
        .pdf-footer {
            display: none;
        }
        
        .pdf-footer-content {
            margin-top: 50px;
            padding-top: 30px;
            border-top: 1px solid #000;
            text-align: right;
        }
        
        .signature-box {
            display: inline-block;
            text-align: center;
            width: 250px;
        }
        
        .signature-line {
            margin-top: 80px;
            border-bottom: 1px solid #000;
            width: 250px;
            display: inline-block;
        }
        
        /* ==============================
           PDF ONLY STYLES
           ============================== */
        .pdf-only {
            display: none;
        }
        
        .browser-only {
            display: block;
        }
        
        /* ==============================
           FOR PDF EXPORT - SPECIAL CLASSES
           ============================== */
        .pdf-export .admin-sidebar,
        .pdf-export .web-header,
        .pdf-export .filter-bar,
        .pdf-export .btn-download,
        .pdf-export .sidebar-toggle,
        .pdf-export [data-html2pdf-ignore="true"] {
            display: none !important;
        }
        
        .pdf-export .pdf-header {
            display: block !important;
        }
        
        .pdf-export .pdf-footer {
            display: block !important;
        }
        
        .pdf-export .main-content {
            margin-left: 0 !important;
            padding: 20px !important;
            width: 100% !important;
        }
        
        .pdf-export .stats-grid {
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 15px !important;
            margin-bottom: 25px !important;
        }
        
        .pdf-export .stat-card {
            border: 1px solid #ddd !important;
            padding: 15px !important;
            box-shadow: none !important;
            display: block !important;
            text-align: center;
            border-radius: 8px !important;
            background: #f9f9f9 !important;
        }
        
        .pdf-export .stat-icon {
            width: 40px !important;
            height: 40px !important;
            margin: 0 auto 10px !important;
            font-size: 1rem !important;
            border-radius: 8px !important;
        }
        
        .pdf-export .stat-info h3 {
            font-size: 16px !important;
            margin-bottom: 5px !important;
            color: #000 !important;
        }
        
        .pdf-export .stat-info p {
            font-size: 12px !important;
            color: #555 !important;
        }
        
        .pdf-export .chart-container {
            border: 1px solid #ddd !important;
            padding: 15px !important;
            margin-bottom: 25px !important;
            box-shadow: none !important;
            border-radius: 8px !important;
        }
        
        .pdf-export .chart-title {
            font-size: 16px !important;
            margin-bottom: 15px !important;
            color: #000 !important;
        }
        
        .pdf-export .chart-wrapper {
            height: 200px !important;
        }
        
        .pdf-export .report-grid {
            grid-template-columns: 1fr 1fr !important;
            gap: 20px !important;
            margin-bottom: 25px !important;
        }
        
        .pdf-export .report-section {
            border: 1px solid #ddd !important;
            padding: 15px !important;
            box-shadow: none !important;
            border-radius: 8px !important;
        }
        
        .pdf-export .table-report {
            font-size: 10px !important;
        }
        
        .pdf-export .table-report th {
            padding: 8px 6px !important;
            font-size: 10px !important;
            background: #f1f1f1 !important;
        }
        
        .pdf-export .table-report td {
            padding: 8px 6px !important;
            font-size: 10px !important;
        }
        
        .pdf-export .feedback-container {
            grid-template-columns: 1fr !important;
            gap: 10px !important;
        }
        
        .pdf-export .feedback-card {
            margin-bottom: 10px !important;
            padding: 10px !important;
            box-shadow: none !important;
            border: 1px solid #eee !important;
            border-radius: 6px !important;
        }
        
        .pdf-export .feedback-comment {
            font-size: 10px !important;
            padding: 8px !important;
            background: #fafafa !important;
        }
        
        /* ==============================
           RESPONSIVE STYLES
           ============================== */
        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            
            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .admin-sidebar.active {
                transform: translateX(0);
            }
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .web-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .filter-form {
                flex-direction: column;
                width: 100%;
            }
            
            .form-control {
                width: 100%;
            }
            
            .feedback-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <?php include 'sidebar.php'; ?>
    
    <!-- TOGGLE BUTTON FOR MOBILE -->
    <div class="sidebar-toggle" data-html2pdf-ignore="true" style="display: none; position: fixed; top: 15px; left: 15px; z-index: 1001; background: #1e40af; color: white; padding: 10px; border-radius: 5px; cursor: pointer;">
        <i class="fas fa-bars"></i>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content" id="mainContent">
        <!-- PDF HEADER (only for PDF) -->
        <div class="pdf-header" id="pdfHeader">
            <div class="pdf-header-content">
                <div class="company-name-pdf">APOTEK SEHAT</div>
                <div class="company-info-pdf">
                    Jl. Kesehatan No. 123, Jakarta Selatan<br>
                    Telp: (021) 1234-5678 | Email: info@apoteksehat.com<br>
                    Website: www.apoteksehat.com
                </div>
                <div class="report-title-pdf">LAPORAN BULANAN APOTEK</div>
                <div class="period-pdf">Periode: <?php echo strtoupper($bulan_indo[$month]) . ' ' . $year; ?></div>
                <div class="print-date-pdf">Dicetak pada: <?php echo date('d/m/Y H:i:s'); ?></div>
            </div>
        </div>

        <!-- WEB HEADER (only for browser) -->
        <header class="web-header browser-only" data-html2pdf-ignore="true">
            <div class="header-content">
                <div class="header-left">
                    <h1><i class="fas fa-chart-line"></i> Laporan Bulanan</h1>
                    <p>Ringkasan kinerja Apotek Sehat untuk <?php echo $bulan_indo[$month] . ' ' . $year; ?></p>
                </div>
                <div class="header-right">
                    <button onclick="downloadPDF()" class="btn-download" id="btnDownload">
                        <i class="fas fa-file-pdf"></i> Download PDF
                    </button>
                </div>
            </div>
        </header>

        <!-- FILTER BAR (only for browser) -->
        <div class="filter-bar browser-only" data-html2pdf-ignore="true">
            <form method="GET" class="filter-form">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <select name="bulan" class="form-control">
                        <?php foreach($bulan_indo as $k => $v): ?>
                            <option value="<?php echo $k; ?>" <?php echo $k == $month ? 'selected' : ''; ?>>
                                <?php echo $v; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="tahun" class="form-control">
                        <?php for($t = 2023; $t <= date('Y'); $t++): ?>
                            <option value="<?php echo $t; ?>" <?php echo $t == $year ? 'selected' : ''; ?>>
                                <?php echo $t; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-filter"></i> Filter Laporan
                    </button>
                </div>
            </form>
            <div style="font-size: 0.85rem; color: #6b7280;">
                <i class="fas fa-info-circle"></i> Pilih bulan dan tahun untuk melihat laporan
            </div>
        </div>

        <!-- STATISTICS CARDS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-info">
                    <p>Total Pendapatan (<?php echo $bulan_indo[$month]; ?>)</p>
                    <h3>Rp <?php echo number_format($revenue, 0, ',', '.'); ?></h3>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-info">
                    <p>Total Transaksi</p>
                    <h3><?php echo number_format($total_transaksi); ?></h3>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <div class="stat-info">
                    <p>Rata-rata per Transaksi</p>
                    <h3>Rp <?php echo number_format($avg_transaksi, 0, ',', '.'); ?></h3>
                </div>
            </div>
        </div>

        <!-- CHART SECTION - TREND LINE -->
        <div class="chart-container">
            <div class="chart-title">
                <i class="fas fa-chart-line"></i> Tren Transaksi 30 Hari Terakhir
            </div>
            <div class="chart-wrapper">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        <!-- REPORT GRID -->
        <div class="report-grid">
            <!-- Best Selling Products -->
            <div class="report-section">
                <div class="section-title">
                    <i class="fas fa-trophy" style="color: #eab308;"></i> Top 5 Produk Terlaris
                </div>
                <?php if($result_best_selling && mysqli_num_rows($result_best_selling) > 0): ?>
                    <table class="table-report">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="45%">PRODUK</th>
                                <th width="20%" style="text-align: center;">TERJUAL</th>
                                <th width="30%" style="text-align: right;">PENDAPATAN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $rank = 1; while($item = mysqli_fetch_assoc($result_best_selling)): ?>
                            <tr>
                                <td style="font-weight: bold;"><?php echo $rank; ?></td>
                                <td>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($item['nama_obat']); ?></div>
                                    <div style="font-size: 0.85rem; color: #6b7280;"><?php echo htmlspecialchars($item['kategori']); ?></div>
                                </td>
                                <td style="text-align: center; font-weight: bold; color: #10b981;">
                                    <?php echo number_format($item['total_terjual']); ?>
                                </td>
                                <td style="text-align: right; font-weight: bold;">
                                    Rp <?php echo number_format($item['total_pendapatan'], 0, ',', '.'); ?>
                                </td>
                            </tr>
                            <?php $rank++; endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-box-open"></i>
                        <p>Belum ada data penjualan untuk bulan ini</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Low Stock -->
            <div class="report-section">
                <div class="section-title">
                    <i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i> Stok Hampir Habis
                </div>
                <?php if($result_stok && mysqli_num_rows($result_stok) > 0): ?>
                    <table class="table-report">
                        <thead>
                            <tr>
                                <th width="45%">NAMA OBAT</th>
                                <th width="25%">KATEGORI</th>
                                <th width="15%" style="text-align: center;">STOK</th>
                                <th width="15%">STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($item = mysqli_fetch_assoc($result_stok)): 
                                $status = $item['stok'] == 0 ? 'Habis' : ($item['stok'] <= 5 ? 'Kritis' : 'Menipis');
                                $badge_class = $item['stok'] == 0 ? 'badge-danger' : ($item['stok'] <= 5 ? 'badge-danger' : 'badge-warning');
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['nama_obat']); ?></td>
                                <td style="color: #6b7280;"><?php echo htmlspecialchars($item['kategori']); ?></td>
                                <td style="text-align: center; font-weight: bold; color: <?php echo $item['stok'] <= 5 ? '#ef4444' : '#f59e0b'; ?>">
                                    <?php echo $item['stok']; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo $status; ?></span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle" style="color: #10b981;"></i>
                        <p>Semua stok dalam kondisi baik</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- CUSTOMER FEEDBACK -->
        <div class="report-section" style="margin-top: 20px;">
            <div class="section-title">
                <i class="fas fa-star" style="color: #f59e0b;"></i> Ulasan & Rating Terbaru
            </div>
            <div class="feedback-container">
                <?php if($result_feedback && mysqli_num_rows($result_feedback) > 0): ?>
                    <?php while($feed = mysqli_fetch_assoc($result_feedback)): ?>
                    <div class="feedback-card">
                        <div class="feedback-header">
                            <div class="feedback-user"><?php echo htmlspecialchars($feed['nama_user']); ?></div>
                            <div class="feedback-stars">
                                <?php for($i=1; $i<=5; $i++): ?>
                                    <?php if($i <= $feed['bintang']): ?>
                                        <i class="fas fa-star"></i>
                                    <?php else: ?>
                                        <i class="far fa-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="feedback-comment">
                            "<?php echo htmlspecialchars($feed['komentar']); ?>"
                        </div>
                        <div class="feedback-date">
                            <i class="far fa-calendar-alt"></i> 
                            <?php echo date('d M Y', strtotime($feed['tanggal_rating'])); ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="far fa-comment-dots"></i>
                        <p>Belum ada ulasan untuk ditampilkan</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- PDF FOOTER -->
        <div class="pdf-footer" id="pdfFooter">
            <div class="pdf-footer-content">
                <div class="signature-box">
                    <p>Jakarta, <?php echo date('d') . ' ' . $bulan_indo[(int)date('m')] . ' ' . date('Y'); ?></p>
                    <div style="margin-top: 30px;">Mengetahui,</div>
                    <div style="margin-top: 5px;">Kepala Apotek Sehat</div>
                    <div class="signature-line"></div>
                    <div style="margin-top: 5px; font-weight: bold;">[Salsabila]</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize Chart.js with trend line
        const ctx = document.getElementById('salesChart').getContext('2d');
        
        // Create gradient
        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(16, 185, 129, 0.25)');
        gradient.addColorStop(0.7, 'rgba(16, 185, 129, 0.1)');
        gradient.addColorStop(1, 'rgba(16, 185, 129, 0.0)');
        
        // Create chart
        const salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'Transaksi Harian',
                    data: <?php echo json_encode($chart_data); ?>,
                    borderColor: '#10b981',
                    backgroundColor: gradient,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: { size: 12 },
                        bodyFont: { size: 12 },
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                return `${context.raw} transaksi`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                size: 11
                            },
                            stepSize: 1
                        },
                        title: {
                            display: true,
                            text: 'Jumlah Transaksi',
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 10
                            },
                            maxRotation: 45
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'nearest'
                }
            }
        });

        // Mobile sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.querySelector('.sidebar-toggle');
            const sidebar = document.querySelector('.admin-sidebar');
            
            if (window.innerWidth <= 1024) {
                if (sidebarToggle) sidebarToggle.style.display = 'block';
                if (sidebar) sidebar.style.transform = 'translateX(-100%)';
            }
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    if (sidebar) {
                        sidebar.classList.toggle('active');
                    }
                });
            }
            
            // Close sidebar when clicking outside
            document.addEventListener('click', function(event) {
                if (window.innerWidth <= 1024 && sidebar && sidebar.classList.contains('active')) {
                    if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
                        sidebar.classList.remove('active');
                    }
                }
            });
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 1024) {
                    if (sidebarToggle) sidebarToggle.style.display = 'none';
                    if (sidebar) {
                        sidebar.style.transform = 'translateX(0)';
                        sidebar.classList.remove('active');
                    }
                } else {
                    if (sidebarToggle) sidebarToggle.style.display = 'block';
                }
            });
        });

        // PDF Download Function - FIXED VERSION
        function downloadPDF() {
            const element = document.getElementById('mainContent');
            const btn = document.getElementById('btnDownload');
            const pdfHeader = document.getElementById('pdfHeader');
            const pdfFooter = document.getElementById('pdfFooter');
            const originalText = btn.innerHTML;
            
            // Show loading
            btn.classList.add('loading');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Membuat PDF...';
            
            // Store original chart for restoration
            const chartCanvas = document.getElementById('salesChart');
            const chartContainer = chartCanvas.parentElement;
            const originalChartHTML = chartContainer.innerHTML;
            
            // Convert chart to image for PDF
            const chartImage = chartCanvas.toDataURL('image/png', 1.0);
            chartContainer.innerHTML = `<img src="${chartImage}" style="width:100%; height:200px; object-fit:contain;">`;
            
            // Show PDF elements and hide browser elements
            pdfHeader.style.display = 'block';
            pdfFooter.style.display = 'block';
            
            // Hide all browser-only elements
            const browserElements = document.querySelectorAll('.browser-only');
            browserElements.forEach(el => {
                el.style.display = 'none';
            });
            
            // Add PDF export class to body
            document.body.classList.add('pdf-export');
            
            // PDF options - FIXED for clean output
            const opt = {
                margin: [10, 10, 10, 10],
                filename: `Laporan_Apotek_<?php echo str_replace(' ', '_', $bulan_indo[$month]); ?>_<?php echo $year; ?>.pdf`,
                image: { 
                    type: 'jpeg', 
                    quality: 0.98 
                },
                html2canvas: { 
                    scale: 3, // Higher scale for better quality
                    useCORS: true,
                    logging: false,
                    scrollY: 0,
                    backgroundColor: '#ffffff',
                    letterRendering: true,
                    allowTaint: false,
                    foreignObjectRendering: false,
                    onclone: function(clonedDoc) {
                        // Apply PDF styles to cloned document
                        clonedDoc.body.classList.add('pdf-export');
                        
                        // Hide browser elements in cloned document
                        const clonedBrowserElements = clonedDoc.querySelectorAll('.browser-only');
                        clonedBrowserElements.forEach(el => {
                            el.style.display = 'none';
                        });
                        
                        // Show PDF elements in cloned document
                        const clonedPdfHeader = clonedDoc.getElementById('pdfHeader');
                        const clonedPdfFooter = clonedDoc.getElementById('pdfFooter');
                        if (clonedPdfHeader) clonedPdfHeader.style.display = 'block';
                        if (clonedPdfFooter) clonedPdfFooter.style.display = 'block';
                        
                        // Convert chart in cloned document
                        const clonedChartCanvas = clonedDoc.getElementById('salesChart');
                        if (clonedChartCanvas) {
                            const clonedChartContainer = clonedChartCanvas.parentElement;
                            clonedChartContainer.innerHTML = `<img src="${chartImage}" style="width:100%; height:200px; object-fit:contain;">`;
                        }
                    }
                },
                jsPDF: { 
                    unit: 'mm', 
                    format: 'a4', 
                    orientation: 'portrait',
                    compress: true
                },
                pagebreak: { 
                    mode: ['avoid-all', 'css', 'legacy'],
                    avoid: '.report-section, .stat-card, .feedback-card'
                }
            };

            // Delay to ensure DOM updates
            setTimeout(() => {
                html2pdf()
                    .set(opt)
                    .from(element)
                    .save()
                    .then(() => {
                        // Success - restore original state
                        restoreOriginalState();
                        showNotification('PDF berhasil diunduh!', 'success');
                    })
                    .catch(error => {
                        console.error('PDF Error:', error);
                        restoreOriginalState();
                        showNotification('Gagal membuat PDF. Silakan coba lagi.', 'error');
                    });
            }, 1000);
            
            // Function to restore original state
            function restoreOriginalState() {
                // Restore chart
                chartContainer.innerHTML = originalChartHTML;
                
                // Hide PDF elements
                pdfHeader.style.display = 'none';
                pdfFooter.style.display = 'none';
                
                // Show browser elements
                browserElements.forEach(el => {
                    el.style.display = '';
                });
                
                // Remove PDF export class
                document.body.classList.remove('pdf-export');
                
                // Restore button
                btn.classList.remove('loading');
                btn.innerHTML = originalText;
                
                // Reinitialize chart
                setTimeout(() => {
                    if (window.salesChart) {
                        window.salesChart.destroy();
                    }
                    window.salesChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: <?php echo json_encode($chart_labels); ?>,
                            datasets: [{
                                label: 'Transaksi Harian',
                                data: <?php echo json_encode($chart_data); ?>,
                                borderColor: '#10b981',
                                backgroundColor: gradient,
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4,
                                pointBackgroundColor: '#10b981',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 2,
                                pointRadius: 4,
                                pointHoverRadius: 6
                            }]
                        },
                        options: salesChart.options
                    });
                }, 100);
            }
        }

        // Notification function
        function showNotification(message, type) {
            // Remove existing notification
            const existing = document.querySelector('.custom-notification');
            if (existing) existing.remove();
            
            // Create notification
            const notification = document.createElement('div');
            notification.className = 'custom-notification';
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 25px;
                border-radius: 10px;
                color: white;
                font-weight: 500;
                z-index: 9999;
                animation: slideIn 0.3s ease;
                background: ${type === 'success' ? '#10b981' : '#ef4444'};
                box-shadow: 0 6px 20px rgba(0,0,0,0.15);
                font-size: 14px;
                display: flex;
                align-items: center;
                gap: 10px;
            `;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                ${message}
            `;
            document.body.appendChild(notification);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Add animation styles
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>