<?php
require_once __DIR__ . '/../../includes/auth_check.php';

if (!in_array($_SESSION['role'], ['admin', 'librarian'])) {
    header('Location: ' . SITE_URL . '/pages/dashboard.php');
    exit;
}

$page_title = 'Reports';
$report_type = sanitize($_GET['type'] ?? 'overview');
$date_from = sanitize($_GET['date_from'] ?? date('Y-m-01'));
$date_to = sanitize($_GET['date_to'] ?? date('Y-m-d'));

$report_data = [];

switch ($report_type) {
    case 'transactions':
        $stmt = $conn->prepare("
            SELECT DATE(issue_date) as date, COUNT(*) as count
            FROM transactions
            WHERE issue_date BETWEEN ? AND ?
            GROUP BY DATE(issue_date)
            ORDER BY date
        ");
        $stmt->bind_param("ss", $date_from, $date_to);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $report_data[] = $row;
        }
        break;
        
    case 'overdue':
        $stmt = $conn->prepare("
            SELECT t.*, b.title, u.name as user_name
            FROM transactions t
            JOIN books b ON t.book_id = b.id
            JOIN users u ON t.user_id = u.id
            WHERE t.status = 'issued' AND t.due_date < CURDATE()
            ORDER BY t.due_date
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $report_data[] = $row;
        }
        break;
        
    case 'popular':
        $stmt = $conn->prepare("
            SELECT b.id, b.title, b.author, COUNT(t.id) as issue_count
            FROM books b
            LEFT JOIN transactions t ON b.id = t.book_id
            WHERE t.issue_date BETWEEN ? AND ?
            GROUP BY b.id
            ORDER BY issue_count DESC
            LIMIT 20
        ");
        $stmt->bind_param("ss", $date_from, $date_to);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $report_data[] = $row;
        }
        break;
        
    case 'fines':
        $stmt = $conn->prepare("
            SELECT f.*, u.name as user_name, b.title
            FROM fines f
            JOIN users u ON f.user_id = u.id
            JOIN transactions t ON f.transaction_id = t.id
            JOIN books b ON t.book_id = b.id
            WHERE DATE(f.created_at) BETWEEN ? AND ?
            ORDER BY f.created_at DESC
        ");
        $stmt->bind_param("ss", $date_from, $date_to);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $report_data[] = $row;
        }
        break;
        
    default:
        // Overview stats
        $stats = [];
        
        // Total issues in period
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions WHERE issue_date BETWEEN ? AND ?");
        $stmt->bind_param("ss", $date_from, $date_to);
        $stmt->execute();
        $stats['total_issues'] = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
        
        // Total returns in period
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions WHERE return_date BETWEEN ? AND ?");
        $stmt->bind_param("ss", $date_from, $date_to);
        $stmt->execute();
        $stats['total_returns'] = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
        
        // Total fines in period
        $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM fines WHERE DATE(created_at) BETWEEN ? AND ?");
        $stmt->bind_param("ss", $date_from, $date_to);
        $stmt->execute();
        $stats['total_fines'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        
        // Collected fines
        $stmt = $conn->prepare("SELECT COALESCE(SUM(paid_amount), 0) as total FROM fines WHERE is_paid = TRUE AND DATE(created_at) BETWEEN ? AND ?");
        $stmt->bind_param("ss", $date_from, $date_to);
        $stmt->execute();
        $stats['collected_fines'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        
        // Active users
        $stmt = $conn->prepare("SELECT COUNT(DISTINCT user_id) as count FROM transactions WHERE issue_date BETWEEN ? AND ?");
        $stmt->bind_param("ss", $date_from, $date_to);
        $stmt->execute();
        $stats['active_users'] = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
        
        $report_data = $stats;
}

if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="library_report_' . $report_type . '_' . date('Y-m-d') . '.csv"');
    
    if (!empty($report_data)) {
        $output = fopen('php://output', 'w');
        fputcsv($output, array_keys($report_data[0]));
        foreach ($report_data as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
    }
    exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Reports</h1>
        <p class="page-subtitle">View and export library reports</p>
    </div>
</div>

<div class="card" style="margin-bottom: 24px;">
    <form method="GET" action="" style="display: flex; gap: 16px; flex-wrap: wrap; align-items: flex-end;">
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Report Type</label>
            <select name="type" class="form-select" onchange="this.form.submit()">
                <option value="overview" <?php echo $report_type === 'overview' ? 'selected' : ''; ?>>Overview</option>
                <option value="transactions" <?php echo $report_type === 'transactions' ? 'selected' : ''; ?>>Transaction History</option>
                <option value="overdue" <?php echo $report_type === 'overdue' ? 'selected' : ''; ?>>Overdue Books</option>
                <option value="popular" <?php echo $report_type === 'popular' ? 'selected' : ''; ?>>Popular Books</option>
                <option value="fines" <?php echo $report_type === 'fines' ? 'selected' : ''; ?>>Fine Collection</option>
            </select>
        </div>
        
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">From Date</label>
            <input type="date" name="date_from" class="form-input" value="<?php echo $date_from; ?>">
        </div>
        
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">To Date</label>
            <input type="date" name="date_to" class="form-input" value="<?php echo $date_to; ?>">
        </div>
        
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-filter"></i> Generate
        </button>
        
        <a href="?type=<?php echo $report_type; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&export=1" class="btn btn-secondary">
            <i class="fas fa-download"></i> Export CSV
        </a>
    </form>
</div>

<?php if ($report_type === 'overview'): ?>
    <div class="stats-grid">
        <div class="card stat-card">
            <div class="card-header">
                <div>
                    <h3 class="stat-value"><?php echo number_format($report_data['total_issues'] ?? 0); ?></h3>
                    <p class="stat-label">Total Issues</p>
                </div>
                <div class="card-icon primary"><i class="fas fa-hand-holding"></i></div>
            </div>
        </div>
        <div class="card stat-card">
            <div class="card-header">
                <div>
                    <h3 class="stat-value"><?php echo number_format($report_data['total_returns'] ?? 0); ?></h3>
                    <p class="stat-label">Total Returns</p>
                </div>
                <div class="card-icon success"><i class="fas fa-undo"></i></div>
            </div>
        </div>
        <div class="card stat-card">
            <div class="card-header">
                <div>
                    <h3 class="stat-value"><?php echo number_format($report_data['active_users'] ?? 0); ?></h3>
                    <p class="stat-label">Active Users</p>
                </div>
                <div class="card-icon accent"><i class="fas fa-users"></i></div>
            </div>
        </div>
        <div class="card stat-card">
            <div class="card-header">
                <div>
                    <h3 class="stat-value">₹<?php echo number_format($report_data['total_fines'] ?? 0, 2); ?></h3>
                    <p class="stat-label">Total Fines</p>
                </div>
                <div class="card-icon warning"><i class="fas fa-coins"></i></div>
            </div>
        </div>
        <div class="card stat-card">
            <div class="card-header">
                <div>
                    <h3 class="stat-value">₹<?php echo number_format($report_data['collected_fines'] ?? 0, 2); ?></h3>
                    <p class="stat-label">Collected Fines</p>
                </div>
                <div class="card-icon success"><i class="fas fa-check-circle"></i></div>
            </div>
        </div>
    </div>

<?php elseif ($report_type === 'transactions'): ?>
    <div class="card">
        <h3 style="margin-bottom: 20px;">Transaction History (<?php echo formatDate($date_from); ?> - <?php echo formatDate($date_to); ?>)</h3>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Issues</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($report_data)): ?>
                        <?php foreach ($report_data as $row): ?>
                            <tr>
                                <td><?php echo formatDate($row['date']); ?></td>
                                <td><?php echo $row['count']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2" class="text-center text-muted">No transactions in this period</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($report_type === 'overdue'): ?>
    <div class="card">
        <h3 style="margin-bottom: 20px;">Overdue Books</h3>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Book</th>
                        <th>Member</th>
                        <th>Due Date</th>
                        <th>Days Late</th>
                        <th>Est. Fine</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($report_data)): ?>
                        <?php foreach ($report_data as $row): ?>
                            <?php $days_late = floor((time() - strtotime($row['due_date'])) / (60*60*24)); ?>
                            <?php $fine = calculateFine($row['due_date']); ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                                <td><?php echo formatDate($row['due_date']); ?></td>
                                <td><span class="badge badge-danger"><?php echo $days_late; ?> days</span></td>
                                <td class="text-danger">₹<?php echo number_format($fine, 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-muted">No overdue books</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($report_type === 'popular'): ?>
    <div class="card">
        <h3 style="margin-bottom: 20px;">Most Borrowed Books</h3>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Book</th>
                        <th>Author</th>
                        <th>Times Borrowed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($report_data)): ?>
                        <?php foreach ($report_data as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td><?php echo htmlspecialchars($row['author']); ?></td>
                                <td><span class="badge badge-primary"><?php echo $row['issue_count']; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" class="text-center text-muted">No data available</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($report_type === 'fines'): ?>
    <div class="card">
        <h3 style="margin-bottom: 20px;">Fine Collection Report</h3>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Book</th>
                        <th>Member</th>
                        <th>Amount</th>
                        <th>Paid</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($report_data)): ?>
                        <?php foreach ($report_data as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                                <td>₹<?php echo number_format($row['amount'], 2); ?></td>
                                <td>₹<?php echo number_format($row['paid_amount'], 2); ?></td>
                                <td>
                                    <span class="badge <?php echo $row['is_paid'] ? 'badge-success' : 'badge-warning'; ?>">
                                        <?php echo $row['is_paid'] ? 'Paid' : 'Pending'; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-muted">No fines in this period</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
