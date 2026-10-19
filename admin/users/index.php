<?php
require_once __DIR__ . '/../../includes/header.php';

if ($_SESSION['role'] !== 'admin') {
    header('Location: ' . SITE_URL . '/pages/dashboard.php');
    exit;
}

$page_title = 'User Management';
$breadcrumbs = [
    ['label' => 'Home', 'url' => SITE_URL . '/pages/dashboard.php'],
    ['label' => 'Admin', 'url' => SITE_URL . '/pages/admin_dashboard.php'],
    ['label' => 'Users']
];

$search = sanitize($_GET['search'] ?? '');
$role_filter = sanitize($_GET['role'] ?? '');
$status_filter = sanitize($_GET['status'] ?? '');

$where = [];
$params = [];
$types = '';

if ($search) {
    $where[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'sss';
}

if ($role_filter) {
    $where[] = "role = ?";
    $params[] = $role_filter;
    $types .= 's';
}

if ($status_filter) {
    $where[] = "status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Count
$count_query = "SELECT COUNT(*) as total FROM users $where_clause";
if (!empty($params)) {
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
} else {
    $total = $conn->query($count_query)->fetch_assoc()['total'];
}
$total_pages = ceil($total / $per_page);

// Get users
$query = "SELECT * FROM users $where_clause ORDER BY created_at DESC LIMIT $per_page OFFSET $offset";
if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $users = $stmt->get_result();
} else {
    $users = $conn->query($query);
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title">User Management</h1>
        <p class="page-subtitle">Manage library members and staff</p>
    </div>
    <div class="page-actions">
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add User
        </a>
    </div>
</div>

<div class="card" style="margin-bottom: 24px;">
    <form method="GET" action="" style="display: flex; gap: 16px; flex-wrap: wrap; align-items: flex-end;">
        <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
            <label class="form-label">Search</label>
            <input type="text" name="search" class="form-input" placeholder="Name, email, or phone..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Role</label>
            <select name="role" class="form-select">
                <option value="">All Roles</option>
                <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                <option value="librarian" <?php echo $role_filter === 'librarian' ? 'selected' : ''; ?>>Librarian</option>
                <option value="member" <?php echo $role_filter === 'member' ? 'selected' : ''; ?>>Member</option>
            </select>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="">All Status</option>
                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                <option value="suspended" <?php echo $status_filter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-filter"></i> Filter
        </button>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-times"></i> Clear
        </a>
    </form>
</div>

<div class="card">
    <div class="table-container">
        <table class="data-table" id="usersTable">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Phone</th>
                    <th>Membership</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users->num_rows > 0): ?>
                    <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div class="avatar-placeholder" style="width: 40px; height: 40px; font-size: 16px;">
                                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php
                                $role_colors = [
                                    'admin' => 'badge-danger',
                                    'librarian' => 'badge-warning',
                                    'member' => 'badge-success'
                                ];
                                ?>
                                <span class="badge <?php echo $role_colors[$user['role']]; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                            <td>
                                <?php if ($user['membership_validity']): ?>
                                    <?php 
                                    $is_valid = strtotime($user['membership_validity']) > time();
                                    ?>
                                    <span class="<?php echo $is_valid ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo formatDate($user['membership_validity']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $status_colors = [
                                    'active' => 'badge-success',
                                    'inactive' => 'badge-secondary',
                                    'suspended' => 'badge-danger'
                                ];
                                ?>
                                <span class="badge <?php echo $status_colors[$user['status']]; ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($user['created_at']); ?></td>
                            <td>
                                <div class="actions">
                                    <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn btn-icon btn-secondary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <a href="delete.php?id=<?php echo $user['id']; ?>" class="btn btn-icon btn-danger delete-btn" title="Delete" data-confirm="Are you sure you want to delete this user?">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted" style="padding: 40px;">
                            No users found
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($total_pages > 1): ?>
        <div class="table-pagination">
            <div class="pagination-info">
                Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $per_page, $total); ?> of <?php echo $total; ?> entries
            </div>
            <div class="pagination-controls">
                <?php
                $query_params = [];
                if ($search) $query_params['search'] = $search;
                if ($role_filter) $query_params['role'] = $role_filter;
                if ($status_filter) $query_params['status'] = $status_filter;
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
