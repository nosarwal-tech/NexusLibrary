<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

if ($_SESSION['role'] !== 'admin') {
    header('Location: ' . SITE_URL . '/pages/dashboard.php');
    exit;
}

$page_title = 'Settings';

$default_settings = [
    'library_name' => 'Nexus Library',
    'library_address' => '',
    'library_phone' => '',
    'library_email' => '',
    'borrow_limit_member' => '5',
    'borrow_limit_librarian' => '10',
    'borrow_days_member' => '14',
    'borrow_days_librarian' => '30',
    'fine_per_day' => '1.00',
    'grace_period_days' => '7',
    'late_fee_cap' => '50.00'
];

foreach ($default_settings as $key => $value) {
    $check = $conn->prepare("SELECT id FROM settings WHERE setting_key = ?");
    $check->bind_param("s", $key);
    $check->execute();
    $check->store_result();
    if ($check->num_rows === 0) {
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->bind_param("ss", $key, $value);
        $stmt->execute();
    }
}

$settings_result = $conn->query("SELECT * FROM settings");
$settings = [];
while ($row = $settings_result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys_to_update = [
        'library_name', 'library_address', 'library_phone', 'library_email',
        'borrow_limit_member', 'borrow_limit_librarian',
        'borrow_days_member', 'borrow_days_librarian',
        'fine_per_day', 'grace_period_days', 'late_fee_cap'
    ];
    
    foreach ($keys_to_update as $key) {
        if (isset($_POST[$key])) {
            $value = sanitize($_POST[$key]);
            $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->bind_param("ss", $value, $key);
            $stmt->execute();
        }
    }
    
    flashMessage('Settings saved successfully!', 'success');
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title><?php echo $page_title; ?> | <?php echo htmlspecialchars(getLibraryName()); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Orbitron:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
</head>
<body>
    <div class="app-container">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <div class="main-content">
            <?php include __DIR__ . '/../includes/topbar.php'; ?>
            <main class="page-content">
                <?php showFlashMessage(); ?>
                
                <div class="page-header">
                    <div>
                        <h1 class="page-title">System Settings</h1>
                        <p class="page-subtitle">Configure library system settings</p>
                    </div>
                </div>

                <div class="card" style="max-width: 700px;">
                    <h3 style="margin-bottom: 20px;">Library Information</h3>
                    <form method="POST" action="">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Library Name</label>
                                <input type="text" name="library_name" class="form-input" value="<?php echo htmlspecialchars($settings['library_name'] ?? 'Nexus Library'); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" name="library_email" class="form-input" value="<?php echo htmlspecialchars($settings['library_email'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Address</label>
                            <input type="text" name="library_address" class="form-input" value="<?php echo htmlspecialchars($settings['library_address'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone</label>
                            <input type="text" name="library_phone" class="form-input" value="<?php echo htmlspecialchars($settings['library_phone'] ?? ''); ?>">
                        </div>
                        
                        <hr style="margin: 30px 0; border-color: var(--border-color);">
                        <h3 style="margin-bottom: 20px;">Borrowing Rules</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Member Borrow Limit</label>
                                <input type="number" name="borrow_limit_member" class="form-input" min="1" value="<?php echo $settings['borrow_limit_member'] ?? 5; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Librarian Borrow Limit</label>
                                <input type="number" name="borrow_limit_librarian" class="form-input" min="1" value="<?php echo $settings['borrow_limit_librarian'] ?? 10; ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Member Borrow Days</label>
                                <input type="number" name="borrow_days_member" class="form-input" min="1" value="<?php echo $settings['borrow_days_member'] ?? 14; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Librarian Borrow Days</label>
                                <input type="number" name="borrow_days_librarian" class="form-input" min="1" value="<?php echo $settings['borrow_days_librarian'] ?? 30; ?>">
                            </div>
                        </div>
                        
                        <hr style="margin: 30px 0; border-color: var(--border-color);">
                        <h3 style="margin-bottom: 20px;">Fine Rules</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Fine Per Day (₹)</label>
                                <input type="number" name="fine_per_day" class="form-input" min="0" step="0.01" value="<?php echo $settings['fine_per_day'] ?? 1.00; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Grace Period (Days)</label>
                                <input type="number" name="grace_period_days" class="form-input" min="0" value="<?php echo $settings['grace_period_days'] ?? 7; ?>">
                            </div>
                        </div>
                        <div class="form-group" style="max-width: 200px;">
                            <label class="form-label">Late Fee Cap (₹)</label>
                            <input type="number" name="late_fee_cap" class="form-input" min="0" step="0.01" value="<?php echo $settings['late_fee_cap'] ?? 50.00; ?>">
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="margin-top: 24px;">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                    </form>
                </div>
            </main>
        </div>
    </div>
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
