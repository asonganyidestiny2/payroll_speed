<?php
session_start();
require_once '../config/db.php';
require_once __DIR__ . '/../config/auth_middleware.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'secretary') {
    header("Location: ../login.php");
    exit();
}

$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Secretary Dashboard - SpeedNet Payroll</title>
<link rel="stylesheet" href="../css/sec.css">

<!-- Icons -->
<link rel='stylesheet' href='https://cdn-uicons.flaticon.com/3.0.0/uicons-solid-straight/css/uicons-solid-straight.css'>


</head>
<body>

<header>
    <h1>Welcome, <?= htmlspecialchars($username) ?> (Secretary)</h1>
    <form method="POST" action="../logout.php" style="margin:0;">
        <button type="submit" class="logout-btn">Logout</button>
    </form>
</header>

<nav>
    <a href="../module/attendance.php">Attendance <i class="fi fi-ss-calendar"></i></a>
    <a href="../module/record.php">Employee Records <i class="fi fi-ss-clipboard"></i></a>
    <a href="../module/leaves.php">Leave Requests <i class="fi fi-ss-envelope"></i></a>
    <a href="../module/report.php">Reports <i class="fi fi-ss-newspaper"></i></a>
    <a href="../index.php">Home <i class="fi fi-ss-home"></i></a>
</nav>

<main>
    <h2>Secretary Dashboard</h2>
    <p>Manage attendance, employee records, leave requests, and generate reports.</p>

    <div class="stats">
        <div class="card">
            <h3>Attendance</h3>
            <p>Mark & View</p>
        </div>
        <div class="card">
            <h3>Employee Records</h3>
            <p>View & Update</p>
        </div>
        <div class="card">
            <h3>Leave Requests</h3>
            <p>Pending & Approved</p>
        </div>
        <div class="card">
            <h3>Reports</h3>
            <p>Generate & Export</p>
        </div>
    </div>
</main>

<footer>
    <p>&copy; <?= date("Y") ?> SpeedNet. All Rights Reserved.</p>
</footer>

<!-- Simple Script for Animations -->
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

    // Click highlight effect
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
