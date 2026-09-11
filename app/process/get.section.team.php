<?php
// Cream: Community Feeds

require_once '../inc/validate.logged.php';
require_once '../inc/config.php';
$act = '';
if (!empty($_POST)) $act = isset($_POST["act"]) ? $_POST["act"] : '';

// Function to truncate the description to a certain number of words
function truncateDescription($description, $limit = 25) {
    $words = explode(' ', $description);
    if (count($words) > $limit) {
        return implode(' ', array_slice($words, 0, $limit)) . '...';
    } else {
        return $description;
    }
}


// Add to Collection
if ($act == 'addCollection') {
 $data = isset($_POST['data']) ? $_POST['data'] : '';
 if ($data != '') {
  $arrData = json_decode($data, true);
  foreach ($arrData as &$value) {
   $feedTitle = $value['title'];
   $feedURL = $value['url'];
   $feedDesc = $value['desc'];
   $sql = "SELECT url FROM user_collection WHERE url='$feedURL' AND user_id=$gUserId";
   $result = mysqli_query($db, $sql);
   $numRows = mysqli_num_rows($result);
   if ($numRows == 0) {
    $feedTitle = mysqli_real_escape_string($db, $feedTitle);
    $feedURL = mysqli_real_escape_string($db, $feedURL);
    $feedDesc = mysqli_real_escape_string($db, $feedDesc);
    $sql = "INSERT INTO user_collection(user_id,title,url,description,date_added) VALUES($gUserId,'$feedTitle','$feedURL','$feedDesc',Now())";
    mysqli_query($db, $sql);
   }
  }
  echo "OK";
 }
}

?>
<ol class="breadcrumb my-3">
 <li class="breadcrumb-item w-100">
  <div class="text-left w-50"><h4 class="mt-1">Team Feeds</h4></div>
  <div class="text-right w-50"><button type="button" id="buttonAddCollection" class="hide btn btn-success" onclick="chkAddCollection()">Add selected to My Collection</button></div>
 </li>
</ol>

<?php
// Prepare the SQL statement
$sql = "SELECT email FROM user WHERE id = $gUserId";
$result = mysqli_query($db, $sql);
if ($result) {
    $numRows = mysqli_num_rows($result);

    if ($numRows > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
        	$gUserEmail = $row['email'];
        	}
    }
}

$gUserDomain = substr(strrchr($gUserEmail, "@"), 1);
if ($gUserDomain=='gmail.com' OR $gUserDomain=='yahoo.com' OR $gUserDomain=='zohomail.com' OR $gUserDomain=='protonmail.com' OR $gUserDomain=='outlook.com' OR $gUserDomain=='hotmail.com' OR $gUserDomain=='mail.com' OR $gUserDomain=='rediff.com' ){
	$gUserDomain = '';	
}
//echo $gUserDomain;

$sql = "
SELECT
    uc.id AS article_id,
    uc.title,
    uc.description,
    uc.likes,
    uc.date_added,
    uc.is_archive,
    u.full_name AS user_name,
    u.email
FROM
    user_collection uc
INNER JOIN
    user u ON uc.user_id = u.id
WHERE
    SUBSTRING(u.email, LOCATE('@', u.email) + 1) =  '$gUserDomain'
ORDER BY
    uc.date_added DESC;
";

$result = mysqli_query($db, $sql);

if ($result) {
    $numRows = mysqli_num_rows($result);

    if ($numRows > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
        	//$emailDisplay = $row['email'];
        	//echo $emailDisplay;
        	$articleId = $row['article_id'];
            $title = $row['title'];
            $description = truncateDescription($row['description'], 25); // Truncate description to 25 words
            $feedPublisher = $row['user_name']; // Updated to fetch the user's full name
            $feedDate = $row['date_added']; // Updated to fetch the date added
            $isExist = 0;
            ?>
            <div class="card p-0 mb-3 border-0" style="background-color:#f5ede7">
                <div class="panelFeed card-body">
                    <!-- h4><a id="<?= htmlentities($feedTitle) ?>" href="#" onclick="window.open('#','_blank','location=yes')" style="color:#f26522"><?= htmlentities($feedTitle) ?><br><span style="position: absolute; top: -9999px; left: -9999px"></span></a></h4-->
                    <h4><a href="article.php?article_id=<?= $articleId ?>" style="color:#f26522"><?= htmlentities($title) ?></a></h4>
                    <p style="color:#7d7d7d"><?= $description ?></p>
                    <div class="row">
                        <div class="col-12 col-md-6 text-center text-md-left"><?= htmlentities($feedPublisher) ?> &bull; <?= htmlentities($feedDate) ?></div>
                        <div class="data col-12 col-md-6 text-center text-md-left mt-2 m-md-0" data-feed-title="<?= htmlentities($feedTitle) ?>" data-feed-url="#" data-feed-publisher="<?= htmlentities($feedPublisher) ?>" data-feed-desc="<?= htmlentities($feedDesc) ?>">
                            <?php if ($isExist == 0) { ?>
                                <label class="btn-secondary m-0 px-2 py-1" style="user-select:none"><input type="checkbox" name="feedData" onclick="addCollection()"> My Collection</label>
                            <?php } else { ?>
                                <label class="float-right badge-warning m-0 px-3 py-1" style="user-select:none">My Collection</label>
                            <?php } ?>
                            <!-- 
<label class="buttonCreamShare btn-info m-0 px-2 py-1" style="user-select:none">Cream<i>Share</i></label>
                            <label class="btn-success m-0 px-2 py-1" id="button1" onclick="CopyToClipboard('<?= htmlentities($feedTitle) ?>')">Share</label>
                            <button id="incrementButton" data-id="<?= $row['article_id'] ?>">Like Article</button>
                            <div id="likesDisplay"></div>
 -->
                            <br /><br />
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
    } else {
        echo "Nothing found here!. You should use your company email Id to be part of a team.";
    }
} else {
    echo "Error in query execution: " . mysqli_error($db);
}
?>


<!-- JavaScript to make AJAX request -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
  $(document).ready(function() {
  // add click event listener to the button
  $(document).on('click', '.incrementButton', function()  {
    // get the id from the data attribute
    var id = $(this).data('id');
    
    // make an AJAX request to the server-side script
    $.ajax({
      type: 'POST',
      url: 'inc/increment.php',
      data: {id: id},
      success: function(likes) {
        // update the likes display on the page
        $('#likesDisplay').text('Likes: ' + likes);
      },
      error: function() {
        alert('Error: unable to increment likes.');
      }
    });
  });
});
</script>

</script>
<script type="text/javascript">
function CopyToClipboard(text) {
  var $temp = $("<textarea>");
  $("body").append($temp);
  $temp.val(text).select();
  document.execCommand("copy");
  $temp.remove();
  alert("Copied to clipboard!");
}
    </script>