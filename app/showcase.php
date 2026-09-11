<!doctype html>
<?
include 'inc/config.php';
include 'inc/php/function.php';

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
$domain = isset($_GET["domain"]) ? $_GET["domain"] : '';

$sql = '';
if ($id <> '') $sql = "SELECT id,full_name,company,website,news_title,news_logo,subdomain,is_side_panel,side_panel_content FROM user WHERE id=$id";
if ($domain <> '') $sql = "SELECT id,full_name,company,website,news_title,news_logo,subdomain,is_side_panel,side_panel_content FROM user WHERE subdomain='$domain'";

if ($sql <> '') {
   $result = mysqli_query($db, $sql);
   $row = $result->fetch_assoc();
   $numRows = mysqli_num_rows($result);
   if ($numRows == 0) die();

   $serverName = $_SERVER['SERVER_NAME'];
   $id = $row['id'];
   $user = $row['full_name'];
   $companyName = $row['company'];
   $website = strtolower($row['website']);
   $newsTitle = $row['news_title'];
   $newsLogo = $row['news_logo'];
   $userSubdomain = $row['subdomain'];
   $userSidePanel = $row['is_side_panel'];
   $userSidePanelContent = $row['side_panel_content'];
   $userSidePanelContent = str_replace('<img src="data/', '<img src="https://' . $serverName . '/data/', $userSidePanelContent);
   if (strpos($website, 'http') === false) $website = "http://$website";

   $currSubdomain = explode('.', $_SERVER['HTTP_HOST'])[0];
   if ($currSubdomain <> 'newsjunction' && $currSubdomain <> 'www' && $currSubdomain <> 'localhost') {
      if ($userSubdomain <> $currSubdomain) {
         echo '<br><br><center>You are not allowed to acccess this resource!</center>';
         die();
      }
   }
?>
   <html lang="en">

   <head>
      <title><?= $companyName ?></title>
      <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

      <!-- Facebook Meta Tags -->
      <meta property="og:url" content="https://<?= $serverName ?>/" />
      <meta property="og:type" content="website" />
      <meta property="og:title" content="<?= $companyName ?>" />
      <meta property="og:description" content="" />
      <meta property="og:image:secure-url" itemprop="image" content="https://<?= $serverName ?>/data/logos/<?= $newsLogo ?>" />

      <!-- Twitter Meta Tags -->
      <meta property="twitter:url" content="https://<?= $serverName ?>/" />
      <meta name="twitter:card" content="summary" />
      <meta name="twitter:title" content="<?= $companyName ?>" />
      <meta name="twitter:description" content="" />
      <meta name="twitter:image" content="https://<?= $serverName ?>/data/logos/<?= $newsLogo ?>" />

      <link rel="shortcut icon" href="https://<?= $serverName ?>/data/logos/<?= $newsLogo ?>" type="image/x-icon" />
      <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous" />
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
      <link rel="stylesheet" href="/assets/old/style.css" />
      <style>
         body {
            font-size: 16px;

         }

         .boxItem {
            margin: 15px 0;
            padding: 20px;
            border-color: 1px rgba(0, 0, 0, 0.75) solid;
            border-radius: 8px;
            box-shadow: 0px 1px 3px rgba(0, 0, 0, 0.2);
            background-color: #fff;
         }

         .panelContent img {
            width: auto;
            height: auto;
            max-width: 100%;
         }
      </style>
   </head>

   <body>
      <div class="container">

         <header class="blog-header py-3 border-bottom">
            <div class="row flex-nowrap justify-content-between align-items-center">
               <div class="col-8 pt-1">
                  <? if ($newsLogo <> '') { ?>
                     <a class="text-muted" href="<?= $website ?>"><img class="mx-auto" width="256px" src="https://<?= $serverName ?>/data/logos/<?= $newsLogo ?>" /></a>
                  <? } ?>
                  <h6 class="mt-1" style="color:#9e9e9e"><?= $user ?> </h6>
               </div>
               <div class="col-4 text-right">
               </div>
            </div>
         </header>

         <div class="row mt-3 pb-5">
            <div class="panelContent col-12 col-md pb-3">

               <?
               // For KBKB
               if ($userSubdomain == 'kbkb') {
               ?>
                  <iframe width="300" height="260" src="https://liveindex.org/widget/300-light.php" frameborder="0" hspace="0" vspace="0" scrolling="0" style="background:transparent"></iframe>
               <? } ?>

               <?
               // For KBKB
               if ($userSubdomain == 'kannadanaadu') {
               ?>
                  <iframe width="640" height="360" src="https://www.youtube.com/embed/kzK4zmQQSoc" title="Karnataka Legislative Assembly Session Day 05 Live: ಕರ್ನಾಟಕ ವಿಧಾನಸಭೆ ಅಧಿವೇಶನ ನೇರಪ್ರಸಾರ" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
               <? } ?>

               <?
               $sql = "SELECT * FROM user_landing WHERE user_id=$id ORDER BY date_created DESC";
               $result = mysqli_query($db, $sql);
               $numRows = mysqli_num_rows($result);
               if ($numRows == 0) {
                  echo '<div class="p-2">Nothing to show here!</div>';
               } else {
                  while ($row = mysqli_fetch_assoc($result)) {
                     $userId = $row['user_id'];
                     $articleId = $row['article_id'];
                     $articleDate = $row['date_created'];

                     $sql = "SELECT full_name FROM user WHERE id=$userId";
                     $resultInner = mysqli_query($db, $sql);
                     $rowInner = mysqli_fetch_assoc($resultInner);
                     $articleAuthor = $rowInner['full_name'];

                     $sql = "SELECT * FROM user_collection WHERE id=$articleId";
                     $resultInner = mysqli_query($db, $sql);
                     $numRowsInner = mysqli_num_rows($resultInner);
                     if ($numRowsInner > 0) {
                        $rowInner = mysqli_fetch_assoc($resultInner);
                        $artId = $rowInner['id'];
                        $artTitle = $rowInner['title'];
                        $artURL = 'view/' . $artId . '/' . createArticleURL($artTitle);
                        $artDesc = $rowInner['description'];
                        $artDesc = str_replace('<img src="data/posts/', '<img src="https://newsjunction.net/data/posts/', $artDesc);
                        $artCoverImg = $rowInner['cover_img'];
                        $artDate = $rowInner['date_added'];

                        $countReads = 0;
                        $sql = "SELECT count(article_id) AS countReads FROM metrics WHERE article_id=$articleId";
                        $resultCount = mysqli_query($db, $sql);
                        $rowCount = mysqli_fetch_assoc($resultCount);
                        $countReads = $rowCount['countReads'];

                        $collectionURL = $rowInner['url'];
                        $collectionPublisher = substr($collectionURL, strpos($collectionURL, ".") + 1);
                        $collectionPublisher = ucfirst(strtok($collectionPublisher, '.'));
                        if ($collectionPublisher == '') $collectionPublisher = 'Cream';
               ?>
                        <div class="row mx-3 mx-lg-0">
                           <div class="col-12 boxItem">
                              <?
                              if ($artCoverImg <> '') {
                              ?>
                                 <div style="padding-bottom:10px"><a href="<?= $artURL ?>" target="_blank"><img src="https://newsjunction.net/data/covers/<?= $artCoverImg ?>" class="img-fluid" /></a></div>
                              <?
                              }
                              ?>
                              <div style="padding-bottom:10px;font-size:18px;"><a href="<?= $artURL ?>" target="_blank"><strong><?= $artTitle ?></strong></a></div>

                              <? if ($collectionPublisher <> 'Cream') { ?>
                                 <div class="mt-1"><?= $artDesc ?></div>
                              <? } ?>

                              <div style="margin-top:10px;font-size:13px;color:#888;">
                                 <div class="float-left">Date: <?= date('M d, Y', strtotime($artDate)) ?></div>
                                 <? if ($countReads > 10) { ?>
                                    <div class="float-right"><?= $countReads ?> reads</div>
                                 <? } ?>
                              </div>
                           </div>
                        </div>
               <?
                     }
                  }
               }
               ?>

               <?
               // For KBKB
               if ($userSubdomain == 'kbkb') {
               ?>
                  <div class="my-3" style="font-size:13px">
                     <b>Disclaimer:</b><br>
                     This is a news and data aggregation service and do not represent the views and opinions of the publisher. This is published only as a central repo for relevant information for an investor and a trader. Users are advised to use their own analysis and discretion before taking any action. The publisher is not liable for any kind of loss arising out of any action taken based on the content of this service.<br>
                  </div>
               <? } ?>

            </div>

            <? if ($userSidePanel == 1) { ?>
               <div style="width:300px;"><?= $userSidePanelContent ?></div>
            <? } ?>
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

   </html>
<? } ?>