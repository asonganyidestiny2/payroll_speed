<?php
session_start();
require '../config/db.php';

// Check login (adjust as needed)
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

// Initialize search query variable
$search_query = $_GET['search'] ?? null;

// Build the base SQL query
$sql = "SELECT * FROM employees WHERE 1=1";
$params = [];

// If a search query is present, modify the SQL
if ($search_query) {
    $sql .= " AND (full_name LIKE ? OR email LIKE ? OR position LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params = [$search_param, $search_param, $search_param];
}

// Add ordering to the query
$sql .= " ORDER BY id";

// Fetch employees based on the query
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $employees = $stmt->fetchAll();
} catch (PDOException $e) {
    $employees = [];
    $message = "Database error: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html>

<head>
    <title>Employee List - SpeedNet Payroll</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/modules/view.css">
</head>

<body>
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
        <h2 class="text-2xl font-bold mb-6">Employee List</h2>

        <?php if (isset($_GET['updated'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">Employee updated successfully!</div>
        <?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">Employee deleted successfully!</div>
        <?php endif; ?>

        <!-- Search Form -->
        <div class="bg-white p-6 rounded-lg shadow mb-8">
            <h3 class="text-xl font-semibold text-gray-900 mb-4">Search Employees</h3>
            <form method="GET" action="" class="flex flex-col sm:flex-row gap-4">
                <input type="text" name="search" class="flex-grow px-4 py-2 border border-gray-300 rounded-md"
                    placeholder="Search by name, email, or position..."
                    value="<?= htmlspecialchars($search_query ?? '') ?>">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">Search</button>
                <a href="view.php" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-400 text-center">Clear Search</a>
            </form>
        </div>

        <?php if (count($employees) > 0): ?>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Salary Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Base Salary</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Hired</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($employees as $emp): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($emp['id']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($emp['full_name']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($emp['email']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($emp['position']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars(ucfirst($emp['salary_type'])) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">FCFA<?= htmlspecialchars(number_format($emp['base_salary'], 2)) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($emp['date_hired']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?= $emp['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                            <?= htmlspecialchars(ucfirst($emp['status'])) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="edit_employee.php?id=<?= $emp['id'] ?>" class="text-indigo-600 hover:text-indigo-900 mr-2">Edit</a>
                                        <span class="text-gray-400">|</span>
                                        <a href="delete_employee.php?delete=<?= $emp['id'] ?>"
                                            onclick="return confirm('Do you want to delete this employee?')"
                                            class="text-red-600 hover:text-red-900 ml-2">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-white p-6 rounded-lg shadow text-center">
                <p class="text-gray-500">No employees found.</p>
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