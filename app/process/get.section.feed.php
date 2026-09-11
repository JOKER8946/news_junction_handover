<?
// Cream: My Feeds

require_once '../inc/validate.logged.php';
require_once '../inc/config.php';
require_once '../inc/simplepie/autoloader.php';

$act = '';
if (!empty($_POST)) $act = isset($_POST["act"]) ? $_POST["act"] : '';


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


// Default
if ($act == '') {
?>
<ol class="breadcrumb my-3">
 <li class="breadcrumb-item w-100">
  <div class="text-left w-50"><h4 class="mt-1">My Feeds</h4></div>
  <div class="text-right w-50"><button type="button" id="buttonAddCollection" class="hide btn btn-success" onclick="chkAddCollection()">Add selected to My Collection</button></div>
 </li>
</ol>
<div class="panelFeeds pr-2">
<?
 $sql = "SELECT * FROM user_feeds WHERE user_id=$gUserId";
 $result = mysqli_query($db, $sql);
 $numRows = mysqli_num_rows($result);
 if ($numRows > 0) {
  while($row = mysqli_fetch_assoc($result)) {
   $array[] = $row['feed_url'];
  }
  $feed = new SimplePie();
/*
  $feed->set_curl_options(
   array(
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_SSL_VERIFYPEER => false
   )
  );
*/
  $feed->set_feed_url($array);
  $feed->set_cache_location('../inc/simplepie/library/SimplePie/Cache');
  $feed->init();
  $feed->handle_content_type();
  foreach ($feed->get_items() as $item) {
   $isExist = 0;
   $feedTitle= $item->get_title();
   $feedURL = $item->get_permalink();
   $feedPublisher = substr($feedURL, strpos($feedURL, ".") + 1);
   $feedPublisher = ucfirst(strtok($feedPublisher, '.'));
   $feedDesc = $item->get_description();
   $feedDate = $item->get_date('j F Y | g:i a');
   $sql = "SELECT url FROM user_collection WHERE url='$feedURL' AND user_id=$gUserId";
   $resultInner = mysqli_query($db, $sql);
   $numRowsInner = mysqli_num_rows($resultInner);
   if ($numRowsInner > 0) $isExist = 1;
?>
 <div class="card p-0 mb-3 border-0" style="background-color:#f5ede7">
  <div class="panelFeed card-body">
   <h4><a id="<?=$feedTitle?>" href="#" onclick="window.open('<?=$feedURL?>','_blank','location=yes')" style="color:#f26522"><?=$feedTitle?><br><span style="position: absolute; top: -9999px; left: -9999px" ><?=$feedURL?></span></a></h4>
   <p style="color:#7d7d7d"><?=$feedDesc?></p>
   <div class="row">
    <div class="col-12 col-md-6 text-center text-md-left"><?=$feedPublisher?> &bull; <?=$feedDate?></div>
    <div class="data col-12 col-md-6 text-center text-md-left mt-2 m-md-0" data-feed-title="<?=$feedTitle?>" data-feed-url="<?=$feedURL?>" data-feed-publisher="<?=$feedPublisher?>" data-feed-desc="<?=htmlentities($feedDesc)?>">
<? if ($isExist == 0) { ?>
     <label class="btn-secondary m-0 px-2 py-1" style="user-select:none"><input type="checkbox" name="feedData" onclick="addCollection()"> My Collection</label>
<? } else { ?>
     <label class="badge-warning m-0 px-3 py-1" style="user-select:none">My Collection</label>
<? } ?>
     <label class="buttonCreamShare btn-info m-0 px-2 py-1" style="user-select:none">Cream<i>Share</i></label>
     <!-- <div class="buttonsSocial col-12 d-block d-md-inline m-0"> -->
     <!-- <a data-sbg-1="" data-sbg-2="" data-sbg-3="" class="sbg-button" data-sbg-network="facebook" data-sbg-width="600" data-sbg-height="368"><i class="fab fa-facebook-square fa-2x cursorH" style="position:relative;top:5px;color:#f58020;"></i></a>
     <a data-sbg-1="" data-sbg-2="" data-sbg-3="" class="sbg-button" data-sbg-network="twitter" data-sbg-width="600" data-sbg-height="258"><i class="fab fa-twitter-square fa-2x cursorH" style="position:relative;top:5px;color:#f58020;"></i></a>
    <a data-sbg-1="" data-sbg-2="" data-sbg-3="" class="sbg-button" data-sbg-network="linkedin" data-sbg-width="585" data-sbg-height="471"><i class="fab fa-linkedin fa-2x cursorH" style="position:relative;top:5px;color:#f58020;"></i></a>
    <a data-sbg-1="" data-sbg-2="" data-sbg-3="" class="sbg-button" data-sbg-network="pinterest" data-sbg-width="600" data-sbg-height="368"><i class="fab fa-pinterest-square fa-2x cursorH" style="position:relative;top:5px;color:#f58020;"></i></a>
    <a href="#popup-info?" class="open-popup"><i class="fab fa-2x fa-whatsapp-square" style="position:relative;top:5px;color:#f58020;"></i></a> -->

<label class="btn-success m-0 px-2 py-1" id="button1" onclick="CopyToClipboard('<?=$feedTitle?>')">Share</label>

<br /><br />
<!-- <div id="<?=$feedTitle?>"><?=$feedTitle?><br><?=$feedURL?></div> -->

<!-- </div> -->
     <!-- /div-->
    </div>
   </div>
  </div>
 </div>
<?
  }
 } else {
?>
 You have not added feeds!<br>
<?
}
?>
</div>
<script type="text/javascript">
$(function() {
 generateSM();
 $('.buttonCreamShare').magnificPopup({
  type: 'ajax',
  closeBtnInside: true,
  ajax: {
   settings: {
    method: 'POST',
    url: 'process/get.section.general.php'
   }
  },
  callbacks: {
   elementParse: function(item){
    var data = $(item.el[0]).closest('.data');
    postData = {
     act: 'showCreamShare',
     title: data.data('feed-title'),
     url: data.data('feed-url'),
     desc: data.data('feed-desc')
    }
    this.st.ajax.settings.data = postData;
   }
  }
 });
});
</script>
<script type="text/javascript">
  function CopyToClipboard(containerid) {
    if (window.getSelection) {
        if (window.getSelection().empty) { // Chrome
            window.getSelection().empty();
        } else if (window.getSelection().removeAllRanges) { // Firefox
            window.getSelection().removeAllRanges();
        }
    } else if (document.selection) { // IE?
        document.selection.empty();
    }

    if (document.selection) {
        var range = document.body.createTextRange();
        range.moveToElementText(document.getElementById(containerid));
        range.select().createTextRange();
        document.execCommand("copy");
        alert("Copied to clipboard!");
    } else if (window.getSelection) {
        var range = document.createRange();
        range.selectNode(document.getElementById(containerid));
        window.getSelection().addRange(range);
        document.execCommand("copy");
        alert("Copied to clipboard!");
    }
}

    </script>
<?
}
?>
