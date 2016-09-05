<?php
session_start();
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/base/BasePage.php";
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/common/answerFunctions.php";
$page = new BasePage("general/index.html");
$sql = new SQLManager();
$topHTML = "";
if(isset($_GET["errno"])) {
	$text = "An unknown error has occurred.";
	switch(intval(trim($_GET["errno"]))) {
		case 4200: $text = "Please log in before performing this action!"; break;
		case 4201: $text = "An error occurred. Please log in again to proceed."; break;
		case 4202: $text = "You are currently banned from performing this action."; break;
		case 4203: $text = "An error occurred logging in. Please try again later."; break;
		case 4204: $text = "You have been logged out successfully!"; break;
		case 6901: $text = "We couldn't find that tag!"; break;
		case 6902: $text = "We couldn't find that user!"; break;
		case 6903: $text = "We couldn't find that answer!"; break;
		case 6904: $text = "This answer has been deleted because it violated our Terms of Service!"; break;
	}
	$t = new Template("general/top_error.html");
	$t->SetKey("text", $text);
	$topHTML = $t->GetContent();
} else if(isset($page->userInfo["id"])) {
	$topHTML = (new Template("general/top_signedin.html"))->GetContent();
} else {
	$topHTML = (new Template("general/top_index.html"))->GetContent();
}
echo $page->GetPage([
	"contentid" => "frontpagecontent",
	"QAfilter" => $page->GetQAFilterHTML(),  
	"content" => GetFrontPageAnswers($page, $sql, $_GET["filter"], 0),
	"tags" => $page->GetTopTagsHTML($sql), 
	"top" => $topHTML, 
	"form" => (new Template($page->isLoggedIn?"answers/answerForm.html":"answers/answerForm_locked.html"))->GetContent()
]);
?>