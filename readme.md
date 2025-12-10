```
apotik/
│
├── api/                                # API endpoint untuk aplikasi mobile / AJAX frontend
│   ├── create_transaksi.php            # API untuk membuat transaksi baru (insert ke tabel transaksi)
│   ├── delete_transaksi.php            # API untuk menghapus transaksi berdasarkan ID
│   ├── get_doctor_detail.php           # API untuk mendapatkan detail dokter berdasarkan ID
│   ├── get_product_detail.php          # API untuk mendapatkan detail obat/produk berdasarkan ID
│   ├── get_transaction_detail.php      # API untuk mendapatkan detail transaksi tertentu
│   ├── search_products.php             # API untuk mencari obat/produk berdasarkan kata kunci
│   ├── submit_rating.php               # API untuk mengirim rating/ulasan dari user
│   ├── update_transaction_status.php   # API untuk mengubah status transaksi (pending, selesai, dll)
│   ├── user_login.php                  # API login user untuk aplikasi (mengembalikan JSON)
│   └── user_register.php               # API registrasi user (insert + return JSON)
│
├── assets/                             # Folder aset frontend
│   ├── images/                         # Berisi gambar UI
│   └── logo/                           # Berisi logo aplikasi
│
├── auth/                               # Halaman dan proses autentikasi
│
├── includes/                           # File yang digunakan oleh banyak halaman (template/layout)
│   ├── auth.php                        # Proteksi halaman → memastikan user sudah login
│   └── sidebar.php                     # Sidebar navigasi utama dashboard
│
├── screen/                             # Halaman utama aplikasi tampilan web
│   ├── admin/                          # Fitur khusus admin (manajemen pengguna, kontrol sistem)
│   ├── dashboard/                      # Dashboard utama setelah login 
│   ├── konsultasi/                     # Halaman konsultasi dokter
│   ├── obat/                           # Halaman obat
│   ├── rating/                         # Halaman penilaian / ulasan layanan
│   └── transaksi/                      # Riwayat transaksi, detail transaksi, update status
│
├── uploads/                            # Folder upload file (gambar dokter, gambar obat)
│   ├── dokter/                         # Foto profil dokter
│   └── obat/                           # Foto produk obat
│
├── config.php                          # Konfigurasi database & base URL
├── create_admin.php                    # Script untuk membuat admin awal (manual)
├── database.sql                        # Query untuk membuat database
├── index.php                           # Landing page / redirect ke login atau dashboard
├── readme.md                           # Dokumentasi project
└── test_connection.php                 # Tes koneksi database atau testing fungsi 
```