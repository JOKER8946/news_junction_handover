<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Picture Upload and Crop</title>

    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Include Cropper.js CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs/dist/cropper.min.css">

    <!-- Include Cropper.js JS -->
    <script src="https://cdn.jsdelivr.net/npm/cropperjs/dist/cropper.min.js"></script>

    <style>
        #imageContainer {
            display: none;
            margin-top: 20px;
            width: 400px; /* Adjust width of the cropper container */
            height: 400px; /* Adjust height of the cropper container */
            overflow: hidden;
        }
        #imagePreview {
            max-width: 100%;
            width: auto; /* Adjust width of the image inside the cropper container */
        }
        #profilePic {
            max-width: 150px; /* Display profile picture at a smaller size */
        }
    </style>
</head>
<body>

<h2>Upload and Crop Profile Picture</h2>

<form id="uploadForm" enctype="multipart/form-data">
    <input type="file" name="profile_pic" accept="image/*" id="fileInput" required />
    <button type="submit">Upload Image</button>
</form>

<!-- Image Preview and Cropper -->
<div id="imageContainer">
    <img id="imagePreview" src="" alt="Image Preview">
</div>

<!-- Crop Button -->
<button id="cropBtn" style="display:none;">Crop and Save</button>

<!-- Display the Profile Picture -->
<h3>Profile Picture:</h3>
<img id="profilePic" src="uploads/profile.jpg" alt="Profile Picture" style="max-width: 150px;">

<script>
$(document).ready(function() {
    let cropper;

    // Handle image file selection and preview
    $('#fileInput').on('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();

            reader.onload = function(e) {
                $('#imagePreview').attr('src', e.target.result);
                $('#imageContainer').show(); // Show the preview image

                // Initialize Cropper.js with specific container size
                if (cropper) {
                    cropper.destroy(); // Destroy the previous cropper if it exists
                }

                // Create new Cropper.js instance with custom settings
                cropper = new Cropper($('#imagePreview')[0], {
                    aspectRatio: 1,  // Maintain square aspect ratio
                    viewMode: 2,     // Restrict the crop box to the canvas area
                    autoCropArea: 0.8, // Set initial crop box size (80% of the container)
                    scalable: true,  // Allow zoom
                    zoomable: true,  // Allow zoom
                    minCanvasWidth: 100,  // Minimum width for the canvas
                    minCanvasHeight: 100, // Minimum height for the canvas
                    dragMode: 'move',  // Allow dragging the image to reposition
                });

                // Show the crop button
                $('#cropBtn').show();
            };

            reader.readAsDataURL(file);  // Load the image file
        }
    });

    // Handle crop button click
    $('#cropBtn').on('click', function() {
        if (cropper) {
            // Get the cropped image as a canvas
            const canvas = cropper.getCroppedCanvas();

            // Convert the canvas to a blob and upload it
            canvas.toBlob(function(blob) {
                const formData = new FormData();
                formData.append('profile_pic', blob, 'profile.jpg');  // Append the cropped image as form data

                // Send the image to the server using AJAX
                $.ajax({
                    url: '',  // Submit to the same page (this PHP file)
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        const data = JSON.parse(response);
                        if (data.status === 'success') {
                            alert('Profile picture updated successfully.');
                            // Update the profile picture displayed on the page
                            $('#profilePic').attr('src', data.path);
                        } else {
                            alert('Error uploading image: ' + data.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Error uploading cropped image.');
                    }
                });
            });
        }
    });
});
</script>

</body>
</html>
