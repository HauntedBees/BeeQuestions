<?php
session_start();
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/base/BasePage.php";
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/common/answerFunctions.php";
$page = new BasePage("users/user.html");
echo GetTagAnswers($page, new SQLManager(), $_POST["tag"], $_POST["filter"], $_POST["offset"]);
?>