<?
// Cream: Create

require_once '../inc/validate.logged.php';
require_once '../inc/config.php';

$act = '';
if (!empty($_POST)) $act = isset($_POST["act"]) ? $_POST["act"] : '';


// Create Post
if ($act == 'createPost') {
 $postTitle = isset($_POST['postTitle']) ? $_POST['postTitle'] : '';
 $postBody = isset($_POST['postBody']) ? $_POST['postBody'] : '';
 $isArchive = isset($_POST['isArchive']) ? trim($_POST['isArchive']) : '';
 $isReadMore = isset($_POST['isReadMore']) ? trim($_POST['isReadMore']) : '';
 $readMoreTxt = isset($_POST['readMoreTxt']) ? trim($_POST['readMoreTxt']) : '';
 $readMoreResponse = isset($_POST['readMoreResponse']) ? trim($_POST['readMoreResponse']) : '';
 $isMandatoryCompany = isset($_POST['isMandatoryCompany']) ? trim($_POST['isMandatoryCompany']) : '';
 $isMandatoryEmail = isset($_POST['isMandatoryEmail']) ? trim($_POST['isMandatoryEmail']) : '';
 $isMandatoryMobile = isset($_POST['isMandatoryMobile']) ? trim($_POST['isMandatoryMobile']) : '';
 if ($isArchive == 0) $isArchive = 'NULL';
 if ($isReadMore == 0) $isReadMore = 'NULL';
 if ($isMandatoryCompany == '' || $isMandatoryCompany == 'undefined') $isMandatoryCompany = 'NULL';
 if ($isMandatoryEmail == '' || $isMandatoryEmail == 'undefined') $isMandatoryEmail = 'NULL';
 if ($isMandatoryMobile == '' || $isMandatoryMobile == 'undefined') $isMandatoryMobile = 'NULL';
 if ($isReadMore == '') {
  $isMandatoryCompany = 'NULL';
  $isMandatoryEmail = 'NULL';
  $isMandatoryMobile = 'NULL';
 }
 if ($postTitle != '' && $postBody != '') {
  $postTitle = mysqli_real_escape_string($db, $postTitle);
  $postBody = mysqli_real_escape_string($db, $postBody);
  $sql = "INSERT INTO user_collection(user_id,title,description,is_archive,is_read_more,read_more_txt,read_more_response,is_mandatory_company,is_mandatory_email,is_mandatory_mobile,date_added) VALUES($gUserId,'$postTitle','$postBody',$isArchive,$isReadMore,'$readMoreTxt','$readMoreResponse',$isMandatoryCompany,$isMandatoryEmail,$isMandatoryMobile,Now())";
  mysqli_query($db, $sql);
  $postId = mysqli_insert_id($db);

  // For Business Gyan
  if ($gUserId == 287) {
   $pageViewStart = isset($_POST['pageViewStart']) ? $_POST['pageViewStart'] : '';
   $author = isset($_POST['author']) ? $_POST['author'] : '';
   if ($pageViewStart <> '') {
    $sql = "UPDATE user_collection SET page_view_start=$pageViewStart WHERE id=$postId AND user_id=$gUserId";
    mysqli_query($db, $sql);
   }
   $pageViewStart = isset($_POST['datePublished']) ? $_POST['datePublished'] : '';
   if ($datePublished <> '') {
    $sql = "UPDATE user_collection SET date_published=$datePublished WHERE id=$postId AND user_id=$gUserId";
    mysqli_query($db, $sql);
   }
   if ($author <> '') {
    $author = mysqli_real_escape_string($db, $author);
    $sql = "UPDATE user_collection SET author='$author' WHERE id=$postId AND user_id=$gUserId";
    mysqli_query($db, $sql);
   }
   $articleTag = isset($_POST['articleTag']) ? $_POST['articleTag'] : '';
   if ($articleTag <> '') {
    $arrArticleTags = explode(',', $articleTag);
    foreach ($arrArticleTags as $value) {
     $sql = "INSERT INTO user_collection_tag(articleId,articleTag) VALUES($postId,'$value')";
     mysqli_query($db, $sql);
    }
   }
  }

  if (isset($_FILES['uploadCover'])) {
   $temp = $_FILES['uploadCover'];
   if (is_uploaded_file($temp['tmp_name'])) {

    $fileExt = strtolower(pathinfo($temp['name'], PATHINFO_EXTENSION));
    $fileUpload = $postId . '-' . time() . '.' . $fileExt;
    move_uploaded_file($temp['tmp_name'], '../data/covers/' . $fileUpload);
    $sql = "UPDATE user_collection SET cover_img='$fileUpload' WHERE id=$postId AND user_id=$gUserId";
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
 <li class="breadcrumb-item"><h4 class="m-0">Create</h4></li>
</ol>
<div class="row mb-4">
 <div class="col">
  <ul class="nav nav-tabs mb-4">
   <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#rss" role="tab" onclick="$('#panelStatus').html('')">Create your Post</a></li>
   <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#newsletter" role="tab" onclick="$('#panelStatus').html('')">Add from My Collections</a></li>
  </ul>
  <div class="tab-content m-3">
   <div class="tab-pane fade show active" id="rss" role="tabpanel">
    <form id="frmPost">
    <div class="form-row">
     <div class="form-group col">
      <label for="postTitle">Title</label>
      <input type="text" class="form-control" id="postTitle" name="postTitle" maxlength="100" />
     </div>
    </div>
    <div class="form-row">
     <div class="form-group col">
      <textarea id="postBody" name="postBody"></textarea>
     </div>
    </div>
<?
 // For Business Gyan
 if ($gUserId == 287) {
?>
    <div class="form-row mb-3">
     <div class="form-group col-md-4">
      <label for="articleTag">Article Tags</label>
     </div>
     <div class="form-row col-md-8">

       <div class="col-12 col-md-4">
        <b>Marketing</b><br>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Branding & Advertising"><label class="pl-1 small text-secondary form-check-label">Branding & Advertising</label></div>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Customer"><label class="pl-1 small text-secondary form-check-label">Customer</label></div>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Competition"><label class="pl-1 small text-secondary form-check-label">Competition</label></div>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Networking"><label class="pl-1 small text-secondary form-check-label">Networking</label></div>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Business Networking"><label class="pl-1 small text-secondary form-check-label">Business Networking</label></div>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Sales"><label class="pl-1 small text-secondary form-check-label">Sales</label></div>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Social Media"><label class="pl-1 small text-secondary form-check-label">Social Media</label></div>
       </div>

       <div class="col-12 mt-4 col-md-4 mt-md-0">
        <b>Human Resource</b><br>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="HR Management"><label class="pl-1 small text-secondary form-check-label">HR Management</label></div>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Organization Structures"><label class="pl-1 small text-secondary form-check-label">Organization Structures</label></div>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Training"><label class="pl-1 small text-secondary form-check-label">Training</label></div>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Recruit"><label class="pl-1 small text-secondary form-check-label">Recruit</label></div>
       </div>

       <div class="col-12 mt-4 col-md-4 mt-md-0">
        <b>Technology</b><br>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Enterprise Software"><label class="pl-1 small text-secondary form-check-label">Enterprise Software</label></div>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Personal Software"><label class="pl-1 small text-secondary form-check-label">Personal Software</label></div>
       </div>

       <div class="col-12 col-md-4 mt-4">
        <b>Finance</b><br>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Funding"><label class="pl-1 small text-secondary form-check-label">Funding</label></div>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Accounting"><label class="pl-1 small text-secondary form-check-label">Accounting</label></div>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Taxation"><label class="pl-1 small text-secondary form-check-label">Taxation</label></div>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Treasury"><label class="pl-1 small text-secondary form-check-label">Treasury</label></div>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Management Information Systems"><label class="pl-1 small text-secondary form-check-label">Management Information Systems </label></div>
       </div>

       <div class="col-12 col-md-4 mt-4">
        <b>Operations</b><br>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Quality"><label class="pl-1 small text-secondary form-check-label">Quality</label></div>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Facility management"><label class="pl-1 small text-secondary form-check-label">Facility management</label></div>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Infrastructure"><label class="pl-1 small text-secondary form-check-label">Infrastructure</label></div>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Office Automation"><label class="pl-1 small text-secondary form-check-label">Office Automation</label></div>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Customer Service"><label class="pl-1 small text-secondary form-check-label">Customer Service</label></div>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Automation"><label class="pl-1 small text-secondary form-check-label">Automation</label></div>
       </div>

       <div class="col-12 col-md-4 mt-4">
        <b>Industry</b><br>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Ites & Bpo"><label class="pl-1 small text-secondary form-check-label">Ites & Bpo</label></div>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Hospitality"><label class="pl-1 small text-secondary form-check-label">Hospitality</label></div>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="IT"><label class="pl-1 small text-secondary form-check-label">IT</label></div>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Manufacturing"><label class="pl-1 small text-secondary form-check-label">Manufacturing</label></div>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Retail"><label class="pl-1 small text-secondary form-check-label">Retail</label></div>
       </div>

       <div class="col-12 col-md-4 mt-4">
        <b>Leadership</b><br>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Empowerment"><label class="pl-1 small text-secondary form-check-label">Empowerment</label></div>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Innovation"><label class="pl-1 small text-secondary form-check-label">Innovation</label></div>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Inspiration"><label class="pl-1 small text-secondary form-check-label">Inspiration</label></div>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Srategy"><label class="pl-1 small text-secondary form-check-label">Srategy</label></div>
       </div>

       <div class="col-12 col-md-4 mt-4">
        <b>Work Life</b><br>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Personality Development"><label class="pl-1 small text-secondary form-check-label">Personality Development</label></div>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Success"><label class="pl-1 small text-secondary form-check-label">Success</label></div>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Health"><label class="pl-1 small text-secondary form-check-label">Health</label></div>
        <div class="form-check"><input type="checkbox" class="form-check-input" id="articleTag" name="articleTag" value="Personal Finance"><label class="pl-1 small text-secondary form-check-label">Personal Finance</label></div>
       </div>

      </tr>
      </table>
     </div>
    </div>
    <div class="form-row">
     <div class="form-group col-md-4">
      <label for="author">Author(s)</label>
     </div>
     <div class="form-group col-md-8">
      <input type="text" class="form-control" id="author" name="author" maxlength="200" />
     </div>
    </div>
    <div class="form-row">
     <div class="form-group col-md-4">
      <label for="pageViewStart">Initial Page View</label>
     </div>
     <div class="form-group col-md-8">
      <input type="text" class="form-control" id="pageViewStart" name="pageViewStart" style="width:100px" onkeyPress="return numCheck(event)" maxlength="8" />
     </div>
    </div>
    <div class="form-row">
     <div class="form-group col-md-4">
      <label for="datePublished">Date Published</label>
     </div>
     <div class="form-group col-md-8">
      <input type="text" class="form-control" id="datePublished" name="datePublished" style="width:100px" onkeyPress="return numCheck(event)" maxlength="8" />
     </div>
    </div>
<? } ?>
    <div class="form-row">
     <div class="form-group col-md-4">
      <label for="uploadCover">Cover Image</label>
     </div>
     <div class="form-group col-md-8">
      <div class="form-check form-check-inline ml-0 ml-sm-2 w-50">
       <input type="file" class="form-control-file" id="uploadCover" name="uploadCover" accept="image/*" />
      </div>
     </div>
    </div>
    <div class="form-row">
     <div class="form-group col-md-4">
      <label for="isArchive">Archive Item</label>
     </div>
     <div class="form-group col-md-8">
      <div class="form-check-inline ml-0 ml-sm-2 w-25">
       <label class="form-control radio-inline border-0 pr-0"><input type="radio" id="isArchive" name="isArchive" value="1"> Yes</label>
       <label class="form-control radio-inline border-0 pr-0"><input type="radio" id="isArchive" name="isArchive" value="0" CHECKED> No</label>
      </div>
     </div>
    </div>
<?
 if ($gUserPlan == 0) {
  echo '<div class="alert alert-success" role="alert">Lead Capture feature is only available in <b>Pro</b> plan! Go to My Account to upgrade.</div>';
 } else {
?>
    <div class="form-row">
     <div class="form-group col-md-4">
      <label for="isReadMore">Show Call To Action Button</label>
     </div>
     <div class="form-group col-md-8">
      <div class="form-check-inline ml-0 ml-sm-2 w-25">
       <label class="form-control radio-inline border-0 pr-0"><input type="radio" id="isReadMore" name="isReadMore" value="1"> Yes</label>
       <label class="form-control radio-inline border-0 pr-0"><input type="radio" id="isReadMore" name="isReadMore" value="0" CHECKED> No</label>
      </div>
     </div>
    </div>
    <div class="form-row">
     <div class="form-group col-md-4">
      <label for="readMoreTxt">Call To Action Button Text</label>
     </div>
     <div class="form-group col-md-8">
      <input type="text" class="form-control" id="readMoreTxt" name="readMoreTxt" maxlength="50" />
     </div>
    </div>
    <div class="form-row">
     <div class="form-group col-md-4 pt-2">
      <label for="readMoreResponse">Call To Action Response</label>
     </div>
     <div class="form-group col-md-8">
      <input type="text" class="form-control" id="readMoreResponse" name="readMoreResponse" maxlength="300" />
      <small class="form-text text-muted">Enter a URL (including http or https) or if left blank, will show a default Thank you page</small>
     </div>
    </div>
    <div class="form-row">
     <div class="form-group col-md-4">
      <label for="isMandatoryCompany">Call To Action Mandatory Fields</label>
     </div>
     <div class="form-group col-md-8">
      <div class="row">
       <div class="col-12 col-lg-4"><label class="form-control border-0"><input type="checkbox" id="isMandatoryCompany" name="isMandatoryCompany" value="1"> Company/Institution</label></div>
       <div class="col-12 col-lg-4"><label class="form-control border-0"><input type="checkbox" id="isMandatoryEmail" name="isMandatoryEmail" value="1"> Email</label></div>
       <div class="col-12 col-lg-4"><label class="form-control border-0"><input type="checkbox" id="isMandatoryMobile" name="isMandatoryMobile" value="1"> Mobile</label></div>
      </div>
     </div>
    </div>
<? } ?>
    <input type="hidden" id="act" name="act" value="createPost" />
    <button class="btn btn-primary" onclick="return chkCreatePost()">Save Post</button>
    </form>
   </div>
   <div class="tab-pane fade" id="newsletter" role="tabpanel">
<?
 $numFeed = 1;
 $sql = "SELECT * FROM user_collection WHERE user_id=$gUserId AND is_archive IS NULL ORDER BY id DESC";
 $result = mysqli_query($db, $sql);
 $numRows = mysqli_num_rows($result);
 if ($numRows > 0) {
?>
    <div class="table-responsive">
     <table class="table table-striped">
     <thead>
     <tr>
      <th scope="col">#</th>
      <th scope="col">Post</th>
      <th scope="col">Publisher</th>
      <th scope="col"></th>
     </tr>
     </thead>
     <tbody>
<?
  while($row = mysqli_fetch_assoc($result)) {
   $collectionId = $row['id'];
   $collectionTitle = $row['title'];
   $collectionURL = $row['url'];
   $collectionDesc = $row['description'];
   $collectionDesc = str_replace(array("\r", "\n"), '', $collectionDesc);
   $collectionPublisher = substr($collectionURL, strpos($collectionURL, ".") + 1);
   $collectionPublisher = ucfirst(strtok($collectionPublisher, '.'));
   if ($collectionPublisher == '') $collectionPublisher = 'Cream';
   $collectionLink = '/view/' . $collectionId . '/' . createArticleURL($collectionTitle);
   if ($gUserSubdomain <> '') {
    $collectionLinkFull = 'https://newsjunction.net' . $collectionLink;
   } else {
    $collectionLinkFull = 'https://www.newsjunction.net' . $collectionLink;
   }
   $copyClipboard = htmlspecialchars(str_replace("'", "\'", $collectionTitle)) . '\n' . htmlspecialchars(str_replace("'", "\'", $collectionDesc)) . '\n' . htmlspecialchars($collectionLinkFull);
?>
     <tr>
      <th scope="row"><?=$numFeed?>.</th>
      <td><a href="javascript:np()" onclick="openWin('<?=$collectionLink?>')"><?=$collectionTitle?></a></td>
      <td><?=$collectionPublisher?></td>
      <td align="right"><a href="javascript:np()" onclick="copyToClipboard('<?=$copyClipboard?>')" title="Copy to Clipboard"><i class="far fa-clipboard fa-lg text-muted pr-2"></i></a></td>
     </tr>
<?
   $numFeed += 1;
  }
?>
     </tbody>
     </table>
<?
 } else {
?>
   You do not have any items in your collection!
<?
 }
?>
   </div>
  </div>
 </div>
</div>
<script>
tinymce.init({
 init_instance_callback: 'insert_contents',
 selector: 'textarea',
 height: 400,
 statusbar: false,
 forced_root_block: '',
 force_br_newlines: true,
 force_p_newlines: false,
 file_picker_types: 'image',
 automatic_uploads: true,
 paste_data_images: true,
 invalid_elements: 'span',
 extended_valid_elements: 'script[src|async|defer|type|charset]',
 images_upload_url: 'process/upload.php',
 file_picker_callback:
  function(callback, value, meta) {
   var input = document.createElement('input');
   input.setAttribute('type', 'file');
   input.setAttribute('accept', 'image/*');
   input.onchange = function () {
    var file = this.files[0];
    var reader = new FileReader();
    reader.onload = function () {
    var id = 'blob' + (new Date()).getTime();
    var blobCache =  tinymce.activeEditor.editorUpload.blobCache;
    var base64 = reader.result.split(',')[1];
    var blobInfo = blobCache.create(id, file, base64);
    blobCache.add(blobInfo);
    callback(blobInfo.blobUri(), { title: file.name });
   };
   reader.readAsDataURL(file);
  };
  input.click();
 },
 menubar: 'edit format',
 toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright alignjustify | outdent indent | numlist bullist | table | forecolor backcolor removeformat | code fullscreen preview | hr charmap image media link',
 plugins: 'image imagetools code preview table charmap paste media autolink link hr fullscreen lists',
 contextmenu: 'link image imagetools table spellchecker lists'
});

function insert_contents(inst) {
 inst.setContent('');
}
</script>
<?
}
?>