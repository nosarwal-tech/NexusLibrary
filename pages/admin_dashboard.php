<?php
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . '/auth/login.php');
    exit;
}

if (!in_array($_SESSION['role'], ['admin', 'librarian'])) {
    header('Location: ' . SITE_URL . '/pages/books.php');
    exit;
}

$page_title = 'Admin Dashboard';
$breadcrumbs = [
    ['label' => 'Home', 'url' => SITE_URL . '/pages/dashboard.php'],
    ['label' => 'Admin']
];

$stats = [];

// Quick stats
$result = $conn->query("SELECT COUNT(*) as count FROM books WHERE type = 'book'");
$stats['total_books'] = $result->fetch_assoc()['count'] ?? 0;

$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
$stats['total_users'] = $result->fetch_assoc()['count'] ?? 0;

$result = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE status = 'issued'");
$stats['issued'] = $result->fetch_assoc()['count'] ?? 0;

$result = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE status = 'issued' AND due_date < CURDATE()");
$stats['overdue'] = $result->fetch_assoc()['count'] ?? 0;

$result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM fines");
$stats['total_fines'] = $result->fetch_assoc()['total'] ?? 0;

$result = $conn->query("SELECT COALESCE(SUM(paid_amount), 0) as total FROM fines WHERE is_paid = TRUE");
$stats['collected_fines'] = $result->fetch_assoc()['total'] ?? 0;

// Recent activity
$recent_activity = $conn->query("
    SELECT a.*, u.name as user_name 
    FROM activity_logs a 
    LEFT JOIN users u ON a.user_id = u.id 
    ORDER BY a.created_at DESC 
    LIMIT 10
");

// Overdue list
$overdue_list = $conn->query("
    SELECT t.*, b.title, u.name as user_name, u.email, 
           DATEDIFF(CURDATE(), t.due_date) as days_late
    FROM transactions t
    JOIN books b ON t.book_id = b.id
    JOIN users u ON t.user_id = u.id
    WHERE t.status = 'issued' AND t.due_date < CURDATE()
    ORDER BY days_late DESC
    LIMIT 10
");
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Admin Dashboard</h1>
        <p class="page-subtitle">Manage your library operations</p>
    </div>
    <div class="page-actions">
        <a href="<?php echo SITE_URL; ?>/admin/books/add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Book
        </a>
    </div>
</div>

<div class="stats-grid">
    <div class="card stat-card">
        <div class="card-header">
            <div>
                <h3 class="stat-value" data-counter="<?php echo $stats['total_books']; ?>"><?php echo number_format($stats['total_books']); ?></h3>
                <p class="stat-label">Total Books</p>
            </div>
            <div class="card-icon primary">
                <i class="fas fa-book"></i>
            </div>
        </div>
    </div>
    
    <div class="card stat-card">
        <div class="card-header">
            <div>
                <h3 class="stat-value" data-counter="<?php echo $stats['issued']; ?>"><?php echo number_format($stats['issued']); ?></h3>
                <p class="stat-label">Currently Issued</p>
            </div>
            <div class="card-icon warning">
                <i class="fas fa-hand-holding"></i>
            </div>
        </div>
    </div>
    
    <div class="card stat-card">
        <div class="card-header">
            <div>
                <h3 class="stat-value" data-counter="<?php echo $stats['overdue']; ?>"><?php echo number_format($stats['overdue']); ?></h3>
                <p class="stat-label">Overdue Books</p>
            </div>
            <div class="card-icon danger">
                <i class="fas fa-exclamation-circle"></i>
            </div>
        </div>
    </div>
    
    <div class="card stat-card">
        <div class="card-header">
            <div>
                <h3 class="stat-value" data-counter="<?php echo $stats['total_users']; ?>"><?php echo number_format($stats['total_users']); ?></h3>
                <p class="stat-label">Active Members</p>
            </div>
            <div class="card-icon success">
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Overdue Books</h3>
            <a href="<?php echo SITE_URL; ?>/admin/transactions/index.php?status=overdue" class="btn btn-sm btn-secondary">
                View All <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="table-container" style="border: none;">
            <?php if ($overdue_list->num_rows > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Book</th>
                            <th>Member</th>
                            <th>Days Late</th>
                            <th>Fine</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($item = $overdue_list->fetch_assoc()): ?>
                            <?php $fine = calculateFine($item['due_date']); ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['title']); ?></td>
                                <td><?php echo htmlspecialchars($item['user_name']); ?></td>
                                <td><span class="badge badge-danger"><?php echo $item['days_late']; ?> days</span></td>
                                <td><strong class="text-danger">₹<?php echo number_format($fine, 2); ?></strong></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-check-circle"></i></div>
                    <p class="text-success">No overdue books!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Fine Collection</h3>
        </div>
        <div style="padding: 20px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; text-align: center;">
                <div>
                    <h2 style="font-family: 'Orbitron', sans-serif; color: var(--danger); margin-bottom: 8px;">
                        ₹<?php echo number_format($stats['total_fines'], 2); ?>
                    </h2>
                    <p class="text-muted">Total Fines</p>
                </div>
                <div>
                    <h2 style="font-family: 'Orbitron', sans-serif; color: var(--success); margin-bottom: 8px;">
                        ₹<?php echo number_format($stats['collected_fines'], 2); ?>
                    </h2>
                    <p class="text-muted">Collected</p>
                </div>
            </div>
            <div style="margin-top: 20px;">
                <?php
                $collection_rate = $stats['total_fines'] > 0 
                    ? ($stats['collected_fines'] / $stats['total_fines']) * 100 
                    : 0;
                ?>
                <div style="background: var(--bg-glass); height: 12px; border-radius: 6px; overflow: hidden;">
                    <div style="width: <?php echo $collection_rate; ?>%; height: 100%; background: linear-gradient(90deg, var(--success), var(--secondary));"></div>
                </div>
                <p class="text-center text-muted" style="margin-top: 8px; font-size: 13px;">
                    <?php echo number_format($collection_rate, 1); ?>% collection rate
                </p>
            </div>
            <div style="margin-top: 20px; display: flex; gap: 12px;">
                <a href="<?php echo SITE_URL; ?>/admin/transactions/issue.php" class="btn btn-primary" style="flex: 1;">
                    <i class="fas fa-hand-holding"></i> Issue Book
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/transactions/return.php" class="btn btn-success" style="flex: 1;">
                    <i class="fas fa-undo"></i> Return Book
                </a>
            </div>
        </div>
    </div>
</div>

<div class="card" style="margin-top: 24px;">
    <div class="card-header">
        <h3 class="card-title">Recent Activity</h3>
    </div>
    <div class="table-container" style="border: none;">
        <?php if ($recent_activity->num_rows > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>IP Address</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($activity = $recent_activity->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($activity['user_name'] ?? 'System'); ?></td>
                            <td><span class="badge badge-primary"><?php echo htmlspecialchars($activity['action']); ?></span></td>
                            <td><?php echo htmlspecialchars($activity['description'] ?? '-'); ?></td>
                            <td><code style="font-size: 12px;"><?php echo htmlspecialchars($activity['ip_address'] ?? '-'); ?></code></td>
                            <td><?php echo formatDateTime($activity['created_at']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <p class="text-muted">No recent activity</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
