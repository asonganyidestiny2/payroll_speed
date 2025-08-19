<?php
session_start();
require_once '../config/db.php';
require_once __DIR__ . '/../config/auth_middleware.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'hrm') {
    header("Location: ../login.php");
    exit();
}

$username = $_SESSION['username'];

// Quick stats
$totalEmployees = 0;
$openRecruitments = 0;

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM employees");
    $totalEmployees = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM recruitment WHERE status = 'open'");
    $openRecruitments = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Handle error (optional logging)
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>HRM Dashboard - SpeedNet Payroll</title>
<link rel="stylesheet" href="../css/hr.css">

<!-- Icons -->
<link rel='stylesheet' href='https://cdn-uicons.flaticon.com/3.0.0/uicons-solid-straight/css/uicons-solid-straight.css'>

<!-- Styling -->

</head>
<body>

<header>
    <h1>Welcome, <?= htmlspecialchars($username) ?> (HR Manager)</h1>
    <form method="POST" action="../logout.php" style="margin:0;">
        <button type="submit" class="logout-btn">Logout</button>
    </form>
</header>

<nav>
    <a href="../module/employee.php">Add Employees <i class="fi fi-ss-user-add"></i></a>
    <a href="../module/view_employee.php">Manage Employees <i class="fi fi-ss-users"></i></a>
    <a href="../module/attendance_history.php">Attendance History <i class="fi fi-ss-calendar"></i></a>
    <a href="../module/record.php">Employee Records <i class="fi fi-ss-clipboard"></i></a>
    <a href="../module/payroll.php">Payroll <i class="fi fi-ss-calculator"></i></a>
    <a href="../module/recruitment.php">Recruitment <i class="fi fi-ss-briefcase"></i></a>
    <a href="../module/report.php">Reports <i class="fi fi-ss-newspaper"></i></a>
    <a href="../index.php">Home <i class="fi fi-ss-home"></i></a>
</nav>

<div class="container">
    <h2>Dashboard Overview</h2>

    <div class="stats">
        <div class="card" id="employeeCard">
            <h3>Total Employees</h3>
            <p><?= $totalEmployees ?></p>
        </div>
        <div class="card" id="recruitmentCard">
            <h3>Open Recruitments</h3>
            <p><?= $openRecruitments ?></p>
        </div>
    </div>
</div>

<footer>
    <p>&copy; <?= date("Y") ?> SpeedNet. All Rights Reserved.</p>
</footer>

<!-- Simple Script for Interactivity -->
<script>
    // Animate cards on load
    document.addEventListener("DOMContentLoaded", () => {
        document.querySelectorAll(".card").forEach((card, index) => {
            setTimeout(() => {
                card.style.opacity = "1";
                card.style.transform = "translateY(0)";
            }, 200 * index);
        });
    });

    // Highlight cards on click
    document.querySelectorAll(".card").forEach(card => {
        card.addEventListener("click", () => {
            card.style.background = "#1abc9c";
            card.style.color = "white";
            setTimeout(() => {
                card.style.background = "white";
                card.style.color = "#333";
            }, 800);
        });
    });
</script>

</body>
</html>
