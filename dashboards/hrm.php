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
    $stmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'active'");
    $totalEmployees = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM recruitments WHERE status = 'applied'");
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
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel='stylesheet'
        href='https://cdn-uicons.flaticon.com/3.0.0/uicons-solid-straight/css/uicons-solid-straight.css'>
    <link rel="stylesheet" href="../css/dashboard/hrm.css">
</head>

<body class="font-inter">

    <header class="main-header">
        <div class="logo"><img src="../image1_edited.png" alt="Company Logo" class="w-32"></div>
        <div class="dropdown">
            <button id="dropdown-btn" class="dropdown-btn">
                <svg class="hamburger-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z" />
                </svg>
            </button>
            <?php
            include '../module/components/nav.php';
            ?>
        </div>
        <form method="POST" action="../logout.php" class="ml-auto">
            <button type="submit" class="btn-logout">Logout</button>
        </form>
    </header>

    <main class="container mx-auto p-8 pt-12">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Welcome, <?= htmlspecialchars($username) ?></h1>
        <p class="text-lg text-gray-600 mb-8">HR Manager Dashboard</p>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6">
            <div class="card" id="employeeCard">
                <i class="fi fi-ss-users text-4xl text-blue-500 mb-3"></i>
                <h3>Total Active Employees</h3>
                <p><?= htmlspecialchars($totalEmployees) ?></p>
            </div>
            <div class="card" id="recruitmentCard">
                <i class="fi fi-ss-briefcase text-4xl text-blue-500 mb-3"></i>
                <h3>Open Recruitments</h3>
                <p><?= htmlspecialchars($openRecruitments) ?></p>
            </div>
        </div>
    </main>

    <footer class="text-center text-gray-500 text-sm mt-12 mb-4">
        <p>&copy; <?= date("Y") ?> SpeedNet. All Rights Reserved.</p>
    </footer>

    <script>
        // Animate cards on load
        document.addEventListener("DOMContentLoaded", () => {
            document.querySelectorAll(".card").forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = "1";
                    card.style.transform = "translateY(0)";
                }, 100 * index);
            });

            // Add hover effect
            document.querySelectorAll(".card").forEach(card => {
                card.addEventListener("mouseenter", () => {
                    card.style.backgroundColor = '#e5e7eb';
                });
                card.addEventListener("mouseleave", () => {
                    card.style.backgroundColor = '#ffffff';
                });
            });

            // Dropdown Navigation Toggle
            const dropdownBtn = document.getElementById('dropdown-btn');
            const dropdownMenu = document.getElementById('dropdown-menu');
            const dropdownContainer = document.querySelector('.dropdown');

            dropdownBtn.addEventListener('click', () => {
                dropdownContainer.classList.toggle('open');
            });

            // Close the dropdown if the user clicks outside of it
            window.addEventListener('click', (event) => {
                if (!dropdownContainer.contains(event.target) && dropdownContainer.classList.contains('open')) {
                    dropdownContainer.classList.remove('open');
                }
            });
        });
    </script>
</body>

</html>