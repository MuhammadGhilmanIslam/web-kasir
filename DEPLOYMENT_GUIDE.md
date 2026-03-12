# 🚀 Panduan Deploy Kasir Modern ke Website

## 📋 Persiapan Sebelum Deploy

### 1. **Backup Database**
```sql
-- Export database dari phpMyAdmin atau command line
mysqldump -u root -p kasir_modern > kasir_modern_backup.sql
```

### 2. **Siapkan File Konfigurasi**
- Pastikan `config/database.php` siap untuk production
- Siapkan file `.htaccess` untuk security
- Siapkan file `robots.txt`

### 3. **Optimasi untuk Production**
- Minify CSS/JS
- Optimize images
- Enable gzip compression
- Set proper file permissions

## 🌐 Opsi Hosting

### **A. Shared Hosting (Rekomendasi untuk Pemula)**

#### **Pilihan Hosting:**
- **Niagahoster** - Rp 25rb/bulan (promo)
- **Rumahweb** - Rp 30rb/bulan
- **Hostinger** - Rp 15rb/bulan (promo)
- **Jagoanhosting** - Rp 50rb/bulan

#### **Syarat Minimum:**
- PHP 7.4 atau 8.0+
- MySQL 5.7 atau 8.0+
- cPanel/WHM
- SSL Certificate (gratis)
- 1GB storage minimum

### **B. VPS (Untuk Toko Besar)**

#### **Pilihan VPS:**
- **DigitalOcean** - $5/bulan (1GB RAM)
- **Vultr** - $6/bulan (1GB RAM)
- **AWS EC2** - $3-10/bulan
- **Linode** - $5/bulan

## 📁 Langkah Deploy

### **Step 1: Upload Files**
1. **Via cPanel File Manager:**
   - Login ke cPanel
   - Buka File Manager
   - Upload semua file ke folder `public_html` atau subdomain

2. **Via FTP (FileZilla):**
   - Host: `ftp.namadomain.com`
   - Username: `username@namadomain.com`
   - Password: password hosting
   - Port: 21

### **Step 2: Setup Database**
1. **Buat Database:**
   - Login ke cPanel
   - Buka "MySQL Databases"
   - Buat database baru: `namadomain_kasir`
   - Buat user database: `namadomain_user`
   - Assign user ke database

2. **Import Database:**
   - Buka phpMyAdmin
   - Pilih database yang baru dibuat
   - Import file `kasir_modern_backup.sql`

### **Step 3: Konfigurasi Database**
Edit file `config/database.php`:
```php
<?php
$host = 'localhost'; // atau IP database server
$dbname = 'namadomain_kasir';
$username = 'namadomain_user';
$password = 'password_database';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
?>
```

### **Step 4: Set Permissions**
```bash
# Set permission untuk folder uploads
chmod 755 uploads/
chmod 644 config/database.php
chmod 644 *.php
```

### **Step 5: Setup Domain/Subdomain**
1. **Subdomain:** `kasir.namadomain.com`
2. **Domain terpisah:** `namadomain.com`
3. **Folder:** `namadomain.com/kasir`

## 🔒 Security & Optimization

### **1. File .htaccess**
Buat file `.htaccess` di root folder:
```apache
# Security Headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"

# Hide sensitive files
<Files "config/database.php">
    Order Allow,Deny
    Deny from all
</Files>

# Enable GZIP
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>

# Cache Control
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
</IfModule>
```

### **2. File robots.txt**
```txt
User-agent: *
Disallow: /config/
Disallow: /includes/
Disallow: /uploads/
Disallow: /manager/
Disallow: /kasir/
```

## 🚀 Testing Setelah Deploy

### **1. Test Koneksi Database**
- Buka `namadomain.com/kasir-modern/`
- Login dengan akun admin
- Cek apakah data muncul

### **2. Test Upload File**
- Coba upload gambar produk
- Pastikan folder `uploads/` writable

### **3. Test Transaksi**
- Buat transaksi test
- Cek apakah data tersimpan

## 📱 Domain & SSL

### **Setup Domain:**
1. **Beli domain** di Niagahoster, Rumahweb, atau GoDaddy
2. **Point DNS** ke hosting server
3. **Setup SSL** (biasanya gratis di hosting)

### **Contoh URL:**
- **Manager:** `https://kasir.namadomain.com/manager/`
- **Kasir:** `https://kasir.namadomain.com/kasir/`
- **Login:** `https://kasir.namadomain.com/`

## 💰 Estimasi Biaya

### **Shared Hosting:**
- Domain: Rp 150rb/tahun
- Hosting: Rp 300rb/tahun
- SSL: Gratis
- **Total: Rp 450rb/tahun**

### **VPS:**
- Domain: Rp 150rb/tahun
- VPS: Rp 600rb/tahun
- SSL: Gratis (Let's Encrypt)
- **Total: Rp 750rb/tahun**

## 🆘 Troubleshooting

### **Error "Database Connection Failed":**
- Cek username/password database
- Cek host database (biasanya localhost)
- Pastikan database sudah dibuat

### **Error "Permission Denied":**
- Set permission folder uploads ke 755
- Set permission file PHP ke 644

### **Error "File Not Found":**
- Cek path file di browser
- Pastikan file sudah terupload lengkap

## 📞 Support

Jika ada masalah saat deploy, hubungi:
- Support hosting provider
- Developer sistem ini
- Forum hosting Indonesia

---
**Catatan:** Pastikan backup database dan file sebelum deploy!
