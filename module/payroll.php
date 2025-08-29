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
// This will be used for both the search functionality and the "Run All" feature
$stmt = $pdo->prepare("SELECT * FROM employees WHERE status = 'active' ORDER BY full_name");
$stmt->execute();
$employees = $stmt->fetchAll();

// Initialize variables for single employee calculation
$pay_month = $_POST['pay_month'] ?? date('Y-m');
$employee_id = $_POST['employee_id'] ?? null;
$payrollData = null;

// =========================================================================
// NEW FEATURE: Handle "Run All Payroll" button click
// =========================================================================
if (isset($_POST['run_all_payroll'])) {
    $pay_month_all = $_POST['pay_month_all'] ?? date('Y-m');
    $employees_processed = [];
    $errors = [];

    // Begin a transaction to ensure atomicity
    $pdo->beginTransaction();

    try {
        // Loop through all active employees and calculate/save their payroll
        foreach ($employees as $emp) {
            $start_date = $pay_month_all . '-01';
            $end_date = date("Y-m-t", strtotime($start_date));

            // Sum the 'hours_worked' column directly
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(hours_worked), 0) FROM attendance WHERE employee_id = ? AND date BETWEEN ? AND ?");
            $stmt->execute([$emp['id'], $start_date, $end_date]);
            $hours = $stmt->fetchColumn();

            // Calculate bonuses sum for the month
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM bonuses WHERE employee_id = ? AND bonus_date BETWEEN ? AND ?");
            $stmt->execute([$emp['id'], $start_date, $end_date]);
            $total_bonuses = $stmt->fetchColumn();

            // Calculate deductions sum for the month
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM deductions WHERE employee_id = ? AND deduction_date BETWEEN ? AND ?");
            $stmt->execute([$emp['id'], $start_date, $end_date]);
            $total_deductions = $stmt->fetchColumn();

            // Calculate gross salary
            if ($emp['salary_type'] === 'hourly') {
                $gross_salary = $hours * $emp['base_salary'];
            } else {
                $gross_salary = $emp['base_salary'];
                $hours = 0;
            }
            $net_salary = $gross_salary + $total_bonuses - $total_deductions;

            // Check if payroll already exists
            $stmt = $pdo->prepare("SELECT * FROM payroll WHERE employee_id = ? AND pay_month = ?");
            $stmt->execute([$emp['id'], $pay_month_all]);
            $existing_payroll = $stmt->fetch();

            if (!$existing_payroll) {
                // Insert new payroll if it doesn't exist
                $stmt = $pdo->prepare("INSERT INTO payroll (employee_id, pay_month, hours_worked, gross_salary, deductions, bonuses, net_salary, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
                $saved = $stmt->execute([
                    $emp['id'],
                    $pay_month_all,
                    $hours,
                    $gross_salary,
                    $total_deductions,
                    $total_bonuses,
                    $net_salary
                ]);
                if (!$saved) {
                    $errors[] = "Error saving payroll for " . htmlspecialchars($emp['full_name']);
                }
            }
            $employees_processed[] = $emp['full_name'];
        }

        // Now, update all pending payrolls for the month to 'paid'
        $stmt = $pdo->prepare("UPDATE payroll SET payment_status = 'paid', payment_date = CURDATE() WHERE pay_month = ? AND payment_status = 'pending'");
        $result = $stmt->execute([$pay_month_all]);

        $pdo->commit();

        if ($result) {
            $message = "🎉 ALL PAYROLLS PROCESSED! The payroll for " . date('F Y', strtotime($pay_month_all . '-01')) . " has been run and marked as PAID for all active employees. " . count($employees_processed) . " employees processed.";
        } else {
            $message = "Error processing payroll for all employees.";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "An error occurred during bulk payroll processing: " . $e->getMessage();
    }
}


//  =========================================================================
//  Existing Logic: Handling single employee calculation and save/run
//  =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $employee_id && !isset($_POST['run_all_payroll'])) {
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

        // Sum the 'hours_worked' column directly
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(hours_worked), 0) FROM attendance WHERE employee_id = ? AND date BETWEEN ? AND ?");
        $stmt->execute([$employee_id, $start_date, $end_date]);
        $hours = $stmt->fetchColumn();

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
            'pay_month' => $pay_month,
            'net_salary_raw' => $net_salary,
            'gross_salary_raw' => $gross_salary,
            'bonuses_raw' => $total_bonuses,
            'deductions_raw' => $total_deductions,
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
                        $employee['salary_type'] === 'hourly' ? ($hours === 'N/A' ? 0 : $hours) : 0,
                        $payrollData['gross_salary_raw'],
                        $payrollData['deductions_raw'],
                        $payrollData['bonuses_raw'],
                        $payrollData['net_salary_raw'],
                        $existing_payroll['id']
                    ]);
                    if ($updated) {
                        $message = "Payroll updated successfully.";
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
                    $employee['salary_type'] === 'hourly' ? ($hours === 'N/A' ? 0 : $hours) : 0,
                    $payrollData['gross_salary_raw'],
                    $payrollData['deductions_raw'],
                    $payrollData['bonuses_raw'],
                    $payrollData['net_salary_raw']
                ]);
                if ($saved) {
                    $message = "Payroll saved successfully with status: PENDING";
                    $stmt = $pdo->prepare("SELECT * FROM payroll WHERE employee_id = ? AND pay_month = ?");
                    $stmt->execute([$employee_id, $pay_month]);
                    $payrollData['existing_payroll'] = $stmt->fetch();
                } else {
                    $message = "Error saving payroll.";
                }
            }
        }
    }
}

// Handle single-employee "Run" button click
if (isset($_POST['run_payroll'])) {
    // Refresh existing payroll data just in case
    $stmt = $pdo->prepare("SELECT * FROM payroll WHERE employee_id = ? AND pay_month = ?");
    $stmt->execute([$_POST['employee_id'], $_POST['pay_month']]);
    $existing_payroll = $stmt->fetch();

    if ($existing_payroll && $existing_payroll['payment_status'] === 'pending') {
        $stmt = $pdo->prepare("UPDATE payroll SET payment_status = 'paid', payment_date = CURDATE() WHERE id = ?");
        $result = $stmt->execute([$existing_payroll['id']]);

        if ($result) {
            $message = "🎉 PAYROLL PROCESSED! Status changed from PENDING to PAID";
            $stmt = $pdo->prepare("SELECT * FROM payroll WHERE employee_id = ? AND pay_month = ?");
            $stmt->execute([$_POST['employee_id'], $_POST['pay_month']]);
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

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Calculation - SpeedNet Payroll</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/modules/payroll_history.css">
</head>
<body class="bg-gray-100 font-inter min-h-screen">
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
        <h2 class="text-3xl font-bold text-gray-800 mb-8">Payroll Calculation</h2>

        <?php if ($message): ?>
            <div
                class="p-4 mb-6 rounded-lg font-medium <?= strpos($message, 'PAYROLL PROCESSED') !== false ? 'bg-green-50 text-green-700' : 'bg-blue-50 text-blue-700' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Form Card for Single Employee -->
            <div class="bg-white p-6 rounded-lg shadow md:col-span-2">
                <h3 class="text-xl font-semibold text-gray-800 mb-6">Calculate Single Employee Payroll</h3>
                <form method="POST" action="">
                    <div class="mb-4">
                        <label for="employee_id" class="block mb-2 text-sm font-medium text-gray-700">Select Employee</label>
                        <!-- NEW FEATURE: Search input and dynamic dropdown -->
                        <input type="text" id="employee-search" placeholder="Search for an employee..."
                            class="block w-full px-4 py-2 border border-gray-300 rounded-md mb-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <select name="employee_id" id="employee_id" class="block w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="">-- Select --</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= $emp['id'] ?>"
                                    data-name="<?= htmlspecialchars(strtolower($emp['full_name'])) ?>"
                                    <?= isset($employee_id) && $employee_id == $emp['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($emp['full_name']) ?> (<?= ucfirst($emp['salary_type']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="pay_month" class="block mb-2 text-sm font-medium text-gray-700">Select Month</label>
                        <input type="month" name="pay_month" id="pay_month" class="block w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            value="<?= htmlspecialchars($pay_month) ?>" required>
                    </div>

                    <button type="submit" name="calculate" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                        Calculate Payroll
                    </button>
                </form>
            </div>

            <!-- Card for "Run All" feature -->
            <div class="bg-white p-6 rounded-lg shadow flex flex-col justify-between">
                <div class="flex-grow">
                    <h3 class="text-xl font-semibold text-gray-800 mb-6">Batch Payroll Processing</h3>
                    <p class="text-gray-500 mb-4 text-sm">This will calculate and finalize payroll for ALL active
                        employees for the selected month in a single action.</p>
                </div>
                <form method="POST" action="" class="w-full">
                    <input type="hidden" name="pay_month_all" value="<?= htmlspecialchars($pay_month) ?>">
                    <button type="button" onclick="showRunAllPayrollModal()" class="w-full bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors">
                        Process All Payrolls for This Month
                    </button>
                </form>
            </div>

            <!-- Payroll Summary Card -->
            <?php if ($payrollData): ?>
                <div class="bg-white p-6 rounded-lg shadow relative overflow-hidden md:col-span-2 lg:col-span-3 mt-8">
                    <!-- Status Badge -->
                    <div class="absolute top-4 right-4">
                        <?php if ($payrollData['existing_payroll']): ?>
                            <span class="px-3 py-1 text-xs font-semibold rounded-full <?= $payrollData['existing_payroll']['payment_status'] === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                <?= ucfirst($payrollData['existing_payroll']['payment_status']) ?>
                                <?php if ($payrollData['existing_payroll']['payment_status'] === 'paid' && $payrollData['existing_payroll']['payment_date']): ?>
                                    <span class="text-xs block mt-1">Paid:
                                        <?= date('M d, Y', strtotime($payrollData['existing_payroll']['payment_date'])) ?></span>
                                <?php endif; ?>
                            </span>
                        <?php else: ?>
                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Not Saved</span>
                        <?php endif; ?>
                    </div>

                    <h3 class="text-xl font-semibold text-gray-800 mb-6">Payroll Summary</h3>

                    <div class="space-y-4">
                        <div class="flex justify-between items-center border-b border-gray-300 pb-2">
                            <span class="text-sm font-medium text-gray-500">Employee</span>
                            <span
                                class="font-semibold text-gray-800"><?= htmlspecialchars($payrollData['employee']['full_name']) ?></span>
                        </div>
                        <div class="flex justify-between items-center border-b border-gray-300 pb-2">
                            <span class="text-sm font-medium text-gray-500">Position</span>
                            <span
                                class="font-semibold text-gray-800"><?= htmlspecialchars($payrollData['employee']['position']) ?></span>
                        </div>
                        <div class="flex justify-between items-center border-b border-gray-300 pb-2">
                            <span class="text-sm font-medium text-gray-500">Month</span>
                            <span
                                class="font-semibold text-gray-800"><?= date('F Y', strtotime($payrollData['pay_month'] . '-01')) ?></span>
                        </div>
                        <div class="flex justify-between items-center border-b border-gray-300 pb-2">
                            <span class="text-sm font-medium text-gray-500">Salary Type</span>
                            <span
                                class="font-semibold text-gray-800"><?= ucfirst($payrollData['employee']['salary_type']) ?></span>
                        </div>
                        <div class="flex justify-between items-center border-b border-gray-300 pb-2">
                            <span class="text-sm font-medium text-gray-500">Hours Worked</span>
                            <span
                                class="font-semibold text-gray-800"><?= htmlspecialchars($payrollData['hours_worked']) ?></span>
                        </div>
                        <div class="flex justify-between items-center border-b border-gray-300 pb-2">
                            <span class="text-sm font-medium text-gray-500">Base Salary</span>
                            <span
                                class="font-semibold text-gray-800">FCFA<?= number_format($payrollData['employee']['base_salary'], 2) ?></span>
                        </div>
                        <div class="flex justify-between items-center border-b border-gray-300 pb-2">
                            <span class="text-sm font-medium text-gray-500">Gross Salary</span>
                            <span class="font-semibold text-gray-800">FCFA<?= $payrollData['gross_salary'] ?></span>
                        </div>
                        <div class="flex justify-between items-center border-b border-gray-300 pb-2">
                            <span class="text-sm font-medium text-gray-500">Total Bonuses</span>
                            <span class="font-semibold text-green-600">+FCFA<?= $payrollData['bonuses'] ?></span>
                        </div>
                        <div class="flex justify-between items-center border-b border-gray-300 pb-2">
                            <span class="text-sm font-medium text-gray-500">Total Deductions</span>
                            <span class="font-semibold text-red-600">-FCFA<?= $payrollData['deductions'] ?></span>
                        </div>
                        <div class="mt-6 pt-4 border-t-2 border-dashed border-gray-300">
                            <div class="flex justify-between items-center text-xl font-bold">
                                <span>Net Salary</span>
                                <span class="text-gray-800">FCFA<?= $payrollData['net_salary'] ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 space-y-4 md:space-y-0 md:space-x-4 md:flex">
                        <?php if (!$payrollData['existing_payroll'] || $payrollData['existing_payroll']['payment_status'] === 'pending'): ?>
                            <form method="POST" action="" class="inline-block">
                                <input type="hidden" name="employee_id" value="<?= htmlspecialchars($employee_id) ?>">
                                <input type="hidden" name="pay_month" value="<?= htmlspecialchars($pay_month) ?>">
                                <button type="submit" name="save_payroll" class="w-full md:w-auto bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors mb-2 md:mb-0">
                                    <?= $payrollData['existing_payroll'] ? 'Update Payroll' : 'Save Payroll' ?>
                                </button>
                            </form>

                            <?php if ($payrollData['existing_payroll']): ?>
                                <button onclick="showRunPayrollModal()" class="w-full md:w-auto bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors">
                                    ▶️ Run Payroll (Mark as Paid)
                                </button>
                            <?php endif; ?>

                        <?php else: ?>
                            <div class="bg-green-100 p-4 rounded-lg border border-green-200">
                                <strong class="text-green-700">✅ PAYROLL COMPLETED!</strong>
                                <p class="text-green-600 text-sm mt-1">This payroll has been processed and marked as PAID.</p>
                                <?php if ($payrollData['existing_payroll']['payment_date']): ?>
                                    <p class="text-green-600 text-xs mt-1">Payment processed on:
                                        <?= date('F d, Y', strtotime($payrollData['existing_payroll']['payment_date'])) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Run Single Payroll Confirmation Modal -->
    <div id="runPayrollModal" class="fixed inset-0 z-50 overflow-y-auto hidden bg-black bg-opacity-50">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="bg-white rounded-lg shadow-xl p-8 max-w-sm w-full">
                <div class="text-center">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Confirm Payroll Run</h3>
                    <p class="text-sm text-gray-500 mb-6">
                        This action will mark the payroll as <span class="font-semibold text-red-600">PAID</span>. This
                        cannot be undone. Are you sure you want to proceed?
                    </p>
                    <div class="flex justify-center space-x-4">
                        <button type="button" onclick="hideRunPayrollModal()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 transition-colors">
                            Cancel
                        </button>
                        <form method="POST" action="" style="display: inline-block;">
                            <input type="hidden" name="employee_id" value="<?= htmlspecialchars($employee_id) ?>">
                            <input type="hidden" name="pay_month" value="<?= htmlspecialchars($pay_month) ?>">
                            <button type="submit" name="run_payroll"
                                class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 transition-colors">
                                Confirm & Run
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- NEW FEATURE: Run All Payroll Confirmation Modal -->
    <div id="runAllPayrollModal" class="fixed inset-0 z-50 overflow-y-auto hidden bg-black bg-opacity-50">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="bg-white rounded-lg shadow-xl p-8 max-w-lg w-full">
                <div class="text-center">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Confirm Batch Payroll Run</h3>
                    <p class="text-sm text-gray-500 mb-6">
                        This will calculate and mark as <span class="font-semibold text-red-600">PAID</span> all pending
                        payrolls for <strong id="modal-month-text" class="text-gray-800"></strong>. This cannot be
                        undone. Are you sure you want to proceed?
                    </p>
                    <div class="flex justify-center space-x-4">
                        <button type="button" onclick="hideRunAllPayrollModal()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 transition-colors">
                            Cancel
                        </button>
                        <form method="POST" action="" style="display: inline-block;">
                            <input type="hidden" id="modal_pay_month_all" name="pay_month_all"
                                value="<?= htmlspecialchars($pay_month) ?>">
                            <button type="submit" name="run_all_payroll"
                                class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 transition-colors">
                                Confirm & Process All
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Modal functions
        const runPayrollModal = document.getElementById('runPayrollModal');
        const runAllPayrollModal = document.getElementById('runAllPayrollModal');

        function showRunPayrollModal() {
            runPayrollModal.classList.remove('hidden');
        }

        function hideRunPayrollModal() {
            runPayrollModal.classList.add('hidden');
        }

        // NEW FUNCTION: Show confirmation modal for "Run All Payroll"
        function showRunAllPayrollModal() {
            const payMonthInput = document.getElementById('pay_month');
            const modalMonthText = document.getElementById('modal-month-text');
            const modalPayMonthInput = document.getElementById('modal_pay_month_all');

            const date = new Date(payMonthInput.value + '-01');
            const options = {
                year: 'numeric',
                month: 'long'
            };
            modalMonthText.textContent = date.toLocaleDateString('en-US', options);
            modalPayMonthInput.value = payMonthInput.value;

            runAllPayrollModal.classList.remove('hidden');
        }

        function hideRunAllPayrollModal() {
            runAllPayrollModal.classList.add('hidden');
        }

        // NEW FEATURE: Employee Search functionality
        const employeeSearchInput = document.getElementById('employee-search');
        const employeeSelect = document.getElementById('employee_id');
        const employees = Array.from(employeeSelect.options).slice(1); // Exclude the "-- Select --" option

        employeeSearchInput.addEventListener('keyup', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            employees.forEach(option => {
                const employeeName = option.getAttribute('data-name');
                if (employeeName.includes(searchTerm)) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            });
            // If the currently selected option is hidden, reset the selection
            if (employeeSelect.selectedOptions[0] && employeeSelect.selectedOptions[0].style.display === 'none') {
                employeeSelect.value = '';
            }
        });
    </script>
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