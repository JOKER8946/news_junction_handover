<?php
include './inc/php/db_config.php';
include './inc/php/validate.logged.php';
include './inc/php/function.php';

// Only admins can access (role-based check; set by validate.logged.php)
if (empty($gIsAdmin)) {
    header('Location: dashboard.php');
    exit;
}

$message = '';
$messageType = '';

// Handle adding new email contact
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $department = trim($_POST['department'] ?? '');

        if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Invalid name or email address';
            $messageType = 'error';
        } else {
            // Check if email already exists
            $checkSql = "SELECT id FROM complaint_email_contacts WHERE email = ?";
            $checkStmt = $creamdb->prepare($checkSql);
            $checkStmt->bind_param("s", $email);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                $message = 'Email already exists';
                $messageType = 'error';
            } else {
                $sql = "INSERT INTO complaint_email_contacts (name, email, department) VALUES (?, ?, ?)";
                $stmt = $creamdb->prepare($sql);
                $stmt->bind_param("sss", $name, $email, $department);
                if ($stmt->execute()) {
                    $message = 'Email contact added successfully';
                    $messageType = 'success';
                } else {
                    $message = 'Error adding email contact';
                    $messageType = 'error';
                }
                $stmt->close();
            }
            $checkStmt->close();
        }
    } elseif ($_POST['action'] == 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $sql = "DELETE FROM complaint_email_contacts WHERE id = ?";
            $stmt = $creamdb->prepare($sql);
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $message = 'Email contact deleted successfully';
                $messageType = 'success';
            } else {
                $message = 'Error deleting email contact';
                $messageType = 'error';
            }
            $stmt->close();
        }
    } elseif ($_POST['action'] == 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $sql = "UPDATE complaint_email_contacts SET is_active = NOT is_active WHERE id = ?";
            $stmt = $creamdb->prepare($sql);
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $message = 'Status updated successfully';
                $messageType = 'success';
            } else {
                $message = 'Error updating status';
                $messageType = 'error';
            }
            $stmt->close();
        }
    }
}

// Fetch all email contacts
$emailContacts = [];
$sql = "SELECT * FROM complaint_email_contacts ORDER BY name ASC";
$result = $creamdb->query($sql);
if ($result && $result->num_rows > 0) {
    $emailContacts = $result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Complaint Email Contacts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="grfx/img/logo.ico">
    <style>
        body {
            background: #f5f7fa;
            font-family: 'Poppins', sans-serif;
        }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-weight: 700;
            font-size: 28px;
            margin: 0;
        }

        .page-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }

        .container-main {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .card-header {
            background: white;
            border-bottom: 2px solid #f0f0f0;
            padding: 20px;
            border-radius: 12px 12px 0 0;
        }

        .card-body {
            padding: 20px;
        }

        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: block;
        }

        .form-control {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 13px;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-submit {
            background: #667eea;
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-submit:hover {
            background: #5568d3;
            color: white;
            text-decoration: none;
        }

        .table-container {
            overflow-x: auto;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 700;
            color: #333;
            border-bottom: 2px solid #e0e0e0;
            padding: 15px;
        }

        .table td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
        }

        .badge-active {
            background: #d4edda;
            color: #155724;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-inactive {
            background: #f8d7da;
            color: #721c24;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .btn-action {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-right: 5px;
        }

        .btn-toggle {
            background: #f39c12;
            color: white;
        }

        .btn-toggle:hover {
            background: #e67e22;
        }

        .btn-delete {
            background: #e74c3c;
            color: white;
        }

        .btn-delete:hover {
            background: #c0392b;
        }

        .back-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 20px;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 20px;
            }

            .btn-action {
                display: block;
                width: 100%;
                margin-bottom: 5px;
                margin-right: 0;
            }
        }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="container-main">
            <a href="admin_complaints.php" class="back-link" style="color: rgba(255,255,255,0.8); margin-bottom: 10px;">
                <i class="fas fa-arrow-left"></i> Back to Complaints
            </a>
            <h1><i class="fas fa-envelope"></i> Manage Complaint Email Contacts</h1>
            <p>Add, edit, and manage email addresses for complaint distribution</p>
        </div>
    </div>

    <div class="container-main">
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3 style="margin: 0;"><i class="fas fa-plus-circle"></i> Add New Email Contact</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="name" class="form-control" placeholder="e.g., John Manager" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Email Address *</label>
                                <input type="email" name="email" class="form-control" placeholder="john@company.com" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Department</label>
                                <input type="text" name="department" class="form-control" placeholder="e.g., Support Team">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-plus"></i> Add Contact
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 style="margin: 0;"><i class="fas fa-list"></i> All Email Contacts (<?php echo count($emailContacts); ?>)</h3>
            </div>
            <div class="card-body">
                <?php if (empty($emailContacts)): ?>
                    <p style="color: #999; text-align: center; padding: 40px 0;">
                        <i class="fas fa-inbox" style="font-size: 24px;"></i><br>
                        No email contacts added yet
                    </p>
                <?php else: ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <th>Added</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($emailContacts as $contact): ?>
                                    <tr>
                                        <td style="font-weight: 600;">
                                            <?php echo htmlspecialchars($contact['name']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($contact['email']); ?>
                                        </td>
                                        <td>
                                            <?php echo !empty($contact['department']) ? htmlspecialchars($contact['department']) : '<span style="color: #999;">-</span>'; ?>
                                        </td>
                                        <td>
                                            <?php if ($contact['is_active']): ?>
                                                <span class="badge-active">Active</span>
                                            <?php else: ?>
                                                <span class="badge-inactive">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="font-size: 12px; color: #999;">
                                            <?php echo date('M d, Y', strtotime($contact['created_at'])); ?>
                                        </td>
                                        <td>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle">
                                                <input type="hidden" name="id" value="<?php echo $contact['id']; ?>">
                                                <button type="submit" class="btn-action btn-toggle" title="Toggle Active/Inactive">
                                                    <i class="fas fa-toggle-<?php echo $contact['is_active'] ? 'on' : 'off'; ?>"></i>
                                                </button>
                                            </form>
                                            <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this contact?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $contact['id']; ?>">
                                                <button type="submit" class="btn-action btn-delete" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
