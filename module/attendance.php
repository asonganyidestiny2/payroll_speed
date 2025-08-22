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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attendance_status'])) {
    $attendanceStatusData = $_POST['attendance_status']; // array: employee_id => status
    $attendanceHoursData = $_POST['hours_worked']; // array: employee_id => hours

    foreach ($attendanceStatusData as $empId => $status) {
        $hours_worked = $attendanceHoursData[$empId] ?? 0;

        // Set hours to 0 if status is not 'present'
        if ($status !== 'present') {
            $hours_worked = 0;
        }

        // Check if attendance already recorded for employee today
        $check = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? AND date = ?");
        $check->execute([$empId, $today]);

        if ($check->rowCount() > 0) {
            // Update existing record with new status and hours
            $update = $pdo->prepare("UPDATE attendance SET status = ?, hours_worked = ? WHERE employee_id = ? AND date = ?");
            $update->execute([$status, $hours_worked, $empId, $today]);
        } else {
            // Insert new record
            $insert = $pdo->prepare("INSERT INTO attendance (employee_id, date, status, hours_worked) VALUES (?, ?, ?, ?)");
            $insert->execute([$empId, $today, $status, $hours_worked]);
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
    <script>
        // Function to show/hide hours input based on attendance status
        function toggleHoursInput(employeeId, status) {
            const hoursInput = document.getElementById('hours_' + employeeId);
            if (status === 'present') {
                hoursInput.style.display = 'block';
            } else {
                hoursInput.style.display = 'none';
            }
        }

        // Set initial state of hours input fields
        document.addEventListener('DOMContentLoaded', (event) => {
            const radioButtons = document.querySelectorAll('input[type="radio"]');
            radioButtons.forEach(radio => {
                const empId = radio.name.split('[')[1].replace(']', '');
                if (radio.checked) {
                    toggleHoursInput(empId, radio.value);
                }
                radio.addEventListener('change', () => {
                    toggleHoursInput(empId, radio.value);
                });
            });
        });
    </script>
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
                    <th>Hours Worked</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employees as $emp): ?>
                    <?php
                    // Check if attendance already recorded for employee today
                    $stmt2 = $pdo->prepare("SELECT status, hours_worked FROM attendance WHERE employee_id = ? AND date = ?");
                    $stmt2->execute([$emp['id'], $today]);
                    $record = $stmt2->fetch();
                    $currentStatus = $record ? $record['status'] : 'present';
                    $currentHours = $record ? $record['hours_worked'] : 8; // Default to 8 hours for 'present'
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($emp['full_name']) ?></td>
                        <td>
                            <div class="status-options">
                                <label>
                                    <input type="radio" name="attendance_status[<?= $emp['id'] ?>]" value="present"
                                           <?= $currentStatus === 'present' ? 'checked' : '' ?>
                                           onchange="toggleHoursInput(<?= $emp['id'] ?>, this.value)"> Present
                                </label>
                                <label>
                                    <input type="radio" name="attendance_status[<?= $emp['id'] ?>]" value="absent"
                                           <?= $currentStatus === 'absent' ? 'checked' : '' ?>
                                           onchange="toggleHoursInput(<?= $emp['id'] ?>, this.value)"> Absent
                                </label>
                                <label>
                                    <input type="radio" name="attendance_status[<?= $emp['id'] ?>]" value="leave"
                                           <?= $currentStatus === 'leave' ? 'checked' : '' ?>
                                           onchange="toggleHoursInput(<?= $emp['id'] ?>, this.value)"> Leave
                                </label>
                            </div>
                        </td>
                        <td>
                            <input type="number" step="0.5" min="0" max="24"
                                   name="hours_worked[<?= $emp['id'] ?>]" id="hours_<?= $emp['id'] ?>"
                                   value="<?= htmlspecialchars($currentHours) ?>"
                                   style="<?= $currentStatus === 'present' ? 'display: block;' : 'display: none;' ?>">
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button type="submit">Save Attendance</button>
    </form>
</body>
</html>