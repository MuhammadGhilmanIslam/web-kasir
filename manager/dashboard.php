<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
redirectIfNotAuthorized('manajer');

// Get statistics for dashboard
$today = date('Y-m-d');
$current_month = date('Y-m');
$current_year = date('Y');

// Basic stats
$stmt = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM products WHERE status = 'active') as total_produk,
        (SELECT COUNT(*) FROM users WHERE role = 'kasir') as total_kasir,
        (SELECT COUNT(*) FROM transactions WHERE DATE(created_at) = '$today') as transaksi_hari_ini,
        (SELECT COALESCE(SUM(total), 0) FROM transactions WHERE DATE(created_at) = '$today') as penjualan_hari_ini,
        (SELECT COUNT(*) FROM products WHERE stok <= stok_minimum) as stok_minimum
");
$stats = $stmt->fetch();

// Monthly sales data for chart
$monthly_sales = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total), 0) as total FROM transactions WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
    $stmt->execute([$month]);
    $sales = $stmt->fetch()['total'];
    $monthly_sales[] = [
        'month' => date('M Y', strtotime($month)),
        'total' => $sales
    ];
}

// Top selling products
$stmt = $pdo->query("
    SELECT p.nama_produk, SUM(ti.quantity) as total_terjual, SUM(ti.subtotal) as total_penjualan
    FROM transaction_items ti
    JOIN products p ON ti.product_id = p.id
    JOIN transactions t ON ti.transaction_id = t.id
    WHERE MONTH(t.created_at) = MONTH(CURRENT_DATE())
    GROUP BY p.id
    ORDER BY total_terjual DESC
    LIMIT 5
");
$top_products = $stmt->fetchAll();

// Recent transactions
$stmt = $pdo->query("
    SELECT t.*, u.nama_lengkap 
    FROM transactions t 
    JOIN users u ON t.user_id = u.id 
    ORDER BY t.created_at DESC 
    LIMIT 5
");
$recent_transactions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Manager - M Mart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CSS -->
<link rel="stylesheet" href="../assets/css/style.css">

<!-- Favicon -->
<link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">

<!-- JavaScript -->
<script src="../assets/js/main.js"></script>
<script src="../assets/js/chart.js"></script>
<script src="../assets/js/form-validation.js"></script>
</head>
<body class="h-full">
    <!-- Sidebar -->
    <div class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0">
        <div class="flex-1 flex flex-col min-h-0 bg-indigo-800">
            <!-- Logo -->
            <div class="flex items-center h-16 flex-shrink-0 px-4 bg-indigo-900">
                <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-cash-register text-indigo-600"></i>
                </div>
                <div class="text-white">
                    <div class="font-bold text-lg">M Mart</div>
                    <div class="text-indigo-200 text-xs">Manager Mode</div>
                </div>
            </div>
            
            <!-- Navigation -->
            <div class="flex-1 flex flex-col overflow-y-auto pt-5 pb-4">
                <nav class="mt-5 flex-1 px-2 space-y-1">
                    <a href="dashboard.php" class="bg-indigo-900 text-white group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-chart-pie mr-3 text-indigo-300"></i>
                        Dashboard
                    </a>
                    <a href="transaksi.php" class="text-indigo-100 hover:bg-indigo-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-shopping-cart mr-3 text-indigo-300"></i>
                        Transaksi
                    </a>
                    <a href="produk.php" class="text-indigo-100 hover:bg-indigo-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-boxes mr-3 text-indigo-300"></i>
                        Produk
                    </a>
                    <a href="laporan.php" class="text-indigo-100 hover:bg-indigo-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-chart-bar mr-3 text-indigo-300"></i>
                        Laporan
                    </a>
                    <a href="user.php" class="text-indigo-100 hover:bg-indigo-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-users mr-3 text-indigo-300"></i>
                        Manajemen User
                    </a>
                </nav>
            </div>
            
            <!-- User section -->
            <div class="flex-shrink-0 flex border-t border-indigo-700 p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-indigo-600 rounded-full flex items-center justify-center">
                            <i class="fas fa-user-shield text-white text-sm"></i>
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-white"><?php echo $_SESSION['nama_lengkap']; ?></p>
                        <a href="../logout.php" class="text-xs font-medium text-indigo-200 hover:text-white">Keluar</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <div class="md:pl-64 flex flex-col flex-1">
        <main class="flex-1">
            <div class="py-6">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                    <!-- Header -->
                    <div class="mb-8">
                        <h1 class="text-2xl font-bold text-gray-900">Dashboard Manager</h1>
                        <p class="text-gray-600">Overview sistem dan performa toko</p>
                    </div>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
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

                        <!-- Total Kasir -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-users text-3xl text-green-500"></i>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Total Kasir</dt>
                                            <dd class="text-lg font-medium text-gray-900"><?php echo $stats['total_kasir']; ?></dd>
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
                                        <i class="fas fa-shopping-cart text-3xl text-yellow-500"></i>
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
                                        <i class="fas fa-money-bill-wave text-3xl text-purple-500"></i>
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
                    </div>

                    <!-- Charts and Data -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                        <!-- Sales Chart -->
                        <div class="bg-white shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Penjualan 6 Bulan Terakhir</h3>
                                <canvas id="salesChart" width="400" height="200"></canvas>
                            </div>
                        </div>

                        <!-- Top Products -->
                        <div class="bg-white shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Produk Terlaris Bulan Ini</h3>
                                <?php if (empty($top_products)): ?>
                                    <p class="text-gray-500 text-center py-4">Belum ada data penjualan</p>
                                <?php else: ?>
                                    <div class="space-y-3">
                                        <?php foreach ($top_products as $index => $product): ?>
                                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                                <div class="flex items-center">
                                                    <span class="flex-shrink-0 w-8 h-8 bg-indigo-100 text-indigo-800 rounded-full flex items-center justify-center text-sm font-medium">
                                                        <?php echo $index + 1; ?>
                                                    </span>
                                                    <div class="ml-3">
                                                        <p class="text-sm font-medium text-gray-900"><?php echo $product['nama_produk']; ?></p>
                                                        <p class="text-sm text-gray-500"><?php echo $product['total_terjual']; ?> terjual</p>
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-sm font-medium text-gray-900">Rp <?php echo number_format($product['total_penjualan'], 0, ',', '.'); ?></p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Transactions and Stock Alert -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Recent Transactions -->
                        <div class="bg-white shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Transaksi Terbaru</h3>
                                <?php if (empty($recent_transactions)): ?>
                                    <p class="text-gray-500 text-center py-4">Belum ada transaksi</p>
                                <?php else: ?>
                                    <div class="space-y-3">
                                        <?php foreach ($recent_transactions as $transaction): ?>
                                            <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900"><?php echo $transaction['kode_transaksi']; ?></p>
                                                    <p class="text-sm text-gray-500"><?php echo $transaction['nama_lengkap']; ?> • <?php echo date('H:i', strtotime($transaction['created_at'])); ?></p>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-sm font-medium text-gray-900">Rp <?php echo number_format($transaction['total'], 0, ',', '.'); ?></p>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                        <?php echo ucfirst($transaction['metode_pembayaran']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Stock Alert -->
                        <div class="bg-white shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Peringatan Stok</h3>
                                <?php if ($stats['stok_minimum'] == 0): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-check-circle text-3xl text-green-400 mb-2"></i>
                                        <p class="text-green-600">Semua stok dalam kondisi aman</p>
                                    </div>
                                <?php else: ?>
                                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                                        <div class="flex">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                                            </div>
                                            <div class="ml-3">
                                                <h3 class="text-sm font-medium text-yellow-800">
                                                    <?php echo $stats['stok_minimum']; ?> produk perlu restock
                                                </h3>
                                                <div class="mt-2 text-sm text-yellow-700">
                                                    <p>Beberapa produk mendekati stok minimum. Segera lakukan restock.</p>
                                                </div>
                                                <div class="mt-3">
                                                    <a href="produk.php" class="text-sm font-medium text-yellow-800 underline hover:text-yellow-900">
                                                        Lihat daftar produk <span aria-hidden="true">&rarr;</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Sales Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(salesCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($monthly_sales, 'month')); ?>,
                datasets: [{
                    label: 'Total Penjualan',
                    data: <?php echo json_encode(array_column($monthly_sales, 'total')); ?>,
                    backgroundColor: 'rgba(79, 70, 229, 0.5)',
                    borderColor: 'rgba(79, 70, 229, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Penjualan: Rp ' + context.parsed.y.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>