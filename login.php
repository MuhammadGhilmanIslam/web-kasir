<?php
session_start();
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['role'] === 'kasir' ? 'kasir/dashboard.php' : 'manager/dashboard.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        
        header('Location: ' . ($user['role'] === 'kasir' ? 'kasir/dashboard.php' : 'manager/dashboard.php'));
        exit;
    } else {
        $error = "Username atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Kasir Modern</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="h-full">
    <div class="min-h-full flex">
        <!-- Left Section - Branding -->
        <div class="hidden lg:flex lg:flex-1 lg:flex-col lg:justify-center lg:bg-gradient-to-br lg:from-blue-600 lg:to-purple-700 lg:px-20">
            <div class="text-white">
                <div class="flex items-center mb-8">
                    <div class="w-12 h-12 bg-white rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-cash-register text-2xl text-blue-600"></i>
                    </div>
                    <h1 class="text-3xl font-bold">M Mart</h1>
                </div>
                <h2 class="text-4xl font-bold mb-6">M Mart</h2>
                <p class="text-xl text-blue-100 mb-8">Kelola transaksi dan inventori toko Anda dengan mudah dan efisien</p>
                <div class="space-y-4">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-300 mr-3"></i>
                        <span>Transaksi cepat dengan scan barcode</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-300 mr-3"></i>
                        <span>Laporan penjualan real-time</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-300 mr-3"></i>
                        <span>Manajemen stok otomatis</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Section - Login Form -->
        <div class="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-20 xl:px-24">
            <div class="mx-auto w-full max-w-sm lg:w-96">
                <div class="lg:hidden flex items-center justify-center mb-8">
                    <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-cash-register text-2xl text-white"></i>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900">KasirPro</h1>
                </div>

                <div>
                    <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Masuk ke akun Anda</h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Atau 
                        <a href="#" class="font-medium text-blue-600 hover:text-blue-500">
                            hubungi administrator untuk membuat akun
                        </a>
                    </p>
                </div>

                <form class="mt-8 space-y-6" method="POST">
                    <?php if (isset($error)): ?>
                        <div class="rounded-md bg-red-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-circle text-red-400"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800"><?php echo $error; ?></h3>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="space-y-4">
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                            <div class="mt-1 relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-gray-400"></i>
                                </div>
                                <input id="username" name="username" type="text" required 
                                       class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md placeholder-gray-500 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                       placeholder="Masukkan username">
                            </div>
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                            <div class="mt-1 relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                                <input id="password" name="password" type="password" required 
                                       class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md placeholder-gray-500 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                       placeholder="Masukkan password">
                            </div>
                        </div>
                    </div>

                    <div>
                        <button type="submit" 
                                class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <i class="fas fa-sign-in-alt text-blue-300"></i>
                            </span>
                            Masuk ke Sistem
                        </button>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-900 mb-2">Akun Demo:</h3>
                        <div class="text-xs text-gray-600 space-y-1">
                            <p><strong>Kasir:</strong> kasir1 / password</p>
                            <p><strong>Manajer:</strong> admin / password</p>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>