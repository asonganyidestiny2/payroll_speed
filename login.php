<?php
session_start();
require 'config/db.php';

// Redirect logged-in users to their respective dashboards
if (isset($_SESSION['username'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: dashboards/admin.php");
    } elseif ($_SESSION['role'] === 'hrm') {
        header("Location: dashboards/hrm.php");
    } elseif ($_SESSION['role'] === 'secretary') {
        header("Location: dashboards/secretary.php");
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Fetch user from 'users' table
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // Redirect based on role
        if ($user['role'] === 'admin') {
            header("Location: dashboards/admin.php");
        } elseif ($user['role'] === 'hrm') {
            header("Location: dashboards/hrm.php");
        } else {
            header("Location: dashboards/secretary.php");
        }
        exit();
    } else {
        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>SpeedNet Payroll - Login</title>
    <link rel="stylesheet" href="css/login.css" />
</head>
<body>
<div class="login-container">
    <div class="login-box">
        <img src="image1_edited.png" alt="SpeedNet Logo" class="logo" />
        <h2>SpeedNet Payroll</h2>
        <p class="tagline">Secure User Login</p>

        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Enter Username" required autofocus />
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter Password" required />
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
        <p style="margin-top: 10px;">
            Don't have an account? <a href="register.php">Register here</a>
        </p>
    </div>
</div>

<script src="login.js"></script>
</body>
</html>
