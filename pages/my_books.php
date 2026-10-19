<?php
require_once __DIR__ . '/../includes/header.php';

$page_title = 'My Books';
$breadcrumbs = [
    ['label' => 'Home', 'url' => SITE_URL . '/pages/dashboard.php'],
    ['label' => 'My Books']
];

$filter = sanitize($_GET['filter'] ?? 'all');

// Get user's transactions
$where = "WHERE t.user_id = ?";
$params = [$_SESSION['user_id']];

if ($filter === 'borrowed') {
    $where .= " AND t.status = 'issued'";
} elseif ($filter === 'returned') {
    $where .= " AND t.status = 'returned'";
}

$query = "SELECT t.*, b.title, b.isbn, b.author, c.name as category_name
          FROM transactions t
          JOIN books b ON t.book_id = b.id
          LEFT JOIN categories c ON b.category_id = c.id
          $where
          ORDER BY t.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$transactions = $stmt->get_result();

// Count stats
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions WHERE user_id = ? AND status = 'issued'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$borrowed_count = $stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions WHERE user_id = ? AND status = 'issued' AND due_date < CURDATE()");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$overdue_count = $stmt->get_result()->fetch_assoc()['count'];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">My Books</h1>
        <p class="page-subtitle">Track your borrowed books and history</p>
    </div>
</div>

<div class="stats-grid" style="margin-bottom: 32px;">
    <div class="card stat-card">
        <div class="card-header">
            <div>
                <h3 class="stat-value"><?php echo $borrowed_count; ?></h3>
                <p class="stat-label">Currently Borrowed</p>
            </div>
            <div class="card-icon warning"><i class="fas fa-book"></i></div>
        </div>
    </div>
    <div class="card stat-card">
        <div class="card-header">
            <div>
                <h3 class="stat-value" style="color: var(--danger);"><?php echo $overdue_count; ?></h3>
                <p class="stat-label">Overdue</p>
            </div>
            <div class="card-icon danger"><i class="fas fa-exclamation-circle"></i></div>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 24px;">
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="?filter=all" class="btn <?php echo $filter === 'all' ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
            All Books
        </a>
        <a href="?filter=borrowed" class="btn <?php echo $filter === 'borrowed' ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
            Currently Borrowed
        </a>
        <a href="?filter=returned" class="btn <?php echo $filter === 'returned' ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
            Returned
        </a>
        <a href="<?php echo SITE_URL; ?>/pages/wishlist.php" class="btn btn-info btn-sm">
            <i class="fas fa-heart"></i> My Wishlist
        </a>
    </div>
</div>

<div class="card">
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Book</th>
                    <th>Category</th>
                    <th>Issue Date</th>
                    <th>Due Date</th>
                    <th>Return Date</th>
                    <th>Fine</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($transactions->num_rows > 0): ?>
                    <?php while ($tx = $transactions->fetch_assoc()): ?>
                        <?php 
                        $is_overdue = $tx['status'] === 'issued' && strtotime($tx['due_date']) < time();
                        $fine = calculateFine($tx['due_date'], $tx['return_date'] ?? null);
                        ?>
                        <tr class="<?php echo $is_overdue ? 'overdue-row' : ''; ?>">
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 40px; height: 40px; background: var(--bg-glass); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-book" style="color: var(--primary);"></i>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($tx['title']); ?></strong>
                                        <br><small class="text-muted">by <?php echo htmlspecialchars($tx['author']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($tx['category_name'] ?? '-'); ?></td>
                            <td><?php echo formatDate($tx['issue_date']); ?></td>
                            <td>
                                <?php echo formatDate($tx['due_date']); ?>
                                <?php if ($is_overdue): ?>
                                    <br><small class="text-danger"><?php echo floor((time() - strtotime($tx['due_date'])) / (60*60*24)); ?> days overdue</small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $tx['return_date'] ? formatDate($tx['return_date']) : '-'; ?></td>
                            <td>
                                <?php if ($fine > 0): ?>
                                    <strong class="text-danger">₹<?php echo number_format($fine, 2); ?></strong>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                if ($is_overdue) {
                                    $status_class = 'badge-danger';
                                    $status_text = 'Overdue';
                                } else {
                                    $status_map = [
                                        'issued' => ['badge-warning', 'Borrowed'],
                                        'returned' => ['badge-success', 'Returned']
                                    ];
                                    $status_class = $status_map[$tx['status']][0] ?? 'badge-secondary';
                                    $status_text = $status_map[$tx['status']][1] ?? ucfirst($tx['status']);
                                }
                                ?>
                                <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted" style="padding: 40px;">
                            <div class="empty-state-icon" style="margin: 0 auto 16px;">
                                <i class="fas fa-book-open"></i>
                            </div>
                            <p>No books found in this category.</p>
                            <a href="<?php echo SITE_URL; ?>/pages/books.php" class="btn btn-primary btn-sm">
                                Browse Books
                            </a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.overdue-row {
    background: rgba(255, 71, 87, 0.05);
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
