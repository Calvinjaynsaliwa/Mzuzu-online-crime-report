<?php
session_start();
require_once 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Handle status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['report_id']) && isset($_POST['status'])) {
    $report_id = filter_input(INPUT_POST, 'report_id', FILTER_SANITIZE_NUMBER_INT);
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

    // Fetch email address for notification
    $stmt_email = $conn->prepare("SELECT email FROM crime_reports WHERE id = ?");
    $stmt_email->bind_param("i", $report_id);
    $stmt_email->execute();
    $result_email = $stmt_email->get_result();
    $email = $result_email->fetch_assoc()['email'] ?? null;
    $stmt_email->close();

    $stmt = $conn->prepare("UPDATE crime_reports SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $report_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Report status updated successfully.";
        // Send notification email if status is In Progress
        if ($status === "In Progress" && $email) {
            $to = $email;
            $subject = "Crime Report Update - In Progress";
            $message = "Dear reporter,\n\nYour crime report (ID: $report_id) is now In Progress. Thank you for your cooperation.\n\nRegards,\nMzuzu Police Service";
            $headers = "From: noreply@mzuzupolice.gov.mw\r\n";
            $headers .= "Reply-To: mzuzupolice@malawi.gov.mw\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

            $success = mail($to, $subject, $message, $headers);
            if (!$success) {
                $_SESSION['message'] .= " However, the notification email failed to send.";
            }
        }
    } else {
        $_SESSION['message'] = "Error updating report status.";
    }
    $stmt->close();
}

// Fetch all crime reports
$result = $conn->query("SELECT * FROM crime_reports ORDER BY created_at DESC");

// Dynamic data for cards
$totalCrimes = $conn->query("SELECT COUNT(*) as count FROM crime_reports")->fetch_assoc()['count'];
$totalOfficers = 50; // Placeholder, replace with actual data
$solvedCrimes = $conn->query("SELECT COUNT(*) as count FROM crime_reports WHERE status = 'Resolved'")->fetch_assoc()['count'];
$ongoingCrimes = $conn->query("SELECT COUNT(*) as count FROM crime_reports WHERE status = 'In Progress' OR status = 'Pending'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1" name="viewport"/>
    <title>Mzuzu Police - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
    <style>
        /* Custom scrollbar for sidebar */
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-thumb {
            background-color: #4b5563;
            border-radius: 10px;
        }
        .card-bg {
            background-color: #4b5563;
        }
        .chart-bg {
            background-image: url('https://placehold.co/1200x300?text=Wood+Texture+Background&font=roboto');
            background-repeat: no-repeat;
            background-size: cover;
            background-position: center;
        }
        .bar {
            background-color: #4b5563;
            color: #FFFFFF;
            padding-top: 5px;
            text-align: center;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="bg-gray-800 text-white w-64 flex flex-col p-6 space-y-6 select-none" style="min-width: 10rem">
            <h1 class="text-white font-semibold text-lg mb-6">
                Mzuzu Police Service
            </h1>
            <nav class="flex flex-col space-y-3 text-sm font-semibold">
                <a class="flex items-center space-x-2 text-gray-350 hover:text-white transition-colors" href="admin_dashboard.php">
                    <i class="fas fa-home text-sm"></i>
                    <span>Home</span>
                </a>
                <a class="flex items-center space-x-2 text-gray-350 hover:text-white transition-colors" href="reports.php">
                    <i class="fas fa-file-alt text-sm"></i>
                    <span>Report a Crime</span>
                </a>
                <a class="flex items-center space-x-2 text-gray-350 hover:text-white transition-colors" href="admin_login.php">
                    <i class="fas fa-sign-in-alt text-sm"></i>
                    <span>Admin Login</span>
                </a>
            </nav>
            <a href="?logout=true" class="mt-auto text-gray-350 hover:text-white transition-colors flex items-center space-x-2">
                <i class="fas fa-sign-out-alt text-sm"></i>
                <span>Logout</span>
            </a>
        </aside>
        <!-- Main content -->
        <main class="flex-1 flex flex-col">
            <!-- Dashboard content -->
            <section class="flex flex-col p-6 space-y-6">
                <!-- Cards container with background image -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-6 rounded chart-bg">
                    <!-- Card 1 -->
                    <div class="bg-gray-800 text-white p-4 rounded card-bg">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-semibold">
                                    <?php echo $totalCrimes; ?>
                                </p>
                                <p class="text-sm">
                                    Total Crimes Reported
                                </p>
                            </div>
                            <button aria-label="Settings" class="text-white opacity-80 hover:opacity-100">
                                <i class="fas fa-cog"></i>
                            </button>
                        </div>
                        <svg aria-hidden="true" class="mt-4 w-full h-10 opacity-60" fill="none" stroke="currentColor" stroke-width="1.5" viewbox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3 16l4-4 4 4 4-8 4 8 4-4" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </div>
                    <!-- Card 2 -->
                    <div class="bg-gray-800 text-white p-4 rounded card-bg">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-semibold">
                                    <?php echo $totalOfficers; ?>
                                </p>
                                <p class="text-sm">
                                    Total Police Officers
                                </p>
                            </div>
                            <button aria-label="Settings" class="text-white opacity-80 hover:opacity-100">
                                <i class="fas fa-cog"></i>
                            </button>
                        </div>
                        <svg aria-hidden="true" class="mt-4 w-full h-10 opacity-60" fill="none" stroke="currentColor" stroke-width="1.5" viewbox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3 16l4-4 4 4 4-8 4 8 4-4" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </div>
                    <!-- Card 3 -->
                    <div class="bg-gray-800 text-white p-4 rounded card-bg">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-semibold">
                                    <?php echo $solvedCrimes; ?>
                                </p>
                                <p class="text-sm">
                                    Total Crimes Solved
                                </p>
                            </div>
                            <button aria-label="Settings" class="text-white opacity-80 hover:opacity-100">
                                <i class="fas fa-cog"></i>
                            </button>
                        </div>
                        <svg aria-hidden="true" class="mt-4 w-full h-10 opacity-60" fill="none" stroke="currentColor" stroke-width="1.5" viewbox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3 16l4-4 4 4 4-8 4 8 4-4" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </div>
                    <!-- Card 4 -->
                    <div class="bg-gray-800 text-white p-4 rounded card-bg">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-semibold">
                                    <?php echo $ongoingCrimes; ?>
                                </p>
                                <p class="text-sm">
                                    Total Crimes on Going
                                </p>
                            </div>
                            <button aria-label="Settings" class="text-white opacity-80 hover:opacity-100">
                                <i class="fas fa-cog"></i>
                            </button>
                        </div>
                        <svg aria-hidden="true" class="mt-4 w-full h-10 opacity-60" fill="none" stroke="currentColor" stroke-width="1.5" viewbox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3 16l4-4 4 4 4-8 4 8 4-4" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </div>
                </div>
                <!-- Report Table -->
                <div class="bg-white rounded shadow p-4">
                    <h4 class="text-xl font-semibold mb-4">Crime Reports</h4>
                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert <?php echo strpos($_SESSION['message'], 'Error') !== false ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?> p-3 rounded mb-4">
                            <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
                        </div>
                    <?php endif; ?>
                    <div class="table-responsive">
                        <table class="table w-full text-base">
                            <thead>
                                <tr class="bg-gray-800 text-white">
                                    <th class="py-2 px-4">ID</th>
                                    <th class="py-2 px-4">Name</th>
                                    <th class="py-2 px-4">Phone</th>
                                    <th class="py-2 px-4">Email</th>
                                    <th class="py-2 px-4">Crime Type</th>
                                    <th class="py-2 px-4">Description</th>
                                    <th class="py-2 px-4">Location</th>
                                    <th class="py-2 px-4">Evidence</th>
                                    <th class="py-2 px-4">Status</th>
                                    <th class="py-2 px-4">Date</th>
                                    <th class="py-2 px-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr class="border-b">
                                        <td class="py-2 px-4"><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td class="py-2 px-4"><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td class="py-2 px-4"><?php echo htmlspecialchars($row['phone']); ?></td>
                                        <td class="py-2 px-4"><?php echo htmlspecialchars($row['email'] ?? 'N/A'); ?></td>
                                        <td class="py-2 px-4"><?php echo htmlspecialchars($row['crime_type']); ?></td>
                                        <td class="py-2 px-4"><?php echo htmlspecialchars(substr($row['description'], 0, 50)) . (strlen($row['description']) > 50 ? '...' : ''); ?></td>
                                        <td class="py-2 px-4"><?php echo htmlspecialchars($row['location']); ?></td>
                                        <td class="py-2 px-4">
                                            <?php if ($row['evidence']): ?>
                                                <a href="<?php echo htmlspecialchars($row['evidence']); ?>" target="_blank" class="text-red-500 hover:text-red-700">View</a>
                                            <?php else: ?>
                                                None
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-2 px-4"><?php echo htmlspecialchars($row['status']); ?></td>
                                        <td class="py-2 px-4"><?php echo htmlspecialchars($row['created_at']); ?></td>
                                        <td class="py-2 px-4">
                                            <form method="POST">
                                                <input type="hidden" name="report_id" value="<?php echo $row['id']; ?>">
                                                <select name="status" class="form-select text-base p-1 rounded">
                                                    <option value="Pending" <?php echo $row['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="In Progress" <?php echo $row['status'] == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                                    <option value="Resolved" <?php echo $row['status'] == 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                                                </select>
                                                <button type="submit" class="bg-red-500 text-white p-1 rounded mt-1 hover:bg-red-700">Update</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>
</html>