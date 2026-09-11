<?php
require_once 'inc/php/validate.logged.php';
require_once 'inc/php/db_config.php';

// Check if user is admin (adjust role check based on your schema)
$isAdmin = false;
global $gUserId, $creamdb;
if (!empty($gUserId) && isset($creamdb)) {
    $userId = intval($gUserId);
    $stmt = $creamdb->prepare("SELECT role FROM nj_cream.user WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $row = $res->fetch_assoc()) {
            $isAdmin = ($row['role'] === 'admin');
        }
        $stmt->close();
    }
}

if (!$isAdmin) {
    header('Location: /');
    exit;
}

// Get database connection
function getAdDb() {
    global $readerdb;
    if (isset($readerdb) && $readerdb) return $readerdb;
    return null;
}

$db = getAdDb();
if (!$db) {
    die('Database connection error');
}

// Handle AJAX get_ad request EARLY (before HTML output)
if (isset($_GET['get_ad'])) {
    header('Content-Type: application/json');
    $ad_id = intval($_GET['get_ad']);
    $result = $db->query("SELECT * FROM nj_reader.ads WHERE id=$ad_id LIMIT 1");
    if ($result && $row = $result->fetch_assoc()) {
        echo json_encode($row);
    } else {
        echo json_encode([]);
    }
    exit;
}

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'create' || $action == 'update') {
        $uploadError = '';
        $title = $db->real_escape_string($_POST['title'] ?? '');
        $description = $db->real_escape_string($_POST['description'] ?? '');
        $ad_link = $db->real_escape_string($_POST['ad_link'] ?? '');
        $ad_type = ($_POST['ad_type'] ?? 'image') === 'video' ? 'video' : 'image';
        // Position and page are now arrays from checkboxes, store as comma-separated
        $positionArr = isset($_POST['position']) && is_array($_POST['position']) ? $_POST['position'] : ['feed'];
        $positionArr = array_map(function($v) use ($db) { return $db->real_escape_string($v); }, $positionArr);
        $position = implode(',', $positionArr);

        $pageArr = isset($_POST['page']) && is_array($_POST['page']) ? $_POST['page'] : ['all'];
        $pageArr = array_map(function($v) use ($db) { return $db->real_escape_string($v); }, $pageArr);
        $page = implode(',', $pageArr);
        $is_active = intval($_POST['is_active'] ?? 1);
        $start_date = !empty($_POST['start_date']) ? $db->real_escape_string(str_replace('T', ' ', $_POST['start_date'])) : 'NULL';
        $end_date = !empty($_POST['end_date']) ? $db->real_escape_string(str_replace('T', ' ', $_POST['end_date'])) : 'NULL';
        $image_url = $_POST['image_url'] ?? '';
        $video_url = $_POST['video_url'] ?? '';

        // Handle image upload with stronger validation
        $imgErrCode = isset($_FILES['image']) ? (int)$_FILES['image']['error'] : UPLOAD_ERR_NO_FILE;
        if ($imgErrCode !== UPLOAD_ERR_OK && $imgErrCode !== UPLOAD_ERR_NO_FILE) {
            $codes = [
                1 => 'Image exceeds upload_max_filesize (server limit)',
                2 => 'Image exceeds form MAX_FILE_SIZE',
                3 => 'Image upload was interrupted',
                6 => 'Server temp folder missing',
                7 => 'Server could not write image to disk',
                8 => 'Upload blocked by PHP extension',
            ];
            $uploadError = $codes[$imgErrCode] ?? ('Image upload failed (code ' . $imgErrCode . ')');
        }
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'png' => 'image/png'];
            $filename = $_FILES['image']['name'];
            $filesize = $_FILES['image']['size'];
            $tmpPath = $_FILES['image']['tmp_name'];

            // Check file size (max 5MB)
            if ($filesize > 5242880) {
                $uploadError = 'Image must be less than 5MB';
            } else {
                // Use finfo and getimagesize for reliable mime detection
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = $finfo ? finfo_file($finfo, $tmpPath) : mime_content_type($tmpPath);
                if ($finfo) finfo_close($finfo);

                $imageInfo = @getimagesize($tmpPath);
                if (!$imageInfo) {
                    $uploadError = 'Uploaded file is not a valid image';
                } else {
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    if (!array_key_exists($ext, $allowed) || $allowed[$ext] !== $mime) {
                        $uploadError = 'Invalid file type. Only JPG, PNG, GIF allowed';
                    } else {
                        $uploadDir = __DIR__ . '/uploads/ads/';
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                        // Generate secure filename
                        try {
                            $rand = bin2hex(random_bytes(6));
                        } catch (Exception $e) {
                            $rand = uniqid();
                        }
                        $newFilename = time() . '_' . $rand . '.' . $ext;
                        $filepath = $uploadDir . $newFilename;

                        if (move_uploaded_file($tmpPath, $filepath)) {
                            $image_url = 'uploads/ads/' . $newFilename;
                        } else {
                            $uploadError = 'Failed to upload image. Check folder permissions';
                        }
                    }
                }
            }
        }

        // Handle video upload
        error_log("AD DEBUG: ad_type=$ad_type, video isset=" . (isset($_FILES['video']) ? 'yes' : 'no') . ", video error=" . ($_FILES['video']['error'] ?? 'N/A'));
        if ($ad_type === 'video' && isset($_FILES['video']) && $_FILES['video']['error'] == 0) {
            $allowedVideo = ['mp4' => 'video/mp4', 'webm' => 'video/webm'];
            $vFilename = $_FILES['video']['name'];
            $vFilesize = $_FILES['video']['size'];
            $vTmpPath = $_FILES['video']['tmp_name'];

            // Max 20MB for video
            if ($vFilesize > 20971520) {
                $uploadError = 'Video must be less than 20MB';
            } else {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $vMime = $finfo ? finfo_file($finfo, $vTmpPath) : mime_content_type($vTmpPath);
                if ($finfo) finfo_close($finfo);

                $vExt = strtolower(pathinfo($vFilename, PATHINFO_EXTENSION));
                error_log("AD DEBUG: vExt=$vExt, vMime=$vMime, vFilename=$vFilename, vFilesize=$vFilesize");
                if (!array_key_exists($vExt, $allowedVideo) || $allowedVideo[$vExt] !== $vMime) {
                    $uploadError = 'Invalid video type. Only MP4, WEBM allowed';
                } else {
                    $uploadDir = __DIR__ . '/uploads/ads/videos/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                    try {
                        $rand = bin2hex(random_bytes(6));
                    } catch (Exception $e) {
                        $rand = uniqid();
                    }
                    $newVideoFilename = time() . '_' . $rand . '.' . $vExt;
                    $vFilepath = $uploadDir . $newVideoFilename;

                    if (move_uploaded_file($vTmpPath, $vFilepath)) {
                        $video_url = 'uploads/ads/videos/' . $newVideoFilename;
                    } else {
                        $uploadError = 'Failed to upload video. Check folder permissions';
                    }
                }
            }
        }

        $image_url = $db->real_escape_string($image_url);
        $video_url = $db->real_escape_string($video_url);
        $start_date_sql = ($start_date == 'NULL') ? 'NULL' : "'$start_date'";
        $end_date_sql = ($end_date == 'NULL') ? 'NULL' : "'$end_date'";

        // Block save when required media is missing — don't insert a row with empty image_url
        if ($ad_type === 'image' && $image_url === '' && $action === 'create') {
            $message = $uploadError !== '' ? $uploadError : 'Please select an image for the ad.';
            $messageType = 'error';
            goto end_create_update;
        }
        if ($ad_type === 'video' && $video_url === '' && $action === 'create') {
            $message = $uploadError !== '' ? $uploadError : 'Please select a video for the ad.';
            $messageType = 'error';
            goto end_create_update;
        }

        if ($action == 'create') {
            $creator = intval($gUserId);
            $sql = "INSERT INTO nj_reader.ads (title, description, image_url, ad_type, video_url, ad_link, position, page, is_active, start_date, end_date, created_by)
                    VALUES ('$title', '$description', '$image_url', '$ad_type', '$video_url', '$ad_link', '$position', '$page', $is_active, $start_date_sql, $end_date_sql, $creator)";
            if ($db->query($sql)) {
                $message = 'Ad created successfully!';
                $messageType = 'success';
            } else {
                $message = 'Error: ' . $db->error;
                $messageType = 'error';
            }
        } else {
            $ad_id = intval($_POST['ad_id']);
            $sql = "UPDATE nj_reader.ads SET title='$title', description='$description', image_url='$image_url',
                    ad_type='$ad_type', video_url='$video_url',
                    ad_link='$ad_link', position='$position', page='$page', is_active=$is_active,
                    start_date=$start_date_sql, end_date=$end_date_sql WHERE id=$ad_id";
            if ($db->query($sql)) {
                $message = 'Ad updated successfully!';
                $messageType = 'success';
            } else {
                $message = 'Error: ' . $db->error;
                $messageType = 'error';
            }
        }

        // If the DB write succeeded but the file upload had failed mid-flow,
        // surface the upload warning so the user knows the image/video is missing.
        if ($messageType === 'success' && $uploadError !== '') {
            $message .= ' (Warning: ' . $uploadError . ')';
            $messageType = 'error';
        }

        end_create_update:
    } elseif ($action == 'delete') {
        $ad_id = intval($_POST['ad_id']);
        // Use prepared statements for delete
        $stmt = $db->prepare("DELETE FROM nj_reader.ads WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $ad_id);
            if ($stmt->execute()) {
                $stmt->close();
                $stmt2 = $db->prepare("DELETE FROM nj_reader.ad_analytics WHERE ad_id = ?");
                if ($stmt2) {
                    $stmt2->bind_param('i', $ad_id);
                    $stmt2->execute();
                    $stmt2->close();
                }
                $message = 'Ad deleted successfully!';
                $messageType = 'success';
            } else {
                $message = 'Error: ' . $stmt->error;
                $messageType = 'error';
                $stmt->close();
            }
        } else {
            $message = 'Error: ' . $db->error;
            $messageType = 'error';
        }
    }
}

// Get ads
$ads = [];
$result = $db->query("SELECT * FROM nj_reader.ads ORDER BY created_at DESC");
if ($result) {
    $ads = $result->fetch_all(MYSQLI_ASSOC);
}

// Get ad for editing
$editAd = null;
if (isset($_GET['edit'])) {
    $ad_id = intval($_GET['edit']);
    $result = $db->query("SELECT * FROM nj_reader.ads WHERE id=$ad_id LIMIT 1");
    if ($result) {
        $editAd = $result->fetch_assoc();
    }
}

// Get analytics for an ad
function getAdAnalytics($ad_id) {
    global $db;
    // Use prepared statement to avoid injection
    $impressions = 0;
    $clicks = 0;
    $stmt = $db->prepare("SELECT 
            COUNT(CASE WHEN action='impression' THEN 1 END) as impressions,
            COUNT(CASE WHEN action='click' THEN 1 END) as clicks
            FROM nj_reader.ad_analytics WHERE ad_id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $ad_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $row = $res->fetch_assoc()) {
            $impressions = $row['impressions'] ?? 0;
            $clicks = $row['clicks'] ?? 0;
        }
        $stmt->close();
    }
    return ['impressions' => intval($impressions), 'clicks' => intval($clicks)];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Ads - News Junction</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f5f5;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .header h1 {
            margin-bottom: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            font-size: 14px;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.4);
        }

        .btn-danger {
            background: #ff6b6b;
            color: white;
        }

        .btn-danger:hover {
            background: #ee5a52;
        }

        .btn-success {
            background: #51cf66;
            color: white;
        }

        .btn-success:hover {
            background: #40c057;
        }

        .btn-warning {
            background: #ffd43b;
            color: #333;
        }

        .btn-warning:hover {
            background: #ffca3d;
        }

        .btn-secondary {
            background: #999;
            color: white;
        }

        .btn-secondary:hover {
            background: #888;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            display: none;
        }

        .message.show {
            display: block;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .controls {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .modal-header h2 {
            margin: 0;
            color: #667eea;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #fafafa;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 20px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
            user-select: none;
        }

        .checkbox-label:hover {
            border-color: #667eea;
            background: #f0f3ff;
        }

        .checkbox-label input[type="checkbox"] {
            width: auto;
            margin: 0;
            accent-color: #667eea;
        }

        .checkbox-label:has(input:checked) {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .ads-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .ad-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border-left: 4px solid #667eea;
        }

        .ad-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }

        .ad-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .ad-card h3 {
            margin-bottom: 8px;
            color: #667eea;
            font-size: 16px;
        }

        .ad-card p {
            color: #666;
            font-size: 13px;
            margin-bottom: 12px;
            line-height: 1.4;
        }

        .ad-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
            font-size: 12px;
            background: #f9f9f9;
            padding: 10px;
            border-radius: 5px;
        }

        .meta-item {
            display: flex;
            justify-content: space-between;
        }

        .meta-label {
            color: #999;
            font-weight: 500;
        }

        .meta-value {
            color: #667eea;
            font-weight: 600;
        }

        .ad-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .ad-actions {
            display: flex;
            gap: 8px;
        }

        .ad-actions button {
            flex: 1;
            padding: 8px 12px;
            font-size: 12px;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background: white;
            border-radius: 10px;
            color: #999;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .ads-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-advertisement"></i> Advertisement Manager</h1>
            <p>Create, edit, and track your ads</p>
        </div>

        <div id="message" class="message"></div>

        <div class="controls">
            <button class="btn btn-primary" onclick="openCreateModal()">
                <i class="fas fa-plus"></i> New Ad
            </button>
            <a href="/" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>

        <?php if (!empty($ads)): ?>
            <div class="ads-grid">
                <?php foreach ($ads as $ad): 
                    $analytics = getAdAnalytics($ad['id']);
                    $ctr = $analytics['impressions'] > 0 ? round(($analytics['clicks'] / $analytics['impressions']) * 100, 2) : 0;
                ?>
                    <div class="ad-card">
                        <?php if (($ad['ad_type'] ?? 'image') === 'video' && !empty($ad['video_url'])): ?>
                            <video src="<?php echo htmlspecialchars($ad['video_url']); ?>" style="width:100%;height:150px;object-fit:cover;border-radius:5px;margin-bottom:15px;" muted preload="metadata"></video>
                            <span style="position:absolute;top:25px;left:25px;background:#667eea;color:#fff;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:600;">VIDEO</span>
                        <?php elseif (!empty($ad['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($ad['image_url']); ?>" alt="<?php echo htmlspecialchars($ad['title']); ?>" onerror="this.src='/data/placeholder.jpg'">
                        <?php endif; ?>

                        <div class="ad-status <?php echo $ad['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo $ad['is_active'] ? '✓ Active' : '✗ Inactive'; ?>
                        </div>

                        <h3><?php echo htmlspecialchars($ad['title']); ?></h3>

                        <?php if (!empty($ad['description'])): ?>
                            <p><?php echo htmlspecialchars(substr($ad['description'], 0, 80)) . (strlen($ad['description']) > 80 ? '...' : ''); ?></p>
                        <?php endif; ?>

                        <div class="ad-meta">
                            <div class="meta-item">
                                <span class="meta-label">Type:</span>
                                <span class="meta-value"><?php echo ucfirst($ad['ad_type'] ?? 'image'); ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Position:</span>
                                <span class="meta-value"><?php echo htmlspecialchars(implode(', ', array_map('ucfirst', explode(',', str_replace('_', ' ', $ad['position']))))); ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Page:</span>
                                <span class="meta-value"><?php echo htmlspecialchars(implode(', ', array_map('ucfirst', explode(',', $ad['page'] ?? 'all')))); ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Impressions:</span>
                                <span class="meta-value"><?php echo number_format($analytics['impressions']); ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Clicks:</span>
                                <span class="meta-value"><?php echo number_format($analytics['clicks']); ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">CTR:</span>
                                <span class="meta-value"><?php echo $ctr . '%'; ?></span>
                            </div>
                        </div>

                        <div class="ad-actions">
                            <button class="btn btn-warning" onclick="editAd(<?php echo $ad['id']; ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-danger" onclick="deleteAd(<?php echo $ad['id']; ?>, '<?php echo htmlspecialchars($ad['title']); ?>')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>No Ads Yet</h3>
                <p>Create your first ad to get started</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Create/Edit Modal -->
    <div class="modal" id="adModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Create New Ad</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form id="adForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" id="action" name="action" value="create">
                <input type="hidden" id="adId" name="ad_id" value="">

                <div class="form-group">
                    <label for="title">Ad Title *</label>
                    <input type="text" id="title" name="title" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"></textarea>
                </div>

                <div class="form-group">
                    <label>Ad Type *</label>
                    <div style="display:flex;gap:15px;margin-top:5px;">
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;padding:8px 16px;border:2px solid #ddd;border-radius:8px;transition:all 0.2s;" id="typeImageLabel">
                            <input type="radio" name="ad_type" value="image" checked onchange="toggleAdType()" style="accent-color:#667eea;">
                            <i class="fas fa-image"></i> Image
                        </label>
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;padding:8px 16px;border:2px solid #ddd;border-radius:8px;transition:all 0.2s;" id="typeVideoLabel">
                            <input type="radio" name="ad_type" value="video" onchange="toggleAdType()" style="accent-color:#667eea;">
                            <i class="fas fa-video"></i> Video
                        </label>
                    </div>
                </div>

                <div class="form-group" id="imageUploadGroup">
                    <label for="image">Image *</label>
                    <input type="file" id="image" name="image" accept="image/*">
                    <small style="color: #999;">Max 5MB. Formats: JPG, PNG, GIF</small>
                    <div id="imagePreview" style="margin-top:10px; display:none;">
                        <img id="previewImg" src="" alt="Preview" style="max-width:200px; max-height:150px; border-radius:4px;">
                        <p style="font-size:12px; color:#999; margin-top:5px;">Current image</p>
                    </div>
                    <input type="hidden" id="imageUrl" name="image_url" value="">
                </div>

                <div class="form-group" id="videoUploadGroup" style="display:none;">
                    <label for="video">Video *</label>
                    <input type="file" id="video" name="video" accept="video/mp4,video/webm">
                    <small style="color: #999;">Max 20MB. Formats: MP4, WEBM. Duration: max 30 seconds</small>
                    <div id="videoPreview" style="margin-top:10px; display:none;">
                        <video id="previewVideo" src="" style="max-width:300px; max-height:200px; border-radius:4px;" controls muted></video>
                        <p style="font-size:12px; color:#999; margin-top:5px;">Current video</p>
                    </div>
                    <div id="videoDurationError" style="display:none; margin-top:8px; padding:8px 12px; background:#f8d7da; color:#721c24; border-radius:5px; font-size:13px;">
                        <i class="fas fa-exclamation-triangle"></i> Video must be 30 seconds or less
                    </div>
                    <div id="videoDurationInfo" style="display:none; margin-top:8px; padding:8px 12px; background:#d4edda; color:#155724; border-radius:5px; font-size:13px;">
                        <i class="fas fa-check-circle"></i> Duration: <span id="videoDurationText"></span>
                    </div>
                    <input type="hidden" id="videoUrl" name="video_url" value="">
                </div>

                <div class="form-group">
                    <label for="adLink">Ad Link (Destination URL) *</label>
                    <input type="url" id="adLink" name="ad_link" required placeholder="https://example.com">
                </div>

                <div class="form-group">
                    <label>Position * <small style="color:#999; font-weight:normal;">(select one or more)</small></label>
                    <div class="checkbox-group" id="positionGroup">
                        <label class="checkbox-label"><input type="checkbox" name="position[]" value="top"> Top Banner</label>
                        <label class="checkbox-label"><input type="checkbox" name="position[]" value="feed"> Feed</label>
                        <label class="checkbox-label"><input type="checkbox" name="position[]" value="left_vertical"> Left Vertical</label>
                        <label class="checkbox-label"><input type="checkbox" name="position[]" value="right_vertical"> Right Vertical</label>
                        <label class="checkbox-label"><input type="checkbox" name="position[]" value="horizontal"> Horizontal</label>
                        <label class="checkbox-label"><input type="checkbox" name="position[]" value="scroll"> Scroll</label>
                        <label class="checkbox-label"><input type="checkbox" name="position[]" value="sticky_bottom"> Sticky Bottom</label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Page * <small style="color:#999; font-weight:normal;">(select one or more)</small></label>
                    <div class="checkbox-group" id="pageGroup">
                        <label class="checkbox-label"><input type="checkbox" name="page[]" value="all"> All Pages</label>
                        <label class="checkbox-label"><input type="checkbox" name="page[]" value="view"> View Page</label>
                        <label class="checkbox-label"><input type="checkbox" name="page[]" value="home"> Home Page</label>
                        <label class="checkbox-label"><input type="checkbox" name="page[]" value="stream"> Stream</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="isActive">Status</label>
                    <select id="isActive" name="is_active">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="startDate">Start Date</label>
                        <input type="datetime-local" id="startDate" name="start_date">
                    </div>

                    <div class="form-group">
                        <label for="endDate">End Date</label>
                        <input type="datetime-local" id="endDate" name="end_date">
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Ad</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        var videoValid = true;

        function toggleAdType() {
            var isVideo = document.querySelector('input[name="ad_type"][value="video"]').checked;
            document.getElementById('imageUploadGroup').style.display = isVideo ? 'none' : 'block';
            document.getElementById('videoUploadGroup').style.display = isVideo ? 'block' : 'none';

            // Style the selected type label
            document.getElementById('typeImageLabel').style.borderColor = isVideo ? '#ddd' : '#667eea';
            document.getElementById('typeImageLabel').style.background = isVideo ? '#fff' : '#f0f3ff';
            document.getElementById('typeVideoLabel').style.borderColor = isVideo ? '#667eea' : '#ddd';
            document.getElementById('typeVideoLabel').style.background = isVideo ? '#f0f3ff' : '#fff';
        }

        function openCreateModal() {
            document.getElementById('action').value = 'create';
            document.getElementById('modalTitle').textContent = 'Create New Ad';
            document.getElementById('adForm').reset();
            document.getElementById('adId').value = '';
            // Uncheck all checkboxes
            document.querySelectorAll('#positionGroup input, #pageGroup input').forEach(cb => cb.checked = false);
            document.getElementById('imagePreview').style.display = 'none';
            document.getElementById('videoPreview').style.display = 'none';
            document.getElementById('videoDurationError').style.display = 'none';
            document.getElementById('videoDurationInfo').style.display = 'none';
            videoValid = true;
            toggleAdType();
            document.getElementById('adModal').classList.add('active');
        }

        function editAd(adId) {
            // Fetch ad data via AJAX
            fetch('ads.php?get_ad=' + adId)
                .then(r => r.json())
                .then(ad => {
                    document.getElementById('action').value = 'update';
                    document.getElementById('modalTitle').textContent = 'Edit Ad';
                    document.getElementById('adId').value = ad.id;
                    document.getElementById('title').value = ad.title;
                    document.getElementById('description').value = ad.description || '';
                    document.getElementById('adLink').value = ad.ad_link || '';

                    // Set position checkboxes
                    var positions = (ad.position || 'feed').split(',');
                    document.querySelectorAll('#positionGroup input').forEach(cb => {
                        cb.checked = positions.indexOf(cb.value) !== -1;
                    });

                    // Set page checkboxes
                    var pages = (ad.page || 'all').split(',');
                    document.querySelectorAll('#pageGroup input').forEach(cb => {
                        cb.checked = pages.indexOf(cb.value) !== -1;
                    });

                    document.getElementById('isActive').value = ad.is_active;
                    document.getElementById('startDate').value = ad.start_date ? ad.start_date.replace(' ', 'T') : '';
                    document.getElementById('endDate').value = ad.end_date ? ad.end_date.replace(' ', 'T') : '';
                    document.getElementById('imageUrl').value = ad.image_url || '';
                    document.getElementById('videoUrl').value = ad.video_url || '';

                    // Set ad type
                    var adType = ad.ad_type || 'image';
                    document.querySelector('input[name="ad_type"][value="' + adType + '"]').checked = true;
                    toggleAdType();

                    // Show preview of current image
                    if (ad.image_url) {
                        document.getElementById('previewImg').src = ad.image_url;
                        document.getElementById('imagePreview').style.display = 'block';
                    } else {
                        document.getElementById('imagePreview').style.display = 'none';
                    }

                    // Show preview of current video
                    if (ad.video_url) {
                        document.getElementById('previewVideo').src = '/' + ad.video_url;
                        document.getElementById('videoPreview').style.display = 'block';
                    } else {
                        document.getElementById('videoPreview').style.display = 'none';
                    }

                    // Clear file inputs
                    document.getElementById('image').value = '';
                    document.getElementById('video').value = '';
                    document.getElementById('videoDurationError').style.display = 'none';
                    document.getElementById('videoDurationInfo').style.display = 'none';
                    videoValid = true;
                    document.getElementById('adModal').classList.add('active');
                });
        }

        function closeModal() {
            document.getElementById('adModal').classList.remove('active');
        }

        function deleteAd(adId, title) {
            if (confirm('Are you sure you want to delete "' + title + '"?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="ad_id" value="${adId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        document.getElementById('adForm').addEventListener('submit', function(e) {
            e.preventDefault();

            // Validate at least one position checked
            var posChecked = document.querySelectorAll('#positionGroup input:checked').length;
            if (posChecked === 0) {
                alert('Please select at least one Position');
                return;
            }

            // Validate at least one page checked
            var pageChecked = document.querySelectorAll('#pageGroup input:checked').length;
            if (pageChecked === 0) {
                alert('Please select at least one Page');
                return;
            }

            // Check video validation
            var isVideo = document.querySelector('input[name="ad_type"][value="video"]').checked;
            if (isVideo && document.getElementById('video').files.length > 0 && !videoValid) {
                alert('Video must be 30 seconds or less');
                return;
            }

            // If no new file selected, keep old one
            if (!document.getElementById('image').files.length && document.getElementById('imageUrl').value) {
                // imageUrl already set, submit as is
            }
            this.submit();
        });

        // Handle image file selection and preview
        document.getElementById('image').addEventListener('change', function(e) {
            if (this.files.length > 0) {
                const file = this.files[0];
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImg').src = e.target.result;
                    document.getElementById('imagePreview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });

        // Handle video file selection, preview, and duration validation
        document.getElementById('video').addEventListener('change', function(e) {
            videoValid = false;
            document.getElementById('videoDurationError').style.display = 'none';
            document.getElementById('videoDurationInfo').style.display = 'none';

            if (this.files.length > 0) {
                const file = this.files[0];
                const url = URL.createObjectURL(file);
                const videoEl = document.createElement('video');
                videoEl.preload = 'metadata';
                videoEl.onloadedmetadata = function() {
                    URL.revokeObjectURL(url);
                    var duration = videoEl.duration;
                    if (duration > 30) {
                        document.getElementById('videoDurationError').style.display = 'block';
                        document.getElementById('videoDurationInfo').style.display = 'none';
                        videoValid = false;
                    } else {
                        document.getElementById('videoDurationError').style.display = 'none';
                        document.getElementById('videoDurationInfo').style.display = 'block';
                        document.getElementById('videoDurationText').textContent = Math.round(duration) + ' seconds';
                        videoValid = true;
                    }
                };
                videoEl.src = url;

                // Show preview
                document.getElementById('previewVideo').src = URL.createObjectURL(file);
                document.getElementById('videoPreview').style.display = 'block';
            }
        });

        window.addEventListener('click', function(e) {
            const modal = document.getElementById('adModal');
            if (e.target === modal) closeModal();
        });

        // Show message if present
        <?php if ($message): ?>
            const msg = document.getElementById('message');
            msg.textContent = '<?php echo addslashes($message); ?>';
            msg.className = 'message show <?php echo $messageType; ?>';
            setTimeout(() => msg.classList.remove('show'), 5000);
        <?php endif; ?>
    </script>
</body>
</html>

