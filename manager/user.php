<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
redirectIfNotAuthorized('manajer');

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['tambah_user'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $nama_lengkap = trim($_POST['nama_lengkap']);
        $role = $_POST['role'];
        
        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Username sudah digunakan!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$username, $hashed_password, $nama_lengkap, $role])) {
                $_SESSION['success'] = "User berhasil ditambahkan!";
                header('Location: user.php');
                exit;
            } else {
                $_SESSION['error'] = "Gagal menambahkan user!";
            }
        }
    }
    
    if (isset($_POST['edit_user'])) {
        $user_id = $_POST['user_id'];
        $username = trim($_POST['username']);
        $nama_lengkap = trim($_POST['nama_lengkap']);
        $role = $_POST['role'];
        $password = $_POST['password'];
        
        // Check if username exists (excluding current user)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $user_id]);
        
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Username sudah digunakan!";
        } else {
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, nama_lengkap = ?, role = ? WHERE id = ?");
                $stmt->execute([$username, $hashed_password, $nama_lengkap, $role, $user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, nama_lengkap = ?, role = ? WHERE id = ?");
                $stmt->execute([$username, $nama_lengkap, $role, $user_id]);
            }
            
            $_SESSION['success'] = "User berhasil diperbarui!";
            header('Location: user.php');
            exit;
        }
    }
}

// Handle delete user
if (isset($_GET['hapus'])) {
    $user_id = $_GET['hapus'];
    
    // Prevent deleting own account
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['error'] = "Tidak dapat menghapus akun sendiri!";
    } else {
        // Check if user has transactions
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $has_transactions = $stmt->fetchColumn() > 0;
        
        if ($has_transactions) {
            $_SESSION['error'] = "Tidak dapat menghapus user yang memiliki riwayat transaksi!";
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$user_id])) {
                $_SESSION['success'] = "User berhasil dihapus!";
            } else {
                $_SESSION['error'] = "Gagal menghapus user!";
            }
        }
    }
    header('Location: user.php');
    exit;
}

// Get all users
$stmt = $pdo->query("SELECT * FROM users ORDER BY role DESC, created_at DESC");
$users = $stmt->fetchAll();

// Get user for editing
$edit_user = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_user = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - M Mart</title>
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
                    <a href="laporan.php" class="text-indigo-100 hover:bg-indigo-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-chart-bar mr-3 text-indigo-300"></i>
                        Laporan
                    </a>
                    <a href="user.php" class="bg-indigo-900 text-white group flex items-center px-2 py-2 text-sm font-medium rounded-md">
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
                                Manajemen User
                            </h1>
                            <p class="mt-1 text-sm text-gray-500">Kelola akses user dan hak akses sistem</p>
                        </div>
                        <div class="mt-4 flex md:mt-0 md:ml-4">
                            <button onclick="openAddModal()" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <i class="fas fa-plus mr-2"></i>
                                Tambah User
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

                    <!-- User Summary -->
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3 mb-6">
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-users text-3xl text-blue-500"></i>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Total User</dt>
                                            <dd class="text-lg font-medium text-gray-900"><?php echo count($users); ?></dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-user-shield text-3xl text-purple-500"></i>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Manager</dt>
                                            <dd class="text-lg font-medium text-gray-900">
                                                <?php echo count(array_filter($users, function($u) { return $u['role'] === 'manajer'; })); ?>
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-user-tie text-3xl text-green-500"></i>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Kasir</dt>
                                            <dd class="text-lg font-medium text-gray-900">
                                                <?php echo count(array_filter($users, function($u) { return $u['role'] === 'kasir'; })); ?>
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Users Table -->
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <?php if (empty($users)): ?>
                                <div class="text-center py-8">
                                    <i class="fas fa-users text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-gray-500">Belum ada user terdaftar</p>
                                    <button onclick="openAddModal()" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                        <i class="fas fa-plus mr-2"></i>
                                        Tambah User Pertama
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 rounded-lg">
                                    <table class="min-w-full divide-y divide-gray-300">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Dibuat</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php foreach ($users as $user): ?>
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="flex items-center">
                                                            <div class="flex-shrink-0 h-10 w-10">
                                                                <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                                                    <i class="fas fa-user text-indigo-600"></i>
                                                                </div>
                                                            </div>
                                                            <div class="ml-4">
                                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['nama_lengkap']); ?></div>
                                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['username']); ?></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                            <?php echo $user['role'] === 'manajer' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800'; ?>">
                                                            <i class="fas fa-<?php echo $user['role'] === 'manajer' ? 'user-shield' : 'user-tie'; ?> mr-1"></i>
                                                            <?php echo ucfirst($user['role']); ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                                <i class="fas fa-star mr-1"></i>
                                                                Anda
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                                Aktif
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                        <div class="flex space-x-2">
                                                            <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($user)); ?>)" 
                                                                    class="text-indigo-600 hover:text-indigo-900">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </button>
                                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                                <a href="?hapus=<?php echo $user['id']; ?>" 
                                                                   onclick="return confirm('Hapus user <?php echo htmlspecialchars($user['nama_lengkap']); ?>?')"
                                                                   class="text-red-600 hover:text-red-900">
                                                                    <i class="fas fa-trash"></i> Hapus
                                                                </a>
                                                            <?php else: ?>
                                                                <span class="text-gray-400 cursor-not-allowed">
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

    <!-- Add User Modal -->
    <div id="addModal" class="fixed inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <form method="POST">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Tambah User Baru
                        </h3>
                        <div class="mt-4 space-y-4">
                            <div>
                                <label for="nama_lengkap" class="block text-sm font-medium text-gray-700">Nama Lengkap *</label>
                                <input type="text" name="nama_lengkap" id="nama_lengkap" required
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div>
                                <label for="username" class="block text-sm font-medium text-gray-700">Username *</label>
                                <input type="text" name="username" id="username" required
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700">Password *</label>
                                <input type="password" name="password" id="password" required
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div>
                                <label for="role" class="block text-sm font-medium text-gray-700">Role *</label>
                                <select name="role" id="role" required
                                        class="mt-1 block w-full bg-white border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="kasir">Kasir</option>
                                    <option value="manajer">Manager</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                        <button type="submit" name="tambah_user"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:col-start-2 sm:text-sm">
                            Tambah User
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

    <!-- Edit User Modal -->
    <div id="editModal" class="fixed inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <form method="POST" id="editForm">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Edit User
                        </h3>
                        <div class="mt-4 space-y-4">
                            <div>
                                <label for="edit_nama_lengkap" class="block text-sm font-medium text-gray-700">Nama Lengkap *</label>
                                <input type="text" name="nama_lengkap" id="edit_nama_lengkap" required
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div>
                                <label for="edit_username" class="block text-sm font-medium text-gray-700">Username *</label>
                                <input type="text" name="username" id="edit_username" required
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div>
                                <label for="edit_password" class="block text-sm font-medium text-gray-700">Password Baru</label>
                                <input type="password" name="password" id="edit_password"
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <p class="mt-1 text-sm text-gray-500">Kosongkan jika tidak ingin mengubah password</p>
                            </div>
                            <div>
                                <label for="edit_role" class="block text-sm font-medium text-gray-700">Role *</label>
                                <select name="role" id="edit_role" required
                                        class="mt-1 block w-full bg-white border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="kasir">Kasir</option>
                                    <option value="manajer">Manager</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                        <button type="submit" name="edit_user"
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

        function openEditModal(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_nama_lengkap').value = user.nama_lengkap;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_role').value = user.role;
            document.getElementById('edit_password').value = '';
            
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