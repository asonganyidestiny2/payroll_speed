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
    ORDER BY pay_month DESC
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
    $months[] = date('F Y', strtotime($row['pay_month']));
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
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/modules/report.css">
</head>

<body>
    <header class="main-header flex justify-between items-center p-4 bg-white shadow-md">
        <div class="logo"><img src="../image1_edited.png" alt="Company Logo" class="w-32"></div>
        <div class="dropdown">
            <button id="dropdown-btn" class="dropdown-btn">
                <svg class="hamburger-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z" />
                </svg>
            </button>
                <?php include '../module/components/nav.php'; ?>
        </div>
    </header>

    <main class="container mx-auto p-8 pt-12">
        <h2 class="text-2xl font-bold mb-6">Payroll Summary by Month</h2>

        <?php if (count($summary) === 0): ?>
            <div class="bg-white p-6 rounded-lg shadow text-center">
                <p class="text-gray-500">No payroll data found.</p>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow overflow-hidden mb-8">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Month</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Gross Salary</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Deductions</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Bonuses</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Net Salary</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($summary as $row): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars(date('F Y', strtotime($row['pay_month']))) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">FCFA<?= number_format($row['total_gross'], 2) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">FCFA<?= number_format($row['total_deductions'], 2) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">FCFA<?= number_format($row['total_bonuses'], 2) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">FCFA<?= number_format($row['total_net'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <canvas id="payrollChart"></canvas>
            </div>

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
                                backgroundColor: '#4f46e5',
                                borderColor: '#4f46e5',
                                borderWidth: 1
                            },
                            {
                                label: 'Deductions',
                                data: <?= json_encode($deductions) ?>,
                                backgroundColor: '#dc2626',
                                borderColor: '#dc2626',
                                borderWidth: 1
                            },
                            {
                                label: 'Bonuses',
                                data: <?= json_encode($bonuses) ?>,
                                backgroundColor: '#22c55e',
                                borderColor: '#22c55e',
                                borderWidth: 1
                            },
                            {
                                label: 'Net Salary',
                                data: <?= json_encode($netSalaries) ?>,
                                backgroundColor: '#3b82f6',
                                borderColor: '#3b82f6',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: '#e5e7eb'
                                },
                                ticks: {
                                    color: '#4b5563',
                                    callback: function(value) {
                                        return 'FCFA' + value.toLocaleString();
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: '#4b5563'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    color: '#1f2937'
                                }
                            },
                            title: {
                                display: true,
                                text: 'Company Payroll Overview',
                                color: '#1f2937',
                                font: {
                                    size: 16
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': FCFA' + context.raw.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
            </script>
        <?php endif; ?>
    </main>
</body>
<script>
    // Dropdown Navigation Toggle
    document.addEventListener("DOMContentLoaded", () => {
        const dropdownBtn = document.getElementById('dropdown-btn');
        const dropdownContainer = document.querySelector('.dropdown');

        dropdownBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdownContainer.classList.toggle('open');
        });

        // Close the dropdown if the user clicks outside of it
        document.addEventListener('click', (event) => {
            if (!dropdownContainer.contains(event.target)) {
                dropdownContainer.classList.remove('open');
            }
        });
    });
</script>

</html>