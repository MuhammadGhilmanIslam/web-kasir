<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
redirectIfNotAuthorized('kasir');

$user = getCurrentUser($pdo);

// Stats untuk dashboard
$stmt = $pdo->prepare("
    SELECT 
        (SELECT COUNT(*) FROM products WHERE stok > 0) as total_produk,
        (SELECT COUNT(*) FROM transactions WHERE DATE(created_at) = CURDATE() AND user_id = ?) as transaksi_hari_ini,
        (SELECT COALESCE(SUM(total), 0) FROM transactions WHERE DATE(created_at) = CURDATE() AND user_id = ?) as penjualan_hari_ini,
        (SELECT COUNT(*) FROM products WHERE stok <= stok_minimum) as stok_minimum
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$stats = $stmt->fetch();

// Transaksi terbaru
$stmt = $pdo->prepare("
    SELECT t.*, u.nama_lengkap 
    FROM transactions t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.user_id = ? 
    ORDER BY t.created_at DESC 
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$transaksi_terbaru = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Kasir - M Mart</title>
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
                    <a href="dashboard.php" class="bg-blue-900 text-white group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-chart-pie mr-3 text-blue-300"></i>
                        Dashboard
                    </a>
                    <a href="transaksi.php" class="text-blue-100 hover:bg-blue-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-shopping-cart mr-3 text-blue-300"></i>
                        Transaksi
                    </a>
                    <a href="produk.php" class="text-blue-100 hover:bg-blue-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-boxes mr-3 text-blue-300"></i>
                        Produk
                    </a>
                    <a href="riwayat.php" class="text-blue-100 hover:bg-blue-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-history mr-3 text-blue-300"></i>
                        Riwayat Transaksi
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
        <!-- Top bar -->
        <div class="sticky top-0 z-10 md:hidden pl-1 pt-1 sm:pl-3 sm:pt-3 bg-gray-100">
            <button type="button" class="-ml-0.5 -mt-0.5 h-12 w-12 inline-flex items-center justify-center rounded-md text-gray-500 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <main class="flex-1">
            <div class="py-6">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                    <!-- Header -->
                    <div class="md:flex md:items-center md:justify-between mb-6">
                        <div class="flex-1 min-w-0">
                            <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                                Dashboard Kasir
                            </h1>
                            <p class="mt-1 text-sm text-gray-500">Ringkasan aktivitas dan statistik hari ini</p>
                        </div>
                        <div class="mt-4 flex md:mt-0 md:ml-4">
                            <a href="transaksi.php" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-plus mr-2"></i>
                                Transaksi Baru
                            </a>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
                        <!-- Total Produk -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-boxes text-3xl text-blue-500"></i>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Total Produk</dt>
                                            <dd class="text-lg font-medium text-gray-900"><?php echo $stats['total_produk']; ?></dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Transaksi Hari Ini -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-shopping-cart text-3xl text-green-500"></i>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Transaksi Hari Ini</dt>
                                            <dd class="text-lg font-medium text-gray-900"><?php echo $stats['transaksi_hari_ini']; ?></dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Penjualan Hari Ini -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-money-bill-wave text-3xl text-yellow-500"></i>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Penjualan Hari Ini</dt>
                                            <dd class="text-lg font-medium text-gray-900">Rp <?php echo number_format($stats['penjualan_hari_ini'], 0, ',', '.'); ?></dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Stok Minimum -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-exclamation-triangle text-3xl text-red-500"></i>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Stok Minimum</dt>
                                            <dd class="text-lg font-medium text-gray-900"><?php echo $stats['stok_minimum']; ?></dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Transactions -->
                    <div class="bg-white shadow rounded-lg mb-8">
                        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Transaksi Terbaru</h3>
                            <p class="mt-1 max-w-2xl text-sm text-gray-500">5 transaksi terakhir yang Anda lakukan</p>
                        </div>
                        <div class="px-4 py-5 sm:p-6">
                            <?php if (empty($transaksi_terbaru)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-receipt text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-gray-500">Belum ada transaksi hari ini</p>
                                </div>
                            <?php else: ?>
                                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 rounded-lg">
                                    <table class="min-w-full divide-y divide-gray-300">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php foreach ($transaksi_terbaru as $transaksi): ?>
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $transaksi['kode_transaksi']; ?></td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('d/m/Y H:i', strtotime($transaksi['created_at'])); ?></td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Rp <?php echo number_format($transaksi['total'], 0, ',', '.'); ?></td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                            <?php echo ucfirst($transaksi['status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-barcode text-3xl text-blue-500"></i>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-lg font-medium text-gray-900">Scan Barcode</h3>
                                        <p class="mt-1 text-sm text-gray-500">Input produk cepat dengan scanner</p>
                                    </div>
                                </div>
                                <div class="mt-6">
                                    <a href="transaksi.php" class="w-full flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                        Mulai Scan
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-box text-3xl text-green-500"></i>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-lg font-medium text-gray-900">Kelola Produk</h3>
                                        <p class="mt-1 text-sm text-gray-500">Lihat dan kelola inventori produk</p>
                                    </div>
                                </div>
                                <div class="mt-6">
                                    <a href="produk.php" class="w-full flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                        Kelola Produk
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-history text-3xl text-purple-500"></i>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-lg font-medium text-gray-900">Riwayat Transaksi</h3>
                                        <p class="mt-1 text-sm text-gray-500">Lihat semua transaksi yang telah dilakukan</p>
                                    </div>
                                </div>
                                <div class="mt-6">
                                    <a href="riwayat.php" class="w-full flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                                        Lihat Riwayat
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Mobile menu -->
    <div class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200">
        <div class="flex justify-around">
            <a href="dashboard.php" class="flex flex-col items-center py-2 text-blue-600">
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
            <a href="riwayat.php" class="flex flex-col items-center py-2 text-gray-500">
                <i class="fas fa-history text-lg"></i>
                <span class="text-xs mt-1">Riwayat</span>
            </a>
        </div>
    </div>
</body>
</html>