<?php
session_start();
require '../config/db.php';

// Only HRM or Admin can access
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['hrm', 'admin'])) {
    header("Location: ../login.php");
    exit();
}

$message = "";

// Fetch active employees for dropdown
$stmt = $pdo->prepare("SELECT id, full_name FROM employees WHERE status = 'active' ORDER BY full_name");
$stmt->execute();
$employees = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_deduction'])) {
    $employee_id = $_POST['employee_id'] ?? null;
    $type = trim($_POST['type'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $deduction_date = $_POST['deduction_date'] ?? date('Y-m-d');

    if (!$employee_id || !$type || $amount <= 0) {
        $message = "Please fill all fields correctly.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO deductions (employee_id, type, amount, deduction_date) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$employee_id, $type, $amount, $deduction_date])) {
            $message = "Deduction added successfully.";
        } else {
            $message = "Failed to add deduction.";
        }
    }
}

// Fetch deductions list (latest first)
$stmt = $pdo->prepare("
    SELECT d.*, e.full_name FROM deductions d
    JOIN employees e ON d.employee_id = e.id
    ORDER BY d.deduction_date DESC, d.id DESC
");
$stmt->execute();
$deductions = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html>

<head>
    <title>Deductions - SpeedNet Payroll</title>
    <link rel="stylesheet" href="../css/deduct.css">
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

    <h2>Manage Deductions</h2>

    <?php if ($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="employee_id">Select Employee</label>
        <select name="employee_id" id="employee_id" required>
            <option value="">-- Select Employee --</option>
            <?php foreach ($employees as $emp): ?>
                <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['full_name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="type">Deduction Type</label>
        <input type="text" name="type" id="type" placeholder="e.g., Tax, Loan" required>

        <label for="amount">Amount</label>
        <input type="number" step="0.01" min="0" name="amount" id="amount" placeholder="0.00" required>

        <label for="deduction_date">Deduction Date</label>
        <input type="date" name="deduction_date" id="deduction_date" value="<?= date('Y-m-d') ?>" required>

        <button type="submit" name="add_deduction">Add Deduction</button>
    </form>

    <h3>Recent Deductions</h3>

    <?php if (count($deductions) === 0): ?>
        <p class="no-records">No deductions recorded yet.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Deduction Type</th>
                    <th>Amount</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($deductions as $deduction): ?>
                    <tr>
                        <td><?= htmlspecialchars($deduction['full_name']) ?></td>
                        <td><?= htmlspecialchars($deduction['type']) ?></td>
                        <td>$<?= number_format($deduction['amount'], 2) ?></td>
                        <td><?= htmlspecialchars($deduction['deduction_date']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</body>

</html>