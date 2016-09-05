<?php
session_start();
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/base/BasePage.php";
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/common/userFunctions.php";
$page = new BasePage("users/user.html");
$userId = $page->sql->QueryVal("SELECT cID FROM bq_users WHERE bnID = :id", ["id" => hex2bin(Base64::toHex($_POST["user"]))]);
if(!isset($page->userInfo["id"]) || $page->userInfo["id"] != $userId) { 
	echo "You can't view other people's history!";
	exit;
}
echo GetUserHistory($page, new SQLManager(), $userId, $_POST["filter"], $_POST["offset"]);
?>