<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
redirectIfNotAuthorized('manajer');

// Handle setup default categories
if (isset($_GET['setup_default'])) {
    $default_categories = [
        ['nama_kategori' => 'Makanan', 'deskripsi' => 'Berbagai jenis makanan dan makanan ringan'],
        ['nama_kategori' => 'Minuman', 'deskripsi' => 'Minuman dalam kemasan, air mineral, dan minuman ringan'],
        ['nama_kategori' => 'Snack', 'deskripsi' => 'Makanan ringan, keripik, dan camilan'],
        ['nama_kategori' => 'Roti & Kue', 'deskripsi' => 'Roti, kue, dan produk bakery'],
        ['nama_kategori' => 'Daging & Seafood', 'deskripsi' => 'Daging segar, ayam, ikan, dan seafood'],
        ['nama_kategori' => 'Sayur & Buah', 'deskripsi' => 'Sayuran dan buah-buahan segar'],
        ['nama_kategori' => 'Susu & Telur', 'deskripsi' => 'Produk susu, keju, yoghurt, dan telur'],
        ['nama_kategori' => 'Sembako', 'deskripsi' => 'Beras, minyak, gula, garam, dan kebutuhan pokok'],
        ['nama_kategori' => 'Kecantikan', 'deskripsi' => 'Produk kosmetik, perawatan tubuh, dan kecantikan'],
        ['nama_kategori' => 'Kesehatan', 'deskripsi' => 'Obat-obatan, vitamin, dan produk kesehatan'],
        ['nama_kategori' => 'Perawatan Rumah', 'deskripsi' => 'Sabun cuci, pembersih lantai, dan perawatan rumah'],
        ['nama_kategori' => 'Elektronik', 'deskripsi' => 'Aksesori elektronik dan gadget kecil'],
        ['nama_kategori' => 'Pakaian', 'deskripsi' => 'Pakaian, kaos, dan aksesori fashion'],
        ['nama_kategori' => 'Mainan', 'deskripsi' => 'Mainan anak-anak dan permainan'],
        ['nama_kategori' => 'Lain-lain', 'deskripsi' => 'Produk lainnya yang tidak termasuk kategori di atas']
    ];
    
    $added_count = 0;
    foreach ($default_categories as $category) {
        // Cek apakah kategori sudah ada
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE nama_kategori = ?");
        $stmt->execute([$category['nama_kategori']]);
        
        if ($stmt->fetchColumn() == 0) {
            // Kategori belum ada, tambahkan
            $stmt = $pdo->prepare("INSERT INTO categories (nama_kategori, deskripsi, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$category['nama_kategori'], $category['deskripsi']]);
            $added_count++;
        }
    }
    
    $_SESSION['success'] = "Setup kategori default selesai! {$added_count} kategori baru ditambahkan.";
    header('Location: kategori.php');
    exit;
}

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['tambah_kategori'])) {
        $nama_kategori = trim($_POST['nama_kategori']);
        $deskripsi = trim($_POST['deskripsi']);
        
        $stmt = $pdo->prepare("INSERT INTO categories (nama_kategori, deskripsi) VALUES (?, ?)");
        if ($stmt->execute([$nama_kategori, $deskripsi])) {
            $_SESSION['success'] = "Kategori berhasil ditambahkan!";
            header('Location: kategori.php');
            exit;
        } else {
            $_SESSION['error'] = "Gagal menambahkan kategori!";
        }
    }
    
    if (isset($_POST['edit_kategori'])) {
        $id = $_POST['id'];
        $nama_kategori = trim($_POST['nama_kategori']);
        $deskripsi = trim($_POST['deskripsi']);
        
        $stmt = $pdo->prepare("UPDATE categories SET nama_kategori = ?, deskripsi = ? WHERE id = ?");
        if ($stmt->execute([$nama_kategori, $deskripsi, $id])) {
            $_SESSION['success'] = "Kategori berhasil diperbarui!";
            header('Location: kategori.php');
            exit;
        } else {
            $_SESSION['error'] = "Gagal memperbarui kategori!";
        }
    }
}

// Handle delete kategori
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    // Check if category is used by products
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE kategori_id = ?");
    $stmt->execute([$id]);
    $used_in_products = $stmt->fetchColumn() > 0;
    
    if ($used_in_products) {
        $_SESSION['error'] = "Tidak dapat menghapus kategori yang digunakan oleh produk!";
    } else {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        if ($stmt->execute([$id])) {
            $_SESSION['success'] = "Kategori berhasil dihapus!";
        } else {
            $_SESSION['error'] = "Gagal menghapus kategori!";
        }
    }
    header('Location: kategori.php');
    exit;
}

// Get all categories with product count
$stmt = $pdo->query("
    SELECT c.*, COUNT(p.id) as jumlah_produk 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.kategori_id 
    GROUP BY c.id 
    ORDER BY c.nama_kategori
");
$categories = $stmt->fetchAll();

// Get category for editing
$edit_category = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_category = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori Produk - M Mart</title>
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
                        <i class="fas fa-chart-pie mr-3 text-indigo-300"></i>Dashboard
                    </a>
                    <a href="transaksi.php" class="text-indigo-100 hover:bg-indigo-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-shopping-cart mr-3 text-indigo-300"></i>Transaksi
                    </a>
                    <a href="produk.php" class="text-indigo-100 hover:bg-indigo-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-boxes mr-3 text-indigo-300"></i>Produk
                    </a>
                    <a href="kategori.php" class="bg-indigo-900 text-white group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-tags mr-3 text-indigo-300"></i>Kategori
                    </a>
                    <a href="laporan.php" class="text-indigo-100 hover:bg-indigo-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-chart-bar mr-3 text-indigo-300"></i>Laporan
                    </a>
                    <a href="user.php" class="text-indigo-100 hover:bg-indigo-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-users mr-3 text-indigo-300"></i>Manajemen User
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
                                Manajemen Kategori
                            </h1>
                            <p class="mt-1 text-sm text-gray-500">Kelola kategori produk toko</p>
                        </div>
                        <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
                            <a href="?setup_default=1" onclick="return confirm('Setup kategori default untuk supermarket?')" class="inline-flex items-center px-4 py-2 border border-indigo-600 rounded-md shadow-sm text-sm font-medium text-indigo-600 bg-white hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <i class="fas fa-magic mr-2"></i>
                                Setup Kategori Default
                            </a>
                            <button onclick="openAddModal()" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <i class="fas fa-plus mr-2"></i>
                                Tambah Kategori
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

                    <!-- Categories Table -->
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <?php if (empty($categories)): ?>
                                <div class="text-center py-8">
                                    <i class="fas fa-tags text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-gray-500">Belum ada kategori terdaftar</p>
                                    <button onclick="openAddModal()" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                        <i class="fas fa-plus mr-2"></i>
                                        Tambah Kategori Pertama
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 rounded-lg">
                                    <table class="min-w-full divide-y divide-gray-300">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah Produk</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Dibuat</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php foreach ($categories as $category): ?>
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($category['nama_kategori']); ?></div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <?php echo $category['deskripsi'] ? htmlspecialchars($category['deskripsi']) : '<span class="text-gray-400">-</span>'; ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                            <?php echo $category['jumlah_produk']; ?> produk
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <?php echo date('d/m/Y', strtotime($category['created_at'])); ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                        <div class="flex space-x-2">
                                                            <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($category)); ?>)" 
                                                                    class="text-indigo-600 hover:text-indigo-900">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </button>
                                                            <?php if ($category['jumlah_produk'] == 0): ?>
                                                                <a href="?hapus=<?php echo $category['id']; ?>" 
                                                                   onclick="return confirm('Hapus kategori <?php echo htmlspecialchars($category['nama_kategori']); ?>?')"
                                                                   class="text-red-600 hover:text-red-900">
                                                                    <i class="fas fa-trash"></i> Hapus
                                                                </a>
                                                            <?php else: ?>
                                                                <span class="text-gray-400 cursor-not-allowed" title="Tidak dapat dihapus karena digunakan oleh produk">
                                                                    <i class="fas fa-trash"></i> Hapus
                                                                </span>
                                                            <?php endif; ?>
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

    <!-- Add Category Modal -->
    <div id="addModal" class="fixed inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <form method="POST">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Tambah Kategori Baru
                        </h3>
                        <div class="mt-4 space-y-4">
                            <div>
                                <label for="nama_kategori" class="block text-sm font-medium text-gray-700">Nama Kategori *</label>
                                <input type="text" name="nama_kategori" id="nama_kategori" required
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div>
                                <label for="deskripsi" class="block text-sm font-medium text-gray-700">Deskripsi</label>
                                <textarea name="deskripsi" id="deskripsi" rows="3"
                                          class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                        <button type="submit" name="tambah_kategori"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:col-start-2 sm:text-sm">
                            Tambah Kategori
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

    <!-- Edit Category Modal -->
    <div id="editModal" class="fixed inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <form method="POST" id="editForm">
                    <input type="hidden" name="id" id="edit_id">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Edit Kategori
                        </h3>
                        <div class="mt-4 space-y-4">
                            <div>
                                <label for="edit_nama_kategori" class="block text-sm font-medium text-gray-700">Nama Kategori *</label>
                                <input type="text" name="nama_kategori" id="edit_nama_kategori" required
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div>
                                <label for="edit_deskripsi" class="block text-sm font-medium text-gray-700">Deskripsi</label>
                                <textarea name="deskripsi" id="edit_deskripsi" rows="3"
                                          class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                        <button type="submit" name="edit_kategori"
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

        function openEditModal(category) {
            document.getElementById('edit_id').value = category.id;
            document.getElementById('edit_nama_kategori').value = category.nama_kategori;
            document.getElementById('edit_deskripsi').value = category.deskripsi || '';
            
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
    </script>
</body>
</html>