<?php
require_once __DIR__ . '/../config/db.php';

if (!in_array($_SESSION['role'], ['admin', 'librarian'])) {
    exit;
}

$fine_id = intval($_GET['id'] ?? 0);

if ($fine_id) {
    $stmt = $conn->prepare("UPDATE fines SET is_paid = TRUE, paid_amount = amount, payment_date = CURDATE() WHERE id = ?");
    $stmt->bind_param("i", $fine_id);
    $stmt->execute();
}
