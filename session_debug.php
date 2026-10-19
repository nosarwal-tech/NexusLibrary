<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Debug</title>
</head>
<body>
    <h1>Session Debug</h1>
    <pre>
<?php
print_r($_SESSION);
?>
    </pre>
    <p><a href="test.html">Back to Test</a></p>
    <p><a href="auth/login.php">Go to Login</a></p>
</body>
</html>
