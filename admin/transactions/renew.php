<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';

if (!in_array($_SESSION['role'], ['admin', 'librarian'])) {
    header('Location: ' . SITE_URL . '/pages/dashboard.php');
    exit;
}

$page_title = 'Renew Book';
$error = '';

$tx_id = intval($_GET['id'] ?? 0);

if (!$tx_id) {
    header('Location: index.php');
    exit;
}

$stmt = $conn->prepare("
    SELECT t.*, b.title, u.name as user_name
    FROM transactions t
    JOIN books b ON t.book_id = b.id
    JOIN users u ON t.user_id = u.id
    WHERE t.id = ? AND t.status = 'issued'
");
$stmt->bind_param("i", $tx_id);
$stmt->execute();
$transaction = $stmt->get_result()->fetch_assoc();

if (!$transaction) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $extend_days = intval($_POST['extend_days'] ?? 14);
    $new_due_date = date('Y-m-d', strtotime($transaction['due_date'] . " + $extend_days days"));
    
    $stmt = $conn->prepare("UPDATE transactions SET due_date = ?, renewal_count = renewal_count + 1 WHERE id = ?");
    $stmt->bind_param("si", $new_due_date, $tx_id);
    
    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], 'renew_book', "Renewed book TX $tx_id. New due: $new_due_date");
        flashMessage('Book renewed successfully! New due date: ' . formatDate($new_due_date), 'success');
        header('Location: index.php');
        exit;
    } else {
        $error = 'Failed to renew book.';
    }
}
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
    <div class="app-container">
        <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
        <div class="main-content">
            <?php include __DIR__ . '/../../includes/topbar.php'; ?>
            <main class="page-content">
                <?php showFlashMessage(); ?>
                
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Renew Book</h1>
                        <p class="page-subtitle">Extend the due date for a borrowed book</p>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="card" style="max-width: 500px;">
                    <h3 style="margin-bottom: 20px;">Renewal Details</h3>
                    
                    <div style="background: var(--bg-glass); padding: 16px; border-radius: var(--radius-md); margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                            <span class="text-muted">Book</span>
                            <strong><?php echo htmlspecialchars($transaction['title']); ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                            <span class="text-muted">Member</span>
                            <span><?php echo htmlspecialchars($transaction['user_name']); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                            <span class="text-muted">Current Due Date</span>
                            <span class="text-warning"><?php echo formatDate($transaction['due_date']); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span class="text-muted">Renewal Count</span>
                            <span><?php echo $transaction['renewal_count']; ?> times</span>
                        </div>
                    </div>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label class="form-label">Extend Due Date By</label>
                            <select name="extend_days" class="form-select">
                                <option value="7">7 days</option>
                                <option value="14" selected>14 days</option>
                                <option value="21">21 days</option>
                                <option value="30">30 days</option>
                            </select>
                        </div>
                        
                        <div style="display: flex; gap: 12px; margin-top: 24px;">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-redo"></i> Renew Book
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
