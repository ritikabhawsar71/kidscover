<?php
session_start();
require_once("twitteroauth/twitteroauth/twitteroauth.php"); //Path to twitteroauth library
 
$twitteruser = "kidscover";
$notweets = 30;
$consumerkey = "BWcN9pHWbq2EAgqRNRqrYe7S7";
$consumersecret = "MfPuo00YpJ0e7X1otdOAgtY7bSwG2SRhfGD767H5zQ7dZ4i5e2";
$accesstoken = "1733124860-ah3A74uGRMg87ldfmTbmBfJCqSYVvszwbxnXfGH";
$accesstokensecret = "B9KoOyGlmSnjQlW7kivfGOZctkvU6pSV2P1X1OVDtPAiJ";
 
function getConnectionWithAccessToken($cons_key, $cons_secret, $oauth_token, $oauth_token_secret) {
  $connection = new TwitterOAuth($cons_key, $cons_secret, $oauth_token, $oauth_token_secret);
  return $connection;
}
  
$connection = getConnectionWithAccessToken($consumerkey, $consumersecret, $accesstoken, $accesstokensecret);
 
$tweets = $connection->get("https://api.twitter.com/1.1/statuses/user_timeline.json?screen_name=".$twitteruser."&count=".$notweets);
 
echo json_encode($tweets);
?>