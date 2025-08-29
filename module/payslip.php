<?php
session_start();
require '../config/db.php';

// Initialize variables
$employee_id = $_GET['employee_id'] ?? null;
$payroll_id = $_GET['payroll_id'] ?? null;
$search_query = $_GET['search_query'] ?? '';
$error = '';
$payslip = null;
$bonuses = [];
$deductions = [];
$employees = [];
$employee_payrolls = [];
$selected_employee_name = '';

// Handle Employee Search
if (!empty($search_query)) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, full_name, position
            FROM employees
            WHERE full_name LIKE ?
            LIMIT 20
        ");
        $stmt->execute(["%$search_query%"]);
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($employees)) {
            $error = "No employees found matching your search.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// If an employee is selected, fetch their payroll records
if ($employee_id && !$payroll_id) {
    try {
        // First, get the employee's name to display
        $nameStmt = $pdo->prepare("SELECT full_name FROM employees WHERE id = ?");
        $nameStmt->execute([$employee_id]);
        $employee_data = $nameStmt->fetch(PDO::FETCH_ASSOC);
        if ($employee_data) {
            $selected_employee_name = $employee_data['full_name'];
        }

        // Then, fetch all payroll records for that employee
        $payrollStmt = $pdo->prepare("
            SELECT id, pay_month, payment_date
            FROM payroll
            WHERE employee_id = ?
            ORDER BY pay_month DESC
        ");
        $payrollStmt->execute([$employee_id]);
        $employee_payrolls = $payrollStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($employee_payrolls)) {
            $error = "No payroll records found for this employee.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// If both employee_id and payroll_id are provided, fetch the single payslip
if ($employee_id && $payroll_id) {
    try {
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
            // Your existing logic for calculating totals remains here
            $pay_month = $payslip['pay_month'];
            $payment_date = $payslip['payment_date'];

            if ($pay_month && !$payment_date) {
                $period_start = date('Y-m-01', strtotime($pay_month));
                $period_end = date('Y-m-t', strtotime($pay_month));
            } elseif ($payment_date) {
                $period_start = date('Y-m-01', strtotime($payment_date));
                $period_end = date('Y-m-t', strtotime($payment_date));
            } else {
                $period_start = date('Y-m-01');
                $period_end = date('Y-m-t');
            }

            $bonusStmt = $pdo->prepare("
                SELECT bonus_type, amount 
                FROM bonuses 
                WHERE employee_id = ? AND bonus_date BETWEEN ? AND ?
            ");
            $bonusStmt->execute([$employee_id, $period_start, $period_end]);
            $bonuses = $bonusStmt->fetchAll(PDO::FETCH_ASSOC);

            $deductionStmt = $pdo->prepare("
                SELECT type, amount 
                FROM deductions 
                WHERE employee_id = ? AND deduction_date BETWEEN ? AND ?
            ");
            $deductionStmt->execute([$employee_id, $period_start, $period_end]);
            $deductions = $deductionStmt->fetchAll(PDO::FETCH_ASSOC);

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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SpeedNet Payroll - Employee Payslip</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/modules/pay_slip.css">
</head>

<body class="bg-gray-100 min-h-screen">
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
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!$employee_id): ?>
            <div class="bg-white p-6 rounded-lg shadow mb-8">
                <h2 class="text-xl font-semibold mb-4"><i class="fas fa-search mr-2"></i> Search for an Employee</h2>
                <form method="GET" action="">
                    <div class="mb-4">
                        <label for="search_query" class="block text-sm font-medium text-gray-700 mb-1">Employee Name</label>
                        <input type="text" id="search_query" name="search_query" placeholder="Enter employee name"
                            value="<?php echo htmlspecialchars($search_query ?? ''); ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                        <i class="fas fa-search mr-2"></i> Search
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <?php if (!empty($employees)): ?>
            <div class="bg-white p-6 rounded-lg shadow mb-8">
                <h3 class="text-lg font-semibold mb-4">Select an Employee</h3>
                <div class="space-y-4">
                    <?php foreach ($employees as $employee): ?>
                        <div class="flex justify-between items-center p-4 border border-gray-200 rounded-lg">
                            <div>
                                <strong class="block text-gray-900"><?php echo htmlspecialchars($employee['full_name']); ?></strong>
                                <span class="text-sm text-gray-600"><?php echo htmlspecialchars($employee['position']); ?></span>
                            </div>
                            <a href="?employee_id=<?php echo htmlspecialchars($employee['id']); ?>" 
                               class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors text-sm">
                                Select
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($employee_id && !$payroll_id): ?>
            <div class="bg-white p-6 rounded-lg shadow mb-8">
                <h3 class="text-lg font-semibold mb-4">Select Payroll for <?php echo htmlspecialchars($selected_employee_name); ?></h3>
                <?php if (!empty($employee_payrolls)): ?>
                    <div class="space-y-4">
                        <?php foreach ($employee_payrolls as $payroll): ?>
                            <div class="flex justify-between items-center p-4 border border-gray-200 rounded-lg">
                                <div>
                                    <strong class="block text-gray-900">Payroll ID: <?php echo htmlspecialchars($payroll['id']); ?></strong>
                                    <span class="text-sm text-gray-600">Pay Period: <?php echo htmlspecialchars($payroll['pay_month']); ?></span>
                                    <span class="text-sm text-gray-600 block">Date: <?php echo htmlspecialchars($payroll['payment_date']); ?></span>
                                </div>
                                <a href="?employee_id=<?php echo htmlspecialchars($employee_id); ?>&payroll_id=<?php echo htmlspecialchars($payroll['id']); ?>"
                                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors text-sm">
                                    View Payslip
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center text-gray-500 py-4">No payroll records found for this employee.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($payslip): ?>
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="bg-blue-600 text-white p-6">
                    <div class="text-center">
                        <h1 class="text-2xl font-bold">SpeedNet Communications</h1>
                        <p class="text-sm opacity-90">chief street molyko</p>
                        <p class="text-sm opacity-90">Buea, Cameroon 10001 | Phone: (+237) 677-35-48-39</p>
                    </div>
                    <h2 class="text-center text-xl font-semibold mt-4">EMPLOYEE PAYSLIP</h2>
                </div>
                
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="font-semibold text-gray-900 mb-3">Employee Information</h3>
                            <p class="mb-2"><strong>Name:</strong> <?php echo htmlspecialchars($payslip['full_name']); ?></p>
                            <p class="mb-2"><strong>Position:</strong> <?php echo htmlspecialchars($payslip['position']); ?></p>
                            <p><strong>Employee ID:</strong> <?php echo htmlspecialchars($employee_id); ?></p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="font-semibold text-gray-900 mb-3">Payroll Information</h3>
                            <p class="mb-2"><strong>Pay Period:</strong> <?php echo htmlspecialchars($payslip['pay_month']); ?></p>
                            <p class="mb-2"><strong>Pay Date:</strong> <?php echo htmlspecialchars($payslip['payment_date']); ?></p>
                            <p class="mb-2"><strong>Payroll ID:</strong> <?php echo htmlspecialchars($payroll_id); ?></p>
                            <p><strong>Hours Worked:</strong> <?php echo htmlspecialchars($payslip['hours_worked']); ?></p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="bg-green-50 p-4 rounded-lg">
                            <h3 class="font-semibold text-green-900 mb-3">Earnings</h3>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span>Basic Salary</span>
                                    <span class="font-mono"><?php echo number_format($payslip['gross_salary'], 2); ?> CFA</span>
                                </div>
                                <?php foreach ($bonuses as $bonus): ?>
                                    <div class="flex justify-between">
                                        <span><?php echo htmlspecialchars($bonus['bonus_type']); ?> Bonus</span>
                                        <span class="font-mono"><?php echo number_format($bonus['amount'], 2); ?> CFA</span>
                                    </div>
                                <?php endforeach; ?>
                                <div class="flex justify-between border-t border-green-200 pt-2 mt-2">
                                    <strong>Total Earnings</strong>
                                    <strong class="font-mono"><?php echo number_format($payslip['gross_salary'] + $totalBonuses, 2); ?> CFA</strong>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-red-50 p-4 rounded-lg">
                            <h3 class="font-semibold text-red-900 mb-3">Deductions</h3>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span>Standard Deductions</span>
                                    <span class="font-mono"><?php echo number_format($payslip['deductions'], 2); ?> CFA</span>
                                </div>
                                <?php foreach ($deductions as $deduction): ?>
                                    <div class="flex justify-between">
                                        <span><?php echo htmlspecialchars($deduction['type']); ?></span>
                                        <span class="font-mono"><?php echo number_format($deduction['amount'], 2); ?> CFA</span>
                                    </div>
                                <?php endforeach; ?>
                                <div class="flex justify-between border-t border-red-200 pt-2 mt-2">
                                    <strong>Total Deductions</strong>
                                    <strong class="font-mono"><?php echo number_format($payslip['deductions'] + $totalDeductions, 2); ?> CFA</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-blue-50 p-6 rounded-lg text-center mb-6">
                        <h3 class="font-semibold text-blue-900 mb-2">NET PAY</h3>
                        <div class="text-3xl font-bold text-blue-800 mb-2"><?php echo number_format($netPay, 2); ?> CFA</div>
                        <p class="text-sm text-blue-700 mb-1">Paid via Direct Deposit to <?php echo htmlspecialchars($payslip['bank_account']); ?></p>
                        <p class="text-sm text-blue-700">
                            Status: <strong><?php echo htmlspecialchars($payslip['payment_status']); ?></strong>
                        </p>
                    </div>
                    
                    <div class="flex flex-wrap gap-4 justify-center">
                        <a href="../module/generate_payslip_pdf.php?employee_id=<?php echo htmlspecialchars($employee_id); ?>&payroll_id=<?php echo htmlspecialchars($payroll_id); ?>"
                            class="bg-green-600 text-white px-6 py-3 rounded-md hover:bg-green-700 transition-colors">
                            <i class="fas fa-download mr-2"></i> Download Payslip (PDF)
                        </a>
                        <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 transition-colors">
                            <i class="fas fa-print mr-2"></i> Print Payslip
                        </button>
                    </div>
                </div>
            </div>
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