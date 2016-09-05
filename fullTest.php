<?php
session_start();
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/base/BasePage.php";
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/common/answerFunctions.php";
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/common/commonFunctions.php";
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/common/userFunctions.php";
$page = new BasePage("moderator/fullTest.html");
if(!isset($page->userInfo["modTier"])) { echo "nope"; exit; }
$modTier = intval($page->userInfo["modTier"]);
if($modTier < 100) { echo "nope"; exit; }

echo $page->GetPage([
	"test1Name" => "Popular 'Bees' Tag",
	"test1" => GetTagAnswers($page, $page->sql, "bees", "popular", 0),
	"test2Name" => "Popular Front Page Answers",
	"test2" => GetFrontPageAnswers($page, $page->sql, "popular", 0),
	"test3Name" => "Random ID",
	"test3" => Base64::GenerateBase64ID(),
	"test4Name" => "Hex 'A71E2FE26C8F11E69AAC549F350D5E4C' to 'FNULUCOf4uqqH5ivdgRuj'",
	"test4" => Base64::to64("A71E2FE26C8F11E69AAC549F350D5E4C"),
	"test5Name" => "My History",
	"test5" => GetUserHistory($page, $page->sql, $page->userInfo["id"], "history", 0),
	"test6Name" => "My Likes",
	"test6" => GetUserHistory($page, $page->sql, $page->userInfo["id"], "likes", 0),
	"test7Name" => "My Answers",
	"test7" => GetUserAnswers($page, $page->sql, $page->userInfo["id"], "popular", 0),
	"test8Name" => "My Questions",
	"test8" => GetUserQuestions($page, $page->sql, $page->userInfo["id"], "popular", 0)
]);
?>