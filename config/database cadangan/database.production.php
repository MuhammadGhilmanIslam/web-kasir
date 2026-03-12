<?php
/**
 * Konfigurasi Database untuk Production
 * Ganti nilai-nilai di bawah sesuai dengan hosting Anda
 */

// Konfigurasi Database Production
$host = 'localhost'; // Ganti dengan host database hosting Anda
$dbname = 'your_database_name'; // Ganti dengan nama database Anda
$username = 'your_username'; // Ganti dengan username database Anda
$password = 'your_password'; // Ganti dengan password database Anda

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
