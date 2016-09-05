<?php
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/HTMLFriend/TemplateClass.php";
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/Facebook/autoload.php";
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/user/UserHandler.php";
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/common/commonFunctions.php";
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/common/scoreUpdating.php";
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/base/UpdateAnswers.php";
class BasePage {
	private $uh, $master;
	public $template, $fb, $userInfo, $sql;
	function __construct($template) {
		$c = parse_ini_file($_SERVER["DOCUMENT_ROOT"]."/bq/secure/config.ini", true)["facebook"];
		$this->fb = new Facebook\Facebook([
			"app_id" => $c["app_id"],
			"app_secret" => $c["app_secret"],
			"default_graph_version" => $c["default_graph_version"],
		]);
		$this->uh = new UserHandler();
		$this->sql = new SQLManager();
		$this->master = new Template("master.html");
		$this->template = new Template($template);
		$this->userInfo = $this->uh->CheckSessionAndGetInfo($this->fb);
		$fblogin = $this->uh->GetLoginArea($this->fb, $this->userInfo);
		$this->master->SetKey("fblogin", $fblogin);
		$this->UpdateLogin($this->userInfo["id"]);
		UpdateAnswers();
	}
	function ChangeTemplate($t) { $this->template = new Template($t); }
	function QStoDB($val, $errCode) { // converts querystring value to binary value to search database with
		if(strlen($val) != 21) { $this->ReturnError($errCode); }
		return hex2bin(Base64::toHex($val));
	}
	function ReturnError($code) {
		header("Location: http://hauntedbees.com/bq/index.html?errno=$code");
		exit;
	}
	public function GetPage($keyArr) {
		$this->template->SetKeys($keyArr);
		$this->master->SetKey("content", $this->template->GetContent());
		return $this->master->GetContent();
	}
	private function UpdateLogin($userId) {
		$sql = new SQLManager();
		$alreadySignedIn = intval($sql->QueryVal("SELECT CASE WHEN DATE(dtLastLoad) = DATE(NOW()) THEN 1 ELSE 0 END FROM bq_users WHERE cID = :id", ["id" => $userId]));
		if($alreadySignedIn == 0) {
			$sql->Query("UPDATE bq_users SET dtLastLoad = NOW() WHERE cID = :id",  ["id" => $userId]);
			$sql->Query("INSERT INTO bq_notifications (xUser, sTemplate, sIconClass, dtPosted, bDismissed) VALUES (:id, 'loginBonus.html', 'glyphicon-ok-sign', NOW(), 0)", ["id" => $userId]);
			IncrementScore($sql, $userId, 5);
		}
	}
	
	public function GetAnswerKeys($row, $args) {
		$answersTemplate = $args["answersTemplate"];
		$tagTemplate = $args["tagTemplate"];
		$tags = explode(",", $row["tagName"]);
		$tagsHTML = $tagTemplate->GetForEachContent($tags, function($elem, $args) { return ["name" => $elem]; });
		return [
			"url" => FULLPATH."answers/".Base64::to64($row["hexId"]),
			"answer" => $row["answertext"],
			"questions" => $row["questions"]." question".$this->Plural($row["questions"]),
			"postdate" => $this->GetTimeElapsedString(new DateTime($row["postdate"])),
			"user" => $row["username"],
			"userURL" => FULLPATH."users/".$row["userId"],
			"tags" => $tagsHTML
		];
	}
	public function GetAnswersHTML($table, $offset, $type) {
		$answersTemplate = new Template("answers/answerEntry.html");
		$tagTemplate = new Template("answers/tagEntry.html");
		$count = 0;
		$resultHTML = $answersTemplate->GetPDOFetchAssocContent($table, function($row, $args) { 
			return $this->GetAnswerKeys($row, $args);
		}, [
			"answersTemplate" => $answersTemplate, 
			"tagTemplate" => $tagTemplate
		], $count);
		if($resultHTML == "") { 
			$resultHTML = (new Template("answers/noAnswers.html"))->GetContent();
		} else if($count == PAGESIZE) {
			$resultHTML .= (new Template("general/loadMore.html"))->GetLoopedContent(["offset" => $offset, "type" => $type]);
		}
		return $resultHTML;
	}
	public function GetTopTagsHTML($sql) {
		$popularTagsTemplate = new Template("general/topTag.html");
		$popularTagsTable = $sql->Query("SELECT t.sTag FROM bq_tags t INNER JOIN bq_answers_tags_xref x ON t.cID = x.xTag GROUP BY t.sTag ORDER BY COUNT(t.sTag) DESC LIMIT 0, 5");
		return $popularTagsTemplate->GetPDOFetchAssocContent($popularTagsTable, function($row) { return ["name" => $row["sTag"]]; });
	}
	function GetQAFilterHTML() { return (new Template("general/QAFilter.html"))->GetContent(); }
	
	public function GetTimeElapsedString($then) {
		$now = new DateTime();
		$dt = $now->diff($then);
		if($dt->y > 0) { return $dt->y." year".$this->Plural($dt->y)." ago"; }
		if($dt->m > 0) { return $dt->m." month".$this->Plural($dt->m)." ago"; }
		if($dt->d > 7) { return floor($dt->d / 7)." week".$this->Plural(floor($dt->d / 7))." ago"; }
		if($dt->d > 0) { return $dt->d." day".$this->Plural($dt->d)." ago"; }
		if($dt->h > 0) { return $dt->h." hour".$this->Plural($dt->h)." ago"; }
		if($dt->i > 0) { return $dt->i." minute".$this->Plural($dt->i)." ago"; }
		return "a few moments ago";
	}
	public function Plural($i) { return $i == 1 ? "" : "s"; }
	public function FormatDate($d, $justDate = false) { return date_format($d, "F j, Y".($justDate ? "" : " H:i")); }
}
?>