<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';

if (!in_array($_SESSION['role'], ['admin', 'librarian'])) {
    header('Location: ' . SITE_URL . '/pages/dashboard.php');
    exit;
}

$page_title = 'Add Book';
$breadcrumbs = [
    ['label' => 'Home', 'url' => SITE_URL . '/pages/dashboard.php'],
    ['label' => 'Books', 'url' => SITE_URL . '/admin/books/index.php'],
    ['label' => 'Add Book']
];

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
    $price = floatval($_POST['price'] ?? 0);
    $pages = intval($_POST['pages'] ?? 0);
    $language = sanitize($_POST['language'] ?? 'English');
    
    if (empty($title) || empty($author)) {
        $error = 'Please fill in all required fields (Title and Author).';
    } else {
        $stmt = $conn->prepare("INSERT INTO books (isbn, title, author, publisher, category_id, issuable, description, shelf_location, total_copies, available_copies, price, pages, language, type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'book')");
        $stmt->bind_param("ssssssssidiis", $isbn, $title, $author, $publisher, $category_id, $issuable, $description, $shelf_location, $total_copies, $total_copies, $price, $pages, $language);
        
        if ($stmt->execute()) {
            $book_id = $conn->insert_id;
            logActivity($_SESSION['user_id'], 'add_book', "Added new book: $title (ID: $book_id)");
            flashMessage('Book added successfully!', 'success');
            header('Location: index.php');
            exit;
        } else {
            $error = 'Failed to add book. ISBN might already exist.';
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
                        <h1 class="page-title">Add New Book</h1>
                        <p class="page-subtitle">Add a new book to the library collection</p>
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
                                <input type="text" name="isbn" class="form-input" placeholder="e.g., 978-0-123456-78-9" value="<?php echo htmlspecialchars($_POST['isbn'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Total Copies *</label>
                                <input type="number" name="total_copies" class="form-input" min="1" value="<?php echo htmlspecialchars($_POST['total_copies'] ?? '1'); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Book Title *</label>
                            <input type="text" name="title" class="form-input" placeholder="Enter book title" required value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Author *</label>
                                <input type="text" name="author" class="form-input" placeholder="Enter author name" required value="<?php echo htmlspecialchars($_POST['author'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Publisher</label>
                                <input type="text" name="publisher" class="form-input" placeholder="Enter publisher name" value="<?php echo htmlspecialchars($_POST['publisher'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group" style="flex: 1;">
                                <label class="form-label">Category</label>
                                <div style="display: flex; gap: 8px;">
                                    <select name="category_id" id="categorySelect" class="form-select" style="flex: 1;">
                                        <option value="">Select Category</option>
                                        <?php $categories->data_seek(0); while ($cat = $categories->fetch_assoc()): ?>
                                            <option value="<?php echo $cat['id']; ?>" <?php echo ($_POST['category_id'] ?? '') == $cat['id'] ? 'selected' : ''; ?>>
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
                                    <option value="yes" <?php echo ($_POST['issuable'] ?? 'yes') === 'yes' ? 'selected' : ''; ?>>Yes - Can be borrowed</option>
                                    <option value="no" <?php echo ($_POST['issuable'] ?? '') === 'no' ? 'selected' : ''; ?>>No - Reference only</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Pages</label>
                                <input type="number" name="pages" class="form-input" min="1" placeholder="Number of pages" value="<?php echo htmlspecialchars($_POST['pages'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Language</label>
                                <input type="text" name="language" class="form-input" placeholder="e.g., English" value="<?php echo htmlspecialchars($_POST['language'] ?? 'English'); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Price (₹)</label>
                                <input type="number" name="price" class="form-input" min="0" step="0.01" placeholder="0.00" value="<?php echo htmlspecialchars($_POST['price'] ?? '0'); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-textarea" placeholder="Enter book description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div style="display: flex; gap: 12px; margin-top: 24px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Book
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
                        <form method="POST" id="inlineCategoryForm">
                            <div class="form-group">
                                <label class="form-label">Category Name *</label>
                                <input type="text" name="inline_category_name" id="inline_category_name" class="form-input" required placeholder="e.g., Science Fiction">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea name="inline_category_description" id="inline_category_description" class="form-textarea" placeholder="Brief description"></textarea>
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
