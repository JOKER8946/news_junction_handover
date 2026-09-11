<?php
include './inc/db_connect.php';
include './inc/config.php';
include './inc/validate.logged.php';

// Upload and insert post
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $content = $_POST['content'];


    // Handle file upload
    $media_url = null;
    if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        $media_url = $upload_dir . basename($_FILES['media']['name']);
        move_uploaded_file($_FILES['media']['tmp_name'], $media_url);
    }

    // Insert the post into the database
    $stmt = $conn->prepare("INSERT INTO reader_chat (userId, chat, mediaPath) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $gUserId, $content, $media_url);

    if ($stmt->execute()) {
        echo "<script>alert('Posted successfully...');</script>";
    } else {
        echo "<script>alert('Error: " . $stmt->error . "');</script>";
    }
    unset($_POST);
    $stmt->close();
}

function showUserName($db, $userId) {
    $sql = "SELECT full_name FROM user WHERE id = ?";
    $fullName = "";

    if ($stmt = $db->prepare($sql)) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($fullName);
        if ($stmt->fetch()) {
            return $fullName;
        } else {
            return null;
        }

        $stmt->close();
    } else {
        return "Error preparing statement: " . $db->error;
    }
}


// Fetch posts from the database
$sql = "SELECT * FROM reader_chat ORDER BY postedOn DESC";
$result = $conn->query($sql);

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News Junction</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #141414;
            color: #e5e5e5;
            font-family: Arial, sans-serif;
        }

        .container {
            max-width: 500px;
        }

        .upload-section,
        .post {
            background-color: #333;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid #444;
        }

        .post-header .avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #555;
        }

        .post-header h5 {
            color: #e5e5e5;
        }

        .post-header span {
            color: #888;
            font-size: 0.9rem;
        }

        .post-content img,
        .post-content video {
            width: 100%;
            border-radius: 10px;
            margin-top: 10px;
            max-height: 300px;
        }

        .actions .btn {
            color: #888;
            font-size: 0.9rem;
        }

        .actions .btn:hover {
            color: #0d6efd;
        }

        .upload-section textarea {
            width: 100%;
            height: 50px;
            background-color: #141414;
            border: 1px solid #444;
            color: #e5e5e5;
            resize: none;
            border-radius: 5px;
            padding: 10px;
        }

        .upload-section .btn-upload {
            background-color: #0d6efd;
            color: #ffffff;
            border-radius: 23px;
            padding: 2px 15px;
        }
    </style>
</head>

<body>
    <div class="container my-4">
        <!-- Header -->
        <div class="header d-flex justify-content-between align-items-center py-3">
            <h1 class="fs-5 fw-bold text-white">Explore</h1>
        </div>

        <div class="upload-section">
            <form action="" method="post" enctype="multipart/form-data">
                <div class="d-flex align-items-start">
                    <div class="avatar"></div>
                    <div class="w-100">
                        <textarea class="form-control mb-2" name="content" rows="3" placeholder="What would you like to share?"></textarea>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <!-- Hidden file input -->
                                <input type="file" name="media" id="fileInput" accept="image/*,video/*" class="d-none" onchange="previewMedia();">
                                <button type="button" class="btn btn-link text-decoration-none text-light" onclick="document.getElementById('fileInput').click();">
                                    <i class="bi bi-image"></i> Upload Media
                                </button>
                            </div>
                            <button type="submit" class="btn btn-upload">Post</button>
                        </div>

                        <!-- Preview Section -->
                        <div id="mediaPreview" class="mt-3">
                            <!-- Placeholder for image/video preview -->
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Posts -->
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="post">
                    <div class="post-header d-flex align-items-center">
                        <div class="avatar me-3"></div>
                        <div>
                            <h5 class="mb-0"><?= showUserName($db, $row['userId']) ?> <span class="text-muted"><?= $row['postedOn']; ?></span></h5>
                        </div>
                    </div>
                    <div class="post-content mt-3">
                        <p><?= htmlspecialchars($row['chat']); ?></p>
                        <?php if ($row['mediaPath']): ?>
                            <?php if (strpos($row['mediaPath'], 'mp4') !== false): ?>
                                <video controls>
                                    <source src="<?= $row['mediaPath']; ?>" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            <?php else: ?>
                                <img src="<?= $row['mediaPath']; ?>" alt="Post media">
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div class="actions d-flex justify-content-around mt-3">
                        <button class="btn btn-link">Like</button>
                        <button class="btn btn-link">Comment</button>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No posts found.</p>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS and Bootstrap Icons -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <script>
        function previewMedia() {
            const fileInput = document.getElementById('fileInput');
            const mediaPreview = document.getElementById('mediaPreview');
            const file = fileInput.files[0];

            // Clear previous previews
            mediaPreview.innerHTML = '';

            if (file) {
                const fileReader = new FileReader();

                // For image files
                if (file.type.startsWith('image')) {
                    fileReader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.style.maxWidth = '100%';
                        img.style.maxHeight = '300px'; // Set max height for the preview
                        mediaPreview.appendChild(img);
                    };
                }

                // For video files
                else if (file.type.startsWith('video')) {
                    const video = document.createElement('video');
                    video.controls = true;
                    video.style.maxWidth = '100%';
                    video.style.maxHeight = '300px'; // Set max height for the preview
                    mediaPreview.appendChild(video);

                    fileReader.onload = function(e) {
                        video.src = e.target.result;
                        video.load();
                        video.play();
                    };
                }

                // Start reading the file
                fileReader.readAsDataURL(file);
            }
        }
    </script>
</body>

</html>