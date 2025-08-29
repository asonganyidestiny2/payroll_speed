<?php
session_start();
require '../config/db.php';

// Check user role - allow only secretary or hrm
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['secretary', 'hrm'])) {
    header("Location: ../login.php");
    exit();
}

// Get employees list for filter dropdown
$stmt = $pdo->prepare("SELECT id, full_name FROM employees WHERE status = 'active' ORDER BY full_name ASC");
$stmt->execute();
$employees = $stmt->fetchAll();

// Initialize filter variables
$employee_id = $_GET['employee_id'] ?? 'all';
$year_month = $_GET['year_month'] ?? date('Y-m');

$attendanceRecords = [];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $employee_id !== null) {
    $start_date = $year_month . '-01';
    $end_date = date("Y-m-t", strtotime($start_date)); // last day of month

    if ($employee_id === 'all') {
        // Get all attendance in that month
        $stmt = $pdo->prepare("
            SELECT a.date, e.full_name, a.status
            FROM attendance a
            JOIN employees e ON a.employee_id = e.id
            WHERE a.date BETWEEN ? AND ?
            ORDER BY a.date, e.full_name
        ");
        $stmt->execute([$start_date, $end_date]);
    } else {
        // Get attendance for selected employee
        $stmt = $pdo->prepare("
            SELECT a.date, e.full_name, a.status
            FROM attendance a
            JOIN employees e ON a.employee_id = e.id
            WHERE a.employee_id = ? AND a.date BETWEEN ? AND ?
            ORDER BY a.date
        ");
        $stmt->execute([$employee_id, $start_date, $end_date]);
    }
    $attendanceRecords = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance History - SpeedNet Payroll</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/modules/attendance_history.css">
</head>

<body class="font-inter">
    <header class="main-header">
        <div class="logo"><img src="../image1_edited.png" alt="Company Logo" class="w-32"></div>
        <div class="dropdown">
            <button id="dropdown-btn" class="dropdown-btn p-2 rounded hover:bg-gray-100">
                <svg class="hamburger-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/>
                </svg>
            </button>
            <div id="dropdown-menu" class="dropdown-content">
                <a href="../dashboard.php">Dashboard</a>
                <a href="attendance.php">Attendance</a>
                <a href="employees.php">Employees</a>
                <a href="payroll.php">Payroll</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </header>

    <main class="container mx-auto p-8 pt-12">
        <h2 class="text-3xl font-bold text-gray-900 mb-8">Attendance History</h2>

        <!-- Filter Form -->
        <div class="card mb-8">
            <form method="GET" action="" class="flex flex-col sm:flex-row gap-4 items-center">
                <div class="flex-grow w-full">
                    <label for="employee_id" class="block text-sm font-medium text-gray-700">Select Employee</label>
                    <select name="employee_id" id="employee_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 px-3 py-2">
                        <option value="all" <?= $employee_id === 'all' ? 'selected' : '' ?>>All Employees</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?= $emp['id'] ?>" <?= $employee_id == $emp['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($emp['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex-grow w-full">
                    <label for="year_month" class="block text-sm font-medium text-gray-700">Select Month</label>
                    <input type="month" name="year_month" id="year_month" value="<?= htmlspecialchars($year_month) ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 px-3 py-2">
                </div>
                <div class="w-full sm:w-auto mt-6">
                    <button type="submit" class="btn-primary w-full">Filter</button>
                </div>
            </form>
        </div>

        <!-- Attendance Records Table -->
        <div class="card">
            <?php if (!empty($attendanceRecords)): ?>
                <div class="overflow-x-auto">
                    <table class="striped-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Employee</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendanceRecords as $record): ?>
                                <tr>
                                    <td><?= date('F j, Y', strtotime($record['date'])) ?></td>
                                    <td><?= htmlspecialchars($record['full_name']) ?></td>
                                    <td><?= ucfirst($record['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="no-records">No attendance records found for the selected filter.</p>
            <?php endif; ?>
        </div>
    </main>
    
    <script>
        // Dropdown Navigation Toggle
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
                if (!dropdownContainer.contains(event.target)) {
                    dropdownContainer.classList.remove('open');
                }
            });
        });
    </script>
</body>

</html>