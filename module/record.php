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
<title>Records - SpeedNet Payroll</title>
<link rel="stylesheet" href="../css/record.css">
</head>
<body>
    <header class="main-header">
    <div class="logo"><img src="../image1_edited.png" alt=""></div>
    <nav>
        <a href="../index.php">Home</a>
        <a href="#features">Features</a>
        <a href="../login.php" class="btn-login">Back</a>
    </nav>
</header>

<div class="container">
    <h1>Company Records</h1>

    <div class="grid">
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
</div>

</body>
</html>
