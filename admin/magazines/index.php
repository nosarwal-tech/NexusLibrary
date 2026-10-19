<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';

if (!in_array($_SESSION['role'], ['admin', 'librarian'])) {
    header('Location: ' . SITE_URL . '/pages/dashboard.php');
    exit;
}

$page_title = 'Magazine Management';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_magazine'])) {
    $title = sanitize($_POST['title'] ?? '');
    $publisher = sanitize($_POST['publisher'] ?? '');
    $shelf_location = sanitize($_POST['shelf_location'] ?? '');
    $total_copies = intval($_POST['total_copies'] ?? 1);
    $issuable = sanitize($_POST['issuable'] ?? 'yes');
    
    if ($title && $publisher) {
        $stmt = $conn->prepare("INSERT INTO books (title, publisher, shelf_location, total_copies, available_copies, issuable, type) VALUES (?, ?, ?, ?, ?, ?, 'magazine')");
        $stmt->bind_param("sssiis", $title, $publisher, $shelf_location, $total_copies, $total_copies, $issuable);
        if ($stmt->execute()) {
            flashMessage('Magazine added successfully!', 'success');
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

$magazines = $conn->query("SELECT * FROM books WHERE type = 'magazine' ORDER BY title");
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
        <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
        <div class="main-content">
            <?php include __DIR__ . '/../../includes/topbar.php'; ?>
            <main class="page-content">
                <?php showFlashMessage(); ?>
                
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Magazine Management</h1>
                        <p class="page-subtitle">Manage library magazines</p>
                    </div>
                    <div class="page-actions">
                        <button onclick="openModal('addMagazineModal')" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Magazine
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Magazine</th>
                                    <th>Publisher</th>
                                    <th>Shelf Location</th>
                                    <th>Available</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($magazines->num_rows > 0): ?>
                                    <?php while ($mag = $magazines->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($mag['title']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($mag['publisher'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($mag['shelf_location'] ?? '-'); ?></td>
                                            <td><span class="badge <?php echo $mag['available_copies'] > 0 ? 'badge-success' : 'badge-danger'; ?>"><?php echo $mag['available_copies']; ?>/<?php echo $mag['total_copies']; ?></span></td>
                                            <td>
                                                <div class="actions">
                                                    <a href="<?php echo SITE_URL; ?>/admin/books/edit.php?id=<?php echo $mag['id']; ?>" class="btn btn-icon btn-secondary"><i class="fas fa-edit"></i></a>
                                                    <a href="<?php echo SITE_URL; ?>/admin/books/delete.php?id=<?php echo $mag['id']; ?>" class="btn btn-icon btn-danger delete-btn"><i class="fas fa-trash"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center text-muted" style="padding: 40px;">No magazines found</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-overlay" id="addMagazineModalOverlay"></div>
                <div class="modal" id="addMagazineModal">
                    <div class="modal-header">
                        <h3 class="modal-title">Add Magazine</h3>
                        <button class="modal-close" onclick="closeModal('addMagazineModal')"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label class="form-label">Magazine Title *</label>
                                <input type="text" name="title" class="form-input" required placeholder="e.g., National Geographic - March 2024">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Publisher *</label>
                                <input type="text" name="publisher" class="form-input" required placeholder="e.g., National Geographic Society">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Shelf Location</label>
                                <input type="text" name="shelf_location" class="form-input" placeholder="e.g., M-101">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Total Copies</label>
                                <input type="number" name="total_copies" class="form-input" min="1" value="1">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Issuable</label>
                                <select name="issuable" class="form-select">
                                    <option value="yes">Yes - Can be borrowed</option>
                                    <option value="no">No - Reference only</option>
                                </select>
                            </div>
                            <div class="modal-footer" style="padding: 0; border: none; margin-top: 20px;">
                                <button type="button" class="btn btn-secondary" onclick="closeModal('addMagazineModal')">Cancel</button>
                                <button type="submit" name="add_magazine" class="btn btn-primary"><i class="fas fa-save"></i> Add Magazine</button>
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
