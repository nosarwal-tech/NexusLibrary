<?php
/**
 * Database Configuration
 * Library Management System
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'library_db');

define('SITE_NAME', 'Nexus Library');
define('SITE_URL', 'http://localhost/library');

define('FINE_PER_DAY', 1.00);
define('GRACE_PERIOD_DAYS', 7);
define('DEFAULT_BORROW_DAYS_MEMBER', 14);
define('DEFAULT_BORROW_DAYS_LIBRARIAN', 30);
define('DEFAULT_BORROW_LIMIT_MEMBER', 5);
define('DEFAULT_BORROW_LIMIT_LIBRARIAN', 10);

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

$settings_cache = [];
$settings_result = $conn->query("SELECT * FROM settings");
while ($row = $settings_result->fetch_assoc()) {
    $settings_cache[$row['setting_key']] = $row['setting_value'];
}

function getSetting($key, $default = null) {
    global $settings_cache;
    return $settings_cache[$key] ?? $default;
}

function getLibraryName() {
    return getSetting('library_name', 'Nexus Library');
}

function sanitize($data) {
    global $conn;
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($conn->real_escape_string($data))));
}

function generateToken() {
    return bin2hex(random_bytes(32));
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/auth/login.php');
        exit;
    }
}

function requireRole($roles) {
    requireLogin();
    if (!in_array($_SESSION['role'], $roles)) {
        header('Location: ' . SITE_URL . '/pages/dashboard.php');
        exit;
    }
}

function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('M d, Y h:i A', strtotime($datetime));
}

function calculateFine($due_date, $return_date = null) {
    $return = $return_date ?? date('Y-m-d');
    $due = strtotime($due_date);
    $return_ts = strtotime($return);
    
    if ($return_ts <= $due) {
        return 0;
    }
    
    $days_late = floor(($return_ts - $due) / (60 * 60 * 24));
    $grace_period = GRACE_PERIOD_DAYS;
    
    if ($days_late <= $grace_period) {
        return 0;
    }
    
    $chargeable_days = $days_late - $grace_period;
    return $chargeable_days * FINE_PER_DAY;
}

function logActivity($user_id, $action, $description = '') {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $action, $description, $ip, $user_agent);
    $stmt->execute();
}

function getNotificationCount($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'] ?? 0;
}

function sendNotification($user_id, $title, $message, $type = 'info') {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $title, $message, $type);
    return $stmt->execute();
}

function flashMessage($message, $type = 'success') {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function showFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        echo '<div class="alert alert-' . $flash['type'] . '">' . $flash['message'] . '</div>';
        unset($_SESSION['flash']);
    }
}

function getCurrentUser() {
    global $conn;
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function currency($amount) {
    return '₹' . number_format($amount, 2);
}
?>
