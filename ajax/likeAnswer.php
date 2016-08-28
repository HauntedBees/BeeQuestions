<?php
session_start();
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/base/Validation.php";
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/common/scoreUpdating.php";
$userId = ValidateAndReturnUserId(true);
$sql = new SQLManager();

$answerID = $sql->QueryCount("SELECT cID FROM bq_answers WHERE bnID = :a AND dtClosed IS NULL", ["a" => QStoDB($_POST["id"], "Please select a valid answer!")]);
if($answerID == 0) { ReturnError("Please select a valid answer!"); }

$isYourAnswer = $sql->QueryExists("SELECT COUNT(*) FROM bq_answers WHERE cID = :a AND xUser = :u", ["a" => $answerID, "u" => $userId]);
if($isYourAnswer) { ReturnError("You can't like your own answer!"); }

$alreadyLiked = $sql->QueryExists("SELECT COUNT(*) FROM bq_answers_likes_xref WHERE xAnswer = :a AND xUser = :u", ["a" => $answerId, "u" => $userId]);
if($alreadyLiked) { ReturnError("You've already liked this answer!"); }

$sql->Query("INSERT INTO bq_answers_likes_xref (xAnswer, xUser) VALUES (:a, :u)", ["a" => $answerId, "u" => $userId]);
$sql->Query("UPDATE bq_answers SET iScore = iScore + 1 WHERE cID = :a", ["a" => $answerId]);

$yourInfo = $sql->QueryRow("SELECT HEX(bnID) AS hexID, sDisplayName FROM bq_users WHERE cID = :id", ["id" => $userId]);
$answerData = $sql->QueryRow("SELECT a.sAnswer, u.sDisplayName, u.cID AS uID, HEX(u.bnID) AS uHexID FROM bq_answers a INNER JOIN bq_users u ON a.xUser = u.cID WHERE a.cID = :answerId", ["answerId" => $answerId]);
$query = <<<EOT
	INSERT INTO bq_notifications (xUser, sTemplate, sToken1, sToken2, sToken3, sToken4, dtPosted, bDismissed) VALUES 
		(:you, 'youLikedAnswer.html', :qURL, :a, :theirURL, :theirName, NOW(), 1),
		(:them, 'likedAnswer.html', :qURL, :a, :yourURL, :yourName, NOW(), 0)
EOT;
$yourID = Base64::to64($yourInfo["hexID"]);
$theirID = Base64::to64($answerData["uHexID"]);
$sql->Query($query, [
	"you" => $yourID,
	"them" => $theirID, 
	"qURL" => "viewAnswer.php?answer=".$_POST["id"], 
	"a" => $answerData["sAnswer"], 
	"theirURL" => "user.php?id=$theirID", 
	"theirName" => $answerData["sDisplayName"],
	"yourURL" => "user.php?id=$yourID", 
	"yourName" => $yourInfo["sDisplayName"]
]);
IncrementScore($sql, $answerData["uID"], 1);

echo json_encode(["status" => true, "count" => $sql->QueryVal("SELECT iScore FROM bq_answers WHERE cID = :a", ["a" => $answerId])]);
?>