<?php
require_once 'inc/php/validate.logged.php';
require_once 'inc/php/db_config.php';

// Check if user is admin (adjust role check based on your schema)
$isAdmin = false;
if (isset($gUserId)) {
    $sql = "SELECT role FROM nj_cream.user WHERE id = $gUserId LIMIT 1";
    $result = $creamdb->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $isAdmin = ($row['role'] == 'admin');
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
        $title = $db->real_escape_string($_POST['title'] ?? '');
        $description = $db->real_escape_string($_POST['description'] ?? '');
        $ad_link = $db->real_escape_string($_POST['ad_link'] ?? '');
        $position = $db->real_escape_string($_POST['position'] ?? 'feed');
        $is_active = intval($_POST['is_active'] ?? 1);
        $start_date = !empty($_POST['start_date']) ? $db->real_escape_string($_POST['start_date']) : 'NULL';
        $end_date = !empty($_POST['end_date']) ? $db->real_escape_string($_POST['end_date']) : 'NULL';
        $image_url = $_POST['image_url'] ?? '';

        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            // Validate file
            $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'png' => 'image/png'];
            $filename = $_FILES['image']['name'];
            $filetype = $_FILES['image']['type'];
            $filesize = $_FILES['image']['size'];
            
            // Check file size (max 5MB)
            if ($filesize > 5242880) {
                $message = 'Image must be less than 5MB';
                $messageType = 'error';
            } else {
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                if (!array_key_exists($ext, $allowed)) {
                    $message = 'Invalid file type. Only JPG, PNG, GIF allowed';
                    $messageType = 'error';
                } else {
                    $uploadDir = __DIR__ . '/uploads/ads/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                    $newFilename = time() . '_' . uniqid() . '.' . $ext;
                    $filepath = $uploadDir . $newFilename;

                    if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
                        $image_url = 'uploads/ads/' . $newFilename;
                    } else {
                        $message = 'Failed to upload image. Check folder permissions';
                        $messageType = 'error';
                    }
                }
            }
        }

        $image_url = $db->real_escape_string($image_url);
        $start_date_sql = ($start_date == 'NULL') ? 'NULL' : "'$start_date'";
        $end_date_sql = ($end_date == 'NULL') ? 'NULL' : "'$end_date'";

        if ($action == 'create') {
            $sql = "INSERT INTO nj_reader.ads (title, description, image_url, ad_link, position, is_active, start_date, end_date, created_by)
                    VALUES ('$title', '$description', '$image_url', '$ad_link', '$position', $is_active, $start_date_sql, $end_date_sql, $gUserId)";
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
                    ad_link='$ad_link', position='$position', is_active=$is_active, 
                    start_date=$start_date_sql, end_date=$end_date_sql WHERE id=$ad_id";
            if ($db->query($sql)) {
                $message = 'Ad updated successfully!';
                $messageType = 'success';
            } else {
                $message = 'Error: ' . $db->error;
                $messageType = 'error';
            }
        }
    } elseif ($action == 'delete') {
        $ad_id = intval($_POST['ad_id']);
        if ($db->query("DELETE FROM nj_reader.ads WHERE id=$ad_id")) {
            $db->query("DELETE FROM nj_reader.ad_analytics WHERE ad_id=$ad_id");
            $message = 'Ad deleted successfully!';
            $messageType = 'success';
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
    $sql = "SELECT 
            COUNT(CASE WHEN action='impression' THEN 1 END) as impressions,
            COUNT(CASE WHEN action='click' THEN 1 END) as clicks
            FROM nj_reader.ad_analytics WHERE ad_id=$ad_id";
    $result = $db->query($sql);
    return $result ? $result->fetch_assoc() : ['impressions' => 0, 'clicks' => 0];
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
                        <?php if (!empty($ad['image_url'])): ?>
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
                                <span class="meta-label">Position:</span>
                                <span class="meta-value"><?php echo ucfirst($ad['position']); ?></span>
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
                    <label for="image">Image *</label>
                    <input type="file" id="image" name="image" accept="image/*">
                    <small style="color: #999;">Max 5MB. Formats: JPG, PNG, GIF</small>
                    <div id="imagePreview" style="margin-top:10px; display:none;">
                        <img id="previewImg" src="" alt="Preview" style="max-width:200px; max-height:150px; border-radius:4px;">
                        <p style="font-size:12px; color:#999; margin-top:5px;">Current image</p>
                    </div>
                    <input type="hidden" id="imageUrl" name="image_url" value="">
                </div>

                <div class="form-group">
                    <label for="adLink">Ad Link (Destination URL) *</label>
                    <input type="url" id="adLink" name="ad_link" required placeholder="https://example.com">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="position">Position *</label>
                        <select id="position" name="position" required>
                            <option value="top">Top Banner</option>
                            <option value="feed">Feed</option>
                            <!-- <option value="sidebar">Sidebar</option>
                            <option value="bottom">Bottom</option> -->
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="isActive">Status</label>
                        <select id="isActive" name="is_active">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
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
        function openCreateModal() {
            document.getElementById('action').value = 'create';
            document.getElementById('modalTitle').textContent = 'Create New Ad';
            document.getElementById('adForm').reset();
            document.getElementById('adId').value = '';
            document.getElementById('adModal').classList.add('active');
        }

        function editAd(adId) {
            // Fetch ad data via AJAX
            fetch('admin_ads.php?get_ad=' + adId)
                .then(r => r.json())
                .then(ad => {
                    document.getElementById('action').value = 'update';
                    document.getElementById('modalTitle').textContent = 'Edit Ad';
                    document.getElementById('adId').value = ad.id;
                    document.getElementById('title').value = ad.title;
                    document.getElementById('description').value = ad.description || '';
                    document.getElementById('adLink').value = ad.ad_link || '';
                    document.getElementById('position').value = ad.position;
                    document.getElementById('isActive').value = ad.is_active;
                    document.getElementById('startDate').value = ad.start_date ? ad.start_date.replace(' ', 'T') : '';
                    document.getElementById('endDate').value = ad.end_date ? ad.end_date.replace(' ', 'T') : '';
                    document.getElementById('imageUrl').value = ad.image_url || '';
                    
                    // Show preview of current image
                    if (ad.image_url) {
                        document.getElementById('previewImg').src = ad.image_url;
                        document.getElementById('imagePreview').style.display = 'block';
                    } else {
                        document.getElementById('imagePreview').style.display = 'none';
                    }
                    
                    // Clear file input
                    document.getElementById('image').value = '';
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
            // If no new image selected, keep old one
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

