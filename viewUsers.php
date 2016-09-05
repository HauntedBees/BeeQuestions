<?php
session_start();
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/base/BasePage.php";
$page = new BasePage("moderator/userQueue.html");
if(!isset($page->userInfo["modTier"])) { echo "nope"; exit; }
$modTier = intval($page->userInfo["modTier"]);
if($modTier < 100) { echo "nope"; exit; }

$sql = new SQLManager();
$data = $sql->Query("SELECT cID64, sDisplayName, sName, dtJoined, dtLastLoad, iScore, iLevel FROM bq_users ORDER BY cID DESC");

$userRow = new Template("moderator/userRow.html");
$results = $userRow->GetPDOFetchAssocContent($data, function($row, $args) {
	return [
		"uid" => $row["cID64"], 
		"user" => $row["sDisplayName"], 
		"real" => $row["sName"], 
		"join" => $row["dtJoined"], 
		"load" => $row["dtLastLoad"], 
		"score" => $row["iScore"], 
		"level" => $row["iLevel"]
	];
});

echo $page->GetPage(["tableRows" => $results]);
?>