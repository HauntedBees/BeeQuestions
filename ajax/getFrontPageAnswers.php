<?php
session_start();
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/base/BasePage.php";
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/common/answerFunctions.php";
$page = new BasePage("users/user.html");
echo GetFrontPageAnswers($page, new SQLManager(), $_POST["filter"], $_POST["offset"]);
?>