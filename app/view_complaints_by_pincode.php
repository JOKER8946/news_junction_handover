<?php
include './inc/php/db_config.php';
include './inc/php/validate.logged.php';
include './inc/php/function.php';

// Check if user is admin
if ($gUserId != 18) {  // Change to your admin ID
    header('Location: my_complaints.php');
    exit;
}

// Get all unique pincodes with complaint counts
$sql = "SELECT 
            pincode,
            COUNT(*) as total_complaints,
            COUNT(CASE WHEN status = 'open' THEN 1 END) as open_complaints,
            COUNT(CASE WHEN status = 'in-progress' THEN 1 END) as in_progress_complaints,
            COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_complaints,
            COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_complaints,
            MAX(created_at) as latest_complaint
        FROM complaints
        WHERE pincode IS NOT NULL
        GROUP BY pincode
        ORDER BY latest_complaint DESC";

$result = $creamdb->query($sql);
$pincode_groups = $result->fetch_all(MYSQLI_ASSOC);

// Get selected pincode if any
$selectedPincode = isset($_GET['pincode']) ? trim($_GET['pincode']) : '';
$selectedComplaints = [];

if (!empty($selectedPincode)) {
    $sql_detail = "SELECT c.*, u.full_name, u.email
                   FROM complaints c
                   JOIN user u ON c.user_id = u.id
                   WHERE c.pincode = ?
                   ORDER BY c.created_at DESC";
    
    $stmt = $creamdb->prepare($sql_detail);
    $stmt->bind_param("s", $selectedPincode);
    $stmt->execute();
    $result_detail = $stmt->get_result();
    $selectedComplaints = $result_detail->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

function getStatusBadge($status) {
    $colors = [
        'open' => 'danger',
        'in-progress' => 'warning',
        'resolved' => 'success',
        'closed' => 'secondary'
    ];
    return $colors[$status] ?? 'secondary';
}

function formatDate($date) {
    return date('M d, Y H:i', strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaints by Pincode - Admin</title>
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
        .pincode-view-container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .page-header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .back-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .back-link:hover {
            background: #f0f0f0;
            text-decoration: none;
        }

        .main-content-wrapper {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 30px;
            align-items: start;
        }

        .pincode-list {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .pincode-list-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            font-weight: 700;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .pincode-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pincode-item:hover {
            background: #f8f9fa;
        }

        .pincode-item.active {
            background: #f0f4ff;
            border-left: 4px solid #667eea;
            padding-left: 16px;
        }

        .pincode-item-code {
            font-weight: 700;
            font-size: 16px;
            color: #333;
            font-family: 'Courier New', monospace;
            margin-bottom: 4px;
        }

        .pincode-item-count {
            font-size: 12px;
            color: #999;
        }

        .pincode-item-meta {
            text-align: right;
            font-size: 12px;
        }

        .meta-count {
            background: #e8e8e8;
            color: #333;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 4px;
        }

        .meta-status {
            display: flex;
            gap: 4px;
            margin-top: 4px;
            flex-direction: column;
            align-items: flex-end;
        }

        .status-badge-small {
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: 600;
        }

        .pincode-detail {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            padding: 30px;
        }

        .pincode-detail-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .detail-title {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin: 0 0 15px 0;
            font-family: 'Courier New', monospace;
        }

        .detail-stats {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .stat-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 10px 20px;
            background: #f8f9fa;
            border-radius: 8px;
            text-align: center;
        }

        .stat-value {
            font-weight: 700;
            font-size: 24px;
            color: #667eea;
        }

        .stat-label {
            font-size: 12px;
            color: #999;
            margin-top: 4px;
        }

        .complaints-list {
            margin-top: 30px;
        }

        .complaint-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .complaint-row:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transform: translateX(4px);
        }

        .complaint-info {
            flex: 1;
        }

        .complaint-title {
            font-weight: 600;
            color: #333;
            margin: 0 0 4px 0;
            font-size: 15px;
        }

        .complaint-meta {
            font-size: 12px;
            color: #999;
        }

        .complaint-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .action-btn {
            padding: 6px 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }

        .action-btn:hover {
            background: #5568d3;
            color: white;
            text-decoration: none;
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

        .empty-message {
            color: #666;
            margin-bottom: 10px;
        }

        @media (max-width: 1024px) {
            .main-content-wrapper {
                grid-template-columns: 1fr;
            }

            .pincode-list {
                max-height: 400px;
                overflow-y: auto;
            }
        }
    </style>
</head>

<body>
    <div class="containers">
        <?php include 'inc/php/social_navbar.php'; ?>
        <?php include 'inc/php/social_sidebar.php'; ?>

        <div class="search-main-content main-content">
            <div class="pincode-view-container">
                <div class="page-header">
                    <h1>
                        <i class="fas fa-th-list"></i>
                        Complaints by Pincode
                    </h1>
                    <a href="admin_complaints.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> Back to All Complaints
                    </a>
                </div>

                <div class="main-content-wrapper">
                    <!-- Pincode List -->
                    <div class="pincode-list">
                        <div class="pincode-list-header">
                            <i class="fas fa-map-pin"></i>
                            Pincodes (<?php echo count($pincode_groups); ?>)
                        </div>

                        <?php if (empty($pincode_groups)): ?>
                            <div style="padding: 20px; text-align: center; color: #999;">
                                No complaints yet
                            </div>
                        <?php else: ?>
                            <?php foreach ($pincode_groups as $group): ?>
                                <a href="?pincode=<?php echo urlencode($group['pincode']); ?>" 
                                   style="text-decoration: none; color: inherit;">
                                    <div class="pincode-item <?php echo ($selectedPincode == $group['pincode']) ? 'active' : ''; ?>">
                                        <div>
                                            <div class="pincode-item-code">
                                                <?php echo htmlspecialchars($group['pincode']); ?>
                                            </div>
                                            <div class="pincode-item-count">
                                                <?php echo $group['total_complaints']; ?> complaint<?php echo $group['total_complaints'] != 1 ? 's' : ''; ?>
                                            </div>
                                        </div>
                                        <div class="pincode-item-meta">
                                            <div class="meta-count">
                                                <?php echo $group['total_complaints']; ?>
                                            </div>
                                            <div class="meta-status">
                                                <?php if ($group['open_complaints'] > 0): ?>
                                                    <span class="status-badge-small bg-danger">
                                                        Open: <?php echo $group['open_complaints']; ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($group['in_progress_complaints'] > 0): ?>
                                                    <span class="status-badge-small bg-warning">
                                                        Progress: <?php echo $group['in_progress_complaints']; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Pincode Details -->
                    <div class="pincode-detail">
                        <?php if (empty($selectedPincode)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="fas fa-hand-point-left"></i>
                                </div>
                                <div class="empty-message">Select a pincode from the list to view complaints</div>
                            </div>
                        <?php else: ?>
                            <div class="pincode-detail-header">
                                <div class="detail-title">
                                    <i class="fas fa-map-pin"></i> <?php echo htmlspecialchars($selectedPincode); ?>
                                </div>
                                <div class="detail-stats">
                                    <div class="stat-box">
                                        <div class="stat-value"><?php echo count($selectedComplaints); ?></div>
                                        <div class="stat-label">Total Complaints</div>
                                    </div>
                                    <div class="stat-box">
                                        <div class="stat-value" style="color: #e74c3c;">
                                            <?php echo count(array_filter($selectedComplaints, fn($c) => $c['status'] == 'open')); ?>
                                        </div>
                                        <div class="stat-label">Open</div>
                                    </div>
                                    <div class="stat-box">
                                        <div class="stat-value" style="color: #f39c12;">
                                            <?php echo count(array_filter($selectedComplaints, fn($c) => $c['status'] == 'in-progress')); ?>
                                        </div>
                                        <div class="stat-label">In Progress</div>
                                    </div>
                                    <div class="stat-box">
                                        <div class="stat-value" style="color: #27ae60;">
                                            <?php echo count(array_filter($selectedComplaints, fn($c) => $c['status'] == 'resolved')); ?>
                                        </div>
                                        <div class="stat-label">Resolved</div>
                                    </div>
                                </div>
                            </div>

                            <?php if (empty($selectedComplaints)): ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="empty-message">No complaints for this pincode</div>
                                </div>
                            <?php else: ?>
                                <div class="complaints-list">
                                    <?php foreach ($selectedComplaints as $complaint): ?>
                                        <div class="complaint-row">
                                            <div class="complaint-info">
                                                <h4 class="complaint-title">
                                                    <?php echo htmlspecialchars(substr($complaint['title'], 0, 60)); ?>
                                                    <?php if (strlen($complaint['title']) > 60) echo '...'; ?>
                                                </h4>
                                                <div class="complaint-meta">
                                                    <strong><?php echo htmlspecialchars($complaint['full_name']); ?></strong>
                                                    (<span><?php echo htmlspecialchars($complaint['email']); ?></span>)
                                                    • Ticket: <?php echo htmlspecialchars($complaint['ticket_id']); ?>
                                                    • <?php echo formatDate($complaint['created_at']); ?>
                                                </div>
                                            </div>
                                            <div class="complaint-actions">
                                                <span class="badge bg-<?php echo getStatusBadge($complaint['status']); ?>">
                                                    <?php echo ucfirst(str_replace('-', ' ', $complaint['status'])); ?>
                                                </span>
                                                <a href="admin_complaint_detail.php?id=<?php echo $complaint['id']; ?>" 
                                                   class="action-btn">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php include 'inc/php/footer.php'; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
