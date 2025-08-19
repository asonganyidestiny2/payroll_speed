<?php
session_start();
require '../config/db.php';

$message = "";

// Handle form submission
if (isset($_POST['add_employee'])) {
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
        $stmt = $pdo->prepare("INSERT INTO employees (full_name, email, phone, position, salary_type, base_salary, bank_account, date_hired, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$full_name, $email, $phone, $position, $salary_type, $base_salary, $bank_account, $date_hired, $status])) {
            $message = "✅ Employee added successfully!";
        } else {
            $message = "❌ Error adding employee.";
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Add Employee - SpeedNet Payroll</title>
    <link rel="stylesheet" href="../css/emp.css">
</head>

<body>
    <header class="main-header">
        <div class="logo"><img src="image1_edited.png" alt=""></div>
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
        <h2>Add New Employee</h2>
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="POST">
            <label>Full Name:</label>
            <input type="text" name="full_name" required>

            <label>Email:</label>
            <input type="email" name="email" required>

            <label>Phone:</label>
            <input type="tel" name="phone" required>

            <label>Position:</label>
            <input type="text" name="position" required>

            <label>Salary Type:</label>
            <select name="salary_type" required>
                <option value="monthly" selected>Monthly</option>
                <option value="hourly">Hourly</option>
            </select>

            <label>Base Salary:</label>
            <input type="number" step="0.01" name="base_salary" required>

            <label>Bank Account</label>
            <input type="text" name="bank_account">

            <label>Date Hired:</label>
            <input type="date" name="date_hired" required>

            <label>Status:</label>
            <select name="status" required>
                <option value="active" selected>Active</option>
                <option value="terminated">Terminated</option>
            </select>

            <button type="submit" name="add_employee">Add Employee</button>
        </form>
    </div>
</body>

</html>