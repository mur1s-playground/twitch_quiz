<?php

namespace TwitchQuiz;

require_once "../vendor/reinerlanz/frame/src/DB/DBTable.php";

class TwitchQuizUsersModel extends \Frame\DBTable {

	const FIELD_ID = 'Id';
	const FIELD_NAME = 'Name';
	const FIELD_PASSWORD = 'Password';
	const FIELD_EMAIL = 'Email';
	const FIELD_TOKEN = 'Token';
	const FIELD_BIRTHDATE = 'Birthdate';
	const FIELD_GENDER_ID = 'GenderId';
	const FIELD_IS_ADMIN = 'IsAdmin';
	const FIELD_CHANNEL = 'Channel';

	/* int(11) */
	private $Id;

	/* varchar(45) */
	private $Name;

	/* varchar(255) */
	private $Password;

	/* varchar(255) */
	private $Email;

	/* varchar(255) */
	private $Token;

	/* date */
	private $Birthdate;

	/* int(11) */
	private $GenderId;

	/* tinyint(4) */
	private $IsAdmin;

	/* varchar(255) */
	private $Channel;


	public function __construct($values = null) {
		parent::__construct('twitch_quiz_users','{"Id":{"Field":"id","Type":"int(11)","Null":"NO","Key":"PRI","Default":"0","Extra":""},"Name":{"Field":"name","Type":"varchar(45)","Null":"NO","Key":"","Default":null,"Extra":""},"Password":{"Field":"password","Type":"varchar(255)","Null":"NO","Key":"","Default":null,"Extra":""},"Email":{"Field":"email","Type":"varchar(255)","Null":"NO","Key":"","Default":null,"Extra":""},"Token":{"Field":"token","Type":"varchar(255)","Null":"YES","Key":"","Default":null,"Extra":""},"Birthdate":{"Field":"birthdate","Type":"date","Null":"NO","Key":"","Default":null,"Extra":""},"GenderId":{"Field":"gender_id","Type":"int(11)","Null":"NO","Key":"","Default":null,"Extra":""},"IsAdmin":{"Field":"is_admin","Type":"tinyint(4)","Null":"YES","Key":"","Default":"0","Extra":""},"Channel":{"Field":"channel","Type":"varchar(255)","Null":"NO","Key":"","Default":null,"Extra":""}}', $values);
	}

	/* @return int(11) $this->Id */
	public function getId() {
		return $this->Id;
	}
	/* @param int(11) $Id */
	public function setId($Id) {
		$this->Id = $Id;
	}
	/* @return varchar(45) $this->Name */
	public function getName() {
		return $this->Name;
	}
	/* @param varchar(45) $Name */
	public function setName($Name) {
		$this->Name = $Name;
	}
	/* @return varchar(255) $this->Password */
	public function getPassword() {
		return $this->Password;
	}
	/* @param varchar(255) $Password */
	public function setPassword($Password) {
		$this->Password = $Password;
	}
	/* @return varchar(255) $this->Email */
	public function getEmail() {
		return $this->Email;
	}
	/* @param varchar(255) $Email */
	public function setEmail($Email) {
		$this->Email = $Email;
	}
	/* @return varchar(255) $this->Token */
	public function getToken() {
		return $this->Token;
	}
	/* @param varchar(255) $Token */
	public function setToken($Token) {
		$this->Token = $Token;
	}
	/* @return date $this->Birthdate */
	public function getBirthdate() {
		return $this->Birthdate;
	}
	/* @param date $Birthdate */
	public function setBirthdate($Birthdate) {
		$this->Birthdate = $Birthdate;
	}
	/* @return int(11) $this->GenderId */
	public function getGenderId() {
		return $this->GenderId;
	}
	/* @param int(11) $GenderId */
	public function setGenderId($GenderId) {
		$this->GenderId = $GenderId;
	}
	/* @return tinyint(4) $this->IsAdmin */
	public function getIsAdmin() {
		return $this->IsAdmin;
	}
	/* @param tinyint(4) $IsAdmin */
	public function setIsAdmin($IsAdmin) {
		$this->IsAdmin = $IsAdmin;
	}
	/* @return varchar(255) $this->Channel */
	public function getChannel() {
		return $this->Channel;
	}
	/* @param varchar(255) $Channel */
	public function setChannel($Channel) {
		$this->Channel = $Channel;
	}

}