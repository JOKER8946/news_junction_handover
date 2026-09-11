<?
// Cream: Analytics

require_once '../inc/validate.logged.php';
require_once '../inc/config.php';

$act = '';
if (!empty($_POST)) $act = isset($_POST["act"]) ? $_POST["act"] : '';

// Show Blast Details
if ($act == 'showBlastDetails') {
 $blastId = isset($_POST['id']) ? $_POST['id'] : '';
 $sql = "SELECT send_grid_key FROM user WHERE id=$gUserId";
 $result = mysqli_query($db, $sql);
 $row = mysqli_fetch_assoc($result);
 $userSendGridKey = $row['send_grid_key'];
 $apiURL = "https://api.sendgrid.com/v3/marketing/stats/singlesends/$blastId?group_by=ab_variation&aggregated_by=total";
 $curl = curl_init();
 curl_setopt_array($curl, array(
  CURLOPT_URL => $apiURL,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_SSL_VERIFYHOST => false,
  CURLOPT_SSL_VERIFYPEER => false,
  CURLOPT_HTTPHEADER => array(
   "authorization: Bearer $userSendGridKey"
  ),
 ));
 $response = curl_exec($curl);
 curl_close($curl);
 $arrStats = json_decode($response, true);
 $arrStatsClicks = $arrStats['results'][0]['stats']['clicks'];
 $arrStatsUniqueClicks = $arrStats['results'][0]['stats']['unique_clicks'];
 $arrStatsDelivered = $arrStats['results'][0]['stats']['delivered'];
 $arrStatsInvalidEmails = $arrStats['results'][0]['stats']['invalid_emails'];
 $arrStatsOpens = $arrStats['results'][0]['stats']['opens'];
 $arrStatsUniqueOpens = $arrStats['results'][0]['stats']['unique_opens'];
 $arrStatsUnsubscribes = $arrStats['results'][0]['stats']['unsubscribes'];
?>
<div class="popup" style="max-width:600px">
<div class="widget">
 <div class="card">
  <div class="card-header bg-dark"><h5 class="mb-0 text-light">Newsletter Details</h5></div>
 </div>
 <div id="widget_B" style="padding:15px 25px; max-height:calc(100vh - 200px);overflow-y:auto;">
  <table class="table table-striped">
  <tr>
   <td>Delivered</td>
   <td><?=$arrStatsDelivered?></td>
  </tr>
  <tr>
   <td>Unique Opens</td>
   <td><?=$arrStatsUniqueOpens?></td>
  </tr>
  <tr>
   <td>Unique Clicks</td>
   <td><?=$arrStatsUniqueClicks?></td>
  </tr>
  <tr>
   <td>Unsubscribes</td>
   <td><?=$arrStatsUnsubscribes?></td>
  </tr>
  </table>
 </div>
</div>
</div>
<?
}


// Show Article Details
if ($act == 'showArticleDetails') {
 $postId = isset($_POST['id']) ? $_POST['id'] : '';
?>
<div class="popup" style="max-width:600px">
<div class="widget">
 <div class="card">
  <div class="card-header bg-dark"><h5 class="mb-0 text-light">Article Details</h5></div>
 </div>
 <div id="widget_B" style="padding:15px 25px; max-height:calc(100vh - 200px);overflow-y:auto;">
<?
 if ($gUserPlan == 0) {
  echo '<div class="alert alert-success" role="alert">This feature is only available in <b>Pro</b> plan! Go to My Account to upgrade.</div>';
 } else {
?>
  <table class="table table-striped">
  <thead>
  <tr>
   <th>Country</th>
   <th class="text-center">Total Views</th>
   <th>View Breakdown</th>
  </tr>
  </thead>
  <tbody>
<?
  $sql = "SELECT count(article_id) AS totalVisits,visit_country FROM metrics WHERE article_id=$postId GROUP BY visit_country ORDER BY visit_country";
  $result = mysqli_query($db, $sql);
  while($row = mysqli_fetch_assoc($result)) {
   $visitCountry = $row['visit_country'];
   $visitCountryCount = $row['totalVisits'];
?>
  <tr>
   <td><?=$visitCountry?></td>
   <td align="center"><?=$visitCountryCount?></td>
   <td>
<?
   $sql = "SELECT count(article_id) AS totalVisits,visit_city FROM metrics WHERE article_id=$postId AND visit_country='$visitCountry' GROUP BY visit_city ORDER BY visit_city";
   $resultInner = mysqli_query($db, $sql);
   while($rowInner = mysqli_fetch_assoc($resultInner)) {
    $visitCity = $rowInner['visit_city'];
    $visitCityCount = $rowInner['totalVisits'];
    if ($visitCity == '') $visitCity = 'Unknown';
?>
    <?=$visitCity?> - <?=$visitCityCount?><br>
<?
   }
?>
   </td>
  </tr>
<?
  }
?>
  </tbody>
  </table>
<?
 }
?>
 </div>
</div>
</div>
<?
}


// Show Article Leads
if ($act == 'showArticleLeads') {
 $postId = isset($_POST['id']) ? $_POST['id'] : '';
?>
<div class="popup" style="max-width:900px">
<div class="widget">
 <div class="card">
  <div class="card-header bg-dark"><h5 class="mb-0 text-light">Article Lead Details</h5></div>
 </div>
 <div id="widget_B" style="padding:15px 25px; max-height:calc(100vh - 200px);overflow-y:auto;">
  <table class="table table-striped">
  <thead>
  <tr>
   <th width="20">#</th>
   <th>Name</th>
   <th>Company</th>
   <th>Email</th>
   <th>Mobile</th>
   <th>Date</th>
  </tr>
  </thead>
  <tbody>
<?
 $sql = "SELECT * FROM user_collection_lead WHERE article_id=$postId ORDER BY date_created DESC";
 $result = mysqli_query($db, $sql);
 $i = 1;
 while($row = mysqli_fetch_assoc($result)) {
  $leadName = $row['full_name'];
  $leadCompany = $row['company'];
  $leadEmail = $row['email'];
  $leadMobile = $row['mobile'];
  $leadDate = $row['date_created'];
  $leadDate = date('M d, Y', strtotime($leadDate));
?>
  <tr>
   <td><?=$i?>.</td>
   <td><?=$leadName?></td>
   <td><?=$leadCompany?></td>
   <td><?=$leadEmail?></td>
   <td><?=$leadMobile?></td>
   <td><?=$leadDate?></td>
  </tr>
<?
  $i += 1;
 }
?>
  </tbody>
  </table>
 </div>
</div>
</div>
<?
}


if ($act == '') {
?>
<ol class="breadcrumb my-3">
 <li class="breadcrumb-item"><h4 class="m-0">Analytics</h4></li>
</ol>
<div class="row mb-4">
 <div class="col">
  <ul class="nav nav-tabs mb-4">
   <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#articles" role="tab">Top Visited Articles</a></li>
   <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#lead" role="tab">Article Leads</a></li>
   <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#newsletter" role="tab">Sent Newsletters</a></li>
  </ul>
  <div class="tab-content my-3">
   <div class="tab-pane fade show active" id="articles" role="tabpanel">
<?
 $sql = "SELECT B.id,B.title,count(A.article_id) AS totalVisits FROM metrics A
  INNER JOIN user_collection B ON (A.article_id=B.id AND B.user_id=$gUserId)
  GROUP BY B.url,B.id ORDER BY totalVisits DESC";
 $result = mysqli_query($db, $sql);
 $numRows = mysqli_num_rows($result);
 if ($numRows == 0) {
  echo '<div class="px-3">No articles to show!</div>';
 } else {
?>
    <table class="table table-striped">
    <thead>
    <tr>
     <th width="20">#</th>
     <th>Title</th>
     <th>Views</th>
     <th></th>
    </tr>
    </thead>
    <tbody>
<?
  $i = 1;
  while($row = mysqli_fetch_assoc($result)) {
   $topArticleId = $row['id'];
   $topArticleTitle = $row['title'];
   $topArticleVisit = $row['totalVisits'];
?>
    <tr>
     <td><?=$i?>.</td>
     <td><a href="https://www.newsjunction.net/view/<?=$topArticleId?>/<?=createArticleURL($topArticleTitle)?>" target="_blank"><?=$topArticleTitle?></a></td>
     <td><?=$topArticleVisit?></td>
     <td align="right"><a href="#" title="View Details"><i data-id="<?=$topArticleId?>" class="viewRowA far fa-chart-bar fa-lg text-muted"></i></a></td>
    </tr>
<?
   $i += 1;
  }
?>
    </tbody>
    </table>
<?
 }
?>
   </div>
   <div class="tab-pane fade" id="lead" role="tabpanel">
<?
 if ($gUserPlan == 0) {
  echo '<div class="alert alert-success" role="alert">This feature is only available in <b>Pro</b> plan! Go to My Account to upgrade.</div>';
 } else {
  $sql = "SELECT B.id,B.title,count(A.article_id) AS totalLeads FROM user_collection_lead A
   INNER JOIN user_collection B ON (A.article_id=B.id AND B.user_id=$gUserId)
   GROUP BY B.url,B.id ORDER BY totalLeads DESC LIMIT 25";
  $result = mysqli_query($db, $sql);
  $numRows = mysqli_num_rows($result);
  if ($numRows == 0) {
   echo '<div class="px-3">No article leads to show!</div>';
  } else {
?>
    <table class="table table-striped">
    <thead>
    <tr>
     <th width="20">#</th>
     <th>Title</th>
     <th>Leads</th>
     <th></th>
    </tr>
    </thead>
    <tbody>
<?
   $i = 1;
   while($row = mysqli_fetch_assoc($result)) {
    $topArticleId = $row['id'];
    $topArticleTitle = $row['title'];
    $topArticleLead = $row['totalLeads'];
?>
    <tr>
     <td><?=$i?>.</td>
     <td><a href="https://www.newsjunction.net/view/<?=$topArticleId?>/<?=createArticleURL($topArticleTitle)?>" target="_blank"><?=$topArticleTitle?></a></td>
     <td><?=$topArticleLead?></td>
     <td align="right"><a href="#" title="View Details"><i data-id="<?=$topArticleId?>" class="viewRowL far fa-chart-bar fa-lg text-muted"></i></a></td>
    </tr>
<?
    $i += 1;
   }
?>
    </tbody>
    </table>
<?
  }
 }
?>
   </div>
   <div class="tab-pane fade" id="newsletter" role="tabpanel">
<?
 if ($gUserPlan == 0) {
  echo '<div class="alert alert-success" role="alert">This feature is only available in <b>Pro</b> plan! Go to My Account to upgrade.</div>';
 } else {
  $sql = "SELECT A.newsletter_id,A.blast_provider,A.blast_subject,A.blast_id,A.date_created FROM user_blast A
   INNER JOIN user_newsletter B ON A.newsletter_id=B.id AND B.user_id=$gUserId";
  $result = mysqli_query($db, $sql);
  $numRows = mysqli_num_rows($result);
  if ($numRows == 0) {
   echo '<div class="px-3">No newsletters sent!</div>';
  } else {
?>
    <table class="table table-striped">
    <thead>
    <tr>
     <th width="20">#</th>
     <th>Provider</th>
     <th>Subject</th>
     <th>Date Sent</th>
     <th></th>
    </tr>
    </thead>
    <tbody>
<?
   $i = 1;
   while($row = mysqli_fetch_assoc($result)) {
    $newsletterId = $row['newsletter_id'];
    $blastId = $row['blast_id'];
    $blastProvider = $row['blast_provider'];
    $blastSubject = $row['blast_subject'];
    $blastDate = $row['date_created'];
    $blastDate = date('M d, Y', strtotime($blastDate));
?>
    <tr>
     <td><?=$i?>.</td>
     <td><?=$blastProvider?></td>
     <td><a href="newsletter.php?id=<?=$newsletterId?>" target="_blank"><?=$blastSubject?></a></td>
     <td><?=$blastDate?></td>
     <td align="right"><a href="#" title="View Details"><i data-id="<?=$blastId?>" class="viewRowN far fa-chart-bar fa-lg text-muted"></i></a></td>
    </tr>
<?
    $i += 1;
   }
?>
    </tbody>
    </table>
<?
  }
 }
?>
   </div>

  </div>
 </div>
</div>

<script type="text/javascript">
$(function () {
 $('.viewRowA').magnificPopup({
  type: 'ajax',
  closeBtnInside: true,
  ajax: {
   settings: {
    method: 'POST',
    url: 'process/get.section.analytics.php'
   }
  },
  callbacks: {
   elementParse: function(item){
    var data = $(item.el[0]).closest('.data');
    postData = {
     act: 'showArticleDetails',
     id: $(item.el[0]).data('id')
    }
    this.st.ajax.settings.data = postData;
   }
  }
 });
 $('.viewRowL').magnificPopup({
  type: 'ajax',
  closeBtnInside: true,
  ajax: {
   settings: {
    method: 'POST',
    url: 'process/get.section.analytics.php'
   }
  },
  callbacks: {
   elementParse: function(item){
    var data = $(item.el[0]).closest('.data');
    postData = {
     act: 'showArticleLeads',
     id: $(item.el[0]).data('id')
    }
    this.st.ajax.settings.data = postData;
   }
  }
 });
 $('.viewRowN').magnificPopup({
  type: 'ajax',
  closeBtnInside: true,
  ajax: {
   settings: {
    method: 'POST',
    url: 'process/get.section.analytics.php'
   }
  },
  callbacks: {
   elementParse: function(item){
    var data = $(item.el[0]).closest('.data');
    postData = {
     act: 'showBlastDetails',
     id: $(item.el[0]).data('id')
    }
    this.st.ajax.settings.data = postData;
   }
  }
 });
});
</script>
<?
}
?>