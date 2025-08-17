<?php
session_start();
require '../config/db.php';

// Check login (adjust as needed)
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

// Fetch all employees
$employees = $pdo->query(query: "SELECT * FROM employees ORDER BY id")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Employee List - SpeedNet Payroll</title>
    <link rel="stylesheet" href="../css/view.css">
</head>
<body>
    <header class="main-header">
    <div class="logo"><img src="../image1_edited.png" alt=""></div>
    <nav>
        <a href="../index.php">Home</a>
        <a href="#features">Features</a>
        <a href="../login.php" class="btn-login">Back</a>
    </nav>
</header>
    <h2>Employees</h2>

    <?php if (isset($_GET['updated'])): ?>
        <div class="message">Employee updated successfully!</div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
        <div class="message">Employee deleted successfully!</div>
    <?php endif; ?>

    <?php if (count($employees) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Position</th>
                    <th>Salary Type</th>
                    <th>Base Salary</th>
                    <th>Bank Account</th>
                    <th>Date Hired</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employees as $emp): ?>
                <tr>
                    <td><?= htmlspecialchars($emp['id']) ?></td>
                    <td><?= htmlspecialchars($emp['full_name']) ?></td>
                    <td><?= htmlspecialchars($emp['email']) ?></td>
                    <td><?= htmlspecialchars($emp['phone']) ?></td>
                    <td><?= htmlspecialchars($emp['position']) ?></td>
                    <td><?= htmlspecialchars(ucfirst($emp['salary_type'])) ?></td>
                    <td><?= htmlspecialchars(number_format($emp['base_salary'], 2)) ?></td>
                    <td><?= htmlspecialchars($emp['bank_account']) ?></td>
                    <td><?= htmlspecialchars($emp['date_hired']) ?></td>
                    <td><?= htmlspecialchars(ucfirst($emp['status'])) ?></td>
                    <td class="actions">
                        <a href="edit_employee.php?id=<?= $emp['id'] ?>">Edit</a>
                        |
                        <a href="delete_employee.php?delete=<?= $emp['id'] ?>" onclick="return confirm(' Do you want to delete this employee?')" class="btn btn-del">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="no-data">No employees found.</p>
    <?php endif; ?>
</body>
</html>
