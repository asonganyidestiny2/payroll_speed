<?php
session_start();

require_once __DIR__ . '/../config/auth_middleware.php';
requireRole(['admin', 'hrm']); // Only Admin & HRM can delete
require_once __DIR__ . '/../config/db.php';

if (!isset($_GET['id'])) {
    header("Location: ../module/view_employee.php");
    exit();
}

$id = intval($_GET['id']);
$message = "";

// Fetch employee details
$stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->execute([$id]);
$employee = $stmt->fetch();

if (!$employee) {
    die("Employee not found.");
}

// Handle form submission
if (isset($_POST['update_employee'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $position = trim($_POST['position']);
    $salary_type = $_POST['salary_type'];
    $base_salary = trim($_POST['base_salary']);
    $bank_account = trim($_POST['bank_account']);
    $date_hired = $_POST['date_hired'];
    $status = $_POST['status'];

    if (empty($full_name) || empty($email) || empty($phone) || empty($position) || empty($base_salary) || empty($date_hired)) {
        $message = "Please fill in all required fields.";
    } else {
        $stmt = $pdo->prepare("UPDATE employees SET full_name = ?, email = ?, phone = ?, position = ?, salary_type = ?, base_salary = ?, bank_account = ?, date_hired = ?, status = ? WHERE id = ?");
        if ($stmt->execute([$full_name, $email, $phone, $position, $salary_type, $base_salary, $bank_account, $date_hired, $status, $id])) {
            header("Location: ../module/view_employee.php?updated=1");
            exit();
        } else {
            $message = "Error updating employee.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Employee - SpeedNet Payroll</title>
    <link rel="stylesheet" href="../css/edit.css">
</head>
<body>
    <header class="main-header">
        <div class="logo"><img src="../image1_edited.png" alt=""></div>
        <?php
        include '../module/components/nav.php';
        ?>
        <nav>
            <a href="../login.php" class="btn-login">Back</a>
        </nav>
    </header>
    <div class="container">
        <h2>Edit Employee</h2>
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="POST">
            <label>Full Name :</label>
            <input type="text" name="full_name" required value="<?= htmlspecialchars($employee['full_name']) ?>">

            <label>Email :</label>
            <input type="email" name="email" required value="<?= htmlspecialchars($employee['email']) ?>">

            <label>Phone :</label>
            <input type="tel" name="phone" required value="<?= htmlspecialchars($employee['phone']) ?>">

            <label>Position :</label>
            <input type="text" name="position" required value="<?= htmlspecialchars($employee['position']) ?>">

            <label>Salary Type :</label>
            <select name="salary_type" required>
                <option value="monthly" <?= $employee['salary_type'] == 'monthly' ? 'selected' : '' ?>>Monthly</option>
                <option value="hourly" <?= $employee['salary_type'] == 'hourly' ? 'selected' : '' ?>>Hourly</option>
            </select>

            <label>Base Salary :</label>
            <input type="number" step="0.01" name="base_salary" required value="<?= htmlspecialchars($employee['base_salary']) ?>">

            <label>Bank Account</label>
            <input type="text" name="bank_account" value="<?= htmlspecialchars($employee['bank_account']) ?>">

            <label>Date Hired :</label>
            <input type="date" name="date_hired" required value="<?= htmlspecialchars($employee['date_hired']) ?>">

            <label>Status :</label>
            <select name="status" required>
                <option value="active" <?= $employee['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                <option value="terminated" <?= $employee['status'] == 'terminated' ? 'selected' : '' ?>>Terminated</option>
            </select>

            <button type="submit" name="update_employee">Update Employee</button>
        </form>
    </div>
</body>
</html>
