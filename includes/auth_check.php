<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

function checkAuth() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        header('Location: ' . SITE_URL . '/auth/login.php');
        exit;
    }
}

function requireAdmin() {
    checkAuth();
    if (!in_array($_SESSION['role'], ['admin', 'librarian'])) {
        header('Location: ' . SITE_URL . '/pages/books.php');
        exit;
    }
}
?>
