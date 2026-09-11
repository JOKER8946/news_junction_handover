<?
include 'inc/config.php';
$id = isset($_GET["id"]) ? $_GET["id"] : '';
$newsData = buildNewsletter($id);

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

$ip = getenv('HTTP_CLIENT_IP') ?: getenv('HTTP_X_FORWARDED_FOR') ?: getenv('HTTP_X_FORWARDED') ?: getenv('HTTP_FORWARDED_FOR') ?: getenv('HTTP_FORWARDED') ?: getenv('REMOTE_ADDR');
$response = unserialize(file_get_contents('http://www.geoplugin.net/php.gp?ip=' . $ip));
if ($response === false) {
	$visitCity = '';
	$visitCountry = '';
} else {
	$visitCity = $response['geoplugin_city'];
	$visitCountry = $response['geoplugin_countryName'];
}
$sql = "INSERT INTO metrics(article_id,ip,visit_city,visit_country,date_visited,category) VALUES($id,'$ip','$visitCity','$visitCountry',Now(), 'Newsletter')";
  mysqli_query($db, $sql);

?>

<!doctype html>
<html lang="en">

<head>
	<title>News Junction</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<?=$newsData['meta_tag'] ?>

	<link rel="stylesheet" href="inc/fontawesome/css/all.min.css" />
	<link rel="stylesheet" href="inc/style.css" />
	<link rel="icon" type="image/x-icon" href="/img/logo.ico">

	<style>
		img {
			max-width: 300px;
		}
	</style>
</head>

<body>
	<center>
		<? echo $newsData['html_data']; ?>
	</center>
</body>

</html>