<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

if (!in_array($_SESSION['role'], ['admin', 'librarian'])) {
    header('Location: ' . SITE_URL . '/pages/dashboard.php');
    exit;
}

$page_title = 'Vendors';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_vendor'])) {
        $name = sanitize($_POST['name'] ?? '');
        $contact = sanitize($_POST['contact_person'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        
        if ($name) {
            $stmt = $conn->prepare("INSERT INTO vendors (name, contact_person, email, phone, address) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $contact, $email, $phone, $address);
            $stmt->execute();
            flashMessage('Vendor added successfully!', 'success');
            header('Location: vendors.php');
            exit;
        }
    }
    
    if (isset($_POST['delete_vendor'])) {
        $id = intval($_POST['vendor_id'] ?? 0);
        $conn->query("DELETE FROM vendors WHERE id = $id");
        flashMessage('Vendor deleted!', 'success');
        header('Location: vendors.php');
        exit;
    }
}

$vendors = $conn->query("SELECT * FROM vendors ORDER BY name");
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
                        <h1 class="page-title">Vendor Management</h1>
                        <p class="page-subtitle">Manage book vendors and suppliers</p>
                    </div>
                    <div class="page-actions">
                        <button onclick="openModal('addVendorModal')" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Vendor
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Vendor</th>
                                    <th>Contact Person</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($vendors->num_rows > 0): ?>
                                    <?php while ($vendor = $vendors->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($vendor['name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($vendor['contact_person'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($vendor['email'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($vendor['phone'] ?? '-'); ?></td>
                                            <td>
                                                <span class="badge <?php echo $vendor['status'] === 'active' ? 'badge-success' : 'badge-secondary'; ?>">
                                                    <?php echo ucfirst($vendor['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="vendor_id" value="<?php echo $vendor['id']; ?>">
                                                    <button type="submit" name="delete_vendor" class="btn btn-icon btn-danger delete-btn" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center text-muted" style="padding: 40px;">No vendors found</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-overlay" id="addVendorModalOverlay"></div>
                <div class="modal" id="addVendorModal">
                    <div class="modal-header">
                        <h3 class="modal-title">Add Vendor</h3>
                        <button class="modal-close" onclick="closeModal('addVendorModal')"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label class="form-label">Vendor Name *</label>
                                <input type="text" name="name" class="form-input" required placeholder="e.g., Penguin Random House">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Contact Person</label>
                                <input type="text" name="contact_person" class="form-input" placeholder="e.g., John Smith">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-input" placeholder="e.g., orders@publisher.com">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-input" placeholder="e.g., +1 234 567 8900">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-textarea" placeholder="Full address"></textarea>
                            </div>
                            <div class="modal-footer" style="padding: 0; border: none; margin-top: 20px;">
                                <button type="button" class="btn btn-secondary" onclick="closeModal('addVendorModal')">Cancel</button>
                                <button type="submit" name="add_vendor" class="btn btn-primary"><i class="fas fa-save"></i> Add Vendor</button>
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
