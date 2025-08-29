<?php
session_start();
require '../config/db.php';

// Only HRM or Admin can access
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['hrm', 'admin'])) {
    header("Location: ../login.php");
    exit();
}

$message = "";

// Fetch all active employees for dropdown
$stmt = $pdo->prepare("SELECT id, full_name FROM employees WHERE status = 'active' ORDER BY full_name");
$stmt->execute();
$employees = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_bonus'])) {
    $employee_id = $_POST['employee_id'] ?? null;
    $bonus_type = trim($_POST['bonus_type'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $bonus_date = $_POST['bonus_date'] ?? date('Y-m-d');

    if (!$employee_id || !$bonus_type || $amount <= 0) {
        $message = "Please fill all fields correctly.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO bonuses (employee_id, bonus_type, amount, bonus_date) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$employee_id, $bonus_type, $amount, $bonus_date])) {
            $message = "Bonus added successfully.";
        } else {
            $message = "Failed to add bonus.";
        }
    }
}

// Fetch bonuses list (latest first)
$stmt = $pdo->prepare("
    SELECT b.*, e.full_name FROM bonuses b
    JOIN employees e ON b.employee_id = e.id
    ORDER BY b.bonus_date DESC, b.id DESC
");
$stmt->execute();
$bonuses = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bonuses - SpeedNet Payroll</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/modules/bonus.css">
</head>
<body class="font-inter bg-[#f3f4f6]">
    <header class="main-header">
        <div class="logo"><img src="../image1_edited.png" alt="Company Logo" class="w-32"></div>
        <div class="dropdown">
            <button id="dropdown-btn" class="dropdown-btn p-2">
                <svg class="hamburger-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/>
                </svg>
            </button>
                <?php include '../module/components/nav.php'; ?>
        </div>
    </header>

    <main class="container mx-auto p-8 pt-12">
        <h2 class="text-3xl font-bold text-gray-900 mb-8">Manage Bonuses</h2>

        <?php if ($message): ?>
            <div class="<?= strpos($message, 'successfully') !== false ? 'bg-green-100 text-green-700 p-4 rounded mb-6' : 'bg-red-100 text-red-700 p-4 rounded mb-6' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Add Bonus Form -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h3 class="text-xl font-semibold text-gray-900 mb-4">Add New Bonus</h3>
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
                    <label for="bonus_type" class="block text-sm font-medium text-gray-700 mb-1">Bonus Type</label>
                    <input type="text" name="bonus_type" id="bonus_type" placeholder="e.g., Performance, Holiday" required class="w-full p-2 border border-gray-300 rounded-md">
                </div>

                <div class="input-group">
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                    <input type="number" step="0.01" min="0" name="amount" id="amount" placeholder="0.00" required class="w-full p-2 border border-gray-300 rounded-md">
                </div>

                <div class="input-group">
                    <label for="bonus_date" class="block text-sm font-medium text-gray-700 mb-1">Bonus Date</label>
                    <input type="date" name="bonus_date" id="bonus_date" value="<?= date('Y-m-d') ?>" required class="w-full p-2 border border-gray-300 rounded-md">
                </div>

                <div class="input-group md:col-span-2">
                    <button type="submit" name="add_bonus" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">Add Bonus</button>
                </div>
            </form>
        </div>

        <!-- Bonuses List -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-xl font-semibold text-gray-900 mb-4">Recent Bonuses</h3>
            
            <?php if (count($bonuses) === 0): ?>
                <p class="text-center text-gray-500 py-8">No bonuses recorded yet.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bonus Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($bonuses as $bonus): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($bonus['full_name']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($bonus['bonus_type']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap font-medium text-green-600">FCFA<?= number_format($bonus['amount'], 2) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?= date('M j, Y', strtotime($bonus['bonus_date'])) ?></td>
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