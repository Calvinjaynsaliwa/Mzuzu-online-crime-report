<?php
session_start();
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header("Location: admin_dashboard.php");
    exit;
}

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login_submit'])) {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = $_POST['password'];

    // Validate inputs
    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        // Check user credentials
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                header("Location: admin_dashboard.php");
                exit;
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mzuzu Police - Online Crime Reporting</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Arial', sans-serif; background-color: #F5F6F5; }
        .navbar { background-color:  #4b5563; }
        .navbar-brand, .navbar-nav .nav-link { color: #FFFFFF !important; }
        .navbar-nav .nav-link:hover { color: #E30613 !important; }
        .hero-section { background-color:  #4b5563; color: #FFFFFF; padding: 80px 0; text-align: center; }
        .hero-section h1 { font-size: 2.8rem; font-weight: bold; }
        .hero-section .btn-primary { background-color: #E30613; border-color: #E30613; }
        .hero-section .btn-primary:hover { background-color: #b80510; border-color: #b80510; }
        .hero-section .btn-outline-light:hover { background-color: #FFFFFF; color:  #4b5563; }
        .about-section { padding: 50px 0; background-color: #FFFFFF; }
        .contact-section { padding: 50px 0; background-color: #F5F6F5; color:  #4b5563; }
        .footer { background-color:  #4b5563; color: #FFFFFF; padding: 20px 0; text-align: center; }
        .footer a { color: #FFFFFF; text-decoration: none; }
        .footer a:hover { color: #E30613; }
        .modal-content { border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .modal-header { background-color:  #4b5563; color: #FFFFFF; }
        .btn-primary { background-color: #E30613; border-color: #E30613; }
        .btn-primary:hover { background-color: #b80510; border-color: #b80510; }
        .alert { margin-bottom: 10px; }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="#">Mzuzu Police Service</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="report_crime.php">Report a Crime</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#modal-login">Admin Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1>Welcome to Mzuzu Police Online Crime Reporting</h1>
            <p class="lead">Report crimes securely and efficiently to support a safer Mzuzu community.</p>
            <a href="report_crime.php" class="btn btn-primary btn-lg">Report a Crime</a>
            <button class="btn btn-outline-light btn-lg" data-bs-toggle="modal" data-bs-target="#modal-login">Police Admin Login</button>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section">
        <div class="container">
            <h2 class="text-center mb-4">About Our System</h2>
            <p>The Mzuzu Police Online Crime Reporting System enables citizens to report crimes such as theft, assault, or robbery from anywhere in Mzuzu. Our mission is to enhance accessibility and response times, empowering the Malawi Police Service to maintain safety and security.</p>
            <p>Provide incident details, including location and optional evidence, and track your report’s status online.</p>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section">
        <div class="container">
            <h2 class="text-center mb-4">Contact Mzuzu Police</h2>
            <p class="text-center">For emergencies, call <strong>997</strong> or visit your nearest police station.</p>
            <ul class="list-unstyled text-center">
                <li>Phone: +265 1 311 333</li>
                <li>Email: mzuzupolice@malawi.gov.mw</li>
                <li>Address: Mzuzu Police Station, M1 Road, Mzuzu, Malawi</li>
            </ul>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>© 2025 Mzuzu Police Service. All rights reserved.</p>
            <p><a href="report_crime.php">Report a Crime</a> | <a href="#" data-bs-toggle="modal" data-bs-target="#modal-login">Admin Login</a></p>
        </div>
    </footer>

    <!-- Login Modal -->
    <div class="modal fade" id="modal-login" tabindex="-1" aria-labelledby="modalLoginLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalLoginLabel">Admin Login</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" name="login_submit">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>