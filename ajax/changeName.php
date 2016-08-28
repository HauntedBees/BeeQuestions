<?php
session_start();
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/base/Validation.php";
$userId = ValidateAndReturnUserId(true);
$newName = WordFilterAndRemoveHTML(trim($_POST["name"]));
if($newName == "" || strlen($newName) > 20) { ReturnError("Please enter a valid display name (alphanumeric characters only, less than 20 characters)."); }
if(!preg_match("/^[A-Za-z0-9\-_\s]+$/", $newName)) { ReturnError("Please enter a valid display name (alphanumeric characters only, less than 20 characters)."); }

$sql = new SQLManager();
$count = $sql->QueryVal("SELECT COUNT(*) FROM bq_users WHERE sDisplayName = :s AND cID <> :u", ["s" => $newName, "u" => $userId]);
if(intval($count) > 0) { ReturnError("A user with this display name already exists."); }

$sql->Query("UPDATE bq_users SET sDisplayName = :s WHERE cID = :u", ["s" => $newName, "u" => $userId]);
echo json_encode(["status" => true]);
?>