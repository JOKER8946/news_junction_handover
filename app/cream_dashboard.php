<? require_once './assets/php/validate.logged.php';
include './assets/php/function.php';
include './inc/config.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>News Junction</title>

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/js/bootstrap.min.js"></script>

  <!-- Bootstrap CSS -->
  <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="assets/js/script.js">
  <link rel="stylesheet" href="assets/css/styles.css">

  <!-- Custom CSS -->
  <style>
    /* Navbar brand */
    .navbar-brand {
      font-weight: 700;
      font-size: 1.5rem;
      color: #e50914 !important;
      /* Netflix Red */
    }

    /* Navbar links */
    .navbar-nav .nav-link {
      color: #bbb !important;
      transition: color 0.3s;
    }

    .navbar-nav .nav-link:hover {
      color: #fff !important;
    }

    .navbar {
      background-color: #141414;
    }

    .container-fluid {
      /* padding: 7rem 2%; */
    }

    /* Container Padding */
    @media screen and (max-width: 768px) {
      .container-fluid {
        padding: 40px 0 !important;
        max-width: 1400px;
        margin: 0 auto;

      }

    }


    /* Category Section */
    .category {
      margin-bottom: 2rem;
    }

    .category-title {
      margin-bottom: 2rem;
      font-size: 1.75rem;
      font-weight: 700;
      /* color: #fff; */
      text-align: center;
    }

    /* Scrollable Row */
    .scroll-row {
      display: flex;
      overflow-x: auto;
      padding-bottom: 1rem;
      scroll-behavior: smooth;
    }

    .scroll-row::-webkit-scrollbar {
      height: 8px;
    }

    .scroll-row::-webkit-scrollbar-thumb {
      background-color: #555;
      border-radius: 4px;
    }

    .scroll-row::-webkit-scrollbar-track {
      background-color: #222;
    }

    .scroll-row {
      display: flex;
      justify-content: center;
      flex-wrap: nowrap;
      overflow-x: auto;
      gap: 4px;
      padding-bottom: 10px;
    }

    .item-card {
      flex: 0 0 auto;
      /* Prevent flex items from shrinking */
      max-width: 200px;
      min-width: 200px;
      margin-right: 0.5rem;
      background-color: #1c1c1c !important;
      border: none;
      border-radius: 8px;
      transition: transform 0.3s, box-shadow 0.3s;
      cursor: pointer;
    }



    /* Card Styles */
    .item-card:last-child {
      margin-right: 0;
    }

    .item-card:hover {
      transform: scale(1.05);
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.6);
      z-index: 2;
    }

    .item-card img {
      border-top-left-radius: 8px;
      border-top-right-radius: 8px;
      height: 120px;
      object-fit: cover;
    }

    .item-card .card-body {
      padding: 0.5rem 1rem;
    }

    .item-card .card-title {
      font-size: 1rem;
      margin-bottom: 0.5rem;
      color: #fff;
      text-align: center;
    }

    .card-para {
      color: #fff !important;
    }

    .item-card .btn {
      padding: 0.25rem 0.5rem;
      font-size: 0.75rem;
      border-radius: 4px;
      display: block;
      margin: 0 auto;
      width: 60%;
    }

    /* Action Buttons Section */
    .actions-section {
      margin-top: 2rem;
      margin-bottom: 2rem;
    }

    .action-card {
      background-color: #333 !important;

      border: none;
      border-radius: 8px;
      transition: background-color 0.3s, transform 0.3s;
      cursor: pointer;
    }

    .action-card:hover {
      background-color: #444 !important;
      transform: translateY(-5px);
    }

    .action-card .card-body {
      text-align: center;
      padding: 2rem 1rem;
    }

    .action-card .card-title {
      margin-bottom: 1rem;
      font-size: 1.25rem;
      font-weight: 700;
      color: #fff;
    }

    .action-card .btn {
      margin-top: 1rem;
      padding: 0.5rem 1rem;
      font-size: 1rem;
      border-radius: 4px;
    }

    .icon {
      color: white !important;
      /* White color for the icons */
      /* margin-right: 10px; */
      border: none;
    }

    .navbar-toggler:focus {
      border-color: black !important;
      border: none !important;
      outline: none;
    }



    .tooltip {
      position: relative;
      display: inline-block;
    }

    .tooltiptext {
      visibility: hidden;
      width: 120px;
      background-color: black;
      color: #fff;
      text-align: center;
      border-radius: 5px;
      padding: 5px;
      position: absolute;
      z-index: 1;
      bottom: 125%;
      /* Position above the icon */
      left: 50%;
      margin-left: -60px;
      /* Center the tooltip */
      opacity: 0;
      /* Hide tooltip initially */
      transition: opacity 0.3s;
    }

    .tooltip:hover .tooltiptext {
      visibility: visible;
      opacity: 1;
      /* Show tooltip on hover */
    }

    .action-card {
      height: 220px;
      /* Set a fixed height */
      display: flex;
      /* Use flexbox for vertical centering */
      flex-direction: column;
      /* Align children vertically */
      justify-content: center;
      /* Center content vertically */
      text-align: center;
      /* Center text horizontally */
    }

    .dropdown-menu {
      position: absolute;
      top: 100%;
      left: -100px;
      z-index: 1000;
      display: none;
      float: left;
      min-width: 10rem;
      padding: .5rem 0;
      margin: .125rem 0 0;
      font-size: 1rem;
      color: #212529;
      text-align: left;
      list-style: none;
      background-color: #fff;
      background-clip: padding-box;
      border: 1px solid rgba(0, 0, 0, .15);
      border-radius: .25rem;
    }

    /* 
    .modal-content {
      background-color: #1c1c1c !important;
      color: white !important;
    } */

    .modal-body {
      padding: 20px;
    }

    /* .morewithlike i {
      color: #a3a2a2 !important;
      border: none;
      padding: 0;
    } */

    .morewithlike button::after {
      border-color: pink;
      /* Ensure no outline when focused */
      border: none;
      outline: none;

    }

    .modal-title {
      max-width: 88%;
    }

    /* Responsive Adjustments */
    @media (max-width: 992px) {
      .item-card {
        min-width: 180px;
      }

      .category-title {
        font-size: 1.5rem;
      }
    }

    @media (max-width: 768px) {
      .item-card {
        min-width: 160px;
      }

      .category-title {
        font-size: 1.25rem;
      }

      .action-card .card-title {
        font-size: 1.1rem;
      }

      .action-card .btn {
        font-size: 0.95rem;
      }

    }

    @media (max-width: 576px) {
      .navbar-brand {
        font-size: 1.25rem;
      }

      .nav-link {
        /* margin-left: 0.5rem; */
        font-size: 0.9rem;
        padding: .5rem 0rem;
      }

      .item-card {
        max-width: 140px;
      }

      .category-title {
        font-size: 1.4rem !important;
      }

      .action-card .card-title {
        font-size: 1rem;
      }

      .action-card .btn {
        font-size: 0.9rem;
      }

      .scroll-row {
        display: flex;
        min-width: 120px !important;
        justify-content: center;
        flex-wrap: wrap;
        overflow-x: auto;
        gap: 8px;
        padding-bottom: 10px;
      }

      .item-card {
        flex: 0 0 auto;
        /* Prevent flex items from shrinking */
        /* max-width: 200px; */
        min-width: 120px !important;
        margin-right: 0px;
        background-color: #1c1c1c !important;
        border: none;
        border-radius: 8px;
        transition: transform 0.3s, box-shadow 0.3s;
        cursor: pointer;
      }
    }

    .go-back-bar {
      margin-top: 100px;
    }
  </style>
</head>

<body>

  <!-- Navbar -->
  <?php include 'assets/php/navbar.php' ?>
  <? include 'assets/php/go_backbar.php' ?>


  <div class="container-fluid">
    <!-- Featured Channels Category -->
    <div class="category">
      <h2 class="category-title">Create</h2>
      <div class="scroll-row">
        <!-- Channel Cards with Logos -->

        <div class="card item-card">
          <a href="create.php">
            <img src="inc/img/editor.jpg" class="card-img-top" alt="TOI Logo">
            <div class="card-body">
              <h5 class="card-title py-2">Editor</h5>
              <!-- <a href="featured_channels.php?rss_id=9" class="btn btn-primary">Read</a> -->
            </div>
          </a>
        </div>


        <div class="card item-card">
          <a href="./genai/genai.php">
            <img src="inc/img/genAI.jpg" class="card-img-top" alt="Deccan Herald Logo">
            <div class="card-body">
              <h5 class="card-title py-2">Buddy</h5>
              <!-- <a href="featured_channels.php?rss_id=38" class="btn btn-primary">Read</a> -->
            </div>
          </a>
        </div>

        <div class="card item-card">
          <a href="./request_article.php">
            <img src="inc/img/equest_article.jpg" class="card-img-top" alt="The Hindu Logo">
            <div class="card-body">
              <h5 class="card-title py-2">GenAi Creator</h5>
              <!-- <a href="featured_channels.php?rss_id=33" class="btn btn-primary">Read</a> -->
            </div>
          </a>
        </div>
        <div class="card item-card">
          <a href="CreatePage">
            <img src="inc/img/landing-page.webp" class="card-img-top" alt="The Hindu Logo">
            <div class="card-body">
              <h5 class="card-title py-2">Landing Page</h5>
            </div>
          </a>
        </div>
        <!-- <div class="card item-card">
          <a href="videoAI/index.php">
            <img src="inc/img/video.png" class="card-img-top" alt="The Hindu Logo">
            <div class="card-body">
              <h5 class="card-title py-2">Create Video <i>(Beta)</i></h5>
              
            </div>
          </a>
        </div> -->

      </div>
    </div>

    <!-- Featured Topics Category -->
    <div class="category">
      <h2 class="category-title">Reach</h2>
      <div class="scroll-row">
        <!-- Repeat Item Cards as Needed -->
        <div class="card item-card" onclick="window.location.href='./card-news.html'">
          <a href="newsletter.php">
            <img src="inc/img/NL.jpg" class="card-img-top" alt="Channel 1">
            <div class="card-body">
              <h5 class="card-title py-2 ">Newsletter</h5>
              <!-- <a href="featured_topics.php?ft_id=21" class="btn btn-primary">Read</a> -->
            </div>
          </a>
        </div>
        <div class="card item-card">
          <a href="xpress_landing_page.php">
            <img src="inc/img/mailer.jpg" class="card-img-top" alt="Channel 10">
            <div class="card-body">
              <h5 class="card-title py-2">Xpress</h5>
              <!-- <a href="featured_topics.php?ft_id=13" class="btn btn-primary">Read</a> -->
            </div>
          </a>
        </div>
        <!-- <div class="card item-card">
          <img src="inc/img/new_mag.jpg" class="card-img-top" alt="Channel 9">
          <div class="card-body">
            <h5 class="card-title py-2">Magazine</h5>
            <a href="featured_topics.php?ft_id=10" class="btn btn-primary">Read</a>
          </div>
        </div> -->
        <!-- <div class="card item-card">
          <a href="Xpress/createList.php">
            <img src="inc/img/manage_newsletter.jpg" class="card-img-top" alt="Channel 2">
            <div class="card-body">
              <h5 class="card-title py-2">Manage Newsletter</h5>

            </div>
          </a>
        </div> -->
      </div>
    </div>

    <div class="category">
      <h2 class="category-title">Measure</h2>
      <div class="scroll-row">
        <!-- Repeat Item Cards as Needed -->
        <div class="card item-card" onclick="window.location.href='./card-news.html'">
          <a href="analytics.php">
            <img src="inc/img/analytices.jpg" class="card-img-top" alt="Channel 1">
            <div class="card-body">
              <h5 class="card-title py-2">Analytics</h5>
              <!-- <a href="featured_topics.php?ft_id=21" class="btn btn-primary">Read</a> -->
            </div>
          </a>
        </div>



      </div>

      <!-- <div class="category">
        <h2 class="category-title">Collaboration</h2>
        <div class="scroll-row">
          Repeat Item Cards as Needed
          <div class="card item-card" onclick="window.location.href='./card-news.html'">
            <a href="./groupchat/index.php">
              <img src="inc/img/group.jpg" class="card-img-top" alt="Channel 1">
              <div class="card-body">
                <h5 class="card-title py-2">Groups</h5>
                <a href="featured_topics.php?ft_id=21" class="btn btn-primary">Read</a>
              </div>
            </a>
          </div>
        </div>
      </div> -->




      <!-- Influencers Category -->


    </div>

  </div>

  <!-- Footer -->
  <? include 'assets/php/bottom_navbar.php' ?>
  <? include 'assets/php/footer.php' ?>




  <!-- Bootstrap JS -->
  <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> -->
  <!-- jQuery -->

  <!-- <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script> -->
  <!-- <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script> -->
  <!-- <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script> -->
  <!-- Bootstrap CSS -->
  <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet"> -->
  <!-- Bootstrap JS -->
  <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script> -->

  <!-- <script>
    $(document).ready(function() {
      const newsModal = new bootstrap.Modal($('#newsModal'));

      $('.read-more').on('click', function(event) {
        event.preventDefault(); // Prevent default anchor behavior

        const title = $(this).data('title');
        const description = $(this).data('description');
        const image = $(this).data('image');
        const url = $(this).data('url');
        const date = $(this).data('date');
        console.log(date);

        // Populate modal with data
        $('#newsModalLabel').text(title);
        $('#modal-description').text(description);
        $('#modal-image').attr('src', image);
        $('#modal-url').attr('href', url);
        $('#modal-date').text(date);

        // Show the modal
        newsModal.show();
      });
    });
  </script>

  <script>
    const userId = <?= $gUserId ?>;
    $(document).ready(function() {
      $('.news-item').on('click', function() {
        var feed_id = $(this).data('id');
        var title = $(this).data('title');
        var description = $(this).data('description');
        var image = $(this).data('image');
        var date = $(this).data('date');
        var url = $(this).data('url');

        $('#dataModal').attr('data-id', feed_id);
        $('#dataModal').attr('data-title', title);
        $('#newsModalLabel').text(title);
        $('#modal-description').text(description);
        $('#modal-image').attr('src', image);
        $('#modal-date').text(date);
        $('#modal-url').attr('href', url);

        // loadLike(feed_id, userId);
        // likeCount(feed_id, userId);
        checkCollection(feed_id, userId)

        var modal = new bootstrap.Modal($('#newsModal')[0]);


        modal.show();
      });


      $('.likeButton').on('click', function() {
        var feed_id = $('#dataModal').attr('data-id');
        toggleLike(feed_id, userId);
      });

      $('.copyButton').on('click', function() {
        var feed_url = $('#modal-url').attr('href');
        // var feed_url = $('.news-item').data('url');
        copyToClipboard(feed_url); // Call the correct function with the correct variable
      });

      $('#bookmarkIcon').on('click', function() {
        var feed_id = $('#dataModal').attr('data-id'); // Ensure this targets the correct item
        console.log(feed_id);

        // Check the current state of the icon
        if ($(this).hasClass('far fa-bookmark')) {
          addToCollection(feed_id, userId);
        } else {
          removeFromCollection(feed_id, userId);
        }
      });



    });

    function loadLike(feedId, userId) {
      $.ajax({
        url: 'inc/handler.php',
        type: 'POST',
        contentType: 'application/json', // Specify that you're sending JSON
        data: JSON.stringify({
          request: 'loadLike',
          userId: userId,
          feedId: feedId
        }),
        success: function(response) {
          if (response.status === "success") {
            console.log("Response:", response.response); // Access 'response' instead of 'data'
            if (response.response === true) {
              $('.likeButton #thumbsUp').removeClass('fa-regular').addClass('fa-solid'); // Change to solid thumbs up
            } else {
              $('.likeButton #thumbsUp').removeClass('fa-solid').addClass('fa-regular'); // Change to regular thumbs up
            }
          } else {
            console.error("Error message:", response.message);
          }
        },
        error: function(jqXHR, textStatus, errorThrown) {
          console.error("Error:", textStatus, errorThrown);
        }
      });
    }

    function likeCount(feedId, userId) {
      $.ajax({
        url: 'inc/handler.php',
        type: 'POST',
        contentType: 'application/json', // Specify that you're sending JSON
        data: JSON.stringify({
          request: 'likeCount',
          userId: userId,
          feedId: feedId
        }),
        success: function(response) {
          if (response.status === "success") {
            console.log("Response:", response); // Access 'response' instead of 'data'
            $('.likeButton #likeCount').html(response.count || ''); // Change to solid thumbs up

          } else {
            console.error("Error message:", response.message);
          }
        },
        error: function(jqXHR, textStatus, errorThrown) {
          console.error("Error:", textStatus, errorThrown);
        }
      });
    }

    function toggleLike(feedId, userId) {
      var thumbsUpIcon = $('.likeButton #thumbsUp');
      var isLiked = thumbsUpIcon.hasClass('fa-solid');

      // Determine the request type based on the current like status
      var requestType = isLiked ? 'unlike' : 'like';

      // Make the AJAX call to like/unlike the post
      $.ajax({
        url: 'inc/handler.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
          request: requestType,
          userId: userId,
          feedId: feedId
        }),
        success: function(response) {
          if (response.status === "success") {
            // Toggle the icon based on the like status
            if (requestType === 'like') {
              thumbsUpIcon.removeClass('fa-regular').addClass('fa-solid');
            } else {
              thumbsUpIcon.removeClass('fa-solid').addClass('fa-regular');
            }
            likeCount(feedId, userId);
          } else {
            console.error("Error message:", response.message);
          }
        },
        error: function(jqXHR, textStatus, errorThrown) {
          console.error("Error:", textStatus, errorThrown);
        }
      });
    }
  </script>

  <script>
    function copyToClipboard(feedUrl) {
      // var feedUrl = `http://localhost/new_reader/feed/${feedId}`; // Adjust this to your actual URL structure

      navigator.clipboard.writeText(feedUrl).then(function() {
        alert('Feed URL copied to clipboard: ' + feedUrl);
      }).catch(function(error) {
        console.error('Error copying text: ', error);
      });
    }
  </script>

  <script>
    function checkCollection(feedId, userId) {
      $.ajax({
        url: 'inc/handler.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
          request: 'checkColl',
          userId: userId,
          feedId: feedId
        }),
        success: function(response) {
          try {
            if (response.status === 'success') {
              if (response.count > 0) {
                // console.log(response.count);
                $('#bookmarkIcon').removeClass('far fa-bookmark').addClass('fas fa-bookmark');
              } else {
                $('#bookmarkIcon').removeClass('fas fa-bookmark').addClass('far fa-bookmark');
              }
            } else {
              console.error('Failed to check collection: ' + response.message);
            }
          } catch (e) {
            console.error('Parsing error:', e);
            // alert('An error occurred while processing your request.'); // User-friendly error message
          }
        },
        error: function(xhr, status, error) {
          console.error('AJAX request failed: ' + error);
          alert('Failed to check collection. Please check your connection and try again.'); // User-friendly error message
        }
      });
    }

    function addToCollection(feedId, userId) {
      $.ajax({
        url: 'inc/handler.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
          request: 'addColl',
          userId: userId,
          feedId: feedId
        }),
        success: function(response) {
          if (response.status === 'success') {
            console.log('Added to collection');
            $('#bookmarkIcon').removeClass('far fa-bookmark').addClass('fas fa-bookmark');
          } else {
            console.error('Failed to add to collection: ' + response.message);
          }
        },
        error: function(xhr, status, error) {
          console.error('AJAX request failed: ' + error);
        }
      });
    }

    function removeFromCollection(feedId, userId) {
      $.ajax({
        url: 'inc/handler.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
          request: 'removeColl',
          userId: userId,
          feedId: feedId
        }),
        success: function(response) {
          if (response.status === 'success') {
            console.log('Removed from collection');
            $('#bookmarkIcon').removeClass('fas fa-bookmark').addClass('far fa-bookmark');
          } else {
            console.error('Failed to remove from collection: ' + response.message);
          }
        },
        error: function(xhr, status, error) {
          console.error('AJAX request failed: ' + error);
        }
      });
    }
  </script>

  <script>
    var isPlaying = false;

    $(document).ready(function() {

      // Play button click handler
      $('.play-button').on('click', function() {
        var title = $('#dataModal').data('title');
        var desc = $('#dataModal').data('description');
        playAudio(title, desc, $(this));
      });

      // Function to play audio
      function playAudio(title, desc, button) {
        var audio = new Audio();

        if (isPlaying) {
          console.log("Audio is already playing. Cannot start another instance.");
          return;
        }

        console.log("Starting audio playback...");

        isPlaying = true; // Set flag to true to indicate audio playback has started

        // Ajax call to get audio file URL
        $.ajax({
          url: 'texttovoice/processvoice.php',
          type: 'POST',
          data: {
            title: title,
            description: desc
          },
          success: function(response) {
            audio.src = response;
            audio.play();

            // Update button visibility
            button.hide();
            button.siblings('.pause-button').show();
            button.siblings('.stop-button').show();

            // Pause button click handler
            button.siblings('.pause-button').on('click', function() {
              audio.pause();
              $(this).hide();
              $(this).siblings('.resume-button').show();
            });

            // Resume button click handler
            button.siblings('.resume-button').on('click', function() {
              audio.play();
              $(this).hide();
              $(this).siblings('.pause-button').show();
            });

            button.siblings('.stop-button').on('click', function() {
              audio.pause(); // Pause the audio (assuming this stops playback)
              audio.currentTime = 0; // Reset audio playback to the beginning
              button.siblings('.resume-button').hide(); // Hide resume button if shown
              button.siblings('.stop-button').hide();
              button.siblings('.pause-button').hide();
              button.show();
              isPlaying = false;
            });

            // Reset when audio ends
            audio.onended = function() {
              isPlaying = false;
              button.show();
              button.siblings('.pause-button').hide();
              button.siblings('.resume-button').hide();
              button.siblings('.stop-button').hide();
            };
          },
          error: function(xhr, status, error) {
            console.error("Error playing audio:", error);
          }
        });
      }
    });

  </script> -->


</body>

</html>