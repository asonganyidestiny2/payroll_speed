<?php
session_start();
require '../config/db.php';

// Initialize variables
$employee_id = $_GET['employee_id'] ?? null;
$payroll_id = $_GET['payroll_id'] ?? null;
$error = '';
$payslip = null;
$bonuses = [];
$deductions = [];

// If parameters are provided, fetch data
if ($employee_id && $payroll_id) {
    try {
        // Fetch employee and payroll data
        $stmt = $pdo->prepare("
            SELECT e.full_name, e.position, e.bank_account, p.*
            FROM employees e
            JOIN payroll p ON e.id = p.employee_id
            WHERE e.id = ? AND p.id = ?
        ");
        $stmt->execute([$employee_id, $payroll_id]);
        $payslip = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$payslip) {
            $error = "Payslip data not found for the provided IDs.";
        } else {
            // Calculate start and end dates for the pay period (assuming monthly pay)
            $pay_month = $payslip['pay_month'];
            $payment_date = $payslip['payment_date'];

            // If pay_month is in format like "January 2024", parse it
            if ($pay_month && !$payment_date) {
                $period_start = date('Y-m-01', strtotime($pay_month));
                $period_end = date('Y-m-t', strtotime($pay_month));
            }
            // If payment_date is available, use it to determine period
            elseif ($payment_date) {
                $period_start = date('Y-m-01', strtotime($payment_date));
                $period_end = date('Y-m-t', strtotime($payment_date));
            }
            // Fallback: assume current month
            else {
                $period_start = date('Y-m-01');
                $period_end = date('Y-m-t');
            }

            // Fetch bonuses for this payroll period
            $bonusStmt = $pdo->prepare("
                SELECT bonus_type, amount 
                FROM bonuses 
                WHERE employee_id = ? AND bonus_date BETWEEN ? AND ?
            ");
            $bonusStmt->execute([$employee_id, $period_start, $period_end]);
            $bonuses = $bonusStmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch deductions for this payroll period
            $deductionStmt = $pdo->prepare("
                SELECT type, amount 
                FROM deductions 
                WHERE employee_id = ? AND deduction_date BETWEEN ? AND ?
            ");
            $deductionStmt->execute([$employee_id, $period_start, $period_end]);
            $deductions = $deductionStmt->fetchAll(PDO::FETCH_ASSOC);

            // Calculate totals
            $totalBonuses = array_sum(array_column($bonuses, 'amount'));
            $totalDeductions = array_sum(array_column($deductions, 'amount'));
            $netPay = $payslip['gross_salary'] + $totalBonuses - $payslip['deductions'] - $totalDeductions;
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SpeedNet Payroll - Employee Payslip</title>
    <link rel="stylesheet" href="../css/slip.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <header>
            <div class="logo">
                <i class="fas fa-network-wired"></i>
                <h1>SpeedNet Payroll System</h1>
            </div>
            <div class="user-info">
                <p><i class="fas fa-user-circle"></i> Welcome, Payroll Administrator</p>
            </div>
        </header>
        <?php
        include '../module/components/nav.php';
        ?>

        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php elseif (!$employee_id && !$payroll_id): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <span>Error: Employee ID or Payroll ID is missing from the URL. Please enter them below.</span>
            </div>
        <?php endif; ?>

        <div class="search-form">
            <h2 class="form-title"><i class="fas fa-search"></i> Retrieve Payslip Information</h2>
            <form method="GET" action="">
                <div class="form-group">
                    <label for="employee_id">Employee ID</label>
                    <input type="text" id="employee_id" name="employee_id" placeholder="Enter employee ID"
                        value="<?php echo htmlspecialchars($employee_id ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="payroll_id">Payroll ID</label>
                    <input type="text" id="payroll_id" name="payroll_id" placeholder="Enter payroll ID"
                        value="<?php echo htmlspecialchars($payroll_id ?? ''); ?>">
                </div>
                <button type="submit" class="btn"><i class="fas fa-file-invoice"></i> Retrieve Payslip</button>
            </form>
        </div>

        <?php if ($payslip): ?>
            <div class="payslip-container">
                <div class="payslip-header">
                    <div class="company-info">
                        <h1>SpeedNet Communications</h1>
                        <p>chief street molyko</p>
                        <p>Buea, Cameroon 10001 | Phone: (+237) 677-35-48-39</p>
                    </div>
                    <h2 class="payslip-title">EMPLOYEE PAYSLIP</h2>
                </div>

                <div class="payslip-content">
                    <div class="employee-info">
                        <div class="info-group">
                            <h3>Employee Information</h3>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($payslip['full_name']); ?></p>
                            <p><strong>Position:</strong> <?php echo htmlspecialchars($payslip['position']); ?></p>
                            <p><strong>Employee ID:</strong> <?php echo htmlspecialchars($employee_id); ?></p>
                        </div>

                        <div class="info-group">
                            <h3>Payroll Information</h3>
                            <p><strong>Pay Period:</strong> <?php echo htmlspecialchars($payslip['pay_month']); ?></p>
                            <p><strong>Pay Date:</strong> <?php echo htmlspecialchars($payslip['payment_date']); ?></p>
                            <p><strong>Payroll ID:</strong> <?php echo htmlspecialchars($payroll_id); ?></p>
                            <p><strong>Hours Worked:</strong> <?php echo htmlspecialchars($payslip['hours_worked']); ?></p>
                        </div>
                    </div>

                    <div class="payment-details">
                        <div class="earnings">
                            <h3 class="section-title">Earnings</h3>
                            <div class="detail-row">
                                <span>Basic Salary</span>
                                <span><?php echo number_format($payslip['gross_salary'], 2); ?>cfa</span>
                            </div>

                            <?php foreach ($bonuses as $bonus): ?>
                                <div class="detail-row">
                                    <span><?php echo htmlspecialchars($bonus['bonus_type']); ?> Bonus</span>
                                    <span><?php echo number_format($bonus['amount'], 2); ?>cfa</span>
                                </div>
                            <?php endforeach; ?>

                            <div class="detail-row total">
                                <span>Total Earnings</span>
                                <span><?php echo number_format($payslip['gross_salary'] + $totalBonuses, 2); ?>cfa</span>
                            </div>
                        </div>

                        <div class="deductions">
                            <h3 class="section-title">Deductions</h3>

                            <div class="detail-row">
                                <span>Standard Deductions</span>
                                <span><?php echo number_format($payslip['deductions'], 2); ?>-cfa</span>
                            </div>

                            <?php foreach ($deductions as $deduction): ?>
                                <div class="detail-row">
                                    <span><?php echo htmlspecialchars($deduction['type']); ?></span>
                                    <span><?php echo number_format($deduction['amount'], 2); ?>-cfa</span>
                                </div>
                            <?php endforeach; ?>

                            <div class="detail-row total">
                                <span>Total Deductions</span>
                                <span><?php echo number_format($payslip['deductions'] + $totalDeductions, 2); ?>-cfa</span>
                            </div>
                        </div>
                    </div>

                    <div class="summary">
                        <h3>NET PAY</h3>
                        <div class="net-pay"><?php echo number_format($netPay, 2); ?>cfa</div>
                        <p>Paid via Direct Deposit to <?php echo htmlspecialchars($payslip['bank_account']); ?></p>
                        <p>Status: <strong><?php echo htmlspecialchars($payslip['payment_status']); ?></strong></p>
                    </div>

                    <div class="actions">
                        <a href="generate_payslip_pdf.php?employee_id=<?php echo htmlspecialchars($employee_id); ?>&payroll_id=<?php echo htmlspecialchars($payroll_id); ?>"
                            class="btn btn-download">
                            <i class="fas fa-download"></i> Download Payslip (PDF)
                        </a>
                        <button onclick="window.print()" class="btn btn-print">
                            <i class="fas fa-print"></i> Print Payslip
                        </button>
                    </div>
                </div>
            </div>

            <?php
            include '../module/components/footer.php';
            // endif;
            ?>
        <?php endif; ?>
    </div>
</body>

</html>