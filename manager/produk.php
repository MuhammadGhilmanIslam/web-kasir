<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
redirectIfNotAuthorized('manajer');

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['tambah_produk'])) {
        $nama_produk = $_POST['nama_produk'];
        $deskripsi = $_POST['deskripsi'];
        $harga = $_POST['harga'];
        $stok = $_POST['stok'];
        $stok_minimum = $_POST['stok_minimum'];
        $barcode = $_POST['barcode'];
        $kategori_id = $_POST['kategori_id'];
        
        // Handle file upload
        $gambar = null;
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === 0) {
            $uploadDir = '../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExtension = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array(strtolower($fileExtension), $allowedExtensions)) {
                $fileName = uniqid() . '.' . $fileExtension;
                $uploadPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $uploadPath)) {
                    $gambar = $fileName;
                }
            }
        }
        
        // Check if barcode already exists (if provided)
        if (!empty($barcode)) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE barcode = ?");
            $stmt->execute([$barcode]);
            if ($stmt->fetchColumn() > 0) {
                $_SESSION['error'] = "Barcode '$barcode' sudah digunakan oleh produk lain!";
                header('Location: produk.php');
                exit;
            }
        }
        
        try {
        $stmt = $pdo->prepare("INSERT INTO products (nama_produk, deskripsi, harga, stok, stok_minimum, barcode, kategori_id, gambar) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$nama_produk, $deskripsi, $harga, $stok, $stok_minimum, $barcode, $kategori_id, $gambar])) {
            $_SESSION['success'] = "Produk berhasil ditambahkan!";
            header('Location: produk.php');
            exit;
        } else {
            $_SESSION['error'] = "Gagal menambahkan produk!";
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Integrity constraint violation
                $_SESSION['error'] = "Barcode '$barcode' sudah digunakan oleh produk lain!";
            } else {
                $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
            }
        }
    }
    
    if (isset($_POST['edit_produk'])) {
        $id = $_POST['id'];
        $nama_produk = $_POST['nama_produk'];
        $deskripsi = $_POST['deskripsi'];
        $harga = $_POST['harga'];
        $stok = $_POST['stok'];
        $stok_minimum = $_POST['stok_minimum'];
        $barcode = $_POST['barcode'];
        $kategori_id = $_POST['kategori_id'];
        
        // Handle file upload
        $gambar = $_POST['gambar_lama'];
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === 0) {
            $uploadDir = '../uploads/';
            $fileExtension = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array(strtolower($fileExtension), $allowedExtensions)) {
                // Delete old image if exists
                if ($gambar && file_exists($uploadDir . $gambar)) {
                    unlink($uploadDir . $gambar);
                }
                
                $fileName = uniqid() . '.' . $fileExtension;
                $uploadPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $uploadPath)) {
                    $gambar = $fileName;
                }
            }
        }
        
        // Check if barcode already exists (if provided and different from current)
        if (!empty($barcode)) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE barcode = ? AND id != ?");
            $stmt->execute([$barcode, $id]);
            if ($stmt->fetchColumn() > 0) {
                $_SESSION['error'] = "Barcode '$barcode' sudah digunakan oleh produk lain!";
                header('Location: produk.php');
                exit;
            }
        }
        
        try {
        $stmt = $pdo->prepare("UPDATE products SET nama_produk=?, deskripsi=?, harga=?, stok=?, stok_minimum=?, barcode=?, kategori_id=?, gambar=? WHERE id=?");
        if ($stmt->execute([$nama_produk, $deskripsi, $harga, $stok, $stok_minimum, $barcode, $kategori_id, $gambar, $id])) {
            $_SESSION['success'] = "Produk berhasil diperbarui!";
            header('Location: produk.php');
            exit;
        } else {
            $_SESSION['error'] = "Gagal memperbarui produk!";
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Integrity constraint violation
                $_SESSION['error'] = "Barcode '$barcode' sudah digunakan oleh produk lain!";
            } else {
                $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
            }
        }
    }
}

// Handle delete produk
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    // Check if product exists in transactions
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM transaction_items WHERE product_id = ?");
    $stmt->execute([$id]);
    $used_in_transactions = $stmt->fetchColumn() > 0;
    
    if ($used_in_transactions) {
        // Soft delete
        $stmt = $pdo->prepare("UPDATE products SET status = 'inactive' WHERE id = ?");
        if ($stmt->execute([$id])) {
            $_SESSION['success'] = "Produk berhasil dinonaktifkan!";
        } else {
            $_SESSION['error'] = "Gagal menonaktifkan produk!";
        }
    } else {
        // Hard delete with image removal
        $stmt = $pdo->prepare("SELECT gambar FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        
        if ($product && $product['gambar']) {
            $filePath = '../uploads/' . $product['gambar'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        if ($stmt->execute([$id])) {
            $_SESSION['success'] = "Produk berhasil dihapus!";
        } else {
            $_SESSION['error'] = "Gagal menghapus produk!";
        }
    }
    header('Location: produk.php');
    exit;
}

// Get products with categories
$search = $_GET['search'] ?? '';
$kategori_filter = $_GET['kategori'] ?? '';
$status_filter = $_GET['status'] ?? 'active';

$sql = "SELECT p.*, c.nama_kategori FROM products p LEFT JOIN categories c ON p.kategori_id = c.id WHERE 1=1";
$params = [];

if ($status_filter === 'active') {
    $sql .= " AND p.status = 'active'";
} elseif ($status_filter === 'inactive') {
    $sql .= " AND p.status = 'inactive'";
}

if ($search) {
    $sql .= " AND (p.nama_produk LIKE ? OR p.barcode LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($kategori_filter) {
    $sql .= " AND p.kategori_id = ?";
    $params[] = $kategori_filter;
}

$sql .= " ORDER BY p.nama_produk";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories for filter
$stmt = $pdo->query("SELECT * FROM categories ORDER BY nama_kategori");
$categories = $stmt->fetchAll();

// Get product for editing
$edit_product = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_product = $stmt->fetch();
}

// Get statistics
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_produk,
        SUM(stok) as total_stok,
        COUNT(CASE WHEN stok <= stok_minimum THEN 1 END) as stok_minimum,
        COUNT(CASE WHEN status = 'inactive' THEN 1 END) as nonaktif
    FROM products
");
$stats = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Produk - M Mart</title>
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
                    <a href="produk.php" class="bg-indigo-900 text-white group flex items-center px-2 py-2 text-sm font-medium rounded-md">
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
                    <div class="md:flex md:items-center md:justify-between mb-6">
                        <div class="flex-1 min-w-0">
                            <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                                Manajemen Produk
                            </h1>
                            <p class="mt-1 text-sm text-gray-500">Kelola inventori produk toko</p>
                        </div>
                        <div class="mt-4 flex md:mt-0 md:ml-4">
                            <button onclick="openAddModal()" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <i class="fas fa-plus mr-2"></i>
                                Tambah Produk
                            </button>
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

                    <!-- Product Stats -->
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-4 mb-6">
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
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

                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-box-open text-3xl text-green-500"></i>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Total Stok</dt>
                                            <dd class="text-lg font-medium text-gray-900"><?php echo $stats['total_stok']; ?></dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-exclamation-triangle text-3xl text-yellow-500"></i>
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

                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-ban text-3xl text-red-500"></i>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Nonaktif</dt>
                                            <dd class="text-lg font-medium text-gray-900"><?php echo $stats['nonaktif']; ?></dd>
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
                                    <label for="search" class="block text-sm font-medium text-gray-700">Cari Produk</label>
                                    <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>"
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                           placeholder="Nama produk atau barcode...">
                                </div>
                                <div>
                                    <label for="kategori" class="block text-sm font-medium text-gray-700">Filter Kategori</label>
                                    <select name="kategori" id="kategori" class="mt-1 block w-full bg-white border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="">Semua Kategori</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" <?php echo $kategori_filter == $category['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['nama_kategori']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                                    <select name="status" id="status" class="mt-1 block w-full bg-white border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Aktif</option>
                                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Nonaktif</option>
                                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Semua</option>
                                    </select>
                                </div>
                                <div class="flex items-end">
                                    <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        <i class="fas fa-search mr-2"></i>
                                        Terapkan Filter
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Products Table -->
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <?php if (empty($products)): ?>
                                <div class="text-center py-8">
                                    <i class="fas fa-box-open text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-gray-500">Tidak ada produk ditemukan</p>
                                    <button onclick="openAddModal()" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                        <i class="fas fa-plus mr-2"></i>
                                        Tambah Produk Pertama
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 rounded-lg">
                                    <table class="min-w-full divide-y divide-gray-300">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produk</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stok</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php foreach ($products as $product): ?>
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="flex items-center">
                                                            <?php if ($product['gambar']): ?>
                                                                <div class="flex-shrink-0 h-10 w-10">
                                                                    <img class="h-10 w-10 rounded-lg object-cover" src="../uploads/<?php echo htmlspecialchars($product['gambar']); ?>" alt="<?php echo htmlspecialchars($product['nama_produk']); ?>">
                                                                </div>
                                                            <?php else: ?>
                                                                <div class="flex-shrink-0 h-10 w-10 bg-gray-200 rounded-lg flex items-center justify-center">
                                                                    <i class="fas fa-box text-gray-400"></i>
                                                                </div>
                                                            <?php endif; ?>
                                                            <div class="ml-4">
                                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['nama_produk']); ?></div>
                                                                <div class="text-sm text-gray-500"><?php echo $product['barcode'] ?: 'No barcode'; ?></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <?php echo $product['nama_kategori'] ? htmlspecialchars($product['nama_kategori']) : '<span class="text-gray-400">-</span>'; ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        Rp <?php echo number_format($product['harga'], 0, ',', '.'); ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                            <?php echo $product['stok'] == 0 ? 'bg-red-100 text-red-800' : 
                                                                   ($product['stok'] <= $product['stok_minimum'] ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'); ?>">
                                                            <?php echo $product['stok']; ?>
                                                            <?php if ($product['stok'] <= $product['stok_minimum']): ?>
                                                                <i class="fas fa-exclamation-triangle ml-1"></i>
                                                            <?php endif; ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                            <?php echo $product['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                            <?php echo $product['status'] === 'active' ? 'Aktif' : 'Nonaktif'; ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                        <div class="flex space-x-2">
                                                            <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($product)); ?>)" 
                                                                    class="text-indigo-600 hover:text-indigo-900">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <a href="?hapus=<?php echo $product['id']; ?>" 
                                                               onclick="return confirm('<?php echo $product['status'] === 'active' ? 'Nonaktifkan' : 'Hapus'; ?> produk <?php echo htmlspecialchars($product['nama_produk']); ?>?')"
                                                               class="text-red-600 hover:text-red-900">
                                                                <i class="fas fa-<?php echo $product['status'] === 'active' ? 'ban' : 'trash'; ?>"></i>
                                                            </a>
                                                        </div>
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

    <!-- Add Product Modal -->
    <div id="addModal" class="fixed inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <form method="POST" enctype="multipart/form-data">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Tambah Produk Baru
                        </h3>
                        <div class="mt-4 space-y-4">
                            <div>
                                <label for="nama_produk" class="block text-sm font-medium text-gray-700">Nama Produk *</label>
                                <input type="text" name="nama_produk" id="nama_produk" required
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div>
                                <label for="deskripsi" class="block text-sm font-medium text-gray-700">Deskripsi</label>
                                <textarea name="deskripsi" id="deskripsi" rows="3"
                                          class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="harga" class="block text-sm font-medium text-gray-700">Harga *</label>
                                    <input type="number" name="harga" id="harga" required min="0" step="0.01"
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label for="stok" class="block text-sm font-medium text-gray-700">Stok *</label>
                                    <input type="number" name="stok" id="stok" required min="0"
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="stok_minimum" class="block text-sm font-medium text-gray-700">Stok Minimum *</label>
                                    <input type="number" name="stok_minimum" id="stok_minimum" required min="1"
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" value="5">
                                </div>
                                <div>
                                    <label for="kategori_id" class="block text-sm font-medium text-gray-700">Kategori</label>
                                    <select name="kategori_id" id="kategori_id" class="mt-1 block w-full bg-white border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="">Pilih Kategori</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['nama_kategori']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label for="barcode" class="block text-sm font-medium text-gray-700">Barcode</label>
                                <input type="text" name="barcode" id="barcode"
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div>
                                <label for="gambar" class="block text-sm font-medium text-gray-700">Gambar Produk</label>
                                <input type="file" name="gambar" id="gambar" accept="image/*"
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <p class="mt-1 text-sm text-gray-500">Format: JPG, PNG, GIF (Maks. 2MB)</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                        <button type="submit" name="tambah_produk"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:col-start-2 sm:text-sm">
                            Tambah Produk
                        </button>
                        <button type="button" onclick="closeAddModal()"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div id="editModal" class="fixed inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <form method="POST" enctype="multipart/form-data" id="editForm">
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="gambar_lama" id="edit_gambar_lama">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Edit Produk
                        </h3>
                        <div class="mt-4 space-y-4">
                            <div>
                                <label for="edit_nama_produk" class="block text-sm font-medium text-gray-700">Nama Produk *</label>
                                <input type="text" name="nama_produk" id="edit_nama_produk" required
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div>
                                <label for="edit_deskripsi" class="block text-sm font-medium text-gray-700">Deskripsi</label>
                                <textarea name="deskripsi" id="edit_deskripsi" rows="3"
                                          class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="edit_harga" class="block text-sm font-medium text-gray-700">Harga *</label>
                                    <input type="number" name="harga" id="edit_harga" required min="0" step="0.01"
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label for="edit_stok" class="block text-sm font-medium text-gray-700">Stok *</label>
                                    <input type="number" name="stok" id="edit_stok" required min="0"
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="edit_stok_minimum" class="block text-sm font-medium text-gray-700">Stok Minimum *</label>
                                    <input type="number" name="stok_minimum" id="edit_stok_minimum" required min="1"
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label for="edit_kategori_id" class="block text-sm font-medium text-gray-700">Kategori</label>
                                    <select name="kategori_id" id="edit_kategori_id" class="mt-1 block w-full bg-white border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="">Pilih Kategori</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['nama_kategori']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label for="edit_barcode" class="block text-sm font-medium text-gray-700">Barcode</label>
                                <input type="text" name="barcode" id="edit_barcode"
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div>
                                <label for="edit_gambar" class="block text-sm font-medium text-gray-700">Gambar Produk</label>
                                <input type="file" name="gambar" id="edit_gambar" accept="image/*"
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <div id="currentImage" class="mt-2"></div>
                                <p class="mt-1 text-sm text-gray-500">Format: JPG, PNG, GIF (Maks. 2MB)</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                        <button type="submit" name="edit_produk"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:col-start-2 sm:text-sm">
                            Simpan Perubahan
                        </button>
                        <button type="button" onclick="closeEditModal()"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Modal functions
        function openAddModal() {
            document.getElementById('addModal').classList.remove('hidden');
        }

        function closeAddModal() {
            document.getElementById('addModal').classList.add('hidden');
        }

        function openEditModal(product) {
            document.getElementById('edit_id').value = product.id;
            document.getElementById('edit_nama_produk').value = product.nama_produk;
            document.getElementById('edit_deskripsi').value = product.deskripsi || '';
            document.getElementById('edit_harga').value = product.harga;
            document.getElementById('edit_stok').value = product.stok;
            document.getElementById('edit_stok_minimum').value = product.stok_minimum;
            document.getElementById('edit_barcode').value = product.barcode || '';
            document.getElementById('edit_kategori_id').value = product.kategori_id || '';
            document.getElementById('edit_gambar_lama').value = product.gambar || '';
            
            // Show current image if exists
            const currentImageDiv = document.getElementById('currentImage');
            if (product.gambar) {
                currentImageDiv.innerHTML = `
                    <p class="text-sm text-gray-500 mb-1">Gambar saat ini:</p>
                    <img src="../uploads/${product.gambar}" alt="Current image" class="h-20 w-20 object-cover rounded-lg border">
                `;
            } else {
                currentImageDiv.innerHTML = '<p class="text-sm text-gray-500">Tidak ada gambar</p>';
            }
            
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        // Close modals when clicking outside
        document.addEventListener('click', function(event) {
            if (event.target.id === 'addModal') {
                closeAddModal();
            }
            if (event.target.id === 'editModal') {
                closeEditModal();
            }
        });

        // Format price input
        document.addEventListener('DOMContentLoaded', function() {
            const priceInputs = document.querySelectorAll('input[type="number"][name="harga"]');
            priceInputs.forEach(input => {
                input.addEventListener('input', function() {
                    if (this.value < 0) this.value = 0;
                });
            });
        });
    </script>
</body>
</html>