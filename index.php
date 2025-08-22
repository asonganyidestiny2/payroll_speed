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
    <div class="logo"><img src="image1_edited.png" alt="SpeedNet Logo"></div>
    <nav class="main-nav">
        <a href="#hero">Home</a>
        <a href="#features">Features</a>
        <a href="#about">About</a>
        <a href="#contact">Contact</a>
        <a href="login.php" class="btn-login">LOGIN</a>
    </nav>
</header>

<main>
    <section id="hero" class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">Simplify Your Payroll.</h1>
            <p class="hero-subtitle">Efficient, secure, and reliable payroll management for modern businesses.</p>
            <a href="login.php" class="btn-cta">Get Started Now</a>
        </div>
        <div class="hero-image">
            <img src="Screenshot_20250806-191947_edited.png" alt="Payroll dashboard screenshot">
        </div>
    </section>

    <section id="features" class="features-section">
        <h2 class="section-title">Key Features</h2>
        <div class="feature-grid">
            <div class="feature-card">
                <img src="img3.png" alt="Employee Management Icon">
                <h3>Employee Management</h3>
                <p>Easily add, update, and manage employee records with detailed salary and personal information.</p>
            </div>
            <div class="feature-card">
                <img src="img4.png" alt="Payroll Processing Icon">
                <h3>Automated Payroll</h3>
                <p>Run payroll calculations instantly, including deductions, bonuses, and tax withholdings.</p>
            </div>
            <div class="feature-card">
                <img src="img.5.png" alt="Reporting Icon">
                <h3>Advanced Reporting</h3>
                <p>Generate comprehensive reports, payslips, and financial summaries for easy analysis and compliance.</p>
            </div>
        </div>
    </section>

    <section id="about" class="about-section">
        <h2 class="section-title">About SpeedNet</h2>
        <p>SpeedNet Payroll System was built to simplify the complexities of managing employee payroll. Our mission is to provide businesses of all sizes with a powerful, intuitive, and secure tool that saves time and reduces administrative burden. We focus on accuracy and reliability so you can focus on growing your business.</p>
    </section>

    <section id="contact" class="contact-section">
        <h2 class="section-title">Get In Touch</h2>
        <p class="contact-subtitle">Have questions or need a demo? Fill out the form below and we'll get back to you shortly.</p>

        <?php
        // PHP code to handle form submission
        $message = "";
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Collect and sanitize form data
            $name = htmlspecialchars(trim($_POST['name']));
            $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
            $subject = htmlspecialchars(trim($_POST['subject']));
            $message_text = htmlspecialchars(trim($_POST['message']));

            // Validate fields
            if (empty($name) || empty($email) || empty($subject) || empty($message_text)) {
                $message = "<div class='message error'>Please fill in all required fields.</div>";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message = "<div class='message error'>Invalid email format.</div>";
            } else {
                // Set recipient email
                $to = "asonganyidestinysavior@gmail.com"; 
                $headers = "From: " . $name . " <" . $email . ">\r\n";
                $headers .= "Reply-To: " . $email . "\r\n";
                $headers .= "Content-type: text/plain; charset=UTF-8\r\n";

                // Compose email content
                $email_content = "Name: $name\n";
                $email_content .= "Email: $email\n";
                $email_content .= "Subject: $subject\n\n";
                $email_content .= "Message:\n$message_text";

                // Send the email
                if (mail($to, $subject, $email_content, $headers)) {
                    $message = "<div class='message success'>Thank you for your message! We will get back to you soon.</div>";
                } else {
                    $message = "<div class='message error'>Oops! Something went wrong and we couldn't send your message, pleas check your internet connection and try again.</div>";
                }
            }
        }
        echo $message;
        ?>

        <form action="#contact" method="POST" class="contact-form">
            <div class="form-group">
                <label for="name">Your Name</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="email">Your Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="subject">Subject</label>
                <input type="text" id="subject" name="subject" required>
            </div>
            <div class="form-group">
                <label for="message">Message</label>
                <textarea id="message" name="message" rows="5" required></textarea>
            </div>
            <button type="submit" class="btn-cta">Send Message</button>
        </form>
    </section>
</main>

<footer>
    <p>&copy; <?php echo date("Y"); ?> SpeedNet Payroll. All Rights Reserved.</p>
</footer>

<script src="../script/index.js"></script>
</body>
</html>