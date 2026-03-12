# 🚀 Panduan Setup Sistem Kasir di InfinityFree

## 📋 Langkah 1: Setup Database di InfinityFree

### 1.1. Buat Database MySQL
1. Login ke **InfinityFree Control Panel**
2. Klik menu **"MySQL Databases"** di panel kiri
3. Klik **"Create Database"**
4. Isi form:
   - **Database Name**: `kasir_modern` (atau nama lain yang Anda inginkan)
   - **Database User**: (akan dibuat otomatis dengan format `if0_xxxxx_kasir`)
   - **Password**: Buat password yang kuat (simpan password ini!)
5. Klik **"Create"**
6. **CATAT INFORMASI BERIKUT:**
   - Database Name: `if0_xxxxx_kasir_modern` (format lengkap)
   - Database User: `if0_xxxxx_kasir` (format lengkap)
   - Password: (password yang Anda buat)
   - Host: Biasanya `localhost` atau `sqlXXX.infinityfree.com`

### 1.2. Import Database Structure
1. Klik **"phpMyAdmin"** di panel MySQL Databases
2. Pilih database yang baru dibuat
3. Klik tab **"Import"**
4. Upload file SQL dari localhost Anda (export dari phpMyAdmin local)
   - Atau gunakan file `database_structure.sql` jika ada
5. Klik **"Go"** untuk import

**Atau buat tabel manual dengan SQL berikut:**

```sql
-- Tabel Users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('kasir','manajer') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Products
CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_produk` varchar(255) NOT NULL,
  `barcode` varchar(50) DEFAULT NULL,
  `harga` decimal(10,2) NOT NULL,
  `stok` int(11) NOT NULL DEFAULT '0',
  `stok_minimum` int(11) NOT NULL DEFAULT '10',
  `gambar` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `barcode` (`barcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Transactions
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_transaksi` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `jumlah_bayar` decimal(10,2) NOT NULL,
  `kembalian` decimal(10,2) NOT NULL,
  `metode_pembayaran` varchar(50) NOT NULL,
  `status` enum('completed','cancelled') NOT NULL DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_transaksi` (`kode_transaksi`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Transaction Items
CREATE TABLE IF NOT EXISTS `transaction_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `harga_satuan` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `transaction_id` (`transaction_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `transaction_items_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transaction_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user
-- Password: password (hash bcrypt)
INSERT INTO `users` (`username`, `password`, `nama_lengkap`, `role`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'manajer'),
('kasir1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kasir Satu', 'kasir');
```

## 📋 Langkah 2: Konfigurasi Database di File

### 2.1. Edit File `config/database.production.php`

1. Buka file `config/database.production.php` di File Manager InfinityFree
2. Edit dengan informasi database Anda:

```php
<?php
// Konfigurasi Database untuk InfinityFree
$host = 'localhost'; // atau 'sqlXXX.infinityfree.com' jika diberikan
$dbname = 'if0_40692342_kasir_modern'; // GANTI dengan database name lengkap Anda
$username = 'if0_40692342_kasir'; // GANTI dengan username lengkap Anda
$password = 'NextPay745'; // GANTI dengan password database Anda

try {
    // Untuk InfinityFree, kadang perlu menambahkan port atau socket
    // Coba dulu dengan ini, jika error coba dengan port 3306
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    
    // Jika masih error, coba dengan port:
    // $dsn = "mysql:host=$host;port=3306;dbname=$dbname;charset=utf8mb4";
    
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Set timezone
    $pdo->exec("SET time_zone = '+07:00'");
    
} catch(PDOException $e) {
    // Jangan tampilkan error detail di production
    error_log("Database connection failed: " . $e->getMessage());
    die("Koneksi database gagal. Silakan hubungi administrator.");
}
?>
```

**PENTING:** Ganti nilai-nilai berikut dengan data Anda:
- `$dbname`: Database name lengkap dari InfinityFree (format: `if0_xxxxx_namadb`)
- `$username`: Username lengkap dari InfinityFree (format: `if0_xxxxx_user`)
- `$password`: Password yang Anda buat saat membuat database

## 📋 Langkah 3: Upload File ke InfinityFree

### 3.1. Via File Manager
1. Login ke InfinityFree Control Panel
2. Klik **"File Manager"**
3. Navigasi ke folder `htdocs` atau `public_html`
4. Upload semua file project ke folder tersebut
5. Pastikan struktur folder sama seperti di localhost

### 3.2. Via FTP (FileZilla)
1. Dapatkan FTP details dari InfinityFree:
   - **Host**: `ftpupload.net` atau IP yang diberikan
   - **Username**: `if0_40692342` (username account Anda)
   - **Password**: `NextPay745` (password account Anda)
   - **Port**: 21
2. Connect dengan FileZilla
3. Upload semua file ke folder `htdocs` atau `public_html`

## 📋 Langkah 4: Set Permissions

1. Di File Manager, set permission folder `uploads/` ke **755**
2. Set permission file PHP ke **644**
3. Pastikan folder `uploads/` writable

## 📋 Langkah 5: Test Koneksi Database

1. Buat file `test_connection.php` di root folder:

```php
<?php
require_once 'config/database.production.php';

echo "Database connection: OK<br>";
echo "Database: " . $dbname . "<br>";

// Test query
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $result = $stmt->fetch();
    echo "Total users: " . $result['total'] . "<br>";
    echo "<strong style='color: green;'>SUCCESS! Database connected!</strong>";
} catch(Exception $e) {
    echo "<strong style='color: red;'>ERROR: " . $e->getMessage() . "</strong>";
}
?>
```

2. Akses via browser: `https://web-kasir-m-mart.infinityfreeapp.com/test_connection.php`
3. Jika muncul "SUCCESS", berarti koneksi berhasil
4. **HAPUS file test_connection.php** setelah testing (untuk security)

## 🔧 Troubleshooting

### Error: "No such file or directory"
**Solusi:**
1. Cek apakah host database benar (biasanya `localhost`)
2. Coba tambahkan port: `mysql:host=localhost;port=3306;dbname=...`
3. Pastikan database name dan username lengkap (dengan prefix `if0_xxxxx_`)

### Error: "Access denied"
**Solusi:**
1. Pastikan username dan password benar
2. Pastikan user sudah di-assign ke database di MySQL Databases
3. Cek apakah user memiliki privilege yang cukup

### Error: "Unknown database"
**Solusi:**
1. Pastikan database name lengkap (dengan prefix `if0_xxxxx_`)
2. Pastikan database sudah dibuat di MySQL Databases
3. Pastikan database sudah di-import struktur tabelnya

### Website Blank/White Screen
**Solusi:**
1. Cek error log di InfinityFree Control Panel
2. Pastikan PHP version 7.4 atau 8.0+
3. Cek apakah semua file terupload lengkap
4. Pastikan `.htaccess` tidak bermasalah

## ✅ Checklist Sebelum Go Live

- [ ] Database sudah dibuat dan di-import
- [ ] File `config/database.production.php` sudah dikonfigurasi
- [ ] File sudah terupload lengkap
- [ ] Permission folder `uploads/` sudah di-set 755
- [ ] Test koneksi database berhasil
- [ ] Test login dengan akun admin
- [ ] Test upload gambar produk
- [ ] Test transaksi kasir
- [ ] File `test_connection.php` sudah dihapus
- [ ] SSL sudah aktif (InfinityFree biasanya otomatis)

## 🔐 Security Tips

1. **Jangan simpan password di file yang bisa diakses public**
2. **Gunakan password yang kuat untuk database**
3. **Hapus file test setelah selesai**
4. **Backup database secara berkala**
5. **Update password secara berkala**

## 📞 Support

Jika masih ada masalah:
1. Cek error log di InfinityFree Control Panel
2. Hubungi support InfinityFree
3. Cek dokumentasi InfinityFree: https://forum.infinityfree.com/

---

**Catatan:** 
- InfinityFree adalah hosting gratis, jadi ada beberapa limitasi
- Untuk production serius, pertimbangkan hosting berbayar
- Backup database secara berkala!

