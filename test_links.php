<?php
// Simple test to check if links work
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Links</title>
</head>
<body>
    <h1>Library System Links Test</h1>
    <p>Click these links to test navigation:</p>
    <ul>
        <li><a href="auth/login.php">Login</a></li>
        <li><a href="pages/dashboard.php">User Dashboard</a></li>
        <li><a href="pages/admin_dashboard.php">Admin Dashboard</a></li>
        <li><a href="pages/books.php">Browse Books</a></li>
        <li><a href="pages/profile.php">Profile</a></li>
        <li><a href="admin/users/index.php">User Management</a></li>
        <li><a href="admin/books/index.php">Manage Books</a></li>
        <li><a href="admin/books/add.php">Add Book</a></li>
        <li><a href="admin/transactions/index.php">Transactions</a></li>
        <li><a href="admin/reports/index.php">Reports</a></li>
    </ul>
</body>
</html>
