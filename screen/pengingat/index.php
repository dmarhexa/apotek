<?php
require_once '../../config.php';
require_once '../../includes/auth.php'; 

// Check if user is logged in
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengingat Minum Obat - Apotek Sehat</title>
    <!-- Use Inter font to match Dashboard -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            /* Theme Colors based on Sidebar (Green/Emerald) */
            --primary: #10b981; /* Emerald 500 */
            --primary-dark: #059669; /* Emerald 600 */
            --primary-light: #d1fae5; /* Emerald 100 */
            --secondary: #3b82f6; /* Blue 500 for secondary actions */
            
            --bg-body: #f3f4f6;
            --bg-card: #ffffff;
            --text-main: #1f2937; /* Gray 800 */
            --text-muted: #6b7280; /* Gray 500 */
            
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --radius: 12px;
            
            /* Sidebar Width variable */
            --sidebar-width: 280px; 
        }

        body {
            font-family: 'Inter', sans-serif; /* Consistent font */
            background-color: var(--bg-body);
            color: var(--text-main);
            margin: 0;
            padding: 0;
            display: flex;
            min-height: 100vh;
        }

        /* Layout adjustment for sidebar */
        .main-content {
            flex: 1;
            padding: 2rem;
            margin-left: var(--sidebar-width); /* Correct sidebar width */
            max-width: 1200px;
            margin-right: auto;
            width: calc(100% - var(--sidebar-width));
        }

        h1, h2, h3, h4 {
            font-weight: 600;
            color: #111827;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .grid-container {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 1rem;
            }
            /* Assume sidebar becomes hidden or drawer on mobile ultimately */
        }

        /* Header Style matching Dashboard */
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
        }

        .header-title h1 {
            font-size: 1.5rem;
            margin: 0;
            color: var(--text-main);
        }
        
        .header-desc {
            margin-top: 0.25rem;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        /* Grid Layout */
        .grid-container {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 1.5rem;
        }

        /* Cards */
        .card {
            background: var(--bg-card);
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .card-icon-bg {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .blue-icon { background: #eff6ff; color: #3b82f6; }
        .green-icon { background: #ecfdf5; color: #10b981; }
        .purple-icon { background: #f3e8ff; color: #a855f7; }

        /* Forms */
        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-main);
        }

        input[type="text"],
        input[type="time"],
        textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.9rem;
            transition: all 0.2s;
            box-sizing: border-box;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1); /* Emerald ring */
        }

        /* Time Inputs */
        .time-input-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .btn-remove-time {
            color: var(--text-muted);
            background: none;
            border: none;
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .btn-remove-time:hover {
            color: var(--danger);
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.25rem;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            font-size: 0.9rem;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
        }

        .btn-primary:hover {
            opacity: 0.95;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3);
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #4b5563;
        }
        
        .btn-secondary:hover {
            background: #e5e7eb;
            color: #1f2937;
        }
        
        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
        }

        /* Reminder List */
        .reminder-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .reminder-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 1.25rem;
            border-radius: var(--radius);
            border: 1px solid #e5e7eb;
            transition: border-color 0.2s;
        }
        
        .reminder-item:hover {
            border-color: var(--primary-light);
        }

        .reminder-item.taken {
            background: #f0fdf4; /* Light green bg */
            border-color: #bbf7d0;
        }

        .reminder-time-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: var(--primary-light);
            color: var(--primary-dark);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            min-width: 60px;
            margin-right: 1rem;
        }
        
        .reminder-content {
            flex: 1;
        }
        
        .reminder-content h4 {
            margin: 0 0 0.25rem 0;
            font-size: 1rem;
        }
        
        .reminder-content p {
            margin: 0;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .status-badge-taken {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            background: var(--success);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* Table */
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f9fafb;
            text-align: left;
            padding: 0.75rem 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--text-muted);
            border-bottom: 2px solid #e5e7eb;
        }
        
        td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e5e7eb;
            font-size: 0.9rem;
        }
        
        tr:last-child td {
            border-bottom: none;
        }

        /* Toast */
        .toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transform: translateY(150%);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 1000;
            border-left: 4px solid var(--success);
        }
        
        .toast.show {
            transform: translateY(0);
        }
    </style>
</head>
<body>

    <!-- Include Sidebar -->
    <?php include '../../includes/sidebar.php'; ?>

    <div class="main-content">
        <!-- Header -->
        <div class="header-section">
            <div>
                <div class="header-title">
                    <h1>Pengingat Obat</h1>
                </div>
                <div class="header-desc">Kelola jadwal minum obat Anda dengan mudah dan tepat waktu.</div>
            </div>
            <button class="btn btn-secondary" onclick="window.scrollTo({top: document.body.scrollHeight, behavior: 'smooth'})">
                <i class="fa-solid fa-history"></i> Riwayat
            </button>
        </div>

        <div class="grid-container">
            <!-- Left Column: Active Reminders -->
            <div>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <div class="card-icon-bg green-icon">
                                <i class="fa-regular fa-bell"></i>
                            </div>
                            Jadwal Hari Ini
                        </div>
                        <span style="font-size:0.85rem; color:var(--text-muted); background: #f3f4f6; padding: 4px 10px; border-radius: 20px;" id="currentDate"></span>
                    </div>
                    
                    <div id="activeRemindersList" class="reminder-list">
                        <!-- Content loaded via JS -->
                        <div style="text-align:center; padding: 3rem; color: var(--text-muted);">
                            <i class="fa-solid fa-spinner fa-spin"></i> Memuat jadwal...
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Add Form -->
            <div>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <div class="card-icon-bg blue-icon">
                                <i class="fa-solid fa-plus"></i>
                            </div>
                            Tambah Pengingat
                        </div>
                    </div>
                    <form id="addReminderForm">
                        <div class="form-group">
                            <label>Nama Obat</label>
                            <input type="text" name="nama_obat" placeholder="Contoh: Paracetamol" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Dosis</label>
                            <input type="text" name="dosis" placeholder="Contoh: 1 Tablet" required>
                        </div>

                        <div class="form-group">
                            <label>Waktu Minum</label>
                            <div id="timeInputs">
                                <div class="time-input-group">
                                    <input type="time" name="waktu[]" required>
                                </div>
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm" style="margin-top: 0.5rem; width: 100%; text-align: center; justify-content: center;" onclick="addTimeInput()">
                                <i class="fa-solid fa-plus"></i> Tambah Waktu
                            </button>
                        </div>

                        <div class="form-group">
                            <label>Catatan (Opsional)</label>
                            <textarea name="catatan" rows="3" placeholder="Contoh: Sesudah makan"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                            Simpan Pengingat
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- History Section -->
        <div class="card" style="margin-top: 2rem;">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-icon-bg purple-icon">
                        <i class="fa-solid fa-clipboard-list"></i>
                    </div>
                    Riwayat Minum Obat
                </div>
            </div>
            <div class="table-responsive">
                <table id="historyTable">
                    <thead>
                        <tr>
                            <th>Waktu Minum</th>
                            <th>Nama Obat</th>
                            <th>Dosis</th>
                            <th>Jadwal Awal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- History Data -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <i class="fa-solid fa-check-circle" style="color: var(--success); font-size: 1.2rem;"></i>
        <span id="toastMessage">Berhasil disimpan!</span>
    </div>

    <script>
        // Set Current Date
        const dateOptions = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
        document.getElementById('currentDate').textContent = new Date().toLocaleDateString('id-ID', dateOptions);

        // Add Dynamic Time Input
        function addTimeInput() {
            const container = document.getElementById('timeInputs');
            const div = document.createElement('div');
            div.className = 'time-input-group';
            div.innerHTML = `
                <input type="time" name="waktu[]" required>
                <button type="button" class="btn-remove-time" onclick="this.parentElement.remove()" title="Hapus waktu">
                    <i class="fa-solid fa-times"></i>
                </button>
            `;
            container.appendChild(div);
        }

        // Show Toast
        function showToast(message) {
            const toast = document.getElementById('toast');
            document.getElementById('toastMessage').textContent = message;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        // Fetch Data
        async function fetchReminders() {
            try {
                const response = await fetch('../../api/pengingat_handler.php?action=get_active');
                const result = await response.json();
                
                const list = document.getElementById('activeRemindersList');
                list.innerHTML = '';

                if (result.status === 'success' && result.data.length > 0) {
                    result.data.forEach(item => {
                        const div = document.createElement('div');
                        div.className = `reminder-item ${item.is_taken ? 'taken' : ''}`;
                        
                        // Format Time
                        const timeString = item.waktu.substring(0, 5); // HH:MM

                        let actionHtml = '';
                        if (item.is_taken) {
                            actionHtml = `<span class="status-badge-taken"><i class="fa-solid fa-check"></i> Sudah</span>`;
                        } else {
                            actionHtml = `
                                <div style="display:flex; gap:0.5rem;">
                                    <button class="btn btn-primary btn-sm" onclick="markTaken(${item.id}, '${item.waktu}')">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                    <button class="btn btn-secondary btn-sm" title="Hapus" onclick="deleteReminder(${item.id})" style="color: var(--danger);">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            `;
                        }

                        div.innerHTML = `
                            <div style="display:flex; align-items:center; flex:1;">
                                <div class="reminder-time-box">
                                    ${timeString}
                                </div>
                                <div class="reminder-content">
                                    <h4>${item.nama_obat}</h4>
                                    <p>${item.dosis} • ${item.catatan || 'Tanpa catatan'}</p>
                                </div>
                            </div>
                            <div>${actionHtml}</div>
                        `;
                        list.appendChild(div);
                    });
                } else {
                    list.innerHTML = `
                        <div style="text-align:center; padding: 3rem; color: var(--text-muted);">
                            <i class="fa-regular fa-calendar-check" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                            <p>Tidak ada jadwal minum obat hari ini.</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error:', error);
                const list = document.getElementById('activeRemindersList');
                list.innerHTML = `<div style="text-align:center; padding: 2rem; color: var(--danger);">Gagal memuat data.</div>`;
            }
        }

        async function fetchHistory() {
            try {
                const response = await fetch('../../api/pengingat_handler.php?action=get_history');
                const result = await response.json();
                
                const tbody = document.querySelector('#historyTable tbody');
                tbody.innerHTML = '';

                if (result.status === 'success' && result.data.length > 0) {
                    result.data.forEach(item => {
                        const tr = document.createElement('tr');
                        const date = new Date(item.waktu_diminum).toLocaleString('id-ID');
                        tr.innerHTML = `
                            <td>${date}</td>
                            <td>${item.nama_obat}</td>
                            <td>${item.dosis}</td>
                            <td><span style="background:#f3f4f6; padding:2px 6px; border-radius:4px; font-size:0.8em;">${item.waktu_dijadwalkan.substring(0,5)}</span></td>
                        `;
                        tbody.appendChild(tr);
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; color: var(--text-muted); padding: 2rem;">Belum ada riwayat minum obat.</td></tr>';
                }
            } catch (error) {
                console.error('Error fetching history:', error);
            }
        }

        // Actions
        async function markTaken(id, time) {
            if(!confirm('Konfirmasi obat sudah diminum?')) return;

            try {
                const response = await fetch('../../api/pengingat_handler.php?action=mark_taken', {
                    method: 'POST',
                    body: JSON.stringify({ id_pengingat: id, waktu_dijadwalkan: time })
                });
                const result = await response.json();
                if (result.status === 'success') {
                    showToast('Berhasil dikonfirmasi!');
                    fetchReminders();
                    fetchHistory();
                } else {
                    alert('Gagal: ' + result.message);
                }
            } catch (error) {
                console.error(error);
            }
        }

        async function deleteReminder(id) {
            if(!confirm('Hapus pengingat ini? Jadwal ini tidak akan muncul lagi.')) return;
             try {
                const response = await fetch('../../api/pengingat_handler.php?action=delete', {
                    method: 'POST',
                    body: JSON.stringify({ id_pengingat: id })
                });
                const result = await response.json();
                if (result.status === 'success') {
                    showToast('Pengingat dihapus.');
                    fetchReminders();
                } else {
                    alert('Gagal: ' + result.message);
                }
            } catch (error) {
                console.error(error);
            }
        }

        // Form Submit
        document.getElementById('addReminderForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = {
                nama_obat: formData.get('nama_obat'),
                dosis: formData.get('dosis'),
                catatan: formData.get('catatan'),
                waktu: formData.getAll('waktu[]')
            };

            try {
                const response = await fetch('../../api/pengingat_handler.php?action=create', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                
                if (result.status === 'success') {
                    showToast('Pengingat berhasil ditambahkan!');
                    e.target.reset();
                    // Reset time inputs to just one
                    document.getElementById('timeInputs').innerHTML = `
                        <div class="time-input-group">
                            <input type="time" name="waktu[]" required>
                        </div>
                    `;
                    fetchReminders();
                } else {
                    alert('Gagal menambahkan: ' + result.message);
                }
            } catch (error) {
                console.error(error);
                alert('Terjadi kesalahan sistem.');
            }
        });

        // Initialize
        fetchReminders();
        fetchHistory();

        // Refresh every minute to check time updates
        setInterval(fetchReminders, 60000);
    </script>
</body>
</html>
