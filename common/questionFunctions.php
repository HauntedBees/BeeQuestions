<?php
function GetQuestionRow($row, $args) {
	return [
		"questionId" => Base64::to64($row["hexID"]), 
		"question" => $row["sQuestion"], 
		"userId" => Base64::to64($row["uHexId"]), 
		"user" => $row["sDisplayName"], 
		"date" => $args["basePage"]->GetTimeElapsedString(new DateTime($row["dtPosted"])), 
		"score" => $row["iScore"]. " like".$args["basePage"]->Plural($row["iScore"])
	];
}
?>