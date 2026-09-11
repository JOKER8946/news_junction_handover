<!doctype html>
<?php

error_reporting(E_ALL & ~E_WARNING);
ini_set('display_errors', '0');  // Hide errors from the browser

include 'inc/config.php';
// include 'conversations/functions.php';

$chat = [];

function createArticleURL($title)
{
  if ($title <> '') {
    $title = str_replace(' ', '-', $title);
    $title = str_replace('%', '', $title);
    $title = str_replace("'", "", $title);
    return $title;
  } else {
    return '';
  }
}


$id = isset($_GET["id"]) ? $_GET["id"] : '';
if ($id <> '') {
  $sql = "SELECT * FROM user_collection WHERE id=$id";
  $result = mysqli_query($db, $sql);
  $row = $result->fetch_assoc();
  $numRows = mysqli_num_rows($result);
  if ($numRows == 0) die();

  // $serverName = "newsjunction.net";
  $serverName = $_SERVER['SERVER_NAME'];
  $collectionUserId = $row['user_id'];
  $sql = "SELECT subdomain,is_side_panel,side_panel_content FROM user WHERE id=$collectionUserId";
  $resultInner = mysqli_query($db, $sql);
  $rowInner = mysqli_fetch_assoc($resultInner);
  $userSubdomain = $rowInner['subdomain'];
  $userSidePanel = $rowInner['is_side_panel'];
  $userSidePanelContent = $rowInner['side_panel_content'];
  $userSidePanelContent = str_replace('<img src="data/', '<img src="https://' . $serverName . '/data/', $userSidePanelContent);



  // $chat = fetch_messages($conn1, $id);
  // $chat = fetch_messages($db, $id);



  $ip = getenv('HTTP_CLIENT_IP') ?: getenv('HTTP_X_FORWARDED_FOR') ?: getenv('HTTP_X_FORWARDED') ?: getenv('HTTP_FORWARDED_FOR') ?: getenv('HTTP_FORWARDED') ?: getenv('REMOTE_ADDR');
  $response = unserialize(file_get_contents('http://www.geoplugin.net/php.gp?ip=' . $ip));

  if ($response === false) {
    $visitCity = '';
    $visitCountry = '';
  } else {
    $visitCity = $response['geoplugin_city'];
    $visitCountry = $response['geoplugin_countryName'];
  }
  $sql = "INSERT INTO metrics(article_id,ip,visit_city,visit_country,date_visited,category) VALUES($id,'$ip','$visitCity','$visitCountry',Now(), 'Anonymous')";
  mysqli_query($db, $sql);

  $collectionURL = $row['url'];
  if ($collectionURL <> '') {
    header("Location: " . $collectionURL);
    die();
  }

  $collectionTitle = $row['title'];
  $collectionDesc = $row['description'];

  $collectionDesc = preg_replace('/<img([^>]*?)src="data\/([^"]+)"/', '<img$1src="https://' . $serverName . '/data/$2"', $collectionDesc);
  // $collectionDesc = str_replace('<img src="data/', '<img src="https://' . $serverName . '/data/', $collectionDesc);
  $collectionDesc = str_replace('\n', '<br>', $collectionDesc);
  $collectionDesc = str_replace('\\', '', $collectionDesc);
  $collectionShareUserId = $row['share_user_id'];
  $collectionPublisher = substr($collectionURL, strpos($collectionURL, ".") + 1);
  $collectionPublisher = ucfirst(strtok($collectionPublisher, '.'));
  if ($collectionPublisher == '') $collectionPublisher = 'Cream';
  $collectionDate = date('d M, Y', strtotime($row['date_added']));
  $collectionLink = 'https://' . $serverName . '/view/' . $id . '/' . createArticleURL($collectionTitle);
  $collectionImgCover = $row['cover_img'];
  $collectionIsReadMore = $row['is_read_more'];
  $collectionReadMoreTxt = $row['read_more_txt'];
  if ($collectionReadMoreTxt == '') $collectionReadMoreTxt = "Read More";

  if ($collectionShareUserId <> '') {
    $sql = "SELECT full_name,company,website,news_title,news_logo FROM user WHERE id=$collectionShareUserId";
  } else {
    $sql = "SELECT full_name,company,website,news_title,news_logo FROM user WHERE id=$collectionUserId";
  }
  $result = mysqli_query($db, $sql);
  $row = mysqli_fetch_assoc($result);
  $user = $row['full_name'];
  $companyName = $row['company'];
  $website = strtolower($row['website']);
  $newsTitle = $row['news_title'];
  $newsLogo = $row['news_logo'];
  if (strpos($website, 'http') === false) $website = "http://$website";
?>
  <html lang="en">

  <head>
    <title><?= $collectionTitle ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    <!-- Facebook Meta Tags -->
    <meta property="og:url" content="<?= $collectionLink ?>" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="<?= $collectionTitle ?>" />
    <meta property="og:description" content="<?= $collectionTitle ?>" />
    <? if ($collectionImgCover <> '') { ?>
      <meta property="og:image" content="https://<?= $serverName ?>/data/covers/<?= $collectionImgCover ?>" />
      <meta property="og:image:secure-url" itemprop="image" content="https://<?= $serverName ?>/data/covers/<?= $collectionImgCover ?>" />
    <? } else { ?>
      <meta property="og:image" content="https://<?= $serverName ?>/data/logos/<?= $newsLogo ?>" />
      <meta property="og:image:secure-url" itemprop="image" content="https://<?= $serverName ?>/data/logos/<?= $newsLogo ?>" />
    <? } ?>

    <!-- Twitter Meta Tags -->
    <meta property="twitter:url" content="<?= $collectionLink ?>" />
    <meta name="twitter:card" content="summary" />
    <meta name="twitter:title" content="<?= $companyName ?>" />
    <meta name="twitter:description" content="" />
    <? if ($collectionImgCover <> '') { ?>
      <meta name="twitter:image" content="https://<?= $serverName ?>/data/covers/<?= $collectionImgCover ?>" />
    <? } else { ?>
      <meta name="twitter:image" content="https://<?= $serverName ?>/data/logos/<?= $newsLogo ?>" />
    <? } ?>

    <link rel="shortcut icon" href="https://<?= $serverName ?>/data/logos/<?= $newsLogo ?>" type="image/x-icon" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous" />
    <link rel="stylesheet" href="inc/fontawesome/css/all.min.css" />
    <link rel="stylesheet" href="/inc/style.css" />
    <link rel="icon" type="image/x-icon" href="/img/logo.ico">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>

    <script>
      // Disable text selection (copy)
      document.addEventListener('selectstart', function(e) {
        e.preventDefault();
        return false;
      });

      // Disable keyboard shortcuts (Ctrl+C, Ctrl+X, Ctrl+A, Print Screen)
      document.addEventListener('keydown', function(e) {
        // Ctrl+C, Ctrl+X, Ctrl+A, Ctrl+S
        if ((e.ctrlKey || e.metaKey) && (e.key === 'c' || e.key === 'x' || e.key === 'a' || e.key === 's')) {
          e.preventDefault();
          return false;
        }
        // Ctrl+Shift+I (Developer Tools)
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'i' && e.key === 's') {
          e.preventDefault();
          return false;
        }
        // F12 (Developer Tools)
        if (e.key === 'F12') {
          e.preventDefault();
          return false;
        }
      });

      // Disable drag and drop
      document.addEventListener('dragstart', function(e) {
        e.preventDefault();
        return false;
      });

      // Disable user select via CSS
      document.body.style.userSelect = 'none';
      document.body.style.webkitUserSelect = 'none';
      document.body.style.msUserSelect = 'none';
    </script>
    <style>
      body {
        font-size: 16px;

      }

      .panelContent img {
        max-width: 100%;
        height: auto;
      }
    </style>
    <style>
      .conversations {
        max-height: 300px;
        overflow-y: scroll;
      }

      .comments-section {
        margin-top: 40px;
        margin-bottom: 80px;
        margin-left: 90px;
        margin-right: 90px;

      }

      .comment-form {
        margin-bottom: 20px;
      }

      .comment-form textarea {
        width: 100%;
        height: 100px;
        margin-bottom: 10px;
      }

      .comment {
        width: fit-content;
        margin-bottom: 20px;
        padding: 10px 20px;
        /* border: 1px solid #ccc; */
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        border-radius: 5px;
      }

      .comment .author {
        font-weight: bold;
      }

      .conversation-form {
        display: flex;
        width: 100%;
        column-gap: 8px;
        justify-content: left;
        margin: 10px 0px;
        margin-bottom: 20px;
      }

      .comment-text {
        border-radius: 6px;
        padding: 5px 10px;
        width: 80%;
        height: 40px;
      }

      .conversation-btn {
        border-radius: 5px;

      }

      .author span {
        padding-left: 10px;
      }

      .login-redirect {
        display: flex;
        justify-content: center;
        max-width: 100%;


      }

      .login-redirect span {
        border: 0.2px solid black;
        padding: 8px 16px;
        border-radius: 10px;
        margin: 10px 20px;
        width: 600px;
        display: flex;
        justify-content: center;
      }

      .login-redirect a:hover {
        text-decoration: none;
        color: #000;
      }

      .login-redirect span:hover {
        background-color: #d9d9e18f;
      }
    </style>
  </head>

  <body>
    <div class="container">

      <? if ($newsLogo <> '' && $user <> '' && $companyName <> '') { ?>
        <header class="blog-header py-3 border-bottom">
          <div class="row flex-nowrap justify-content-between align-items-center">
            <div class="col-8 pt-1">
              <? if ($newsLogo <> '') { ?>
                <a class="text-muted" href="<?= $website ?>"><img class="mx-auto" width="256px" src="https://<?= $serverName ?>/data/logos/<?= $newsLogo ?>" /></a>
              <? } ?>
              <h6 class="mt-1" style="color:#9e9e9e"><?= $user ?> | <?= $companyName ?></h6>
            </div>
            <div class="col-4 text-right">
            </div>
          </div>
        </header>
      <? } ?>

      <div class="row mt-3 pb-5">
        <div class="panelContent col-12 col-md pb-3">
          <h1><?= $collectionTitle ?></h1>
          <h6 style="color:#9e9e9e"><?= $collectionDate ?></h6>
          <? if ($collectionImgCover <> '') { ?>
            <div class="my-2"><img src="https://<?= $serverName ?>/data/covers/<?= $collectionImgCover ?>" /></div>
          <? } ?>
          <p><?= $collectionDesc ?></p>
          <? if ($collectionIsReadMore <> '') { ?>
            <center>
              <a href="https://<?= $serverName ?>/more.php?id=<?= $id ?>" target="_blank">
                <div class="btn btn-warning py-2"><?= $collectionReadMoreTxt ?></div>
              </a>
            </center>
            <br>
          <? } ?>
          <? if ($newsLogo <> '' && $user <> '' && $companyName <> '') { ?>
            <p class="small" style="color:#189eb5;">Publisher: <?= $user ?> | <?= $companyName ?></p>
          <? } ?>
        </div>

        <? if ($userSidePanel == 1) { ?>
          <div style="width:300px;"><?= $userSidePanelContent ?></div>
        <? } ?>

      </div>

    </div>


    <!-- <div class="comments-section" style="padding:0 15px;">
      <h3>Comments<span style="font-size: 12px; font-style: italic; color: grey; margin-left: 10px;"><? //noOfComments($db, $id) 
                                                                                                      ?> Comments</span></h3>
      <div class="conversations">
        <div id="comments">
          <?php
          // display_comments($chat);
          ?>
        </div>
      </div> -->

    <div class="login-redirect">
      <span><a href="/signup.html?type=login&article_Id=<?= $id ?>">Login to Give your comment</a></span>
    </div>


    </div>

    <div class="container fixed-bottom pb-3" style="background-color:#fff">
      <div class="row no-gutters" style="border-top:1px solid #eb5e31">
        <div class="col mt-2 text-right">
          <small>Powered by</small> <a href="https://www.newsjunction.net/" target="_blank"><img src="/inc/img/logo.black.png" width="100" /></a>
        </div>
      </div>
    </div>

  </body>



  <script>
    function sendMessageAndRefresh(articleId, user_id) {
      // Place your sendMessage functionality here
      sendMessage(articleId, userName);

      // Refresh the page
      location.reload();
    }

    function sendMessage(article_id, user_name) {

      var messageText = $('#comment-text1').val().trim();
      var now = new Date(); // Get current date and time in user's local time zone

      var posted_on = now.getFullYear() + '-' +
        ('0' + (now.getMonth() + 1)).slice(-2) + '-' +
        ('0' + now.getDate()).slice(-2) + ' ' +
        ('0' + now.getHours()).slice(-2) + ':' +
        ('0' + now.getMinutes()).slice(-2) + ':' +
        ('0' + now.getSeconds()).slice(-2); // Format as 'YYYY-MM-DD HH:MM:SS'

      if (messageText === '') {
        return;
      }

      $.ajax({
        url: 'conversations/save_conversations.php',
        method: 'POST',
        data: {
          article_id: article_id,
          user_id: user_id,
          message: messageText,
          posted_on: posted_on
        },
        success: function(response) {
          // Handle the response
          alert('Message sent successfully!');
          console.log(response); // Log response to console
          location.reload(); // Reload the page after successful message post
        },
        error: function(xhr, status, error) {
          // Handle errors
          console.error(xhr.responseText);
        }
      });
    }



    function saveComments(comments) {
      localStorage.setItem('comments', JSON.stringify(comments));
    }

    function addComment() {
      const text = $('#comment-text').val();
      // const author = $('#comment-author').val();
      const author = '<?php echo $user_name; ?>';


      if (!text || !author) {
        alert('Please enter your name and comment.');
        return;
      }

      const comments = getComments();
      comments.push({
        text: text,
        author: author,
        date: new Date().toLocaleString()
      });
      saveComments(comments);

      $('#comment-text').val('');
      $('#comment-author').val('');
      renderComments();
    }

    function renderComments() {
      const comments = getComments();
      const commentsDiv = $('#comments');

      comments.forEach(comment => {
        const commentDiv = $('<div></div>').addClass('comment');
        commentDiv.html(`
            <div class="author">${comment.author} <span style="font-size: 0.8em; color: #555;">${comment.date}</span></div>
            <div class="text">${comment.text}</div>
            `);
        commentsDiv.append(commentDiv);
      });
    }

    $(document).ready(renderComments);
  </script>

  </html>
<? } ?>