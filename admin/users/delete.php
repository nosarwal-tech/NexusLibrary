<?php
require_once __DIR__ . '/../../config/db.php';

if ($_SESSION['role'] !== 'admin') {
    header('Location: ' . SITE_URL . '/pages/dashboard.php');
    exit;
}

$user_id = intval($_GET['id'] ?? 0);

if ($user_id && $user_id != $_SESSION['user_id']) {
    // Check if user has active transactions
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions WHERE user_id = ? AND status = 'issued'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $active_tx = $stmt->get_result()->fetch_assoc()['count'];
    
    if ($active_tx > 0) {
        flashMessage('Cannot delete user with active book issues.', 'error');
        header('Location: index.php');
        exit;
    }
    
    // Get user info for logging
    $stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    // Delete user
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], 'delete_user', "Deleted user: " . ($user['name'] ?? 'Unknown') . " (ID: $user_id)");
        flashMessage('User deleted successfully!', 'success');
    } else {
        flashMessage('Failed to delete user.', 'error');
    }
}

header('Location: index.php');
exit;
