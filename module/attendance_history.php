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