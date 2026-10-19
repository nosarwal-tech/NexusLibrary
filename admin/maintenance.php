<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

if (!in_array($_SESSION['role'], ['admin', 'librarian'])) {
    header('Location: ' . SITE_URL . '/pages/dashboard.php');
    exit;
}

$page_title = 'Maintenance';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_log'])) {
        $book_id = intval($_POST['book_id'] ?? 0);
        $issue_type = sanitize($_POST['issue_type'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $reported_date = sanitize($_POST['reported_date'] ?? date('Y-m-d'));
        $cost = floatval($_POST['cost'] ?? 0);
        
        if ($book_id && $issue_type) {
            $stmt = $conn->prepare("INSERT INTO maintenance_logs (book_id, issue_type, description, reported_by, reported_date, cost) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssd", $book_id, $issue_type, $description, $_SESSION['user_id'], $reported_date, $cost);
            $stmt->execute();
            flashMessage('Maintenance log added!', 'success');
            header('Location: maintenance.php');
            exit;
        }
    }
    
    if (isset($_POST['update_status'])) {
        $log_id = intval($_POST['log_id'] ?? 0);
        $status = sanitize($_POST['status'] ?? '');
        $resolved_date = $status === 'resolved' ? date('Y-m-d') : NULL;
        
        $stmt = $conn->prepare("UPDATE maintenance_logs SET status = ?, resolved_date = ? WHERE id = ?");
        $stmt->bind_param("ssi", $status, $resolved_date, $log_id);
        $stmt->execute();
        flashMessage('Status updated!', 'success');
        header('Location: maintenance.php');
        exit;
    }
}

$logs = $conn->query("
    SELECT m.*, b.title, u.name as reported_by_name
    FROM maintenance_logs m
    JOIN books b ON m.book_id = b.id
    LEFT JOIN users u ON m.reported_by = u.id
    ORDER BY m.reported_date DESC
");

$books = $conn->query("SELECT id, title FROM books WHERE type = 'book' ORDER BY title");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | <?php echo SITE_NAME; ?></title>
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
                        <h1 class="page-title">Book Maintenance</h1>
                        <p class="page-subtitle">Track damaged, lost, and maintenance records</p>
                    </div>
                    <div class="page-actions">
                        <button onclick="openModal('addLogModal')" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Log
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Book</th>
                                    <th>Issue Type</th>
                                    <th>Description</th>
                                    <th>Reported</th>
                                    <th>Cost</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($logs->num_rows > 0): ?>
                                    <?php while ($log = $logs->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($log['title']); ?></strong></td>
                                            <td>
                                                <?php
                                                $type_colors = [
                                                    'damaged' => 'badge-warning',
                                                    'lost' => 'badge-danger',
                                                    'missing_pages' => 'badge-secondary',
                                                    'binding_loose' => 'badge-info',
                                                    'cleaning' => 'badge-success',
                                                    'other' => 'badge-primary'
                                                ];
                                                ?>
                                                <span class="badge <?php echo $type_colors[$log['issue_type']] ?? ''; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $log['issue_type'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($log['description'] ?? '-'); ?></td>
                                            <td>
                                                <?php echo formatDate($log['reported_date']); ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($log['reported_by_name'] ?? 'System'); ?></small>
                                            </td>
                                            <td><?php echo $log['cost'] > 0 ? '₹' . number_format($log['cost'], 2) : '-'; ?></td>
                                            <td>
                                                <?php
                                                $status_colors = ['open' => 'badge-danger', 'in_progress' => 'badge-warning', 'resolved' => 'badge-success'];
                                                ?>
                                                <span class="badge <?php echo $status_colors[$log['status']]; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $log['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($log['status'] !== 'resolved'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="log_id" value="<?php echo $log['id']; ?>">
                                                        <button type="submit" name="update_status" value="resolved" class="btn btn-icon btn-success" title="Mark Resolved">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="text-center text-muted" style="padding: 40px;">No maintenance records</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-overlay" id="addLogModalOverlay"></div>
                <div class="modal" id="addLogModal">
                    <div class="modal-header">
                        <h3 class="modal-title">Add Maintenance Log</h3>
                        <button class="modal-close" onclick="closeModal('addLogModal')"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label class="form-label">Book *</label>
                                <select name="book_id" class="form-select" required>
                                    <option value="">Select Book</option>
                                    <?php $books->data_seek(0); while ($book = $books->fetch_assoc()): ?>
                                        <option value="<?php echo $book['id']; ?>"><?php echo htmlspecialchars($book['title']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Issue Type *</label>
                                <select name="issue_type" class="form-select" required>
                                    <option value="damaged">Damaged</option>
                                    <option value="lost">Lost</option>
                                    <option value="missing_pages">Missing Pages</option>
                                    <option value="binding_loose">Binding Loose</option>
                                    <option value="cleaning">Cleaning</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-textarea" placeholder="Describe the issue..."></textarea>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Reported Date</label>
                                    <input type="date" name="reported_date" class="form-input" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Cost ($)</label>
                                    <input type="number" name="cost" class="form-input" min="0" step="0.01" placeholder="0.00">
                                </div>
                            </div>
                            <div class="modal-footer" style="padding: 0; border: none; margin-top: 20px;">
                                <button type="button" class="btn btn-secondary" onclick="closeModal('addLogModal')">Cancel</button>
                                <button type="submit" name="add_log" class="btn btn-primary"><i class="fas fa-save"></i> Add Log</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
