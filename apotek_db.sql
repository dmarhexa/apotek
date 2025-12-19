-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 19, 2025 at 02:09 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `apotek_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `dokter`
--

CREATE TABLE `dokter` (
  `id_dokter` int(11) NOT NULL,
  `nama_dokter` varchar(100) NOT NULL,
  `spesialisasi` varchar(100) DEFAULT NULL,
  `pengalaman` varchar(50) DEFAULT NULL,
  `nomor_telepon` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `jadwal_praktek` text DEFAULT NULL,
  `foto` varchar(100) DEFAULT NULL,
  `biaya_konsultasi` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dokter`
--

INSERT INTO `dokter` (`id_dokter`, `nama_dokter`, `spesialisasi`, `pengalaman`, `nomor_telepon`, `email`, `jadwal_praktek`, `foto`, `biaya_konsultasi`, `created_at`) VALUES
(3, 'Ahmad Setiawan', 'Lainnya', '10', '081234567890', 'ahmad.setiawan@email.com', 'Senin-Jumat: 08:00 - 16:00\r\nSabtu: 08:00 - 12:00', '1765009619_6933e8d329f9b_dokter2.jpg', 150000.00, '2025-12-06 06:30:28'),
(4, 'Yudha Santoso', 'Lainnya', '15', '081298765432', 'budi.santoso@email.com', 'Selasa-Kamis: 09:00 - 17:00\r\nJumat: 09:00 - 15:00', '1765021260_6934164c3fa50_dokterYudha.jpg', 200000.00, '2025-12-06 06:30:28'),
(5, 'Cynthia Dewi', 'Lainnya', '12', '081345678901', 'cynthia.dewi@email.com', 'Senin-Rabu: 10:00 - 18:00\r\nKamis: 10:00 - 16:00', '1765045055_6934733fa36cf_dokter1.jpg', 250000.00, '2025-12-06 06:30:28'),
(6, 'Dodi Pratama', 'Lainnya', '20', '081456789012', 'dodi.pratama@email.com', 'Rabu-Jumat: 08:00 - 16:00\r\nSabtu: 08:00 - 12:00', '1765045072_6934735018848_dokter2.webp', 300000.00, '2025-12-06 06:30:28'),
(7, 'Eka Putri', 'Lainnya', '18', '081567890123', 'eka.putri@email.com', 'Senin-Jumat: 07:00 - 15:00', '1765045109_693473750776c_dokter3.webp', 350000.00, '2025-12-06 06:30:28'),
(8, 'Dokter Lazima', 'Dokter Bedah', '10', '0852698230914', 'lazima@gmail.com', 'tidak ada praktek angjayy', '1765005881_6933da3990e72_dokter3.jpg', 100000.00, '2025-12-06 07:24:41');

-- --------------------------------------------------------

--
-- Table structure for table `obat`
--

CREATE TABLE `obat` (
  `id_obat` int(11) NOT NULL,
  `nama_obat` varchar(100) NOT NULL,
  `jenis_obat` varchar(50) DEFAULT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `harga` decimal(10,2) NOT NULL,
  `stok` int(11) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `indikasi` text DEFAULT NULL,
  `cara_pakai` text DEFAULT NULL,
  `efek_samping` text DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `obat`
--

INSERT INTO `obat` (`id_obat`, `nama_obat`, `jenis_obat`, `kategori`, `harga`, `stok`, `deskripsi`, `indikasi`, `cara_pakai`, `efek_samping`, `gambar`, `created_at`) VALUES
(1, 'Paracetamol 500mg', 'tablet', 'analgesik', 15000.00, 100, 'Obat pereda nyeri dan demam', 'Demam, sakit kepala, nyeri ringan', '1 tablet setiap 4-6 jam', 'Mual, ruam kulit', 'paracetamol.jpg', '2025-12-05 15:25:18'),
(2, 'Amoxicillin 500mg', 'kapsul', 'antibiotik', 35000.00, 50, 'Antibiotik untuk infeksi bakteri', 'Infeksi saluran pernafasan, kulit', '1 kapsul 3x sehari', 'Diare, mual, alergi', 'amoxicillin.jpg', '2025-12-05 15:25:18'),
(3, 'Vitamin C 500mg', 'tablet', 'vitamin', 25000.00, 200, 'Suplemen vitamin C', 'Meningkatkan daya tahan tubuh', '1 tablet sehari', 'Perut tidak nyaman', 'vitaminc.jpg', '2025-12-05 15:25:18'),
(4, 'Salep Kulit', 'salep', 'obat_luar', 45000.00, 30, 'Salep untuk masalah kulit', 'Infeksi kulit ringan', 'Oleskan 2x sehari', 'Iritasi lokal', 'salep.jpg', '2025-12-05 15:25:18'),
(6, 'Paracetamol', 'sirup', 'antibiotik', 85000.00, 134, 'Suplemen vitamin B kompleks untuk energi', 'Kekurangan vitamin B, kelelahan', '1 tablet sehari setelah makan', 'Urine berwarna kuning', '1764959579_sanmag-sirup-di-apotik.webp', '2025-12-05 16:33:03'),
(7, 'Ibuprofen 400mg', 'tablet', 'analgesik', 25000.00, 78, 'Obat pereda nyeri dan anti-inflamasi', 'Nyeri otot, sakit gigi, demam', '1 tablet setiap 6-8 jam', 'Mual, sakit perut', '1764959570_obat-sakit-lambung-paling-ampuh-6-alodokter.jpg', '2025-12-05 16:33:03'),
(8, 'Cetirizine 10mg', 'tablet', 'antihistamin', 35000.00, 117, 'Obat alergi generasi kedua', 'Alergi, biduran, rhinitis', '1 tablet sehari', 'Mengantuk ringan', '1765044829_6934725d15b3b_8-obat-pegal-linu-dan-nyeri-sendi-yang-aman-dan-ampuh-2-alodokter.jpg', '2025-12-05 16:33:03'),
(10, 'Salbutamol Inhaler', 'inhaler', 'obat_asma', 125000.00, 40, 'Obat inhalasi untuk asma', 'Asma, bronkitis', '2-4 puff setiap 4-6 jam', 'Jantung berdebar', 'inhaler.jpg', '2025-12-05 16:33:03'),
(11, 'Paracetamol Sirup 120mg/5ml', 'sirup', 'analgesik', 55000.00, 60, 'Sirup paracetamol untuk anak', 'Demam dan nyeri pada anak', 'Berdasarkan berat badan anak', 'Sangat jarang', 'paracetamol_sirup.jpg', '2025-12-05 16:33:03'),
(12, 'Antasida Doen', 'tablet', 'obat_lambung', 20000.00, 200, 'Obat penetral asam lambung', 'Mual, maag, perut kembung', '1-2 tablet saat gejala', 'Diare atau sembelit', 'antasida.jpg', '2025-12-05 16:33:03'),
(13, 'Dexamethasone 0.5mg', 'tablet', 'steroid', 30000.00, 50, 'Obat steroid anti-inflamasi', 'Alergi berat, inflamasi', 'Aturan dokter', 'Meningkatkan nafsu makan', 'dexamethasone.jpg', '2025-12-05 16:33:03'),
(14, 'Ambroxol 30mg', 'tablet', 'obat_batuk', 25000.00, 110, 'Obat pengencer dahak', 'Batuk berdahak', '1 tablet 3x sehari', 'Mual, muntah', 'ambroxol.jpg', '2025-12-05 16:33:03'),
(15, 'Tetes Mata Chloramphenicol', 'tetes', 'obat_mata', 35000.00, 75, 'Obat tetes mata antibiotik', 'Infeksi mata', '1-2 tetes 3-4x sehari', 'Iritasi ringan', 'tetes_mata.jpg', '2025-12-05 16:33:03'),
(16, 'Vitamin D3 1000IU', 'softgel', 'vitamin', 95000.00, 99, 'Suplemen vitamin D3', 'Kekurangan vitamin D', '1 softgel sehari', 'Sangat jarang', 'vitamin_d.jpg', '2025-12-05 16:33:03'),
(17, 'Zinc 50mg', 'tablet', 'suplemen', 65000.00, 85, 'Suplemen zinc untuk imunitas', 'Meningkatkan daya tahan tubuh', '1 tablet sehari', 'Mual ringan', 'zinc.jpg', '2025-12-05 16:33:03'),
(18, 'alkohol', 'sirup', 'vitamin', 20000.00, 102, 'alkohol menyehatkan ', 'untuk sakit hati', 'minum sepuasnya sampai habis', 'sakit hati hilang dan ada sedikit ketenangan', '1765001163_obat_dan_vitamin_Doktersehat_com_Elkana_Film_Coated_Tablet.jpg', '2025-12-06 06:06:03');

-- --------------------------------------------------------

--
-- Table structure for table `pegawai`
--

CREATE TABLE `pegawai` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `role` varchar(20) NOT NULL DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pegawai`
--

INSERT INTO `pegawai` (`id`, `username`, `password`, `nama_lengkap`, `status`, `role`, `created_at`, `last_login`) VALUES
(3, 'admin', '$2y$10$doyDsD5KGP8LhLB39fyb3ueJxp/gWX5WcgskZ1QWmynHU7co5.ixG', 'Administrator Utama', 'active', 'admin', '2025-12-06 15:21:34', '2025-12-18 14:37:57'),
(4, 'tekekid12345', '$2y$10$Ae8h3AtYuF9bvDhD.8a24emhKwLjMK4S2RAeAGTtqpB3PBvoUpBx6', 'Tekek ID', 'active', 'Developer', '2025-12-06 16:19:44', '2025-12-06 18:25:52'),
(6, 'yudha', '$2y$10$Qe38g5fPW1RUE13ZIpdu2.3IhxPsw6bFeiDXSRwudbzo1OwOSd1.u', 'yudha123', 'active', 'admin', '2025-12-09 05:26:00', '2025-12-09 05:27:42');

-- --------------------------------------------------------

--
-- Table structure for table `pengingat`
--

CREATE TABLE `pengingat` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `nama_obat` varchar(100) NOT NULL,
  `dosis` varchar(50) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengingat`
--

INSERT INTO `pengingat` (`id`, `id_user`, `nama_obat`, `dosis`, `catatan`, `is_active`, `created_at`) VALUES
(1, 2, 'jhbwrhbv', '1', 'mnbhfbw', 0, '2025-12-18 03:30:09'),
(2, 2, 'Paracetamol', '1 tablet', 'Sesudah Makan', 1, '2025-12-18 13:34:09'),
(3, 2, 'Alkohol', '5 tablet', 'Sedudah Makan', 1, '2025-12-18 14:29:21');

-- --------------------------------------------------------

--
-- Table structure for table `pengingat_obat`
--

CREATE TABLE `pengingat_obat` (
  `id` int(11) NOT NULL,
  `id_pengguna` int(11) NOT NULL,
  `nama_obat` varchar(255) NOT NULL,
  `frekuensi` varchar(50) NOT NULL COMMENT 'e.g. 3x1',
  `waktu` time NOT NULL COMMENT 'e.g. 08:00:00',
  `keterangan` text DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pengingat_waktu`
--

CREATE TABLE `pengingat_waktu` (
  `id` int(11) NOT NULL,
  `id_pengingat` int(11) NOT NULL,
  `waktu` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengingat_waktu`
--

INSERT INTO `pengingat_waktu` (`id`, `id_pengingat`, `waktu`) VALUES
(1, 1, '12:00:00'),
(2, 2, '07:30:00'),
(3, 3, '06:00:00'),
(4, 3, '12:00:00'),
(5, 3, '18:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `rating`
--

CREATE TABLE `rating` (
  `id_rating` int(11) NOT NULL,
  `nama_user` varchar(100) DEFAULT NULL,
  `bintang` int(11) NOT NULL CHECK (`bintang` between 1 and 5),
  `komentar` text DEFAULT NULL,
  `tanggal_rating` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rating`
--

INSERT INTO `rating` (`id_rating`, `nama_user`, `bintang`, `komentar`, `tanggal_rating`) VALUES
(5, 'Damar Bima Sahika', 4, 'Terimakasih Apotek Sehat', '2025-12-18 14:37:46');

-- --------------------------------------------------------

--
-- Table structure for table `riwayat_minum`
--

CREATE TABLE `riwayat_minum` (
  `id` int(11) NOT NULL,
  `id_pengingat` int(11) NOT NULL,
  `waktu_dijadwalkan` time DEFAULT NULL,
  `waktu_diminum` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `riwayat_minum`
--

INSERT INTO `riwayat_minum` (`id`, `id_pengingat`, `waktu_dijadwalkan`, `waktu_diminum`) VALUES
(1, 2, '07:30:00', '2025-12-18 20:34:20');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id_transaksi` int(11) NOT NULL,
  `id_pengguna` int(11) DEFAULT NULL,
  `nama_pembeli` varchar(100) DEFAULT NULL,
  `tanggal_transaksi` datetime DEFAULT current_timestamp(),
  `total_harga` decimal(10,2) NOT NULL,
  `status_pembayaran` enum('lunas','pending','dibatalkan') DEFAULT 'pending',
  `metode_pembayaran` varchar(50) DEFAULT NULL,
  `alamat_pengiriman` text DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaksi`
--

INSERT INTO `transaksi` (`id_transaksi`, `id_pengguna`, `nama_pembeli`, `tanggal_transaksi`, `total_harga`, `status_pembayaran`, `metode_pembayaran`, `alamat_pengiriman`, `catatan`, `created_at`) VALUES
(1, NULL, 'Budi Santoso', '2025-12-05 22:25:35', 75000.00, 'lunas', 'Transfer Bank', 'Jl. Merdeka No. 123', NULL, '2025-12-05 15:25:35'),
(3, NULL, 'Ahmad Hidayat', '2025-12-05 22:25:35', 90000.00, 'lunas', 'E-Wallet', 'Jl. Gatot Subroto No. 67', NULL, '2025-12-05 15:25:35'),
(4, NULL, 'hbrbhib', '2025-12-06 03:29:08', 340000.00, 'lunas', 'tunai', 'hbwefbhb', 'jwbehifbhib', '2025-12-05 20:29:08'),
(5, NULL, 'angjayyy', '2025-12-06 03:31:28', 105000.00, 'lunas', 'transfer_bank', 'nsrhfvbhbwr', 'n ws rfk', '2025-12-05 20:31:28'),
(8, 1, 'jhberfhb', '2025-12-06 13:13:47', 100000.00, 'lunas', 'e-wallet', 'jihwefbhiwrf', 'uihwrgfwef', '2025-12-06 06:13:47'),
(9, 1, 'jhbarhwvbhb', '2025-12-06 13:37:18', 200000.00, 'lunas', 'tunai', 'sedhfbchisv', 'kbsahebfhb', '2025-12-06 06:37:18'),
(11, 1, 'hjwbjevf', '2025-12-06 23:04:40', 25000.00, 'pending', 'tunai', 'hjwef', 'wq4rf', '2025-12-06 16:04:40'),
(12, 1, 'j warhbf', '2025-12-07 01:22:37', 20000.00, 'pending', 'transfer_bank', 'kbwsefbhb', 'jkbwebf', '2025-12-06 18:22:37'),
(13, 1, 'dafvrgerg', '2025-12-07 01:24:54', 20000.00, 'pending', 'e-wallet', 'rwrthetg', 'sethrsth', '2025-12-06 18:24:54'),
(14, NULL, 'Damar', '2025-12-08 18:37:43', 95000.00, 'lunas', 'kartu_kredit', 'SMG', '', '2025-12-08 11:37:43'),
(16, NULL, 'Loki', '2025-12-13 10:04:01', 20000.00, 'lunas', 'transfer_bank', '', '', '2025-12-13 03:04:01'),
(17, 2, 'Agus', '2025-12-13 10:36:44', 20000.00, 'lunas', 'tunai', '', '', '2025-12-13 03:36:44'),
(18, 2, 'Bambang', '2025-12-13 11:05:00', 25000.00, 'lunas', 'tunai', 'SMG', '', '2025-12-13 04:05:00');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi_detail`
--

CREATE TABLE `transaksi_detail` (
  `id_detail` int(11) NOT NULL,
  `id_transaksi` int(11) NOT NULL,
  `id_obat` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 1,
  `harga_satuan` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaksi_detail`
--

INSERT INTO `transaksi_detail` (`id_detail`, `id_transaksi`, `id_obat`, `jumlah`, `harga_satuan`, `subtotal`, `created_at`) VALUES
(1, 4, 6, 4, 85000.00, 340000.00, '2025-12-05 20:29:08'),
(2, 5, 8, 3, 35000.00, 105000.00, '2025-12-05 20:31:28'),
(5, 8, 18, 5, 20000.00, 100000.00, '2025-12-06 06:13:47'),
(6, 9, 18, 10, 20000.00, 200000.00, '2025-12-06 06:37:18'),
(8, 11, 7, 1, 25000.00, 25000.00, '2025-12-06 16:04:40'),
(9, 12, 18, 1, 20000.00, 20000.00, '2025-12-06 18:22:37'),
(10, 13, 18, 1, 20000.00, 20000.00, '2025-12-06 18:24:54'),
(11, 14, 16, 1, 95000.00, 95000.00, '2025-12-08 11:37:43'),
(13, 16, 18, 1, 20000.00, 20000.00, '2025-12-13 03:04:01'),
(14, 17, 18, 1, 20000.00, 20000.00, '2025-12-13 03:36:44'),
(15, 18, 7, 1, 25000.00, 25000.00, '2025-12-13 04:05:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama_lengkap`, `email`, `password`, `no_hp`, `created_at`) VALUES
(2, 'Agus', 'agus123@gmail.com', '$2y$10$Xfed1Nx.wnAeKjyRiszPJeX0ExULKqPnnFBD.7s5oH9.4V/3cRuam', '1234567810', '2025-12-13 03:36:02');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dokter`
--
ALTER TABLE `dokter`
  ADD PRIMARY KEY (`id_dokter`);

--
-- Indexes for table `obat`
--
ALTER TABLE `obat`
  ADD PRIMARY KEY (`id_obat`);

--
-- Indexes for table `pegawai`
--
ALTER TABLE `pegawai`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `pengingat`
--
ALTER TABLE `pengingat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `pengingat_obat`
--
ALTER TABLE `pengingat_obat`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pengingat_waktu`
--
ALTER TABLE `pengingat_waktu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_pengingat` (`id_pengingat`);

--
-- Indexes for table `rating`
--
ALTER TABLE `rating`
  ADD PRIMARY KEY (`id_rating`);

--
-- Indexes for table `riwayat_minum`
--
ALTER TABLE `riwayat_minum`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_pengingat` (`id_pengingat`);

--
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id_transaksi`);

--
-- Indexes for table `transaksi_detail`
--
ALTER TABLE `transaksi_detail`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `idx_transaksi` (`id_transaksi`),
  ADD KEY `idx_obat` (`id_obat`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dokter`
--
ALTER TABLE `dokter`
  MODIFY `id_dokter` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `obat`
--
ALTER TABLE `obat`
  MODIFY `id_obat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `pegawai`
--
ALTER TABLE `pegawai`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `pengingat`
--
ALTER TABLE `pengingat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pengingat_obat`
--
ALTER TABLE `pengingat_obat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `pengingat_waktu`
--
ALTER TABLE `pengingat_waktu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `rating`
--
ALTER TABLE `rating`
  MODIFY `id_rating` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `riwayat_minum`
--
ALTER TABLE `riwayat_minum`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id_transaksi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `transaksi_detail`
--
ALTER TABLE `transaksi_detail`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `pengingat`
--
ALTER TABLE `pengingat`
  ADD CONSTRAINT `pengingat_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pengingat_waktu`
--
ALTER TABLE `pengingat_waktu`
  ADD CONSTRAINT `pengingat_waktu_ibfk_1` FOREIGN KEY (`id_pengingat`) REFERENCES `pengingat` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `riwayat_minum`
--
ALTER TABLE `riwayat_minum`
  ADD CONSTRAINT `riwayat_minum_ibfk_1` FOREIGN KEY (`id_pengingat`) REFERENCES `pengingat` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transaksi_detail`
--
ALTER TABLE `transaksi_detail`
  ADD CONSTRAINT `transaksi_detail_ibfk_1` FOREIGN KEY (`id_transaksi`) REFERENCES `transaksi` (`id_transaksi`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaksi_detail_ibfk_2` FOREIGN KEY (`id_obat`) REFERENCES `obat` (`id_obat`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
