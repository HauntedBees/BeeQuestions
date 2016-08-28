<?php
session_start();
unset($_SESSION["fbid"]);
header("Location: http://".$_SERVER["SERVER_NAME"]."/bq/index.php?errno=4204");
?>