<?php
session_start();
require '../config/db.php';
require_once '../config/auth_middleware.php';

// Only admin allowed
requireRole(['admin']);

$message = "";

// Load current settings
$stmt = $pdo->prepare("SELECT * FROM settings WHERE id = 1");
$stmt->execute();
$settings = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tax_rate = floatval($_POST['tax_rate']);
    $default_bonus = floatval($_POST['default_bonus']);

    // Validate inputs
    if ($tax_rate < 0 || $tax_rate > 100) {
        $message = "Tax rate must be between 0 and 100.";
    } else {
        // Update or insert settings
        if ($settings) {
            $stmt = $pdo->prepare("UPDATE settings SET tax_rate = ?, default_bonus = ? WHERE id = 1");
            $stmt->execute([$tax_rate, $default_bonus]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO settings (id, tax_rate, default_bonus) VALUES (1, ?, ?)");
            $stmt->execute([$tax_rate, $default_bonus]);
        }
        $message = "Settings updated.";
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Settings - SpeedNet Payroll</title>
    <link rel="stylesheet" href="../css/settings.css">
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
<h2>Payroll Settings</h2>

<?php if ($message): ?>
    <p class="message"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<form method="POST" action="">
    <label for="tax_rate">Tax Rate (%)</label>
    <input type="number" id="tax_rate" name="tax_rate" step="0.01" min="0" max="100" required value="<?= htmlspecialchars($settings['tax_rate'] ?? '') ?>">

    <label for="default_bonus">Default Bonus Amount</label>
    <input type="number" id="default_bonus" name="default_bonus" step="0.01" min="0" required value="<?= htmlspecialchars($settings['default_bonus'] ?? '') ?>">

    <button type="submit">Save Settings</button>
</form>
</body>
</html>