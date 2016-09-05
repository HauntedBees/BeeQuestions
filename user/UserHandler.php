<?php
session_start();
require_once $_SERVER["DOCUMENT_ROOT"]."/bq/SqlManager/SqlManager.php";
class UserHandler {
	private $sql;
	function __construct() { $this->sql = new SQLManager(); }
	public function CreateOrUpdateFacebookUser($fbid, $name) {
		$userId = $this->sql->QueryVal("SELECT cID FROM bq_users WHERE iFBID = :id", ["id" => $fbid]);
		if($userId == null) {
			$this->sql->Query("INSERT INTO bq_users (bnID, iFBID, sName, sDisplayName, dtJoined, dtLastLoad) VALUES (UNHEX(REPLACE(UUID() COLLATE utf8_unicode_ci, '-', '')), :id, :name, :disp, NOW(), NOW())", ["id" => $fbid, "name" => $name, "disp" => $this->GetRandomName()]);
			$userId = $this->sql->GetLastInsertId();
		} else {
			$this->sql->Query("UPDATE bq_users SET dtLastLoad = NOW() WHERE iFBID = :id", ["id" => $fbid]);
		}
		return $userId;
	}
	public function GetFacebookUser($fbid) { return $this->sql->QueryRow("SELECT cID, dtBannedUntil FROM bq_users WHERE iFBID = :id", ["id" => $fbid]); }
	private function GetRandomName() {
		return "Clyde"; // TODO: something
	}
	
	public function CheckSessionAndGetInfo($fb) {
		if(!isset($_SESSION["fbid"])) { return []; }
		try {
			$accessToken = new Facebook\Authentication\AccessToken($_SESSION["fbid"]);
			if($_SESSION["fbid"] != $accessToken->getValue()) {
				unset($_SESSION["fbid"]);
			} else {
				try {
					$response = $fb->get("/me?fields=id,name", $accessToken->getValue());
				} catch(Facebook\Exceptions\FacebookResponseException $e) {
					ErrorLog::AddError("index::FacebookResponseException", $e->getMessage());
					unset($_SESSION["fbid"]);
					return [];
				} catch(Facebook\Exceptions\FacebookSDKException $e) {
					ErrorLog::AddError("index1::FacebookSDKException", $e->getMessage());
					unset($_SESSION["fbid"]);
					return [];
				}
				$user = $response->getGraphUser();
				$userInfo = $this->sql->QueryRow("SELECT cID, HEX(bnID) AS hexId, sDisplayName, iModeratorTier FROM bq_users WHERE iFBID = :id", ["id" => $user["id"]]);
				return [
					"name" => $user["name"],
					"displayName" => $userInfo["sDisplayName"],
					"id" => $userInfo["cID"],
					"hexId" => $userInfo["hexId"], 
					"modTier" => $userInfo["iModeratorTier"]
				];
			}
		} catch(Facebook\Exceptions\FacebookSDKException $e) {
			ErrorLog::AddError("index2::FacebookSDKException", $e->getMessage());
			unset($_SESSION["fbid"]);
		}
		return [];
	}
	public function GetLoginArea($fb, $userInfo) {
		if(isset($_SESSION["fbid"])) {
			return "<span>Logged in as <a href='http://hauntedbees.com/bq/users/".Base64::to64($userInfo["hexId"])."'>".$userInfo["name"]." (".$userInfo["displayName"].")</a></span> <a href='http://hauntedbees.com/bq/logout.php' class='btn btn-success btn-sm'>Log out</a>";
		} else {
			$helper = $fb->getRedirectLoginHelper();
			$loginUrl = $helper->getLoginUrl("http://".$_SERVER["SERVER_NAME"]."/bq/fb-callback.php");
			return "<a href='".htmlspecialchars($loginUrl)."' class='btn btn-success btn-sm'>Log in with Facebook!</a>";
		}
	}
}
?>