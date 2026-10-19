<?php
session_start();

if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../config/db.php';
    logActivity($_SESSION['user_id'], 'logout', 'User logged out');
}

session_unset();
session_destroy();

if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

header('Location: ' . (SITE_URL ?? 'http://localhost/library') . '/auth/login.php?logout=1');
exit;
