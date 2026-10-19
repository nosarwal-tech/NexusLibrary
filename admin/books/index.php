<?php
require_once __DIR__ . '/../../includes/header.php';

if (!in_array($_SESSION['role'], ['admin', 'librarian'])) {
    header('Location: ' . SITE_URL . '/pages/dashboard.php');
    exit;
}

$page_title = 'Manage Books';
$breadcrumbs = [
    ['label' => 'Home', 'url' => SITE_URL . '/pages/dashboard.php'],
    ['label' => 'Books']
];

$search = sanitize($_GET['search'] ?? '');
$category = sanitize($_GET['category'] ?? '');
$type = sanitize($_GET['type'] ?? '');

$where = ["b.type = 'book'"];
$params = [];
$types = '';

if ($search) {
    $where[] = "(b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'sss';
}

if ($category) {
    $where[] = "b.category_id = ?";
    $params[] = $category;
    $types .= 'i';
}

$where_clause = implode(' AND ', $where);

// Get categories
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Count
$count_query = "SELECT COUNT(*) as total FROM books b WHERE $where_clause";
if (!empty($params)) {
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
} else {
    $total = $conn->query($count_query)->fetch_assoc()['total'];
}
$total_pages = ceil($total / $per_page);

// Get books
$query = "SELECT b.*, c.name as category_name 
          FROM books b 
          LEFT JOIN categories c ON b.category_id = c.id 
          WHERE $where_clause 
          ORDER BY b.title ASC 
          LIMIT $per_page OFFSET $offset";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $books = $stmt->get_result();
} else {
    $books = $conn->query($query);
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Book Management</h1>
        <p class="page-subtitle">Add, edit, and manage library books</p>
    </div>
    <div class="page-actions">
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Book
        </a>
    </div>
</div>

<div class="card" style="margin-bottom: 24px;">
    <form method="GET" action="" style="display: flex; gap: 16px; flex-wrap: wrap; align-items: flex-end;">
        <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
            <label class="form-label">Search</label>
            <input type="text" name="search" class="form-input" placeholder="Title, author, or ISBN..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Category</label>
            <select name="category" class="form-select">
                <option value="">All Categories</option>
                <?php while ($cat = $categories->fetch_assoc()): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-search"></i> Search
        </button>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-times"></i> Clear
        </a>
    </form>
</div>

<div class="card">
    <div class="table-container">
        <table class="data-table" id="booksTable">
            <thead>
                <tr>
                    <th>Book Details</th>
                    <th>ISBN</th>
                    <th>Category</th>
                    <th>Shelf</th>
                    <th>Availability</th>
                    <th>Issuable</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($books->num_rows > 0): ?>
                    <?php while ($book = $books->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 40px; height: 40px; background: var(--bg-glass); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-book" style="color: var(--primary);"></i>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($book['title']); ?></strong>
                                        <br><small class="text-muted">by <?php echo htmlspecialchars($book['author']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><code><?php echo htmlspecialchars($book['isbn'] ?? '-'); ?></code></td>
                            <td><?php echo htmlspecialchars($book['category_name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($book['shelf_location'] ?? '-'); ?></td>
                            <td>
                                <span class="badge <?php echo $book['available_copies'] > 0 ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo $book['available_copies']; ?>/<?php echo $book['total_copies']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if (($book['issuable'] ?? 'yes') === 'yes'): ?>
                                    <span class="badge badge-success"><i class="fas fa-check"></i> Yes</span>
                                <?php else: ?>
                                    <span class="badge badge-warning"><i class="fas fa-lock"></i> No</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="edit.php?id=<?php echo $book['id']; ?>" class="btn btn-icon btn-secondary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete.php?id=<?php echo $book['id']; ?>" class="btn btn-icon btn-danger delete-btn" title="Delete" data-confirm="Are you sure you want to delete this book?">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted" style="padding: 40px;">
                            No books found. <a href="add.php">Add a new book</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($total_pages > 1): ?>
        <div class="table-pagination">
            <div class="pagination-info">
                Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $per_page, $total); ?> of <?php echo $total; ?> books
            </div>
            <div class="pagination-controls">
                <?php
                $query_params = [];
                if ($search) $query_params['search'] = $search;
                if ($category) $query_params['category'] = $category;
                $base_url = '?' . http_build_query($query_params) . '&page=';
                ?>
                
                <?php if ($page > 1): ?>
                    <a href="<?php echo $base_url . ($page - 1); ?>" class="pagination-btn">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <a href="<?php echo $base_url . $i; ?>" class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="<?php echo $base_url . ($page + 1); ?>" class="pagination-btn">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
