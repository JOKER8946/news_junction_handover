<?php
include 'assets/php/db_config.php'; // Include your DB configuration
include 'assets/php/validate.logged.php'; // Include your DB configuration

// date_default_timezone_set('Asia/Kolkata');

// Step 2: Handle form submission and get the selected start and end dates
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d'); // Default to today's date if no start date is selected
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d'); // Default to today's date if no end date is selected

// Define SQL queries with the date range parameters
$sql = "SELECT 
        u.id,
        u.full_name, 
        COUNT(*) AS number_of_posts  
    FROM 
        reader.reader_stream r 
    JOIN 
        cream.user u ON r.userId = u.id  
    WHERE 
        DATE(r.postedOn) BETWEEN ? AND ?
        AND
        r.deleteFlag=0
    GROUP BY 
        u.id, 
        u.full_name
    ORDER BY  
        number_of_posts DESC; ";

// Query to fetch articles count from user_collection table
$articles_sql = "SELECT 
        u.id,
        COUNT(*) AS number_of_articles
    FROM 
        cream.user_collection uc
    JOIN 
        cream.user u ON uc.user_id = u.id
    WHERE 
        DATE(uc.date_added) BETWEEN ? AND ?
    GROUP BY 
        u.id
";

// SQL query to count users with active sessions (no endTime) and started in the last 30 days
$active_sessions_sql = "SELECT 
        COUNT(userId) AS active_users
    FROM 
        session_log
    WHERE 
        endTime IS NULL
        AND startTime >= CURDATE() - INTERVAL 30 DAY
";

$session_sql = "SELECT s.startTime ,u.full_name FROM session_log as s JOIN user as u ON u.id=s.userId WHERE s.endTime IS NULL ORDER BY startTime DESC";

// Function to get user data - reused in both PDF and HTML sections
function getUsersData($readerdb, $start_date, $end_date, $sql, $articles_sql, $active_sessions_sql, $session_sql)
{
    global $creamdb;
    try {
        // Prepare the query for social posts
        $stmt = $readerdb->prepare($sql);
        $stmt->bind_param('ss', $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();

        // Store results in an associative array for easy access
        $users_data = [];
        while ($row = $result->fetch_assoc()) {
            $users_data[$row['id']] = [
                'id' => $row['id'],
                'full_name' => $row['full_name'],
                'number_of_posts' => $row['number_of_posts'],
                'number_of_articles' => 0 // Default to 0
            ];
        }

        // Prepare the query for articles count
        $articles_stmt = $readerdb->prepare($articles_sql);
        $articles_stmt->bind_param('ss', $start_date, $end_date);
        $articles_stmt->execute();
        $articles_result = $articles_stmt->get_result();

        // Update the users_data array with article counts
        while ($article_row = $articles_result->fetch_assoc()) {
            $user_id = $article_row['id'];

            // If user exists in our array, update article count
            if (isset($users_data[$user_id])) {
                $users_data[$user_id]['number_of_articles'] = $article_row['number_of_articles'];
            } else {
                // User has articles but no social posts, add them to the array
                $user_stmt = $readerdb->prepare("SELECT full_name FROM cream.user WHERE id = ?");
                $user_stmt->bind_param('i', $user_id);
                $user_stmt->execute();
                $user_result = $user_stmt->get_result();
                $user_data = $user_result->fetch_assoc();

                $users_data[$user_id] = [
                    'id' => $user_id,
                    'full_name' => $user_data['full_name'],
                    'number_of_posts' => 0, // No social posts
                    'number_of_articles' => $article_row['number_of_articles']
                ];
            }
        }

        $session_stmt = $creamdb->prepare($session_sql);
        $session_stmt->execute();
        $session_result = $session_stmt->get_result();
        $session_data = [];
        while ($row = $session_result->fetch_assoc()) {
            $session_data[] = $row;
        }

        // Query to get active sessions
        $active_sessions_stmt = $creamdb->prepare($active_sessions_sql);
        $active_sessions_stmt->execute();
        $active_sessions_result = $active_sessions_stmt->get_result();
        $active_sessions_row = $active_sessions_result->fetch_assoc();
        $active_users_count = $active_sessions_row['active_users'];

        // Sort by number of posts DESC
        usort($users_data, function ($a, $b) {
            return $b['number_of_posts'] - $a['number_of_posts'];
        });


        return [
            'users_data' => $users_data,
            'session_data' => $session_data,
            'active_users_count' => $active_users_count
        ];
    } catch (mysqli_sql_exception $e) {
        echo "Error in SQL query: " . $e->getMessage();
        exit;
    }
}

// Check if PDF download is requested
if (isset($_POST['download_pdf'])) {
    // Alternative approach: Use TCPDF instead of FPDF for better Unicode support
    require_once('tcpdf/tcpdf.php');

    $data = getUsersData($readerdb, $start_date, $end_date, $sql, $articles_sql, $active_sessions_sql, $session_sql);
    $users_data = $data['users_data'];
    $active_users_count = $data['active_users_count'];


    // Create new TCPDF object
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('User Analytics');
    $pdf->SetTitle('User Activity Report');
    $pdf->SetSubject('User Analytics Report');

    // Set default header data
    $pdf->SetHeaderData('', 0, 'User Activity Analytics', 'Date: ' . $date);

    // Set header and footer fonts
    $pdf->setHeaderFont(array('dejavusans', '', 12));
    $pdf->setFooterFont(array('dejavusans', '', 10));

    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont('courier');

    // Set margins
    $pdf->SetMargins(15, 27, 15);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);

    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, 25);

    // Set image scale factor
    $pdf->setImageScale(1.25);

    // Set font
    $pdf->SetFont('dejavusans', '', 10);

    // Add a page
    $pdf->AddPage();

    // Create the table content
    $html = '<table border="1" cellpadding="5">
        <thead>
            <tr style="background-color:#f2f2f2;">
                <th><b>User ID</b></th>
                <th><b>Full Name</b></th>
                <th><b>Social Posts</b></th>
                <th><b>Articles</b></th>
            </tr>
        </thead>
        <tbody>';

    foreach ($users_data as $user) {
        $html .= '<tr>
            <td style="text-align:center;">' . $user['id'] . '</td>
            <td>' . $user['full_name'] . '</td>
            <td style="text-align:center;">' . $user['number_of_posts'] . '</td>
            <td style="text-align:center;">' . $user['number_of_articles'] . '</td>
        </tr>';
    }

    $html .= '</tbody></table>';

    // Print the HTML table
    $pdf->writeHTML($html, true, false, true, false, '');

    // Add active session count to the footer
    $pdf->SetFooterData('', 0, 'Active Sessions: ' . $active_users_count);

    // Close and output PDF document
    $pdf_filename = 'user_activity_' . $date . '.pdf';
    $pdf->Output($pdf_filename, 'D');
    exit;
}

// Get data for HTML display
$data = getUsersData($readerdb, $start_date, $end_date, $sql, $articles_sql, $active_sessions_sql, $session_sql);
$users_data = $data['users_data'];
$session_data = $data['session_data'];
$active_users_count = $data['active_users_count'];

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Activity Analytics</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .form-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
            gap: 10px;
        }

        .date-form {
            display: inline-block;
            margin-right: 10px;
        }

        .pdf-form {
            display: inline-block;
        }

        .btn {
            padding: 8px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn:hover {
            background-color: #45a049;
        }

        .btn-pdf {
            background-color: #f44336;
        }

        .btn-pdf:hover {
            background-color: #d32f2f;
        }

        table {
            width: 100%;
            margin: 20px auto;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 10px;
            text-align: center;
            border: 1px solid #ddd;
        }

        th {
            background-color: #f2f2f2;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
    <script>
        function toggleSessionTable() {
            const Compressed = document.getElementById('compressed');
            // Check if the current display is 'none'
            if (Compressed.style.display === 'none') {
                Compressed.style.display = 'block'; // Show the element
            } else {
                Compressed.style.display = 'none'; // Hide the element
            }
        }
    </script>
</head>

<body>
    <? if ($gUserId == 418 || $gUserId == 23) { ?>
        <div class="container">
            <div class="header">
                <h1>User Activity Analytics</h1>
            </div>

            <h3 style="text-align: center; color: #4CAF50; cursor: pointer;" onclick="toggleSessionTable()">Active Sessions: <?= htmlspecialchars($active_users_count) ?></h3>
            <table id="compressed" style="display: none;">
                <thead>
                    <tr>
                        <th>Serial No</th>
                        <th>Full Name</th>
                        <th>Started At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Loop through the results and display them
                    $count = 1;
                    foreach ($session_data as $user) {
                        echo "<tr>";
                        echo "<td>" . $count . "</td>";
                        echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($user['startTime']) . "</td>";
                        echo "</tr>";
                        $count++;
                    }
                    ?>
                </tbody>
            </table>
            <div class="form-container">
                <!-- Form to select the date -->
                <form class="date-form" method="POST" action="userActivity.php">
                    <label for="start_date">Start Date: </label>
                    <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" required>
                    <label for="end_date">End Date: </label>
                    <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" required>
                    <input type="submit" value="Submit" class="btn">
                </form>

                <!-- Form to download PDF -->
                <!-- <form class="pdf-form" method="POST" action="daysanalytics.php">
                <input type="hidden" name="date" value="<?php echo htmlspecialchars($date); ?>">
                <input type="hidden" name="download_pdf" value="1">
                <input type="submit" value="Download PDF" class="btn btn-pdf">
            </form> -->
            </div>
            <h2 style="text-align: center;">Date Range: <?= htmlspecialchars($start_date) ?> to <?= htmlspecialchars($end_date) ?></h2>
            <table>
                <thead>
                    <tr>
                        <th>Serial No</th>
                        <th>Full Name</th>
                        <th>Social Posts</th>
                        <th>Collections</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Loop through the results and display them
                    $count = 1;
                    foreach ($users_data as $user) {
                        echo "<tr>";
                        echo "<td>" . $count . "</td>";
                        echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($user['number_of_posts']) . "</td>";
                        echo "<td>" . htmlspecialchars($user['number_of_articles']) . "</td>";
                        echo "</tr>";
                        $count++;
                    }
                    ?>
                </tbody>
            </table>
        </div>
    <? } else { ?>
        <H1>You are not allowed to view this page</H1>
    <? } ?>
</body>

</html>