# Web Kasir (Point of Sale System)

Sistem kasir berbasis web yang digunakan untuk mengelola transaksi penjualan, manajemen produk, stok barang, dan laporan penjualan.
Project ini dibuat menggunakan **PHP Native** dan **MySQL** dengan tampilan sederhana dan mudah digunakan.

## ✨ Features

* 🔐 Login Authentication (Admin / Manager / Kasir)
* 📦 Manajemen Produk
* 🏷️ Manajemen Kategori Produk
* 🛒 Transaksi Penjualan
* 🧾 Cetak Struk Pembelian
* 📊 Laporan Penjualan
* 📁 Export Data Transaksi
* 👥 Manajemen User
* 📈 Dashboard Statistik Penjualan

## 🛠️ Tech Stack

* **Backend:** PHP (Native)
* **Database:** MySQL
* **Frontend:** HTML, CSS, JavaScript
* **Library:** Chart.js

## 📂 Project Structure

```
web-kasir/
│
├── assets/            # CSS, JS, images
├── config/            # Database configuration
├── includes/          # Helper functions & authentication
├── kasir/             # Halaman untuk kasir
├── manager/           # Halaman untuk manager / admin
├── uploads/           # Upload gambar produk
│
├── index.php          # Halaman utama
├── login.php          # Login page
├── logout.php         # Logout
├── database_structure.sql  # Struktur database
```

## ⚙️ Installation

1. Clone repository ini

```
git clone https://github.com/username/web-kasir.git
```

2. Pindahkan project ke folder web server

Contoh jika menggunakan **XAMPP**

```
htdocs/web-kasir
```

3. Import database

Buka **phpMyAdmin** lalu import file:

```
database_structure.sql
```

4. Konfigurasi database

Edit file:

```
config/database.php
```

Ubah sesuai database kamu

```
$host = "localhost";
$user = "root";
$password = "";
$database = "kasir";
```

5. Jalankan project

Buka browser

```
http://localhost/web-kasir
```

## 👤 User Roles

### Kasir

* Melakukan transaksi penjualan
* Melihat riwayat transaksi
* Cetak struk

### Manager / Admin

* Mengelola produk
* Mengelola kategori
* Mengelola user
* Melihat laporan penjualan
* Export data transaksi

## 📸 Screenshots

Tambahkan screenshot aplikasi di sini agar repository lebih menarik.

Contoh:

* Dashboard
* Halaman Transaksi
* Halaman Produk
* Laporan Penjualan

## 🚀 Future Improvements

* Sistem stok otomatis
* Grafik laporan lebih lengkap
* API integration
* UI/UX improvement
* Responsive mobile design

## 📄 License

Project ini menggunakan **MIT License** sehingga bebas digunakan dan dimodifikasi.

---

⭐ Jika project ini bermanfaat, jangan lupa berikan **star** di repository ini.
