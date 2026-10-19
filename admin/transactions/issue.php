<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';

if (!in_array($_SESSION['role'], ['admin', 'librarian'])) {
    header('Location: ' . SITE_URL . '/pages/dashboard.php');
    exit;
}

$page_title = 'Issue Book';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_id = intval($_POST['book_id'] ?? 0);
    $user_id = intval($_POST['user_id'] ?? 0);
    $due_days = intval($_POST['due_days'] ?? 14);
    $reservation_id = intval($_POST['reservation_id'] ?? 0);
    
    if (!$book_id || !$user_id) {
        $error = 'Please select both a book and a member.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM books WHERE id = ? AND available_copies > 0");
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $book = $stmt->get_result()->fetch_assoc();
        
        if (!$book) {
            $error = 'Selected book is not available.';
        } elseif (($book['issuable'] ?? 'yes') === 'no') {
            $error = 'This book is marked as reference-only and cannot be issued.';
        } else {
            $stmt = $conn->prepare("SELECT u.*, COUNT(t.id) as active_borrows FROM users u LEFT JOIN transactions t ON u.id = t.user_id AND t.status = 'issued' WHERE u.id = ? GROUP BY u.id");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            
            if ($user['active_borrows'] >= $user['borrow_limit']) {
                $error = "This member has reached their borrowing limit of {$user['borrow_limit']} books.";
            } else {
                $issue_date = date('Y-m-d');
                $due_date = date('Y-m-d', strtotime("+$due_days days"));
                
                $stmt = $conn->prepare("INSERT INTO transactions (book_id, user_id, issued_by, issue_date, due_date, status) VALUES (?, ?, ?, ?, ?, 'issued')");
                $stmt->bind_param("iiiss", $book_id, $user_id, $_SESSION['user_id'], $issue_date, $due_date);
                
                if ($stmt->execute()) {
                    $conn->query("UPDATE books SET available_copies = available_copies - 1 WHERE id = $book_id");
                    
                    if ($reservation_id) {
                        $conn->query("UPDATE reservations SET status = 'fulfilled' WHERE id = $reservation_id");
                        sendNotification($user_id, 'Reservation Fulfilled', 'Your reserved book has been issued to you. Please collect it from the library.', 'success');
                    }
                    
                    $tx_id = $conn->insert_id;
                    logActivity($_SESSION['user_id'], 'issue_book', "Issued book ID $book_id to user ID $user_id (TX: $tx_id)");
                    
                    flashMessage($reservation_id ? 'Book issued successfully! Reservation fulfilled.' : 'Book issued successfully!', 'success');
                    header('Location: index.php');
                    exit;
                } else {
                    $error = 'Failed to issue book. Please try again.';
                }
            }
        }
    }
}

$available_books = $conn->query("
    SELECT b.*, c.name as category_name 
    FROM books b 
    LEFT JOIN categories c ON b.category_id = c.id 
    WHERE b.type = 'book' AND b.available_copies > 0 AND b.status = 'available' AND b.issuable = 'yes'
    ORDER BY b.title
");

$members = $conn->query("
    SELECT * FROM users 
    WHERE role IN ('member', 'librarian', 'admin') AND status = 'active'
    ORDER BY name
");

$pre_book_id = intval($_GET['book_id'] ?? 0);
$pre_user_id = intval($_GET['user_id'] ?? 0);
$pre_reservation_id = intval($_GET['reservation_id'] ?? 0);

if ($pre_reservation_id) {
    $stmt = $conn->prepare("SELECT * FROM reservations WHERE id = ? AND status = 'active'");
    $stmt->bind_param("i", $pre_reservation_id);
    $stmt->execute();
    $pre_reservation = $stmt->get_result()->fetch_assoc();
    if ($pre_reservation) {
        $pre_book_id = $pre_reservation['book_id'];
        $pre_user_id = $pre_reservation['user_id'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title><?php echo $page_title; ?> | <?php echo htmlspecialchars(getLibraryName()); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Orbitron:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
</head>
<body>
    <div class="app-container">
        <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
        <div class="main-content">
            <?php include __DIR__ . '/../../includes/topbar.php'; ?>
            <main class="page-content">
                <?php showFlashMessage(); ?>
                
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Issue Book</h1>
                        <p class="page-subtitle">Issue a book to a library member</p>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="card" style="max-width: 600px;">
                    <form method="POST" action="" data-validate>
                        <?php if ($pre_reservation_id): ?>
                        <input type="hidden" name="reservation_id" value="<?php echo $pre_reservation_id; ?>">
                        <?php endif; ?>
                        <div class="form-group">
                            <label class="form-label">Select Book *</label>
                            <select name="book_id" class="form-select" required>
                                <option value="">-- Select a Book --</option>
                                <?php 
                                $available_books->data_seek(0);
                                while ($book = $available_books->fetch_assoc()): ?>
                                    <option value="<?php echo $book['id']; ?>" <?php echo ($pre_book_id ?? $_POST['book_id'] ?? '') == $book['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($book['title']); ?> by <?php echo htmlspecialchars($book['author']); ?>
                                        (<?php echo $book['available_copies']; ?> available)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Select Member *</label>
                            <select name="user_id" class="form-select" required>
                                <option value="">-- Select a Member --</option>
                                <?php 
                                $members->data_seek(0);
                                while ($member = $members->fetch_assoc()): ?>
                                    <?php
                                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions WHERE user_id = ? AND status = 'issued'");
                                    $stmt->bind_param("i", $member['id']);
                                    $stmt->execute();
                                    $borrowed = $stmt->get_result()->fetch_assoc()['count'];
                                    ?>
                                    <option value="<?php echo $member['id']; ?>" <?php echo ($pre_user_id ?? $_POST['user_id'] ?? '') == $member['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($member['name']); ?> (<?php echo $borrowed; ?>/<?php echo $member['borrow_limit']; ?> borrowed)
                                        - <?php echo htmlspecialchars($member['email']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" style="max-width: 200px;">
                            <label class="form-label">Due Date (Days)</label>
                            <select name="due_days" class="form-select">
                                <option value="7" <?php echo ($_POST['due_days'] ?? '14') === '7' ? 'selected' : ''; ?>>7 days</option>
                                <option value="14" <?php echo ($_POST['due_days'] ?? '14') === '14' ? 'selected' : ''; ?>>14 days</option>
                                <option value="21" <?php echo ($_POST['due_days'] ?? '14') === '21' ? 'selected' : ''; ?>>21 days</option>
                                <option value="30" <?php echo ($_POST['due_days'] ?? '14') === '30' ? 'selected' : ''; ?>>30 days</option>
                            </select>
                        </div>
                        
                        <div style="display: flex; gap: 12px; margin-top: 24px;">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-hand-holding"></i> Issue Book
                            </button>
                            <a href="index.php" class="btn btn-secondary btn-lg">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
