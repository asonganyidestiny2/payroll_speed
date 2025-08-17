<?php
session_start();
require '../config/db.php';

// Check user role - allow only secretary or hrm
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['secretary', 'hrm'])) {
    header("Location: ../login.php");
    exit();
}

$message = "";

// Get today’s date
$today = date('Y-m-d');

// Fetch active employees
$stmt = $pdo->prepare("SELECT * FROM employees WHERE status = 'active' ORDER BY full_name ASC");
$stmt->execute();
$employees = $stmt->fetchAll();

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attendance'])) {
    $attendanceData = $_POST['attendance']; // array: employee_id => status

    foreach ($attendanceData as $empId => $status) {
        // Check if attendance already recorded for employee today
        $check = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? AND date = ?");
        $check->execute([$empId, $today]);
        if ($check->rowCount() > 0) {
            // Update existing record
            $update = $pdo->prepare("UPDATE attendance SET status = ? WHERE employee_id = ? AND date = ?");
            $update->execute([$status, $empId, $today]);
        } else {
            // Insert new record
            $insert = $pdo->prepare("INSERT INTO attendance (employee_id, date, status) VALUES (?, ?, ?)");
            $insert->execute([$empId, $today, $status]);
        }
    }
    $message = "Attendance saved for " . date('F j, Y', strtotime($today));
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Mark Attendance - SpeedNet Payroll</title>
    <link rel="stylesheet" href="../css/att.css">
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

<h2>Mark Attendance for <?= date('F j, Y', strtotime($today)) ?></h2>

<?php if ($message): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<form method="POST" action="">
    <table>
        <thead>
            <tr>
                <th>Employee</th>
                <th>Attendance Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($employees as $emp): ?>
                <?php
                // Check if attendance already recorded for employee today
                $stmt2 = $pdo->prepare("SELECT status FROM attendance WHERE employee_id = ? AND date = ?");
                $stmt2->execute([$emp['id'], $today]);
                $record = $stmt2->fetch();
                $currentStatus = $record ? $record['status'] : 'present';
                ?>
                <tr>
                    <td><?= htmlspecialchars($emp['full_name']) ?></td>
                    <td>
                        <select name="attendance[<?= $emp['id'] ?>]">
                            <option value="present" <?= $currentStatus === 'present' ? 'selected' : '' ?>>Present</option>
                            <option value="absent" <?= $currentStatus === 'absent' ? 'selected' : '' ?>>Absent</option>
                            <option value="leave" <?= $currentStatus === 'leave' ? 'selected' : '' ?>>Leave</option>
                        </select>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <button type="submit">Save Attendance</button>
</form>

</body>
</html>
