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
$message_type = "";

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
        $message_type = "error";
    } else {
        $stmt = $pdo->prepare("UPDATE employees SET full_name = ?, email = ?, phone = ?, position = ?, salary_type = ?, base_salary = ?, bank_account = ?, date_hired = ?, status = ? WHERE id = ?");
        if ($stmt->execute([$full_name, $email, $phone, $position, $salary_type, $base_salary, $bank_account, $date_hired, $status, $id])) {
            header("Location: ../module/view_employee.php?updated=1");
            exit();
        } else {
            $message = "Error updating employee.";
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee - SpeedNet Payroll</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/modules/edit.css">
</head>
<body>
    <header class="main-header">
        <div class="logo"><img src="../image1_edited.png" alt="Company Logo" class="w-32"></div>
        <div class="dropdown">
            <button id="dropdown-btn" class="dropdown-btn">
                <svg class="hamburger-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/>
                </svg>
            </button>
                <?php include '../module/components/nav.php'; ?>
    </header>
    
    <div class="container">
        <h2 class="page-title">Edit Employee</h2>
        
        <div class="card">
            <?php if ($message): ?>
                <div class="<?php echo $message_type == 'error' ? 'alert-error' : 'alert-success'; ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-grid">
                    <div class="input-group">
                        <label for="full_name">Full Name:</label>
                        <input type="text" id="full_name" name="full_name" required value="<?= htmlspecialchars($employee['full_name']) ?>">
                    </div>
                    
                    <div class="input-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required value="<?= htmlspecialchars($employee['email']) ?>">
                    </div>
                    
                    <div class="input-group">
                        <label for="phone">Phone:</label>
                        <input type="tel" id="phone" name="phone" required value="<?= htmlspecialchars($employee['phone']) ?>">
                    </div>
                    
                    <div class="input-group">
                        <label for="position">Position:</label>
                        <input type="text" id="position" name="position" required value="<?= htmlspecialchars($employee['position']) ?>">
                    </div>
                    
                    <div class="input-group">
                        <label for="salary_type">Salary Type:</label>
                        <select id="salary_type" name="salary_type" required>
                            <option value="monthly" <?= $employee['salary_type'] == 'monthly' ? 'selected' : '' ?>>Monthly</option>
                            <option value="hourly" <?= $employee['salary_type'] == 'hourly' ? 'selected' : '' ?>>Hourly</option>
                        </select>
                    </div>
                    
                    <div class="input-group">
                        <label for="base_salary">Base Salary:</label>
                        <input type="number" id="base_salary" step="0.01" name="base_salary" required
                            value="<?= htmlspecialchars($employee['base_salary']) ?>">
                    </div>
                    
                    <div class="input-group">
                        <label for="bank_account">Bank Account:</label>
                        <input type="text" id="bank_account" name="bank_account" value="<?= htmlspecialchars($employee['bank_account']) ?>">
                    </div>
                    
                    <div class="input-group">
                        <label for="date_hired">Date Hired:</label>
                        <input type="date" id="date_hired" name="date_hired" required value="<?= htmlspecialchars($employee['date_hired']) ?>">
                    </div>
                    
                    <div class="input-group">
                        <label for="status">Status:</label>
                        <select id="status" name="status" required>
                            <option value="active" <?= $employee['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="terminated" <?= $employee['status'] == 'terminated' ? 'selected' : '' ?>>Terminated</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" name="update_employee" class="btn-primary">Update Employee</button>
            </form>
        </div>
        
        <a href="../module/view_employee.php" class="btn-back">Back to Employee List</a>
    </div>
</body>
<script>
        // Enhanced Dropdown Navigation Toggle
        document.addEventListener("DOMContentLoaded", () => {
            const dropdownBtn = document.getElementById('dropdown-btn');
            const dropdownMenu = document.getElementById('dropdown-menu');
            const dropdownContainer = document.querySelector('.dropdown');

            dropdownBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdownContainer.classList.toggle('open');
            });

            // Close the dropdown if the user clicks outside of it
            document.addEventListener('click', (event) => {
                if (!dropdownContainer.contains(event.target) && dropdownContainer.classList.contains('open')) {
                    dropdownContainer.classList.remove('open');
                }
            });

            // Close dropdown when a menu item is clicked
            const navLinks = dropdownMenu.querySelectorAll('a');
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    dropdownContainer.classList.remove('open');
                });
            });
        });
    </script>
</html>