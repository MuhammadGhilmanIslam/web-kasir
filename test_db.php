<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=kasir_modern', 'root', '');
    echo "Database connection: OK<br>";
    
    // Test if tables exist
    $tables = ['users', 'products', 'transactions', 'transaction_items'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "Table '$table': EXISTS<br>";
        } else {
            echo "Table '$table': NOT FOUND<br>";
        }
    }
    
} catch(Exception $e) {
    echo "Database error: " . $e->getMessage();
}
?>
