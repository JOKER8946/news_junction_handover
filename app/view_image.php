<?php
// Get query parameters
$type = isset($_GET['type']) ? $_GET['type'] : '';
$imagePaths = isset($_GET['imagePaths']) ? explode(',', urldecode($_GET['imagePaths'])) : [];
$selectedImage = isset($_GET['selectedImage']) ? urldecode($_GET['selectedImage']) : '';
$videoPath = isset($_GET['videoPath']) ? urldecode($_GET['videoPath']) : '';
$postId = isset($_GET['postId']) ? $_GET['postId'] : '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Viewer</title>
    <link rel="icon" type="image/x-icon" href="../grfx/img/logo.ico">

    <!-- Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #000;
            color: #fff;
            overflow: hidden;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .carousel-container {
            width: 80%;
            height: 80%;
            margin: auto;
            position: relative;
        }

        .carousel-inner img,
        .carousel-inner video {
            max-height: 80vh;
            width: 100%;
            object-fit: contain;
            cursor: pointer;
        }

        .video-container {
            max-width: 90%;
            max-height: 90vh;
            text-align: center;
        }

        .video-container video {
            width: 100%;
            max-height: 80vh;
            object-fit: contain;
        }

        /* Hide carousel controls on mobile */
        @media (max-width: 576px) {

            .carousel-control-prev,
            .carousel-control-next {
                display: none;
            }

            .carousel-container {
                width: 80%;
                height: 80%;
                margin: auto;
                position: relative;
                top: 10%;
            }
        }

        /* Full-Screen Image View */
        .fullscreen-image-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #000;
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .fullscreen-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform 0.25s ease;
            cursor: zoom-in;
            transform-origin: center center;
        }

        .close-btn {
            position: absolute;
            top: 15px;
            left: 15px;
            color: #fff;
            font-size: 34px;
            cursor: pointer;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            padding: 5px;
            z-index: 1001;
        }
    </style>
</head>

<body>
    <span class="close-btn" onclick="goToPost('<?= $postId ?>')">×</span>
    <?php
    if (!empty($imagePaths)) {
        // Image carousel
        echo '<div class="carousel-container" id="carouselContainer">';
        echo '<div id="imageCarousel" class="carousel slide" data-interval="false">';
        // Indicators (Dots)
        echo '<ol class="carousel-indicators">';
        foreach ($imagePaths as $index => $imagePath) {
            $activeClass = ($imagePath === $selectedImage || $index === 0) ? 'active' : '';
            echo '<li data-target="#imageCarousel" data-slide-to="' . $index . '" class="' . $activeClass . '"></li>';
        }
        echo '</ol>';

        // Carousel Items
        echo '<div class="carousel-inner">';
        $isActive = true;
        foreach ($imagePaths as $imagePath) {
            $activeClass = ($imagePath === $selectedImage || $isActive) ? 'active' : '';
            echo '<div class="carousel-item ' . $activeClass . '">';
            echo '<img src="' . htmlspecialchars($imagePath) . '" class="d-block w-100 open-fullscreen" alt="Image" data-image="' . htmlspecialchars($imagePath) . '">';
            echo '</div>';
            $isActive = false;
        }
        echo '</div>';

        // Carousel Controls
        echo '<a class="carousel-control-prev" href="#imageCarousel" role="button" data-slide="prev">';
        echo '<span class="carousel-control-prev-icon" aria-hidden="true"></span>';
        echo '<span class="sr-only">Previous</span>';
        echo '</a>';
        echo '<a class="carousel-control-next" href="#imageCarousel" role="button" data-slide="next">';
        echo '<span class="carousel-control-next-icon" aria-hidden="true"></span>';
        echo '<span class="sr-only">Next</span>';
        echo '</a>';
        echo '</div>';
        echo '</div>';
    } elseif (!empty($videoPath)) {
        // Video display
        echo '<div class="video-container">';
        echo '<video controls autoplay>';
        echo '<source src="' . htmlspecialchars($videoPath) . '" type="video/mp4">';
        echo 'Your browser does not support the video tag.';
        echo '</video>';
        echo '</div>';
    } else {
        echo '<p>No media found.</p>';
    }
    ?>

    <!-- Full-Screen Image Container -->
    <div class="fullscreen-image-container" id="fullscreenImageContainer">
        <span class="close-btn" id="closeFullscreen">×</span>
        <img id="fullscreenImage" class="fullscreen-image" src="" alt="Full-Screen Image">
    </div>

    <!-- jQuery, Popper.js, Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                goToPost('<?= $postId ?>');
            }
        });
        var zoomLevel = 1;
        var fullscreenImage = document.getElementById("fullscreenImage");
        var isZoomedIn = false;
        var isDragging = false;
        var lastX = 0;
        var lastY = 0;

        // Open full-screen image
        $(document).on('click', '.open-fullscreen', function() {
            var imageSrc = $(this).data('image');
            fullscreenImage.src = imageSrc;
            document.getElementById('fullscreenImageContainer').style.display = 'flex';
            document.getElementById('carouselContainer').style.display = 'none';
            zoomLevel = 1;
            isZoomedIn = false;
            fullscreenImage.style.transform = 'scale(1)';
            fullscreenImage.style.transformOrigin = 'center center';
        });

        // Close full-screen image
        document.getElementById('closeFullscreen').addEventListener('click', function() {
            document.getElementById('fullscreenImageContainer').style.display = 'none';
            document.getElementById('carouselContainer').style.display = 'block';
        });

        // Mousewheel zoom functionality
        fullscreenImage.addEventListener("wheel", function(e) {
            e.preventDefault();
            if (isZoomedIn) {
                if (e.deltaY < 0) {
                    zoomLevel += 0.1; // Zoom in
                } else {
                    zoomLevel -= 0.1; // Zoom out
                }
                zoomLevel = Math.max(1, Math.min(3, zoomLevel));
                this.style.transform = "scale(" + zoomLevel + ")";
            }
        });

        // Set zoom origin based on mouse position
        fullscreenImage.addEventListener("mousemove", function(e) {
            var rect = this.getBoundingClientRect();
            var offsetX = e.clientX - rect.left;
            var offsetY = e.clientY - rect.top;
            var zoomOriginX = offsetX / rect.width * 100;
            var zoomOriginY = offsetY / rect.height * 100;
            this.style.transformOrigin = `${zoomOriginX}% ${zoomOriginY}%`;
        });

        // Single tap to zoom functionality
        fullscreenImage.addEventListener("click", function(e) {
            if (!isZoomedIn) {
                zoomLevel = 2;
                this.style.transform = "scale(" + zoomLevel + ")";
                var rect = this.getBoundingClientRect();
                var offsetX = e.clientX - rect.left;
                var offsetY = e.clientY - rect.top;
                var zoomOriginX = offsetX / rect.width * 100;
                var zoomOriginY = offsetY / rect.height * 100;
                this.style.transformOrigin = `${zoomOriginX}% ${zoomOriginY}%`;
                isZoomedIn = true;
            } else {
                zoomLevel = 1;
                this.style.transform = "scale(1)";
                isZoomedIn = false;
            }
        });

        // Dragging functionality when zoomed in
        function startDrag(e) {
            if (isZoomedIn) {
                isDragging = true;
                lastX = e.clientX || e.touches[0].clientX;
                lastY = e.clientY || e.touches[0].clientY;
                fullscreenImage.style.cursor = "grabbing";
            }
        }

        function dragMove(e) {
            if (isDragging && isZoomedIn) {
                var dx = (e.clientX || e.touches[0].clientX) - lastX;
                var dy = (e.clientY || e.touches[0].clientY) - lastY;
                var currentTransform = window.getComputedStyle(fullscreenImage).transform;
                var matrix = new WebKitCSSMatrix(currentTransform);
                fullscreenImage.style.transform = currentTransform + " translate(" + dx + "px, " + dy + "px)";
                lastX = e.clientX || e.touches[0].clientX;
                lastY = e.clientY || e.touches[0].clientY;
            }
        }

        function stopDrag() {
            isDragging = false;
            fullscreenImage.style.cursor = "grab";
        }

        fullscreenImage.addEventListener("mousedown", startDrag);
        fullscreenImage.addEventListener("touchstart", startDrag);
        window.addEventListener("mousemove", dragMove);
        window.addEventListener("touchmove", dragMove);
        window.addEventListener("mouseup", stopDrag);
        window.addEventListener("touchend", stopDrag);

        // Scroll to selected image in carousel
        $(document).ready(function() {
            const selectedImage = <?php echo json_encode($selectedImage); ?>;
            const carousel = $('#imageCarousel');
            if (selectedImage) {
                const index = <?php
                                $index = array_search($selectedImage, $imagePaths);
                                echo $index !== false ? $index : 0;
                                ?>;
                carousel.carousel(index);
            }
        });

        function goToPost(postId) {
            if (document.referrer) {
                localStorage.setItem('scrollToPost', postId);
                window.location.href = document.referrer + '#post_' + <?= $postId ?>;
            } else window.history.back();
        }
    </script>
</body>

</html>