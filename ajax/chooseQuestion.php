<?php
session_start();
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/base/Validation.php";
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/common/scoreUpdating.php";
$userId = ValidateAndReturnUserId(true);

$questionId = $sql->QueryCount("SELECT cID FROM bq_questions WHERE bnID = :q", ["q" => QStoDB($_POST["id"], "Please select a valid question!")]);
if($questionId == 0) { ReturnError("Please select a valid question!"); }

$sql = new SQLManager();
$answerInfo = $sql->QueryRow("SELECT a.cID, HEX(a.bnID) AS hexId FROM bq_questions q INNER JOIN bq_answers a ON q.xAnswer = a.cID WHERE q.cID = :q AND a.iStatus = 1 AND a.xUser = :u", 
	["q" => $questionId, "u" => $userId]);
$answerId = intval($answerInfo["cID"]);
if($answerId == 0) { ReturnError("Please select a valid question!"); }
$answer64 = Base64::to64($answerInfo["hexId"]);

$sql->Query("UPDATE bq_answers SET iStatus = 3, dtClosed = NOW(), xBestQuestion = :q WHERE cID = :a", ["a" => $answerId, "q" => $questionId]);

$questionData = $sql->QueryRow("SELECT q.xAnswer, q.sQuestion, a.sAnswer FROM bq_questions q INNER JOIN bq_answers a ON q.xAnswer = a.cID WHERE q.cID = :questionId", ["questionId" => $questionId]);
$query = <<<EOT
	INSERT INTO bq_notifications (xUser, sTemplate, sIconClass, sToken1, sToken2, sToken3, sToken4, dtPosted, bDismissed) VALUES 
		(:them, 'yourBestQuestion.html', 'glyphicon-question-sign', :aURL, :a, :qURL, :q, NOW(), 0)
EOT;
$sql->Query($query, [
	"them" => $questionData["uID"], 
	"aURL" => "http://hauntedbees.com/bq/answers/$answer64", 
	"a" => $questionData["sAnswer"],
	"qURL" => "http://hauntedbees.com/bq/answers/$answer64#q".$_POST["id"], 
	"q" => $questionData["sQuestion"]
]);
IncrementScore($sql, $userId, 5);
IncrementScore($sql, $questionData["uID"], 10);

echo json_encode(["status" => true]);
?>