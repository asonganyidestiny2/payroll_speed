<?php
session_start();
require '../config/db.php';

// Only HRM or Admin can access
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['hrm', 'admin'])) {
    header("Location: ../login.php");
    exit();
}

$message = "";

// Fetch all active employees
$stmt = $pdo->prepare("SELECT * FROM employees WHERE status = 'active' ORDER BY full_name");
$stmt->execute();
$employees = $stmt->fetchAll();

$pay_month = $_POST['pay_month'] ?? date('Y-m');
$employee_id = $_POST['employee_id'] ?? null;

$payrollData = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $employee_id) {
    // Get employee info
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch();

    if (!$employee) {
        $message = "Employee not found.";
    } else {
        // Calculate total hours worked in the month (for hourly employees)
        $start_date = $pay_month . '-01';
        $end_date = date("Y-m-t", strtotime($start_date));

        $stmt = $pdo->prepare("SELECT SUM(CASE WHEN status='present' THEN 8 ELSE 0 END) as total_hours FROM attendance WHERE employee_id = ? AND date BETWEEN ? AND ?");
        $stmt->execute([$employee_id, $start_date, $end_date]);
        $hours = $stmt->fetchColumn() ?? 0;

        // Calculate bonuses sum for the month
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM bonuses WHERE employee_id = ? AND bonus_date BETWEEN ? AND ?");
        $stmt->execute([$employee_id, $start_date, $end_date]);
        $total_bonuses = $stmt->fetchColumn();

        // Calculate deductions sum for the month
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM deductions WHERE employee_id = ? AND deduction_date BETWEEN ? AND ?");
        $stmt->execute([$employee_id, $start_date, $end_date]);
        $total_deductions = $stmt->fetchColumn();

        // Calculate gross salary
        if ($employee['salary_type'] === 'hourly') {
            $gross_salary = $hours * $employee['base_salary'];
        } else {
            $gross_salary = $employee['base_salary'];
            $hours = 'N/A';
        }

        $net_salary = $gross_salary + $total_bonuses - $total_deductions;

        $payrollData = [
            'employee' => $employee,
            'hours_worked' => $hours,
            'gross_salary' => number_format($gross_salary, 2),
            'bonuses' => number_format($total_bonuses, 2),
            'deductions' => number_format($total_deductions, 2),
            'net_salary' => number_format($net_salary, 2),
            'pay_month' => $pay_month
        ];

        // Check if payroll already exists
        $stmt = $pdo->prepare("SELECT * FROM payroll WHERE employee_id = ? AND pay_month = ?");
        $stmt->execute([$employee_id, $pay_month]);
        $existing_payroll = $stmt->fetch();
        
        $payrollData['existing_payroll'] = $existing_payroll;

        // Save payroll to DB if "Save Payroll" button clicked
        if (isset($_POST['save_payroll'])) {
            if ($existing_payroll) {
                if ($existing_payroll['payment_status'] === 'paid') {
                    $message = "Cannot modify payroll that has already been paid.";
                } else {
                    // Update existing payroll
                    $stmt = $pdo->prepare("UPDATE payroll SET hours_worked = ?, gross_salary = ?, deductions = ?, bonuses = ?, net_salary = ? WHERE id = ?");
                    $updated = $stmt->execute([
                        $employee['salary_type'] === 'hourly' ? $hours : 0,
                        $gross_salary,
                        $total_deductions,
                        $total_bonuses,
                        $net_salary,
                        $existing_payroll['id']
                    ]);
                    if ($updated) {
                        $message = "Payroll updated successfully.";
                        // Refresh payroll data
                        $stmt = $pdo->prepare("SELECT * FROM payroll WHERE employee_id = ? AND pay_month = ?");
                        $stmt->execute([$employee_id, $pay_month]);
                        $payrollData['existing_payroll'] = $stmt->fetch();
                    } else {
                        $message = "Error updating payroll.";
                    }
                }
            } else {
                // Insert new payroll
                $stmt = $pdo->prepare("INSERT INTO payroll (employee_id, pay_month, hours_worked, gross_salary, deductions, bonuses, net_salary, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
                $saved = $stmt->execute([
                    $employee_id,
                    $pay_month,
                    $employee['salary_type'] === 'hourly' ? $hours : 0,
                    $gross_salary,
                    $total_deductions,
                    $total_bonuses,
                    $net_salary
                ]);
                if ($saved) {
                    $message = "Payroll saved successfully with status: PENDING";
                    // Get the newly saved payroll
                    $stmt = $pdo->prepare("SELECT * FROM payroll WHERE employee_id = ? AND pay_month = ?");
                    $stmt->execute([$employee_id, $pay_month]);
                    $payrollData['existing_payroll'] = $stmt->fetch();
                } else {
                    $message = "Error saving payroll.";
                }
            }
        }

        // NEW FEATURE: RUN PAYROLL - Change status from pending to paid
        if (isset($_POST['run_payroll'])) {
            if ($existing_payroll && $existing_payroll['payment_status'] === 'pending') {
                $stmt = $pdo->prepare("UPDATE payroll SET payment_status = 'paid', payment_date = CURDATE() WHERE id = ?");
                $result = $stmt->execute([$existing_payroll['id']]);
                
                if ($result) {
                    $message = "🎉 PAYROLL PROCESSED! Status changed from PENDING to PAID";
                    // Refresh payroll data to show updated status
                    $stmt = $pdo->prepare("SELECT * FROM payroll WHERE employee_id = ? AND pay_month = ?");
                    $stmt->execute([$employee_id, $pay_month]);
                    $payrollData['existing_payroll'] = $stmt->fetch();
                } else {
                    $message = "Error processing payroll.";
                }
            } elseif (!$existing_payroll) {
                $message = "Please save the payroll first before running it.";
            } else {
                $message = "This payroll has already been processed and marked as PAID.";
            }
        }
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Payroll Calculation - SpeedNet Payroll</title>
    <link rel="stylesheet" href="../css/.payroll.css">
    
</head>
<body>
    <header class="main-header">
        <div class="logo"><img src="../image1_edited.png" alt="SpeedNet Payroll"></div>
        <nav>
            <a href="../index.php">Home</a>
            <a href="#features">Features</a>
            <a href="../login.php" class="btn-login">Back</a>
        </nav>
    </header>

    <h2>Payroll Calculation</h2>

    <?php if ($message): ?>
        <div class="<?= strpos($message, 'PAYROLL PROCESSED') !== false ? 'success-message' : 'message' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="employee_id">Select Employee</label>
        <select name="employee_id" id="employee_id" required>
            <option value="">-- Select --</option>
            <?php foreach ($employees as $emp): ?>
                <option value="<?= $emp['id'] ?>" <?= isset($employee_id) && $employee_id == $emp['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($emp['full_name']) ?> (<?= ucfirst($emp['salary_type']) ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <label for="pay_month">Select Month</label>
        <input type="month" name="pay_month" id="pay_month" value="<?= htmlspecialchars($pay_month) ?>" required>

        <button type="submit" name="calculate">Calculate Payroll</button>
    </form>

    <?php if ($payrollData): ?>
        <div class="payroll-summary">
            <div class="status-indicator">
                <?php if ($payrollData['existing_payroll']): ?>
                    <span class="status-badge status-<?= $payrollData['existing_payroll']['payment_status'] ?>">
                        <?= ucfirst($payrollData['existing_payroll']['payment_status']) ?>
                        <?php if ($payrollData['existing_payroll']['payment_status'] === 'paid' && $payrollData['existing_payroll']['payment_date']): ?>
                            <br><small style="font-size: 0.7em;">Paid: <?= date('M d, Y', strtotime($payrollData['existing_payroll']['payment_date'])) ?></small>
                        <?php endif; ?>
                    </span>
                <?php else: ?>
                    <span class="status-badge" style="background: #6c757d; color: white;">Not Saved</span>
                <?php endif; ?>
            </div>

            <h3>Payroll Summary</h3>
            
            <div>
                <strong>Employee:</strong> 
                <span><?= htmlspecialchars($payrollData['employee']['full_name']) ?></span>
            </div>
            <div>
                <strong>Position:</strong> 
                <span><?= htmlspecialchars($payrollData['employee']['position']) ?></span>
            </div>
            <div>
                <strong>Month:</strong> 
                <span><?= date('F Y', strtotime($payrollData['pay_month'].'-01')) ?></span>
            </div>
            <div>
                <strong>Salary Type:</strong> 
                <span><?= ucfirst($payrollData['employee']['salary_type']) ?></span>
            </div>
            <div>
                <strong>Hours Worked:</strong> 
                <span><?= htmlspecialchars($payrollData['hours_worked']) ?></span>
            </div>
            <div>
                <strong>Base Salary:</strong> 
                <span>FCFA<?= number_format($payrollData['employee']['base_salary'], 2) ?></span>
            </div>
            <div>
                <strong>Gross Salary:</strong> 
                <span>FCFA<?= $payrollData['gross_salary'] ?></span>
            </div>
            <div>
                <strong>Total Bonuses:</strong> 
                <span style="color: #28a745;">+FCFA<?= $payrollData['bonuses'] ?></span>
            </div>
            <div>
                <strong>Total Deductions:</strong> 
                <span style="color: #dc3545;">-FCFA<?= $payrollData['deductions'] ?></span>
            </div>
            <div style="font-size: 1.2em; margin-top: 15px; padding: 10px; background: #e9ecef; border-radius: 5px;">
                <strong>Net Salary: FCFA<?= $payrollData['net_salary'] ?></strong>
            </div>

            <div class="button-group">
                <?php if (!$payrollData['existing_payroll'] || $payrollData['existing_payroll']['payment_status'] === 'pending'): ?>
                    
                    <!-- Save/Update Payroll Button -->
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="employee_id" value="<?= htmlspecialchars($employee_id) ?>">
                        <input type="hidden" name="pay_month" value="<?= htmlspecialchars($pay_month) ?>">
                        <button type="submit" name="save_payroll" style="background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px;">
                            <?= $payrollData['existing_payroll'] ? 'Update Payroll' : 'Save Payroll' ?>
                        </button>
                    </form>

                    <!-- RUN PAYROLL Button (only if payroll exists and is pending) -->
                    <?php if ($payrollData['existing_payroll']): ?>
                        <form method="POST" action="" style="display: inline;">
                            <input type="hidden" name="employee_id" value="<?= htmlspecialchars($employee_id) ?>">
                            <input type="hidden" name="pay_month" value="<?= htmlspecialchars($pay_month) ?>">
                            <button type="submit" name="run_payroll" class="run-payroll-btn" 
                                    onclick="return confirm('This will mark the payroll as PAID and cannot be undone. Continue?')">
                                ▶️ RUN PAYROLL (Mark as Paid)
                            </button>
                        </form>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- Already Paid Status -->
                    <div style="background: #d4edda; padding: 15px; border-radius: 5px; border: 1px solid #c3e6cb;">
                        <strong style="color: #155724;">✅ PAYROLL COMPLETED!</strong>
                        <p style="margin: 5px 0; color: #155724;">
                            This payroll has been processed and marked as PAID.
                            <?php if ($payrollData['existing_payroll']['payment_date']): ?>
                                <br>Payment processed on: <?= date('F d, Y', strtotime($payrollData['existing_payroll']['payment_date'])) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</body>
</html>