<?php
session_start();
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/base/BasePage.php";
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/base/Validation.php";
$userId = ValidateAndReturnUserId(true);

$tag = array_pop(explode(" ", trim($_GET["term"])));
$sql = new SQLManager();
$canAdd = $sql->QueryCount("SELECT iLevel FROM bq_users WHERE cID = :user", ["user" => $userId]) >= 3;
$results = [];
if($tag != "") {
	$tagTbl = $sql->Query("SELECT sTag FROM bq_tags WHERE sTag LIKE :tag ORDER BY sTag LIMIT 0, 5", ["tag" => "$tag%"]);
	while($row = $tagTbl->fetch(PDO::FETCH_ASSOC)) { $results[] = ["id" => $row["sTag"], "label" => $row["sTag"], "value" => $row["sTag"]]; }
}
if($canAdd) {
	$results[] = ["id" => $tag, "label" => "Create tag '$tag.'", "value" => $tag];
} else {
	$results[] = ["id" => "", "label" => "You must be level 3 or higher to add your own tags.", "value" => ""];
}

echo json_encode($results);
?>