<?php
// Get the image paths and the selected image from the URL
$imagePaths = isset($_GET['imagePaths']) ? explode(",", $_GET['imagePaths']) : [];
$selectedImage = isset($_GET['selectedImage']) ? $_GET['selectedImage'] : '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

    <title>Image Viewer</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #000;
            color: #fff;
            overflow: hidden;
            height: 100vh;
        }

        .carousel-inner img {
            max-height: 80vh;
            width: 100%;
            object-fit: contain;
            cursor: pointer;
        }

        .carousel-container {
            width: 80%;
            height: 80%;
            margin: auto;
            top: 5%;
            position: relative;
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
                top: 30%;
            }

            .modal-content {
                height:100vh;

            }
            body{
                height:50vh;
            }
        }

        /* Modal Styles */
        .modal-dialog {
            max-width: 95%;
        }
        .modal-content {
background-color:#000;
            }
            .close{
                color: #fafafa;
            }
            .close:hover{
                color: #fafafa;
            }

        .img-container {
            text-align: center;
            overflow: hidden;
        }

        #zoomImage {
            width: 100%;
            /* height: 80vh; */
            transition: transform 0.25s ease;
            cursor: zoom-in;
            transform-origin: center center;
            position: relative;
        }
        .go-back-bar {
    margin-top: 30px;
}
    </style>
</head>

<body>
    <? include 'inc/php/go_backbar.php'?>
    <div class="carousel-container">
        <div id="imageCarousel" class="carousel slide" data-interval="false">
            <!-- Indicators (Dots) -->
            <ol class="carousel-indicators">
                <?php
                // Loop through the images to create the indicator dots
                foreach ($imagePaths as $index => $imagePath) {
                    // Check if this is the selected image
                    $activeClass = ($imagePath == $selectedImage || $index == 0) ? 'active' : '';
                    echo '<li data-target="#imageCarousel" data-slide-to="' . $index . '" class="' . $activeClass . '"></li>';
                }
                ?>
            </ol>

            <div class="carousel-inner">
                <?php
                // Loop through the images to create the carousel items
                $isActive = true; // First image should be active
                foreach ($imagePaths as $imagePath) {
                    // Check if this is the selected image
                    $activeClass = ($imagePath == $selectedImage || $isActive) ? 'active' : '';
                    echo '<div class="carousel-item ' . $activeClass . '">';
                    echo '<img src="' . htmlspecialchars($imagePath) . '" class="d-block w-100" alt="Image" data-toggle="modal" data-target="#zoomModal" data-image="' . htmlspecialchars($imagePath) . '">';
                    echo '</div>';

                    $isActive = false; // After the first item, all others should not be active
                }
                ?>
            </div>

            <!-- Carousel Controls -->
            <a class="carousel-control-prev" href="#imageCarousel" role="button" data-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="sr-only">Previous</span>
            </a>
            <a class="carousel-control-next" href="#imageCarousel" role="button" data-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="sr-only">Next</span>
            </a>
        </div>
    </div>

    <!-- Modal for Zoomed Image -->
    <div class="modal fade" id="zoomModal" tabindex="-1" role="dialog" aria-labelledby="zoomModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true" >&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="img-container">
                        <img id="zoomImage" src="" alt="Zoomed Image">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery, Popper.js, Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        var zoomLevel = 1;
        var zoomImage = document.getElementById("zoomImage");
        var isZoomedIn = false; // Flag to track zoom state
        var isDragging = false; // Flag to track dragging state
        var lastX = 0;
        var lastY = 0;

        // Open the modal and load the image
        $('#zoomModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget); // Button that triggered the modal
            var imageSrc = button.data('image'); // Extract the image source from data-* attributes
            var modal = $(this);

            zoomImage.src = imageSrc;
            zoomImage.style.transform = "scale(1)"; // Reset zoom on modal open
            zoomImage.style.transformOrigin = "center center"; // Set default zoom origin
            zoomLevel = 1;
            isZoomedIn = false;
        });

        // Mousewheel zoom functionality
        zoomImage.addEventListener("wheel", function(e) {
            e.preventDefault();

            // Only zoom if image is zoomed in already
            if (isZoomedIn) {
                // Increase or decrease zoom level based on wheel direction
                if (e.deltaY < 0) {
                    zoomLevel += 0.1; // Zoom in
                } else {
                    zoomLevel -= 0.1; // Zoom out
                }

                // Limit zoom level
                zoomLevel = Math.max(1, Math.min(3, zoomLevel));

                // Apply zoom and set transform origin based on mouse position
                this.style.transform = "scale(" + zoomLevel + ")";
            }
        });

        // Set zoom origin based on mouse position when clicking on image
        zoomImage.addEventListener("mousemove", function(e) {
            var rect = this.getBoundingClientRect();
            var offsetX = e.clientX - rect.left;
            var offsetY = e.clientY - rect.top;

            // Normalize to a percentage (0 to 1)
            var zoomOriginX = offsetX / rect.width * 100;
            var zoomOriginY = offsetY / rect.height * 100;

            this.style.transformOrigin = `${zoomOriginX}% ${zoomOriginY}%`;
        });

        // Single tap to zoom functionality
        zoomImage.addEventListener("click", function(e) {
            if (!isZoomedIn) {
                // Zoom in on first click
                zoomLevel = 2; // Set desired zoom level
                this.style.transform = "scale(" + zoomLevel + ")";

                // Set zoom origin based on the click position
                var rect = this.getBoundingClientRect();
                var offsetX = e.clientX - rect.left;
                var offsetY = e.clientY - rect.top;
                var zoomOriginX = offsetX / rect.width * 100;
                var zoomOriginY = offsetY / rect.height * 100;

                this.style.transformOrigin = `${zoomOriginX}% ${zoomOriginY}%`;
                isZoomedIn = true; // Mark as zoomed in
            } else {
                // Zoom out on second click
                zoomLevel = 1;
                this.style.transform = "scale(1)";
                isZoomedIn = false; // Mark as zoomed out
            }
        });

        // Dragging functionality when zoomed in (Desktop and Mobile)
        function startDrag(e) {
            if (isZoomedIn) {
                isDragging = true;
                lastX = e.clientX || e.touches[0].clientX;
                lastY = e.clientY || e.touches[0].clientY;
                zoomImage.style.cursor = "grabbing"; // Change cursor to grabbing when dragging
            }
        }

        function dragMove(e) {
            if (isDragging && isZoomedIn) {
                var dx = (e.clientX || e.touches[0].clientX) - lastX;
                var dy = (e.clientY || e.touches[0].clientY) - lastY;

                // Move the image based on mouse movement
                var currentTransform = window.getComputedStyle(zoomImage).transform;
                var matrix = new WebKitCSSMatrix(currentTransform);

                zoomImage.style.transform = currentTransform + " translate(" + dx + "px, " + dy + "px)";
                lastX = e.clientX || e.touches[0].clientX;
                lastY = e.clientY || e.touches[0].clientY;
            }
        }

        function stopDrag() {
            isDragging = false;
            zoomImage.style.cursor = "grab"; // Change cursor back to grab
        }

        // Add event listeners for mouse and touch events for drag functionality
        zoomImage.addEventListener("mousedown", startDrag);
        zoomImage.addEventListener("touchstart", startDrag);

        window.addEventListener("mousemove", dragMove);
        window.addEventListener("touchmove", dragMove);

        window.addEventListener("mouseup", stopDrag);
        window.addEventListener("touchend", stopDrag);
    </script>
</body>

</html>