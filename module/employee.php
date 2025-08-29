<?php
session_start();
require '../config/db.php';

// Only HRM or Admin can access
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['hrm', 'admin'])) {
    header("Location: ../login.php");
    exit();
}

// Initialize the message variable
$message = "";

// --- Handle form submission ---
if (isset($_POST['add_employee'])) {
    // Sanitize and trim all incoming data
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $position = trim($_POST['position']);
    $salary_type = $_POST['salary_type'];
    $base_salary = trim($_POST['base_salary']);
    $bank_account = trim($_POST['bank_account']);
    $job_type = $_POST['job_type'];
    $date_hired = $_POST['date_hired'];
    $status = $_POST['status'];

    // Validate required fields
    if (empty($full_name) || empty($email) || empty($phone) || empty($position) || empty($base_salary) || empty($date_hired)) {
        $message = "Please fill in all required fields.";
    } else {
        // Use prepared statements to prevent SQL injection
        $stmt = $pdo->prepare("INSERT INTO employees (full_name, email, phone, position, salary_type, base_salary, bank_account, job_type, date_hired, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if ($stmt->execute([$full_name, $email, $phone, $position, $salary_type, $base_salary, $bank_account, $job_type, $date_hired, $status])) {
            $message = "Employee added successfully!";
        } else {
            $message = "Error adding employee.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Employee - SpeedNet Payroll</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
     <link rel="stylesheet" href="../css/modules/employee.css">
</head>

<body class="bg-[#f3f4f6] font-inter">
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

    <main class="container mx-auto p-8 pt-12">
        <h2 class="text-3xl font-bold text-gray-900 mb-8">Add New Employee</h2>

        <?php if ($message): ?>
            <div class="<?= strpos($message, 'successfully') !== false ? 'alert-success' : 'alert-error' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" class="form-grid">
                <div class="input-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>

                <div class="input-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="input-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>

                <div class="input-group">
                    <label for="position">Position</label>
                    <input type="text" id="position" name="position" required>
                </div>

                <div class="input-group">
                    <label for="salary_type">Salary Type</label>
                    <select id="salary_type" name="salary_type" required>
                        <option value="monthly" selected>Monthly</option>
                        <option value="hourly">Hourly</option>
                    </select>
                </div>

                <div class="input-group">
                    <label for="base_salary">Base Salary</label>
                    <input type="number" step="0.01" id="base_salary" name="base_salary" required>
                </div>

                <div class="input-group">
                    <label for="bank_account">Bank Account</label>
                    <input type="text" id="bank_account" name="bank_account">
                </div>

                <div class="input-group">
                    <label for="job_type">Job Type</label>
                    <select id="job_type" name="job_type" required>
                        <option value="full-time" selected>Full-Time</option>
                        <option value="part-time">Part-Time</option>
                        <option value="intern">Intern</option>
                    </select>
                </div>

                <div class="input-group">
                    <label for="date_hired">Date Hired</label>
                    <input type="date" id="date_hired" name="date_hired" required>
                </div>

                <div class="input-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="active" selected>Active</option>
                        <option value="terminated">Terminated</option>
                    </select>
                </div>

                <div class="input-group" style="grid-column: 1 / -1;">
                    <button type="submit" name="add_employee" class="btn-primary">Add Employee</button>
                </div>
            </form>
        </div>
    </main>
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