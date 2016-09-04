<?php
function GetQuestionRow($row, $args) {
	return [
		"questionId" => $row["cID64"], 
		"question" => $row["sQuestion"], 
		"userId" => $row["uID64"], 
		"user" => $row["sDisplayName"], 
		"date" => $args["basePage"]->GetTimeElapsedString(new DateTime($row["dtPosted"])), 
		"score" => $row["iScore"]. " like".$args["basePage"]->Plural($row["iScore"])
	];
}
?>