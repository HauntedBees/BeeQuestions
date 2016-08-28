<?php
session_start();
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/base/BasePage.php";
$page = new BasePage("moderator/reportQueue.html");
if(!isset($page->userInfo["modTier"])) {
	echo "nope";
	exit;
}
$modTier = intval($page->userInfo["modTier"]);
if($modTier < 1) {
	echo "nope";
	exit;
}

$sql = new SQLManager();
$query = <<<EOT
	SELECT x.xUser, u.sDisplayName, x.xAnswer, a.sAnswer, x.xQuestion, q.sQuestion, ur.sDisplayName AS reporterName, x.dtReported
	FROM bq_users_reports_xref x
		INNER JOIN bq_users u ON x.xUser = u.cID
		LEFT JOIN bq_answers a ON x.xAnswer = a.cID
		LEFT JOIN bq_questions q ON x.xQuestion = q.cID
		INNER JOIN bq_users ur ON x.xReportedBy = ur.cID
	WHERE x.bDismissed = 0
	ORDER BY x.xUser ASC, x.xAnswer ASC, x.xQuestion ASC, x.dtReported ASC
EOT;
$data = $sql->Query($query);

$actionTemplate = new Template("moderator/reportAction.html");
$reportRow = new Template("moderator/reportRow.html");
$results = $reportRow->GetPDOFetchAssocContent($data, function($row, $args) {
	$actionsHTML = "";
	$separatorHTML = "<li role='separator' class='divider'></li>";
	if(intval($row["xQuestion"]) > 0) { 
		$actionsHTML .= $args["actionTemplate"]->GetLoopedContent([
			"category" => "Deletes", 
			"type" => "DeleteQ",
			"text" => "Delete Question"
		]);
	}
	if(intval($row["xAnswer"]) > 0) { 
		$actionsHTML .= $args["actionTemplate"]->GetLoopedContent([
			"category" => "Deletes", 
			"type" => "DeleteA",
			"text" => "Delete Answer"
		]);
	}
	$actionsHTML .= $separatorHTML;
	if($args["tier"] >= 2) {
		$actionsHTML .= $args["actionTemplate"]->GetLoopedContent([
			"category" => "Bans", 
			"type" => "Tempban",
			"text" => "Temporary Ban"
		]);
		if($args["tier"] >= 3) {
			$actionsHTML .= $args["actionTemplate"]->GetLoopedContent([
				"category" => "Bans",
				"type" => "Permaban",
				"text" => "Ban"
			]);
		}
		$actionsHTML .= $separatorHTML;
		$actionsHTML .= $args["actionTemplate"]->GetLoopedContent([
			"category" => "Purge",
			"type" =>"Purge",
			"text" => "Purge"
		]);
	}
	return [
		"uid" => $row["xUser"], 
		"aid" => $row["xAnswer"], 
		"qid" => $row["xQuestion"], 
		"user" => $row["sDisplayName"], 
		"answer" => $row["sAnswer"], 
		"question" => $row["sQuestion"], 
		"date" => $args["basePage"]->GetTimeElapsedString(new DateTime($row["dtReported"])), 
		"reporter" => $row["reporterName"], 
		"actions" => $actionsHTML
	];
}, ["actionTemplate" => $actionTemplate, "basePage" => $page, "tier" => $modTier]);

echo $page->GetPage(["tableRows" => $results]);
?>