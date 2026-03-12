<?php
// Auto-detect environment (local vs production)
// Jika file database.production.php ada dan berisi konfigurasi valid, gunakan itu
// Jika tidak, gunakan konfigurasi local

$isProduction = file_exists(__DIR__ . '/database.production.php');

if ($isProduction) {
    // Cek apakah production config sudah dikonfigurasi
    $prodConfig = file_get_contents(__DIR__ . '/database.production.php');
    if (strpos($prodConfig, 'your_database_name') === false && strpos($prodConfig, 'your_username') === false) {
        // Production config sudah dikonfigurasi, gunakan itu
        require_once __DIR__ . '/database.production.php';
        return; // Stop execution, production config sudah handle koneksi
    }
}

// Konfigurasi Local (Development)
$host = 'localhost';
$dbname = 'kasir_modern';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
function generateKodeTransaksi($pdo) {
    $date = date('Ymd');
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM transactions WHERE DATE(created_at)=CURDATE()");
    $stmt->execute();
    $count = $stmt->fetch()['total'] + 1;
    return "TRX" . $date . str_pad($count, 4, '0', STR_PAD_LEFT);
}

function uploadGambar($file) {
    if ($file['error'] === 0) {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif'])) {
            $fileName = uniqid() . '.' . $ext;
            $path = $uploadDir . $fileName;
            if (move_uploaded_file($file['tmp_name'], $path)) return $fileName;
        }
    }
    return null;
}
?>
