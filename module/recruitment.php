<?php
require_once __DIR__ . '/../config/auth_middleware.php';
requireRole(['admin', 'hrm']); // Only Admin and HRM can manage recruitment
require_once __DIR__ . '/../config/db.php';



// Handle new applicant submission
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_applicant'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $position_applied = trim($_POST['position_applied']);

    if ($full_name && $position_applied) {
        $stmt = $pdo->prepare("INSERT INTO recruitments (full_name, email, phone, position_applied) VALUES (?, ?, ?, ?)");
        $stmt->execute([$full_name, $email, $phone, $position_applied]);
        $message = "Applicant added successfully!";
    } else {
        $error = "Full Name and Position are required.";
    }
}

// Handle hiring an applicant
if (isset($_GET['hire_id'])) {
    $hire_id = (int)$_GET['hire_id'];
    $stmt = $pdo->prepare("SELECT * FROM recruitments WHERE id = ?");
    $stmt->execute([$hire_id]);
    $applicant = $stmt->fetch();
    
    if ($applicant) {
        // Insert into employees table
        $stmt2 = $pdo->prepare("INSERT INTO employees (full_name, email, phone, position, status) VALUES (?, ?, ?, ?, 'active')");
        $stmt2->execute([$applicant['full_name'], $applicant['email'], $applicant['phone'], $applicant['position_applied']]);
        // Update recruitment status
        $stmt3 = $pdo->prepare("UPDATE recruitments SET status='Hired' WHERE id=?");
        $stmt3->execute([$hire_id]);
        $message = "Applicant hired successfully!";
    }
}

// Fetch all applicants
$applicants = $pdo->query(query: "SELECT * FROM recruitments ORDER BY id")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Recruitment - SpeedNet Payroll</title>
<link rel="stylesheet" href="../css/recruit.css">
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
    <h1>Recruitment Management</h1>

    <?php if ($message) echo "<p class='success'>$message</p>"; ?>
    <?php if ($error) echo "<p class='error'>$error</p>"; ?>

    <form method="POST">
        <input type="text" name="full_name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email">
        <input type="text" name="phone" placeholder="Phone">
        <input type="text" name="position_applied" placeholder="Position Applied" required>
        <button type="submit" name="add_applicant">Add Applicant</button>
    </form>

    <h2>Applicants</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Position Applied</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php foreach ($applicants as $applicant): ?>
        <tr>
            <td><?php echo $applicant['id']; ?></td>
            <td><?php echo htmlspecialchars($applicant['full_name']); ?></td>
            <td><?php echo htmlspecialchars($applicant['email']); ?></td>
            <td><?php echo htmlspecialchars($applicant['phone']); ?></td>
            <td><?php echo htmlspecialchars($applicant['position_applied']); ?></td>
            <td><?php echo $applicant['status']; ?></td>
            <td>
                <?php if ($applicant['status'] !== 'Hired'): ?>
                    <a href="?hire_id=<?php echo $applicant['id']; ?>"><button>Hire</button></a>
                <?php else: ?>
                    Hired
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>
