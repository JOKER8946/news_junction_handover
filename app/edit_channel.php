<?php
include './inc/php/db_config.php';
include './inc/php/validate.logged.php';
include './inc/php/function.php';

$channelId = isset($_GET['channelId']) ? intval($_GET['channelId']) : 0;

// Load channel — must be owned by the current user
$channel = null;
if ($channelId > 0) {
    $stmt = $readerdb->prepare("SELECT id, name, bio, visibility, profilePic FROM channels WHERE id = ? AND created_by = ? LIMIT 1");
    $stmt->bind_param("ii", $channelId, $gUserId);
    $stmt->execute();
    $res = $stmt->get_result();
    $channel = $res->fetch_assoc();
    $stmt->close();
}

if (!$channel) {
    header('Location: /dashboard.php#featuredchannels');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json');

    if (!isset($_POST['name'], $_POST['visibility'])) {
        echo json_encode(['status' => 'error', 'message' => 'Please complete all fields.']);
        exit;
    }

    $name       = trim($_POST['name']);
    $bio        = trim($_POST['bio'] ?? '');
    $visibility = $_POST['visibility'];

    if (empty($name)) {
        echo json_encode(['status' => 'error', 'message' => 'Channel name is required.']);
        exit;
    }

    $newProfilePic = null;
    if (isset($_FILES['profilepic']) && $_FILES['profilepic']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['profilepic']['type'], $allowed_types)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid file type for profile picture.']);
            exit;
        }
        $profilepic_name = $channelId . "_" . time() . ".png";
        $profilepic_path = 'data/channelPic/' . $profilepic_name;
        if (!move_uploaded_file($_FILES['profilepic']['tmp_name'], $profilepic_path)) {
            echo json_encode(['status' => 'error', 'message' => 'Error uploading profile picture.']);
            exit;
        }
        $newProfilePic = $profilepic_name;
    }

    if ($newProfilePic !== null) {
        $stmt = $readerdb->prepare("UPDATE channels SET name = ?, bio = ?, visibility = ?, profilePic = ? WHERE id = ? AND created_by = ?");
        $stmt->bind_param("ssssii", $name, $bio, $visibility, $newProfilePic, $channelId, $gUserId);
    } else {
        $stmt = $readerdb->prepare("UPDATE channels SET name = ?, bio = ?, visibility = ? WHERE id = ? AND created_by = ?");
        $stmt->bind_param("sssii", $name, $bio, $visibility, $channelId, $gUserId);
    }

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Channel updated successfully!', 'channel_id' => $channelId]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $stmt->error]);
    }
    $stmt->close();
    $readerdb->close();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Channel</title>
    <link rel="stylesheet" href="inc/css/social.css">
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="inc/js/common.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="../grfx/img/logo.ico">

    <style>
        label { display: block; margin-bottom: 8px; font-weight: bold; }
        input[type="text"], textarea, input[type="file"] {
            width: 100%; padding: 10px; margin-bottom: 20px;
            border: 1px solid var(--border-color); border-radius: 4px;
            font-size: 16px; transition: all 0.2s ease-in-out;
        }
        #name, #bio {
            border: 1px solid var(--border-color);
            background-color: var(--bg-card); color: var(--text-primary);
        }
        input[type="submit"] {
            background-color: #db5919; color: white; border: none;
            padding: 10px 20px; border-radius: 4px; cursor: pointer;
            font-size: 16px; width: 100%; transition: background-color 0.2s ease-in-out;
        }
        input[type="submit"]:hover { background-color: #c14b16; }
        #responseMessage { text-align: center; margin-top: 20px; }
        .container-fluid { width: 75%; }
        .visibility { display: flex; line-height: 0.35; border-radius: 5px; margin: 20px 20px 20px 0px; gap: 20px; }
        .current-pic { max-width: 120px; max-height: 120px; border-radius: 8px; margin-bottom: 12px; display: block; }
        @media (max-width: 768px) { .container-fluid { width: 100%; } }
    </style>

    <script>
        $(document).ready(function() {
            $('#editChannelForm').on('submit', function(event) {
                event.preventDefault();
                var formData = new FormData(this);
                $.ajax({
                    url: '',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.status === 'success') {
                            alert(response.message);
                            window.location.href = 'dashboard.php#featuredchannels';
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('An error occurred: ' + error);
                    }
                });
            });
        });
    </script>
</head>

<body>
    <div class="container">
        <? include './inc/php/social_navbar.php' ?>
        <? include './inc/php/social_sidebar.php' ?>
        <main class="reader-main-content main-content">
            <div class="container-fluid col-sm-12 col-md-12 sideMaincontent">

                <h2>Edit Channel</h2>
                <form id="editChannelForm" enctype="multipart/form-data">
                    <label for="name">Channel Name:</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($channel['name']) ?>" required><br><br>

                    <label for="bio">Bio:</label>
                    <textarea id="bio" name="bio"><?= htmlspecialchars($channel['bio'] ?? '') ?></textarea><br><br>

                    <div class="visibility">
                        <label for="visibility" style="margin-bottom: 0px;">Visibility:</label>
                        <select id="visibility" name="visibility" required>
                            <option value="public"  <?= $channel['visibility'] === 'public'  ? 'selected' : '' ?>>Public</option>
                            <option value="private" <?= $channel['visibility'] === 'private' ? 'selected' : '' ?>>Private</option>
                        </select><br><br>
                    </div>

                    <label>Current Profile Picture:</label>
                    <img src="<?= build_image_url('channelPic', $channel['profilePic']) ?>" class="current-pic" onerror="this.onerror=null;this.src='/data/channelPic/default.png';">

                    <label for="profilepic">Replace Profile Picture (optional):</label>
                    <input type="file" id="profilepic" name="profilepic"><br><br>

                    <input type="submit" value="Save Changes">
                </form>
                <div id="responseMessage"></div>
            </div>
        </main>
        <? include 'inc/php/footer.php' ?>
    </div>
</body>

</html>
