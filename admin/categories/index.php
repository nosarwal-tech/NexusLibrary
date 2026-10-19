<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';

if (!in_array($_SESSION['role'], ['admin', 'librarian'])) {
    header('Location: ' . SITE_URL . '/pages/dashboard.php');
    exit;
}

$page_title = 'Category Management';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        
        if ($name) {
            $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $description);
            if ($stmt->execute()) {
                flashMessage('Category added successfully!', 'success');
            } else {
                flashMessage('Category already exists or failed to add.', 'error');
            }
            header('Location: index.php');
            exit;
        }
    }
    
    if (isset($_POST['edit_category'])) {
        $id = intval($_POST['category_id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        
        if ($id && $name) {
            $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $description, $id);
            if ($stmt->execute()) {
                flashMessage('Category updated successfully!', 'success');
            }
            header('Location: index.php');
            exit;
        }
    }
    
    if (isset($_POST['delete_category'])) {
        $id = intval($_POST['category_id'] ?? 0);
        if ($id) {
            $conn->query("DELETE FROM categories WHERE id = $id");
            flashMessage('Category deleted!', 'success');
            header('Location: index.php');
            exit;
        }
    }
}

if (isset($_GET['ajax']) && $_GET['ajax'] === 'categories') {
    header('Content-Type: application/json');
    $result = $conn->query("SELECT id, name FROM categories ORDER BY name");
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode($data);
    exit;
}

$categories = $conn->query("SELECT c.*, COUNT(b.id) as book_count 
    FROM categories c 
    LEFT JOIN books b ON c.id = b.category_id 
    GROUP BY c.id 
    ORDER BY c.name");
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
                        <h1 class="page-title">Category Management</h1>
                        <p class="page-subtitle">Manage book categories</p>
                    </div>
                    <div class="page-actions">
                        <button onclick="openModal('addCategoryModal')" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Category
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Category Name</th>
                                    <th>Description</th>
                                    <th>Books</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($categories->num_rows > 0): ?>
                                    <?php while ($cat = $categories->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($cat['description'] ?? '-'); ?></td>
                                            <td><span class="badge badge-primary"><?php echo $cat['book_count']; ?></span></td>
                                            <td>
                                                <div class="actions">
                                                    <button onclick="editCategory(<?php echo $cat['id']; ?>, '<?php echo htmlspecialchars(addslashes($cat['name'])); ?>', '<?php echo htmlspecialchars(addslashes($cat['description'] ?? '')); ?>')" class="btn btn-icon btn-secondary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                                                        <button type="submit" name="delete_category" class="btn btn-icon btn-danger delete-btn" title="Delete" onclick="return confirm('Delete this category? Books in this category will have no category.')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center text-muted" style="padding: 40px;">No categories found</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-overlay" id="addCategoryModalOverlay"></div>
                <div class="modal" id="addCategoryModal">
                    <div class="modal-header">
                        <h3 class="modal-title">Add Category</h3>
                        <button class="modal-close" onclick="closeModal('addCategoryModal')"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label class="form-label">Category Name *</label>
                                <input type="text" name="name" class="form-input" required placeholder="e.g., Science Fiction">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-textarea" placeholder="Brief description of this category"></textarea>
                            </div>
                            <div class="modal-footer" style="padding: 0; border: none; margin-top: 20px;">
                                <button type="button" class="btn btn-secondary" onclick="closeModal('addCategoryModal')">Cancel</button>
                                <button type="submit" name="add_category" class="btn btn-primary"><i class="fas fa-save"></i> Add Category</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="modal-overlay" id="editCategoryModalOverlay"></div>
                <div class="modal" id="editCategoryModal">
                    <div class="modal-header">
                        <h3 class="modal-title">Edit Category</h3>
                        <button class="modal-close" onclick="closeModal('editCategoryModal')"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="">
                            <input type="hidden" name="category_id" id="edit_category_id">
                            <div class="form-group">
                                <label class="form-label">Category Name *</label>
                                <input type="text" name="name" id="edit_category_name" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea name="description" id="edit_category_description" class="form-textarea"></textarea>
                            </div>
                            <div class="modal-footer" style="padding: 0; border: none; margin-top: 20px;">
                                <button type="button" class="btn btn-secondary" onclick="closeModal('editCategoryModal')">Cancel</button>
                                <button type="submit" name="edit_category" class="btn btn-primary"><i class="fas fa-save"></i> Update Category</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    <script>
    function editCategory(id, name, description) {
        document.getElementById('edit_category_id').value = id;
        document.getElementById('edit_category_name').value = name;
        document.getElementById('edit_category_description').value = description;
        openModal('editCategoryModal');
    }
    </script>
</body>
</html>
