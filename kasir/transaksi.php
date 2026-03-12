<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
redirectIfNotAuthorized('kasir');

// Initialize cart session
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle add to cart via barcode
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scan_barcode'])) {
    $barcode = trim($_POST['barcode']);
    $quantity = intval($_POST['quantity']) ?: 1;
    
    if (!empty($barcode)) {
        // Debug: Check if barcode exists
        $stmt = $pdo->prepare("SELECT * FROM products WHERE barcode = ? AND status = 'active'");
        $stmt->execute([$barcode]);
        $product = $stmt->fetch();
        
        if ($product) {
            // Check stock availability
            if ($product['stok'] >= $quantity) {
                $item_key = array_search($product['id'], array_column($_SESSION['cart'], 'product_id'));
                
                if ($item_key !== false) {
                    // Update quantity if product already in cart
                    $_SESSION['cart'][$item_key]['quantity'] += $quantity;
                    $_SESSION['cart'][$item_key]['subtotal'] = $_SESSION['cart'][$item_key]['quantity'] * $product['harga'];
                } else {
                    // Add new item to cart
                    $_SESSION['cart'][] = [
                        'product_id' => $product['id'],
                        'nama_produk' => $product['nama_produk'],
                        'harga' => $product['harga'],
                        'quantity' => $quantity,
                        'subtotal' => $product['harga'] * $quantity,
                        'barcode' => $product['barcode'],
                        'gambar' => $product['gambar']
                    ];
                }
                
                $_SESSION['success'] = "Produk '{$product['nama_produk']}' berhasil ditambahkan ke keranjang!";
            } else {
                $_SESSION['error'] = "Stok tidak mencukupi! Stok tersedia: {$product['stok']}, yang diminta: {$quantity}";
            }
        } else {
            $_SESSION['error'] = "Produk dengan barcode '$barcode' tidak ditemukan!";
        }
    } else {
        $_SESSION['error'] = "Barcode tidak boleh kosong!";
    }
    
    // Redirect to prevent form resubmission
    header('Location: transaksi.php');
    exit;
}

// Handle add to cart via product selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $product_id = $_POST['product_id'];
    $quantity = intval($_POST['manual_quantity']) ?: 1;
    
    if (!empty($product_id)) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if ($product) {
            if ($product['stok'] >= $quantity) {
                $item_key = array_search($product_id, array_column($_SESSION['cart'], 'product_id'));
                
                if ($item_key !== false) {
                    $_SESSION['cart'][$item_key]['quantity'] += $quantity;
                    $_SESSION['cart'][$item_key]['subtotal'] = $_SESSION['cart'][$item_key]['quantity'] * $product['harga'];
                } else {
                    $_SESSION['cart'][] = [
                        'product_id' => $product['id'],
                        'nama_produk' => $product['nama_produk'],
                        'harga' => $product['harga'],
                        'quantity' => $quantity,
                        'subtotal' => $product['harga'] * $quantity,
                        'barcode' => $product['barcode'],
                        'gambar' => $product['gambar']
                    ];
                }
                
                $_SESSION['success'] = "Produk '{$product['nama_produk']}' berhasil ditambahkan ke keranjang!";
            } else {
                $_SESSION['error'] = "Stok tidak mencukupi! Stok tersedia: {$product['stok']}, yang diminta: {$quantity}";
            }
        } else {
            $_SESSION['error'] = "Produk tidak ditemukan!";
        }
    } else {
        $_SESSION['error'] = "Pilih produk terlebih dahulu!";
    }
    
    // Redirect to prevent form resubmission
    header('Location: transaksi.php');
    exit;
}

// Handle remove from cart
if (isset($_GET['remove_item'])) {
    $index = $_GET['remove_item'];
    if (isset($_SESSION['cart'][$index])) {
        array_splice($_SESSION['cart'], $index, 1);
        $_SESSION['success'] = "Item berhasil dihapus dari keranjang!";
    }
    header('Location: transaksi.php');
    exit;
}

// Handle clear cart
if (isset($_GET['clear_cart'])) {
    $_SESSION['cart'] = [];
    $_SESSION['success'] = "Keranjang berhasil dikosongkan!";
    header('Location: transaksi.php');
    exit;
}

// Handle save transaction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_transaction'])) {
    if (empty($_SESSION['cart'])) {
        $_SESSION['error'] = "Keranjang belanja kosong!";
    } else {
        try {
            $pdo->beginTransaction();
            
            $total = 0;
            foreach ($_SESSION['cart'] as $item) {
                $total += $item['subtotal'];
            }
            
            $jumlah_bayar = floatval(str_replace(['Rp', '.', ' '], '', $_POST['jumlah_bayar']));
            $kembalian = $jumlah_bayar - $total;
            $metode_pembayaran = $_POST['metode_pembayaran'];
            
            if ($jumlah_bayar < $total) {
                throw new Exception("Jumlah bayar kurang dari total!");
            }
            
            // Generate transaction code
            $kode_transaksi = generateKodeTransaksi($pdo);
            
            // Save transaction
            $stmt = $pdo->prepare("INSERT INTO transactions (kode_transaksi, user_id, total, jumlah_bayar, kembalian, metode_pembayaran) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$kode_transaksi, $_SESSION['user_id'], $total, $jumlah_bayar, $kembalian, $metode_pembayaran]);
            $transaction_id = $pdo->lastInsertId();
            
            // Save transaction items and update stock
            foreach ($_SESSION['cart'] as $item) {
                $stmt = $pdo->prepare("INSERT INTO transaction_items (transaction_id, product_id, quantity, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$transaction_id, $item['product_id'], $item['quantity'], $item['harga'], $item['subtotal']]);
                
                // Update product stock
                $stmt = $pdo->prepare("UPDATE products SET stok = stok - ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }
            
            $pdo->commit();
            
            $_SESSION['transaction_success'] = $transaction_id;
            $_SESSION['cart'] = [];
            
            header('Location: struk.php?id=' . $transaction_id);
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Transaksi gagal: " . $e->getMessage();
        }
    }
}

// Get products for manual selection
$stmt = $pdo->query("SELECT * FROM products WHERE status = 'active' AND stok > 0 ORDER BY nama_produk");
$products = $stmt->fetchAll();

// Calculate cart total
$cart_total = 0;
foreach ($_SESSION['cart'] as $item) {
    $cart_total += $item['subtotal'];
}
?>

<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi - M Mart</title>
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
                        <i class="fas fa-chart-pie mr-3 text-blue-300"></i>
                        Dashboard
                    </a>
                    <a href="transaksi.php" class="bg-blue-900 text-white group flex items-center px-2 py-2 text-sm font-medium rounded-md">
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
        <main class="flex-1">
            <div class="py-6">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                    <!-- Header -->
                    <div class="md:flex md:items-center md:justify-between mb-6">
                        <div class="flex-1 min-w-0">
                            <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                                Transaksi Kasir
                            </h1>
                            <p class="mt-1 text-sm text-gray-500">Lakukan transaksi penjualan dengan scan barcode atau input manual</p>
                        </div>
                    </div>

                    <!-- Notifications -->
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="rounded-md bg-green-50 p-4 mb-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check-circle text-green-400"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-green-800"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="rounded-md bg-red-50 p-4 mb-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-circle text-red-400"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-red-800"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Input Section -->
                        <div class="space-y-6">
                            <!-- Barcode Scanner -->
                            <div class="bg-white shadow rounded-lg">
                                <div class="px-4 py-5 sm:p-6">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                        <i class="fas fa-barcode mr-2"></i>Scan Barcode
                                    </h3>
                                    <form method="POST" class="space-y-4">
                                        <div>
                                            <label for="barcode" class="block text-sm font-medium text-gray-700">Barcode Produk</label>
                                            <input type="text" name="barcode" id="barcode" 
                                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-3 px-4 focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-lg"
                                                   placeholder="Scan barcode di sini..." autofocus>
                                            <p class="mt-1 text-sm text-gray-500">Gunakan scanner barcode atau input manual</p>
                                        </div>
                                        <div>
                                            <label for="quantity" class="block text-sm font-medium text-gray-700">Jumlah</label>
                                            <input type="number" name="quantity" id="quantity" value="1" min="1"
                                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                        </div>
                                        <div class="flex space-x-2">
                                            <button type="submit" name="scan_barcode" 
                                                    class="flex-1 flex justify-center items-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                <i class="fas fa-plus mr-2"></i>Tambah ke Keranjang
                                            </button>
                                            <button type="button" onclick="clearBarcodeInput()" 
                                                    class="px-4 py-3 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Manual Product Selection -->
                            <div class="bg-white shadow rounded-lg">
                                <div class="px-4 py-5 sm:p-6">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                        <i class="fas fa-search mr-2"></i>Pilih Produk Manual
                                    </h3>
                                    <form method="POST" class="space-y-4" id="manual_product_form">
                                        <div class="relative">
                                            <label for="product_search" class="block text-sm font-medium text-gray-700">Cari Produk</label>
                                            <div class="mt-1 relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <i class="fas fa-search text-gray-400"></i>
                                                </div>
                                                <input type="text" 
                                                       id="product_search" 
                                                       autocomplete="off"
                                                       placeholder="Ketik nama produk atau barcode..."
                                                       class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                                <input type="hidden" name="product_id" id="product_id" value="">
                                            </div>
                                            
                                            <!-- Search Results Dropdown -->
                                            <div id="product_results" class="hidden absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto" style="top: 100%;">
                                                <!-- Results will be populated by JavaScript -->
                                            </div>
                                            
                                            <!-- Selected Product Display -->
                                            <div id="selected_product" class="hidden mt-2 p-3 bg-blue-50 border border-blue-200 rounded-md">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900" id="selected_product_name"></p>
                                                        <p class="text-xs text-gray-500">
                                                            Harga: <span id="selected_product_price"></span> | 
                                                            Stok: <span id="selected_product_stock"></span>
                                                        </p>
                                                    </div>
                                                    <button type="button" onclick="clearProductSelection()" class="text-red-600 hover:text-red-800">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <label for="manual_quantity" class="block text-sm font-medium text-gray-700">Jumlah</label>
                                            <input type="number" name="manual_quantity" id="manual_quantity" value="1" min="1"
                                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                            <p id="stock_info" class="mt-1 text-sm text-gray-500"></p>
                                        </div>
                                        <button type="submit" name="add_product" id="add_product_btn"
                                                class="w-full flex justify-center items-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                                disabled>
                                            <i class="fas fa-cart-plus mr-2"></i>Tambah ke Keranjang
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Cart Section -->
                        <div class="bg-white shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                                        <i class="fas fa-shopping-cart mr-2"></i>Keranjang Belanja
                                    </h3>
                                    <?php if (!empty($_SESSION['cart'])): ?>
                                        <a href="?clear_cart" 
                                           onclick="return confirm('Kosongkan seluruh keranjang?')"
                                           class="text-sm text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash mr-1"></i>Kosongkan
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <?php if (empty($_SESSION['cart'])): ?>
                                    <div class="text-center py-8">
                                        <i class="fas fa-shopping-cart text-4xl text-gray-300 mb-3"></i>
                                        <p class="text-gray-500">Keranjang belanja kosong</p>
                                        <p class="text-sm text-gray-400 mt-1">Scan barcode atau pilih produk untuk mulai</p>
                                    </div>
                                <?php else: ?>
                                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 rounded-lg mb-4">
                                        <table class="min-w-full divide-y divide-gray-300">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produk</th>
                                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga</th>
                                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <?php foreach ($_SESSION['cart'] as $index => $item): ?>
                                                    <tr>
                                                        <td class="px-4 py-3">
                                                            <div class="flex items-center">
                                                                <?php if ($item['gambar']): ?>
                                                                    <div class="flex-shrink-0 h-8 w-8">
                                                                        <img class="h-8 w-8 rounded object-cover" src="../uploads/<?php echo htmlspecialchars($item['gambar']); ?>" alt="<?php echo htmlspecialchars($item['nama_produk']); ?>">
                                                                    </div>
                                                                <?php else: ?>
                                                                    <div class="flex-shrink-0 h-8 w-8 bg-gray-200 rounded flex items-center justify-center">
                                                                        <i class="fas fa-box text-gray-400 text-xs"></i>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <div class="ml-3">
                                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['nama_produk']); ?></div>
                                                                    <?php if ($item['barcode']): ?>
                                                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($item['barcode']); ?></div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3 text-sm text-gray-900">
                                                            Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?>
                                                        </td>
                                                        <td class="px-4 py-3 text-sm text-gray-900">
                                                            <?php echo $item['quantity']; ?>
                                                        </td>
                                                        <td class="px-4 py-3 text-sm text-gray-900">
                                                            Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?>
                                                        </td>
                                                        <td class="px-4 py-3 text-sm text-gray-500">
                                                            <a href="?remove_item=<?php echo $index; ?>" 
                                                               class="text-red-600 hover:text-red-900">
                                                                <i class="fas fa-times"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot class="bg-gray-50">
                                                <tr>
                                                    <td colspan="3" class="px-4 py-3 text-sm font-medium text-gray-900 text-right">Total</td>
                                                    <td colspan="2" class="px-4 py-3 text-sm font-medium text-gray-900">
                                                        Rp <?php echo number_format($cart_total, 0, ',', '.'); ?>
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>

                                    <!-- Payment Form -->
                                    <form method="POST" class="space-y-4">
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label for="metode_pembayaran" class="block text-sm font-medium text-gray-700">Metode Bayar</label>
                                                <select name="metode_pembayaran" id="metode_pembayaran" required
                                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                                    <option value="cash">Tunai</option>
                                                    <option value="debit">Kartu Debit</option>
                                                    <option value="credit">Kartu Kredit</option>
                                                    <option value="dana">DANA</option>
                                                    <option value="transfer_bank">Transfer Bank</option>
                                                    <option value="shopeepay">ShopeePay</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label for="jumlah_bayar" class="block text-sm font-medium text-gray-700">Jumlah Bayar</label>
                                                <input type="text" name="jumlah_bayar" id="jumlah_bayar" required
                                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                                       placeholder="Rp 0">
                                            </div>
                                        </div>
                                        <div id="kembalian_info" class="hidden p-3 bg-green-50 rounded-md">
                                            <p class="text-sm font-medium text-green-800">
                                                Kembalian: <span id="kembalian_text">Rp 0</span>
                                            </p>
                                        </div>
                                        <button type="submit" name="save_transaction"
                                                class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                            <i class="fas fa-check mr-2"></i>Simpan Transaksi
                                        </button>
                                    </form>
                                <?php endif; ?>
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
            <a href="dashboard.php" class="flex flex-col items-center py-2 text-gray-500">
                <i class="fas fa-chart-pie text-lg"></i>
                <span class="text-xs mt-1">Dashboard</span>
            </a>
            <a href="transaksi.php" class="flex flex-col items-center py-2 text-blue-600">
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

    <script>
        // Auto-focus barcode input
        document.getElementById('barcode')?.focus();

        // Auto-submit barcode form on Enter key (for barcode scanners)
        document.getElementById('barcode')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (this.value.trim() !== '') {
                    this.form.submit();
                }
            }
        });

        // Auto-submit barcode form after scanning (for barcode scanners that don't send Enter)
        let lastInputTime = 0;
        let isManualTyping = false;
        let inputCount = 0;
        
        document.getElementById('barcode')?.addEventListener('input', function(e) {
            const now = Date.now();
            const timeSinceLastInput = now - lastInputTime;
            inputCount++;
            
            // Clear any existing timeout
            if (this.scanTimeout) {
                clearTimeout(this.scanTimeout);
            }
            
            // Detect if this is manual typing (slow input) vs barcode scanner (fast input)
            // Barcode scanners typically input very fast (< 50ms between characters)
            if (timeSinceLastInput > 100 || inputCount === 1) {
                isManualTyping = true;
            } else if (timeSinceLastInput < 50 && inputCount > 1) {
                isManualTyping = false; // Likely a barcode scanner
            }
            
            lastInputTime = now;
            
            // Only auto-submit for barcode scanners (fast input) with longer timeout
            this.scanTimeout = setTimeout(() => {
                if (this.value.trim() !== '' && this.value.length >= 3 && !isManualTyping) {
                    this.form.submit();
                }
            }, 800); // Longer timeout for manual typing
        });

        // Track keydown to detect manual typing
        document.getElementById('barcode')?.addEventListener('keydown', function(e) {
            // If user presses any key except Enter, they're probably typing manually
            if (e.key !== 'Enter') {
                isManualTyping = true;
            }
        });

        // Reset manual typing detection when field is focused
        document.getElementById('barcode')?.addEventListener('focus', function(e) {
            isManualTyping = false;
            inputCount = 0;
        });

        // Function to clear barcode input
        function clearBarcodeInput() {
            document.getElementById('barcode').value = '';
            document.getElementById('barcode').focus();
        }

        // Product search functionality
        const products = <?php echo json_encode($products); ?>;
        const productSearch = document.getElementById('product_search');
        const productResults = document.getElementById('product_results');
        const productIdInput = document.getElementById('product_id');
        const selectedProductDiv = document.getElementById('selected_product');
        const selectedProductName = document.getElementById('selected_product_name');
        const selectedProductPrice = document.getElementById('selected_product_price');
        const selectedProductStock = document.getElementById('selected_product_stock');
        const stockInfo = document.getElementById('stock_info');
        const addProductBtn = document.getElementById('add_product_btn');
        const manualQuantity = document.getElementById('manual_quantity');
        
        let selectedProduct = null;
        
        // Search products as user types
        productSearch?.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase().trim();
            
            if (searchTerm.length === 0) {
                productResults.classList.add('hidden');
                return;
            }
            
            // Filter products
            const filtered = products.filter(product => {
                const nama = product.nama_produk.toLowerCase();
                const barcode = (product.barcode || '').toLowerCase();
                return nama.includes(searchTerm) || barcode.includes(searchTerm);
            });
            
            // Display results
            if (filtered.length > 0) {
                productResults.innerHTML = filtered.map(product => `
                    <div class="px-4 py-3 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0" 
                         onclick="selectProduct(${product.id})">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">${product.nama_produk}</p>
                                <p class="text-xs text-gray-500">
                                    Rp ${parseInt(product.harga).toLocaleString('id-ID')} | 
                                    Stok: ${product.stok}
                                    ${product.barcode ? ' | Barcode: ' + product.barcode : ''}
                                </p>
                            </div>
                            <i class="fas fa-chevron-right text-gray-400 ml-2"></i>
                        </div>
                    </div>
                `).join('');
                productResults.classList.remove('hidden');
            } else {
                productResults.innerHTML = `
                    <div class="px-4 py-3 text-center text-sm text-gray-500">
                        <i class="fas fa-search mr-2"></i>Produk tidak ditemukan
                    </div>
                `;
                productResults.classList.remove('hidden');
            }
        });
        
        // Hide results when clicking outside
        document.addEventListener('click', function(e) {
            if (!productSearch.contains(e.target) && !productResults.contains(e.target)) {
                productResults.classList.add('hidden');
            }
        });
        
        // Select product function
        window.selectProduct = function(productId) {
            selectedProduct = products.find(p => p.id == productId);
            
            if (selectedProduct) {
                productIdInput.value = selectedProduct.id;
                productSearch.value = selectedProduct.nama_produk;
                
                // Update selected product display
                selectedProductName.textContent = selectedProduct.nama_produk;
                selectedProductPrice.textContent = 'Rp ' + parseInt(selectedProduct.harga).toLocaleString('id-ID');
                selectedProductStock.textContent = selectedProduct.stok;
                selectedProductDiv.classList.remove('hidden');
                
                // Update stock info
                stockInfo.textContent = `Stok tersedia: ${selectedProduct.stok}`;
                stockInfo.className = `mt-1 text-sm ${parseInt(selectedProduct.stok) === 0 ? 'text-red-500' : 'text-green-500'}`;
                
                // Set max quantity
                manualQuantity.max = selectedProduct.stok;
                manualQuantity.value = Math.min(parseInt(manualQuantity.value) || 1, selectedProduct.stok);
                
                // Enable add button
                addProductBtn.disabled = false;
                
                // Hide results
                productResults.classList.add('hidden');
            }
        };
        
        // Clear product selection
        window.clearProductSelection = function() {
            selectedProduct = null;
            productIdInput.value = '';
            productSearch.value = '';
            selectedProductDiv.classList.add('hidden');
            stockInfo.textContent = '';
            manualQuantity.max = '';
            addProductBtn.disabled = true;
            productSearch.focus();
        };
        
        // Handle Enter key in search
        productSearch?.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const firstResult = productResults.querySelector('div[onclick]');
                if (firstResult) {
                    firstResult.click();
                }
            }
        });
        
        // Update stock info when quantity changes
        manualQuantity?.addEventListener('input', function() {
            if (selectedProduct) {
                const qty = parseInt(this.value) || 0;
                const stock = parseInt(selectedProduct.stok);
                
                if (qty > stock) {
                    stockInfo.textContent = `Stok tidak mencukupi! Stok tersedia: ${stock}`;
                    stockInfo.className = 'mt-1 text-sm text-red-500';
                } else if (qty > 0) {
                    stockInfo.textContent = `Stok tersedia: ${stock}`;
                    stockInfo.className = 'mt-1 text-sm text-green-500';
                }
            }
        });
        
        // Form validation before submit
        document.getElementById('manual_product_form')?.addEventListener('submit', function(e) {
            if (!productIdInput.value || !selectedProduct) {
                e.preventDefault();
                alert('Silakan pilih produk terlebih dahulu!');
                productSearch.focus();
                return false;
            }
            
            const qty = parseInt(manualQuantity.value) || 0;
            if (qty <= 0) {
                e.preventDefault();
                alert('Jumlah harus lebih dari 0!');
                manualQuantity.focus();
                return false;
            }
            
            if (qty > parseInt(selectedProduct.stok)) {
                e.preventDefault();
                alert(`Stok tidak mencukupi! Stok tersedia: ${selectedProduct.stok}`);
                manualQuantity.focus();
                return false;
            }
        });

        // Format payment amount and calculate change
        document.getElementById('jumlah_bayar')?.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            let number = parseInt(value) || 0;
            e.target.value = 'Rp ' + number.toLocaleString('id-ID');
            
            // Calculate change
            const total = <?php echo $cart_total; ?>;
            const change = number - total;
            const changeInfo = document.getElementById('kembalian_info');
            const changeText = document.getElementById('kembalian_text');
            
            if (number >= total && total > 0) {
                changeText.textContent = 'Rp ' + change.toLocaleString('id-ID');
                changeInfo.classList.remove('hidden');
            } else {
                changeInfo.classList.add('hidden');
            }
        });

        // No need for client-side validation - let PHP handle it
    </script>
</body>
</html>