<?php
session_start();
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/base/Validation.php";
$userId = ValidateAndReturnUserId(true);
$sql = new SQLManager();

$answerID = $sql->QueryCount("SELECT cID FROM bq_answers WHERE cID64 = :a AND dtClosed IS NULL", ["a" =>$_POST["id"]]);
if($answerID == 0) { ReturnError("Please select a valid answer!"); }

$isYourAnswer = $sql->QueryExists("SELECT COUNT(*) FROM bq_answers WHERE cID = :a AND xUser = :u", ["a" => $answerId, "u" => $userId]);
if($isYourAnswer) { ReturnError("You can't report your own answer!"); }

$count = $sql->QueryVal("SELECT COUNT(*) FROM bq_users_reports_xref WHERE xQuestion IS NULL AND xAnswer = :a AND xReportedBy = :u", ["a" => $answerId, "u" => $userId]);
if(intval($count) > 0) { ReturnError("You have already reported this answer. A moderator will review it soon!"); }

$fullInfo = $sql->QueryRow("SELECT xUser FROM bq_answers WHERE cID = :a", ["a" => $answerId]);
$sql->Query("INSERT INTO bq_users_reports_xref (xUser, xAnswer, xReportedBy, dtReported) VALUES (:u, :a, :r, NOW())", [
	"u" => $fullInfo["xUser"],
	"a" => $answerId,
	"r" => $userId
]);

echo json_encode(["status" => true]);
?>