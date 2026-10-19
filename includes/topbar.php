<?php
$notifications = [];
if (isLoggedIn()) {
    global $conn;
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<header class="topbar">
    <div class="topbar-left">
        <button class="mobile-menu-toggle" id="mobileMenuToggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="breadcrumb">
            <?php if (isset($breadcrumbs)): ?>
                <?php foreach ($breadcrumbs as $index => $crumb): ?>
                    <?php if ($index > 0): ?>
                        <span class="breadcrumb-separator">/</span>
                    <?php endif; ?>
                    <?php if (isset($crumb['url'])): ?>
                        <a href="<?php echo $crumb['url']; ?>" class="breadcrumb-item"><?php echo $crumb['label']; ?></a>
                    <?php else: ?>
                        <span class="breadcrumb-item current"><?php echo $crumb['label']; ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <span class="breadcrumb-item current"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="topbar-right">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search books, members..." id="globalSearch">
            <kbd>Ctrl+K</kbd>
        </div>

        <div class="topbar-actions">
            <button class="action-btn" id="themeToggle" title="Toggle theme">
                <i class="fas fa-moon"></i>
            </button>

            <div class="notification-dropdown">
                <button class="action-btn notification-btn" id="notificationBtn">
                    <i class="fas fa-bell"></i>
                    <?php if ($notifications_count > 0): ?>
                        <span class="notification-badge"><?php echo $notifications_count; ?></span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu notification-menu" id="notificationMenu">
                    <div class="dropdown-header">
                        <span>Notifications</span>
                        <a href="#" class="mark-all-read">Mark all read</a>
                    </div>
                    <div class="notification-list">
                        <?php if (empty($notifications)): ?>
                            <div class="empty-notification">
                                <i class="fas fa-bell-slash"></i>
                                <span>No notifications</span>
                            </div>
                        <?php else: ?>
                            <?php foreach ($notifications as $notif): ?>
                                <div class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>" data-id="<?php echo $notif['id']; ?>">
                                    <div class="notification-icon notification-<?php echo $notif['type']; ?>">
                                        <?php
                                        $icon_map = [
                                            'info' => 'fa-info-circle',
                                            'warning' => 'fa-exclamation-triangle',
                                            'success' => 'fa-check-circle',
                                            'danger' => 'fa-exclamation-circle'
                                        ];
                                        ?>
                                        <i class="fas <?php echo $icon_map[$notif['type']] ?? 'fa-info-circle'; ?>"></i>
                                    </div>
                                    <div class="notification-content">
                                        <p class="notification-title"><?php echo htmlspecialchars($notif['title']); ?></p>
                                        <p class="notification-message"><?php echo htmlspecialchars($notif['message']); ?></p>
                                        <span class="notification-time"><?php echo formatDateTime($notif['created_at']); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const notificationBtn = document.getElementById('notificationBtn');
    const notificationMenu = document.getElementById('notificationMenu');
    
    if (notificationBtn && notificationMenu) {
        notificationBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationMenu.classList.toggle('show');
        });
        
        document.addEventListener('click', function(e) {
            if (!notificationMenu.contains(e.target)) {
                notificationMenu.classList.remove('show');
            }
        });
    }

    document.querySelectorAll('.mark-all-read').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            fetch('<?php echo SITE_URL; ?>/api/notifications.php?action=mark_all_read')
                .then(() => {
                    document.querySelectorAll('.notification-item.unread').forEach(item => {
                        item.classList.remove('unread');
                    });
                    document.querySelector('.notification-badge')?.remove();
                });
        });
    });

    document.querySelectorAll('.notification-item.unread').forEach(item => {
        item.addEventListener('click', function() {
            const id = this.dataset.id;
            fetch('<?php echo SITE_URL; ?>/api/notifications.php?action=mark_read&id=' + id);
            this.classList.remove('unread');
        });
    });
});
</script>
