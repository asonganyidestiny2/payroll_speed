<?php
session_start();
require '../config/db.php';

// Check if parameters are provided
$employee_id = $_GET['employee_id'] ?? null;
$payroll_id = $_GET['payroll_id'] ?? null;

if (!$employee_id || !$payroll_id) {
    die("Error: Employee ID or Payroll ID is missing.");
}

try {
    // Fetch employee and payroll data
    $stmt = $pdo->prepare("
        SELECT 
            e.id as employee_id,
            e.full_name, 
            e.position, 
            e.bank_account,
            e.base_salary,
            p.id as payroll_id,
            p.pay_month,
            p.hours_worked,
            p.gross_salary,
            p.deductions,
            p.bonuses,
            p.net_salary,
            p.payment_status,
            p.payment_date
        FROM employees e
        JOIN payroll p ON e.id = p.employee_id
        WHERE e.id = ? AND p.id = ?
    ");
    $stmt->execute([$employee_id, $payroll_id]);
    $payslip = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payslip) {
        die("Payslip data not found for Employee ID: $employee_id and Payroll ID: $payroll_id");
    }

    // Calculate totals - using the values directly from payroll table
    $totalBonuses = $payslip['bonuses'] ?? 0;
    $totalDeductions = $payslip['deductions'] ?? 0;

    // Use net_salary from payroll if available, otherwise calculate it
    if (isset($payslip['net_salary']) && $payslip['net_salary'] > 0) {
        $netPay = $payslip['net_salary'];
    } else {
        $netPay = $payslip['gross_salary'] + $totalBonuses - $totalDeductions;
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Include the TCPDF library
require_once __DIR__ . '/../vendor/autoload.php';

// Create new PDF document
$pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('SpeedNet Payroll System');
$pdf->SetAuthor('SpeedNet Communications');
$pdf->SetTitle('Employee Payslip - ' . $payslip['full_name']);
$pdf->SetSubject('Payslip for ' . $payslip['full_name']);

// Remove header and footer for cleaner look
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Set margins
$pdf->SetMargins(15, 15, 15);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 15);

// Set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Add a page
$pdf->AddPage();

// Format payment date properly
$paymentDate = $payslip['payment_date'];
if ($paymentDate && $paymentDate != '0000-00-00') {
    $formattedDate = date('F j, Y', strtotime($paymentDate));
} else {
    $formattedDate = 'Pending';
}

//html content for pdf
$html = '
<style>
    .header { 
        text-align: center; 
        margin-bottom: 20px; 
        border-bottom: 2px solid #4a6491;
        padding-bottom: 10px;
    }
    .company { 
        font-size: 20px; 
        font-weight: bold; 
        color: #2c3e50;
    }
    .title { 
        font-size: 16px; 
        margin: 5px 0; 
        color: #4a6491;
    }
    .section { 
        margin: 15px 0; 
    }
    .section-title { 
        background-color: #4a6491; 
        color: white;
        padding: 8px; 
        font-weight: bold; 
        margin-bottom: 10px;
    }
    .info-table { 
        width: 100%; 
        border-collapse: collapse; 
        margin: 10px 0; 
    }
    .info-table td { 
        padding: 8px; 
        border: 1px solid #ddd; 
    }
    .detail-table { 
        width: 100%; 
        border-collapse: collapse; 
        margin: 10px 0;
    }
    .detail-table th { 
        background-color: #2c3e50; 
        color: white; 
        padding: 10px; 
        text-align: left; 
    }
    .detail-table td { 
        padding: 10px; 
        border: 1px solid #ddd; 
    }
    .total { 
        font-weight: bold; 
        border-top: 2px solid #000 !important; 
        background-color: #f9f9f9;
    }
    .summary { 
        background-color: #2c3e50; 
        color: white; 
        padding: 15px; 
        text-align: center; 
        margin-top: 20px;
        border-radius: 5px;
    }
    .net-pay { 
        font-size: 24px; 
        font-weight: bold; 
        margin: 10px 0; 
    }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
</style>

<div class="header">
    <div class="company">SpeedNet Communications</div>
    <div class="title">EMPLOYEE PAYSLIP</div>
    <div style="font-size: 12px; color: #666;">chief street, Buea | Cameroon, CMR 10001</div>
</div>

<div class="section">
    <div class="section-title">Employee Information</div>
    <table class="info-table">
        <tr>
            <td width="50%"><strong>Name:</strong> ' . htmlspecialchars($payslip['full_name']) . '</td>
            <td width="50%"><strong>Employee ID:</strong> ' . htmlspecialchars($payslip['employee_id']) . '</td>
        </tr>
        <tr>
            <td><strong>Position:</strong> ' . htmlspecialchars($payslip['position']) . '</td>
            <td><strong>Bank Account:</strong> ' . htmlspecialchars($payslip['bank_account']) . '</td>
        </tr>
        <tr>
            <td><strong>Base Salary:</strong>' . number_format($payslip['base_salary'], 2) . ' cfa</td>
            <td><strong>Payroll ID:</strong> ' . htmlspecialchars($payslip['payroll_id']) . '</td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">Payroll Information</div>
    <table class="info-table">
        <tr>
            <td width="50%"><strong>Pay Period:</strong> ' . htmlspecialchars($payslip['pay_month']) . '</td>
            <td width="50%"><strong>Pay Date:</strong> ' . htmlspecialchars($formattedDate) . '</td>
        </tr>
        <tr>
            <td><strong>Hours Worked:</strong> ' . number_format($payslip['hours_worked'], 2) . '</td>
            <td><strong>Status:</strong> ' . htmlspecialchars(ucfirst($payslip['payment_status'])) . '</td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">Earnings & Deductions Summary</div>
    <table class="detail-table">
        <tr>
            <th width="70%">Description</th>
            <th width="30%" class="text-right">Amount</th>
        </tr>
        <tr>
            <td>Gross Salary</td>
            <td class="text-right">' . number_format($payslip['gross_salary'], 2) . ' cfa</td>
        </tr>
        <tr>
            <td>Bonuses</td>
            <td class="text-right">' . number_format($totalBonuses, 2) . ' cfa</td>
        </tr>
        <tr class="total">
            <td><strong>Total Earnings</strong></td>
            <td class="text-right"><strong>' . number_format($payslip['gross_salary'] + $totalBonuses, 2) . ' cfa</strong></td>
        </tr>
        <tr>
            <td>Deductions</td>
            <td class="text-right">' . number_format($totalDeductions, 2) . ' -cfa</td>
        </tr>
        <tr class="total">
            <td><strong>Total Deductions</strong></td>
            <td class="text-right"><strong>' . number_format($totalDeductions, 2) . '-cfa</strong></td>
        </tr>
    </table>
</div>

<div class="summary">
    <div style="font-size: 18px; margin-bottom: 10px;"><strong>NET PAY</strong></div>
    <div class="net-pay">' . number_format($netPay, 2) . ' cfa</div>
    <div style="margin: 10px 0;">Paid via Direct Deposit to: ' . htmlspecialchars($payslip['bank_account']) . '</div>
    <div>Payment Status: ' . htmlspecialchars(ucfirst($payslip['payment_status'])) . '</div>
</div>

<div style="margin-top: 30px; text-align: center; font-size: 10px; color: #666; border-top: 1px solid #ddd; padding-top: 10px;">
    Generated on: ' . date('F j, Y \a\t g:i A') . ' | 
    © ' . date('Y') . ' SpeedNet Communications. All rights reserved.
</div>';

// Output HTML content
$pdf->writeHTML($html, true, false, true, false, '');

// Close and output PDF document
$pdf->Output('payslip_' . $employee_id . '_' . $payroll_id . '.pdf', 'D');

exit;