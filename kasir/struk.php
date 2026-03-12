<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
redirectIfNotAuthorized('kasir');

if (!isset($_GET['id'])) {
    header('Location: riwayat.php');
    exit;
}

$transaction_id = $_GET['id'];

// Get transaction data
$stmt = $pdo->prepare("
    SELECT t.*, u.nama_lengkap as kasir 
    FROM transactions t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.id = ? AND t.user_id = ?
");
$stmt->execute([$transaction_id, $_SESSION['user_id']]);
$transaction = $stmt->fetch();

if (!$transaction) {
    die("Transaksi tidak ditemukan!");
}

// Get transaction items
$stmt = $pdo->prepare("
    SELECT ti.*, p.nama_produk, p.barcode 
    FROM transaction_items ti 
    JOIN products p ON ti.product_id = p.id 
    WHERE ti.transaction_id = ?
");
$stmt->execute([$transaction_id]);
$items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Transaksi - M Mart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            .struk-container, .struk-container * {
                visibility: visible;
            }
            .struk-container {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                background: white;
                padding: 0;
                margin: 0;
            }
            .no-print {
                display: none !important;
            }
        }
        
        .struk-container {
            max-width: 300px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
        
        .struk-header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        
        .struk-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            border-bottom: 1px dotted #ddd;
            padding-bottom: 3px;
        }
        
        .struk-total {
            border-top: 2px solid #000;
            padding-top: 10px;
            margin-top: 10px;
            font-weight: bold;
        }
        
        .barcode {
            text-align: center;
            margin: 10px 0;
            font-family: 'Libre Barcode 39', monospace;
            font-size: 24px;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto py-6">
        <!-- Print Controls -->
        <div class="no-print text-center mb-6">
            <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mr-2">
                <i class="fas fa-print mr-2"></i>Cetak Struk
            </button>
            <a href="riwayat.php" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-arrow-left mr-2"></i>Kembali
            </a>
        </div>

        <!-- Receipt -->
        <div class="struk-container shadow-lg">
            <div class="struk-header">
                <h2 class="text-xl font-bold">M MART</h2>
                <p>Jl. Contoh No. 123</p>
                <p>Telp: (021) 123-4567</p>
                <div class="barcode">
                    *<?php echo $transaction['kode_transaksi']; ?>*
                </div>
            </div>
            
            <div class="struk-info mb-4">
                <p><strong>No. Transaksi:</strong> <?php echo $transaction['kode_transaksi']; ?></p>
                <p><strong>Tanggal:</strong> <?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></p>
                <p><strong>Kasir:</strong> <?php echo $transaction['kasir']; ?></p>
            </div>
            
            <div class="struk-items mb-4">
                <?php foreach ($items as $item): ?>
                    <div class="struk-item">
                        <div class="item-info">
                            <div class="item-name"><?php echo $item['nama_produk']; ?></div>
                            <div class="item-details text-xs">
                                <?php echo $item['quantity']; ?> x Rp <?php echo number_format($item['harga_satuan'], 0, ',', '.'); ?>
                            </div>
                        </div>
                        <div class="item-subtotal">
                            Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="struk-total">
                <div class="struk-item">
                    <span>Total:</span>
                    <span>Rp <?php echo number_format($transaction['total'], 0, ',', '.'); ?></span>
                </div>
                <div class="struk-item">
                    <span>Bayar:</span>
                    <span>Rp <?php echo number_format($transaction['jumlah_bayar'], 0, ',', '.'); ?></span>
                </div>
                <div class="struk-item">
                    <span>Kembali:</span>
                    <span>Rp <?php echo number_format($transaction['kembalian'], 0, ',', '.'); ?></span>
                </div>
                <div class="struk-item">
                    <span>Metode:</span>
                    <span><?php echo strtoupper($transaction['metode_pembayaran']); ?></span>
                </div>
            </div>
            
            <div class="struk-footer text-center mt-6 pt-4 border-t border-dashed">
                <p>Terima kasih atas kunjungan Anda!</p>
                <p class="text-xs">Barang yang sudah dibeli tidak dapat ditukar/dikembalikan</p>
            </div>
        </div>
    </div>

    <script>
        // Auto print when page loads
        window.onload = function() {
            // Uncomment line below to auto-print
            // window.print();
        };
    </script>
</body>
</html>