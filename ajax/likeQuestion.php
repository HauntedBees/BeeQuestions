<?php
session_start();
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/base/Validation.php";
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/common/scoreUpdating.php";
$userId = ValidateAndReturnUserId(true);
$sql = new SQLManager();

$questionID = $sql->QueryCount("SELECT q.cID FROM bq_questions q INNER JOIN bq_answers a ON q.xAnswer = a.cID WHERE q.bnID = :q AND a.dtClosed IS NULL", ["q" => QStoDB($_POST["id"], "Please select a valid question!")]);
if($questionID == 0) { ReturnError("Please select a valid question!"); }

$isYourQuestion = $sql->QueryExists("SELECT COUNT(*) FROM bq_questions WHERE cID = :q AND xUser = :u", ["q" => $questionID, "u" => $userId]);
if($isYourQuestion) { ReturnError("You can't like your own question!"); }

$alreadyLiked = $sql->QueryExists("SELECT COUNT(*) FROM bq_questions_likes_xref WHERE xQuestion = :q AND xUser = :u", ["q" => $questionID, "u" => $userId]);
if($alreadyLiked) { ReturnError("You've already liked this question!"); }

$sql->Query("INSERT INTO bq_questions_likes_xref (xQuestion, xUser) VALUES (:q, :u)", ["q" => $questionID, "u" => $userId]);
$sql->Query("UPDATE bq_questions SET iScore = iScore + 1 WHERE cID = :q", ["q" => $questionID]);

$yourInfo = $sql->QueryRow("SELECT HEX(bnID) AS hexID, sDisplayName FROM bq_users WHERE cID = :id", ["id" => $userId]);
$questionData = $sql->QueryRow("SELECT HEX(a.bnID) AS aHexID, q.sQuestion, u.sDisplayName, u.cID AS uID, HEX(u.bnID) AS uHexID FROM bq_questions q INNER JOIN bq_answers a ON q.xAnswer = a.cID INNER JOIN bq_users u ON q.xUser = u.cID WHERE q.cID = :questionID", ["questionID" => $questionID]);
$query = <<<EOT
	INSERT INTO bq_notifications (xUser, sTemplate, sIconClass, sToken1, sToken2, sToken3, sToken4, dtPosted, bDismissed) VALUES 
		(:you, 'youLikedQuestion.html', 'glyphicon-question-sign', :qURL, :q, :theirURL, :theirName, NOW(), 1),
		(:them, 'likedQuestion.html', 'glyphicon-question-sign', :qURL, :q, :yourURL, :yourName, NOW(), 0)
EOT;
$yourID = Base64::to64($yourInfo["hexID"]);
$theirCID = $answerData["uID"];
$theirID = Base64::to64($answerData["uHexID"]);
$sql->Query($query, [
	"you" => $userId, 
	"them" => $theirCID, 
	"qURL" => "http://hauntedbees.com/bq/answers/".Base64::to64($questionData["aHexID"])."#q".$_POST["id"], 
	"q" => $questionData["sQuestion"], 
	"theirURL" => "http://hauntedbees.com/bq/users/$theirID", 
	"theirName" => $questionData["sDisplayName"],
	"yourURL" => "http://hauntedbees.com/bq/users/$yourID", 
	"yourName" => $yourName
]);
IncrementScore($sql, $questionData["uID"], 1);

echo json_encode(["status" => true, "count" => $sql->QueryVal("SELECT iScore FROM bq_questions WHERE cID = :q", ["q" => $questionID])]);
?>