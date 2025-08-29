<?php
session_start();
require '../config/db.php';

// Only HRM or Admin can access
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['hrm', 'admin'])) {
    header("Location: ../login.php");
    exit();
}

// Fetch all active employees for the filter dropdown
$stmt = $pdo->prepare("SELECT id, full_name FROM employees WHERE status = 'active' ORDER BY full_name");
$stmt->execute();
$employees = $stmt->fetchAll();

// Initialize filter variables
$filter_employee_id = $_GET['employee_id'] ?? null;
$filter_month = $_GET['pay_month'] ?? null;
$search_query = $_GET['search'] ?? null;

// Build the SQL query with filters
$sql = "SELECT p.*, e.full_name, e.position, e.salary_type
        FROM payroll p
        JOIN employees e ON p.employee_id = e.id
        WHERE 1=1";
$params = [];

if ($filter_employee_id) {
    $sql .= " AND p.employee_id = ?";
    $params[] = $filter_employee_id;
}
if ($filter_month) {
    $sql .= " AND p.pay_month = ?";
    $params[] = $filter_month;
}
if ($search_query) {
    $sql .= " AND (e.full_name LIKE ? OR e.position LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
}

$sql .= " ORDER BY p.pay_month DESC, e.full_name ASC";

// Execute the query
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $payroll_history = $stmt->fetchAll();
    $message = count($payroll_history) > 0 ? "" : "No payroll records found for the selected filters.";
} catch (PDOException $e) {
    $message = "Database error: " . $e->getMessage();
    $payroll_history = [];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll History - SpeedNet Payroll</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/modules/payroll_history.css">
</head>

<body class="bg-[#f3f4f6] font-inter">
    <header class="main-header flex justify-between items-center p-4 bg-white shadow">
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
        <h2 class="text-3xl font-bold text-gray-900 mb-8">Payroll History</h2>

        <!-- Search Bar -->
        <div class="card mb-8">
            <h3 class="text-xl font-semibold text-gray-900 mb-4">Search Payroll Records</h3>
            <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div class="search-container relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" name="search" id="search" class="pl-10 search-input block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2 border"
                        placeholder="Search by name or position" value="<?= htmlspecialchars($search_query ?? '') ?>">
                </div>

                <div class="input-group">
                    <label for="employee_id" class="block mb-2 text-sm font-medium text-gray-700">Select Employee</label>
                    <select name="employee_id" id="employee_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2 border">
                        <option value="">All Employees</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?= $emp['id'] ?>" <?= $filter_employee_id == $emp['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($emp['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="input-group">
                    <label for="pay_month" class="block mb-2 text-sm font-medium text-gray-700">Select Month</label>
                    <input type="month" name="pay_month" id="pay_month" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2 border"
                        value="<?= htmlspecialchars($filter_month ?? '') ?>">
                </div>

                <div class="flex space-x-2">
                    <button type="submit" class="bg-indigo-600 text-white font-semibold px-4 py-2 rounded-lg flex items-center justify-center hover:bg-indigo-700 transition-colors duration-200 w-full">Apply Filters</button>
                    <a href="payroll_history.php"
                        class="bg-gray-500 text-white font-semibold px-4 py-2 rounded-lg flex items-center justify-center hover:bg-gray-600 transition-colors duration-200">
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <?php if ($message): ?>
            <div class="p-4 mb-6 rounded-lg font-medium bg-red-100 text-red-700">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Payroll History Table -->
        <div class="card overflow-x-auto bg-white shadow rounded-lg">
            <table class="w-full text-left striped-table">
                <thead class="bg-gray-50">
                    <tr class="text-gray-600 uppercase text-sm leading-normal">
                        <th class="py-3 px-6 text-left">Employee Name</th>
                        <th class="py-3 px-6 text-left">Position</th>
                        <th class="py-3 px-6 text-left">Pay Month</th>
                        <th class="py-3 px-6 text-left">Gross Salary</th>
                        <th class="py-3 px-6 text-left">Bonuses</th>
                        <th class="py-3 px-6 text-left">Deductions</th>
                        <th class="py-3 px-6 text-left">Net Salary</th>
                        <th class="py-3 px-6 text-left">Status</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm font-light">
                    <?php if (count($payroll_history) > 0): ?>
                        <?php foreach ($payroll_history as $payroll): ?>
                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                <td class="py-3 px-6 whitespace-nowrap"><?= htmlspecialchars($payroll['full_name']) ?></td>
                                <td class="py-3 px-6"><?= htmlspecialchars($payroll['position']) ?></td>
                                <td class="py-3 px-6"><?= date('F Y', strtotime($payroll['pay_month'] . '-01')) ?></td>
                                <td class="py-3 px-6 text-indigo-600 font-medium">
                                    FCFA<?= number_format($payroll['gross_salary'], 2) ?></td>
                                <td class="py-3 px-6 text-emerald-600 font-medium">
                                    +FCFA<?= number_format($payroll['bonuses'], 2) ?></td>
                                <td class="py-3 px-6 text-rose-600 font-medium">
                                    -FCFA<?= number_format($payroll['deductions'], 2) ?></td>
                                <td class="py-3 px-6 font-bold text-lg text-gray-900">
                                    FCFA<?= number_format($payroll['net_salary'], 2) ?></td>
                                <td class="py-3 px-6">
                                    <span class="px-3 py-1 text-xs font-medium rounded-full 
                                        <?= $payroll['payment_status'] === 'paid' ? 'bg-green-100 text-green-800' : 
                                           ($payroll['payment_status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                           'bg-red-100 text-red-800') ?>">
                                        <?= ucfirst($payroll['payment_status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="py-4 px-6 text-center text-gray-500">
                                No payroll records found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>

</body>
<script>
    // Dropdown Navigation Toggle
    document.addEventListener("DOMContentLoaded", () => {
        const dropdownBtn = document.getElementById('dropdown-btn');
        const dropdownMenu = document.getElementById('dropdown-menu');
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