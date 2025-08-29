<?php
session_start();
require '../config/db.php';

// Only HRM or Admin can access
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['hrm', 'admin'])) {
    header("Location: ../login.php");
    exit();
}

$message = "";

// Fetch active employees for dropdown
$stmt = $pdo->prepare("SELECT id, full_name FROM employees WHERE status = 'active' ORDER BY full_name");
$stmt->execute();
$employees = $stmt->fetchAll();

// Handle form submission for individual deduction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_deduction'])) {
    $employee_id = $_POST['employee_id'] ?? null;
    $type = trim($_POST['type'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $deduction_date = $_POST['deduction_date'] ?? date('Y-m-d');

    if (!$employee_id || !$type || $amount <= 0) {
        $message = "Please fill all fields correctly.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO deductions (employee_id, type, amount, deduction_date) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$employee_id, $type, $amount, $deduction_date])) {
            $message = "Deduction added successfully.";
        } else {
            $message = "Failed to add deduction.";
        }
    }
}

// Handle form submission for bulk deduction to all employees
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_bulk_deduction'])) {
    $type = trim($_POST['bulk_type'] ?? '');
    $amount = floatval($_POST['bulk_amount'] ?? 0);
    $deduction_date = $_POST['bulk_deduction_date'] ?? date('Y-m-d');

    if (!$type || $amount <= 0) {
        $message = "Please fill all fields correctly for bulk deduction.";
    } else {
        $success_count = 0;
        $error_count = 0;
        
        foreach ($employees as $employee) {
            $stmt = $pdo->prepare("INSERT INTO deductions (employee_id, type, amount, deduction_date) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$employee['id'], $type, $amount, $deduction_date])) {
                $success_count++;
            } else {
                $error_count++;
            }
        }
        
        $message = "Bulk deduction added: {$success_count} successful, {$error_count} failed.";
    }
}

// Fetch deductions list (latest first)
$stmt = $pdo->prepare("
    SELECT d.*, e.full_name FROM deductions d
    JOIN employees e ON d.employee_id = e.id
    ORDER BY d.deduction_date DESC, d.id DESC
");
$stmt->execute();
$deductions = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deductions - SpeedNet Payroll</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/modules/deduct.css">
</head>

<body class="bg-[#f3f4f6] font-inter">
    <header class="main-header flex justify-between items-center p-4 bg-white shadow">
        <div class="logo"><img src="../image1_edited.png" alt="Company Logo" class="w-32"></div>
        <div class="dropdown">
            <button id="dropdown-btn" class="dropdown-btn p-2">
                <svg class="hamburger-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z" />
                </svg>
            </button>
                <?php include '../module/components/nav.php'; ?>
    </header>
    <main class="container mx-auto p-8 pt-12">
        <h2 class="text-3xl font-bold text-gray-900 mb-8">Manage Deductions</h2>

        <?php if ($message): ?>
            <div class="<?= strpos($message, 'successfully') !== false || strpos($message, 'successful') !== false ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?> p-4 rounded mb-6">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Add Deduction Form -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h3 class="text-xl font-semibold text-gray-900 mb-4">Add New Deduction</h3>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="input-group">
                    <label for="employee_id" class="block text-sm font-medium text-gray-700 mb-1">Select Employee</label>
                    <select name="employee_id" id="employee_id" required class="w-full p-2 border border-gray-300 rounded-md">
                        <option value="">-- Select Employee --</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="input-group">
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Deduction Type</label>
                    <input type="text" name="type" id="type" placeholder="e.g., Tax, Loan" required class="w-full p-2 border border-gray-300 rounded-md">
                </div>

                <div class="input-group">
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                    <input type="number" step="0.01" min="0" name="amount" id="amount" placeholder="0.00" required class="w-full p-2 border border-gray-300 rounded-md">
                </div>

                <div class="input-group">
                    <label for="deduction_date" class="block text-sm font-medium text-gray-700 mb-1">Deduction Date</label>
                    <input type="date" name="deduction_date" id="deduction_date" value="<?= date('Y-m-d') ?>" required class="w-full p-2 border border-gray-300 rounded-md">
                </div>

                <div class="input-group md:col-span-2">
                    <button type="submit" name="add_deduction" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">Add Deduction</button>
                </div>
            </form>
        </div>

        <!-- Add Bulk Deduction Form -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h3 class="text-xl font-semibold text-gray-900 mb-4">Add Deduction to All Employees</h3>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="input-group">
                    <label for="bulk_type" class="block text-sm font-medium text-gray-700 mb-1">Deduction Type</label>
                    <input type="text" name="bulk_type" id="bulk_type" placeholder="e.g., Tax, Loan" required class="w-full p-2 border border-gray-300 rounded-md">
                </div>

                <div class="input-group">
                    <label for="bulk_amount" class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                    <input type="number" step="0.01" min="0" name="bulk_amount" id="bulk_amount" placeholder="0.00" required class="w-full p-2 border border-gray-300 rounded-md">
                </div>

                <div class="input-group">
                    <label for="bulk_deduction_date" class="block text-sm font-medium text-gray-700 mb-1">Deduction Date</label>
                    <input type="date" name="bulk_deduction_date" id="bulk_deduction_date" value="<?= date('Y-m-d') ?>" required class="w-full p-2 border border-gray-300 rounded-md">
                </div>

                <div class="input-group md:col-span-2">
                    <button type="submit" name="add_bulk_deduction" class="bg-rose-600 text-white px-4 py-2 rounded-md hover:bg-rose-700 transition" onclick="return confirm('Are you sure you want to add this deduction to ALL employees?')">Add Deduction to All Employees</button>
                </div>
            </form>
        </div>

        <!-- Deductions List -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-xl font-semibold text-gray-900 mb-4">Recent Deductions</h3>

            <?php if (count($deductions) === 0): ?>
                <p class="text-center text-gray-500 py-8">No deductions recorded yet.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deduction Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($deductions as $deduction): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($deduction['full_name']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($deduction['type']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap font-medium text-rose-600">FCFA<?= number_format($deduction['amount'], 2) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?= date('M j, Y', strtotime($deduction['deduction_date'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
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