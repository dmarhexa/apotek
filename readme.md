apotek/
├── index.php (entry point)
├── config.php
├── dashboard/
│   ├── index.php
│   ├── style.css
│   └── dashboard.js
├── obat/
│   ├── index.php
│   └── style.css
├── transaksi/
│   └── index.php
├── dokter/
│   └── index.php
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
└── includes/
    └── sidebar.php

-- Tabel pegawai (UNTUK ADMIN SAJA)
CREATE TABLE pegawai (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Insert admin pertama (password: admin123)
-- Note: Password sudah di-hash untuk 'admin123'
INSERT INTO pegawai (username, password, nama_lengkap) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator Utama');