<?php
session_start();
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/base/BasePage.php";
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/common/userFunctions.php";
$page = new BasePage("users/user.html");
$userId = $page->sql->QueryVal("SELECT cID FROM bq_users WHERE cID64 = :id", ["id" => $_POST["user"]]);
echo GetUserQuestions($page, new SQLManager(), $userId, $_POST["filter"], $_POST["offset"]);
?>