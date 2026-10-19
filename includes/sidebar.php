<?php
$user_role = $_SESSION['role'] ?? '';
$user_name = $_SESSION['name'] ?? 'User';
$user_avatar = isset($_SESSION['avatar']) ? $_SESSION['avatar'] : null;

$notifications_count = 0;
if (isLoggedIn()) {
    $notifications_count = getNotificationCount($_SESSION['user_id']);
}
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <div class="logo-icon">
                <i class="fas fa-book-open"></i>
            </div>
            <div class="logo-text">
                <span class="logo-title"><?php echo htmlspecialchars(getLibraryName()); ?></span>
                <span class="logo-subtitle">Library</span>
            </div>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <ul class="nav-list">
            <?php if ($user_role === 'admin' || $user_role === 'librarian'): ?>
                <li class="nav-item">
                    <a href="<?php echo SITE_URL; ?>/pages/dashboard.php" class="nav-link <?php echo ($current_page === 'dashboard') ? 'active' : ''; ?>">
                        <i class="fas fa-chart-pie"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($user_role === 'admin'): ?>
                <li class="nav-item">
                    <a href="<?php echo SITE_URL; ?>/admin/users/index.php" class="nav-link <?php echo ($current_dir === 'users') ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>User Management</span>
                    </a>
                </li>
            <?php endif; ?>

            <li class="nav-item">
                <a href="<?php echo SITE_URL; ?>/pages/books.php" class="nav-link <?php echo ($current_page === 'books') ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i>
                    <span>Books</span>
                </a>
            </li>

            <?php if ($user_role === 'admin' || $user_role === 'librarian'): ?>
                <li class="nav-item">
                    <a href="<?php echo SITE_URL; ?>/admin/books/index.php" class="nav-link <?php echo ($current_dir === 'books' && $current_page !== 'books') ? 'active' : ''; ?>">
                        <i class="fas fa-plus-circle"></i>
                        <span>Manage Books</span>
                    </a>
                </li>

                <li class="nav-item has-submenu">
                    <a href="#" class="nav-link submenu-toggle">
                        <i class="fas fa-newspaper"></i>
                        <span>Periodicals</span>
                        <i class="fas fa-chevron-down submenu-icon"></i>
                    </a>
                    <ul class="submenu">
                        <li><a href="<?php echo SITE_URL; ?>/admin/magazines/index.php">Magazines</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/admin/newspapers/index.php">Newspapers</a></li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a href="<?php echo SITE_URL; ?>/admin/transactions/index.php" class="nav-link <?php echo ($current_dir === 'transactions') ? 'active' : ''; ?>">
                        <i class="fas fa-exchange-alt"></i>
                        <span>Transactions</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?php echo SITE_URL; ?>/admin/transactions/fines.php" class="nav-link <?php echo ($current_page === 'fines') ? 'active' : ''; ?>">
                        <i class="fas fa-coins"></i>
                        <span>Fines</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?php echo SITE_URL; ?>/admin/reports/index.php" class="nav-link <?php echo ($current_dir === 'reports') ? 'active' : ''; ?>">
                        <i class="fas fa-file-alt"></i>
                        <span>Reports</span>
                    </a>
                </li>

                <li class="nav-item has-submenu">
                    <a href="#" class="nav-link submenu-toggle">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                        <i class="fas fa-chevron-down submenu-icon"></i>
                    </a>
                    <ul class="submenu">
                        <?php if ($user_role === 'admin'): ?>
                        <li><a href="<?php echo SITE_URL; ?>/admin/settings.php">General</a></li>
                        <?php endif; ?>
                        <li><a href="<?php echo SITE_URL; ?>/admin/categories/index.php">Categories</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/admin/vendors.php">Vendors</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/admin/maintenance.php">Maintenance</a></li>
                    </ul>
                </li>
            <?php endif; ?>

            <?php if ($user_role === 'member'): ?>
                <li class="nav-item">
                    <a href="<?php echo SITE_URL; ?>/pages/my_books.php" class="nav-link <?php echo ($current_page === 'my_books') ? 'active' : ''; ?>">
                        <i class="fas fa-bookmark"></i>
                        <span>My Books</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo SITE_URL; ?>/pages/wishlist.php" class="nav-link <?php echo ($current_page === 'wishlist') ? 'active' : ''; ?>">
                        <i class="fas fa-heart"></i>
                        <span>My Wishlist</span>
                    </a>
                </li>
            <?php endif; ?>

            <li class="nav-item">
                <a href="<?php echo SITE_URL; ?>/pages/profile.php" class="nav-link <?php echo ($current_page === 'profile') ? 'active' : ''; ?>">
                    <i class="fas fa-user-circle"></i>
                    <span>Profile</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <div class="user-card">
            <div class="user-avatar">
                <?php if ($user_avatar): ?>
                    <img src="<?php echo $user_avatar; ?>" alt="<?php echo htmlspecialchars($user_name); ?>">
                <?php else: ?>
                    <div class="avatar-placeholder">
                        <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                <span class="user-role"><?php echo ucfirst($user_role); ?></span>
            </div>
            <a href="<?php echo SITE_URL; ?>/auth/logout.php" class="logout-btn" title="Logout">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const submenuToggles = document.querySelectorAll('.submenu-toggle');
    
    submenuToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const parent = this.closest('.has-submenu');
            parent.classList.toggle('open');
        });
    });
});
</script>
