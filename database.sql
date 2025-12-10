--tabel obat
CREATE TABLE obat (
    id_obat INT PRIMARY KEY AUTO_INCREMENT,
    nama_obat VARCHAR(100) NOT NULL,
    jenis_obat VARCHAR(50), -- (tablet/sirup/kapsul/salep/dll)
    kategori VARCHAR(50), -- (antibiotik/analgesik/vitamin/dll)
    harga DECIMAL(10,2) NOT NULL,
    stok INT NOT NULL,
    deskripsi TEXT,
    indikasi TEXT, -- untuk penyakit apa
    cara_pakai TEXT,
    efek_samping TEXT,
    gambar VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

--Tabel Dokter
CREATE TABLE dokter (
    id_dokter INT PRIMARY KEY AUTO_INCREMENT,
    nama_dokter VARCHAR(100) NOT NULL,
    spesialisasi VARCHAR(100),
    pengalaman VARCHAR(50), -- (5 tahun, 10 tahun, dll)
    nomor_telepon VARCHAR(20),
    email VARCHAR(100),
    jadwal_praktek TEXT, -- (Senin-Jumat: 08:00-16:00)
    foto VARCHAR(100),
    biaya_konsultasi DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

--Tabel Transaksi
CREATE TABLE transaksi (
    id_transaksi INT PRIMARY KEY AUTO_INCREMENT,
    id_pengguna INT, -- jika ada sistem login
    nama_pembeli VARCHAR(100),
    tanggal_transaksi DATETIME DEFAULT CURRENT_TIMESTAMP,
    total_harga DECIMAL(10,2) NOT NULL,
    status_pembayaran ENUM('lunas', 'pending', 'dibatalkan') DEFAULT 'pending',
    metode_pembayaran VARCHAR(50),
    alamat_pengiriman TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

--Tabel Detail Transaksi
CREATE TABLE detail_transaksi (
    id_detail INT PRIMARY KEY AUTO_INCREMENT,
    id_transaksi INT,
    id_obat INT,
    jumlah INT NOT NULL,
    harga_satuan DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_transaksi) REFERENCES transaksi(id_transaksi),
    FOREIGN KEY (id_obat) REFERENCES obat(id_obat)
);

--Tabel Rating
CREATE TABLE rating (
    id_rating INT PRIMARY KEY AUTO_INCREMENT,
    nama_user VARCHAR(100),
    bintang INT NOT NULL CHECK (bintang BETWEEN 1 AND 5),
    komentar TEXT,
    tanggal_rating TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

--tabel Pengingat Obat
CREATE TABLE pengingat_obat (
    id_pengingat INT PRIMARY KEY AUTO_INCREMENT,
    nama_pasien VARCHAR(100),
    nama_obat VARCHAR(100),
    dosis VARCHAR(50), -- (1 tablet, 2 sendok, dll)
    frekuensi VARCHAR(50), -- (3x sehari, pagi-sore-malam)
    waktu_pengingat TIME,
    tanggal_mulai DATE,
    tanggal_selesai DATE,
    catatan TEXT,
    status ENUM('aktif', 'selesai') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
