<?php
session_start();

// Jika sudah login, arahkan ke dashboard sesuai role
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'kasir') {
        header('Location: kasir/dashboard.php');
        exit;
    }

    // Default ke dashboard manager
    header('Location: manager/dashboard.php');
    exit;
}

// Jika belum login, arahkan ke halaman login
header('Location: login.php');
exit;
?>


