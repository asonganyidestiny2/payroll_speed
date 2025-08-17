<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/db.php';

$message = "";

if (isset($_POST['register'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';

    // Validate inputs
    if (empty($username) || empty($password) || empty($confirm_password) || empty($role)) {
        $message = "All fields are required.";
    } elseif (!in_array($role, ['admin', 'hrm', 'secretary'])) {
        $message = "Invalid role selected.";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {
        try {
            // Check if username exists
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->rowCount() > 0) {
                $message = "Username already exists.";
            } else {
                // Insert new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
                if ($stmt->execute([$username, $hashed_password, $role])) {
                    header("Location: login.php?registered=1");
                    exit();
                } else {
                    $message = "Error creating user account.";
                }
            }
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Register New User - SpeedNet Payroll</title>
<link rel="stylesheet" href="css/register.css" />
</head>
<body>
<div class="form-container">
  <img src="image1_edited.png" alt="SpeedNet Logo" class="logo" />
  <h1>Register New User</h1>

  <?php if ($message): ?>
    <div class="error"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <form method="POST" action="">
    <label for="username">Username</label>
    <input id="username" type="text" name="username" required autofocus />

    <label for="password">Password</label>
    <input id="password" type="password" name="password" required />

    <label for="confirm_password">Confirm Password</label>
    <input id="confirm_password" type="password" name="confirm_password" required />

    <label for="role">Role</label>
    <select id="role" name="role" required>
      <option value="">-- Select role --</option>
      <option value="admin">Admin</option>
      <option value="hrm">HR Manager</option>
      <option value="secretary">Secretary</option>
    </select>

    <button type="submit" name="register" class="btn-login">Register</button>
  </form>

  <p>Already have an account? <a href="login.php">Login here</a></p>
</div>

<script src="register.js"></script>
</body>
</html>
