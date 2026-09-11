<?php
include './inc/php/db_config.php';
include './inc/php/validate.logged.php';
include './inc/php/function.php';

if (isset($_GET['postId'])) {
    $postId = $_GET['postId'];
} else {
    $postId = "";
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json');

    // Check for form fields
    if (isset($_POST['name'],  $_FILES['profilepic'], $_POST['visibility'])) {
        // Get data from form
        $name = trim($_POST['name']);
        $bio = trim($_POST['bio']);
        $visibility = $_POST['visibility']; // New field for visibility
        $created_at = date('Y-m-d H:i:s'); // Get current date and time
        $deleted_at = NULL; // Set to NULL initially

        // Check if required values are not empty
        if (empty($name)) {
            echo json_encode(['status' => 'error', 'message' => 'Channel name and bio are required.']);
            exit;
        }

        // Prepare and bind statement for inserting the channel
        $stmt = $readerdb->prepare("INSERT INTO channels (name, bio, visibility, created_by, created_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $bio, $visibility, $gUserId, $created_at);

        // Execute the query
        if ($stmt->execute()) {
            $channel_id = $stmt->insert_id;

            // Handle profile picture upload
            $profilepic = $_FILES['profilepic'];
            $profilepic_name = $channel_id . "_" . time() . ".png";
            $profilepic_tmp_name = $profilepic['tmp_name'];
            $profilepic_path = 'data/channelPic/' . $profilepic_name;

            // Validate file type (image)
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($profilepic['type'], $allowed_types)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid file type for profile picture.']);
                exit;
            }

            if (!empty($postId)) {
                $stmt = $readerdb->prepare("INSERT INTO channel_content (channel_id,post_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $channel_id, $postId);
                $stmt->execute();
            }
            // Move the uploaded file to the desired folder
            if (move_uploaded_file($profilepic_tmp_name, $profilepic_path)) {
                $stmt = $readerdb->prepare("UPDATE channels SET profilePic = ? WHERE id = ?");
                $stmt->bind_param("si", $profilepic_name, $channel_id);

                if ($stmt->execute()) {
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'New channel added successfully!',
                        'channel_id' => $channel_id
                    ]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Error saving profile picture information.']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error uploading profile picture.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error: ' . $stmt->error]);
        }

        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Please complete all fields.']);
    }

    // Close connection when done
    $readerdb->close();
} else {
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Add Channel</title>
        <link rel="stylesheet" href="inc/css/social.css">
        <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
        <script src="inc/js/common.js"></script>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
        <link rel="icon" type="image/x-icon" href="../grfx/img/logo.ico">

        <style>
            label {
                display: block;
                margin-bottom: 8px;
                font-weight: bold;
            }

            input[type="text"],
            textarea,
            input[type="file"] {
                width: 100%;
                padding: 10px;
                margin-bottom: 20px;
                border: 1px solid var(--border-color);
                border-radius: 4px;
                font-size: 16px;
                transition: all 0.2s ease-in-out;
            }

            #name,
            #bio {
                border: 1px solid var(--border-color);
                background-color: var(--bg-card);
                color: var(--text-primary);

            }


            input[type="submit"] {
                background-color: #db5919;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 16px;
                width: 100%;
                transition: background-color 0.2s ease-in-out;
            }

            input[type="submit"]:hover {
                background-color: #c14b16;
            }

            #responseMessage {
                text-align: center;
                margin-top: 20px;
            }

            /* Media Queries for responsiveness */

            /* Medium Devices (Tablets) */
            @media (max-width: 1024px) {
                .container {
                    padding: 15px;
                    box-shadow: none;
                }

                input[type="submit"] {
                    font-size: 15px;
                }

                label,
                input[type="text"],
                textarea,
                input[type="file"] {
                    font-size: 15px;
                }
            }

            /* Small Devices (Phones) */
            @media (max-width: 768px) {
                body {
                    padding: 10px;
                }

                .container {
                    padding: 15px;
                    width: 100%;
                }

                input[type="submit"] {
                    font-size: 14px;
                }

                label,
                input[type="text"],
                textarea,
                input[type="file"] {
                    font-size: 14px;
                }
            }

            /* Extra Small Devices (Phones in Portrait Mode) */
            @media (max-width: 480px) {
                input[type="submit"] {
                    font-size: 14px;
                    padding: 12px;
                }

                label,
                input[type="text"],
                textarea,
                input[type="file"] {
                    font-size: 14px;
                    padding: 8px;
                }

                .container {
                    padding: 10px;
                }
            }

            /* Very Small Devices (Extra Small Phones) */
            @media (max-width: 320px) {
                .container {
                    padding: 10px;
                    width: 100%;
                }

                input[type="submit"] {
                    font-size: 13px;
                    padding: 12px;
                }

                label,
                input[type="text"],
                textarea,
                input[type="file"] {
                    font-size: 13px;
                }
            }

            .container-fluid {
                width: 75%;
            }

            .visibility {
                display: flex;
                line-height: 0.35;
                border-radius: 5px;
                margin: 20px 20px 20px 0px;
                gap: 20px;
            }
        </style>

        <script>
            $(document).ready(function() {
                // Attach a submit event handler to the form
                $('#addChannelForm').on('submit', function(event) {
                    event.preventDefault(); // Prevent default form submission

                    // Create a FormData object to include all form data
                    var formData = new FormData(this);

                    // Send the AJAX request
                    $.ajax({
                        url: '', // Replace with your PHP handler
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            console.log(response);
                            if (response.status === 'success') {
                                alert(response.message);
                                // Check if postId is set and redirect accordingly
                                if (<?= json_encode($postId) ?> != "") {
                                    window.location.href = 'channel.php?channelId=' + response.channel_id;
                                } else {
                                    window.location.href = 'dashboard.php';
                                }
                            } else {
                                alert(response.message); // Show error message
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
                <div class="container-fluid  col-sm-12 col-md-12 sideMaincontent">

                    <h2>Add Channel</h2>
                    <form id="addChannelForm" enctype="multipart/form-data">
                        <label for="name">Channel Name:</label>
                        <input type="text" id="name" name="name" required><br><br>

                        <label for="bio">Bio:</label>
                        <textarea id="bio" name="bio"></textarea><br><br>

                        <div class="visibility">
                            <label for="visibility" style="margin-bottom: 0px;">Visibility:</label>
                            <select id="visibility" name="visibility" required>
                                <option value="public">Public</option>
                                <option value="private">Private</option>
                            </select><br><br>
                        </div>


                        <label for="profilepic">Profile Picture:</label>
                        <input type="file" id="profilepic" name="profilepic" required><br><br>

                        <input type="submit" value="Add Channel">
                    </form>
                    <div id="responseMessage"></div>
                </div>
            </main>
            <? include 'inc/php/footer.php' ?>
        </div>
    </body>

    </html>
<?php
}
?>