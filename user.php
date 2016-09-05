<?php
session_start();
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/base/BasePage.php";
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/common/userFunctions.php";
$page = new BasePage("users/user.html");
$query = <<<EOT
SELECT u.cID, u.sDisplayName, u.dtJoined, u.iLevel, l.sTitle, l.sDesc, u.iScore, l.iScoreRequired AS prevScore, nl.iScoreRequired AS nextScore, 
	COUNT(DISTINCT a.cID) AS answerCount, 
	COUNT(DISTINCT q.cID) AS questionCount,
	u.dtBannedUntil
FROM bq_users u
	INNER JOIN bq_levels l ON u.iLevel = l.iLevel
	LEFT JOIN bq_levels nl ON (u.iLevel + 1) = nl.iLevel 
	LEFT JOIN bq_answers a ON a.xUser = u.cID
	LEFT JOIN bq_questions q ON q.xUser = u.cID
WHERE u.cID64 = :user
	GROUP BY u.cID
EOT;
$userInfo = $page->sql->QueryRow($query, ["user" => $_GET["user"]]);
if($userInfo == null) { $page->ReturnError("6902"); }

$userId = intval($userInfo["cID"]);
$isUser = $userId == $page->userInfo["id"];

$bestQuestionsCount = $page->sql->QueryVal("SELECT COUNT(DISTINCT q.cID) FROM bq_answers a INNER JOIN bq_questions q ON a.xBestQuestion = q.cID WHERE a.xUser = :user", ["user" => $userId]);
$nextScore = intval($userInfo["nextScore"]);
$percentageToNextLevel = 0;
$pointsNeeded = 0;
if($nextScore > 0) {
	$prevScore = intval($userInfo["prevScore"]);
	$currentScore = intval($userInfo["iScore"]);
	$diffRequirements = $nextScore - $prevScore;
	$diffUser = $currentScore - $prevScore;
	$percentageToNextLevel = intval(floor(100 * ($diffUser / $diffRequirements)));
	$pointsNeeded = $nextScore - $currentScore;
}

$banHTML = "";
if($userInfo["dtBannedUntil"] != null) {
	$banDate = new DateTime($userInfo["dtBannedUntil"]);
	if($banDate > new DateTime()) {
		$bt = new Template("users/user_side_banned.html");
		$bt->SetKeys([
			"pronoun" => ($isUser ? "You are" : "This user is"), 
			"date" => ($banDate->format("Y") == "3333" ? "" : " until ".$banDate->format("F j, Y, g:i a"))
		]);
		$banHTML = $bt->GetContent();
	}
}

$sidebar = new Template($isUser ? "users/user_side_self.html" : "users/user_side.html");
$sidebar->SetKeys([
	"uid" => $_GET["user"], 
	"name" => $userInfo["sDisplayName"], 
	"joindate" => $page->FormatDate(new DateTime($userInfo["dtJoined"]), true), 
	"answerCount" => $userInfo["answerCount"], 
	"questionCount" => $userInfo["questionCount"], 
	"bestQuestionCount" => $bestQuestionsCount,
	"levelName" => $userInfo["sTitle"], 
	"levelDesc" => $userInfo["sDesc"], 
	"level" => $userInfo["iLevel"], 
	"points" => $userInfo["iScore"],
	"banSection" => $banHTML,
	"percentage" => $percentageToNextLevel, 
	"pointsNeeded" => $pointsNeeded
]);
if($isUser) {
	$remaining = $page->sql->QueryRow("SELECT iAnswersPerDay, iQuestionsPerDay FROM bq_levels WHERE iLevel = :level", ["level" => $userInfo["iLevel"]]);
	$query = <<<EOT
SELECT COUNT(DISTINCT a.cID) AS answerCount, COUNT(DISTINCT q.cID) AS questionCount
FROM bq_users u
	LEFT JOIN bq_answers a ON a.xUser = u.cID AND DATE_FORMAT(a.dtOpened, '%Y-%m-%d') = CURDATE()
	LEFT JOIN bq_questions q ON q.xUser = u.cID AND DATE_FORMAT(q.dtPosted, '%Y-%m-%d') = CURDATE()
WHERE u.cID = :user
GROUP BY u.cID
EOT;
	$current = $page->sql->QueryRow($query, ["user" => $userId]);
	$sidebar->SetKeys([
		"remainingAnswers" => intval($remaining["iAnswersPerDay"]) - intval($current["answerCount"]),
		"remainingQuestions" => intval($remaining["iQuestionsPerDay"]) - intval($current["questionCount"])
	]);
}
echo $page->GetPage([
	"historyTab" => $isUser ? "active" : "hidden", 
	"answersFirst" => $isUser ? "" : "active", 
	"QAfilter" => $page->GetQAFilterHTML(), 
	"answers" => GetUserAnswers($page, $page->sql, $userId, "popular", 0), 
	"questions" => GetUserQuestions($page, $page->sql, $userId, "popular", 0), 
	"history" => $isUser ? GetUserHistory($page, $page->sql, $userId, "notifications", 0) : "", 
	"userInfo" => $sidebar->GetContent()
]);
?>