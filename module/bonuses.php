<?php
session_start();
require '../config/db.php';

// Only HRM or Admin can access
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['hrm', 'admin'])) {
    header("Location: ../login.php");
    exit();
}

$message = "";

// Fetch all active employees for dropdown
$stmt = $pdo->prepare("SELECT id, full_name FROM employees WHERE status = 'active' ORDER BY full_name");
$stmt->execute();
$employees = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_bonus'])) {
    $employee_id = $_POST['employee_id'] ?? null;
    $bonus_type = trim($_POST['bonus_type'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $bonus_date = $_POST['bonus_date'] ?? date('Y-m-d');

    if (!$employee_id || !$bonus_type || $amount <= 0) {
        $message = "Please fill all fields correctly.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO bonuses (employee_id, bonus_type, amount, bonus_date) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$employee_id, $bonus_type, $amount, $bonus_date])) {
            $message = "Bonus added successfully.";
        } else {
            $message = "Failed to add bonus.";
        }
    }
}

// Fetch bonuses list (latest first)
$stmt = $pdo->prepare("
    SELECT b.*, e.full_name FROM bonuses b
    JOIN employees e ON b.employee_id = e.id
    ORDER BY b.bonus_date DESC, b.id DESC
");
$stmt->execute();
$bonuses = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html>

<head>
    <title>Bonuses - SpeedNet Payroll</title>
    <link rel="stylesheet" href="../css/bonus.css">

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

    <h2>Manage Bonuses</h2>

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

        <label for="bonus_type">Bonus Type</label>
        <input type="text" name="bonus_type" id="bonus_type" placeholder="e.g., Performance, Holiday" required>

        <label for="amount">Amount</label>
        <input type="number" step="0.01" min="0" name="amount" id="amount" placeholder="0.00" required>

        <label for="bonus_date">Bonus Date</label>
        <input type="date" name="bonus_date" id="bonus_date" value="<?= date('Y-m-d') ?>" required>

        <button type="submit" name="add_bonus">Add Bonus</button>
    </form>

    <h3>Recent Bonuses</h3>

    <?php if (count($bonuses) === 0): ?>
        <p class="no-records">No bonuses recorded yet.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Bonus Type</th>
                    <th>Amount</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bonuses as $bonus): ?>
                    <tr>
                        <td><?= htmlspecialchars($bonus['full_name']) ?></td>
                        <td><?= htmlspecialchars($bonus['bonus_type']) ?></td>
                        <td>$<?= number_format($bonus['amount'], 2) ?></td>
                        <td><?= htmlspecialchars($bonus['bonus_date']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</body>

</html>