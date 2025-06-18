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

// Handle PDF report generation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate_report'])) {
    // Fetch crime category data with status breakdown
    $category_result = $conn->query("SELECT crime_type, status, COUNT(*) as count FROM crime_reports GROUP BY crime_type, status ORDER BY crime_type, status");

    // Generate LaTeX content
    $latex_content = <<<LATEX
\documentclass[a4paper,12pt]{article}
\usepackage[utf8]{inputenc}
\usepackage{booktabs}
\usepackage{geometry}
\geometry{margin=1in}

% Setting up fonts
\usepackage{fontspec}
\setmainfont{Noto Serif}

% Document setup
\begin{document}

% Title and date
\centerline{\textbf{Crime Category Report}}
\centerline{Generated on: June 18, 2025, 01:20 PM CAT}

% Table setup
\begin{table}[h]
    \centering
    \begin{tabular}{lccc}
        \toprule
        Crime Type & Pending & In Progress & Resolved \\
        \midrule
LATEX;

    $has_data = false;
    $current_type = '';
    $pending_count = 0;
    $in_progress_count = 0;
    $resolved_count = 0;

    while ($row = $category_result->fetch_assoc()) {
        $has_data = true;
        if ($current_type !== $row['crime_type']) {
            if ($current_type !== '') {
                $latex_content .= sprintf("        %s & %d & %d & %d \\\\\n", $current_type, $pending_count, $in_progress_count, $resolved_count);
            }
            $current_type = $row['crime_type'];
            $pending_count = 0;
            $in_progress_count = 0;
            $resolved_count = 0;
        }
        switch ($row['status']) {
            case 'Pending':
                $pending_count = $row['count'];
                break;
            case 'In Progress':
                $in_progress_count = $row['count'];
                break;
            case 'Resolved':
                $resolved_count = $row['count'];
                break;
        }
    }
    if ($current_type !== '' && $has_data) {
        $latex_content .= sprintf("        %s & %d & %d & %d \\\\\n", $current_type, $pending_count, $in_progress_count, $resolved_count);
    } elseif (!$has_data) {
        $latex_content .= "        No data available \\\\\n";
    }

    $latex_content .= <<<LATEX
        \bottomrule
    \end{tabular}
    \caption{Summary of Crime Reports by Category and Status}
\end{table}

\end{document}
LATEX;

    // Save LaTeX file
    $temp_dir = sys_get_temp_dir();
    if (!is_writable($temp_dir)) {
        $_SESSION['message'] = "Temporary directory is not writable. Please check server permissions.";
    } else {
        $latex_file = tempnam($temp_dir, 'crime_report_') . '.tex';
        file_put_contents($latex_file, $latex_content);

        // Compile LaTeX to PDF with error handling
        $output = [];
        $return_var = 0;
        exec("latexmk -pdf -silent $latex_file 2>&1", $output, $return_var);

        $pdf_file = preg_replace('/\.tex$/', '.pdf', $latex_file);
        if (file_exists($pdf_file) && $return_var === 0) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="crime_report_' . date('Ymd_His') . '.pdf"');
            header('Content-Length: ' . filesize($pdf_file));
            readfile($pdf_file);
            unlink($latex_file);
            unlink($pdf_file);
            exit;
        } else {
            $_SESSION['message'] = "Error generating PDF report. Please ensure latexmk and texlive-full are installed. Output: " . implode("\n", $output);
        }
    }
}

// Fetch crime category data with status for display
$category_result = $conn->query("SELECT crime_type, status, COUNT(*) as count FROM crime_reports GROUP BY crime_type, status ORDER BY crime_type, status");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1" name="viewport"/>
    <title>Mzuzu Police - Reports</title>
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
                    <span>Reports</span>
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
            <section class="flex flex-col p-6 space-y-6">
                <div class="bg-white rounded shadow p-4">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-xl font-semibold">Crime Report Summary</h4>
                        <form method="POST">
                            <button type="submit" name="generate_report" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-700">Download Report</button>
                        </form>
                    </div>
                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert <?php echo strpos($_SESSION['message'], 'Error') !== false ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?> p-3 rounded mb-4">
                            <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
                        </div>
                    <?php endif; ?>
                    <div class="table-responsive">
                        <table class="table w-full text-base">
                            <thead>
                                <tr class="bg-gray-800 text-white">
                                    <th class="py-2 px-4">Crime Type</th>
                                    <th class="py-2 px-4">Pending</th>
                                    <th class="py-2 px-4">In Progress</th>
                                    <th class="py-2 px-4">Resolved</th>
                                    <th class="py-2 px-4">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $category_data = [];
                                while ($row = $category_result->fetch_assoc()) {
                                    $crime_type = $row['crime_type'];
                                    if (!isset($category_data[$crime_type])) {
                                        $category_data[$crime_type] = ['Pending' => 0, 'In Progress' => 0, 'Resolved' => 0];
                                    }
                                    $category_data[$crime_type][$row['status']] = $row['count'];
                                }
                                foreach ($category_data as $crime_type => $counts) {
                                    $total = array_sum($counts);
                                    echo "<tr class='border-b'>";
                                    echo "<td class='py-2 px-4'>" . htmlspecialchars($crime_type) . "</td>";
                                    echo "<td class='py-2 px-4'>" . htmlspecialchars($counts['Pending'] ?? 0) . "</td>";
                                    echo "<td class='py-2 px-4'>" . htmlspecialchars($counts['In Progress'] ?? 0) . "</td>";
                                    echo "<td class='py-2 px-4'>" . htmlspecialchars($counts['Resolved'] ?? 0) . "</td>";
                                    echo "<td class='py-2 px-4'>" . htmlspecialchars($total) . "</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>
</html>