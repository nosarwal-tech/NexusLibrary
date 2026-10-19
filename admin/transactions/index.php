<?php
require_once __DIR__ . '/../../includes/header.php';

if (!in_array($_SESSION['role'], ['admin', 'librarian'])) {
    header('Location: ' . SITE_URL . '/pages/dashboard.php');
    exit;
}

$page_title = 'Transactions';
$breadcrumbs = [
    ['label' => 'Home', 'url' => SITE_URL . '/pages/dashboard.php'],
    ['label' => 'Transactions']
];

$status_filter = sanitize($_GET['status'] ?? '');
$search = sanitize($_GET['search'] ?? '');

$where = [];
$params = [];
$types = '';

if ($status_filter) {
    $where[] = "t.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($search) {
    $where[] = "(b.title LIKE ? OR u.name LIKE ? OR b.isbn LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'sss';
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Count
$count_query = "SELECT COUNT(*) as total FROM transactions t $where_clause";
if (!empty($params)) {
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
} else {
    $total = $conn->query($count_query)->fetch_assoc()['total'];
}
$total_pages = ceil($total / $per_page);

// Get transactions
$query = "SELECT t.*, b.title, b.isbn, u.name as user_name, u.email as user_email
          FROM transactions t
          JOIN books b ON t.book_id = b.id
          JOIN users u ON t.user_id = u.id
          $where_clause
          ORDER BY t.created_at DESC
          LIMIT $per_page OFFSET $offset";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $transactions = $stmt->get_result();
} else {
    $transactions = $conn->query($query);
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Transaction Management</h1>
        <p class="page-subtitle">Manage book issues and returns</p>
    </div>
    <div class="page-actions">
        <a href="issue.php" class="btn btn-primary">
            <i class="fas fa-hand-holding"></i> Issue Book
        </a>
        <a href="return.php" class="btn btn-success">
            <i class="fas fa-undo"></i> Return Book
        </a>
    </div>
</div>

<div class="card" style="margin-bottom: 24px;">
    <form method="GET" action="" style="display: flex; gap: 16px; flex-wrap: wrap; align-items: flex-end;">
        <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
            <label class="form-label">Search</label>
            <input type="text" name="search" class="form-input" placeholder="Book, member, or ISBN..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="">All Status</option>
                <option value="issued" <?php echo $status_filter === 'issued' ? 'selected' : ''; ?>>Issued</option>
                <option value="returned" <?php echo $status_filter === 'returned' ? 'selected' : ''; ?>>Returned</option>
                <option value="overdue" <?php echo $status_filter === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-filter"></i> Filter
        </button>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-times"></i> Clear
        </a>
    </form>
</div>

<div class="card">
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Book</th>
                    <th>Member</th>
                    <th>Issue Date</th>
                    <th>Due Date</th>
                    <th>Return Date</th>
                    <th>Fine</th>
                    <th>Status</th>
                    <th>Actions</th>
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
                                <strong><?php echo htmlspecialchars($tx['title']); ?></strong>
                                <br><small class="text-muted"><?php echo htmlspecialchars($tx['isbn'] ?? ''); ?></small>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($tx['user_name']); ?></strong>
                                <br><small class="text-muted"><?php echo htmlspecialchars($tx['user_email']); ?></small>
                            </td>
                            <td><?php echo formatDate($tx['issue_date']); ?></td>
                            <td>
                                <?php echo formatDate($tx['due_date']); ?>
                                <?php if ($is_overdue): ?>
                                    <br><small class="text-danger"><?php echo floor((time() - strtotime($tx['due_date'])) / (60*60*24)); ?> days late</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo $tx['return_date'] ? formatDate($tx['return_date']) : '-'; ?>
                            </td>
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
                                        'issued' => ['badge-warning', 'Issued'],
                                        'returned' => ['badge-success', 'Returned'],
                                        'lost' => ['badge-danger', 'Lost']
                                    ];
                                    $status_class = $status_map[$tx['status']][0] ?? 'badge-secondary';
                                    $status_text = $status_map[$tx['status']][1] ?? ucfirst($tx['status']);
                                }
                                ?>
                                <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                            </td>
                            <td>
                                <div class="actions">
                                    <?php if ($tx['status'] === 'issued'): ?>
                                        <a href="return.php?id=<?php echo $tx['id']; ?>" class="btn btn-icon btn-success" title="Return">
                                            <i class="fas fa-undo"></i>
                                        </a>
                                        <a href="renew.php?id=<?php echo $tx['id']; ?>" class="btn btn-icon btn-secondary" title="Renew">
                                            <i class="fas fa-redo"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted" style="padding: 40px;">
                            No transactions found
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($total_pages > 1): ?>
        <div class="table-pagination">
            <div class="pagination-info">
                Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $per_page, $total); ?> of <?php echo $total; ?> entries
            </div>
            <div class="pagination-controls">
                <?php
                $query_params = [];
                if ($search) $query_params['search'] = $search;
                if ($status_filter) $query_params['status'] = $status_filter;
                $base_url = '?' . http_build_query($query_params) . '&page=';
                ?>
                
                <?php if ($page > 1): ?>
                    <a href="<?php echo $base_url . ($page - 1); ?>" class="pagination-btn"><i class="fas fa-chevron-left"></i></a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <a href="<?php echo $base_url . $i; ?>" class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="<?php echo $base_url . ($page + 1); ?>" class="pagination-btn"><i class="fas fa-chevron-right"></i></a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.overdue-row {
    background: rgba(255, 71, 87, 0.05);
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
