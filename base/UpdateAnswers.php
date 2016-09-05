<?php
function UpdateAnswers() {
	$query = <<<EOF
SELECT a.cID, HEX(a.bqID) AS hexId, a.sAnswer, a.xUser, a.iStatus, COUNT(q.cID) AS questions
FROM bq_answers a
	LEFT JOIN bq_questions q ON q.xAnswer = a.cID
WHERE a.dtStatusChanged < DATE_ADD(NOW(), INTERVAL -1 WEEK) AND a.bDeleted = 0 AND a.iStatus < 3
GROUP BY a.cID
EOF;
	$sql = new SQLManager();
	$table = $sql->Query($query);
	
	$moveToAnswererVotingIDs = [];
	$pushBackOneWeekIDs = [];
	$moveToEveryoneVotingIDs = [];
	$moveToCompleteIDs = [];
	
	$notificationQuery = "INSERT INTO bq_notifications (xUser, sTemplate, sIconClass, sToken1, sToken2, sToken3, sToken4, dtPosted, bDismissed) VALUES ";
	$notificationParts = [];
	$notificationArgs = [];
	$notificationCount = 0;
	while($row = $table->fetch(PDO::FETCH_ASSOC)) {
		if($row["questions"] == 0 && $row["iStatus"] == 0) {
			$pushBackOneWeekIDs[] = $row["cID"];
			continue;
		}
		$id64 = Base64::to64($row["hexId"]);
		$template = "";
		if($row["iStatus"] == 0) {
			$moveToAnswererVotingIDs[] = $row["cID"];
			$notificationParts[] = "(:u$notificationCount, 'yourAnswerIsReady.html', 'glyphicon-info-sign', :aURL$notificationCount, :a$notificationCount, '', '', NOW(), 0)";
			$notificationArgs["aURL$notificationCount"] = "viewAnswer.php?answer=$id64";
			$notificationArgs["a$notificationCount"] = $row["sAnswer"];
			$notificationArgs["u$notificationCount"] = $row["xUser"];
			$notificationCount++;
		} else if($row["iStatus"] == 1) {
			$moveToEveryoneVotingIDs[] = $row["cID"];
		} else if($row["iStatus"] == 2) {
			$notificationParts[] = "(:u$notificationCount, 'yourAnswerHasVoted.html', 'glyphicon-info-sign', :aURL$notificationCount, :a$notificationCount, '', '', NOW(), 0)";
			$notificationArgs["aURL$notificationCount"] = "http://hauntedbees.com/bq/answers/$id64";
			$notificationArgs["a$notificationCount"] = $row["sAnswer"];
			$notificationArgs["u$notificationCount"] = $row["xUser"];
			$notificationCount++;
			
			$bestQ = $sql->QueryRow("SELECT cID, HEX(bnID), sQuestion, xUser FROM bq_questions WHERE xAnswer = :a ORDER BY iScore DESC LIMIT 0, 1", ["a" => $row["cID"]]);
			$notificationParts[] = "(:u$notificationCount, 'youreBestQuestion.html', 'glyphicon-question-sign', :aURL$notificationCount, :a$notificationCount, :qURL$notificationCount, :q$notificationCount, NOW(), 0)";
			$notificationArgs["aURL$notificationCount"] = "http://hauntedbees.com/bq/answers/$id64";
			$notificationArgs["a$notificationCount"] = $row["sAnswer"];
			$notificationArgs["qURL$notificationCount"] = "http://hauntedbees.com/bq/answers/$id64#q".Base64::to64($bestQ["bnID"]);
			$notificationArgs["q$notificationCount"] = $bestQ["sQuestion"];
			$notificationArgs["u$notificationCount"] = $bestQ["xUser"];
			$notificationCount++;
			
			IncrementScore($sql, $bestQ["xUser"], 10);
			$moveToCompleteIDs[] = ["aID" => $row["cID"], "qID" => $bestQ["cID"]];
		}
	}
	
	if($notificationCount > 0) {
		$fullNotifQuery = $notificationQuery.implode(", ", $notificationParts);
		$sql->Query($fullNotifQuery, $notificationArgs);
	}
	if(count($pushBackOneWeekIDs) > 0) {
		$backIDs = implode(", ", $pushBackOneWeekIDs);
		$sql->Query("UPDATE bq_answers SET dtStatusChanged = NOW() WHERE cID IN ($backIDs)");
	}
	if(count($moveToAnswererVotingIDs)) {
		$answerIDs = implode(", ", $moveToAnswererVotingIDs);
		$sql->Query("UPDATE bq_answers SET iStatus = 1, dtStatusChanged = NOW() WHERE cID IN ($answerIDs)");
	}
	if(count($moveToEveryoneVotingIDs) > 0) {
		$allAnswerIDs = implode(", ", $moveToEveryoneVotingIDs);
		$sql->Query("UPDATE bq_answers SET iStatus = 2, dtStatusChanged = NOW() WHERE cID IN ($allAnswerIDs)");
	}
	if(count($moveToCompleteIDs) > 0) {
		foreach($moveToCompleteIDs as $pair) {
			$sql->Query("UPDATE bq_answers SET iStatus = 3, dtStatusChanged = NOW(), dtClosed = NOW(), xBestQuestion = :q WHERE cID = :a", 
				["q" => $pair["qID"], "a" => $pair["aID"]]);
		}
	}
}
?>