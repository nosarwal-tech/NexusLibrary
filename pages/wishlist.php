<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . '/auth/login.php');
    exit;
}

$page_title = 'My Wishlist';

if (isset($_GET['cancel'])) {
    $reservation_id = intval($_GET['cancel']);
    if ($reservation_id) {
        $stmt = $conn->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $reservation_id, $_SESSION['user_id']);
        $stmt->execute();
        flashMessage('Book removed from wishlist!', 'success');
        header('Location: wishlist.php');
        exit;
    }
}

if (isset($_GET['remove'])) {
    $reservation_id = intval($_GET['remove']);
    if ($reservation_id) {
        $stmt = $conn->prepare("DELETE FROM reservations WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $reservation_id, $_SESSION['user_id']);
        $stmt->execute();
        flashMessage('Book removed from wishlist!', 'success');
        header('Location: wishlist.php');
        exit;
    }
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT r.*, b.title, b.author, b.isbn, b.available_copies, COALESCE(b.issuable, 'yes') as issuable
    FROM reservations r
    JOIN books b ON r.book_id = b.id
    WHERE r.user_id = ?
    ORDER BY r.status ASC, r.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reservations = $stmt->get_result();

$active_count = $conn->prepare("SELECT COUNT(*) as count FROM reservations WHERE user_id = ? AND status = 'active'");
$active_count->bind_param("i", $user_id);
$active_count->execute();
$active_reservations = $active_count->get_result()->fetch_assoc()['count'];
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
    <div id="toastContainer" class="toast-container"></div>
    <div class="app-container">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <div class="main-content">
            <?php include __DIR__ . '/../includes/topbar.php'; ?>
            <main class="page-content">
                <?php showFlashMessage(); ?>
                
                <div class="page-header">
                    <div>
                        <h1 class="page-title">My Wishlist</h1>
                        <p class="page-subtitle">Books you've saved to read later</p>
                    </div>
                </div>

                <div class="stats-grid" style="margin-bottom: 32px;">
                    <div class="card stat-card">
                        <div class="card-header">
                            <div>
                                <h3 class="stat-value"><?php echo $active_reservations; ?></h3>
                                <p class="stat-label">Books in Wishlist</p>
                            </div>
                            <div class="card-icon primary"><i class="fas fa-heart"></i></div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <?php if ($reservations->num_rows > 0): ?>
                        <div class="books-grid" style="padding: 20px;">
                            <?php while ($res = $reservations->fetch_assoc()): ?>
                                <?php 
                                $is_expired = $res['status'] === 'active' && strtotime($res['expiry_date']) < time();
                                $is_available = $res['available_copies'] > 0 && ($res['issuable'] ?? 'yes') === 'yes';
                                ?>
                                <div class="book-card card" style="padding: 0;">
                                    <div class="book-cover">
                                        <i class="fas fa-book"></i>
                                    </div>
                                    <div class="book-info">
                                        <h3 class="book-title"><?php echo htmlspecialchars($res['title']); ?></h3>
                                        <p class="book-author">by <?php echo htmlspecialchars($res['author']); ?></p>
                                        
                                        <div style="display: flex; flex-wrap: wrap; gap: 8px; margin: 12px 0;">
                                            <?php if ($is_available): ?>
                                                <span class="badge badge-success"><i class="fas fa-check"></i> Available</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger"><i class="fas fa-times"></i> Unavailable</span>
                                            <?php endif; ?>
                                            
                                            <?php if ($res['status'] === 'fulfilled'): ?>
                                                <span class="badge badge-primary">Borrowed</span>
                                            <?php elseif ($res['status'] === 'cancelled'): ?>
                                                <span class="badge badge-secondary">Removed</span>
                                            <?php elseif ($is_expired): ?>
                                                <span class="badge badge-warning">Expired</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if ($res['status'] === 'active' && !$is_expired): ?>
                                            <?php if ($is_available): ?>
                                                <a href="<?php echo SITE_URL; ?>/admin/transactions/issue.php?book_id=<?php echo $res['book_id']; ?>&reservation_id=<?php echo $res['id']; ?>" class="btn btn-sm btn-success" style="width: 100%; margin-bottom: 8px;">
                                                    <i class="fas fa-hand-holding"></i> Borrow Now
                                                </a>
                                            <?php endif; ?>
                                            <a href="wishlist.php?cancel=<?php echo $res['id']; ?>" class="btn btn-sm btn-danger" style="width: 100%;" onclick="return confirm('Remove this book from your wishlist?')">
                                                <i class="fas fa-trash"></i> Remove
                                            </a>
                                        <?php elseif ($res['status'] === 'fulfilled'): ?>
                                            <a href="<?php echo SITE_URL; ?>/pages/my_books.php" class="btn btn-sm btn-primary" style="width: 100%;">
                                                <i class="fas fa-book"></i> View My Books
                                            </a>
                                        <?php elseif ($res['status'] === 'cancelled' || $is_expired): ?>
                                            <a href="wishlist.php?remove=<?php echo $res['id']; ?>" class="btn btn-sm btn-secondary" style="width: 100%;" onclick="return confirm('Delete this wishlist entry?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state" style="padding: 60px 20px;">
                            <div class="empty-state-icon">
                                <i class="fas fa-heart"></i>
                            </div>
                            <h3 class="empty-state-title">Your wishlist is empty</h3>
                            <p class="empty-state-text">Browse our catalog and add books you want to read later!</p>
                            <a href="<?php echo SITE_URL; ?>/pages/books.php" class="btn btn-primary">
                                <i class="fas fa-search"></i> Browse Books
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
