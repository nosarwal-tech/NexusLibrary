<?php
require_once __DIR__ . '/../includes/header.php';

$page_title = 'Dashboard';
$breadcrumbs = [
    ['label' => 'Home'],
    ['label' => 'Dashboard']
];

$user_role = $_SESSION['role'] ?? '';

// Get dashboard statistics
$stats = [];

// Total books
$result = $conn->query("SELECT COUNT(*) as count FROM books WHERE type = 'book'");
$stats['total_books'] = $result->fetch_assoc()['count'] ?? 0;

// Total magazines
$result = $conn->query("SELECT COUNT(*) as count FROM books WHERE type = 'magazine'");
$stats['total_magazines'] = $result->fetch_assoc()['count'] ?? 0;

// Total newspapers
$result = $conn->query("SELECT COUNT(*) as count FROM books WHERE type = 'newspaper'");
$stats['total_newspapers'] = $result->fetch_assoc()['count'] ?? 0;

// Total users
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
$stats['total_users'] = $result->fetch_assoc()['count'] ?? 0;

// Issued books
$result = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE status = 'issued'");
$stats['issued_books'] = $result->fetch_assoc()['count'] ?? 0;

// Available books
$result = $conn->query("SELECT SUM(available_copies) as total FROM books WHERE type = 'book' AND status = 'available'");
$stats['available_books'] = $result->fetch_assoc()['total'] ?? 0;

// Overdue books
$result = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE status = 'issued' AND due_date < CURDATE()");
$stats['overdue_books'] = $result->fetch_assoc()['count'] ?? 0;

// Total fines collected this month
$result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM fines WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
$stats['fines_collected'] = $result->fetch_assoc()['total'] ?? 0;

// Recent transactions
$recent_transactions = $conn->query("
    SELECT t.*, b.title, b.isbn, u.name as user_name, u.email as user_email
    FROM transactions t
    JOIN books b ON t.book_id = b.id
    JOIN users u ON t.user_id = u.id
    ORDER BY t.created_at DESC
    LIMIT 10
");

// Most borrowed books
$popular_books = $conn->query("
    SELECT b.id, b.title, b.author, COUNT(t.id) as borrow_count
    FROM books b
    LEFT JOIN transactions t ON b.id = t.book_id
    GROUP BY b.id, b.title, b.author
    ORDER BY borrow_count DESC
    LIMIT 5
");

// Monthly issue stats for chart
$monthly_stats = $conn->query("
    SELECT 
        DATE_FORMAT(issue_date, '%b') as month,
        COUNT(*) as count
    FROM transactions
    WHERE issue_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(issue_date, '%Y-%m')
    ORDER BY MIN(issue_date)
");

$chart_data = [];
while ($row = $monthly_stats->fetch_assoc()) {
    $chart_data[] = ['label' => $row['month'], 'value' => (int)$row['count']];
}

// Category distribution
$category_stats = $conn->query("
    SELECT c.name, COUNT(b.id) as count
    FROM categories c
    LEFT JOIN books b ON c.id = b.category_id
    GROUP BY c.id, c.name
    ORDER BY count DESC
    LIMIT 5
");
?>

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
                <h3 class="stat-value" data-counter="<?php echo $stats['issued_books']; ?>"><?php echo number_format($stats['issued_books']); ?></h3>
                <p class="stat-label">Issued Books</p>
            </div>
            <div class="card-icon secondary">
                <i class="fas fa-hand-holding"></i>
            </div>
        </div>
    </div>
    
    <div class="card stat-card">
        <div class="card-header">
            <div>
                <h3 class="stat-value" data-counter="<?php echo $stats['available_books']; ?>"><?php echo number_format($stats['available_books']); ?></h3>
                <p class="stat-label">Available Books</p>
            </div>
            <div class="card-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
    </div>
    
    <div class="card stat-card">
        <div class="card-header">
            <div>
                <h3 class="stat-value" data-counter="<?php echo $stats['overdue_books']; ?>"><?php echo number_format($stats['overdue_books']); ?></h3>
                <p class="stat-label">Overdue Books</p>
            </div>
            <div class="card-icon danger">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
        </div>
    </div>
    
    <div class="card stat-card">
        <div class="card-header">
            <div>
                <h3 class="stat-value" data-counter="<?php echo $stats['total_users']; ?>"><?php echo number_format($stats['total_users']); ?></h3>
                <p class="stat-label">Active Members</p>
            </div>
            <div class="card-icon accent">
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>
    
    <div class="card stat-card">
        <div class="card-header">
            <div>
                <h3 class="stat-value">₹<?php echo number_format($stats['fines_collected'], 2); ?></h3>
                <p class="stat-label">Fines This Month</p>
            </div>
            <div class="card-icon warning">
                <i class="fas fa-coins"></i>
            </div>
        </div>
    </div>
</div>

<div class="grid-2" style="margin-bottom: 32px;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Monthly Issues</h3>
        </div>
        <div class="chart-container" id="monthlyChart"></div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Popular Books</h3>
        </div>
        <div class="popular-books-list">
            <?php if ($popular_books->num_rows > 0): ?>
                <?php while ($book = $popular_books->fetch_assoc()): ?>
                    <div class="popular-book-item">
                        <div class="popular-book-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="popular-book-info">
                            <h4><?php echo htmlspecialchars($book['title']); ?></h4>
                            <p><?php echo htmlspecialchars($book['author']); ?></p>
                        </div>
                        <div class="popular-book-count">
                            <span class="badge badge-primary"><?php echo $book['borrow_count']; ?> borrows</span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p class="text-muted">No borrowing data yet</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Recent Transactions</h3>
        <a href="<?php echo SITE_URL; ?>/admin/transactions/index.php" class="btn btn-sm btn-secondary">
            View All <i class="fas fa-arrow-right"></i>
        </a>
    </div>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Book</th>
                    <th>Member</th>
                    <th>Issue Date</th>
                    <th>Due Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($recent_transactions->num_rows > 0): ?>
                    <?php while ($tx = $recent_transactions->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 40px; height: 40px; background: var(--bg-glass); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-book" style="color: var(--primary);"></i>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($tx['title']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($tx['isbn']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($tx['user_name']); ?></strong>
                                <br><small class="text-muted"><?php echo htmlspecialchars($tx['user_email']); ?></small>
                            </td>
                            <td><?php echo formatDate($tx['issue_date']); ?></td>
                            <td>
                                <?php echo formatDate($tx['due_date']); ?>
                                <?php if ($tx['status'] === 'issued' && strtotime($tx['due_date']) < time()): ?>
                                    <span class="badge badge-danger">Overdue</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $status_badge = match($tx['status']) {
                                    'issued' => 'badge-warning',
                                    'returned' => 'badge-success',
                                    'overdue' => 'badge-danger',
                                    default => 'badge-secondary'
                                };
                                ?>
                                <span class="badge <?php echo $status_badge; ?>"><?php echo ucfirst($tx['status']); ?></span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted" style="padding: 40px;">
                            No transactions found
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.popular-books-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.popular-book-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 12px;
    background: var(--bg-glass);
    border-radius: var(--radius-md);
    transition: all var(--transition-fast);
}

.popular-book-item:hover {
    background: var(--bg-glass-hover);
}

.popular-book-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.popular-book-info {
    flex: 1;
}

.popular-book-info h4 {
    font-size: 14px;
    margin-bottom: 4px;
}

.popular-book-info p {
    font-size: 12px;
    color: var(--text-secondary);
    margin: 0;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Monthly Chart
    const monthlyData = <?php echo json_encode($chart_data); ?>;
    if (monthlyData.length > 0) {
        initSimpleChart('monthlyChart', monthlyData, { type: 'bar', height: 280 });
    } else {
        document.getElementById('monthlyChart').innerHTML = '<div class="empty-state"><p class="text-muted">No data available</p></div>';
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
