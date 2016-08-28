<?php
session_start();
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/base/Validation.php";
$userId = ValidateAndReturnUserId(true);
$sql = new SQLManager();

$questionID = $sql->QueryCount("SELECT cID FROM bq_questions q INNER JOIN bq_answers a ON q.xAnswer = a.cID WHERE q.bnID = :q AND a.dtClosed IS NULL", ["q" => QStoDB($_POST["id"], "Please select a valid question!")]);
if($questionID == 0) { ReturnError("Please select a valid question!"); }

$isYourQuestion = $sql->QueryExists("SELECT COUNT(*) FROM bq_questions WHERE cID = :q AND xUser = :u", ["q" => $questionID, "u" => $userId]);
if($isYourQuestion) { ReturnError("You can't report your own question!"); }

$count = $sql->QueryVal("SELECT COUNT(*) FROM bq_users_reports_xref WHERE xQuestion = :q AND xReportedBy = :u", ["q" => $questionID, "u" => $userId]);
if(intval($count) > 0) { ReturnError("You have already reported this question. A moderator will review it soon!"); }

$fullInfo = $sql->QueryRow("SELECT xAnswer, xUser FROM bq_questions WHERE cID = :q", ["q" => $questionID]);
$sql->Query("INSERT INTO bq_users_reports_xref (xUser, xAnswer, xQuestion, xReportedBy, dtReported) VALUES (:u, :a, :q, :r, NOW())", [
	"u" => $fullInfo["xUser"],
	"a" => $fullInfo["xAnswer"],
	"q" => $questionID,
	"r" => $userId
]);

echo json_encode(["status" => true]);
?>