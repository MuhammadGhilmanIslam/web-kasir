<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isKasir() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'kasir';
}

function isManajer() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'manajer';
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit;
    }
}

function redirectIfNotAuthorized($requiredRole) {
    redirectIfNotLoggedIn();
    
    if ($requiredRole === 'kasir' && !isKasir()) {
        header('Location: ../unauthorized.php');
        exit;
    }
    
    if ($requiredRole === 'manajer' && !isManajer()) {
        header('Location: ../unauthorized.php');
        exit;
    }
}

function getCurrentUser($pdo) {
    if (!isLoggedIn()) return null;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}
?>