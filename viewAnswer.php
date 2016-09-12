<?php
session_start();
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/base/BasePage.php";
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/common/questionFunctions.php";
$page = new BasePage("answers/answer.html");
$query = <<<EOT
SELECT a.cID, a.sAnswer, u.sDisplayName, u.cID AS userId, u.cID64 AS uID64, a.dtOpened, a.iViews, a.iScore, 
	GROUP_CONCAT(DISTINCT t.sTag) AS tags, a.bDeleted, a.iStatus, a.dtClosed, a.dtStatusChanged
FROM bq_answers a
	INNER JOIN bq_users u ON a.xUser = u.cID
	LEFT JOIN bq_answers_tags_xref x ON a.cID = x.xAnswer
	LEFT JOIN bq_tags t ON x.xTag = t.cID
WHERE a.cID64 = :id
GROUP BY a.cID
EOT;
$answerRow = $page->sql->QueryRow($query, ["id" => $_GET["answer"]]);
if($answerRow == null) { $page->ReturnError("6903"); }
if(intval($answerRow["bDeleted"]) > 0) { $page->ReturnError("6904"); }

$answerId = $answerRow["cID"];
$page->sql->Query("UPDATE bq_answers SET iViews = iViews + 1 WHERE cID = :id", ["id" => $answerId]);

$qtemplate = "questions/question.html";
$iStatus = intval($answerRow["iStatus"]);
switch($iStatus) {
	case 3:
		$page->ChangeTemplate("answers/answer_closed.html");
		$qtemplate = "questions/question_closed.html";
		break;
	case 2:
		$page->ChangeTemplate("answers/answer_voting.html");
		break;
	case 1:
		if($answerRow["userId"] == $page->userInfo["id"]) {
			$page->ChangeTemplate("answers/answer_chooser.html");
			$qtemplate = "questions/question_chooser.html";
		} else {
			$page->ChangeTemplate("answers/answer_choosing.html");
		}
		break;
}

$postDate = new DateTime($answerRow["dtOpened"]);
$closeDate = new DateTime($answerRow["dtClosed"]);

$questionsHTML = "";
if($iStatus == 3) {
	$query = <<<EOT
SELECT q.cID, q.cID64, q.sQuestion, q.dtPosted, q.iScore, u.cID64 AS uID64, u.sDisplayName
FROM bq_questions q
	INNER JOIN bq_users u ON q.xUser = u.cID
	INNER JOIN bq_answers a ON q.xAnswer = a.cID
WHERE q.xAnswer = :id AND a.xBestQuestion = q.cID
EOT;
	$bestQuestion = $page->sql->QueryRow($query, ["id" => $answerId]);
	$questionsHTML = (new Template("questions/bestQuestion.html"))->GetLoopedContent(GetQuestionRow($bestQuestion, ["basePage" => $page]));
}
$query = <<<EOT
SELECT q.cID, q.cID64, q.sQuestion, q.dtPosted, q.iScore, u.cID64 AS uID64, u.sDisplayName
FROM bq_questions q
	INNER JOIN bq_users u ON q.xUser = u.cID
	INNER JOIN bq_answers a ON q.xAnswer = a.cID
WHERE q.xAnswer = :id AND q.bDeleted = 0 AND IFNULL(a.xBestQuestion, 0) <> q.cID
ORDER BY q.iScore DESC
EOT;
$questions = $page->sql->Query($query, ["id" => $answerId]);
$questionsHTML .= (new Template($qtemplate))->GetPDOFetchAssocContent($questions, GetQuestionRow, [ "basePage" => $page ]);
if($questionsHTML == "") { $questionsHTML = (new Template("questions/noQuestions.html"))->GetContent(); }

$tagArray = explode(",", $answerRow["tags"]);
$tagLabels = [];
$params = ["a" => $answerId];
for($i = 0; $i < count($tagArray); $i++) {
	$tagLabels[] = ":tag$i";
	$params["tag$i"] = $tagArray[$i];
}
$tagInner = implode(",", $tagLabels);
$query = <<<EOT
SELECT a.cID64, a.sAnswer
FROM bq_answers a
	INNER JOIN bq_answers_tags_xref x ON a.cID = x.xAnswer
	INNER JOIN bq_tags t ON x.xTag = t.cID
WHERE t.sTag IN ($tagInner) AND x.xAnswer <> :a
ORDER BY a.iViews DESC
LIMIT 0, 5
EOT;
$similarAnswers = $page->sql->Query($query, $params);
$similarHTML = (new Template("answers/similarAnswer.html"))->GetPDOFetchAssocContent($similarAnswers, function($row, $args) {
	return [
		"id" => $row["cID64"], 
		"name" => $row["sAnswer"]
	];
});
if($similarHTML == "") { $similarHTML = (new Template("answers/similarAnswer_none.html"))->GetContent(); }

$voteEndDate = $answerRow["dtStatusChanged"];
$weekFromThen = date_add(new DateTime($voteEndDate), new DateInterval("P7D"));
$voteEnd = str_replace(" ago", "", $page->GetTimeElapsedString($weekFromThen));

$tagsHTML = (new Template("answers/tagEntry.html"))->GetForEachContent($tagArray, function($elem, $args) { return ["name" => $elem]; });
echo $page->GetPage($answerRow["sAnswer"], [
	"aid" => $_GET["answer"],
	"score" => $answerRow["iScore"]. " like".$page->Plural($answerRow["iScore"]), 
	"answer" => $answerRow["sAnswer"],
	"views" => $answerRow["iViews"]. " time".$page->Plural($answerRow["iViews"]), 
	"postdate" => $page->GetTimeElapsedString($postDate), 
	"actualdate" => $page->FormatDate($postDate), 
	"closedate" => $page->GetTimeElapsedString($closeDate), 
	"actualclose" => $page->FormatDate($closeDate), 
	"user" => $answerRow["sDisplayName"], 
	"userId" => $answerRow["uID64"], 
	"voteEnd" => $voteEnd,
	"similaranswers" => $similarHTML, 
	"tags" => $tagsHTML,
	"questions" => $questionsHTML,
	"form" => (new Template($page->isLoggedIn?"questions/questionForm.html":"questions/questionForm_locked.html"))->GetContent()
]);
?>