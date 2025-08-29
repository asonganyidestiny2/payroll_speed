<?php
require_once __DIR__ . '/../config/auth_middleware.php';
requireRole(['admin', 'hrm', 'secretary']); // Allowed roles
require_once __DIR__ . '/../config/db.php';

// Create leave_requests table if not exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS leave_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_name VARCHAR(100) NOT NULL,
        leave_type VARCHAR(50) NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending'
    )
");

// Handle form submission for leave request
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_name = trim($_POST['employee_name']);
    $leave_type = trim($_POST['leave_type']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    if ($employee_name && $leave_type && $start_date && $end_date) {
        $stmt = $pdo->prepare("INSERT INTO leave_requests (employee_name, leave_type, start_date, end_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$employee_name, $leave_type, $start_date, $end_date]);
        $message = "Leave request submitted successfully!";
    } else {
        $error = "All fields are required.";
    }
}

// Handle search functionality
$search_query = $_GET['search'] ?? '';
$where_clause = '';
$params = [];

if (!empty($search_query)) {
    $where_clause = "WHERE employee_name LIKE ? OR leave_type LIKE ? OR status LIKE ?";
    $search_param = "%$search_query%";
    $params = [$search_param, $search_param, $search_param];
}

// Fetch all leave requests with optional search
$sql = "SELECT * FROM leave_requests $where_clause ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leaveRequests = $stmt->fetchAll();

// Fetch all employees for datalist
$employees = $pdo->query("SELECT full_name FROM employees ORDER BY full_name ASC")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Management - SpeedNet Payroll</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/modules/leaves.css">
    <style>
        /* Dropdown styles */
        .dropdown {
            position: relative;
            display: inline-block;
        }
        
        #dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background-color: white;
            min-width: 200px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            z-index: 1000;
            border-radius: 0.375rem;
            margin-top: 0.5rem;
            padding: 0.5rem 0;
        }
        
        .dropdown.open #dropdown-menu {
            display: block;
        }
        
        .dropdown-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.375rem;
        }
        
        .dropdown-btn:hover {
            background-color: #f3f4f6;
        }
        
        .hamburger-icon {
            width: 24px;
            height: 24px;
            fill: currentColor;
        }
        
        /* Form and table styles */
        .main-header {
            display: flex;
            justify-content: between;
            align-items: center;
            padding: 1rem;
            background-color: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .input-group {
            margin-bottom: 1rem;
        }
        
        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }
        
        .input-group input, .input-group select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
        }
        
        .btn-primary {
            background-color: #4f46e5;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            background-color: #4338ca;
        }
        
        .search-container {
            position: relative;
            flex-grow: 1;
        }
        
        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }
        
        .search-input {
            padding-left: 2.5rem;
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
        }
        
        .btn-secondary {
            background-color: #6b7280;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
        }
        
        .btn-secondary:hover {
            background-color: #4b5563;
        }
        
        .btn-back {
            background-color: #9ca3af;
            color: white;
        }
        
        .alert-success {
            background-color: #dcfce7;
            color: #166534;
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }
        
        .alert-error {
            background-color: #fee2e2;
            color: #b91c1c;
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }
        
        .striped-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .striped-table th {
            background-color: #f9fafb;
            padding: 0.75rem;
            text-align: left;
            font-weight: 500;
            color: #374151;
        }
        
        .striped-table td {
            padding: 0.75rem;
            border-top: 1px solid #e5e7eb;
        }
        
        .striped-table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .status-approved {
            background-color: #dcfce7;
            color: #166534;
        }
        
        .status-rejected {
            background-color: #fee2e2;
            color: #b91c1c;
        }
    </style>
</head>

<body class="bg-[#f3f4f6] font-inter">
    <header class="main-header">
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
        <h2 class="text-3xl font-bold text-gray-900 mb-8">Leave Management</h2>

        <?php if ($message): ?>
            <div class="alert-success">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Leave Request Form -->
        <div class="card mb-8">
            <h3 class="text-xl font-semibold text-gray-900 mb-4">Submit Leave Request</h3>
            <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="input-group">
                    <label for="employee_name">Employee Name</label>
                    <input list="employees_list" name="employee_name" placeholder="Enter employee name" required class="p-2 border border-gray-300 rounded-md">
                    <datalist id="employees_list">
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?= htmlspecialchars($emp) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <div class="input-group">
                    <label for="leave_type">Leave Type</label>
                    <select name="leave_type" required class="p-2 border border-gray-300 rounded-md">
                        <option value="">Select Leave Type</option>
                        <option value="Sick Leave">Sick Leave</option>
                        <option value="Casual Leave">Casual Leave</option>
                        <option value="Annual Leave">Annual Leave</option>
                    </select>
                </div>

                <div class="input-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" name="start_date" required class="p-2 border border-gray-300 rounded-md">
                </div>

                <div class="input-group">
                    <label for="end_date">End Date</label>
                    <input type="date" name="end_date" required class="p-2 border border-gray-300 rounded-md">
                </div>

                <div class="md:col-span-2">
                    <button type="submit" class="btn-primary">Submit Leave Request</button>
                </div>
            </form>
        </div>

        <!-- Search Bar -->
        <div class="card mb-8">
            <h3 class="text-xl font-semibold text-gray-900 mb-4">Search Leave Requests</h3>
            <form method="GET" action="" class="flex items-center gap-4">
                <div class="search-container">
                    <div class="search-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" name="search" id="search" class="search-input"
                        placeholder="Search by employee name, leave type, or status"
                        value="<?= htmlspecialchars($search_query) ?>">
                </div>
                <button type="submit" class="btn-secondary whitespace-nowrap">Search</button>
                <?php if (!empty($search_query)): ?>
                    <a href="leave_management.php" class="btn-back px-4 py-2 rounded-lg text-sm">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Leave Requests Table -->
        <div class="card">
            <h3 class="text-xl font-semibold text-gray-900 mb-4">Leave Requests</h3>
            <?php if (count($leaveRequests) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full striped-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Employee Name</th>
                                <th>Leave Type</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leaveRequests as $leave): ?>
                                <tr>
                                    <td><?= $leave['id'] ?></td>
                                    <td><?= htmlspecialchars($leave['employee_name']) ?></td>
                                    <td><?= htmlspecialchars($leave['leave_type']) ?></td>
                                    <td><?= date('M j, Y', strtotime($leave['start_date'])) ?></td>
                                    <td><?= date('M j, Y', strtotime($leave['end_date'])) ?></td>
                                    <td>
                                        <span class="status-badge status-<?= strtolower($leave['status']) ?>">
                                            <?= $leave['status'] ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-center py-8">
                    <?php if (!empty($search_query)): ?>
                        No leave requests found matching "<?= htmlspecialchars($search_query) ?>"
                    <?php else: ?>
                        No leave requests found.
                    <?php endif; ?>
                </p>
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