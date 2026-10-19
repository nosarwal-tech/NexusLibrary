<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';

if (!in_array($_SESSION['role'], ['admin', 'librarian'])) {
    header('Location: ' . SITE_URL . '/pages/dashboard.php');
    exit;
}

$book_id = intval($_GET['id'] ?? 0);
if (!$book_id) {
    header('Location: index.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();

if (!$book) {
    header('Location: index.php');
    exit;
}

$page_title = 'Edit Book';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isbn = sanitize($_POST['isbn'] ?? '');
    $title = sanitize($_POST['title'] ?? '');
    $author = sanitize($_POST['author'] ?? '');
    $publisher = sanitize($_POST['publisher'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0) ?: null;
    $issuable = sanitize($_POST['issuable'] ?? 'yes');
    $description = sanitize($_POST['description'] ?? '');
    $shelf_location = sanitize($_POST['shelf_location'] ?? '');
    $total_copies = intval($_POST['total_copies'] ?? 1);
    $available_copies = intval($_POST['available_copies'] ?? 1);
    $price = floatval($_POST['price'] ?? 0);
    $pages = intval($_POST['pages'] ?? 0);
    $language = sanitize($_POST['language'] ?? 'English');
    $status = sanitize($_POST['status'] ?? 'available');
    
    if (empty($title) || empty($author)) {
        $error = 'Please fill in all required fields (Title and Author).';
    } else {
        $stmt = $conn->prepare("UPDATE books SET isbn=?, title=?, author=?, publisher=?, category_id=?, issuable=?, description=?, shelf_location=?, total_copies=?, available_copies=?, price=?, pages=?, language=?, status=? WHERE id=?");
        $stmt->bind_param("ssssssssidisisi", $isbn, $title, $author, $publisher, $category_id, $issuable, $description, $shelf_location, $total_copies, $available_copies, $price, $pages, $language, $status, $book_id);
        
        if ($stmt->execute()) {
            logActivity($_SESSION['user_id'], 'update_book', "Updated book: $title (ID: $book_id)");
            flashMessage('Book updated successfully!', 'success');
            header('Location: index.php');
            exit;
        } else {
            $error = 'Failed to update book. Please try again.';
        }
    }
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name");
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
                        <h1 class="page-title">Edit Book</h1>
                        <p class="page-subtitle">Update book information</p>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="card" style="max-width: 800px;">
                    <form method="POST" action="" data-validate>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">ISBN</label>
                                <input type="text" name="isbn" class="form-input" value="<?php echo htmlspecialchars($book['isbn'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Total Copies *</label>
                                <input type="number" name="total_copies" class="form-input" min="1" value="<?php echo $book['total_copies']; ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Book Title *</label>
                            <input type="text" name="title" class="form-input" required value="<?php echo htmlspecialchars($book['title']); ?>">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Author *</label>
                                <input type="text" name="author" class="form-input" required value="<?php echo htmlspecialchars($book['author']); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Publisher</label>
                                <input type="text" name="publisher" class="form-input" value="<?php echo htmlspecialchars($book['publisher'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group" style="flex: 1;">
                                <label class="form-label">Category</label>
                                <div style="display: flex; gap: 8px;">
                                    <select name="category_id" id="categorySelect" class="form-select" style="flex: 1;">
                                        <option value="">Select Category</option>
                                        <?php 
                                        $categories->data_seek(0);
                                        while ($cat = $categories->fetch_assoc()): ?>
                                            <option value="<?php echo $cat['id']; ?>" <?php echo $book['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <button type="button" class="btn btn-secondary" onclick="openModal('addCategoryInlineModal')" title="Add New Category">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Issuable</label>
                                <select name="issuable" class="form-select">
                                    <option value="yes" <?php echo ($book['issuable'] ?? 'yes') === 'yes' ? 'selected' : ''; ?>>Yes - Can be borrowed</option>
                                    <option value="no" <?php echo ($book['issuable'] ?? '') === 'no' ? 'selected' : ''; ?>>No - Reference only</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Shelf Location</label>
                                <input type="text" name="shelf_location" class="form-input" value="<?php echo htmlspecialchars($book['shelf_location'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Available Copies</label>
                                <input type="number" name="available_copies" class="form-input" min="0" value="<?php echo $book['available_copies']; ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Pages</label>
                                <input type="number" name="pages" class="form-input" min="1" value="<?php echo $book['pages'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Language</label>
                                <input type="text" name="language" class="form-input" value="<?php echo htmlspecialchars($book['language'] ?? 'English'); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Price (₹)</label>
                                <input type="number" name="price" class="form-input" min="0" step="0.01" value="<?php echo $book['price'] ?? '0'; ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="available" <?php echo $book['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                                    <option value="reserved" <?php echo $book['status'] === 'reserved' ? 'selected' : ''; ?>>Reserved</option>
                                    <option value="maintenance" <?php echo $book['status'] === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                    <option value="lost" <?php echo $book['status'] === 'lost' ? 'selected' : ''; ?>>Lost</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-textarea"><?php echo htmlspecialchars($book['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div style="display: flex; gap: 12px; margin-top: 24px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Book
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </form>
                </div>

                <div class="modal-overlay" id="addCategoryInlineModalOverlay"></div>
                <div class="modal" id="addCategoryInlineModal">
                    <div class="modal-header">
                        <h3 class="modal-title">Add New Category</h3>
                        <button class="modal-close" onclick="closeModal('addCategoryInlineModal')"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <div class="form-group">
                                <label class="form-label">Category Name *</label>
                                <input type="text" id="inline_category_name" class="form-input" required placeholder="e.g., Science Fiction">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea id="inline_category_description" class="form-textarea" placeholder="Brief description"></textarea>
                            </div>
                            <div class="modal-footer" style="padding: 0; border: none; margin-top: 20px;">
                                <button type="button" class="btn btn-secondary" onclick="closeModal('addCategoryInlineModal')">Cancel</button>
                                <button type="button" class="btn btn-primary" onclick="saveInlineCategory()"><i class="fas fa-save"></i> Save Category</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    <script>
    function saveInlineCategory() {
        const name = document.getElementById('inline_category_name').value.trim();
        const description = document.getElementById('inline_category_description').value.trim();
        
        if (!name) {
            alert('Please enter category name');
            return;
        }
        
        const formData = new FormData();
        formData.append('add_category', '1');
        formData.append('name', name);
        formData.append('description', description);
        
        fetch('<?php echo SITE_URL; ?>/admin/categories/index.php', {
            method: 'POST',
            body: formData
        })
        .then(() => {
            fetch('<?php echo SITE_URL; ?>/admin/categories/index.php?ajax=categories')
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById('categorySelect');
                select.innerHTML = '<option value="">Select Category</option>';
                data.forEach(cat => {
                    const option = document.createElement('option');
                    option.value = cat.id;
                    option.textContent = cat.name;
                    select.appendChild(option);
                });
                if (data.length > 0) {
                    select.value = data[data.length - 1].id;
                }
                closeModal('addCategoryInlineModal');
                document.getElementById('inline_category_name').value = '';
                document.getElementById('inline_category_description').value = '';
            });
        });
    }
    </script>
</body>
</html>
