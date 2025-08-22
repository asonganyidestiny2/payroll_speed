<?php
require '../config/db.php';

// Get parameters from the URL
$employee_id = $_GET['employee_id'] ?? null;
$payroll_id = $_GET['payroll_id'] ?? null;

if (!$employee_id || !$payroll_id) {
    die("Error: Employee ID or Payroll ID is missing from the URL.");
}

// Fetch employee and payroll data from the database
$stmt = $pdo->prepare("
    SELECT e.full_name, e.position, p.*
    FROM employees e
    JOIN payroll_history p ON e.id = p.employee_id
    WHERE e.id = ? AND p.id = ?
");
$stmt->execute([$employee_id, $payroll_id]);
$payslip = $stmt->fetch();

if (!$payslip) {
    die("Payslip data not found.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payslip</title>
</head>
<body>
    <h2>Payslip</h2>
    <p><strong>Employee:</strong> <?= htmlspecialchars($payslip['full_name']) ?></p>

    <a href="generate_payslip_pdf.php?employee_id=<?= htmlspecialchars($employee_id) ?>&payroll_id=<?= htmlspecialchars($payroll_id) ?>">
        <button>Download Payslip (PDF)</button>
    </a>
</body>
</html>