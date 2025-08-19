<?php
session_start();
require '../config/db.php';

// Only HRM or Admin can access
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['hrm', 'admin'])) {
    header("Location: ../login.php");
    exit();
}

// Fetch total payroll summary per month
$stmt = $pdo->prepare("
    SELECT pay_month,
           SUM(gross_salary) AS total_gross,
           SUM(deductions) AS total_deductions,
           SUM(bonuses) AS total_bonuses,
           SUM(net_salary) AS total_net
    FROM payroll
    GROUP BY pay_month
    ORDER BY STR_TO_DATE(pay_month, '%M %Y') DESC
");
$stmt->execute();
$summary = $stmt->fetchAll();

// Prepare data arrays for chart
$months = [];
$grossSalaries = [];
$deductions = [];
$bonuses = [];
$netSalaries = [];

foreach ($summary as $row) {
    $months[] = $row['pay_month'];
    $grossSalaries[] = (float) $row['total_gross'];
    $deductions[] = (float) $row['total_deductions'];
    $bonuses[] = (float) $row['total_bonuses'];
    $netSalaries[] = (float) $row['total_net'];
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Payroll Reports - SpeedNet Payroll</title>
    <link rel="stylesheet" href="../css/report.css">
</head>

<body>
    <header class="main-header">
        <div class="logo"><img src="../image1_edited.png" alt=""></div>
        <?php
        include '../module/components/nav.php';
        ?>
        <nav>
            <a href="../login.php" class="btn-login">Back</a>
        </nav>
    </header>
    <h2>Payroll Summary by Month</h2>

    <?php if (count($summary) === 0): ?>
        <p>No payroll data found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Total Gross Salary</th>
                    <th>Total Deductions</th>
                    <th>Total Bonuses</th>
                    <th>Total Net Salary</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($summary as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['pay_month']) ?></td>
                        <td>$<?= number_format($row['total_gross'], 2) ?></td>
                        <td>$<?= number_format($row['total_deductions'], 2) ?></td>
                        <td>$<?= number_format($row['total_bonuses'], 2) ?></td>
                        <td>$<?= number_format($row['total_net'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <canvas id="payrollChart"></canvas>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            const ctx = document.getElementById('payrollChart').getContext('2d');
            const payrollChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($months) ?>,
                    datasets: [
                        {
                            label: 'Gross Salary',
                            data: <?= json_encode($grossSalaries) ?>,
                            backgroundColor: 'rgba(102, 0, 102, 0.7)'
                        },
                        {
                            label: 'Deductions',
                            data: <?= json_encode($deductions) ?>,
                            backgroundColor: 'rgba(255, 99, 132, 0.7)'
                        },
                        {
                            label: 'Bonuses',
                            data: <?= json_encode($bonuses) ?>,
                            backgroundColor: 'rgba(54, 162, 235, 0.7)'
                        },
                        {
                            label: 'Net Salary',
                            data: <?= json_encode($netSalaries) ?>,
                            backgroundColor: 'rgba(75, 192, 192, 0.7)'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true }
                    },
                    plugins: {
                        legend: { position: 'top' },
                        title: {
                            display: true,
                            text: 'Company Payroll Overview'
                        }
                    }
                }
            });
        </script>
    <?php endif; ?>

</body>

</html>