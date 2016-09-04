<?php
session_start();
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/base/Validation.php";
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/common/scoreUpdating.php";
$userId = ValidateAndReturnUserId(true);
$sql = new SQLManager();

$questionID = $sql->QueryCount("SELECT q.cID FROM bq_questions q INNER JOIN bq_answers a ON q.xAnswer = a.cID WHERE q.cID64 = :q AND a.dtClosed IS NULL", ["q" => $_POST["id"]]);
if($questionID == 0) { ReturnError("Please select a valid question!"); }

$isYourQuestion = $sql->QueryExists("SELECT COUNT(*) FROM bq_questions WHERE cID = :q AND xUser = :u", ["q" => $questionID, "u" => $userId]);
if($isYourQuestion) { ReturnError("You can't like your own question!"); }

$alreadyLiked = $sql->QueryExists("SELECT COUNT(*) FROM bq_questions_likes_xref WHERE xQuestion = :q AND xUser = :u", ["q" => $questionID, "u" => $userId]);
if($alreadyLiked) { ReturnError("You've already liked this question!"); }

$sql->Query("INSERT INTO bq_questions_likes_xref (xQuestion, xUser) VALUES (:q, :u)", ["q" => $questionID, "u" => $userId]);
$sql->Query("UPDATE bq_questions SET iScore = iScore + 1 WHERE cID = :q", ["q" => $questionID]);

$yourInfo = $sql->QueryRow("SELECT cID64, sDisplayName FROM bq_users WHERE cID = :id", ["id" => $userId]);
$questionData = $sql->QueryRow("SELECT a.cID64 AS aID64, q.sQuestion, u.sDisplayName, u.cID AS uID, u.cID64 AS uID64 FROM bq_questions q INNER JOIN bq_answers a ON q.xAnswer = a.cID INNER JOIN bq_users u ON q.xUser = u.cID WHERE q.cID = :questionID", ["questionID" => $questionID]);
$query = <<<EOT
	INSERT INTO bq_notifications (xUser, sTemplate, sIconClass, sToken1, sToken2, sToken3, sToken4, dtPosted, bDismissed) VALUES 
		(:you, 'youLikedQuestion.html', 'glyphicon-question-sign', :qURL, :q, :theirURL, :theirName, NOW(), 1),
		(:them, 'likedQuestion.html', 'glyphicon-question-sign', :qURL, :q, :yourURL, :yourName, NOW(), 0)
EOT;
$yourID = $yourInfo["cID64"];
$theirCID = $answerData["uID"];
$theirID = $answerData["uID64"];
$sql->Query($query, [
	"you" => $userId, 
	"them" => $theirCID, 
	"qURL" => "http://hauntedbees.com/bq/answers/".$questionData["aID64"]."#q".$_POST["id"], 
	"q" => $questionData["sQuestion"], 
	"theirURL" => "http://hauntedbees.com/bq/users/$theirID", 
	"theirName" => $questionData["sDisplayName"],
	"yourURL" => "http://hauntedbees.com/bq/users/$yourID", 
	"yourName" => $yourName
]);
IncrementScore($sql, $questionData["uID"], 1);

echo json_encode(["status" => true, "count" => $sql->QueryVal("SELECT iScore FROM bq_questions WHERE cID = :q", ["q" => $questionID])]);
?>