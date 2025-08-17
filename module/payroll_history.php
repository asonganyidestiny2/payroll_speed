<?php
require '../config/db.php';

$id = $_GET['id'] ?? 0;
$id = intval($id);

$stmt = $pdo->prepare("
    SELECT p.*, e.full_name, e.position, e.bank_account
    FROM payroll p
    JOIN employees e ON p.employee_id = e.id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$payroll = $stmt->fetch();

if (!$payroll) {
    die("Payslip not found.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payslip - <?= htmlspecialchars($payroll['full_name']) ?></title>
    <link rel="stylesheet" href="../css/pay.css">
</head>
<body>
    <header class="main-header">
    <div class="logo"><img src="../image1_edited.png" alt="../css/.payroll.css"></div>
    <nav>
        <a href="../index.php">Home</a>
        <a href="#features">Features</a>
        <a href="../login.php" class="btn-login">Back</a>
    </nav>
</header>

<h2>Payslip</h2>

<div class="info">
    <div><span class="label">Employee:</span> <?= htmlspecialchars($payroll['full_name']) ?></div>
    <div><span class="label">Position:</span> <?= htmlspecialchars($payroll['position']) ?></div>
    <div><span class="label">Bank Account:</span> <?= htmlspecialchars($payroll['bank_account']) ?></div>
    <div><span class="label">Month:</span> <?= date('F Y', strtotime($payroll['pay_month'].'-01')) ?></div>
</div>

<div class="salary-details">
    <div><span class="label">Hours Worked:</span> <?= htmlspecialchars($payroll['hours_worked']) ?></div>
    <div><span class="label">Gross Salary:</span> $<?= number_format($payroll['gross_salary'], 2) ?></div>
    <div><span class="label">Bonuses:</span> $<?= number_format($payroll['bonuses'], 2) ?></div>
    <div><span class="label">Deductions:</span> $<?= number_format($payroll['deductions'], 2) ?></div>
    <div class="net-salary">Net Salary: $<?= number_format($payroll['net_salary'], 2) ?></div>
</div>

<button class="print-btn" onclick="window.print()">Print Payslip</button>

</body>
</html>
