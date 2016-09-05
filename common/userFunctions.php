<?php
function GetUserHistory($basePage, $sql, $userId, $filter, $offset) {
	$pageLen = PAGESIZE;
	$isLikes = $filter == "likes" ? "LIKE" : "NOT LIKE";
	$query = <<<EOT
	SELECT sTemplate, sIconClass, sToken1, sToken2, sToken3, sToken4, sToken5, sToken6, dtPosted
	FROM bq_notifications
	WHERE xUser = :user AND sTemplate $isLikes 'youLiked%'
	ORDER BY dtPosted DESC
	LIMIT $offset, $pageLen
EOT;
	$historyTable = $sql->Query($query, ["user" => $userId]);
	$count = 0;
	$resultHTML = (new Template("users/historyEntry.html"))->GetPDOFetchAssocContent($historyTable, function($row, $args) {
		$innerTemplate = new Template("notifications/".$row["sTemplate"]);
		$innerTemplate->SetKeys([
			"token1" => $row["sToken1"], 
			"token2" => $row["sToken2"], 
			"token3" => $row["sToken3"], 
			"token4" => $row["sToken4"], 
			"token5" => $row["sToken5"], 
			"token6" => $row["sToken6"]
		]);
		return [
			"icon" => $row["sIconClass"],
			"content" => $innerTemplate->GetContent(),
			"postdate" => $args["basePage"]->GetTimeElapsedString(new DateTime($row["dtPosted"]))
		];
	}, ["basePage" => $basePage], $count);
	if($resultHTML == "") { 
		$resultHTML = (new Template("answers/noAnswers.html"))->GetContent();
	} else if($count == PAGESIZE) {
		$resultHTML .= (new Template("general/loadMore.html"))->GetLoopedContent(["offset" => $offset, "type" => "userHistory"]);
	}
	return $resultHTML;
}

function GetUserAnswers($basePage, $sql, $userId, $filter, $offset) {
	$orderBy = "ORDER BY a.dtStatusChanged DESC";
	$additionalWhere = " AND a.iStatus = 0";
	switch($filter) {
		case "popular":
			$orderBy = "ORDER BY popScore DESC";
			break;
		case "recent":
			$orderBy = "ORDER BY a.dtStatusChanged DESC";
			break;
		case "needslove":
			$orderBy = "ORDER BY nlScore ASC";
			break;
		case "invoting": 
			$additionalWhere = " AND a.iStatus IN (1, 2)";
			break;
		case "closed":
			$additionalWhere = " AND a.iStatus = 3";
			break;
	}
	$pageLen = PAGESIZE;
	$query = <<<EOT
	SELECT a.cID64, a.sAnswer AS answertext, u.sDisplayName AS username, u.cID64 AS uID64, a.dtOpened AS postdate, COUNT(DISTINCT q.cID) AS questions, 
		(SELECT GROUP_CONCAT(DISTINCT t2.sTag) FROM bq_tags t2 INNER JOIN bq_answers_tags_xref x2 ON t2.cID = x2.xTag INNER JOIN bq_answers a2 ON x2.xAnswer = a2.cID WHERE a2.cID = a.cID) AS tagName,
		COUNT(DISTINCT q.cID) * 3 + a.iScore AS popScore, a.iViews * 0.25 + COUNT(DISTINCT q.cID) * 10 + a.iScore * 5 AS nlScore
	FROM bq_answers a
		INNER JOIN bq_users u ON a.xUser = u.cID
		INNER JOIN bq_answers_tags_xref x ON a.cID = x.xAnswer
		INNER JOIN bq_tags t ON x.xTag = t.cID
		LEFT JOIN bq_questions q ON q.xAnswer = a.cID
	WHERE u.cID = :user AND a.bDeleted = 0 $additionalWhere
	GROUP BY a.cID
	$orderBy
	LIMIT $offset, $pageLen
EOT;
	return $basePage->GetAnswersHTML($sql->Query($query, ["user" => $userId]), $offset, "userAnswers");
}

function GetUserQuestions($basePage, $sql, $userId, $filter, $offset) {
	$orderBy = "ORDER BY q.dtPosted DESC";
	$additionalWhere = " AND a.iStatus = 0";
	switch($filter) {
		case "popular":
			$orderBy = "ORDER BY q.iScore DESC";
			break;
		case "recent":
			$orderBy = "ORDER BY q.dtPosted DESC";
			break;
		case "needslove":
			$orderBy = "ORDER BY nlScore ASC";
			break;
		case "invoting": 
			$additionalWhere = " AND a.iStatus IN (1, 2)";
			break;
		case "closed":
			$additionalWhere = " AND a.iStatus = 3";
			break;
	}
	$pageLen = PAGESIZE;
	$query = <<<EOT
	SELECT q.cID64, q.sQuestion, q.dtPosted, q.iScore, u.cID64 AS uID64, u.sDisplayName, a.cID64 AS aID64, a.sAnswer,
	a.iViews * 0.25 + q.iScore * 5 AS nlScore
	FROM bq_questions q
		INNER JOIN bq_users u ON q.xUser = u.cID
		INNER JOIN bq_answers a ON q.xAnswer = a.cID
	WHERE u.cID = :user AND q.bDeleted = 0 $additionalWhere
	$orderBy
	LIMIT $offset, $pageLen
EOT;
	$questionsTable = $sql->Query($query, ["user" => $userId]);
	$questionTemplate = new Template("questions/questionWithAnswer.html");
	$tagTemplate = new Template("answers/tagEntry.html");
	$questionsHTML = "";
	$count = 0;
	$questionsHTML = $questionTemplate->GetPDOFetchAssocContent($questionsTable, function($row, $args) {
		return [
			"answerId" => $row["aID64"],
			"answer" => $row["sAnswer"], 
			"questionId" => $row["cID64"], 
			"question" => $row["sQuestion"], 
			"userId" => $row["uID64"], 
			"user" => $row["sDisplayName"], 
			"date" => $args["page"]->GetTimeElapsedString(new DateTime($row["dtPosted"])), 
			"score" => $row["iScore"]. " like".$args["page"]->Plural($row["iScore"])
		];
	}, ["page" => $basePage], $count);
	if($questionsHTML == "") { 
		$questionsHTML = (new Template("answers/noAnswers.html"))->GetContent();
	} else if($count == PAGESIZE) {
		$resultHTML .= (new Template("general/loadMore.html"))->GetLoopedContent(["offset" => $offset, "type" => "userQuestions"]);
	}
	return $questionsHTML;
}
?>