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
<link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<header>
    <h1>Welcome, <?= htmlspecialchars($username) ?> (Admin)</h1>
    <form method="POST" action="../logout.php">
        <button type="submit" class="logout-btn">Logout</button>
    </form>
</header>

<?php
include '../module/components/nav.php';
?>

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
