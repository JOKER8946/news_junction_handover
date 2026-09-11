<?php
// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        // Image upload
        $uploadedImage = $_FILES['image']['tmp_name'];
        $imageType = mime_content_type($uploadedImage);

        // Load the image
        if ($imageType === 'image/jpeg') {
            $image = imagecreatefromjpeg($uploadedImage);
        } elseif ($imageType === 'image/png') {
            $image = imagecreatefrompng($uploadedImage);
        } else {
            die('Unsupported image type.');
        }

        // Apply filter if requested
        if (isset($_POST['filter'])) {
            $filter = $_POST['filter'];
            applyFilter($image, $filter);
        }

        // Process cropping if the image data was received from the client
        if (isset($_FILES['croppedImage'])) {
            $croppedImage = $_FILES['croppedImage']['tmp_name'];
            $croppedData = file_get_contents($croppedImage);
            $croppedImage = imagecreatefromstring($croppedData);
            // Save cropped image to the server or do additional processing here
            imagejpeg($croppedImage, 'cropped_image.jpg');
        }

        // Save final image (after processing)
        imagejpeg($image, 'final_image.jpg');
        imagedestroy($image);
        echo 'Image processed successfully!';
    }
}

// Apply a filter to the image
function applyFilter($image, $filter) {
    switch ($filter) {
        case 'grayscale':
            imagefilter($image, IMG_FILTER_GRAYSCALE);
            break;
        case 'sepia':
            imagefilter($image, IMG_FILTER_COLORIZE, 112, 66, 20);
            break;
        case 'invert':
            imagefilter($image, IMG_FILTER_NEGATE);
            break;
        default:
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Upload and Filter</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css" />
    <style>
        .image-container {
            max-width: 100%;
            max-height: 500px;
            margin-top: 20px;
        }
        .filters {
            margin-top: 20px;
        }
        .filters button {
            margin-right: 10px;
        }
    </style>
</head>
<body>

<h2>Upload, Crop, and Apply Filters to Your Image</h2>

<form id="imageForm" action="process_image.php" method="POST" enctype="multipart/form-data">
    <input type="file" id="imageInput" name="image" accept="image/*" required />
    <button type="submit" id="submitBtn" style="display:none;">Upload Image</button>
</form>

<div class="image-container">
    <img id="imagePreview" src="#" alt="Image Preview" />
</div>

<div class="filters">
    <button onclick="applyFilter('grayscale')">Grayscale</button>
    <button onclick="applyFilter('sepia')">Sepia</button>
    <button onclick="applyFilter('invert')">Invert</button>
</div>

<div class="crop-container">
    <button onclick="cropImage()">Crop Image</button>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    let cropper;

    // File input change event to show image preview and initialize the cropper
    $('#imageInput').change(function (e) {
        const file = e.target.files[0];
        const reader = new FileReader();
        reader.onload = function (event) {
            $('#imagePreview').attr('src', event.target.result);
            $('#submitBtn').show();
            if (cropper) {
                cropper.destroy();
            }
            const image = document.getElementById('imagePreview');
            cropper = new Cropper(image, {
                aspectRatio: 1,
                viewMode: 2,
                autoCropArea: 0.8,
            });
        };
        reader.readAsDataURL(file);
    });

    // Apply filters to the image
    function applyFilter(filter) {
        const image = document.getElementById('imagePreview');
        image.style.filter = filter + '(1)';
    }

    // Crop the image and prepare the base64 data for submission
    function cropImage() {
        const canvas = cropper.getCroppedCanvas();
        canvas.toBlob((blob) => {
            const formData = new FormData();
            formData.append('croppedImage', blob);
            $.ajax({
                url: '',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    alert('Image processed successfully!');
                }
            });
        });
    }
</script>

</body>
</html>
