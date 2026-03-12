<?php
/**
 * File Test Koneksi Database untuk InfinityFree
 * HAPUS FILE INI SETELAH TESTING SELESAI!
 */

// Cek apakah production config ada
if (!file_exists(__DIR__ . '/config/database.production.php')) {
    die("File config/database.production.php tidak ditemukan!<br>Silakan buat file tersebut terlebih dahulu.");
}

require_once __DIR__ . '/config/database.production.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Database Connection</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: green; font-weight: bold; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; margin: 10px 0; }
        .error { color: red; font-weight: bold; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; padding: 10px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .warning { color: #856404; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Test Koneksi Database</h1>
        
        <?php
        try {
            echo '<div class="success">✅ Koneksi database BERHASIL!</div>';
            
            // Test query
            echo '<h2>Informasi Database:</h2>';
            echo '<table>';
            echo '<tr><th>Item</th><th>Value</th></tr>';
            echo '<tr><td>Database Name</td><td>' . htmlspecialchars($dbname) . '</td></tr>';
            echo '<tr><td>Username</td><td>' . htmlspecialchars($username) . '</td></tr>';
            echo '<tr><td>Host</td><td>' . htmlspecialchars($host) . '</td></tr>';
            echo '</table>';
            
            // Test tables
            echo '<h2>Status Tabel:</h2>';
            $tables = ['users', 'products', 'transactions', 'transaction_items'];
            echo '<table>';
            echo '<tr><th>Tabel</th><th>Status</th><th>Jumlah Data</th></tr>';
            
            foreach ($tables as $table) {
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM `$table`");
                    $result = $stmt->fetch();
                    $count = $result['total'];
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($table) . '</td>';
                    echo '<td><span style="color: green;">✓ Ada</span></td>';
                    echo '<td>' . $count . ' record(s)</td>';
                    echo '</tr>';
                } catch(Exception $e) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($table) . '</td>';
                    echo '<td><span style="color: red;">✗ Tidak ada</span></td>';
                    echo '<td>-</td>';
                    echo '</tr>';
                }
            }
            echo '</table>';
            
            // Test users
            echo '<h2>Data User:</h2>';
            try {
                $stmt = $pdo->query("SELECT id, username, nama_lengkap, role FROM users LIMIT 5");
                $users = $stmt->fetchAll();
                
                if (count($users) > 0) {
                    echo '<table>';
                    echo '<tr><th>ID</th><th>Username</th><th>Nama</th><th>Role</th></tr>';
                    foreach ($users as $user) {
                        echo '<tr>';
                        echo '<td>' . $user['id'] . '</td>';
                        echo '<td>' . htmlspecialchars($user['username']) . '</td>';
                        echo '<td>' . htmlspecialchars($user['nama_lengkap']) . '</td>';
                        echo '<td>' . htmlspecialchars($user['role']) . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                } else {
                    echo '<div class="info">Tidak ada data user. Silakan import database_structure.sql</div>';
                }
            } catch(Exception $e) {
                echo '<div class="error">Error membaca data user: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            
            // PHP Info
            echo '<h2>Informasi PHP:</h2>';
            echo '<table>';
            echo '<tr><td>PHP Version</td><td>' . phpversion() . '</td></tr>';
            echo '<tr><td>PDO MySQL</td><td>' . (extension_loaded('pdo_mysql') ? '✓ Terinstall' : '✗ Tidak terinstall') . '</td></tr>';
            echo '</table>';
            
        } catch(PDOException $e) {
            echo '<div class="error">❌ Koneksi database GAGAL!</div>';
            echo '<div class="error">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            
            echo '<h2>🔧 Troubleshooting:</h2>';
            echo '<div class="info">';
            echo '<strong>Kemungkinan masalah:</strong><br>';
            echo '1. Database name salah (pastikan format lengkap: if0_xxxxx_namadb)<br>';
            echo '2. Username salah (pastikan format lengkap: if0_xxxxx_user)<br>';
            echo '3. Password salah<br>';
            echo '4. Database belum dibuat di MySQL Databases<br>';
            echo '5. Host salah (coba ganti dari "localhost" ke IP server jika diberikan)<br>';
            echo '</div>';
            
            echo '<h2>Langkah Perbaikan:</h2>';
            echo '<ol>';
            echo '<li>Buka InfinityFree Control Panel</li>';
            echo '<li>Klik "MySQL Databases"</li>';
            echo '<li>Pastikan database sudah dibuat</li>';
            echo '<li>Copy database name lengkap (format: if0_xxxxx_namadb)</li>';
            echo '<li>Copy username lengkap (format: if0_xxxxx_user)</li>';
            echo '<li>Edit file config/database.production.php dengan data yang benar</li>';
            echo '<li>Refresh halaman ini</li>';
            echo '</ol>';
        }
        ?>
        
        <div class="warning">
            <strong>⚠️ PENTING!</strong><br>
            File ini hanya untuk testing. <strong>HAPUS file test_connection.php</strong> setelah testing selesai untuk keamanan!
        </div>
        
        <div style="margin-top: 20px; padding: 15px; background: #e9ecef; border-radius: 4px;">
            <strong>Langkah selanjutnya:</strong><br>
            1. Jika koneksi berhasil, import file <code>database_structure.sql</code> ke phpMyAdmin<br>
            2. Test login dengan username: <code>admin</code> / password: <code>password</code><br>
            3. Hapus file <code>test_connection.php</code> setelah selesai
        </div>
    </div>
</body>
</html>

