<?php
session_start();
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/base/BasePage.php";
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/base/Validation.php";
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/common/questionFunctions.php";
$userId = ValidateAndReturnUserId(true);

$question = trim($_POST["question"]);
if($question == "" || strlen($question) > 400) { ReturnError("Please enter a valid question (less than 400 characters)."); }

$sql = new SQLManager();
$answerId = $sql->QueryCount("SELECT cID FROM bq_answers WHERE bnID = :a AND dtClosed IS NULL", ["a" => QStoDB($_POST["answer"], "Please select a valid answer!")]);
if($answerId == 0) { ReturnError("Please select a valid answer!"); }

$isOwnAnswer = $sql->QueryExists("SELECT COUNT(*) FROM bq_answers WHERE cID = :id AND xUser = :user", ["id" => $answerId, "user" => $userId]);
if($isOwnAnswer) { ReturnError("You can't question your own answer!"); }

$isDuplicateQuestion = $sql->QueryExists("SELECT COUNT(*) FROM bq_questions WHERE xAnswer = :id AND sQuestion LIKE :question", ["id" => $answerId, "question" => $question]);
if($isDuplicateQuestion) { ReturnError("This answer already has that question!"); }

$sameUser = $sql->QueryCount("SELECT COUNT(*) FROM bq_questions WHERE xAnswer = :id AND xUser = :user", ["id" => $answerId, "user" => $userId]);
if($sameUser > 3) { ReturnError("I think you've questioned this answer enough!"); }

$userLevel = $sql->QueryVal("SELECT iLevel FROM bq_users WHERE cID = :user", ["user" => $userId]);
$allowedQs = $sql->QueryCount("SELECT iQuestionsPerDay FROM bq_levels WHERE iLevel = :level", ["level" => $userLevel]);
$query = <<<EOT
SELECT COUNT(DISTINCT q.cID) AS questionCount
FROM bq_users u
	LEFT JOIN bq_questions q ON q.xUser = u.cID AND DATE_FORMAT(q.dtPosted, '%Y-%m-%d') = CURDATE()
WHERE u.cID = :user
GROUP BY u.cID
EOT;
$postedQs = $sql->QueryCount($query, ["user" => $userId]);
if(($allowedQs - $postedQs) <= 0) { ReturnError("You can't question any more answers today!"); }

$question = WordFilterAndRemoveHTML($question);
$questionId = $sql->InsertAndReturn("INSERT INTO bq_questions (bnID, xAnswer, xUser, sQuestion, dtPosted, iScore) VALUES ($sql_bnID, :answer, :userId, :question, NOW(), 0)", [
	"userId" => $userId, 
	"answer" => $answerId, 
	"question" => $question
]);
if($questionId == null || $questionId <= 0) { ReturnError("An error occurred posting your question! Please try again later!"); }

$questionRow = $sql->QueryRow("SELECT q.cID, HEX(q.bnID) AS hexID, q.sQuestion, q.dtPosted, q.iScore, u.cID AS userId, u.sDisplayName FROM bq_questions q INNER JOIN bq_users u ON q.xUser = u.cID WHERE q.cID = :id", ["id" => $questionId]);
$html = (new Template("questions/question.html"))->GetLoopedContent(GetQuestionRow($questionRow, [ "basePage" => new BasePage("questions/question.html") ]));

$levelData = IncrementScore($sql, $userId, 3);
echo json_encode([
	"status" => true,
	"html" => $html, 
	"pchange" => $levelData["pchange"],
	"lchange" => $levelData["lchange"], 
	"level" => $levelData["level"]
]);
?>