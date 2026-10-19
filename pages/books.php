<?php
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserve_book']) && isLoggedIn()) {
    $book_id = intval($_POST['book_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    if ($book_id) {
        $check = $conn->prepare("SELECT id FROM reservations WHERE book_id = ? AND user_id = ? AND status = 'active'");
        $check->bind_param("ii", $book_id, $user_id);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows === 0) {
            $reserved_date = date('Y-m-d');
            $expiry_date = date('Y-m-d', strtotime('+7 days'));
            
            $stmt = $conn->prepare("INSERT INTO reservations (book_id, user_id, reserved_date, expiry_date, status) VALUES (?, ?, ?, ?, 'active')");
            $stmt->bind_param("iiss", $book_id, $user_id, $reserved_date, $expiry_date);
            if ($stmt->execute()) {
                flashMessage('Book added to your wishlist!', 'success');
            }
        } else {
            flashMessage('This book is already in your wishlist.', 'warning');
        }
    }
    header('Location: ' . SITE_URL . '/pages/books.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';

$page_title = 'Browse Books';
$breadcrumbs = [
    ['label' => 'Home', 'url' => SITE_URL . '/pages/dashboard.php'],
    ['label' => 'Books']
];

$search = sanitize($_GET['search'] ?? '');
$category = sanitize($_GET['category'] ?? '');
$type = sanitize($_GET['type'] ?? '');
$status_filter = sanitize($_GET['status'] ?? '');

$where = [];
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

if ($type) {
    $where[] = "b.type = ?";
    $params[] = $type;
    $types .= 's';
}

if ($status_filter) {
    $where[] = "b.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$categories = $conn->query("SELECT * FROM categories ORDER BY name");

$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

$count_query = "SELECT COUNT(*) as total FROM books b $where_clause";
if (!empty($params)) {
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
} else {
    $total = $conn->query($count_query)->fetch_assoc()['total'];
}
$total_pages = ceil($total / $per_page);

$query = "SELECT b.*, c.name as category_name 
          FROM books b 
          LEFT JOIN categories c ON b.category_id = c.id 
          $where_clause 
          ORDER BY b.created_at DESC 
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
        <h1 class="page-title">Browse Library Collection</h1>
        <p class="page-subtitle">Explore our collection of books, magazines, and newspapers</p>
    </div>
</div>

<div class="card" style="margin-bottom: 24px;">
    <form method="GET" action="" class="search-filters">
        <div class="form-row">
            <div class="form-group" style="margin-bottom: 0;">
                <div style="position: relative;">
                    <i class="fas fa-search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                    <input type="text" name="search" class="form-input" 
                           style="padding-left: 44px;" 
                           placeholder="Search by title, author, or ISBN..."
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <option value="book" <?php echo $type === 'book' ? 'selected' : ''; ?>>Books</option>
                    <option value="magazine" <?php echo $type === 'magazine' ? 'selected' : ''; ?>>Magazines</option>
                    <option value="newspaper" <?php echo $type === 'newspaper' ? 'selected' : ''; ?>>Newspapers</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Filter
            </button>
            <a href="<?php echo SITE_URL; ?>/pages/books.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Clear
            </a>
        </div>
    </form>
</div>

<div class="books-grid">
    <?php if ($books->num_rows > 0): ?>
        <?php while ($book = $books->fetch_assoc()): ?>
            <div class="book-card card">
                <div class="book-cover">
                    <i class="fas fa-book"></i>
                </div>
                <div class="book-info">
                    <span class="badge badge-<?php echo $book['type'] === 'book' ? 'primary' : ($book['type'] === 'magazine' ? 'secondary' : 'info'); ?>">
                        <?php echo ucfirst($book['type']); ?>
                    </span>
                    <?php if ($book['available_copies'] > 0): ?>
                        <span class="badge badge-success">Available</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Not Available</span>
                    <?php endif; ?>
                    
                    <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                    <p class="book-author">by <?php echo htmlspecialchars($book['author']); ?></p>
                    
                    <div class="book-meta">
                        <?php if ($book['isbn']): ?>
                            <span><i class="fas fa-barcode"></i> <?php echo htmlspecialchars($book['isbn']); ?></span>
                        <?php endif; ?>
                        <?php if ($book['shelf_location']): ?>
                            <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($book['shelf_location']); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="book-footer">
                        <span class="text-muted">
                            <?php echo $book['available_copies']; ?>/<?php echo $book['total_copies']; ?> available
                        </span>
                        <?php if (isLoggedIn() && $_SESSION['role'] === 'member'): ?>
                            <?php 
                            $check_res = $conn->prepare("SELECT id FROM reservations WHERE book_id = ? AND user_id = ? AND status = 'active'");
                            $check_res->bind_param("ii", $book['id'], $_SESSION['user_id']);
                            $check_res->execute();
                            $check_res->store_result();
                            $already_reserved = $check_res->num_rows > 0;
                            ?>
                            <?php if ($already_reserved): ?>
                                <a href="<?php echo SITE_URL; ?>/pages/wishlist.php" class="btn btn-sm btn-success">
                                    <i class="fas fa-heart"></i> In Wishlist
                                </a>
                            <?php elseif ($book['available_copies'] == 0): ?>
                                <form method="POST" style="margin-top: 8px;">
                                    <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                    <button type="submit" name="reserve_book" class="btn btn-sm btn-primary">
                                        <i class="fas fa-heart"></i> Add to Wishlist
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-state" style="grid-column: 1 / -1;">
            <div class="empty-state-icon">
                <i class="fas fa-search"></i>
            </div>
            <h3 class="empty-state-title">No books found</h3>
            <p class="empty-state-text">Try adjusting your search or filter criteria</p>
            <a href="<?php echo SITE_URL; ?>/pages/books.php" class="btn btn-secondary">
                Clear Filters
            </a>
        </div>
    <?php endif; ?>
</div>

<?php if ($total_pages > 1): ?>
    <div class="pagination" style="margin-top: 32px; display: flex; justify-content: center;">
        <?php
        $query_params = [];
        if ($search) $query_params['search'] = $search;
        if ($category) $query_params['category'] = $category;
        if ($type) $query_params['type'] = $type;
        if ($status_filter) $query_params['status'] = $status_filter;
        
        $base_url = SITE_URL . '/pages/books.php?' . http_build_query($query_params) . '&page=';
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
<?php endif; ?>

<style>
.books-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 24px;
}

.book-card {
    display: flex;
    flex-direction: column;
    overflow: hidden;
    padding: 0;
}

.book-cover {
    height: 160px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    display: flex;
    align-items: center;
    justify-content: center;
}

.book-cover i {
    font-size: 64px;
    color: rgba(255, 255, 255, 0.3);
}

.book-info {
    padding: 20px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.book-title {
    font-size: 16px;
    margin: 12px 0 4px;
    line-height: 1.3;
}

.book-author {
    color: var(--text-secondary);
    font-size: 13px;
    margin-bottom: 12px;
}

.book-meta {
    display: flex;
    flex-direction: column;
    gap: 6px;
    font-size: 12px;
    color: var(--text-muted);
    margin-bottom: 12px;
}

.book-meta span {
    display: flex;
    align-items: center;
    gap: 6px;
}

.book-footer {
    margin-top: auto;
    padding-top: 12px;
    border-top: 1px solid var(--border-color);
    font-size: 13px;
}

.search-filters .form-row {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    align-items: center;
}

.search-filters .form-group {
    min-width: 180px;
    flex: 1;
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
