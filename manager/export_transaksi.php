<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
redirectIfNotAuthorized('manajer');

// Get filter parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';
$kasir_filter = $_GET['kasir'] ?? '';

// Build query
$sql = "SELECT t.*, u.nama_lengkap 
        FROM transactions t 
        JOIN users u ON t.user_id = u.id 
        WHERE DATE(t.created_at) BETWEEN ? AND ?";
$params = [$start_date, $end_date];

if ($search) {
    $sql .= " AND (t.kode_transaksi LIKE ? OR u.nama_lengkap LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($kasir_filter) {
    $sql .= " AND t.user_id = ?";
    $params[] = $kasir_filter;
}

$sql .= " ORDER BY t.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="laporan_transaksi_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid black; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .title { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
        .subtitle { font-size: 14px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="title">Laporan Transaksi</div>
    <div class="subtitle">
        Periode: <?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Transaksi</th>
                <th>Kasir</th>
                <th>Tanggal</th>
                <th>Total</th>
                <th>Jumlah Bayar</th>
                <th>Kembalian</th>
                <th>Metode Bayar</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $total_penjualan = 0;
            foreach ($transactions as $index => $transaction): 
                $total_penjualan += $transaction['total'];
            ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo $transaction['kode_transaksi']; ?></td>
                    <td><?php echo $transaction['nama_lengkap']; ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></td>
                    <td>Rp <?php echo number_format($transaction['total'], 0, ',', '.'); ?></td>
                    <td>Rp <?php echo number_format($transaction['jumlah_bayar'], 0, ',', '.'); ?></td>
                    <td>Rp <?php echo number_format($transaction['kembalian'], 0, ',', '.'); ?></td>
                    <td><?php echo ucfirst($transaction['metode_pembayaran']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align: right; font-weight: bold;">Total Penjualan:</td>
                <td colspan="4" style="font-weight: bold;">Rp <?php echo number_format($total_penjualan, 0, ',', '.'); ?></td>
            </tr>
            <tr>
                <td colspan="4" style="text-align: right; font-weight: bold;">Total Transaksi:</td>
                <td colspan="4" style="font-weight: bold;"><?php echo count($transactions); ?></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>