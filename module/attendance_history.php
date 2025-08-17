<?php
session_start();
require '../config/db.php';

// Check user role - allow only secretary or hrm
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['secretary', 'hrm'])) {
    header("Location: ../login.php");
    exit();
}

// Get employees list for filter dropdown
$stmt = $pdo->prepare("SELECT id, full_name FROM employees WHERE status = 'active' ORDER BY full_name ASC");
$stmt->execute();
$employees = $stmt->fetchAll();

// Initialize filter variables
$employee_id = $_GET['employee_id'] ?? 'all';
$year_month = $_GET['year_month'] ?? date('Y-m');

$attendanceRecords = [];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $employee_id !== null) {
    $start_date = $year_month . '-01';
    $end_date = date("Y-m-t", strtotime($start_date)); // last day of month

    if ($employee_id === 'all') {
        // Get all attendance in that month
        $stmt = $pdo->prepare("
            SELECT a.date, e.full_name, a.status
            FROM attendance a
            JOIN employees e ON a.employee_id = e.id
            WHERE a.date BETWEEN ? AND ?
            ORDER BY a.date, e.full_name
        ");
        $stmt->execute([$start_date, $end_date]);
    } else {
        // Get attendance for selected employee
        $stmt = $pdo->prepare("
            SELECT a.date, e.full_name, a.status
            FROM attendance a
            JOIN employees e ON a.employee_id = e.id
            WHERE a.employee_id = ? AND a.date BETWEEN ? AND ?
            ORDER BY a.date
        ");
        $stmt->execute([$employee_id, $start_date, $end_date]);
    }
    $attendanceRecords = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Attendance History - SpeedNet Payroll</title>
    <link rel="stylesheet" href="../css/ath.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f7f9fc;
            padding: 20px;
            max-width: 900px;
            margin: auto;
        }
        h2 {
            text-align: center;
            color: #660066;
            margin-bottom: 20px;
        }
        form {
            margin-bottom: 20px;
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        select, input[type="month"] {
            padding: 8px;
            border-radius: 6px;
            border: 1px solid #ccc;
            min-width: 180px;
        }
        button {
            padding: 10px 25px;
            background: #660066;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #4b004b;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px 15px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #660066;
            color: white;
        }
        tbody tr:hover {
            background-color: #f0f0f0;
        }
        .no-records {
            text-align: center;
            padding: 30px 0;
            color: #666;
        }
    </style>
</head>
<body>
    <header class="main-header">
    <div class="logo"><img src="image1_edited.png" alt=""></div>
    <nav>
        <a href="../index.php">Home</a>
        <a href="#features">Features</a>
        <a href="../login.php" class="btn-login">BACK</a>
    </nav>
</header>
<h2>Attendance History</h2>

<form method="GET" action="">
    <select name="employee_id" required>
        <option value="all" <?= $employee_id === 'all' ? 'selected' : '' ?>>All Employees</option>
        <?php foreach ($employees as $emp): ?>
            <option value="<?= $emp['id'] ?>" <?= $employee_id == $emp['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($emp['full_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <input type="month" name="year_month" value="<?= htmlspecialchars($year_month) ?>" required>

    <button type="submit">Filter</button>
</form>

<?php if (!empty($attendanceRecords)): ?>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Employee</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($attendanceRecords as $record): ?>
                <tr>
                    <td><?= date('F j, Y', strtotime($record['date'])) ?></td>
                    <td><?= htmlspecialchars($record['full_name']) ?></td>
                    <td><?= ucfirst($record['status']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p class="no-records">No attendance records found for the selected filter.</p>
<?php endif; ?>

</body>
</html>
