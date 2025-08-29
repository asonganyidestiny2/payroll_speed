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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - SpeedNet Payroll</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/modules/settings.css">
</head>
<body class="font-inter">
    <header class="main-header">
        <div class="logo"><img src="../image1_edited.png" alt="Company Logo" class="w-32"></div>
        <?php
        include '../module/components/nav.php';
        ?>
        <nav>
            <a href="../login.php" class="btn-back px-4 py-2 rounded-lg text-sm transition-colors duration-200 ease-in-out">Back</a>
        </nav>
    </header>

    <main class="container mx-auto p-8 pt-12">
        <h2 class="text-3xl font-bold text-gray-900 mb-8 text-center">Payroll Settings</h2>

        <?php if ($message): ?>
            <div class="<?= strpos($message, 'error') !== false || strpos($message, 'must be') !== false ? 'alert-danger' : 'alert-success' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" action="" class="space-y-6">
                <div>
                    <label for="tax_rate" class="block text-sm font-medium text-gray-700 mb-1">Tax Rate (%)</label>
                    <input type="number" id="tax_rate" name="tax_rate" step="0.01" min="0" max="100" required value="<?= htmlspecialchars($settings['tax_rate'] ?? '') ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 px-3 py-2">
                </div>

                <div>
                    <label for="default_bonus" class="block text-sm font-medium text-gray-700 mb-1">Default Bonus Amount</label>
                    <input type="number" id="default_bonus" name="default_bonus" step="0.01" min="0" required value="<?= htmlspecialchars($settings['default_bonus'] ?? '') ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 px-3 py-2">
                </div>

                <button type="submit" class="btn-primary">Save Settings</button>
            </form>
        </div>
    </main>
</body>
</html>
