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

// Initialize variables with default values to prevent errors
$totalEmployees = 0;
$totalPayrollPaid = 0.00;
$monthlyEmployees = 0;
$dailyEmployees = 0; // Corrected variable name
$interns = 0;

try {
    // Fetch total number of employees
    $stmt = $pdo->query("SELECT COUNT(*) FROM employees");
    $totalEmployees = $stmt->fetchColumn();

    // Fetch total payroll paid
    $stmt = $pdo->query("SELECT SUM(net_salary) FROM payroll WHERE payment_status = 'paid'");
    $totalPayrollPaid = $stmt->fetchColumn() ?: 0.00; // Use null coalescing to default to 0.00

    // Fetch count of monthly employees
    $stmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE salary_type='monthly'");
    $monthlyEmployees = $stmt->fetchColumn();

    // Fetch count of daily/hourly employees
    $stmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE salary_type='hourly'"); // Renamed variable
    $dailyEmployees = $stmt->fetchColumn();

    // Fetch count of interns
    $stmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE salary_type='internship'");
    $interns = $stmt->fetchColumn();

} catch (PDOException $e) {
    // Handle database connection or query errors gracefully
    $error = "Database Error: " . $e->getMessage();
    // In a production environment, you might log the error instead of displaying it.
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

    <?php
    include '../module/components/header.php';

    include '../module/components/nav.php';
    ?>

    <div class="container">
        <?php if (isset($error)): ?>
            <p class="error-message"><?= $error ?></p>
        <?php endif; ?>

        <div class="stats">
            <div class="card">
                <h3>Total Employees</h3>
                <p><?= $totalEmployees ?></p>
            </div>
            <div class="card">
                <h3>Monthly Employees</h3>
                <p><?= $monthlyEmployees ?></p>
            </div>
            <div class="card">
                <h3>Daily Employees</h3>
                <p><?= $dailyEmployees ?></p>
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
                <h3>Employee Distribution</h3>
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
                labels: ['Total', 'Monthly', 'Daily', 'Interns'],
                datasets: [{
                    data: [<?= $totalEmployees ?>, <?= $monthlyEmployees ?>, <?= $dailyEmployees ?>, <?= $interns ?>],
                    backgroundColor: ['#3498db', '#f39c12', '#9b59b6', '#2ecc71']
                }]
            }
        });

        const ctx2 = document.getElementById('payrollChart').getContext('2d');
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Payroll Paid (FCFA)',
                    data: [50, 120, 210, 450, 870, 1000, 1100, 3100, 2900, 3300, 3400, 3600],
                    backgroundColor: '#3498db'
                }]
            }
        });
    </script>

</body>

</html>