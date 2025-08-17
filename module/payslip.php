<?php
require '../config/db.php';

// Get parameters
$employee_id = $_GET['employee_id'] ?? null;
$payroll_id = $_GET['payroll_id'] ?? null;

if (!$employee_id || !$payroll_id) {
    die("Invalid request.");
}

// Fetch employee info
$stmt = $pdo->prepare("
    SELECT e.full_name, e.position, p.*
    FROM employees e
    JOIN payroll_history p ON e.id = p.employee_id
    WHERE e.id = ? AND p.id = ?
");
$stmt->execute([$employee_id, $payroll_id]);
$payslip = $stmt->fetch();

if (!$payslip) {
    die("Payslip not found.");
}
?>
<!DOCTYPE html>
<html>
<head>
    <header class="main-header">
    <div class="logo"><img src="image1_edited.png" alt=""></div>
    <nav>
        <a href="index.php">Home</a>
        <a href="#features">Features</a>
        <a href="login.php" class="btn-login">LOGIN</a>
    </nav>
</header>
    <title>Payslip - <?= htmlspecialchars($payslip['full_name']) ?></title>
    <link rel="stylesheet" href="../module/payslip.php">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .payslip { border: 1px solid #ccc; padding: 20px; width: 400px; }
        h2 { text-align: center; }
        .row { display: flex; justify-content: space-between; margin: 8px 0; }
        .btn-print { display: block; text-align: center; margin-top: 20px; }
    </style>
</head>
<body>
    <header class="main-header">
    <div class="logo"><img src="../image1_edited.png" alt=""></div>
    <nav>
        <a href="../index.php">Home</a>
        <a href="#features">Features</a>
        <a href="../login.php" class="btn-login">Back</a>
    </nav>
</header>
    <div class="payslip">
        <h2>Payslip</h2>
        <p><strong>Employee:</strong> <?= htmlspecialchars($payslip['full_name']) ?></p>
        <p><strong>Position:</strong> <?= htmlspecialchars($payslip['position']) ?></p>
        <p><strong>Period:</strong> <?= $payslip['period_start'] ?> to <?= $payslip['period_end'] ?></p>

        <div class="row"><span>Gross Salary:</span> <span><?= number_format($payslip['gross_salary'], 2) ?></span></div>
        <div class="row"><span>Deductions:</span> <span><?= number_format($payslip['deductions'], 2) ?></span></div>
        <div class="row"><span>Net Salary:</span> <span><?= number_format($payslip['net_salary'], 2) ?></span></div>

        <p class="btn-print"><button onclick="window.print()">Print Payslip</button></p>
    </div>
</body>
</html>
