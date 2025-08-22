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

// Fetch all leave requests
$leaveRequests = $pdo->query("SELECT * FROM leave_requests ORDER BY id DESC")->fetchAll();

// Fetch all employees for datalist
$employees = $pdo->query("SELECT full_name FROM employees ORDER BY full_name ASC")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Leave Management - SpeedNet Payroll</title>
    <link rel="stylesheet" href="../css/leav.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #eef2f7;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 90%;
            max-width: 1100px;
            margin: 30px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: #333;
        }

        form {
            margin-bottom: 20px;
            padding: 15px;
            background: #fafafa;
            border-radius: 6px;
        }

        input,
        select,
        button {
            padding: 10px;
            margin: 5px 0;
            width: 100%;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button {
            background: #2196F3;
            color: white;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background: #1976D2;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }

        th,
        td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
        }

        th {
            background: #2196F3;
            color: white;
        }

        .success {
            color: green;
            margin-bottom: 10px;
        }

        .error {
            color: red;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <header class="main-header">
        <div class="logo"><img src="../image1_edited.png" alt=""></div>
        <?php
        include '../module/components/nav.php';
        ?>
        <nav>
            <a href="../login.php" class="btn-login">Back</a>
        </nav>
    </header>

    <div class="container">
        <h1>Leave Management</h1>

        <?php if ($message)
            echo "<p class='success'>$message</p>"; ?>
        <?php if ($error)
            echo "<p class='error'>$error</p>"; ?>

        <form method="POST">
            <input list="employees_list" name="employee_name" placeholder="Employee Name" required>
            <datalist id="employees_list">
                <?php foreach ($employees as $emp): ?>
                    <option value="<?php echo htmlspecialchars($emp); ?>"></option>
                <?php endforeach; ?>
            </datalist>

            <select name="leave_type" required>
                <option value="">Select Leave Type</option>
                <option value="Sick Leave">Sick Leave</option>
                <option value="Casual Leave">Casual Leave</option>
                <option value="Annual Leave">Annual Leave</option>
            </select>

            <label>Start Date</label>
            <input type="date" name="start_date" required>

            <label>End Date</label>
            <input type="date" name="end_date" required>

            <button type="submit">Submit Leave Request</button>
        </form>

        <h2>Leave Requests</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Employee Name</th>
                <th>Leave Type</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Status</th>
            </tr>
            <?php foreach ($leaveRequests as $leave): ?>
                <tr>
                    <td><?php echo $leave['id']; ?></td>
                    <td><?php echo htmlspecialchars($leave['employee_name']); ?></td>
                    <td><?php echo htmlspecialchars($leave['leave_type']); ?></td>
                    <td><?php echo $leave['start_date']; ?></td>
                    <td><?php echo $leave['end_date']; ?></td>
                    <td><?php echo $leave['status']; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

</body>

</html>