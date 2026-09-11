<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    
</body>
</html>


<?
// Cream: Request Article

require_once '../inc/validate.logged.php';
require_once '../inc/config.php';

$act = '';
if (!empty($_POST)) $act = isset($_POST["act"]) ? $_POST["act"] : '';


// Create Post
if ($act == 'sendRequest') {
 $articleHeadline = isset($_POST['articleHeadline']) ? $_POST['articleHeadline'] : '';
 $articleObjective = isset($_POST['articleObjective']) ? $_POST['articleObjective'] : '';
 $articleTargetGroup = isset($_POST['articleTargetGroup']) ? $_POST['articleTargetGroup'] : '';
 $articleKeywords = isset($_POST['articleKeywords']) ? $_POST['articleKeywords'] : '';
 $articleNumWords = isset($_POST['articleNumWords']) ? $_POST['articleNumWords'] : '';
 $articleNumImages = isset($_POST['articleNumImages']) ? $_POST['articleNumImages'] : '';
 $articleOutline = isset($_POST['articleOutline']) ? $_POST['articleOutline'] : '';
 $articlePrimarySource = isset($_POST['articlePrimarySource']) ? $_POST['articlePrimarySource'] : '';
 $articleSecondarySource = isset($_POST['articleSecondarySource']) ? $_POST['articleSecondarySource'] : '';
 if ($articleHeadline != '' && $articleObjective != '') {
  $tmpHTML = "";
  $tmpHTML .= "<html>";
  $tmpHTML .= "<body>";
  $tmpHTML .= "<div style=\"font-family:Arial;font-size:12px;\">\r\n";
  $tmpHTML .= "The following has been requested from News Junction:<br><br>\r\n";
  $tmpHTML .= "<b>Request by:</b><br>\r\n";
  $tmpHTML .= "$gUserName [$gUserEmail]<br><br>\r\n";
  $tmpHTML .= "<b>Headline:</b><br>\r\n";
  $tmpHTML .= "$articleHeadline<br><br>\r\n";
  $tmpHTML .= "<b>Objective:</b><br>\r\n";
  $tmpHTML .= "$articleObjective <br><br>\r\n";
  $tmpHTML .= "<b>Target Group:</b><br>\r\n";
  $tmpHTML .= "$articleTargetGroup <br><br>\r\n";
  $tmpHTML .= "<b>Keywords:</b><br>\r\n";
  $tmpHTML .= "$articleKeywords<br><br>\r\n";
  $tmpHTML .= "<b>Number of words:</b><br>\r\n";
  $tmpHTML .= "$articleNumWords<br><br>\r\n";
  $tmpHTML .= "<b>Number of Pictures/Graphics/etc.:</b><br>\r\n";
  $tmpHTML .= "$articleNumImages<br><br>\r\n";
  $tmpHTML .= "<b>Outline:</b><br>\r\n";
  $tmpHTML .= "$articleOutline<br><br>\r\n";
  $tmpHTML .= "<b>Primary Source:</b><br>\r\n";
  $tmpHTML .= "$articlePrimarySource<br><br>\r\n";
  $tmpHTML .= "<b>Secondary Source:</b><br>\r\n";
  $tmpHTML .= "$articleSecondarySource<br><br>\r\n";
  $tmpHTML .= "Warm Regards,<br>\r\n";
  $tmpHTML .= "News Junction<br>\r\n";
  $tmpHTML .= "</body>";
  $tmpHTML .= "</html>";
  sendEmail('Site Administrator', 'YOUR_ADMIN_EMAIL', '', 'News Junction: Request Article', $tmpHTML);
  echo 'Thank you for your submission!<br>We will get back to you at the earliest.';
 }
}


// Default
if ($act == '') {
?>
<ol class="breadcrumb my-3">
 <li class="breadcrumb-item"><h4 class="m-0">Request Article</h4></li>
</ol>
<div id="panelRequestArticleHeader" class="row mb-4 p-2">
 <div class="col">
  Fill up the form to help our writers understand your requirement. Charges are applicable.<br>
  Fields marked with <span class="txtRed">*</span> are mandatory.<br>
 </div>
</div>
<div class="row mb-4 p-2">
 <div id="panelRequestArticle" class="col">
  <form id="frmArticle">
   <div class="form-row">
    <div class="form-group col-12 col-md-6">
     <label for="postTitle">Headline<sup class="txtRed"><big>*</big></sup></label>
     <input type="text" class="form-control px-2 py-4" id="articleHeadline" name="articleHeadline" maxlength="100" />
    </div>
    <div class="form-group col-12 col-md-6">
     <label for="postTitle">Objective<sup class="txtRed"><big>*</big></sup></label>
     <input type="text" class="form-control px-2 py-4" id="articleObjective" name="articleObjective" maxlength="100" />
    </div>
   </div>
   <div class="form-row">
    <div class="form-group col-12 col-md-6">
     <label for="postTitle">Target Group<sup class="txtRed"><big>*</big></sup></label>
     <input type="text" class="form-control px-2 py-4" id="articleTargetGroup" name="articleTargetGroup" maxlength="100" />
    </div>
    <div class="form-group col-12 col-md-6">
     <label for="postTitle">Keywords<sup class="txtRed"><big>*</big></sup></label>
     <input type="text" class="form-control px-2 py-4" id="articleKeywords" name="articleKeywords" maxlength="100" />
    </div>
   </div>
   <div class="form-row">
    <div class="form-group col-12 col-md-6">
     <label for="postTitle">Number of words<sup class="txtRed"><big>*</big></sup></label>
     <input type="text" class="form-control px-2 py-4" id="articleNumWords" name="articleNumWords" maxlength="100" />
    </div>
    <div class="form-group col-12 col-md-6">
     <label for="postTitle">Number of Pictures/Graphics/etc.<sup class="txtRed"><big>*</big></sup></label>
     <input type="text" class="form-control px-2 py-4" id="articleNumImages" name="articleNumImages" maxlength="100" />
    </div>
   </div>
   <div class="form-row">
    <div class="form-group col">
     <label for="postTitle">Outline</label>
     <textarea class="form-control" id="articleOutline" name="articleOutline"></textarea>
    </div>
   </div>
   <div class="form-row">
    <div class="form-group col-12 col-md-6">
     <label for="postTitle">Primary source</label>
     <textarea class="form-control" id="articlePrimarySource" name="articlePrimarySource"></textarea>
    </div>
    <div class="form-group col-12 col-md-6">
     <label for="postTitle">Secondary source</label>
     <textarea class="form-control" id="articleSecondarySource" name="articleSecondarySource"></textarea>
    </div>
   </div>
   <div class="mt-3">
    <div class="float-left"><button type="button" class="btn btn-primary" onclick="chkRequestArticle()">Send Request</button></div>
    <div class="float-left ml-4 pt-2"><div id="panelStatusRequestArticle"></div></div>
   </div>
   <input type="hidden" id="act" name="act" value="sendRequest" />
  </form>
 </div>
</div>
<?
}
?>