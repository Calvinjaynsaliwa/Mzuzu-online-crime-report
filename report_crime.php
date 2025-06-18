<?php
session_start();
require_once 'config.php';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $crime_type = filter_input(INPUT_POST, 'crime_type', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING);
    $evidence = '';
    
    // Handle file upload
    if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $filename = $_FILES['evidence']['name'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if (in_array(strtolower($ext), $allowed)) {
            $new_filename = uniqid() . '.' . $ext;
            $upload_path = 'uploads/' . $new_filename;
            if (move_uploaded_file($_FILES['evidence']['tmp_name'], $upload_path)) {
                $evidence = $upload_path;
            } else {
                $_SESSION['message'] = "Error uploading evidence. Please try again.";
            }
        } else {
            $_SESSION['message'] = "Invalid file type. Only JPG and PNG are allowed.";
        }
    }
    
    // Insert report into database
    if (!isset($_SESSION['message'])) {
        $stmt = $conn->prepare("INSERT INTO crime_reports (name, phone, crime_type, description, location, evidence, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
        $stmt->bind_param("ssssss", $name, $phone, $crime_type, $description, $location, $evidence);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Crime report submitted successfully!";
        } else {
            $_SESSION['message'] = "Error submitting report. Please try again.";
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
    <title>Mzuzu Police - Report a Crime</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Arial', sans-serif; background-color: #F5F6F5; }
        .navbar { background-color:  #4b5563; }
        .navbar-brand, .navbar-nav .nav-link { color: #FFFFFF !important; }
        .navbar-nav .nav-link:hover { color: #E30613 !important; }
        .container { max-width: 600px; margin-top: 30px; }
        .card { border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.1); background-color: #FFFFFF; }
        .card-header { background-color:  #4b5563; color: #FFFFFF; text-align: center; }
        .btn-primary { background-color: #E30613; border-color: #E30613; }
        .btn-primary:hover { background-color: #b80510; border-color: #b80510; }
        .alert { margin-bottom: 20px; }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.html">Mzuzu Police Service</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="report_crime.php">Report a Crime</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_login.php">Admin Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Form Section -->
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3>Report a Crime</h3>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert <?php echo strpos($_SESSION['message'], 'Error') !== false ? 'alert-danger' : 'alert-success'; ?>">
                        <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
                    </div>
                <?php endif; ?>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="name" class="form-label">Your Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="crime_type" class="form-label">Crime Type</label>
                        <select class="form-select" id="crime_type" name="crime_type" required>
                            <option value="">Select Crime Type</option>
                            <option value="Theft">Theft</option>
                            <option value="Assault">Assault</option>
                            <option value="Robbery">Robbery</option>
                            <option value="Rape">Rape</option>
                            <option value="Defilement">Defilement</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="location" class="form-label">Location (e.g., Mzuzu street name, area)</label>
                        <input type="text" class="form-control" id="location" name="location" required>
                    </div>
                    <div class="mb-3">
                        <label for="evidence" class="form-label">Upload Evidence (Image, optional)</label>
                        <input type="file" class="form-control" id="evidence" name="evidence" accept="image/jpeg,image/png">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Submit Report</button>
                </form>
            </div>
        </div>
        <p class="text-center mt-3"><a href="index.php">Back to Home</a></p>
    </div>

    <!-- Footer -->
    <footer class="footer mt-5" style="background-color:  #4b5563; color: #FFFFFF; padding: 20px 0; text-align: center;">
        <div class="container">
            <p>© 2025 Mzuzu Police Service. All rights reserved.</p>
            <p><a href="index.html" style="color: #FFFFFF; text-decoration: none;">Home</a> | <a href="report_crime.php" style="color: #FFFFFF; text-decoration: none;">Report a Crime</a> | <a href="admin_login.php" style="color: #FFFFFF; text-decoration: none;">Admin Login</a></p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>