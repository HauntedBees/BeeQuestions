<?php
session_start();
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/base/BasePage.php";
$page = new BasePage("general/about.html");
echo $page->GetPage("About");
?>