<!doctype html>
<?
include 'inc/config.php';

$id = isset($_GET["id"]) ? $_GET["id"] : '';
if ($id <> '') {
   $sql = "SELECT * FROM user_collection WHERE id=$id";
   $result = mysqli_query($db, $sql);
   $row = $result->fetch_assoc();
   $numRows = mysqli_num_rows($result);
   if ($numRows == 0) die();

   $serverName = $_SERVER['SERVER_NAME'];
   $collectionURL = $row['url'];
   $collectionUserId = $row['user_id'];
   $collectionShareUserId = $row['share_user_id'];
   $collectionTitle = $row['title'];
   $collectionDesc = $row['description'];
   $isMandatoryCompany = $row['is_mandatory_company'];
   $isMandatoryEmail = $row['is_mandatory_email'];
   $isMandatoryMobile = $row['is_mandatory_mobile'];
   $collectionPublisher = substr($collectionURL, strpos($collectionURL, ".") + 1);
   $collectionPublisher = ucfirst(strtok($collectionPublisher, '.'));
   if ($collectionPublisher == '') $collectionPublisher = 'Cream';
   $collectionDate = date('d M, Y', strtotime($row['date_added']));
   $readMoreResponse = $row['read_more_response'];

   if ($collectionShareUserId <> '') {
      $sql = "SELECT full_name,company,website,news_title,news_logo FROM user WHERE id=$collectionShareUserId";
   } else {
      $sql = "SELECT full_name,company,website,news_title,news_logo FROM user WHERE id=$collectionUserId";
   }
   $result = mysqli_query($db, $sql);
   $row = mysqli_fetch_assoc($result);
   $user = $row['full_name'];
   $companyName = $row['company'];
   $website = $row['website'];
   $newsTitle = $row['news_title'];
   $newsLogo = $row['news_logo'];
?>
   <html lang="en">

   <head>
      <title><?= $collectionTitle ?></title>
      <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
      <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous" />
      <link rel="stylesheet" href="inc/fontawesome/css/all.min.css" />
      <link rel="stylesheet" href="inc/style.css" />
      <script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
      <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
   </head>

   <body>
      <form id="frmLead">
         <div class="container mb-3 pb-3" style="position:relative">

            <header class="blog-header py-3 border-bottom">
               <div class="row flex-nowrap justify-content-between align-items-center">
                  <div class="col-8 pt-1">
                     <?if(isset($newsLogo)){?>
                        <a class="text-muted" href="<?= $website ?>"><img class="mx-auto" width="256px" src="https://<?= $serverName ?>/data/logos/<?= $newsLogo ?>" /></a>
                     <?}?>
                     <h6 class="mt-1" style="color:#9e9e9e"><?= $user ?> | <?= $companyName ?></h6>
                  </div>
                  <div class="col-4 text-right">
                  </div>
               </div>
            </header>

            <div class="row mt-3 mb-5">
               <div class="panelContent col-12 col-md pb-3">
                  <h4><?= $collectionTitle ?></h4>
                  <div class="pt-2 pb-5">
                     You have requested for more information.<br>
                     Please fill the below form and we will get back to you.<br>
                  </div>
                  <div class="form-group row">
                     <label for="leadName" class="col-sm-3 col-form-label">Full Name<sup class="txtRed"><big>*</big></sup></label>
                     <div class="col-sm-7">
                        <input type="text" class="form-control" id="leadName" name="leadName" maxlength="100" />
                     </div>
                  </div>
                  <div class="form-group row">
                     <label for="leadCompany" class="col-sm-3 col-form-label">Company/Institution<? if ($isMandatoryCompany) { ?><sup class="txtRed"><big>*</big></sup><? } ?></label>
                     <div class="col-sm-7">
                        <input type="text" class="form-control" id="leadCompany" name="leadCompany" maxlength="100" />
                     </div>
                  </div>
                  <div class="form-group row">
                     <label for="leadEmail" class="col-sm-3 col-form-label">Email<? if ($isMandatoryEmail) { ?><sup class="txtRed"><big>*</big></sup><? } ?></label>
                     <div class="col-sm-7">
                        <input type="text" class="form-control" id="leadEmail" name="leadEmail" maxlength="100" />
                     </div>
                  </div>
                  <div class="form-group row">
                     <label for="leadMobile" class="col-sm-3 col-form-label">Mobile<? if ($isMandatoryMobile) { ?><sup class="txtRed"><big>*</big></sup><? } ?></label>
                     <div class="col-sm-7">
                        <input type="text" class="form-control" id="leadMobile" name="leadMobile" maxlength="50" />
                     </div>
                  </div>
                  <center>
                     <section>
                        <div id="contentLoader" class="mt-0 hide">
                           <div class="lds-ring">
                              <div></div>
                              <div></div>
                              <div></div>
                              <div></div>
                           </div>
                        </div>
                     </section>
                     <div id="panelStatus" class="py-3"></div>
                     <input type="hidden" id="leadCollectionId" name="leadCollectionId" value="<?= $id ?>" />
                     <input type="hidden" id="act" name="act" value="createLead" />
                     <button type="button" id="btnSubmit" class="btn btn-primary px-3 py-2" onclick="chkLead()">Submit</button>
                  </center>
               </div>
               <div style="width:300px;">
               </div>
            </div>
         </div>

         <div class="container fixed-bottom pb-3" style="background-color:#fff">
            <div class="row mt-4 no-gutters" style="border-top:1px solid #eb5e31">
               <div class="col mt-2 text-right">
                  <small>Powered by</small> <a href="https://newsjunction.net/" target="_blank"><img src="/inc/img/logo.black.png" width="100"></a>
               </div>
            </div>
         </div>

         <script type="text/javascript">
            var chkFilterEmail = /\w{1,}[@][\w\-]{1,}([.]([\w\-]{1,})){1,3}$/;

            function chkLead() {
               var leadName = $('#leadName').val();
               var leadCompany = $('#leadCompany').val();
               var leadEmail = $('#leadEmail').val();
               var leadMobile = $('#leadMobile').val();
               if (leadName == '') {
                  $('#panelStatus').html('<div class="text-danger animate__animated animate__flash">Error: Full Name not entered!</div>');
                  return false;
               }
               <? if ($isMandatoryCompany) { ?>
                  if (leadCompany == '') {
                     $('#panelStatus').html('<div class="text-danger animate__animated animate__flash">Error: Company not entered!</div>');
                     return false;
                  }
               <? } ?>
               <? if ($isMandatoryEmail) { ?>
                  if (leadEmail == '') {
                     $('#panelStatus').html('<div class="text-danger animate__animated animate__flash">Error: Email not entered!</div>');
                     return false;
                  }
               <? } ?>
               <? if ($isMandatoryMobile) { ?>
                  if (leadMobile == '') {
                     $('#panelStatus').html('<div class="text-danger animate__animated animate__flash">Error: Mobile not entered!</div>');
                     return false;
                  }
               <? } ?>
               if (leadEmail != '') {
                  if (!(chkFilterEmail.test(leadEmail))) {
                     $('#panelStatus').html('<div class="text-danger animate__animated animate__flash">Error: Email is not valid!</div>');
                     return false;
                  }
               }
               $('#panelStatus').html('');
               $('#btnSubmit').hide();
               $('#contentLoader').show();
               $.ajax({
                     url: 'signInProcess.php',
                     method: 'POST',
                     data: $('#frmLead').serializeArray()
                  })
                  .done(function(res) {
                     $('#contentLoader').hide();
                     if (res == 'OK') {
                        <? if ($readMoreResponse <> '') { ?>
                           alert('Your information has been submitted');
                           window.location.href = '<?= $readMoreResponse ?>';
                        <? } else { ?>
                           $('#panelStatus').html('Your information has been submitted');
                        <? } ?>
                     } else {
                        $('#btnSubmit').show();
                        $('#panelStatus').html('<div class="text-danger animate__animated animate__flash">Error: Information could not be submitted!</div>');
                     }
                  });
               return false;
            }
         </script>

      </form>
   </body>

   </html>
<? } ?>