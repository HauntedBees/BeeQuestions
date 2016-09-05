<?php
session_start();
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/base/BasePage.php";
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/common/answerFunctions.php";
$page = new BasePage("general/index.html");
$sql = new SQLManager();
$top = new Template("general/top_tags.html");
$top->SetKey("tag", $_GET["tag"]);
$tagHTML = GetTagAnswers($page, $sql, $_GET["tag"], $_GET["filter"], 0);
if($tagHTML == "") { $page->ReturnError("6901"); }
echo $page->GetPage([
	"contentid" => "tagcontent",
	"QAfilter" => $page->GetQAFilterHTML(),  
	"content" => $tagHTML,
	"tags" => $page->GetTopTagsHTML($sql), 
	"top" => $top->GetContent(), 
	"form" => (new Template($page->isLoggedIn?"answers/answerForm.html":"answers/answerForm_locked.html"))->GetContent()
]);
?>