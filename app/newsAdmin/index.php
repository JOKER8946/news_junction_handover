<?php
include 'db_connect.php';
include '../inc/php/validate.logged.php';

// Handle new user addition
$addUserMessage = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $is_activated =  1;

    // Check if email already exists
    $check_sql = "SELECT id FROM user WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $addUserMessage = "<div class='error-message'>Email already exists!</div>";
    } else {
        // Insert new user
        $insert_sql = "INSERT INTO user (full_name, email, password, is_activated, date_created) VALUES (?, ?, ?, ?, NOW())";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("sssi", $full_name, $email, $password, $is_activated);

        if ($insert_stmt->execute()) {
            $addUserMessage = "<div class='success-message'>User added successfully!</div>";
        } else {
            $addUserMessage = "<div class='error-message'>Error adding user: " . $conn->error . "</div>";
        }
        $insert_stmt->close();
    }
    $check_stmt->close();
}

// if (isset($_SESSION['admin']) && ($gUserId == 23 && $_SESSION['admin'] == "verified")) {
if (true) {

    // Get date range from request
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
    $filter = isset($_GET['filter']) ? $_GET['filter'] : '';

    // Determine date range based on filter
    if ($filter === 'week') {
        $start_date = date('Y-m-d', strtotime('-1 week'));
        $end_date = date('Y-m-d');
    } elseif ($filter === 'month') {
        $start_date = date('Y-m-d', strtotime('-1 month'));
        $end_date = date('Y-m-d');
    } elseif ($filter === 'year') {
        $start_date = date('Y-m-d', strtotime('-1 year'));
        $end_date = date('Y-m-d');
    } elseif ($filter === 'all') {
        $start_date = date('Y-m-d', strtotime('-10 year'));
        $end_date = date('Y-m-d');
    }

    $users = [];
    $userCountByDate = []; // Array to store user count by date
    $adjusted_end_date = date('Y-m-d', strtotime($end_date . ' +1 day'));
    
    if (!empty($start_date) && !empty($end_date)) {
        // Get all users within date range
        $sql = "SELECT * FROM user WHERE DATE(date_created) BETWEEN ? AND ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $start_date, $adjusted_end_date);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
                
                // Extract just the date part for grouping
                $date = date('Y-m-d', strtotime($row['date_created']));
                
                // Initialize or increment count for this date
                if (!isset($userCountByDate[$date])) {
                    $userCountByDate[$date] = 1;
                } else {
                    $userCountByDate[$date]++;
                }
            }
        } else {
            $users = ["message" => "No records found in the given date range"];
        }

        $stmt->close();
        
        // Get the count of users grouped by date for the graph
        $graph_sql = "SELECT DATE(date_created) as reg_date, COUNT(*) as count 
                      FROM user 
                      WHERE DATE(date_created) BETWEEN ? AND ? 
                      GROUP BY DATE(date_created) 
                      ORDER BY reg_date";
        
        $graph_stmt = $conn->prepare($graph_sql);
        $graph_stmt->bind_param("ss", $start_date, $adjusted_end_date);
        $graph_stmt->execute();
        $graph_result = $graph_stmt->get_result();
        
        $chartData = [];
        if ($graph_result->num_rows > 0) {
            while ($row = $graph_result->fetch_assoc()) {
                $chartData[] = [
                    'date' => $row['reg_date'],
                    'count' => (int)$row['count']
                ];
            }
        }
        $graph_stmt->close();
    } else {
        $users = ["error" => "Please provide both start and end date"];
    }

    $conn->close();
?>


    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin</title>
        <!-- Include Chart.js library -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        
        <style>
            body {
                font-family: 'Roboto', sans-serif;
                line-height: 1.6;
                margin: 0;
                padding: 20px;
                background-color: #f5f5f5;
            }
            
            .container {
                max-width: 1200px;
                margin: 0 auto;
                background-color: #fff;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            }
            
            h1 {
                text-align: center;
                color: #333;
            }
            
            .btnContainer {
                display: flex;
                justify-content: center;
                gap: 10px;
                margin-bottom: 20px;
            }
            
            button {
                background-color: #4CAF50;
                color: white;
                border: none;
                padding: 10px 15px;
                cursor: pointer;
                border-radius: 4px;
                font-weight: 500;
            }
            
            button:hover {
                background-color: #45a049;
            }
            
            .form-group {
                margin-bottom: 15px;
            }
            
            .set-date {
                display: flex;
                align-items: center;
                gap: 10px;
                justify-content: center;
                margin-bottom: 20px;
            }
            
            input[type="date"] {
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            
            .filter-buttons {
                display: flex;
                justify-content: center;
                margin-bottom: 20px;
            }
            
            .filter-buttons form {
                display: flex;
                gap: 10px;
            }
            
            .search-container {
                margin-bottom: 15px;
                text-align: center;
            }
            
            .search-input {
                padding: 8px;
                width: 300px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            
            #count-display {
                text-align: center;
                margin-bottom: 15px;
                font-size: 18px;
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }
            
            th, td {
                padding: 12px 15px;
                text-align: left;
                border-bottom: 1px solid #ddd;
            }
            
            thead {
                background-color: #f2f2f2;
            }
            
            tbody tr:hover {
                background-color: #f9f9f9;
            }
            
            .no-results {
                text-align: center;
                padding: 20px;
                font-size: 18px;
                color: #888;
            }
            
            .success-message {
                background-color: #d4edda;
                color: #155724;
                padding: 10px;
                margin: 10px 0;
                border-radius: 4px;
                text-align: center;
            }
            
            .error-message {
                background-color: #f8d7da;
                color: #721c24;
                padding: 10px;
                margin: 10px 0;
                border-radius: 4px;
                text-align: center;
            }
            
            /* Modal styles */
            .modal {
                display: none;
                position: fixed;
                z-index: 1;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                overflow: auto;
                background-color: rgba(0, 0, 0, 0.4);
            }
            
            .modal-content {
                background-color: #fefefe;
                margin: 10% auto;
                padding: 20px;
                border: 1px solid #888;
                width: 50%;
                border-radius: 8px;
            }
            
            .close-modal {
                color: #aaa;
                float: right;
                font-size: 28px;
                font-weight: bold;
                cursor: pointer;
            }
            
            .close-modal:hover {
                color: black;
            }
            
            .modal-title {
                margin-top: 0;
                color: #333;
            }
            
            .modal-actions {
                display: flex;
                justify-content: flex-end;
                gap: 10px;
                margin-top: 20px;
            }
            
            label {
                display: block;
                margin-bottom: 5px;
                font-weight: 500;
            }
            
            input[type="text"],
            input[type="email"] {
                width: 100%;
                padding: 8px;
                margin-bottom: 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            
            .checkbox-group {
                display: flex;
                align-items: center;
                gap: 5px;
            }
            
            /* Chart container */
            .chart-container {
                max-width: 800px;
                margin: 0 auto 30px auto;
                padding: 20px;
                background-color: #ffffff;
                border-radius: 8px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            }
            
            .chart-title {
                text-align: center;
                margin-bottom: 15px;
                color: #333;
            }
        </style>

        <script>
            // Function to filter users based on search input
            function filterUsers() {
                const searchInput = document.getElementById('search-input').value.toLowerCase();
                const userRows = document.querySelectorAll('#users-table tbody tr');
                let visibleCount = 0;

                userRows.forEach(row => {
                    const name = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                    if (name.includes(searchInput)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                document.getElementById('count-display').textContent = `Count-${visibleCount}`;

                // Show/hide the no results message
                const noResults = document.getElementById('no-results');
                if (visibleCount === 0) {
                    noResults.style.display = 'block';
                    document.getElementById('users-table').style.display = 'none';
                } else {
                    noResults.style.display = 'none';
                    document.getElementById('users-table').style.display = '';
                }
            }

            function openAddUserModal() {
                document.getElementById('add-user-modal').style.display = 'block';
            }

            function closeModal(modalId) {
                document.getElementById(modalId).style.display = 'none';
            }

            // Close modal when clicking outside of it
            window.onclick = function(event) {
                if (event.target.className === 'modal') {
                    event.target.style.display = 'none';
                }
            }

            // Function to initialize the chart
            function initializeChart(chartData) {
                const ctx = document.getElementById('userChart').getContext('2d');
                
                // Extract labels (dates) and data (counts)
                const labels = chartData.map(item => item.date);
                const data = chartData.map(item => item.count);
                
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'New Users',
                            data: data,
                            backgroundColor: 'rgba(76, 175, 80, 0.6)',
                            borderColor: 'rgba(76, 175, 80, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                },
                                title: {
                                    display: true,
                                    text: 'Number of New Users'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Date'
                                }
                            }
                        },
                        plugins: {
                            title: {
                                display: true,
                                text: 'User Registration Trends',
                                font: {
                                    size: 18
                                }
                            },
                            legend: {
                                position: 'top'
                            }
                        }
                    }
                });
            }
        </script>
    </head>

    <body>
        <div class="container">
            <h1>Admin Dashboard</h1>
            <?php echo $addUserMessage; ?>
            <div class="btnContainer">
                <!-- <button onclick="openAddUserModal()">Add user</button> -->
                <button onclick="window.location.href='reporters.php'">Add reporter</button>
                <!-- <button onclick="window.location.href='userActivity.php'">User Activity</button> -->
            </div>
            <div class="form-group">
                <form method="GET" class="set-date">
                    <label for="start_date">Start Date:</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                    <label for="end_date">End Date:</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                    <button type="submit">Filter</button>
                </form>
            </div>

            <div class="filter-buttons">
                <form method="GET">
                    <button type="submit" <?php if ($filter == 'week') echo "style='background-color:green'"; ?> name="filter" value="week">Last Week</button>
                    <button type="submit" <?php if ($filter == 'month') echo "style='background-color:green'"; ?> name="filter" value="month">Last Month</button>
                    <button type="submit" <?php if ($filter == 'year') echo "style='background-color:green'"; ?> name="filter" value="year">Last Year</button>
                    <button type="submit" <?php if ($filter == 'all') echo "style='background-color:green'"; ?> name="filter" value="all">All Time</button>
                </form>
            </div>

            <!-- Chart container -->
            <?php if (!empty($chartData)): ?>
            <div class="chart-container">
                <h2 class="chart-title">User Registration Count by Date</h2>
                <canvas id="userChart"></canvas>
            </div>
            <?php endif; ?>

            <div class="search-container">
                <input type="text" id="search-input" class="search-input" placeholder="Search by name..." oninput="filterUsers()">
            </div>
            <div id="count-display"><b>Count-<?= count($users) ?></b>
                From <b><?= $start_date ?></b> to <b><?= $end_date ?></b></div>
            <div class="table-container">
                <div id="no-results" class="no-results" style="display: none;">
                    No users found matching your search.
                </div>
                <div class="table-container">
                    <?php if (!empty($users) && isset($users[0]['id'])): ?>
                        <table id="users-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Verified</th>
                                    <th>Registration Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                                        <td><?php echo htmlspecialchars($user['full_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                                        <td><?php echo ($user['is_activated']) ? 'Yes' : 'No'; ?></td>
                                        <td><?php echo htmlspecialchars($user['date_created'] ?? 'N/A'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p><?php echo htmlspecialchars($users['message'] ?? $users['error']); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Add User Modal -->
            <div id="add-user-modal" class="modal">
                <div class="modal-content">
                    <span class="close-modal" onclick="closeModal('add-user-modal')">&times;</span>
                    <h3 class="modal-title">Add New User</h3>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="full_name">Full Name:</label>
                            <input type="text" id="full_name" name="full_name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password:</label>
                            <input type="text" id="password" name="password" required>
                        </div>
                        <div class="modal-actions">
                            <button type="button" onclick="closeModal('add-user-modal')">Cancel</button>
                            <button type="submit" name="add_user">Add User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Initialize chart with the data -->
        <?php if (!empty($chartData)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const chartData = <?php echo json_encode($chartData); ?>;
                initializeChart(chartData);
            });
        </script>
        <?php endif; ?>
    </body>

    </html>
<? } else {
    // If the request is not from verify_payment.php, throw an HTTP 500 error.
    header('HTTP/1.1 500 Internal Server Error');
    exit();
}
?>