<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

$page_title = 'My Profile';
$error = '';
$success = '';

$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    
    if (empty($name)) {
        $error = 'Name is required.';
    } else {
        if (!empty($current_password) && !empty($new_password)) {
            if (!password_verify($current_password, $user['password'])) {
                $error = 'Current password is incorrect.';
            } elseif (strlen($new_password) < 6) {
                $error = 'New password must be at least 6 characters.';
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET name=?, phone=?, address=?, password=? WHERE id=?");
                $stmt->bind_param("ssssi", $name, $phone, $address, $hashed_password, $_SESSION['user_id']);
            }
        } else {
            $stmt = $conn->prepare("UPDATE users SET name=?, phone=?, address=? WHERE id=?");
            $stmt->bind_param("sssi", $name, $phone, $address, $_SESSION['user_id']);
        }
        
        if (empty($error) && $stmt->execute()) {
            $_SESSION['name'] = $name;
            $success = 'Profile updated successfully!';
            $user = getCurrentUser();
            logActivity($_SESSION['user_id'], 'update_profile', 'Profile updated');
        } elseif (empty($error)) {
            $error = 'Failed to update profile.';
        }
    }
}

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions WHERE user_id = ? AND status = 'issued'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$current_borrows = $stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions WHERE user_id = ? AND status = 'returned'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$total_borrowed = $stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COALESCE(SUM(paid_amount), 0) as total FROM fines WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$total_fines_paid = $stmt->get_result()->fetch_assoc()['total'];
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
                        <h1 class="page-title">My Profile</h1>
                        <p class="page-subtitle">Manage your account information</p>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <div class="grid-2">
                    <div class="card">
                        <h3 style="margin-bottom: 20px;">Account Information</h3>
                        <form method="POST" action="" data-validate>
                            <div class="form-group">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="name" class="form-input" required value="<?php echo htmlspecialchars($user['name']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-input" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                <small class="text-muted">Email cannot be changed</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-input" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-textarea"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </form>
                    </div>
                    
                    <div class="card">
                        <h3 style="margin-bottom: 20px;">Change Password</h3>
                        <form method="POST" action="">
                            <div class="form-group">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-input" placeholder="Enter current password">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-input" placeholder="Enter new password (min 6 characters)">
                            </div>
                            
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </form>
                        
                        <hr style="margin: 30px 0; border-color: var(--border-color);">
                        
                        <h3 style="margin-bottom: 20px;">Membership Details</h3>
                        <div style="display: grid; gap: 16px;">
                            <div style="display: flex; justify-content: space-between; padding: 12px; background: var(--bg-glass); border-radius: var(--radius-sm);">
                                <span class="text-muted">Role</span>
                                <span class="badge <?php echo $user['role'] === 'admin' ? 'badge-danger' : ($user['role'] === 'librarian' ? 'badge-warning' : 'badge-success'); ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 12px; background: var(--bg-glass); border-radius: var(--radius-sm);">
                                <span class="text-muted">Membership Valid Until</span>
                                <span class="<?php echo strtotime($user['membership_validity'] ?? '') > time() ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo $user['membership_validity'] ? formatDate($user['membership_validity']) : 'N/A'; ?>
                                </span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 12px; background: var(--bg-glass); border-radius: var(--radius-sm);">
                                <span class="text-muted">Borrowing Limit</span>
                                <span><?php echo $current_borrows; ?>/<?php echo $user['borrow_limit']; ?> books</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 12px; background: var(--bg-glass); border-radius: var(--radius-sm);">
                                <span class="text-muted">Total Borrowed</span>
                                <span><?php echo $total_borrowed; ?> books</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 12px; background: var(--bg-glass); border-radius: var(--radius-sm);">
                                <span class="text-muted">Fines Paid</span>
                                <span>₹<?php echo number_format($total_fines_paid, 2); ?></span>
                            </div>
                        </div>
                        
                        <hr style="margin: 30px 0; border-color: var(--border-color);">
                        
                        <h3 style="margin-bottom: 20px;">Account Activity</h3>
                        <div style="display: flex; justify-content: space-between; padding: 12px; background: var(--bg-glass); border-radius: var(--radius-sm);">
                            <span class="text-muted">Member Since</span>
                            <span><?php echo formatDate($user['created_at']); ?></span>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
