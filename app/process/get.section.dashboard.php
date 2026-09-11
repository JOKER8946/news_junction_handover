<?
// Cream: Dashboard

require_once '../inc/validate.logged.php';
require_once '../inc/config.php';

$act = '';

// Default
if ($act == '') {
 $sql = "SELECT COUNT(id) AS countArticle FROM user_collection WHERE user_id=$gUserId";
 $result = mysqli_query($db, $sql);
 $row = mysqli_fetch_assoc($result);
 $countArticle = $row['countArticle'];

 $sql = "SELECT COUNT(user_id) AS countFeed FROM user_feeds WHERE user_id=$gUserId";
 $result = mysqli_query($db, $sql);
 $row = mysqli_fetch_assoc($result);
 $countFeed = $row['countFeed'];

 $sql = "SELECT COUNT(user_id) AS countNewsletter FROM user_newsletter WHERE user_id=$gUserId";
 $result = mysqli_query($db, $sql);
 $row = mysqli_fetch_assoc($result);
 $countNewsletter = $row['countNewsletter'];

 $sql = "SELECT COUNT(A.article_id) AS countVisit FROM metrics A INNER JOIN user_collection B ON A.article_id=B.id AND B.user_id=$gUserId";
 $result = mysqli_query($db, $sql);
 $row = mysqli_fetch_assoc($result);
 $countVisit = $row['countVisit'];
?>
<ol class="breadcrumb my-3">
 <li class="breadcrumb-item"><h4 class="m-0">Dashboard</h4></li>
</ol>
<div class="row">
 <div class="col-12 col-md-6 col-xl-3">
  <div class="card bg-primary text-white mb-4">
   <div class="card-body">Articles</div>
   <div class="card-footer text-right"><h3><?=$countArticle?></h3></div>
  </div>
 </div>
 <div class="col-12 col-md-6 col-xl-3">
  <div class="card bg-success text-white mb-4">
   <div class="card-body">Feeds</div>
   <div class="card-footer text-right"><h3><?=$countFeed?></h3></div>
  </div>
 </div>
 <div class="col-12 col-md-6 col-xl-3">
  <div class="card bg-warning text-white mb-4">
   <div class="card-body">Newsletter</div>
   <div class="card-footer text-right"><h3><?=$countNewsletter?></h3></div>
  </div>
 </div>
 <div class="col-12 col-md-6 col-xl-3">
  <div class="card bg-danger text-white mb-4">
   <div class="card-body">Visits</div>
   <div class="card-footer text-right"><h3><?=$countVisit?></h3></div>
  </div>
 </div>
</div>
<ol class="breadcrumb my-3">
 <li class="breadcrumb-item"><h4 class="m-0">Top 5 Visited Articles</h4></li>
</ol>
<?
 $sql = "SELECT B.id,B.title,count(A.article_id) AS totalVisits FROM metrics A
  INNER JOIN user_collection B ON (A.article_id=B.id AND B.user_id=$gUserId)
  GROUP BY B.url,B.id ORDER BY totalVisits DESC LIMIT 5";
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
<script type="text/javascript">
$(function() {
});
</script>
<?
}
?>