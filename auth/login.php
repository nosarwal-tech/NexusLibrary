<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . '/pages/dashboard.php');
    exit;
}

$page_title = 'Login';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND status = 'active' LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();
                
                if ($remember) {
                    $token = generateToken();
                    setcookie('remember_token', $token, time() + (86400 * 30), '/');
                }
                
                logActivity($user['id'], 'login', 'User logged in');
                
                $redirect = match($user['role']) {
                    'admin' => '/pages/admin_dashboard.php',
                    'librarian' => '/pages/admin_dashboard.php',
                    default => '/pages/books.php'
                };
                
                header('Location: ' . SITE_URL . $redirect);
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Login | <?php echo htmlspecialchars(getLibraryName()); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Orbitron:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
</head>
<body>
    <div class="login-page">
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <div class="login-logo">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <h1 class="login-title"><?php echo htmlspecialchars(getLibraryName()); ?></h1>
                    <p class="login-subtitle">Sign in to Library Management System</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['timeout'])): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-clock"></i>
                        Your session has expired. Please login again.
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['logout'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        You have been logged out successfully.
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="login-form" data-validate>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <div style="position: relative;">
                            <i class="fas fa-envelope" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                            <input type="email" name="email" class="form-input" 
                                   style="padding-left: 44px;" 
                                   placeholder="Enter your email"
                                   required 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div style="position: relative;">
                            <i class="fas fa-lock" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                            <input type="password" name="password" id="password" class="form-input" 
                                   style="padding-left: 44px;" 
                                   placeholder="Enter your password"
                                   required>
                            <button type="button" onclick="togglePassword()" 
                                    style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer;">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group" style="display: flex; justify-content: space-between; align-items: center;">
                        <label class="form-checkbox">
                            <input type="checkbox" name="remember">
                            <span>Remember me</span>
                        </label>
                        <a href="#" class="forgot-password">Forgot password?</a>
                    </div>
                    
                    <button type="submit" class="btn btn-primary login-btn">
                        <i class="fas fa-sign-in-alt"></i>
                        Sign In
                    </button>
                </form>
                
                <div class="login-divider">
                    <span>Demo Credentials</span>
                </div>
                
                <div style="background: var(--bg-glass); border-radius: var(--radius-md); padding: 16px; font-size: 13px;">
                    <div style="margin-bottom: 8px;">
                        <strong style="color: var(--primary);">Admin:</strong>
                        <span class="text-muted">admin@example.com / admin123</span>
                    </div>
                    <div style="margin-bottom: 8px;">
                        <strong style="color: var(--secondary);">Librarian:</strong>
                        <span class="text-muted">librarian@example.com / admin123</span>
                    </div>
                    <div>
                        <strong style="color: var(--accent);">Member:</strong>
                        <span class="text-muted">member1@example.com / admin123</span>
                    </div>
                </div>
                
                <div class="login-footer">
                    <p>&copy; <?php echo date('Y'); ?> Nexus Library. All rights reserved.</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        document.querySelector('.login-form').addEventListener('submit', function(e) {
            const email = this.querySelector('[name="email"]').value;
            const password = this.querySelector('[name="password"]').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
            }
        });
    </script>
</body>
</html>
