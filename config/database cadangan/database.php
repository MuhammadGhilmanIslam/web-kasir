<?php
$host = 'localhost';
$dbname = 'kasir_modern';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
