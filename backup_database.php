<?php
/**
 * Script Backup Database
 * Jalankan file ini untuk backup database sebelum deploy
 */

require_once 'config/database.php';

// Konfigurasi backup
$backup_dir = 'backups/';
$backup_file = $backup_dir . 'kasir_modern_backup_' . date('Y-m-d_H-i-s') . '.sql';

// Buat folder backup jika belum ada
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

try {
    // Dapatkan informasi database
    $db_name = $pdo->query("SELECT DATABASE()")->fetchColumn();
    
    // Command untuk mysqldump (sesuaikan path MySQL Anda)
    $mysql_path = 'C:\\xampp\\mysql\\bin\\mysqldump'; // Windows XAMPP
    // $mysql_path = '/usr/bin/mysqldump'; // Linux
    
    // Dapatkan kredensial database dari PDO
    $dsn = $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS);
    
    // Untuk XAMPP, gunakan kredensial default
    $username = 'root';
    $password = '';
    $host = 'localhost';
    
    // Command mysqldump
    $command = "\"$mysql_path\" --host=$host --user=$username --password=$password --single-transaction --routines --triggers $db_name > \"$backup_file\"";
    
    // Jalankan backup
    $output = [];
    $return_var = 0;
    exec($command, $output, $return_var);
    
    if ($return_var === 0 && file_exists($backup_file)) {
        $file_size = filesize($backup_file);
        echo "✅ Backup berhasil!\n";
        echo "📁 File: $backup_file\n";
        echo "📊 Ukuran: " . number_format($file_size / 1024, 2) . " KB\n";
        echo "🕒 Waktu: " . date('Y-m-d H:i:s') . "\n";
        echo "\n📋 Langkah selanjutnya:\n";
        echo "1. Download file backup ini\n";
        echo "2. Upload ke hosting baru\n";
        echo "3. Import ke database hosting\n";
    } else {
        echo "❌ Backup gagal!\n";
        echo "Error: " . implode("\n", $output) . "\n";
        echo "\n💡 Solusi alternatif:\n";
        echo "1. Buka phpMyAdmin\n";
        echo "2. Pilih database 'kasir_modern'\n";
        echo "3. Klik 'Export' > 'Go'\n";
        echo "4. Download file SQL\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\n💡 Solusi alternatif:\n";
    echo "1. Buka phpMyAdmin\n";
    echo "2. Pilih database 'kasir_modern'\n";
    echo "3. Klik 'Export' > 'Go'\n";
    echo "4. Download file SQL\n";
}
?>
