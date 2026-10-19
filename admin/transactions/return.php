<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';

if (!in_array($_SESSION['role'], ['admin', 'librarian'])) {
    header('Location: ' . SITE_URL . '/pages/dashboard.php');
    exit;
}

$page_title = 'Return Book';
$error = '';

$tx_id = intval($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tx_id = intval($_POST['transaction_id'] ?? 0);
    
    if (!$tx_id) {
        $error = 'Please select a transaction.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM transactions WHERE id = ? AND status = 'issued'");
        $stmt->bind_param("i", $tx_id);
        $stmt->execute();
        $tx = $stmt->get_result()->fetch_assoc();
        
        if (!$tx) {
            $error = 'Transaction not found or already returned.';
        } else {
            $return_date = date('Y-m-d');
            $fine = calculateFine($tx['due_date'], $return_date);
            
            $stmt = $conn->prepare("UPDATE transactions SET return_date = ?, status = 'returned' WHERE id = ?");
            $stmt->bind_param("si", $return_date, $tx_id);
            
            if ($stmt->execute()) {
                $conn->query("UPDATE books SET available_copies = available_copies + 1 WHERE id = {$tx['book_id']}");
                
                if ($fine > 0) {
                    $payment_received = floatval($_POST['payment_received'] ?? 0);
                    $stmt = $conn->prepare("INSERT INTO fines (transaction_id, user_id, amount, paid_amount, is_paid) VALUES (?, ?, ?, ?, ?)");
                    $is_paid = $payment_received >= $fine ? 1 : 0;
                    $stmt->bind_param("iiddi", $tx_id, $tx['user_id'], $fine, $payment_received, $is_paid);
                    $stmt->execute();
                    
                    if ($is_paid) {
                        $stmt = $conn->prepare("UPDATE fines SET payment_date = ? WHERE transaction_id = ?");
                        $stmt->bind_param("si", $return_date, $tx_id);
                        $stmt->execute();
                    }
                }
                
                logActivity($_SESSION['user_id'], 'return_book', "Returned book TX $tx_id. Fine: ₹" . number_format($fine, 2));
                
                flashMessage('Book returned successfully! Fine: ₹' . number_format($fine, 2), $fine > 0 ? 'warning' : 'success');
                header('Location: index.php');
                exit;
            } else {
                $error = 'Failed to process return. Please try again.';
            }
        }
    }
}

$issued_txs = $conn->query("
    SELECT t.*, b.title, u.name as user_name
    FROM transactions t
    JOIN books b ON t.book_id = b.id
    JOIN users u ON t.user_id = u.id
    WHERE t.status = 'issued'
    ORDER BY t.due_date ASC
");
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
                        <h1 class="page-title">Return Book</h1>
                        <p class="page-subtitle">Process a book return</p>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="grid-2">
                    <div class="card">
                        <h3 style="margin-bottom: 20px;">Return Book</h3>
                        <form method="POST" action="" data-validate>
                            <div class="form-group">
                                <label class="form-label">Select Transaction *</label>
                                <select name="transaction_id" id="transactionSelect" class="form-select" required>
                                    <option value="">-- Select a Transaction --</option>
                                    <?php while ($tx = $issued_txs->fetch_assoc()): ?>
                                        <?php $is_overdue = strtotime($tx['due_date']) < time(); ?>
                                        <option value="<?php echo $tx['id']; ?>" 
                                                data-book="<?php echo htmlspecialchars($tx['title']); ?>"
                                                data-user="<?php echo htmlspecialchars($tx['user_name']); ?>"
                                                data-due="<?php echo $tx['due_date']; ?>"
                                                data-overdue="<?php echo $is_overdue ? '1' : '0'; ?>">
                                            <?php echo htmlspecialchars($tx['title']); ?> - <?php echo htmlspecialchars($tx['user_name']); ?>
                                            (Due: <?php echo formatDate($tx['due_date']); ?>)
                                            <?php echo $is_overdue ? ' [OVERDUE]' : ''; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div id="returnDetails" class="return-details" style="display: none;">
                                <div class="form-group">
                                    <label class="form-label">Book</label>
                                    <p id="detailBook" class="text-primary"></p>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Member</label>
                                    <p id="detailUser"></p>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Due Date</label>
                                    <p id="detailDue"></p>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Fine Amount</label>
                                    <p id="detailFine" class="text-danger" style="font-size: 20px; font-weight: 600;"></p>
                                    <small class="text-muted">₹1.00 per day after 7 days grace period</small>
                                </div>
                            </div>
                            
                            <div class="form-group" id="paymentGroup" style="display: none;">
                                <label class="form-label">Payment Received</label>
                                <input type="number" name="payment_received" id="paymentReceived" class="form-input" step="0.01" min="0" placeholder="0.00">
                            </div>
                            
                            <div style="display: flex; gap: 12px; margin-top: 24px;">
                                <button type="submit" class="btn btn-success btn-lg" id="submitBtn" disabled>
                                    <i class="fas fa-check"></i> Process Return
                                </button>
                                <a href="index.php" class="btn btn-secondary btn-lg">
                                    <i class="fas fa-arrow-left"></i> Back
                                </a>
                            </div>
                        </form>
                    </div>
                    
                    <div class="card">
                        <h3 style="margin-bottom: 20px;">Return Summary</h3>
                        <div id="summarySection">
                            <div class="empty-state">
                                <div class="empty-state-icon"><i class="fas fa-undo"></i></div>
                                <p class="text-muted">Select a transaction to see return details</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <style>
                .return-details .form-group {
                    margin-bottom: 12px;
                    padding: 12px;
                    background: var(--bg-glass);
                    border-radius: var(--radius-sm);
                }
                .return-details .form-label {
                    margin-bottom: 4px;
                    font-size: 12px;
                }
                .return-details p {
                    margin: 0;
                }
                </style>
                
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const select = document.getElementById('transactionSelect');
                    const details = document.getElementById('returnDetails');
                    const paymentGroup = document.getElementById('paymentGroup');
                    const submitBtn = document.getElementById('submitBtn');
                    
                    select.addEventListener('change', function() {
                        const option = this.options[this.selectedIndex];
                        
                        if (this.value) {
                            details.style.display = 'block';
                            paymentGroup.style.display = 'block';
                            submitBtn.disabled = false;
                            
                            document.getElementById('detailBook').textContent = option.dataset.book;
                            document.getElementById('detailUser').textContent = option.dataset.user;
                            document.getElementById('detailDue').textContent = option.dataset.due;
                            
                            const dueDate = new Date(option.dataset.due);
                            const today = new Date();
                            const diffDays = Math.floor((today - dueDate) / (1000 * 60 * 60 * 24));
                            
                            let fine = 0;
                            if (diffDays > 7) {
                                fine = (diffDays - 7) * 1.00;
                            }
                            
                            const fineEl = document.getElementById('detailFine');
                            if (fine > 0) {
                                fineEl.textContent = '$' + fine.toFixed(2);
                                fineEl.parentElement.querySelector('small').textContent = diffDays + ' days overdue (7 days grace period applied)';
                            } else {
                                fineEl.textContent = 'No Fine';
                                fineEl.classList.remove('text-danger');
                                fineEl.classList.add('text-success');
                            }
                            
                            document.getElementById('paymentReceived').max = fine;
                            document.getElementById('paymentReceived').value = fine > 0 ? fine.toFixed(2) : '0.00';
                        } else {
                            details.style.display = 'none';
                            paymentGroup.style.display = 'none';
                            submitBtn.disabled = true;
                        }
                    });
                });
                </script>
            </main>
        </div>
    </div>
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
