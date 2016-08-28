<?php
session_start();
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/base/BasePage.php";
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/common/scoreUpdating.php";

function PurgeQuestion($sql, $qID, $user) {
	$sql->Query("UPDATE bq_questions SET bDeleted = 1 WHERE cID = :q", ["q" => $qID]);
	$sql->Query("DELETE FROM bq_questions_likes_xref WHERE xQuestion = :q", ["q" => $qID]);
	$question = $sql->QueryVal("SELECT sQuestion FROM bq_questions WHERE cID = :q", ["q" => $qID]);
	$sql->Query("INSERT INTO bq_notifications (xUser, sTemplate, sToken1, dtPosted) VALUES (:u, 'deletedQuestion.html', :q, NOW())", [
		"u" => $user,
		"q" => $question
	]);
	IncrementScore($sql, $user, -20);
}

function PurgeAnswer($sql, $aID, $user) {
	$sql->Query("UPDATE bq_answers SET bDeleted = 1 WHERE cID = :a", ["a" => $aID]);
	$sql->Query("UPDATE bq_questions SET bDeleted = 1 WHERE xAnswer = :a", ["a" => $aID]);
	$sql->Query("DELETE FROM bq_questions_likes_xref WHERE xQuestion IN (SELECT cID FROM bq_questions WHERE xAnswer = :a)", ["a" => $aID]);
	$sql->Query("DELETE FROM bq_answers_likes_xref WHERE xAnswer = :a", ["a" => $aID]);
	$sql->Query("DELETE FROM bq_answers_tags_xref WHERE xAnswer = :a", ["a" => $aID]);
	$answer = $sql->QueryVal("SELECT sAnswer FROM bq_answers WHERE cID = :a", ["a" => $aID]);
	$sql->Query("INSERT INTO bq_notifications (xUser, sTemplate, sToken1, dtPosted) VALUES (:u, 'deletedAnswer.html', :a, NOW())", [
		"u" => $user,
		"a" => $answer
	]);
	IncrementScore($sql, $user, -20);
}
function PurgeUser($sql, $uID, $tier) {
	if($tier < 2) {
		echo json_encode(["status" => false]);
		exit;
	}
	$sql->Query("UPDATE bq_answers SET bDeleted = 1 WHERE xUser = :u", ["u" => $uID]);
	$sql->Query("UPDATE bq_questions SET bDeleted = 1 WHERE xUser = :u", ["u" => $uID]);
	$sql->Query("DELETE FROM bq_questions_likes_xref WHERE xQuestion IN (SELECT cID FROM bq_questions WHERE xUser = :u)", ["u" => $uID]);
	$sql->Query("DELETE FROM bq_answers_likes_xref WHERE xAnswer IN (SELECT cID FROM bq_answers WHERE xUser = :u)", ["u" => $uID]);
	$sql->Query("DELETE FROM bq_answers_tags_xref WHERE xAnswer IN (SELECT cID FROM bq_answers WHERE xUser = :u)", ["u" => $uID]);
	$sql->Query("UPDATE bq_questions SET bDeleted = 1 WHERE xAnswer IN (SELECT cID FROM bq_answers WHERE xUser = :u)", ["u" => $uID]);
	$sql->Query("DELETE FROM bq_questions_likes_xref WHERE xQuestion IN (SELECT cID FROM bq_questions WHERE xAnswer IN (SELECT cID FROM bq_answers WHERE xUser = :u))", ["u" => $uID]);
}
function BanUser($sql, $uID, $permanent, $tier) {
	if(($permanent && $tier < 3) || $tier < 2) {
		echo json_encode(["status" => false]);
		exit;
	}
	$banEnd = new DateTime();
	$priorBans = $sql->QueryVal("SELECT iTimesBanned FROM bq_users WHERE cID = :u", ["u" => $uID]);
	if($priorBans == 0) {
		$banEnd->add(new DateInterval("PT3H"));
	} else if($priorBans < 6) {
		$mult = $priorBans * 2;
		$banEnd->add(new DateInterval("P".$mult."D"));
	} else {
		$permanent = true;
	}
	if($permanent) { $banEnd = new DateTime("3333/03/03"); }
	$banEndSQL = SQLManager::ToSQLDate($banEnd);
	ErrorLog::AddError("arbys", $banEndSQL);
	$sql->Query("UPDATE bq_users SET iTimesBanned = iTimesBanned + 1, dtBannedUntil = :d WHERE cID = :u", ["d" => $banEndSQL, "u" => $uID]);
	$sql->Query("INSERT INTO bq_notifications (xUser, sTemplate, sToken1, dtPosted, bDismissed) VALUES (:u, 'banned.html', :t, NOW(), 0)", [ "u" => $uID, "t" => $banEnd->format("F j, Y") ]);
}

$page = new BasePage("moderator/reportQueue.html");
if(!isset($page->userInfo["modTier"])) {
	echo json_encode(["status" => false]);
	exit;
}
$modTier = intval($page->userInfo["modTier"]);
if($modTier < 1) {
	echo json_encode(["status" => false]);
	exit;
}

$userId = $_POST["user"];
$whereQuery = "WHERE xUser = :user";
$params = ["user" => $userId];
if(intval($_POST["answer"]) > 0) {
	$whereQuery .= " AND xAnswer = :answer";
	$params["answer"] = $_POST["answer"];
}
if(intval($_POST["question"]) > 0) {
	$whereQuery .= " AND xQuestion = :question";
	$params["question"] = $_POST["question"];
}

$sql = new SQLManager();
$sql->Query("UPDATE bq_users_reports_xref SET bDismissed = 1 $whereQuery", $params);
if($_POST["action"] == "") {
	echo json_encode(["status" => true]);
	exit;
}

$actions = explode("|", $_POST["action"]);
foreach($actions as $action) {
	switch($action) {
		case "DeleteQ": PurgeQuestion($sql, $_POST["question"], $userId); break;
		case "DeleteA": PurgeAnswer($sql, $_POST["answer"], $userId); break;
		case "Tempban": BanUser($sql, $userId, false, $modTier); break;
		case "Permaban": BanUser($sql, $userId, true, $modTier); break;
		case "Purge": PurgeUser($sql, $userId, $modTier); break;
	}
}

echo json_encode(["status" => true]);
?>