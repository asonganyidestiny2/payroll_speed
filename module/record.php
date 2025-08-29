<?php
require_once __DIR__ . '/../config/auth_middleware.php';
requireRole(['admin', 'hrm', 'secretary']); // Now includes Secretary

require_once __DIR__ . '/../config/db.php';

// Fetch counts from database
try {
    // Count employees
    $stmt = $pdo->query("SELECT COUNT(*) FROM employees");
    $employeesCount = $stmt->fetchColumn();

    // Count attendance records
    $stmt = $pdo->query("SELECT COUNT(*) FROM attendance");
    $attendanceCount = $stmt->fetchColumn();

    // Count payroll records
    $stmt = $pdo->query("SELECT COUNT(*) FROM payroll");
    $payrollCount = $stmt->fetchColumn();
} catch (PDOException $e) {
    $employeesCount = $attendanceCount = $payrollCount = 0;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Records - SpeedNet Payroll</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/modules/record.css">
    <style>
        /* Dropdown specific styles */
        .dropdown {
            position: relative;
            display: inline-block;
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1000;
            border-radius: 4px;
            padding: 0.5rem 0;
        }
        
        .dropdown.open .dropdown-content {
            display: block;
        }
        
        .dropdown-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 4px;
        }
        
        .dropdown-btn:hover {
            background-color: #f1f1f1;
        }
        
        .hamburger-icon {
            width: 24px;
            height: 24px;
            fill: currentColor;
        }
        
        /* Card styles */
        .card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
            min-width: 250px;
        }
        
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
        }
        
        .label {
            font-size: 1.125rem;
            color: #6b7280;
            margin-bottom: 0.5rem;
        }
        
        .value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #3b82f6;
            margin-bottom: 1.5rem;
        }
        
        .link {
            display: inline-block;
            background-color: #3b82f6;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 6px;
            text-decoration: none;
            transition: background-color 0.2s;
        }
        
        .link:hover {
            background-color: #2563eb;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <header class="main-header flex justify-between items-center p-4 bg-white shadow-md">
        <div class="logo"><img src="../image1_edited.png" alt="Company Logo" class="w-32"></div>
        <div class="dropdown">
            <button id="dropdown-btn" class="dropdown-btn">
                <svg class="hamburger-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z" />
                </svg>
            </button>
                <?php include '../module/components/nav.php'; ?>
        </div>
    </header>

    <main class="container mx-auto p-8 pt-12">
        <h1 class="text-3xl font-bold text-center mb-12 text-gray-800">Company Records</h1>

        <div class="flex flex-wrap justify-center gap-8 md:grid md:grid-cols-2 lg:grid-cols-3">
            <div class="card">
                <div class="label">Total Employees</div>
                <div class="value"><?php echo $employeesCount; ?></div>
                <a href="../module/view_employee.php" class="link">View Employees</a>
            </div>
            <div class="card">
                <div class="label">Attendance Records</div>
                <div class="value"><?php echo $attendanceCount; ?></div>
                <a href="../module/attendance_history.php" class="link">View Attendance</a>
            </div>
            <div class="card">
                <div class="label">Payroll Records</div>
                <div class="value"><?php echo $payrollCount; ?></div>
                <a href="../module/payroll_history.php" class="link">View Payroll</a>
            </div>
        </div>
    </main>
</body>
<script>
    // Dropdown Navigation Toggle
    document.addEventListener("DOMContentLoaded", () => {
        const dropdownBtn = document.getElementById('dropdown-btn');
        const dropdownContainer = document.querySelector('.dropdown');

        dropdownBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdownContainer.classList.toggle('open');
        });

        // Close the dropdown if the user clicks outside of it
        document.addEventListener('click', (event) => {
            if (!dropdownContainer.contains(event.target)) {
                dropdownContainer.classList.remove('open');
            }
        });
    });
</script>

</html>