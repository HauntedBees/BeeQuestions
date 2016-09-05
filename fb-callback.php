<?php
session_start();
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/Facebook/autoload.php";
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/user/UserHandler.php";
$c = parse_ini_file($_SERVER["DOCUMENT_ROOT"]."/bq/secure/config.ini", true)["facebook"];
$fb = new Facebook\Facebook([
	"app_id" => $c["app_id"],
	"app_secret" => $c["app_secret"],
	"default_graph_version" => $c["default_graph_version"],
]);
$helper = $fb->getRedirectLoginHelper();
$_SESSION["FBRLH_state"]=$_GET["state"];
try {
	$accessToken = $helper->getAccessToken();
} catch(Facebook\Exceptions\FacebookResponseException $e) {
	ErrorLog::AddError("fb-callback::FacebookResponseException", $e->getMessage());
	header("Location: http://".$_SERVER["SERVER_NAME"]."/bq/index.html?errno=4203");
	exit;
} catch(Facebook\Exceptions\FacebookSDKException $e) {
	ErrorLog::AddError("fb-callback::FacebookSDKException", $e->getMessage());
	header("Location: http://".$_SERVER["SERVER_NAME"]."/bq/index.html?errno=4203");
	exit;
}
if (!isset($accessToken)) {
	if ($helper->getError()) {
		ErrorLog::AddError("fb-callback::Error", "Error: ".$helper->getError()." (".$helper->getErrorCode().")\nReason:".$helper->getErrorReason()."\nDescription:".$helper->getErrorDescription());
	} else {
		ErrorLog::AddError("fb-callback::No Access Token", "Bad request");
	}
	header("Location: http://".$_SERVER["SERVER_NAME"]."/bq/index.html?errno=4203");
	exit;
}

$oAuth2Client = $fb->getOAuth2Client();
if (!$accessToken->isLongLived()) {
	try {
		$accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
	} catch (Facebook\Exceptions\FacebookSDKException $e) {
		ErrorLog::AddError("fb-callback::FacebookSDKException", "Error getting long-lived access token: ".$helper->getMessage());
		header("Location: http://".$_SERVER["SERVER_NAME"]."/bq/index.html?errno=4203");
		exit;
	}
}
$_SESSION["fbid"] = $accessToken->getValue();

$response = $fb->get("/me?fields=id,name", $accessToken->getValue());
$user = $response->getGraphUser();

$uh = new UserHandler();
$uh->CreateOrUpdateFacebookUser($user["id"], $user["name"]);

header("Location: http://".$_SERVER["SERVER_NAME"]."/bq/index.html");
?>