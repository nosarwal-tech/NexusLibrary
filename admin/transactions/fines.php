<?php
require_once __DIR__ . '/../../includes/header.php';

if (!in_array($_SESSION['role'], ['admin', 'librarian'])) {
    header('Location: ' . SITE_URL . '/pages/dashboard.php');
    exit;
}

$page_title = 'Fine Management';
$breadcrumbs = [
    ['label' => 'Home', 'url' => SITE_URL . '/pages/dashboard.php'],
    ['label' => 'Fines']
];

$status = sanitize($_GET['status'] ?? '');

$where = '';
if ($status === 'paid') {
    $where = "WHERE f.is_paid = TRUE";
} elseif ($status === 'unpaid') {
    $where = "WHERE f.is_paid = FALSE";
}

$query = "SELECT f.*, u.name as user_name, u.email, b.title, t.issue_date, t.due_date, t.return_date
          FROM fines f
          JOIN users u ON f.user_id = u.id
          JOIN transactions t ON f.transaction_id = t.id
          JOIN books b ON t.book_id = b.id
          $where
          ORDER BY f.created_at DESC";

$fines = $conn->query($query);

// Stats
$total_fines = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM fines")->fetch_assoc()['total'];
$collected = $conn->query("SELECT COALESCE(SUM(paid_amount), 0) as total FROM fines WHERE is_paid = TRUE")->fetch_assoc()['total'];
$pending = $total_fines - $collected;
$unpaid_count = $conn->query("SELECT COUNT(*) as count FROM fines WHERE is_paid = FALSE")->fetch_assoc()['count'];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Fine Management</h1>
        <p class="page-subtitle">Track and manage library fines</p>
    </div>
</div>

<div class="stats-grid" style="margin-bottom: 32px;">
    <div class="card stat-card">
        <div class="card-header">
            <div>
                <h3 class="stat-value">₹<?php echo number_format($total_fines, 2); ?></h3>
                <p class="stat-label">Total Fines</p>
            </div>
            <div class="card-icon danger"><i class="fas fa-coins"></i></div>
        </div>
    </div>
    <div class="card stat-card">
        <div class="card-header">
            <div>
                <h3 class="stat-value" style="color: var(--success);">₹<?php echo number_format($collected, 2); ?></h3>
                <p class="stat-label">Collected</p>
            </div>
            <div class="card-icon success"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="card stat-card">
        <div class="card-header">
            <div>
                <h3 class="stat-value">₹<?php echo number_format($pending, 2); ?></h3>
                <p class="stat-label">Pending</p>
            </div>
            <div class="card-icon warning"><i class="fas fa-clock"></i></div>
        </div>
    </div>
    <div class="card stat-card">
        <div class="card-header">
            <div>
                <h3 class="stat-value"><?php echo $unpaid_count; ?></h3>
                <p class="stat-label">Unpaid Fines</p>
            </div>
            <div class="card-icon accent"><i class="fas fa-exclamation-triangle"></i></div>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 24px;">
    <div style="display: flex; gap: 8px;">
        <a href="index.php" class="btn <?php echo !$status ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">All</a>
        <a href="?status=unpaid" class="btn <?php echo $status === 'unpaid' ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">Unpaid</a>
        <a href="?status=paid" class="btn <?php echo $status === 'paid' ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">Paid</a>
    </div>
</div>

<div class="card">
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Member</th>
                    <th>Book</th>
                    <th>Fine Amount</th>
                    <th>Paid</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($fines->num_rows > 0): ?>
                    <?php while ($fine = $fines->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($fine['user_name']); ?></strong>
                                <br><small class="text-muted"><?php echo htmlspecialchars($fine['email']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($fine['title']); ?></td>
                            <td><strong>₹<?php echo number_format($fine['amount'], 2); ?></strong></td>
                            <td>₹<?php echo number_format($fine['paid_amount'], 2); ?></td>
                            <td>
                                <span class="badge <?php echo $fine['is_paid'] ? 'badge-success' : 'badge-warning'; ?>">
                                    <?php echo $fine['is_paid'] ? 'Paid' : 'Pending'; ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($fine['created_at']); ?></td>
                            <td>
                                <?php if (!$fine['is_paid']): ?>
                                    <button onclick="markPaid(<?php echo $fine['id']; ?>)" class="btn btn-icon btn-success" title="Mark as Paid">
                                        <i class="fas fa-check"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted" style="padding: 40px;">No fines found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function markPaid(fineId) {
    if (confirm('Mark this fine as paid?')) {
        fetch('mark_paid.php?id=' + fineId)
            .then(() => location.reload());
    }
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
