<?php
session_start();
require_once '../config/db.php';
require_once __DIR__ . '/../config/auth_middleware.php';

// Check if user is logged in and role is admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$username = $_SESSION['username'];

// Example queries
$totalEmployees = 0;
$totalPayrollPaid = 0.00;
$monthlyEmployees = 0;
$daylyemployees = 0;
$interns = 0;

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM employees");
    $totalEmployees = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT SUM(net_salary) FROM payroll WHERE payment_status = 'paid'");
    $totalPayrollPaid = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE contract_type='contract'");
    $contractEmployees = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE contract_type='freelance'");
    $freelancers = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE contract_type='internship'");
    $interns = $stmt->fetchColumn();

} catch (PDOException $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Dashboard - SpeedNet Payroll</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js/dist/chart.min.css">
<style>
    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background: #1e1e2d;
        color: #fff;
    }
    header {
        background: #2c2c3c;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    header h1 { margin: 0; font-size: 18px; }
    .logout-btn {
        padding: 6px 12px;
        background: #e74c3c;
        border: none;
        color: #fff;
        border-radius: 5px;
        cursor: pointer;
    }
    nav {
        background: #252533;
        padding: 10px;
        display: flex;
        gap: 15px;
        overflow-x: auto;
    }
    nav a {
        color: #bbb;
        text-decoration: none;
        font-size: 14px;
        transition: 0.2s;
    }
    nav a:hover { color: #fff; }
    .container {
        padding: 20px;
    }
    .stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
    }
    .card {
        background: #2c2c3c;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.3);
    }
    .card h3 {
        margin: 0 0 10px;
        font-size: 16px;
        color: #bbb;
    }
    .card p {
        font-size: 22px;
        font-weight: bold;
    }
    .charts {
        margin-top: 30px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
    }
    footer {
        text-align: center;
        padding: 10px;
        font-size: 12px;
        background: #2c2c3c;
        margin-top: 30px;
    }
</style>
</head>
<body>

<header>
    <h1>Welcome, <?= htmlspecialchars($username) ?> (Admin)</h1>
    <form method="POST" action="../logout.php">
        <button type="submit" class="logout-btn">Logout</button>
    </form>
</header>

<nav>
    <a href="../module/view_employee.php">Employees</a>
    <a href="../module/payroll.php">Payroll</a>
    <a href="../module/record.php">Records</a>
    <a href="../module/bonuses.php">Bonuses</a>
    <a href="../module/deduction.php">Deductions</a>
    <a href="../module/report.php">Reports</a>
    <a href="../module/payslip.php">Payslips</a>
    <a href="setting.php">Settings</a>
</nav>

<div class="container">
    <div class="stats">
        <div class="card">
            <h3>Total Employees</h3>
            <p><?= $totalEmployees ?></p>
        </div>
        <div class="card">
            <h3>monthly Employees</h3>
            <p><?= $monthlyEmployees ?></p>
        </div>
        <div class="card">
            <h3>daily employees</h3>
            <p><?= $daylyemployees ?></p>
        </div>
        <div class="card">
            <h3>Interns</h3>
            <p><?= $interns ?></p>
        </div>
        <div class="card">
            <h3>Total Payroll Paid</h3>
            <p>FCFA<?= number_format($totalPayrollPaid, 2) ?></p>
        </div>
    </div>

    <div class="charts">
        <div class="card">
            <h3>Total Employee Distribution</h3>
            <canvas id="employeeChart"></canvas>
        </div>
        <div class="card">
            <h3>Payroll Trends</h3>
            <canvas id="payrollChart"></canvas>
        </div>
    </div>
</div>

<footer>
    <p>&copy; <?= date("Y"); ?> SpeedNet. All Rights Reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx1 = document.getElementById('employeeChart').getContext('2d');
    new Chart(ctx1, {
        type: 'doughnut',
        data: {
            labels: ['Permanent', 'monthly employees', 'day employees', 'Interns'],
            datasets: [{
                data: [<?= $totalEmployees ?>, <?= $monthlyEmployees ?>, <?= $daylyemployees ?>, <?= $interns ?>],
                backgroundColor: ['#3498db', '#f39c12', '#9b59b6', '#2ecc71']
            }]
        }
    });

    const ctx2 = document.getElementById('payrollChart').getContext('2d');
    new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
            datasets: [{
                label: 'Payroll Paid (FCFA)',
                data: [50,120,210,450,870,1000,1100,3100,2900,3300,3400,3600],
                backgroundColor: '#3498db'
            }]
        }
    });
</script>

</body>
</html>
