<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SpeedNet Payroll System</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>

<header class="main-header">
    <div class="logo"><img src="image1_edited.png" alt=""></div>
    <nav>
        <a href="index.php">Home</a>
        <a href="#features">Features</a>
        <a href="login.php" class="btn-login">LOGIN</a>
    </nav>
</header>

<section class="hero">
    <div class="hero-text">
        <h1>Welcome to SpeedNet Payroll System</h1>
        <p>Efficient, secure, and reliable payroll management for your business.</p>
        <a href="login.php" class="btn-primary">Go to dashboard</a>
    </div>
    <img src="Screenshot_20250806-191947_edited.png" alt="">
</section>
<div class="img">
    <img src="img3.png" alt="">
    <img src="img4.png" alt="">
    <img src="img.5.png" alt="">
</div>

<section id="features" class="features">
    <h2>Key Features</h2>
    <div class="feature-list">
        <div class="feature-item">
            <h3>Employee Management</h3>
            <p>Easily add, edit, and remove employee records with salary details.</p>
        </div>
        <div class="feature-item">
            <h3>Payroll Processing</h3>
            <p>Run payroll calculations with deductions and generate payslips instantly.</p>
        </div>
        <div class="feature-item">
            <h3>Reports & Analytics</h3>
            <p>View detailed payroll history, export CSV reports, and download PDF payslips.</p>
        </div>
    </div>
</section>

<footer>
    <p>&copy; <?php echo date("Y"); ?> SpeedNet. All Rights Reserved.</p>
</footer>

<script src="index.js"></script>
</body>
</html>