<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
redirectIfNotAuthorized('kasir');

// Get filter parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT t.*, u.nama_lengkap 
        FROM transactions t 
        JOIN users u ON t.user_id = u.id 
        WHERE t.user_id = ? AND DATE(t.created_at) BETWEEN ? AND ?";
$params = [$_SESSION['user_id'], $start_date, $end_date];

if ($search) {
    $sql .= " AND (t.kode_transaksi LIKE ? OR u.nama_lengkap LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY t.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Calculate totals
$total_transaksi = count($transactions);
$total_penjualan = 0;
foreach ($transactions as $transaction) {
    $total_penjualan += $transaction['total'];
}
?>

<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi - M Mart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="h-full">
    <!-- Sidebar (sama seperti halaman sebelumnya) -->
    <div class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0">
        <div class="flex-1 flex flex-col min-h-0 bg-blue-800">
            <div class="flex items-center h-16 flex-shrink-0 px-4 bg-blue-900">
                <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-cash-register text-blue-600"></i>
                </div>
                <div class="text-white">
                    <div class="font-bold text-lg">M Mart</div>
                    <div class="text-blue-200 text-xs">Kasir Mode</div>
                </div>
            </div>
            
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
                <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                    <!-- Header -->
                    <div class="md:flex md:items-center md:justify-between mb-6">
                        <div class="flex-1 min-w-0">
                            <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                                Riwayat Transaksi
                            </h1>
                            <p class="mt-1 text-sm text-gray-500">Lihat riwayat transaksi yang telah Anda lakukan</p>
                        </div>
                    </div>

                    <!-- Stats -->
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
                                        <i class="fas fa-calendar text-3xl text-purple-500"></i>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Periode</dt>
                                            <dd class="text-lg font-medium text-gray-900"><?php echo date('d M Y', strtotime($start_date)); ?> - <?php echo date('d M Y', strtotime($end_date)); ?></dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Section -->
                    <div class="bg-white shadow rounded-lg mb-6">
                        <div class="px-4 py-5 sm:p-6">
                            <form method="GET" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                                <div>
                                    <label for="start_date" class="block text-sm font-medium text-gray-700">Tanggal Mulai</label>
                                    <input type="date" name="start_date" id="start_date" value="<?php echo $start_date; ?>"
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                </div>
                                <div>
                                    <label for="end_date" class="block text-sm font-medium text-gray-700">Tanggal Akhir</label>
                                    <input type="date" name="end_date" id="end_date" value="<?php echo $end_date; ?>"
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                </div>
                                <div>
                                    <label for="search" class="block text-sm font-medium text-gray-700">Cari Transaksi</label>
                                    <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>"
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                           placeholder="Kode transaksi...">
                                </div>
                                <div class="flex items-end">
                                    <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <i class="fas fa-filter mr-2"></i>
                                        Filter
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Transactions Table -->
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <?php if (empty($transactions)): ?>
                                <div class="text-center py-8">
                                    <i class="fas fa-receipt text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-gray-500">Tidak ada transaksi ditemukan</p>
                                    <p class="text-sm text-gray-400 mt-1">Coba ubah periode atau kata kunci pencarian</p>
                                </div>
                            <?php else: ?>
                                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 rounded-lg">
                                    <table class="min-w-full divide-y divide-gray-300">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode Transaksi</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bayar</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kembalian</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Metode</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php foreach ($transactions as $transaction): ?>
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                        <?php echo $transaction['kode_transaksi']; ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        Rp <?php echo number_format($transaction['total'], 0, ',', '.'); ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        Rp <?php echo number_format($transaction['jumlah_bayar'], 0, ',', '.'); ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        Rp <?php echo number_format($transaction['kembalian'], 0, ',', '.'); ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                            <?php echo $transaction['metode_pembayaran'] === 'cash' ? 'bg-green-100 text-green-800' : 
                                                                   ($transaction['metode_pembayaran'] === 'debit' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'); ?>">
                                                            <?php echo ucfirst($transaction['metode_pembayaran']); ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                        <a href="struk.php?id=<?php echo $transaction['id']; ?>" 
                                                           target="_blank"
                                                           class="text-blue-600 hover:text-blue-900 mr-3">
                                                            <i class="fas fa-print"></i> Struk
                                                        </a>
                                                        <a href="detail_transaksi.php?id=<?php echo $transaction['id']; ?>" 
                                                           class="text-green-600 hover:text-green-900">
                                                            <i class="fas fa-eye"></i> Detail
                                                        </a>
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