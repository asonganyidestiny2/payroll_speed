<?php
session_start();
require '../config/db.php';

// Check user role - allow only secretary or hrm
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['secretary', 'hrm'])) {
    header("Location: ../login.php");
    exit();
}

$message = "";

// Get today's date
$today = date('Y-m-d');

// --- IMPROVEMENT: FETCH EXISTING ATTENDANCE RECORDS ONCE ---
// Fetch existing attendance records for today into a lookup array
$existing_attendance = [];
$stmt_existing = $pdo->prepare("SELECT employee_id, status, hours_worked FROM attendance WHERE date = ?");
$stmt_existing->execute([$today]);
while ($row = $stmt_existing->fetch(PDO::FETCH_ASSOC)) {
    $existing_attendance[$row['employee_id']] = [
        'status' => $row['status'],
        'hours_worked' => $row['hours_worked']
    ];
}

// Fetch all active employees
$stmt_employees = $pdo->prepare("SELECT * FROM employees WHERE status = 'active' ORDER BY full_name ASC");
$stmt_employees->execute();
$employees = $stmt_employees->fetchAll(PDO::FETCH_ASSOC);

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attendance_status'])) {
    $attendanceStatusData = $_POST['attendance_status']; // array: employee_id => status
    $attendanceHoursData = $_POST['hours_worked']; // array: employee_id => hours
    $errors = [];
    $records_to_insert = [];
    $records_to_update = [];

    // Begin a transaction to ensure all or none of the updates are applied
    $pdo->beginTransaction();

    try {
        foreach ($attendanceStatusData as $empId => $status) {
            // --- IMPROVEMENT: SERVER-SIDE VALIDATION ---
            $hours_worked = $attendanceHoursData[$empId] ?? 0;
            if ($status === 'present') {
                // Validate hours_worked for 'present' status
                $hours_worked = filter_var($hours_worked, FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0, 'max_range' => 24]]);
                if ($hours_worked === false) {
                    $errors[] = "Invalid hours worked for employee ID: " . $empId;
                    continue; // Skip this employee and continue the loop
                }
            } else {
                // If not present, hours worked must be 0
                $hours_worked = 0;
            }

            // --- IMPROVEMENT: USE ARRAY LOOKUP INSTEAD OF NEW QUERY ---
            if (isset($existing_attendance[$empId])) {
                // Prepare a record for batch update
                $records_to_update[] = [$status, $hours_worked, $empId, $today];
            } else {
                // Prepare a record for batch insert
                $records_to_insert[] = [$empId, $today, $status, $hours_worked];
            }
        }

        // --- IMPROVEMENT: BATCH INSERTS/UPDATES FOR BETTER PERFORMANCE ---
        if (!empty($records_to_update)) {
            $update = $pdo->prepare("UPDATE attendance SET status = ?, hours_worked = ? WHERE employee_id = ? AND date = ?");
            foreach ($records_to_update as $record) {
                $update->execute($record);
            }
        }

        if (!empty($records_to_insert)) {
            $insert = $pdo->prepare("INSERT INTO attendance (employee_id, date, status, hours_worked) VALUES (?, ?, ?, ?)");
            foreach ($records_to_insert as $record) {
                $insert->execute($record);
            }
        }

        $pdo->commit();
        $message = "Attendance saved for " . date('F j, Y', strtotime($today));
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "An error occurred while saving attendance: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance - SpeedNet Payroll</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/modules/attendance.css">
</head>

<body class="font-inter">
   <header class="main-header flex justify-between items-center p-4 bg-white shadow" style="background-color: #3b82f6;">
        <div class="logo"><img src="../image1_edited.png" alt="Company Logo" class="w-32"></div>
        <div class="dropdown">
            <button id="dropdown-btn" class="dropdown-btn p-2">
                <svg class="hamburger-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z" />
                </svg>
            </button>
                <?php include '../module/components/nav.php'; ?>
        </div>
    </header>

    <main class="container mx-auto p-8 pt-12">
        <h2 class="text-3xl font-bold text-gray-900 mb-8">Mark Attendance for <?= date('F j, Y', strtotime($today)) ?>
        </h2>

        <?php if ($message): ?>
            <div class="alert-success">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Attendance Form -->
        <div class="card">
            <form method="POST" action="">
                <div class="overflow-x-auto">
                    <table class="striped-table">
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
                                // Use the pre-fetched array for lookup, not a new query
                                $record = $existing_attendance[$emp['id']] ?? null;
                                $currentStatus = $record ? $record['status'] : 'present';
                                $currentHours = $record ? $record['hours_worked'] : 8; // Default to 8 hours for 'present'
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($emp['full_name']) ?></td>
                                    <td>
                                        <div class="status-options">
                                            <label>
                                                <input type="radio" name="attendance_status[<?= $emp['id'] ?>]"
                                                    value="present"
                                                    onchange="toggleHoursInput(<?= $emp['id'] ?>, this.value)"
                                                    <?= $currentStatus === 'present' ? 'checked' : '' ?>> Present
                                            </label>
                                            <label>
                                                <input type="radio" name="attendance_status[<?= $emp['id'] ?>]"
                                                    value="absent"
                                                    onchange="toggleHoursInput(<?= $emp['id'] ?>, this.value)"
                                                    <?= $currentStatus === 'absent' ? 'checked' : '' ?>> Absent
                                            </label>
                                            <label>
                                                <input type="radio" name="attendance_status[<?= $emp['id'] ?>]"
                                                    value="leave" onchange="toggleHoursInput(<?= $emp['id'] ?>, this.value)"
                                                    <?= $currentStatus === 'leave' ? 'checked' : '' ?>> Leave
                                            </label>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="number" step="0.5" min="0" max="24"
                                            name="hours_worked[<?= $emp['id'] ?>]" id="hours_<?= $emp['id'] ?>"
                                            class="hours-input <?= $currentStatus !== 'present' ? 'hidden' : '' ?>"
                                            value="<?= htmlspecialchars($currentHours) ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-8">
                    <button type="submit" class="btn-primary">Save Attendance</button>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Function to show/hide hours input based on attendance status
        function toggleHoursInput(employeeId, status) {
            const hoursInput = document.getElementById('hours_' + employeeId);
            if (status === 'present') {
                hoursInput.classList.remove('hidden');
            } else {
                hoursInput.classList.add('hidden');
            }
        }
        
        // Dropdown Navigation Toggle
        document.addEventListener("DOMContentLoaded", () => {
            const dropdownBtn = document.getElementById('dropdown-btn');
            const dropdownMenu = document.getElementById('dropdown-menu');
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
</body>

</html>