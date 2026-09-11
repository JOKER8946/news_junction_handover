<?php
include 'db_connect.php';
include '../inc/php/validate.logged.php';

// $expiration_time = 15 * 60;
// if (isset($_SESSION['admin_set_time']) && (time() - $_SESSION['admin_set_time']) > $expiration_time) {
//     unset($_SESSION['admin']); 
//     unset($_SESSION['admin_set_time']);
// }

// Handle delete request
if (isset($_POST['delete_user']) && !empty($_POST['user_id'])) {
    $userId = $_POST['user_id'];
    $deleteSql = "UPDATE user SET plan=0 WHERE id=?";
    $deleteStmt = $conn->prepare($deleteSql);
    $deleteStmt->bind_param("i", $userId);
    $deleteStmt->execute();
    $deleteStmt->close();

    header("Location: " . $_SERVER['PHP_SELF'] . "?action=deleted");
    exit();
}

// Handle edit request (update end date)
if (isset($_POST['edit_user']) && !empty($_POST['user_id']) && !empty($_POST['new_end_date'])) {
    $userId = $_POST['user_id'];
    $newEndDate = $_POST['new_end_date'];

    $updateSql = "UPDATE user SET plan_expiry = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("si", $newEndDate, $userId);
    $updateStmt->execute();
    $updateStmt->close();

    header("Location: " . $_SERVER['PHP_SELF'] . "?action=updated");
    exit();
}

// if(isset($_SESSION['admin'])&&( $gUserId==23 && $_SESSION['admin']=="verified" )){
if (true) {
    // AJAX handler for search suggestions
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = '%' . $_GET['search'] . '%';

        // Search for users by name and include email
        $searchSql = "SELECT id, full_name, email FROM user WHERE full_name LIKE ?";
        $searchStmt = $conn->prepare($searchSql);
        $searchStmt->bind_param("s", $search);
        $searchStmt->execute();
        $searchResult = $searchStmt->get_result();

        $suggestions = array();
        if ($searchResult->num_rows > 0) {
            while ($row = $searchResult->fetch_assoc()) {
                $suggestions[] = array(
                    'id' => $row['id'],
                    'name' => $row['full_name'],
                    'email' => $row['email']
                );
            }
        }

        // Return JSON response for AJAX
        header('Content-Type: application/json');
        echo json_encode($suggestions);
        $searchStmt->close();
        exit;
    }

    // Main query for pro users table
    $sql = "SELECT full_name,email, id,plan FROM user  
            where plan=1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    } else {
        $users = ["message" => "No records found in the given date range"];
    }

    $stmt->close();
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Pro users</title>
        <style>
            body {
                font-family: 'Roboto', sans-serif;
                background-color: #f4f4f4;
                margin: 0;
                padding: 0;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                color: #333;
            }

            .container {
                background-color: #fff;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                width: 90%;
                max-width: 960px;
            }

            h1 {
                color: #2c3e50;
                margin-bottom: 25px;
                text-align: center;
            }

            .form-group {
                margin-bottom: 20px;
            }

            label {
                display: block;
                margin-bottom: 8px;
                color: #7f8c8d;
                font-weight: 500;
            }

            input[type="number"],
            input[type="datetime-local"],
            input[type="text"] {
                width: calc(50% - 22px);
                padding: 12px;
                border: 1px solid #bdc3c7;
                border-radius: 6px;
                box-sizing: border-box;
                font-size: 16px;
                transition: border-color 0.3s ease;
            }

            input[type="number"]:focus,
            input[type="datetime-local"]:focus,
            input[type="text"]:focus {
                border-color: #3498db;
                outline: none;
                box-shadow: 0 0 5px rgba(52, 152, 219, 0.2);
            }

            button {
                background-color: #3498db;
                color: #fff;
                padding: 12px 20px;
                border: none;
                border-radius: 6px;
                cursor: pointer;
                font-size: 16px;
                transition: background-color 0.3s ease;
            }

            button:hover {
                background-color: #2980b9;
            }

            .action-btn {
                padding: 6px 12px;
                margin: 0 3px;
                font-size: 14px;
            }

            .edit-btn {
                background-color: #f39c12;
            }

            .edit-btn:hover {
                background-color: #e67e22;
            }

            .delete-btn {
                background-color: #e74c3c;
            }

            .delete-btn:hover {
                background-color: #c0392b;
            }

            .filter-buttons {
                margin-bottom: 25px;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 25px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                border-radius: 8px;
                overflow: hidden;
            }

            th,
            td {
                padding: 15px;
                border: none;
                text-align: left;
                background-color: #fff;
            }

            th {
                background-color: #ecf0f1;
                font-weight: 600;
                color: #7f8c8d;
                padding: 18px;
            }

            tr:nth-child(even) {
                background-color: #f9f9f9;
            }

            p {
                color: #7f8c8d;
                margin-top: 10px;
            }

            .table-container {
                overflow-x: auto;
            }

            .set-date {
                display: none;
                flex-direction: column;
                gap: 15px;
                margin-top: 20px;
            }

            .set-date label {
                margin-bottom: 5px;
            }

            .set-date input[type="number"],
            .set-date input[type="datetime-local"] {
                width: 100%;
                padding: 10px;
            }

            /* Search functionality styles */
            .search-container {
                margin: 20px 0;
                position: relative;
                width: 100%;
                max-width: 400px;
            }

            .search-input {
                width: 100%;
                padding: 12px;
                border: 1px solid #bdc3c7;
                border-radius: 6px;
                font-size: 16px;
                transition: border-color 0.3s ease;
            }

            .search-suggestions {
                position: absolute;
                width: 100%;
                max-height: 200px;
                overflow-y: auto;
                background: white;
                border: 1px solid #ddd;
                border-top: none;
                border-radius: 0 0 6px 6px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                z-index: 1000;
                display: none;
            }

            .suggestion-item {
                padding: 10px 15px;
                cursor: pointer;
                border-bottom: 1px solid #f0f0f0;
            }

            .suggestion-item:hover {
                background-color: #f5f5f5;
            }

            .suggestion-item:last-child {
                border-bottom: none;
            }

            .no-suggestions {
                padding: 10px 15px;
                color: #7f8c8d;
                font-style: italic;
            }

            /* Modal styles */
            .modal {
                display: none;
                position: fixed;
                z-index: 1000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
            }

            .modal-content {
                background-color: #fefefe;
                margin: 15% auto;
                padding: 20px;
                border-radius: 10px;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
                width: 50%;
                max-width: 500px;
                animation: modalopen 0.3s;
            }

            @keyframes modalopen {
                from {
                    opacity: 0;
                    transform: translateY(-20px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .close-modal {
                color: #aaa;
                float: right;
                font-size: 28px;
                font-weight: bold;
                cursor: pointer;
            }

            .close-modal:hover {
                color: #333;
            }

            .modal-title {
                margin-top: 0;
                color: #2c3e50;
            }

            .modal-actions {
                text-align: center;
                margin-top: 20px;
            }

            .alert {
                padding: 15px;
                margin: 20px 0;
                border-radius: 6px;
                font-weight: 500;
                text-align: center;
            }

            .alert-success {
                background-color: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }

            .alert-danger {
                background-color: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
        </style>
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.has('success')) {
                    showAlert('Pro User successfully added!', 'success');
                } else if (urlParams.has('action')) {
                    const action = urlParams.get('action');
                    if (action === 'deleted') {
                        showAlert('Pro User subscription removed successfully!', 'success');
                    } else if (action === 'updated') {
                        showAlert('End date updated successfully!', 'success');
                    }
                }
            });

            function showAlert(message, type) {
                const alertDiv = document.createElement('div');
                alertDiv.className = type === 'success' ? 'alert alert-success' : 'alert alert-danger';
                alertDiv.textContent = message;

                const container = document.querySelector('.container');
                container.insertBefore(alertDiv, container.firstChild.nextSibling);

                setTimeout(() => {
                    alertDiv.remove();
                }, 5000);
            }

            function displayForm() {
                const addpro = document.getElementById("set-date");
                addpro.style.display = "flex";
            }

            // Function to filter and show name suggestions from DB via AJAX
            function showSuggestions() {
                const input = document.getElementById('search-input');
                const searchText = input.value.trim();
                const suggestionsContainer = document.getElementById('search-suggestions');

                // Clear previous suggestions
                suggestionsContainer.innerHTML = '';

                if (searchText.length < 1) {
                    suggestionsContainer.style.display = 'none';
                    return;
                }

                // Fetch suggestions from the server
                fetch(`?search=${searchText}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length === 0) {
                            const noSuggestions = document.createElement('div');
                            noSuggestions.className = 'no-suggestions';
                            noSuggestions.textContent = 'No matching names found';
                            suggestionsContainer.appendChild(noSuggestions);
                        } else {
                            data.forEach(user => {
                                const item = document.createElement('div');
                                item.className = 'suggestion-item';
                                item.textContent = user.name + ' (' + user.email + ')';
                                item.setAttribute('data-userid', user.id);

                                // Add click event to select this name and populate the form
                                item.addEventListener('click', function() {
                                    input.value = this.textContent;
                                    suggestionsContainer.style.display = 'none';

                                    // Fill the userId field in the form and display the form
                                    const userIdField = document.getElementById('userId');
                                    userIdField.value = this.getAttribute('data-userid');

                                    // Display the form
                                    displayForm();

                                    // Highlight the matching row in the table if it exists
                                    const targetRow = findRowByUserId(this.getAttribute('data-userid'));
                                    if (targetRow) {
                                        // Improved scrolling behavior
                                        setTimeout(() => {
                                            // Scroll with offset to keep it in view properly
                                            const yOffset = -100; // Adjust this value as needed
                                            const y = targetRow.getBoundingClientRect().top + window.pageYOffset + yOffset;

                                            window.scrollTo({
                                                top: y,
                                                behavior: 'smooth'
                                            });

                                            // Highlight the row
                                            targetRow.style.backgroundColor = '#ffffa3';
                                            setTimeout(() => {
                                                targetRow.style.backgroundColor = '';
                                            }, 2000);
                                        }, 100);
                                    }
                                });

                                suggestionsContainer.appendChild(item);
                            });
                        }

                        // Show the suggestions container
                        suggestionsContainer.style.display = 'block';
                    })
                    .catch(error => {
                        console.error('Error fetching suggestions:', error);
                    });
            }

            // Helper function to find a row by user ID
            function findRowByUserId(userId) {
                const rows = document.querySelectorAll('#users-table tbody tr');
                for (let i = 0; i < rows.length; i++) {
                    const idCell = rows[i].querySelector('td:first-child');
                    if (idCell && idCell.textContent.trim() === userId) {
                        return rows[i];
                    }
                }
                return null;
            }

            // Close suggestions when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.search-container')) {
                    document.getElementById('search-suggestions').style.display = 'none';
                }
            });

            // Edit user end date function
            function openEditModal(userId, userName, currentEndDate) {
                document.getElementById('edit-user-id').value = userId;
                document.getElementById('edit-user-name').textContent = userName;

                // Format the date properly for datetime-local input
                const endDate = new Date(currentEndDate);
                const formattedDate = endDate.toISOString().slice(0, 16);
                document.getElementById('edit-end-date').value = formattedDate;

                document.getElementById('edit-modal').style.display = 'block';
            }

            // Delete user subscription function
            function openDeleteModal(userId, userName) {
                document.getElementById('delete-user-id').value = userId;
                document.getElementById('delete-user-name').textContent = userName;
                document.getElementById('delete-modal').style.display = 'block';
            }

            // Close modal function
            function closeModal(modalId) {
                document.getElementById(modalId).style.display = 'none';
            }
        </script>
    </head>

    <body>
        <div class="container">
            <button onclick="window.location.href='index.php?filter=week'">Back</button>
            <h1>Reporters</h1>
            <button onclick="displayForm()">Add Reporter</button>

            <!-- Search with suggestions -->
            <div class="search-container">
                <input type="text" id="search-input" class="search-input" placeholder="Search user by name..." oninput="showSuggestions()">
                <div id="search-suggestions" class="search-suggestions"></div>
            </div>

            <form method="POST" action="addPro.php" class="set-date" id="set-date">
                <label for="userId">User ID</label>
                <input type="number" id="userId" name="userId" placeholder="User ID" required>
                <button type="submit">Submit</button>
            </form>

            <div>Count-<?= sizeof($users) ?></div>
            <div class="table-container">
                <table id="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>email</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['full_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                                <td>
                                    <!-- <button class="action-btn edit-btn" onclick="openEditModal('<?php echo htmlspecialchars($user['id']); ?>', '<?php echo htmlspecialchars($user['full_name'] ?? 'N/A'); ?>', '<?php echo htmlspecialchars($user['end_date'] ?? ''); ?>')">Edit</button> -->
                                    <button class="action-btn delete-btn" onclick="openDeleteModal('<?php echo htmlspecialchars($user['id']); ?>', '<?php echo htmlspecialchars($user['full_name'] ?? 'N/A'); ?>')">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Edit Modal -->
        <div id="edit-modal" class="modal">
            <div class="modal-content">
                <span class="close-modal" onclick="closeModal('edit-modal')">&times;</span>
                <h3 class="modal-title">Edit End Date</h3>
                <p>Update subscription end date for <strong id="edit-user-name"></strong></p>

                <form method="POST" action="">
                    <input type="hidden" id="edit-user-id" name="user_id">
                    <div class="form-group">
                        <label for="edit-end-date">New End Date:</label>
                        <input type="datetime-local" id="edit-end-date" name="new_end_date" required>
                    </div>
                    <div class="modal-actions">
                        <button type="button" onclick="closeModal('edit-modal')">Cancel</button>
                        <button type="submit" name="edit_user">Update</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Modal -->
        <div id="delete-modal" class="modal">
            <div class="modal-content">
                <span class="close-modal" onclick="closeModal('delete-modal')">&times;</span>
                <h3 class="modal-title">Remove Reporter</h3>
                <p>Are you sure you want to remove Reporter for <strong id="delete-user-name"></strong>?</p>

                <form method="POST" action="">
                    <input type="hidden" id="delete-user-id" name="user_id">
                    <div class="modal-actions">
                        <button type="button" onclick="closeModal('delete-modal')">Cancel</button>
                        <button type="submit" name="delete_user" class="delete-btn">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </body>

    </html>
<?php
} else {
    // If the request is not from verify_payment.php, throw an HTTP 500 error.
    header('HTTP/1.1 500 Internal Server Error');
    exit();
}
?>