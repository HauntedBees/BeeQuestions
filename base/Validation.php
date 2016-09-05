<?php
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/Facebook/autoload.php";
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/user/UserHandler.php";
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/common/commonFunctions.php";
$sql_bnID = "UNHEX(REPLACE(UUID() COLLATE utf8_unicode_ci, '-', ''))";
function QStoDB($val, $errMsg) { // converts querystring value to binary value to search database with
	if(strlen($val) != 21) { ReturnError($errMsg); }
	return hex2bin(Base64::toHex($val));
}
function WordFilterAndRemoveHTML($s) {
	$badWords = ["poopoo", "doodoo"];
	$badRegexes = ["/\sjerks?/"];
	$s = iconv("UTF-8", "ISO-8859-1//TRANSLIT", $s);
	$replacements = ["a" => "@|4|À|Á|Â|Ã|Ä|Å|à|á|â|ã|ä|å|ª",
					 "b" => "8|ß", 
					 "c" => "\(|\[|Ç|ç|¢|©",
					 "d" => "ð|Ð",
					 "e" => "3|³|È|É|Ê|Ë|è|é|ê|ë", 
					 "f" => "ph",
					 "g" => "6",
					 "i" => "Ì|Í|Î|Ï|ì|í|î|ï|¹|¡",
					 "l" => "£", 
					 "n" => "Ñ|ñ", 
					 "o" => "0|º|Ò|Ó|Ô|Õ|Ö|Ø|ò|ó|ô|õ|ö|ø|¤|°", 
					 "p" => "¶", 
					 "q" => "9", 
					 "r" => "®",
					 "s" => "§", 
					 "t" => "7|±", 
					 "u" => "µ|Ù|Ú|Û|Ü|ù|ú|û|ü", 
					 "w" => "vv", 
					 "x" => "×", 
					 "y" => "Ý|Ÿ|ý|ÿ",
					 "z" => "2|²"];
	$halfreplacements = ["" => "", "a" => "Æ|æ", "b" => "þ|Þ", "c" => "k", "e" => "Æ|æ", "i" => "l|!|1", "k" => "c", "l" => "i|!|1", "p" => "þ|Þ", "u" => "v", "v" => "u"];
	$s2 = strtolower($s);
	$s2 = preg_replace("/-/", "", $s2);
	$s2 = preg_replace("/[^a-z0-9]/", " ", $s2);
	$s2 = preg_replace("/\s+/", " ", $s2);
	foreach($replacements as $k => $v) { $s2 = preg_replace("/$v/", $k, $s2); }
	foreach($halfreplacements as $k => $v) {
		$s3 = ($k == "") ? $s2 : preg_replace("/$v/", $k, $s2);
		foreach($badWords as $badWord) { if(preg_match("/.*$badWord.*/", $s3)) { ReturnError("Don't say words like that, come on."); } }
		foreach($badRegexes as $badRegex) { if(preg_match($badRegex, $s3)) { ReturnError("Don't say words like that, come on."); } }
	}
	$sfixed = preg_replace("/</", "&lt;", $s);
	return preg_replace("/>/", "&gt;", $sfixed);
}
function ReturnError($msg) {
	echo json_encode(["status" => false, "errorMessage" => $msg]);
	exit;
}
function ValidateAndReturnUserId($isAjax=false) {
	if(!isset($_SESSION["fbid"])) {
		if($isAjax) { echo json_encode(["status" => false, "errorMessage" => "Please log in."]); }
		else { header("Location: http://".$_SERVER["SERVER_NAME"]."/bq/index.html?errno=4200"); }
		exit;
	}
	$c = parse_ini_file($_SERVER["DOCUMENT_ROOT"]."/bq/secure/config.ini", true)["facebook"];
	$fb = new Facebook\Facebook([
		"app_id" => $c["app_id"],
		"app_secret" => $c["app_secret"],
		"default_graph_version" => $c["default_graph_version"],
	]);
	$accessToken = new Facebook\Authentication\AccessToken($_SESSION["fbid"]);
	if($_SESSION["fbid"] != $accessToken->getValue()) {
		unset($_SESSION["fbid"]);
		if($isAjax) { echo json_encode(["status" => false, "errorMessage" => "An error has occurred. Please log in again to proceed."]); }
		else { header("Location: http://".$_SERVER["SERVER_NAME"]."/bq/index.html?errno=4201"); }
		exit;
	}
	try {
		$response = $fb->get("/me?fields=id", $accessToken->getValue());
	} catch(Facebook\Exceptions\FacebookResponseException $e) {
		unset($_SESSION["fbid"]);
		if($isAjax) { echo json_encode(["status" => false, "errorMessage" => "An error has occured. Please log in again to proceed."]); }
		else { header("Location: http://".$_SERVER["SERVER_NAME"]."/bq/index.html?errno=4201"); }
		exit;
	} catch(Facebook\Exceptions\FacebookSDKException $e) {
		unset($_SESSION["fbid"]);
		if($isAjax) { echo json_encode(["status" => false, "errorMessage" => "An error has occured. Please log in again to proceed."]); }
		else { header("Location: http://".$_SERVER["SERVER_NAME"]."/bq/index.html?errno=4201"); }
		exit;
	}

	$user = $response->getGraphUser();
	$uh = new UserHandler();
	$userInfo = $uh->GetFacebookUser($user["id"]);
	$userID = $userInfo["cID"];
	if($userID == null || $userID <= 0) {
		unset($_SESSION["fbid"]);
		if($isAjax) { echo json_encode(["status" => false, "errorMessage" => "An error has occured. Please log in again to proceed."]); }
		else { header("Location: http://".$_SERVER["SERVER_NAME"]."/bq/index.html?errno=4201"); }
		exit;
	}
	$banDate = $userInfo["dtBannedUntil"];
	if($banDate != null) {
		$banDateTime = new DateTime($banDate);
		if($banDateTime > new DateTime()) {
			if($isAjax) { echo json_encode(["status" => false, "errorMessage" => "You are currently banned from performing this action."]); }
			else { header("Location: http://".$_SERVER["SERVER_NAME"]."/bq/index.html?err=4202"); }
			exit;
		}
	}
	return $userID;
}
?>