<?php
// Ensure you have a database connection file
require '../config/db.php';

// Include Composer's autoloader file. This allows you to use the installed libraries.
require '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Get parameters
$employee_id = $_GET['employee_id'] ?? null;
$payroll_id = $_GET['payroll_id'] ?? null;

if (!$employee_id || !$payroll_id) {
    die("Invalid request.");
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
    die("Payslip not found.");
}

// Create the HTML content for the PDF.
// This is a simple HTML structure with basic styling.
$html = '
<!DOCTYPE html>
<html>
<head>
    <title>Payslip - ' . htmlspecialchars($payslip['full_name']) . '</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; font-size: 14px; }
        .payslip-container { border: 1px solid #ccc; padding: 20px; width: 100%; }
        h2 { text-align: center; }
        .info-section p { margin: 5px 0; }
        .summary-section { margin-top: 20px; }
        .summary-row { display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px dashed #eee; }
        .summary-row:last-child { border-bottom: none; font-weight: bold; }
    </style>
</head>
<body>
    <div class="payslip-container">
        <h2>Payslip</h2>
        <div class="info-section">
            <p><strong>Employee:</strong> ' . htmlspecialchars($payslip['full_name']) . '</p>
            <p><strong>Position:</strong> ' . htmlspecialchars($payslip['position']) . '</p>
            <p><strong>Period:</strong> ' . htmlspecialchars($payslip['period_start']) . ' to ' . htmlspecialchars($payslip['period_end']) . '</p>
        </div>
        <div class="summary-section">
            <div class="summary-row"><span>Gross Salary:</span> <span>$' . number_format($payslip['gross_salary'], 2) . '</span></div>
            <div class="summary-row"><span>Deductions:</span> <span>$' . number_format($payslip['deductions'], 2) . '</span></div>
            <div class="summary-row"><span>Net Salary:</span> <span>$' . number_format($payslip['net_salary'], 2) . '</span></div>
        </div>
    </div>
</body>
</html>';

// Instantiate Dompdf with options
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// Load the HTML into Dompdf
$dompdf->loadHtml($html);

// Set paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render the HTML as a PDF
$dompdf->render();

// Output the generated PDF to the browser for download
$filename = "Payslip_" . $payslip['full_name'] . "_" . $payslip['period_start'] . ".pdf";
$dompdf->stream($filename, ["Attachment" => true]);
?>