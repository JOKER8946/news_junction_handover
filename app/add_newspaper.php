<?php
require_once 'inc/php/validate.logged.php';
require_once 'inc/php/db_config.php';

global $gUserId, $readerdb;

$db = $readerdb;
if (!$db) {
    die('Database connection error');
}

// Handle AJAX get_newspaper request EARLY (before HTML output)
if (isset($_GET['get_newspaper'])) {
    header('Content-Type: application/json');
    $np_id = intval($_GET['get_newspaper']);
    $userId = intval($gUserId);
    $stmt = $db->prepare("SELECT * FROM newspapers WHERE id = ? AND uploaded_by = ? LIMIT 1");
    $stmt->bind_param('ii', $np_id, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
        echo json_encode($row);
    } else {
        echo json_encode([]);
    }
    $stmt->close();
    exit;
}

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'create') {
        $title = trim($_POST['title'] ?? '');
        $district = trim($_POST['district'] ?? '');

        if (empty($title) || empty($district)) {
            $message = 'Title and District are required';
            $messageType = 'error';
        } else {
            $pdf_url = '';
            $cover_image = '';

            // Handle PDF upload
            if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] == 0) {
                $filesize = $_FILES['pdf_file']['size'];
                $tmpPath = $_FILES['pdf_file']['tmp_name'];
                $filename = $_FILES['pdf_file']['name'];

                if ($filesize > 10485760) { // 10MB
                    $message = 'PDF must be less than 10MB';
                    $messageType = 'error';
                } else {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = $finfo ? finfo_file($finfo, $tmpPath) : mime_content_type($tmpPath);
                    if ($finfo) finfo_close($finfo);

                    if ($mime !== 'application/pdf') {
                        $message = 'Only PDF files are allowed';
                        $messageType = 'error';
                    } else {
                        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        if ($ext !== 'pdf') {
                            $message = 'Invalid file extension. Only .pdf allowed';
                            $messageType = 'error';
                        } else {
                            $uploadDir = __DIR__ . '/uploads/newspapers/';
                            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                            try {
                                $rand = bin2hex(random_bytes(6));
                            } catch (Exception $e) {
                                $rand = uniqid();
                            }
                            $newFilename = time() . '_' . $rand . '.pdf';
                            $filepath = $uploadDir . $newFilename;

                            if (move_uploaded_file($tmpPath, $filepath)) {
                                $pdf_url = 'uploads/newspapers/' . $newFilename;
                            } else {
                                $message = 'Failed to upload PDF. Check folder permissions';
                                $messageType = 'error';
                            }
                        }
                    }
                }
            } else {
                $message = 'PDF file is required';
                $messageType = 'error';
            }

            // Handle optional cover image upload
            if (empty($message) && isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
                $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'png' => 'image/png'];
                $imgSize = $_FILES['cover_image']['size'];
                $imgTmp = $_FILES['cover_image']['tmp_name'];
                $imgName = $_FILES['cover_image']['name'];

                if ($imgSize > 5242880) { // 5MB
                    $message = 'Cover image must be less than 5MB';
                    $messageType = 'error';
                } else {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $imgMime = $finfo ? finfo_file($finfo, $imgTmp) : mime_content_type($imgTmp);
                    if ($finfo) finfo_close($finfo);

                    $imageInfo = @getimagesize($imgTmp);
                    if (!$imageInfo) {
                        $message = 'Cover image is not a valid image';
                        $messageType = 'error';
                    } else {
                        $imgExt = strtolower(pathinfo($imgName, PATHINFO_EXTENSION));
                        if (!array_key_exists($imgExt, $allowed) || $allowed[$imgExt] !== $imgMime) {
                            $message = 'Invalid image type. Only JPG, PNG, GIF allowed';
                            $messageType = 'error';
                        } else {
                            $uploadDir = __DIR__ . '/uploads/newspapers/';
                            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                            try {
                                $rand = bin2hex(random_bytes(6));
                            } catch (Exception $e) {
                                $rand = uniqid();
                            }
                            $imgFilename = time() . '_' . $rand . '.' . $imgExt;
                            $imgFilepath = $uploadDir . $imgFilename;

                            if (move_uploaded_file($imgTmp, $imgFilepath)) {
                                $cover_image = 'uploads/newspapers/' . $imgFilename;
                            } else {
                                $message = 'Failed to upload cover image';
                                $messageType = 'error';
                            }
                        }
                    }
                }
            }

            // Insert into database if no errors
            if (empty($message) && !empty($pdf_url)) {
                $creator = intval($gUserId);
                $stmt = $db->prepare("INSERT INTO newspapers (title, district, pdf_url, cover_image, uploaded_by) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param('ssssi', $title, $district, $pdf_url, $cover_image, $creator);
                if ($stmt->execute()) {
                    $message = 'Newspaper uploaded successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Error: ' . $stmt->error;
                    $messageType = 'error';
                }
                $stmt->close();
            }
        }
    } elseif ($action == 'delete') {
        $np_id = intval($_POST['newspaper_id']);
        $userId = intval($gUserId);
        // Only allow deleting own newspapers
        $stmt = $db->prepare("SELECT pdf_url, cover_image FROM newspapers WHERE id = ? AND uploaded_by = ?");
        $stmt->bind_param('ii', $np_id, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            // Delete files
            if (!empty($row['pdf_url']) && file_exists(__DIR__ . '/' . $row['pdf_url'])) {
                unlink(__DIR__ . '/' . $row['pdf_url']);
            }
            if (!empty($row['cover_image']) && file_exists(__DIR__ . '/' . $row['cover_image'])) {
                unlink(__DIR__ . '/' . $row['cover_image']);
            }
            $stmt->close();

            $stmt2 = $db->prepare("DELETE FROM newspapers WHERE id = ? AND uploaded_by = ?");
            $stmt2->bind_param('ii', $np_id, $userId);
            if ($stmt2->execute()) {
                $message = 'Newspaper deleted successfully!';
                $messageType = 'success';
            } else {
                $message = 'Error: ' . $stmt2->error;
                $messageType = 'error';
            }
            $stmt2->close();
        } else {
            $message = 'Newspaper not found or access denied';
            $messageType = 'error';
            $stmt->close();
        }
    }
}

// Get user's newspapers
$newspapers = [];
$userId = intval($gUserId);
$stmt = $db->prepare("SELECT * FROM newspapers WHERE uploaded_by = ? ORDER BY created_at DESC");
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    $newspapers = $result->fetch_all(MYSQLI_ASSOC);
}
$stmt->close();

// Karnataka districts
$districts = [
    'Bagalkot', 'Ballari', 'Belagavi', 'Bengaluru Rural', 'Bengaluru Urban',
    'Bidar', 'Chamarajanagar', 'Chikballapur', 'Chikkamagaluru', 'Chitradurga',
    'Dakshina Kannada', 'Davanagere', 'Dharwad', 'Gadag', 'Hassan',
    'Haveri', 'Kalaburagi', 'Kodagu', 'Kolar', 'Koppal',
    'Mandya', 'Mysuru', 'Raichur', 'Ramanagara', 'Shivamogga',
    'Tumakuru', 'Udupi', 'Uttara Kannada', 'Vijayapura', 'Yadgir'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Newspapers - News Junction</title>
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
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .np-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border-left: 4px solid #667eea;
        }

        .np-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }

        .np-card .np-thumbnail {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 15px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .np-card .np-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 5px;
        }

        .np-card .np-thumbnail .pdf-icon {
            font-size: 48px;
            color: #667eea;
        }

        .np-card h3 {
            margin-bottom: 8px;
            color: #667eea;
            font-size: 16px;
        }

        .np-card p {
            color: #666;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .np-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 12px;
            background: #f9f9f9;
            padding: 10px;
            border-radius: 5px;
        }

        .meta-label {
            color: #999;
            font-weight: 500;
        }

        .meta-value {
            color: #667eea;
            font-weight: 600;
        }

        .np-actions {
            display: flex;
            gap: 8px;
        }

        .np-actions a,
        .np-actions button {
            flex: 1;
            padding: 8px 12px;
            font-size: 12px;
            text-align: center;
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
            .cards-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-newspaper"></i> My Newspapers</h1>
            <p>Upload and manage your newspaper PDFs</p>
        </div>

        <div id="message" class="message"></div>

        <div class="controls">
            <button class="btn btn-primary" onclick="openCreateModal()">
                <i class="fas fa-plus"></i> Upload Newspaper
            </button>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <?php if (!empty($newspapers)): ?>
            <div class="cards-grid">
                <?php foreach ($newspapers as $np): ?>
                    <div class="np-card">
                        <div class="np-thumbnail">
                            <?php if (!empty($np['cover_image'])): ?>
                                <img src="<?php echo htmlspecialchars($np['cover_image']); ?>" alt="<?php echo htmlspecialchars($np['title']); ?>">
                            <?php else: ?>
                                <i class="fas fa-file-pdf pdf-icon"></i>
                            <?php endif; ?>
                        </div>

                        <h3><?php echo htmlspecialchars($np['title']); ?></h3>

                        <div class="np-meta">
                            <div>
                                <span class="meta-label">District:</span>
                                <span class="meta-value"><?php echo htmlspecialchars($np['district']); ?></span>
                            </div>
                            <div>
                                <span class="meta-label">Date:</span>
                                <span class="meta-value"><?php echo date('d M Y', strtotime($np['created_at'])); ?></span>
                            </div>
                        </div>

                        <div class="np-actions">
                            <a href="<?php echo htmlspecialchars($np['pdf_url']); ?>" target="_blank" class="btn btn-primary">
                                <i class="fas fa-eye"></i> View PDF
                            </a>
                            <button class="btn btn-danger" onclick="deleteNewspaper(<?php echo $np['id']; ?>, '<?php echo htmlspecialchars(addslashes($np['title'])); ?>')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-newspaper"></i>
                <h3>No Newspapers Yet</h3>
                <p>Upload your first newspaper PDF to get started</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Upload Modal -->
    <div class="modal" id="npModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Upload Newspaper</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form id="npForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">

                <div class="form-group">
                    <label for="title">Newspaper Title *</label>
                    <input type="text" id="title" name="title" required placeholder="e.g. Prajavani - March 2026">
                </div>

                <div class="form-group">
                    <label for="district">District *</label>
                    <select id="district" name="district" required>
                        <option value="">Select District</option>
                        <?php foreach ($districts as $d): ?>
                            <option value="<?php echo htmlspecialchars($d); ?>"><?php echo htmlspecialchars($d); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="pdf_file">PDF File *</label>
                    <input type="file" id="pdf_file" name="pdf_file" accept=".pdf" required>
                    <small style="color: #999;">Max 10MB. Only .pdf files</small>
                </div>

                <div class="form-group">
                    <label for="cover_image">Cover Image (optional)</label>
                    <input type="file" id="cover_image" name="cover_image" accept="image/*">
                    <small style="color: #999;">Max 5MB. Formats: JPG, PNG, GIF</small>
                    <div id="imagePreview" style="margin-top:10px; display:none;">
                        <img id="previewImg" src="" alt="Preview" style="max-width:200px; max-height:150px; border-radius:4px;">
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-success">Upload</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('npForm').reset();
            document.getElementById('imagePreview').style.display = 'none';
            document.getElementById('npModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('npModal').classList.remove('active');
        }

        function deleteNewspaper(npId, title) {
            if (confirm('Are you sure you want to delete "' + title + '"?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="delete">' +
                    '<input type="hidden" name="newspaper_id" value="' + npId + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Cover image preview
        document.getElementById('cover_image').addEventListener('change', function() {
            if (this.files.length > 0) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImg').src = e.target.result;
                    document.getElementById('imagePreview').style.display = 'block';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });

        window.addEventListener('click', function(e) {
            const modal = document.getElementById('npModal');
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
