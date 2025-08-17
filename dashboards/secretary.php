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

<!-- Icons -->
<link rel='stylesheet' href='https://cdn-uicons.flaticon.com/3.0.0/uicons-solid-straight/css/uicons-solid-straight.css'>

<!-- Styling -->
<style>
    body {
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        margin: 0;
        background: #f4f6f9;
        color: #333;
    }

    header {
        background: #2c3e50;
        color: white;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    header h1 {
        margin: 0;
        font-size: 1.3rem;
    }

    .logout-btn {
        background: #e74c3c;
        border: none;
        padding: 8px 15px;
        color: white;
        cursor: pointer;
        border-radius: 5px;
        transition: 0.3s;
    }
    .logout-btn:hover {
        background: #c0392b;
    }

    nav {
        display: flex;
        background: #34495e;
        padding: 10px;
        flex-wrap: wrap;
    }

    nav a {
        color: white;
        text-decoration: none;
        padding: 10px 15px;
        margin: 5px;
        border-radius: 5px;
        background: #3b4b61;
        transition: 0.3s;
    }

    nav a i {
        margin-left: 5px;
    }

    nav a:hover {
        background: #1abc9c;
    }

    main {
        padding: 20px;
    }

    main h2 {
        margin-bottom: 20px;
        color: #2c3e50;
    }

    .stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
    }

    .card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
        box-shadow: 0px 4px 8px rgba(0,0,0,0.1);
        transition: transform 0.3s, box-shadow 0.3s;
        opacity: 0;
        transform: translateY(20px);
    }

    .card h3 {
        margin-bottom: 10px;
        color: #34495e;
    }

    .card p {
        font-size: 1.2rem;
        font-weight: bold;
        color: #1abc9c;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0px 6px 12px rgba(0,0,0,0.15);
    }

    footer {
        background: #2c3e50;
        color: white;
        text-align: center;
        padding: 10px;
        margin-top: 20px;
    }
</style>
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
