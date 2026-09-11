<?php
include './inc/php/db_config.php';
include './inc/php/validate.logged.php';
include './inc/php/function.php';

if (empty($gIsAdmin)) {
    header('Location: my_complaints.php');
    exit;
}

$complaint_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($complaint_id <= 0) {
    header('Location: admin_complaints.php');
    exit;
}

// Fetch complaint details
$sql = "SELECT c.*, u.full_name, u.email 
        FROM complaints c 
        JOIN user u ON c.user_id = u.id 
        WHERE c.id = ?";

$stmt = $creamdb->prepare($sql);
$stmt->bind_param("i", $complaint_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header('Location: admin_complaints.php');
    exit;
}

$complaint = $result->fetch_assoc();
$stmt->close();

// Fetch email contacts for sending
$emailContacts = [];
$contactSql = "SELECT id, name, email FROM complaint_email_contacts WHERE is_active = TRUE ORDER BY name ASC";
$contactResult = $creamdb->query($contactSql);
if ($contactResult && $contactResult->num_rows > 0) {
    $emailContacts = $contactResult->fetch_all(MYSQLI_ASSOC);
}

// Fetch email logs for this complaint
$emailLogs = [];
$logSql = "SELECT * FROM complaint_email_logs WHERE complaint_id = ? ORDER BY created_at DESC";
$logStmt = $creamdb->prepare($logSql);
$logStmt->bind_param("i", $complaint_id);
$logStmt->execute();
$logResult = $logStmt->get_result();
if ($logResult && $logResult->num_rows > 0) {
    $emailLogs = $logResult->fetch_all(MYSQLI_ASSOC);
}
$logStmt->close();

function getStatusBadge($status)
{
    $colors = [
        'open' => 'danger',
        'in-progress' => 'warning',
        'resolved' => 'success',
        'closed' => 'secondary'
    ];
    return $colors[$status] ?? 'secondary';
}

function formatDate($date)
{
    return date('M d, Y H:i', strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Complaint Detail</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="icon" type="image/x-icon" href="../grfx/img/logo.ico">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <link rel="stylesheet" href="inc/css/social.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://js.zohostatic.com/books/zfwidgets/assets/js/zf-widget.js"></script>
    <script src="inc/js/common.js"></script>
    <style>
        .admin-detail-container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .back-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            display: inline-block;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .detail-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            padding: 30px;
            margin-bottom: 20px;
        }

        .detail-header {
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .detail-header h2 {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin: 0;
        }

        .ticket-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .meta-item {
            font-size: 13px;
        }

        .meta-label {
            color: #666;
            font-weight: 600;
        }

        .badge {
            padding: 8px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }

        .info-box-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .info-box-value {
            font-size: 14px;
            color: #333;
            font-weight: 500;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin: 30px 0 15px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #667eea;
        }

        .description-text {
            color: #555;
            line-height: 1.8;
            white-space: pre-wrap;
            word-wrap: break-word;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .media-preview {
            margin-top: 20px;
            text-align: center;
        }

        .media-preview img,
        .media-preview iframe {
            max-width: 100%;
            max-height: 400px;
            border-radius: 8px;
        }

        .remark-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            margin-top: 20px;
        }

        .remark-form textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            resize: vertical;
            min-height: 120px;
        }

        .remark-form textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-buttons {
            display: flex;
            gap: 12px;
            margin-top: 15px;
        }

        .btn-action {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.2s;
        }

        .btn-primary-action {
            background: #667eea;
            color: white;
        }

        .btn-primary-action:hover {
            background: #5568d3;
            color: white;
            text-decoration: none;
        }

        .btn-secondary-action {
            background: #e0e0e0;
            color: #333;
        }

        .btn-secondary-action:hover {
            background: #d0d0d0;
            color: #333;
            text-decoration: none;
        }

        .status-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 13px;
        }

        .status-select:focus {
            outline: none;
            border-color: #667eea;
        }

        .email-section {
            background: #f0f4ff;
            padding: 20px;
            border-radius: 8px;
            border: 2px solid #667eea;
            margin-top: 20px;
        }

        .email-select-wrapper {
            margin-bottom: 15px;
        }

        .email-select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 13px;
            background: white;
            cursor: pointer;
        }

        .email-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .selected-emails-list {
            background: white;
            padding: 12px;
            border-radius: 8px;
            min-height: 40px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
        }

        .email-tag {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            margin-right: 8px;
            margin-bottom: 8px;
            font-size: 12px;
            font-weight: 600;
        }

        .email-tag .remove-email {
            cursor: pointer;
            margin-left: 6px;
            font-weight: bold;
        }

        .btn-send-email {
            background: #27ae60;
            color: white;
        }

        .btn-send-email:hover {
            background: #229954;
            color: white;
            text-decoration: none;
        }

        .btn-send-email:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }

        .email-logs {
            margin-top: 20px;
        }

        .email-log-item {
            background: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .email-log-item.sent {
            border-left-color: #27ae60;
        }

        .email-log-item.pending {
            border-left-color: #f39c12;
        }

        .email-log-item.failed {
            border-left-color: #e74c3c;
        }

        .log-info {
            flex: 1;
        }

        .log-email {
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
        }

        .log-timestamp {
            font-size: 12px;
            color: #999;
        }

        .log-status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .log-status-badge.sent {
            background: #d5f4e6;
            color: #27ae60;
        }

        .log-status-badge.pending {
            background: #fef5e7;
            color: #f39c12;
        }

        .log-status-badge.failed {
            background: #fadbd8;
            color: #e74c3c;
        }

        .status-select:focus {
            outline: none;
            border-color: #667eea;
        }

        .remark-display {
            background: #e8f5e9;
            border-left: 4px solid #27ae60;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }

        .remark-display .admin-label {
            font-weight: 700;
            color: #27ae60;
            margin-bottom: 8px;
        }

        .remark-display .remark-text {
            color: #333;
            line-height: 1.6;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }

            .ticket-meta {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>

<body>
    <div class="containers">
        <?php include 'inc/php/social_navbar.php'; ?>
        <?php include 'inc/php/social_sidebar.php'; ?>

        <div class="search-main-content main-content">
            <div class="admin-detail-container">
                <a href="admin_complaints.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Complaints
                </a>

                <div class="detail-card">
                    <div class="detail-header">
                        <h2><?php echo htmlspecialchars($complaint['title']); ?></h2>

                        <div class="ticket-meta">
                            <div class="meta-item">
                                <span class="meta-label">Ticket ID:</span>
                                <code><?php echo htmlspecialchars($complaint['ticket_id']); ?></code>
                            </div>
                            <div>
                                <span class="badge bg-<?php echo getStatusBadge($complaint['status']); ?>">
                                    <?php echo ucfirst(str_replace('-', ' ', $complaint['status'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="info-grid">
                        <div class="info-box">
                            <div class="info-box-label">Submitted By</div>
                            <div class="info-box-value"><?php echo htmlspecialchars($complaint['full_name']); ?></div>
                            <div style="font-size: 12px; color: #999; margin-top: 4px;">
                                <?php echo htmlspecialchars($complaint['email']); ?>
                            </div>
                        </div>
                        <div class="info-box">
                            <div class="info-box-label">Submitted On</div>
                            <div class="info-box-value"><?php echo formatDate($complaint['created_at']); ?></div>
                        </div>
                        <div class="info-box">
                            <div class="info-box-label">Pincode</div>
                            <div class="info-box-value">
                                <code style="background: #f0f0f0; padding: 4px 8px; border-radius: 4px; font-size: 14px;">
                                    <?php echo htmlspecialchars($complaint['pincode'] ?? 'N/A'); ?>
                                </code>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="detail-card">
                    <div class="section-title">
                        <i class="fas fa-align-left"></i> Complaint Description
                    </div>
                    <div class="description-text">
                        <?php echo htmlspecialchars($complaint['description']); ?>
                    </div>

                    <?php if (!empty($complaint['media_url'])): ?>
                        <div class="media-preview">
                            <div style="color: #999; font-size: 12px; margin-bottom: 10px;">
                                <i class="fas fa-paperclip"></i> Attached Media
                            </div>
                            <?php if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $complaint['media_url'])): ?>
                                <img src="<?php echo htmlspecialchars($complaint['media_url']); ?>" alt="Complaint attachment">
                            <?php elseif (preg_match('/\.(mp4|webm|ogg|mov)$/i', $complaint['media_url'])): ?>
                                <video width="100%" controls style="border-radius: 8px; max-height: 400px;">
                                    <source src="<?php echo htmlspecialchars($complaint['media_url']); ?>" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            <?php elseif (preg_match('/\.pdf$/i', $complaint['media_url'])): ?>
                                <a href="<?php echo htmlspecialchars($complaint['media_url']); ?>" target="_blank" class="btn btn-primary" style="margin-top: 10px;">
                                    <i class="fas fa-file-pdf"></i> Download PDF
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="detail-card">
                    <div class="section-title">
                        <i class="fas fa-reply"></i> Admin Remarks
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">
                            Update Status
                        </label>
                        <select class="status-select" id="statusSelect">
                            <option value="open" <?php echo $complaint['status'] == 'open' ? 'selected' : ''; ?>>Open</option>
                            <option value="in-progress" <?php echo $complaint['status'] == 'in-progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="resolved" <?php echo $complaint['status'] == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                            <option value="closed" <?php echo $complaint['status'] == 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                    </div>

                    <?php if (!empty($complaint['admin_remark'])): ?>
                        <div class="remark-display">
                            <div class="admin-label">
                                <i class="fas fa-check-circle"></i> Admin Remarks
                            </div>
                            <div class="remark-text">
                                <?php echo htmlspecialchars($complaint['admin_remark']); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form id="remarkForm" class="remark-form">
                        <input type="hidden" name="complaint_id" value="<?php echo $complaint['id']; ?>">
                        <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">
                            Add or Update Remarks
                        </label>
                        <textarea name="remark" id="remarkText" placeholder="Enter your remarks about this complaint..." required><?php echo htmlspecialchars($complaint['admin_remark']); ?></textarea>
                        <div class="form-buttons">
                            <button type="submit" class="btn-action btn-primary-action">
                                <i class="fas fa-save"></i> Save Remarks
                            </button>
                            <button type="button" class="btn-action btn-secondary-action" onclick="window.history.back()">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                    </form>

                    <!-- Email Sending Section -->
                    <div class="email-section">
                        <div style="margin-bottom: 20px;">
                            <h5 style="color: #333; font-weight: 700; margin-bottom: 15px;">
                                <i class="fas fa-envelope"></i> Send Complaint to Email
                            </h5>
                            <p style="color: #666; font-size: 13px; margin-bottom: 15px;">
                                Select one or more email addresses to send this complaint to.
                            </p>

                            <?php if (!empty($emailContacts)): ?>
                                <div class="email-select-wrapper">
                                    <label style="font-weight: 600; color: #333; margin-bottom: 8px; display: block;">
                                        Select Recipients
                                    </label>
                                    <select class="email-select" id="emailSelect">
                                        <option value="">-- Select Email --</option>
                                        <?php foreach ($emailContacts as $contact): ?>
                                            <option value="<?php echo htmlspecialchars($contact['email']); ?>" data-name="<?php echo htmlspecialchars($contact['name']); ?>" data-id="<?php echo $contact['id']; ?>">
                                                <?php echo htmlspecialchars($contact['name']); ?> (<?php echo htmlspecialchars($contact['email']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="selected-emails-list" id="selectedEmailsList">
                                    <span style="color: #999; font-size: 12px;">No emails selected</span>
                                </div>

                                <div class="form-buttons">
                                    <button type="button" class="btn-action btn-send-email" id="sendEmailBtn" onclick="sendComplaintEmail()">
                                        <i class="fas fa-paper-plane"></i> Send Complaint
                                    </button>
                                </div>
                            <?php else: ?>
                                <div style="background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107;">
                                    <p style="margin: 0; color: #856404; font-size: 13px;">
                                        <i class="fas fa-info-circle"></i> No email contacts configured. Please add email contacts first.
                                    </p>
                                </div>
                            <?php endif; ?>

                            <!-- Email Logs -->
                            <?php if (!empty($emailLogs)): ?>
                                <div class="email-logs">
                                    <h6 style="color: #333; font-weight: 700; margin-bottom: 12px; margin-top: 20px;">
                                        <i class="fas fa-history"></i> Send History
                                    </h6>
                                    <?php foreach ($emailLogs as $log): ?>
                                        <div class="email-log-item <?php echo htmlspecialchars($log['status']); ?>">
                                            <div class="log-info">
                                                <div class="log-email">
                                                    <?php echo htmlspecialchars($log['recipient_name'] ?? $log['recipient_email']); ?>
                                                </div>
                                                <div style="font-size: 12px; color: #666; margin-bottom: 4px;">
                                                    <?php echo htmlspecialchars($log['recipient_email']); ?>
                                                </div>
                                                <div class="log-timestamp">
                                                    <?php echo formatDate($log['created_at']); ?>
                                                    <?php if ($log['sent_at']): ?>
                                                        <br>Sent: <?php echo formatDate($log['sent_at']); ?>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($log['status'] == 'failed' && $log['error_message']): ?>
                                                    <div style="font-size: 12px; color: #e74c3c; margin-top: 4px;">
                                                        Error: <?php echo htmlspecialchars($log['error_message']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <span class="log-status-badge <?php echo htmlspecialchars($log['status']); ?>">
                                                <?php echo ucfirst($log['status']); ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="detail-card">


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const statusSelect = document.getElementById('statusSelect');
        const remarkForm = document.getElementById('remarkForm');

        statusSelect.addEventListener('change', async function() {
            const complaintId = <?php echo $complaint_id; ?>;
            const newStatus = this.value;

            try {
                const response = await fetch('inc/php/admin_complaint_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=updateStatus&complaint_id=${complaintId}&status=${newStatus}`
                });

                const data = await response.json();
                if (data.status === 'success') {
                    alert('Status updated successfully');
                    location.reload();
                } else {
                    alert('Error updating status');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred');
            }
        });

        remarkForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'addRemark');

            try {
                const response = await fetch('inc/php/admin_complaint_handler.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                if (data.status === 'success') {
                    alert('Remarks saved successfully');
                    location.reload();
                } else {
                    alert('Error saving remarks');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred');
            }
        });

        // Email sending functionality
        let selectedEmails = [];

        const emailSelect = document.getElementById('emailSelect');
        const selectedEmailsList = document.getElementById('selectedEmailsList');
        const sendEmailBtn = document.getElementById('sendEmailBtn');

        if (emailSelect) {
            emailSelect.addEventListener('change', function() {
                if (this.value) {
                    const email = this.value;
                    const name = this.options[this.selectedIndex].getAttribute('data-name');
                    const contactId = this.options[this.selectedIndex].getAttribute('data-id');

                    // Check if email already selected
                    if (!selectedEmails.find(e => e.email === email)) {
                        selectedEmails.push({ email, name, contactId });
                        updateSelectedEmailsList();
                    }

                    // Reset select
                    this.value = '';
                }
            });
        }

        function updateSelectedEmailsList() {
            if (selectedEmails.length === 0) {
                selectedEmailsList.innerHTML = '<span style="color: #999; font-size: 12px;">No emails selected</span>';
                sendEmailBtn.disabled = true;
            } else {
                let html = '';
                selectedEmails.forEach((item, index) => {
                    html += `<span class="email-tag">
                        ${item.name} (${item.email})
                        <span class="remove-email" onclick="removeEmail(${index})">✕</span>
                    </span>`;
                });
                selectedEmailsList.innerHTML = html;
                sendEmailBtn.disabled = false;
            }
        }

        function removeEmail(index) {
            selectedEmails.splice(index, 1);
            updateSelectedEmailsList();
        }

        function sendComplaintEmail() {
            if (selectedEmails.length === 0) {
                alert('Please select at least one email address');
                return;
            }

            const complaintId = <?php echo $complaint_id; ?>;
            const emails = selectedEmails.map(e => e.email).join(',');

            sendEmailBtn.disabled = true;
            sendEmailBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

            fetch('inc/php/admin_complaint_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=sendComplaintEmail&complaint_id=${complaintId}&emails=${encodeURIComponent(emails)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    let message = 'Complaint sent successfully to ' + selectedEmails.length + ' recipient(s)';
                    if (data.details && data.details.length > 0) {
                        message += '\n\nDetails:\n' + data.details.join('\n');
                    }
                    alert(message);
                    selectedEmails = [];
                    updateSelectedEmailsList();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    let errorMsg = data.message || 'Failed to send email';
                    if (data.details && data.details.length > 0) {
                        errorMsg += '\n\nDetails:\n' + data.details.join('\n');
                    }
                    alert('Error: ' + errorMsg);
                    console.error('Full error response:', data);
                    sendEmailBtn.disabled = false;
                    sendEmailBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Complaint';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while sending: ' + error.message);
                sendEmailBtn.disabled = false;
                sendEmailBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Complaint';
            });
        }
    </script>
</body>

</html>
