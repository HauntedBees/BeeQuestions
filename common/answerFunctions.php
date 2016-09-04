<?php
function GetTagAnswers($basePage, $sql, $tag, $filter, $offset) {
	$orderBy = "ORDER BY a.dtStatusChanged DESC";
	$additionalWhere = " AND a.iStatus = 0";
	switch($filter) {
		case "popular":
			$orderBy = "ORDER BY a.iScore DESC";
			break;
		case "recent":
			$orderBy = "ORDER BY a.dtStatusChanged DESC";
			break;
		case "needslove":
			$orderBy = "ORDER BY a.iScore ASC";
			break;
		case "invoting": 
			$additionalWhere = " AND a.iStatus IN (1, 2)";
			break;
		case "closed":
			$additionalWhere = " AND a.iStatus = 3";
			break;
	}
	$pagelen = PAGESIZE;
	$query = <<<EOT
SELECT a.cID AS answerId, a.cID64, a.sAnswer AS answertext, u.sDisplayName AS username, u.cID64 AS uID64, a.dtOpened AS postdate, COUNT(DISTINCT q.cID) AS questions, 
	(SELECT GROUP_CONCAT(DISTINCT t2.sTag) FROM bq_tags t2 INNER JOIN bq_answers_tags_xref x2 ON t2.cID = x2.xTag INNER JOIN bq_answers a2 ON x2.xAnswer = a2.cID WHERE a2.cID = a.cID) AS tagName
FROM bq_answers a
	INNER JOIN bq_users u ON a.xUser = u.cID
	INNER JOIN bq_answers_tags_xref x ON a.cID = x.xAnswer
	INNER JOIN bq_tags t ON x.xTag = t.cID
	LEFT JOIN bq_questions q ON q.xAnswer = a.cID
WHERE t.sTag = :tag AND a.bDeleted = 0 $additionalWhere
GROUP BY a.cID
$orderBy
LIMIT $offset, $pagelen
EOT;
	return $basePage->GetAnswersHTML($sql->Query($query, ["tag" => $tag]), $offset, "tags");
}

function GetFrontPageAnswers($basePage, $sql, $filter, $offset) {
	$orderBy = "ORDER BY changed DESC";
	$additionalWhere = "WHERE status = 0";
	switch($filter) {
		case "popular":
			$orderBy = "ORDER BY score DESC";
			break;
		case "recent":
			$orderBy = "ORDER BY changed DESC";
			break;
		case "needslove":
			$orderBy = "ORDER BY score ASC";
			break;
		case "invoting":
			$additionalWhere = "WHERE status IN (1, 2)";
			break;
		case "closed":
			$additionalWhere = "WHERE status = 3";
			break;
	}
	$pagelen = PAGESIZE;
	$query = <<<EOT
SELECT answerId, cID64, answertext, username, uID64, postdate, tagName, questions
FROM FrontPageAnswers
$additionalWhere
$orderBy
LIMIT $offset, $pagelen
EOT;
	return $basePage->GetAnswersHTML($sql->Query($query), $offset, "frontpage");
}
?>