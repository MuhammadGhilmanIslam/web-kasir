<?php
require_once __DIR__ . '/includes/auth.php';

// Tentukan tautan kembali berdasarkan role jika login
$backUrl = 'login.php';
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'kasir') {
        $backUrl = 'kasir/dashboard.php';
    } elseif ($_SESSION['role'] === 'manajer') {
        $backUrl = 'manager/dashboard.php';
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditolak - M Mart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="h-full">
    <div class="min-h-full flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <div class="mx-auto h-16 w-16 rounded-full bg-red-100 flex items-center justify-center mb-4">
                    <i class="fas fa-ban text-red-500 text-2xl"></i>
                </div>
                <h2 class="mt-2 text-2xl font-bold text-gray-900">Akses Ditolak</h2>
                <p class="mt-2 text-sm text-gray-600">Anda tidak memiliki izin untuk mengakses halaman ini.</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow">
                <ul class="list-disc list-inside text-sm text-gray-600 space-y-1">
                    <li>Pastikan Anda login dengan akun yang benar.</li>
                    <li>Jika Anda kasir, gunakan menu di dashboard kasir.</li>
                    <li>Jika Anda manajer, gunakan menu di dashboard manager.</li>
                </ul>
                <div class="mt-6">
                    <a href="<?php echo $backUrl; ?>" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>


