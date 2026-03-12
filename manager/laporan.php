<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
redirectIfNotAuthorized('manajer');

// Get filter parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$report_type = $_GET['report_type'] ?? 'daily';

// Build query based on report type
if ($report_type === 'daily') {
    $sql = "
        SELECT 
            DATE(t.created_at) as period,
            COUNT(*) as total_transaksi,
            SUM(t.total) as total_penjualan,
            AVG(t.total) as rata_rata_transaksi
        FROM transactions t
        WHERE DATE(t.created_at) BETWEEN ? AND ?
        GROUP BY DATE(t.created_at)
        ORDER BY period DESC
    ";
} elseif ($report_type === 'monthly') {
    $sql = "
        SELECT 
            DATE_FORMAT(t.created_at, '%Y-%m') as period,
            COUNT(*) as total_transaksi,
            SUM(t.total) as total_penjualan,
            AVG(t.total) as rata_rata_transaksi
        FROM transactions t
        WHERE DATE(t.created_at) BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(t.created_at, '%Y-%m')
        ORDER BY period DESC
    ";
} else {
    $sql = "
        SELECT 
            DATE_FORMAT(t.created_at, '%Y') as period,
            COUNT(*) as total_transaksi,
            SUM(t.total) as total_penjualan,
            AVG(t.total) as rata_rata_transaksi
        FROM transactions t
        WHERE DATE(t.created_at) BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(t.created_at, '%Y')
        ORDER BY period DESC
    ";
}

$stmt = $pdo->prepare($sql);
$stmt->execute([$start_date, $end_date]);
$reports = $stmt->fetchAll();

// Calculate totals
$total_transaksi = 0;
$total_penjualan = 0;
foreach ($reports as $report) {
    $total_transaksi += $report['total_transaksi'];
    $total_penjualan += $report['total_penjualan'];
}

// Top products report
$stmt = $pdo->prepare("
    SELECT 
        p.nama_produk,
        SUM(ti.quantity) as total_terjual,
        SUM(ti.subtotal) as total_penjualan,
        p.stok
    FROM transaction_items ti
    JOIN products p ON ti.product_id = p.id
    JOIN transactions t ON ti.transaction_id = t.id
    WHERE DATE(t.created_at) BETWEEN ? AND ?
    GROUP BY p.id
    ORDER BY total_terjual DESC
    LIMIT 10
");
$stmt->execute([$start_date, $end_date]);
$top_products = $stmt->fetchAll();

// Payment method summary
$stmt = $pdo->prepare("
    SELECT 
        metode_pembayaran,
        COUNT(*) as total_transaksi,
        SUM(total) as total_penjualan
    FROM transactions
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY metode_pembayaran
    ORDER BY total_penjualan DESC
");
$stmt->execute([$start_date, $end_date]);
$payment_summary = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - M Mart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                    <a href="dashboard.php" class="text-indigo-100 hover:bg-indigo-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
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
                    <a href="laporan.php" class="bg-indigo-900 text-white group flex items-center px-2 py-2 text-sm font-medium rounded-md">
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
                    <div class="md:flex md:items-center md:justify-between mb-6">
                        <div class="flex-1 min-w-0">
                            <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                                Laporan Penjualan
                            </h1>
                            <p class="mt-1 text-sm text-gray-500">Analisis dan statistik penjualan toko</p>
                        </div>
                        <div class="mt-4 flex md:mt-0 md:ml-4">
                            <button onclick="window.print()" class="ml-3 inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-print mr-2"></i>
                                Cetak Laporan
                            </button>
                        </div>
                    </div>

                    <!-- Filter Section -->
                    <div class="bg-white shadow rounded-lg mb-6">
                        <div class="px-4 py-5 sm:p-6">
                            <form method="GET" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                                <div>
                                    <label for="report_type" class="block text-sm font-medium text-gray-700">Jenis Laporan</label>
                                    <select name="report_type" id="report_type" 
                                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="daily" <?php echo $report_type === 'daily' ? 'selected' : ''; ?>>Harian</option>
                                        <option value="monthly" <?php echo $report_type === 'monthly' ? 'selected' : ''; ?>>Bulanan</option>
                                        <option value="yearly" <?php echo $report_type === 'yearly' ? 'selected' : ''; ?>>Tahunan</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="start_date" class="block text-sm font-medium text-gray-700">Tanggal Mulai</label>
                                    <input type="date" name="start_date" id="start_date" value="<?php echo $start_date; ?>"
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label for="end_date" class="block text-sm font-medium text-gray-700">Tanggal Akhir</label>
                                    <input type="date" name="end_date" id="end_date" value="<?php echo $end_date; ?>"
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                <div class="flex items-end">
                                    <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        <i class="fas fa-filter mr-2"></i>
                                        Terapkan Filter
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Summary Stats -->
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3 mb-6">
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-receipt text-3xl text-blue-500"></i>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Total Transaksi</dt>
                                            <dd class="text-lg font-medium text-gray-900"><?php echo $total_transaksi; ?></dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-money-bill-wave text-3xl text-green-500"></i>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Total Penjualan</dt>
                                            <dd class="text-lg font-medium text-gray-900">Rp <?php echo number_format($total_penjualan, 0, ',', '.'); ?></dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-chart-line text-3xl text-purple-500"></i>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Rata-rata Transaksi</dt>
                                            <dd class="text-lg font-medium text-gray-900">
                                                Rp <?php echo number_format($total_transaksi ? $total_penjualan / $total_transaksi : 0, 0, ',', '.'); ?>
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Reports -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                        <!-- Sales Report -->
                        <div class="lg:col-span-2 bg-white shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    Laporan Penjualan <?php echo $report_type === 'daily' ? 'Harian' : ($report_type === 'monthly' ? 'Bulanan' : 'Tahunan'); ?>
                                </h3>
                                <?php if (empty($reports)): ?>
                                    <p class="text-gray-500 text-center py-4">Tidak ada data untuk periode yang dipilih</p>
                                <?php else: ?>
                                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 rounded-lg">
                                        <table class="min-w-full divide-y divide-gray-300">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Periode</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaksi</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Penjualan</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rata-rata</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <?php foreach ($reports as $report): ?>
                                                    <tr>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                            <?php 
                                                            if ($report_type === 'daily') {
                                                                echo date('d M Y', strtotime($report['period']));
                                                            } elseif ($report_type === 'monthly') {
                                                                echo date('M Y', strtotime($report['period'] . '-01'));
                                                            } else {
                                                                echo $report['period'];
                                                            }
                                                            ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                            <?php echo $report['total_transaksi']; ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                            Rp <?php echo number_format($report['total_penjualan'], 0, ',', '.'); ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                            Rp <?php echo number_format($report['rata_rata_transaksi'], 0, ',', '.'); ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Payment Summary -->
                        <div class="bg-white shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Ringkasan Pembayaran</h3>
                                <?php if (empty($payment_summary)): ?>
                                    <p class="text-gray-500 text-center py-4">Tidak ada data</p>
                                <?php else: ?>
                                    <div class="space-y-4">
                                        <?php foreach ($payment_summary as $payment): ?>
                                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                                <div class="flex items-center">
                                                    <span class="flex-shrink-0 w-8 h-8 bg-indigo-100 text-indigo-800 rounded-full flex items-center justify-center text-sm font-medium">
                                                        <i class="fas fa-<?php echo $payment['metode_pembayaran'] === 'cash' ? 'money-bill' : ($payment['metode_pembayaran'] === 'debit' ? 'credit-card' : 'university'); ?>"></i>
                                                    </span>
                                                    <div class="ml-3">
                                                        <p class="text-sm font-medium text-gray-900"><?php echo ucfirst($payment['metode_pembayaran']); ?></p>
                                                        <p class="text-sm text-gray-500"><?php echo $payment['total_transaksi']; ?> transaksi</p>
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-sm font-medium text-gray-900">Rp <?php echo number_format($payment['total_penjualan'], 0, ',', '.'); ?></p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Top Products -->
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">10 Produk Terlaris</h3>
                            <?php if (empty($top_products)): ?>
                                <p class="text-gray-500 text-center py-4">Tidak ada data produk terlaris</p>
                            <?php else: ?>
                                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 rounded-lg">
                                    <table class="min-w-full divide-y divide-gray-300">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produk</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Terjual</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stok</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Penjualan</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php foreach ($top_products as $product): ?>
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['nama_produk']); ?></div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        <?php echo $product['total_terjual']; ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                            <?php echo $product['stok'] <= 10 ? 'bg-red-100 text-red-800' : 
                                                                   ($product['stok'] <= 20 ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'); ?>">
                                                            <?php echo $product['stok']; ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        Rp <?php echo number_format($product['total_penjualan'], 0, ',', '.'); ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>