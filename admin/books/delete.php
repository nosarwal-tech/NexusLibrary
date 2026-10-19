<?php
require_once __DIR__ . '/../../config/db.php';

if (!in_array($_SESSION['role'], ['admin', 'librarian'])) {
    header('Location: ' . SITE_URL . '/pages/dashboard.php');
    exit;
}

$book_id = intval($_GET['id'] ?? 0);

if ($book_id) {
    // Check if book has active transactions
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions WHERE book_id = ? AND status = 'issued'");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $active_tx = $stmt->get_result()->fetch_assoc()['count'];
    
    if ($active_tx > 0) {
        flashMessage('Cannot delete book with active issues.', 'error');
        header('Location: index.php');
        exit;
    }
    
    // Get book info for logging
    $stmt = $conn->prepare("SELECT title FROM books WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $book = $stmt->get_result()->fetch_assoc();
    
    // Delete book
    $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    
    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], 'delete_book', "Deleted book: " . ($book['title'] ?? 'Unknown') . " (ID: $book_id)");
        flashMessage('Book deleted successfully!', 'success');
    } else {
        flashMessage('Failed to delete book.', 'error');
    }
}

header('Location: index.php');
exit;
