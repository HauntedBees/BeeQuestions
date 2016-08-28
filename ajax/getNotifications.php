<?php
session_start();
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/base/BasePage.php";
$page = new BasePage("users/user.html");
$userId = $page->userInfo["id"];
if(intval($userId) == 0) { exit; }
$sql = new SQLManager();
$notificationCount = intval($sql->QueryVal("SELECT COUNT(*) FROM bq_notifications WHERE bDismissed = 0 AND xUser = :user", ["user" => $userId]));
if($notificationCount == 0) { exit; }
if($notificationCount > 5) {
	$sql->Query("UPDATE bq_notifications SET bDismissed = 1 WHERE bDismissed = 0 AND xUser = :user", ["user" => $userId]);
	echo json_encode([["notif" => "You have $notificationCount notifications waiting for you!", "url" => "user.php?user=".Base64::to64($page->userInfo["hexId"])]]);
	exit;
}
$notifications = $sql->Query("SELECT sTemplate, sToken1, sToken2, sToken3, sToken4, sToken5, sToken6 FROM bq_notifications WHERE bDismissed = 0 AND xUser = :user ORDER BY dtPosted DESC", ["user" => $userId]);
$resArray = [];
while($row = $notifications->fetch(PDO::FETCH_ASSOC)) {
	$innerTemplate = new Template("notifications/".$row["sTemplate"]);
	$innerTemplate->SetKeys([
		"token1" => $row["sToken1"], 
		"token2" => $row["sToken2"], 
		"token3" => $row["sToken3"], 
		"token4" => $row["sToken4"], 
		"token5" => $row["sToken5"], 
		"token6" => $row["sToken6"]
	]);
	$resArray[] = ["notif" => $innerTemplate->GetContent()];
}
$sql->Query("UPDATE bq_notifications SET bDismissed = 1 WHERE bDismissed = 0 AND xUser = :user", ["user" => $userId]);
echo json_encode($resArray);
?>