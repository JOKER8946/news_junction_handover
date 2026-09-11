<?
// Cream: Utils

require_once '../inc/php/validate.logged.php';
require_once '../inc/config.php';

$act = '';
if (!empty($_POST)) $act = isset($_POST["act"]) ? $_POST["act"] : '';


// Show Notifications
if ($act == 'showNotifications') {
?>
  <ol class="breadcrumb my-3">
    <li class="breadcrumb-item w-100">
      <div class="text-left w-50">
        <h4 class="mt-1">Notification Center</h4>
      </div>
    </li>
  </ol>
  <div class="row mb-4 p-2">
    <div class="col">
      You have no notifications!
    </div>
  </div>
<?
}

// Cream Shared
if ($act == 'creamShared') {
  $shareId = isset($_POST['shareId']) ? $_POST['shareId'] : '';
  $feedId = isset($_POST['feedId']) ? $_POST['feedId'] : '';
  $feedTitle = isset($_POST['feedTitle']) ? $_POST['feedTitle'] : '';
  $feedURL = isset($_POST['feedURL']) ? $_POST['feedURL'] : '';
  $feedDesc = isset($_POST['feedDesc']) ? $_POST['feedDesc'] : '';
  if ($shareId != '') {
    if ($feedId != '') {
      $sql = "SELECT title,url,description,cover_img FROM user_collection WHERE id=$feedId AND user_id=$gUserId";
      $result = mysqli_query($db, $sql);
      $numRows = mysqli_num_rows($result);
      if ($numRows > 0) {
        $row = mysqli_fetch_assoc($result);
        $feedTitle = $row['title'];
        $feedURL = $row['url'];
        $feedDesc = $row['description'];
        $coverImg = $row['cover_img'];
        if (!empty($feedTitle)) {
          $feedTitle = $db->real_escape_string($feedTitle);
        }

        if (!empty($feedURL)) {
          $feedURL = $db->real_escape_string($feedURL);
        }

        if (!empty($feedDesc)) {
          $feedDesc = $db->real_escape_string($feedDesc);
        }
        $sql = "INSERT INTO user_collection(user_id,share_user_id,share_collection_id,title,url,description,cover_img,date_added) VALUES($shareId,$gUserId,$feedId,'$feedTitle','$feedURL','$feedDesc','$coverImg',Now())";
        mysqli_query($db, $sql);
      }
    } else if ($feedTitle != '') {
      $sql = "SELECT url FROM user_collection WHERE url='$feedURL' AND share_user_id=$gUserId AND user_id=$shareId";
      $result = mysqli_query($db, $sql);
      $numRows = mysqli_num_rows($result);
      if ($numRows == 0) {
        if (!empty($feedTitle)) {
          $feedTitle = $db->real_escape_string($feedTitle);
        }
        if (!empty($feedURL)) {
          $feedURL = $db->real_escape_string($feedURL);
        }
        $feedDesc = urldecode($feedDesc);
        if (!empty($feedDesc)) {
          $feedDesc = $db->real_escape_string($feedDesc);
        }
        $sql = "INSERT INTO user_collection(user_id,share_user_id,title,url,description,date_added) VALUES($shareId,$gUserId,'$feedTitle','$feedURL','$feedDesc',Now())";
        mysqli_query($db, $sql);
      }
    }
    echo "OK";
  }
}

// Show Cream Sharing
if ($act == 'showCreamShare') {
  $feedId = isset($_POST['id']) ? $_POST['id'] : '';
  $feedTitle = isset($_POST['title']) ? $_POST['title'] : '';
  $feedURL = isset($_POST['url']) ? $_POST['url'] : '';
  $feedDesc = isset($_POST['desc']) ? $_POST['desc'] : '';
?>
  <div class="popup">
    <div class="widget">
      <form id="frmAdd" name="frmAdd">
        <div class="card">
          <div class="card-header bg-dark">
            <h5 class="mb-0 text-light">Cream<i>Share</i></h5>
          </div>
        </div>
        <div id="widget_B" style="padding:15px 25px">
          <?
          if ($gUserPlan == 0) {
            echo '<div class="alert alert-success" role="alert">This feature is only available in <b>Pro</b> plan!<br>Go to My Account to upgrade.</div>';
          } else {
          ?>
            <div class="form-group">
              <label for="feedURL">Cream User</label>
              <input type="email" class="form-control" id="shareEmail" name="shareEmail" maxlength="100" />
              <small class="form-text text-muted">Please enter the Cream login to share with</small>
            </div>
        </div>
        <div id="widget_F" style="border-top:1px solid #ebedf2;padding:20px 10px;">
          <div class="col">
            <input type="hidden" id="feedId" name="feedId" value="<?= $feedId ?>" />
            <input type="hidden" id="feedTitle" name="feedTitle" value="<?= addslashes($feedTitle) ?>" />
            <input type="hidden" id="feedURL" name="feedURL" value="<?= $feedURL ?>" />
            <input type="hidden" id="feedDesc" name="feedDesc" value="<?= urlencode($feedDesc) ?>" />
            <button type="submit" class="btn btn-primary" onclick="return chkCreamShare()">Share</button>
            <div id="panelStatus" class="float-right text-sm" style="margin-top:5px" align="right"></div>
          </div>
        </div>
      <? } ?>
      </form>
    </div>
  </div>
<?
}
