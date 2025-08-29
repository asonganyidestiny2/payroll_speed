<?php
session_start();
require_once '../config/db.php';
require_once __DIR__ . '/../config/auth_middleware.php';

// Check if user is logged in and has the 'admin' role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get the current user's username from the session
$username = $_SESSION['username'];

// Initialize variables with default values to prevent any errors if the database queries fail
$totalEmployees = 0;
$totalPayrollPaid = 0.00;
$monthlyEmployees = 0;
$hourlyEmployees = 0;
$partTimeEmployees = 0; // Re-added variable for Part-Time Employees
$interns = 0;

try {
    // Fetch total number of active employees
    $stmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'active'");
    $totalEmployees = $stmt->fetchColumn() ?: 0;

    // Fetch total payroll paid
    $stmt = $pdo->query("SELECT SUM(net_salary) FROM payroll WHERE payment_status = 'paid'");
    $totalPayrollPaid = $stmt->fetchColumn() ?: 0.00;

    // Fetch count of employees with 'monthly' salary type
    $stmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE salary_type = 'monthly' AND status = 'active'");
    $monthlyEmployees = $stmt->fetchColumn() ?: 0;

    // Fetch count of employees with 'hourly' salary type
    $stmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE salary_type = 'hourly' AND status = 'active'");
    $hourlyEmployees = $stmt->fetchColumn() ?: 0;
    
    // Fetch count of employees with 'part-time' job type
    $stmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE job_type = 'part-time' AND status = 'active'");
    $partTimeEmployees = $stmt->fetchColumn() ?: 0;

    // Fetch count of employees with 'intern' job type
    $stmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE job_type = 'intern' AND status = 'active'");
    $interns = $stmt->fetchColumn() ?: 0;

} catch (PDOException $e) {
    // Log the error for debugging purposes and show a user-friendly message
    error_log("Database Error: " . $e->getMessage());
    $error = "There was an issue fetching data from the database. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard - SpeedNet Payroll</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/3.0.0/uicons-solid-straight/css/uicons-solid-straight.css'>
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/3.0.0/uicons-regular-straight/css/uicons-regular-straight.css'>
    <link rel="stylesheet" href="../css/dashboard/admin.css">
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
            <?php include '../module/components/nav.php'; ?>
        </div>
        <form method="POST" action="../logout.php" class="ml-auto">
            <button type="submit" class="btn-logout">Logout</button>
        </form>
    </header>

    <main class="container mx-auto p-8 pt-12">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Welcome, <?= htmlspecialchars($username) ?></h1>
        <p class="text-lg text-gray-600 mb-8">Admin Dashboard</p>

        <?php if (isset($error)): ?>
            <p class="text-red-500 text-center mb-4"><?= $error ?></p>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="card">
                <i class="fi fi-ss-users text-4xl text-blue-500 mb-3"></i>
                <h3>Total Employees</h3>
                <p><?= htmlspecialchars($totalEmployees) ?></p>
            </div>
            <div class="card">
                <i class="fi fi-ss-coins text-4xl text-blue-500 mb-3"></i>
                <h3>Total Payroll Paid</h3>
                <p><?= number_format($totalPayrollPaid, 2) ?> cfa</p>
            </div>
            <div class="card">
                <i class="fi fi-ss-user-gear text-4xl text-blue-500 mb-3"></i>
                <h3>Monthly Employees</h3>
                <p><?= htmlspecialchars($monthlyEmployees) ?></p>
            </div>
            <div class="card">
                <i class="fi fi-ss-user-clock text-4xl text-blue-500 mb-3"></i>
                <h3>Hourly Employees</h3>
                <p><?= htmlspecialchars($hourlyEmployees) ?></p>
            </div>
            <div class="card">
                <i class="fi fi-ss-user-alt text-4xl text-blue-500 mb-3"></i>
                <h3>Part-Time Employees</h3>
                <p><?= htmlspecialchars($partTimeEmployees) ?></p>
            </div>
            <div class="card">
                <i class="fi fi-ss-user-graduate text-4xl text-blue-500 mb-3"></i>
                <h3>Interns</h3>
                <p><?= htmlspecialchars($interns) ?></p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-12">
            <div class="card">
                <h3>Employee Distribution</h3>
                <div class="chart-container">
                    <canvas id="employeeChart"></canvas>
                </div>
            </div>
            <div class="card">
                <h3>Payroll Trends</h3>
                <div class="chart-container">
                    <canvas id="payrollChart"></canvas>
                </div>
            </div>
        </div>
    </main>

    <footer class="text-center text-gray-500 text-sm mt-12 mb-4">
        <p>&copy; <?= date("Y"); ?> SpeedNet. All Rights Reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            // Animate cards on load
            document.querySelectorAll(".card").forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = "1";
                    card.style.transform = "translateY(0)";
                }, 100 * index);
            });

            // Employee Distribution Chart
            const employeeData = {
                labels: ['Monthly', 'Hourly', 'Part-Time', 'Interns'],
                datasets: [{
                    // The data comes directly from the PHP variables
                    data: [<?= $monthlyEmployees ?>, <?= $hourlyEmployees ?>, <?= $partTimeEmployees ?>, <?= $interns ?>],
                    backgroundColor: ['#3498db', '#f39c12', '#9b59b6', '#2ecc71'],
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            };

            const employeeOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: { family: 'Inter' }
                        }
                    },
                    tooltip: {
                        bodyFont: { family: 'Inter' },
                        titleFont: { family: 'Inter' }
                    }
                }
            };

            const employeeChart = new Chart(document.getElementById('employeeChart'), {
                type: 'doughnut',
                data: employeeData,
                options: employeeOptions
            });

            // Payroll Trends Chart - You need to fetch this data dynamically as well
            const samplePayrollData = {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Payroll (CFA)',
                    data: [185000, 192000, 210000, 205000, 225000, 240000],
                    backgroundColor: '#3b82f6',
                    borderColor: '#2563eb',
                    borderWidth: 2,
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            };

            const payrollOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                let label = context.dataset.label || '';
                                if (label) { label += ': '; }
                                label += new Intl.NumberFormat('en-US', {
                                    style: 'currency',
                                    currency: 'XAF',
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                }).format(context.raw);
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value, index, values) {
                                return value / 1000 + 'k';
                            }
                        }
                    }
                }
            };

            const payrollChart = new Chart(document.getElementById('payrollChart'), {
                type: 'bar',
                data: samplePayrollData,
                options: payrollOptions
            });
        });

        // Dropdown Navigation Toggle
        const dropdownBtn = document.getElementById('dropdown-btn');
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
    </script>
</body>

</html>