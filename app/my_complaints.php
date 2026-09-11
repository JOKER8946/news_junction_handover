<?php
include './inc/php/db_config.php';
include './inc/php/validate.logged.php';
include './inc/php/function.php';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'updateStatus') {
    header('Content-Type: application/json');

    $complaint_id = isset($_POST['complaint_id']) ? intval($_POST['complaint_id']) : 0;
    $new_status = isset($_POST['status']) ? trim($_POST['status']) : '';
    $user_note = isset($_POST['note']) ? trim($_POST['note']) : '';

    $validStatuses = ['open', 'in-progress', 'resolved', 'closed'];

    if (!in_array($new_status, $validStatuses)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid status']);
        exit;
    }

    // Verify complaint belongs to user
    $checkSql = "SELECT id FROM complaints WHERE id = ? AND user_id = ?";
    $checkStmt = $creamdb->prepare($checkSql);
    $checkStmt->bind_param("ii", $complaint_id, $gUserId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Complaint not found']);
        $checkStmt->close();
        exit;
    }
    $checkStmt->close();

    // Update status
    $updateSql = "UPDATE complaints SET status = ?, user_status_note = ? WHERE id = ?";
    $updateStmt = $creamdb->prepare($updateSql);
    $updateStmt->bind_param("ssi", $new_status, $user_note, $complaint_id);

    if ($updateStmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Status updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update status']);
    }
    $updateStmt->close();
    exit;
}

// Fetch user's complaints with email log info
$sql = "SELECT c.id, c.ticket_id, c.title, c.description, c.media_url, c.status, c.pincode, c.department_name, c.department_email, c.created_at, c.updated_at, c.user_status_note,
               COUNT(cel.id) as email_count,
               MAX(cel.sent_at) as last_email_sent
        FROM complaints c 
        LEFT JOIN complaint_email_logs cel ON c.id = cel.complaint_id
        WHERE c.user_id = ? 
        GROUP BY c.id
        ORDER BY c.created_at DESC";

$stmt = $creamdb->prepare($sql);
$stmt->bind_param("i", $gUserId);
$stmt->execute();
$result = $stmt->get_result();
$complaints = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Helper function to get status badge color
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

// Format date
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
    <title>My Complaints - Pulse</title>
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
        .page-header {
            background: white;
            color: black;
            padding: 40px 0;
            margin-bottom: 40px;
            border-radius: 0 0 20px 20px;
        }

        .page-header h1 {
            font-size: 32px;
            font-weight: 700;
            margin: 0;
        }

        .page-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }

        .container-wrapper {
            max-width: 1000px;
            margin: 0 auto;
        }

        .btn-new-complaint {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 30px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-new-complaint:hover {
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .complaint-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .complaint-card:hover {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .complaint-card.expanded {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .complaint-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            cursor: pointer;
        }

        .complaint-header:hover .expand-icon {
            transform: rotate(180deg);
        }

        .expand-icon {
            transition: transform 0.3s;
        }

        .complaint-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }

        .complaint-ticket {
            font-size: 12px;
            color: #999;
            margin: 5px 0 0 0;
        }

        .complaint-badges {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .badge {
            font-size: 11px;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
        }

        .complaint-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
            flex-wrap: wrap;
            gap: 10px;
        }

        .complaint-meta {
            font-size: 13px;
            color: #666;
        }

        .complaint-date {
            font-size: 12px;
            color: #999;
        }

        .complaint-expanded {
            display: none;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }

        .complaint-expanded.show {
            display: block;
        }

        .status-section {
                background: var(--bg-card);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .status-section h5 {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-primary);
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .status-form {
            display: flex;
            gap: 10px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .status-form select,
        .status-form textarea {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 13px;
        }

        .status-form textarea {
            min-height: 60px;
            resize: vertical;
            flex: 1;
            min-width: 200px;
        }

        .status-form button {
            background: #667eea;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s;
        }

        .status-form button:hover {
            background: #5568d3;
        }

        .email-history {
            background: var(--bg-card);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #0066cc;
        }

        .email-history h5 {
            font-size: 13px;
            font-weight: 700;
            color: #003d7a;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .email-info {
            font-size: 12px;
            color: var(--text-primary);
            line-height: 1.6;
        }

        .email-info strong {
            color: #003d7a;
        }

        .email-icon {
            color: #0066cc;
            margin-right: 8px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #666;
            margin-bottom: 10px;
        }

        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .filter-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-group label {
            font-weight: 600;
            color: #333;
            margin: 0;
        }

        .filter-group select {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 14px;
        }

        .description-section {
            padding: 15px;
            background: var(--bg-card);
            border-left: 4px solid #667eea;
            margin: 15px 0;
            border-radius: 4px;
        }
    </style>
</head>

<body>

    <div class="containers">
        <?php include 'inc/php/social_navbar.php'; ?>
        <?php include 'inc/php/social_sidebar.php'; ?>

        <div class="search-main-content main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <div>
                    <a href="complaint_form.php" class="btn-new-complaint">
                        <i class="fas fa-plus"></i> Submit New Complaint
                    </a>
                </div>
                <div style="font-size: 14px; color: #666;">
                    Total Complaints: <strong><?php echo count($complaints); ?></strong>
                </div>
            </div>

            <?php if (empty($complaints)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <h3>No Complaints Yet</h3>
                    <p>You haven't submitted any complaints. If you're experiencing an issue, let us know!</p>
                    <a href="complaint_form.php" class="btn-new-complaint" style="margin-top: 20px;">
                        <i class="fas fa-plus"></i> Submit Your First Complaint
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($complaints as $complaint): ?>
                    <div class="complaint-card" id="complaint-<?php echo $complaint['id']; ?>">
                        <div class="complaint-header" onclick="toggleExpand(<?php echo $complaint['id']; ?>)">
                            <div>
                                <h3 class="complaint-title"><?php echo htmlspecialchars($complaint['title']); ?></h3>
                                <p class="complaint-ticket">Ticket: <?php echo htmlspecialchars($complaint['ticket_id']); ?></p>
                            </div>
                            <div style="display: flex; gap: 15px; align-items: center;">
                                <div class="complaint-badges">
                                    <span class="badge bg-<?php echo getStatusBadge($complaint['status']); ?>">
                                        <?php echo ucfirst(str_replace('-', ' ', $complaint['status'])); ?>
                                    </span>
                                </div>
                                <i class="fas fa-chevron-down expand-icon"></i>
                            </div>
                        </div>

                        <div class="complaint-details">
                            <div class="complaint-date">
                                <i class="fas fa-clock"></i>
                                <?php echo formatDate($complaint['created_at']); ?>
                            </div>
                            <div class="complaint-meta">
                                <code style="background: #f0f0f0; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                    Pincode: <?php echo htmlspecialchars($complaint['pincode'] ?? 'N/A'); ?>
                                </code>
                            </div>
                            <?php if ($complaint['email_count'] > 0): ?>
                                <div class="complaint-meta" style="color: #0066cc;">
                                    <i class="fas fa-envelope-check"></i>
                                    Email sent to department
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Expanded Details -->
                        <div class="complaint-expanded" id="expanded-<?php echo $complaint['id']; ?>">
                            <!-- Email History -->
                            <?php if ($complaint['email_count'] > 0): ?>
                                <div class="email-history">
                                    <h5><i class="fas fa-envelope email-icon"></i>Email Sent to Department</h5>
                                    <div class="email-info">
                                        <strong>Department:</strong> <?php echo htmlspecialchars($complaint['department_name']); ?><br>
                                        <strong>Email:</strong> <?php echo htmlspecialchars($complaint['department_email']); ?><br>
                                        <strong>Sent On:</strong> <?php echo $complaint['last_email_sent'] ? formatDate($complaint['last_email_sent']) : 'N/A'; ?><br>
                                        <strong>Times Sent:</strong> <?php echo $complaint['email_count']; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="email-history" style="border-left-color: #ffc107; background: #fffbf0;">
                                    <h5 style="color: #856404;"><i class="fas fa-info-circle"></i>Email Status</h5>
                                    <div class="email-info" style="color: #856404;">
                                        <strong>Department:</strong> <?php echo htmlspecialchars($complaint['department_name']); ?><br>
                                        <strong>Email:</strong> <?php echo htmlspecialchars($complaint['department_email']); ?><br>
                                        Email not yet sent to department.
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Complaint Description Section -->
                            <div class="description-section">
                                <h5><i class="fas fa-align-left"></i> Complaint Description</h5>
                                <div style="background: white; padding: 12px; border-radius: 4px; line-height: 1.6; color: #555; white-space: pre-wrap; word-break: break-word;">
                                    <?php echo htmlspecialchars($complaint['description'] ?? 'No description provided'); ?>
                                </div>
                            </div>

                            <!-- Media/Attachment Section -->
                            <?php if (!empty($complaint['media_url'])): ?>
                                <div class="media-section" style="padding: 15px; background: #f0f7ff; border-left: 4px solid #0066cc; margin: 15px 0; border-radius: 4px;">
                                    <h5 style="margin-bottom: 10px; color: #333;"><i class="fas fa-paperclip"></i> Attached Media</h5>
                                    <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                                        <?php
                                        $mediaUrl = htmlspecialchars($complaint['media_url']);
                                        $mediaExtension = strtolower(pathinfo($mediaUrl, PATHINFO_EXTENSION));
                                        $isImage = in_array($mediaExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                        $isVideo = in_array($mediaExtension, ['mp4', 'webm', 'ogg', 'mov', 'avi']);
                                        ?>

                                        <?php if ($isImage): ?>
                                            <img src="<?php echo $mediaUrl; ?>" alt="Complaint media" style="max-width: 300px; max-height: 250px; border-radius: 4px; border: 1px solid #ddd;">
                                        <?php elseif ($isVideo): ?>
                                            <video controls style="max-width: 300px; max-height: 250px; border-radius: 4px; border: 1px solid #ddd;">
                                                <source src="<?php echo $mediaUrl; ?>" type="video/<?php echo $mediaExtension; ?>">
                                                Your browser does not support the video tag.
                                            </video>
                                        <?php endif; ?>

                                        <a href="<?php echo $mediaUrl; ?>" target="_blank" download style="display: inline-flex; align-items: center; gap: 5px; padding: 8px 12px; background: #0066cc; color: white; border-radius: 4px; text-decoration: none; font-size: 14px; transition: 0.3s;">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Status Update Section -->
                            <div class="status-section">
                                <h5>Update Complaint Status</h5>
                                <div class="status-form">
                                    <select id="status-select-<?php echo $complaint['id']; ?>" style="flex: 0 0 auto;">
                                        <option value="open" <?php echo $complaint['status'] == 'open' ? 'selected' : ''; ?>>Open</option>
                                        <option value="in-progress" <?php echo $complaint['status'] == 'in-progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="resolved" <?php echo $complaint['status'] == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                        <option value="closed" <?php echo $complaint['status'] == 'closed' ? 'selected' : ''; ?>>Closed</option>
                                    </select>
                                    <textarea id="note-<?php echo $complaint['id']; ?>" placeholder="Add a note about this update (optional)..."><?php echo htmlspecialchars($complaint['user_status_note'] ?? ''); ?></textarea>
                                    <button onclick="updateComplaintStatus(<?php echo $complaint['id']; ?>)" style="flex: 0 0 auto;">
                                        <i class="fas fa-save"></i> Update
                                    </button>
                                </div>
                                <?php if (!empty($complaint['user_status_note'])): ?>
                                    <div style="margin-top: 10px; padding: 10px; background: white; border-radius: 4px; border-left: 3px solid #667eea; font-size: 12px;">
                                        <strong>Your Note:</strong> <?php echo htmlspecialchars($complaint['user_status_note']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php include 'inc/php/footer.php'; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleExpand(complaintId) {
            const expanded = document.getElementById(`expanded-${complaintId}`);
            const card = document.getElementById(`complaint-${complaintId}`);

            expanded.classList.toggle('show');
            card.classList.toggle('expanded');
        }

        async function updateComplaintStatus(complaintId) {
            const statusSelect = document.getElementById(`status-select-${complaintId}`);
            const noteTextarea = document.getElementById(`note-${complaintId}`);
            const newStatus = statusSelect.value;
            const note = noteTextarea.value.trim();

            const formData = new FormData();
            formData.append('action', 'updateStatus');
            formData.append('complaint_id', complaintId);
            formData.append('status', newStatus);
            formData.append('note', note);

            try {
                const response = await fetch('my_complaints.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.status === 'success') {
                    alert('✓ Status updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to update status'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            }
        }
    </script>
</body>

</html>