<?php
function IncrementScore($sql, $userId, $amount) {
	$OldScoreAndLevel = $sql->QueryRow("SELECT iScore, iLevel FROM bq_users WHERE cID = :user", ["user" => $userId]);
	$oldScore = intval($OldScoreAndLevel["iScore"]);
	$newScore = $oldScore + $amount;
	$oldLevel = intval($OldScoreAndLevel["iLevel"]);
	$newLevel = $sql->QueryVal("SELECT MAX(iLevel) FROM bq_levels WHERE :newScore >= iScoreRequired", ["newScore" => $newScore]);
	$sql->Query("UPDATE bq_users SET iScore = :newScore, iLevel = :newLevel WHERE cID = :user", ["user" => $userId, "newScore" => $newScore, "newLevel" => $newLevel]);
	return ["pchange" => $amount, "lchange" => ($newLevel - $oldLevel), "level" => $newLevel];
}
?>