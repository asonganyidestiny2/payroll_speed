<?php
require_once __DIR__ . '/../config/auth_middleware.php';
requireRole(['admin', 'hrm']);
require_once __DIR__ . '/../config/db.php';

// Handle new applicant submission
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_applicant'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $position_applied = trim($_POST['position_applied']);

    if ($full_name && $position_applied) {
        $stmt = $pdo->prepare("INSERT INTO recruitments (full_name, email, phone, position_applied) VALUES (?, ?, ?, ?)");
        $stmt->execute([$full_name, $email, $phone, $position_applied]);
        $message = "Applicant added successfully!";
    } else {
        $error = "Full Name and Position are required.";
    }
}

// Handle hiring an applicant
if (isset($_GET['hire_id'])) {
    $hire_id = (int) $_GET['hire_id'];
    $stmt = $pdo->prepare("SELECT * FROM recruitments WHERE id = ?");
    $stmt->execute([$hire_id]);
    $applicant = $stmt->fetch();

    if ($applicant) {
        // Insert into employees table
        $stmt2 = $pdo->prepare("INSERT INTO employees (full_name, email, phone, position, status) VALUES (?, ?, ?, ?, 'active')");
        $stmt2->execute([$applicant['full_name'], $applicant['email'], $applicant['phone'], $applicant['position_applied']]);
        // Update recruitment status
        $stmt3 = $pdo->prepare("UPDATE recruitments SET status='Hired' WHERE id=?");
        $stmt3->execute([$hire_id]);
        $message = "Applicant hired successfully!";
    }
}

// Initialize search query variable
$search_query = $_GET['search'] ?? null;

// Build the base SQL query
$sql = "SELECT * FROM recruitments WHERE 1=1";
$params = [];

// If a search query is present, modify the SQL
if ($search_query) {
    $sql .= " AND (full_name LIKE ? OR email LIKE ? OR position_applied LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params = [$search_param, $search_param, $search_param];
}

// Add ordering to the query
$sql .= " ORDER BY id";

// Fetch applicants based on the query
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $applicants = $stmt->fetchAll();
} catch (PDOException $e) {
    $applicants = [];
    $message = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recruitment - SpeedNet Payroll</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/modules/recruit.css">
    <style>
        /* Dropdown specific styles */
        .dropdown {
            position: relative;
            display: inline-block;
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1000;
            border-radius: 4px;
            padding: 0.5rem 0;
        }
        
        .dropdown.open .dropdown-content {
            display: block;
        }
        
        .dropdown-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 4px;
        }
        
        .dropdown-btn:hover {
            background-color: #f1f1f1;
        }
        
        .hamburger-icon {
            width: 24px;
            height: 24px;
            fill: currentColor;
        }
    </style>
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
        <h1 class="text-2xl font-bold mb-6">Recruitment Management</h1>

        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?= $message ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?= $error ?></div>
        <?php endif; ?>

        <div class="bg-white p-6 rounded-lg shadow mb-8">
            <h3 class="text-xl font-semibold text-gray-900 mb-4">Add New Applicant</h3>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="text" name="full_name" placeholder="Full Name" required 
                    class="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <input type="email" name="email" placeholder="Email" 
                    class="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <input type="text" name="phone" placeholder="Phone" 
                    class="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <input type="text" name="position_applied" placeholder="Position Applied" required 
                    class="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <div class="md:col-span-2">
                    <button type="submit" name="add_applicant" 
                        class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors">
                        Add Applicant
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Applicants</h2>
            
            <!-- Search Form -->
            <div class="mb-6">
                <form method="GET" action="" class="flex flex-col sm:flex-row gap-4">
                    <input type="text" name="search" 
                        class="flex-grow px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Search by name, email, or position..."
                        value="<?= htmlspecialchars($search_query ?? '') ?>">
                    <button type="submit" 
                        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                        Search
                    </button>
                    <a href="recruit.php" 
                        class="bg-gray-300 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-400 transition-colors text-center">
                        Clear Search
                    </a>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position Applied</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($applicants) > 0): ?>
                            <?php foreach ($applicants as $applicant): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap"><?= $applicant['id'] ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($applicant['full_name']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($applicant['email']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($applicant['phone']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($applicant['position_applied']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?= $applicant['status'] === 'Hired' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                            <?= htmlspecialchars(ucfirst($applicant['status'])) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <?php if ($applicant['status'] !== 'Hired'): ?>
                                            <a href="?hire_id=<?= $applicant['id'] ?>" 
                                                class="text-green-600 hover:text-green-900"
                                                onclick="return confirm('Do you want to hire this applicant?')">
                                                Hire
                                            </a>
                                        <?php else: ?>
                                            <span class="text-gray-500">Hired</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500 italic">No applicants found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
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