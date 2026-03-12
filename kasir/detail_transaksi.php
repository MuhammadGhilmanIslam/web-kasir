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
    SELECT ti.*, p.nama_produk, p.barcode, p.gambar 
    FROM transaction_items ti 
    JOIN products p ON ti.product_id = p.id 
    WHERE ti.transaction_id = ?
");
$stmt->execute([$transaction_id]);
$items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Transaksi - M Mart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="h-full">
    <!-- Sidebar -->
    <div class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0">
        <div class="flex-1 flex flex-col min-h-0 bg-blue-800">
            <!-- Logo -->
            <div class="flex items-center h-16 flex-shrink-0 px-4 bg-blue-900">
                <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-cash-register text-blue-600"></i>
                </div>
                <div class="text-white">
                    <div class="font-bold text-lg">M Mart</div>
                    <div class="text-blue-200 text-xs">Kasir Mode</div>
                </div>
            </div>
            
            <!-- Navigation -->
            <div class="flex-1 flex flex-col overflow-y-auto pt-5 pb-4">
                <nav class="mt-5 flex-1 px-2 space-y-1">
                    <a href="dashboard.php" class="text-blue-100 hover:bg-blue-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-chart-pie mr-3 text-blue-300"></i>Dashboard
                    </a>
                    <a href="transaksi.php" class="text-blue-100 hover:bg-blue-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-shopping-cart mr-3 text-blue-300"></i>Transaksi
                    </a>
                    <a href="produk.php" class="text-blue-100 hover:bg-blue-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-boxes mr-3 text-blue-300"></i>Produk
                    </a>
                    <a href="riwayat.php" class="bg-blue-900 text-white group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-history mr-3 text-blue-300"></i>Riwayat Transaksi
                    </a>
                </nav>
            </div>
            
            <!-- User section -->
            <div class="flex-shrink-0 flex border-t border-blue-700 p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-white text-sm"></i>
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-white"><?php echo $_SESSION['nama_lengkap']; ?></p>
                        <a href="../logout.php" class="text-xs font-medium text-blue-200 hover:text-white">Keluar</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <div class="md:pl-64 flex flex-col flex-1">
        <main class="flex-1">
            <div class="py-6">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 md:px-8">
                    <!-- Header -->
                    <div class="md:flex md:items-center md:justify-between mb-6">
                        <div class="flex-1 min-w-0">
                            <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                                Detail Transaksi
                            </h1>
                            <p class="mt-1 text-sm text-gray-500">Informasi lengkap transaksi <?php echo $transaction['kode_transaksi']; ?></p>
                        </div>
                        <div class="mt-4 flex md:mt-0 md:ml-4">
                            <a href="struk.php?id=<?php echo $transaction_id; ?>" target="_blank"
                               class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                                <i class="fas fa-print mr-2"></i>Cetak Struk
                            </a>
                        </div>
                    </div>

                    <!-- Transaction Info -->
                    <div class="bg-white shadow rounded-lg mb-6">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Informasi Transaksi</h3>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Kode Transaksi</label>
                                    <p class="mt-1 text-sm text-gray-900"><?php echo $transaction['kode_transaksi']; ?></p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Tanggal & Waktu</label>
                                    <p class="mt-1 text-sm text-gray-900"><?php echo date('d/m/Y H:i:s', strtotime($transaction['created_at'])); ?></p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Kasir</label>
                                    <p class="mt-1 text-sm text-gray-900"><?php echo $transaction['kasir']; ?></p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Metode Pembayaran</label>
                                    <p class="mt-1 text-sm text-gray-900">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            <?php echo $transaction['metode_pembayaran'] === 'cash' ? 'bg-green-100 text-green-800' : 
                                                   ($transaction['metode_pembayaran'] === 'debit' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'); ?>">
                                            <?php echo ucfirst($transaction['metode_pembayaran']); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transaction Items -->
                    <div class="bg-white shadow rounded-lg mb-6">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Detail Produk</h3>
                            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 rounded-lg">
                                <table class="min-w-full divide-y divide-gray-300">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produk</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($items as $item): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        <?php if ($item['gambar']): ?>
                                                            <div class="flex-shrink-0 h-10 w-10">
                                                                <img class="h-10 w-10 rounded object-cover" src="../uploads/<?php echo htmlspecialchars($item['gambar']); ?>" alt="<?php echo htmlspecialchars($item['nama_produk']); ?>">
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="flex-shrink-0 h-10 w-10 bg-gray-200 rounded flex items-center justify-center">
                                                                <i class="fas fa-box text-gray-400"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div class="ml-4">
                                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['nama_produk']); ?></div>
                                                            <?php if ($item['barcode']): ?>
                                                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($item['barcode']); ?></div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    Rp <?php echo number_format($item['harga_satuan'], 0, ',', '.'); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <?php echo $item['quantity']; ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot class="bg-gray-50">
                                        <tr>
                                            <td colspan="3" class="px-6 py-4 text-sm font-medium text-gray-900 text-right">Total</td>
                                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                                Rp <?php echo number_format($transaction['total'], 0, ',', '.'); ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="px-6 py-4 text-sm font-medium text-gray-900 text-right">Jumlah Bayar</td>
                                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                                Rp <?php echo number_format($transaction['jumlah_bayar'], 0, ',', '.'); ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="px-6 py-4 text-sm font-medium text-gray-900 text-right">Kembalian</td>
                                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                                Rp <?php echo number_format($transaction['kembalian'], 0, ',', '.'); ?>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Back Button -->
                    <div class="text-center">
                        <a href="riwayat.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-arrow-left mr-2"></i>Kembali ke Riwayat
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Mobile menu -->
    <div class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200">
        <div class="flex justify-around">
            <a href="dashboard.php" class="flex flex-col items-center py-2 text-gray-500">
                <i class="fas fa-chart-pie text-lg"></i>
                <span class="text-xs mt-1">Dashboard</span>
            </a>
            <a href="transaksi.php" class="flex flex-col items-center py-2 text-gray-500">
                <i class="fas fa-shopping-cart text-lg"></i>
                <span class="text-xs mt-1">Transaksi</span>
            </a>
            <a href="produk.php" class="flex flex-col items-center py-2 text-gray-500">
                <i class="fas fa-boxes text-lg"></i>
                <span class="text-xs mt-1">Produk</span>
            </a>
            <a href="riwayat.php" class="flex flex-col items-center py-2 text-blue-600">
                <i class="fas fa-history text-lg"></i>
                <span class="text-xs mt-1">Riwayat</span>
            </a>
        </div>
    </div>
</body>
</html>