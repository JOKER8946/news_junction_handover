<?
ini_set('display_startup_errors', 1);

include 'fb_function.php';

$credentials = json_decode(file_get_contents('fb_credentials.json'), true);
$longLivedToken = $credentials['longLivedToken'];
// Long-Lived Access Token to be tested
// $longLivedToken = 'EAAGvb6fjh0YBO32eFQzgo93fTwfvZAJRs5iMCSbLDKB9cZCRmS29Gz1wiVUjrMoevJHkozCo02icx6WhxYrGYA9pktT3mVrUsKnfAMLZCHhQn5UhVYSC925PZA3dm2d8MiVb5DiHpXF04XmgUuwvFAmDZBPMhv5ZBIvtqs7IOrohHK5AWtIT2ozGBmZC2QEfC7y2DcAJwZDZD';


$user = facebook_access_test($longLivedToken);

echo 'User ID: ' . $user['id'] . '<br>';
echo 'Name: ' . $user['name'];

?>