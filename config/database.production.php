<?php
/**
 * Konfigurasi Database untuk Production
 * Ganti nilai-nilai di bawah sesuai dengan hosting Anda
 */

// Konfigurasi Database Production
$host = 'sql308.infinityfree.com'; // Ganti dengan host database hosting Anda
$dbname = 'if0_40692342_mmart_db'; // Ganti dengan nama database Anda
$username = 'if0_40692342'; // Ganti dengan username database Anda
$password = 'NextPay745'; // Ganti dengan password database Anda

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
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
