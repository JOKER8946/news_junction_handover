<?php
include './inc/php/db_config.php';
include './inc/php/validate.logged.php';
include './inc/php/function.php';

// Get complaint ID from URL
$complaint_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($complaint_id <= 0) {
    header('Location: my_complaints.php');
    exit;
}

// Fetch complaint details
$sql = "SELECT * FROM complaints WHERE id = ? AND user_id = ?";
$stmt = $creamdb->prepare($sql);
$stmt->bind_param("ii", $complaint_id, $gUserId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header('Location: my_complaints.php');
    exit;
}

$complaint = $result->fetch_assoc();
$stmt->close();

// Helper functions
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

function getDaysOpen($created_at)
{
    $now = new DateTime();
    $created = new DateTime($created_at);
    $diff = $now->diff($created);
    return $diff->days;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($complaint['title']); ?> - News Junction</title>
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
        body {
            background: #f8f9fa;
            padding: 20px 0;
        }

        .container-wrapper {
            max-width: 900px;
            margin: 0 auto;
        }

        .back-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 30px;
            display: inline-block;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .complaint-header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .complaint-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin: 0 0 15px 0;
        }

        .ticket-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .meta-item {
            font-size: 14px;
        }

        .meta-label {
            color: #666;
            font-weight: 600;
        }

        .meta-value {
            color: #333;
        }

        .badges-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .badge {
            font-size: 11px;
            padding: 8px 14px;
            border-radius: 20px;
            font-weight: 600;
        }

        .section-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
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
        }

        .response-section {
            background: #f0f4ff;
            padding: 20px;
            border-left: 4px solid #667eea;
            border-radius: 8px;
            margin-top: 15px;
        }

        .response-label {
            font-weight: 600;
            color: #667eea;
            margin-bottom: 10px;
        }

        .response-text {
            color: #555;
            line-height: 1.8;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .no-response {
            color: #999;
            font-style: italic;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 15px;
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
            font-size: 15px;
            color: #333;
            font-weight: 600;
        }

        .status-timeline {
            padding: 20px 0;
        }

        .timeline-item {
            display: flex;
            margin-bottom: 20px;
        }

        .timeline-marker {
            width: 30px;
            height: 30px;
            background: #667eea;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            flex-shrink: 0;
            margin-right: 20px;
        }

        .timeline-content {
            flex: 1;
        }

        .timeline-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 3px;
        }

        .timeline-date {
            font-size: 13px;
            color: #999;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-action {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            color: white;
        }

        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #d0d0d0;
            color: #333;
        }
    </style>
</head>

<body>
    <div class="containers">
        <?php include 'inc/php/social_navbar.php'; ?>
        <?php include 'inc/php/social_sidebar.php'; ?>

        <div class="search-main-content main-content">
            <a href="my_complaints.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Complaints</a>

            <!-- Complaint Header -->
            <div class="complaint-header">
                <h1><?php echo htmlspecialchars($complaint['title']); ?></h1>

                <div class="ticket-meta">
                    <div class="meta-item">
                        <span class="meta-label">Ticket ID:</span>
                        <span class="meta-value"><?php echo htmlspecialchars($complaint['ticket_id']); ?></span>
                    </div>
                    <div class="badges-group">
                        <span class="badge bg-<?php echo getStatusBadge($complaint['status']); ?>">
                            <i class="fas fa-check-circle"></i>
                            <?php echo ucfirst(str_replace('-', ' ', $complaint['status'])); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Submitted Date Section -->
            <div class="section-card">
                <h2 class="section-title">
                    <i class="fas fa-info-circle"></i> Information
                </h2>

                <div class="info-grid">
                    <div class="info-box">
                        <div class="info-box-label">Submitted On</div>
                        <div class="info-box-value"><?php echo formatDate($complaint['created_at']); ?></div>
                    </div>
                    <div class="info-box">
                        <div class="info-box-label">Days Open</div>
                        <div class="info-box-value"><?php echo getDaysOpen($complaint['created_at']); ?> days</div>
                    </div>
                    <div class="info-box">
                        <div class="info-box-label">Pincode</div>
                        <div class="info-box-value">
                            <code style="background: #f0f0f0; padding: 4px 8px; border-radius: 4px;">
                                <?php echo htmlspecialchars($complaint['pincode'] ?? 'N/A'); ?>
                            </code>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Description Section -->
            <div class="section-card">
                <h2 class="section-title">
                    <i class="fas fa-align-left"></i> Description
                </h2>
                <div class="description-text">
                    <?php echo htmlspecialchars($complaint['description']); ?>
                </div>

                <?php if (!empty($complaint['media_url'])): ?>
                    <div style="margin-top: 20px;">
                        <div style="color: #999; font-size: 12px; margin-bottom: 10px;">
                            <i class="fas fa-paperclip"></i> Attached Media
                        </div>
                        <?php if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $complaint['media_url'])): ?>
                            <img src="<?php echo htmlspecialchars($complaint['media_url']); ?>" alt="Complaint attachment" style="max-width: 100%; max-height: 400px; border-radius: 8px;">
                        <?php elseif (preg_match('/\.(mp4|webm|ogg|mov)$/i', $complaint['media_url'])): ?>
                            <video width="100%" controls style="border-radius: 8px; max-height: 400px;">
                                <source src="<?php echo htmlspecialchars($complaint['media_url']); ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        <?php elseif (preg_match('/\.pdf$/i', $complaint['media_url'])): ?>
                            <a href="<?php echo htmlspecialchars($complaint['media_url']); ?>" target="_blank" class="btn btn-primary">
                                <i class="fas fa-file-pdf"></i> Download PDF
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Admin Response Section -->
            <?php if (!empty($complaint['admin_remark'])): ?>
                <div class="section-card">
                    <h2 class="section-title">
                        <i class="fas fa-reply"></i> Admin Remarks
                    </h2>
                    <div class="response-section">
                        <div class="response-label">Message from our team:</div>
                        <div class="response-text">
                            <?php echo htmlspecialchars($complaint['admin_remark']); ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="section-card">
                    <h2 class="section-title">
                        <i class="fas fa-reply"></i> Admin Remarks
                    </h2>
                    <div class="no-response">
                        No remarks yet. Our team is reviewing your complaint.
                    </div>
                </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="section-card">
                <div class="action-buttons">
                    <a href="my_complaints.php" class="btn-action btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                    <a href="complaint_form.php" class="btn-action btn-primary">
                        <i class="fas fa-plus"></i> Submit Another Complaint
                    </a>
                </div>
            </div>
            <?php include 'inc/php/footer.php'; ?>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>