<?php

namespace TwitchQuiz;

require_once "../vendor/reinerlanz/frame/src/DB/DBTable.php";

class TwitchQuizStateModel extends \Frame\DBTable {

	const FIELD_ID = 'Id';
	const FIELD_TWITCH_QUIZ_USERS_ID = 'TwitchQuizUsersId';
	const FIELD_STATE = 'State';
	const FIELD_PARAM = 'Param';

	/* int(11) */
	private $Id;

	/* int(11) */
	private $TwitchQuizUsersId;

	/* int(11) */
	private $State;

	/* int(11) */
	private $Param;


	public function __construct($values = null) {
		parent::__construct('twitch_quiz_state','{"Id":{"Field":"id","Type":"int(11)","Null":"NO","Key":"PRI","Default":null,"Extra":"auto_increment"},"TwitchQuizUsersId":{"Field":"twitch_quiz_users_id","Type":"int(11)","Null":"NO","Key":"","Default":null,"Extra":""},"State":{"Field":"state","Type":"int(11)","Null":"NO","Key":"","Default":null,"Extra":""},"Param":{"Field":"param","Type":"int(11)","Null":"NO","Key":"","Default":null,"Extra":""}}', $values);
	}

	/* @return int(11) $this->Id */
	public function getId() {
		return $this->Id;
	}
	/* @param int(11) $Id */
	public function setId($Id) {
		$this->Id = $Id;
	}
	/* @return int(11) $this->TwitchQuizUsersId */
	public function getTwitchQuizUsersId() {
		return $this->TwitchQuizUsersId;
	}
	/* @param int(11) $TwitchQuizUsersId */
	public function setTwitchQuizUsersId($TwitchQuizUsersId) {
		$this->TwitchQuizUsersId = $TwitchQuizUsersId;
	}
	/* @return int(11) $this->State */
	public function getState() {
		return $this->State;
	}
	/* @param int(11) $State */
	public function setState($State) {
		$this->State = $State;
	}
	/* @return int(11) $this->Param */
	public function getParam() {
		return $this->Param;
	}
	/* @param int(11) $Param */
	public function setParam($Param) {
		$this->Param = $Param;
	}

}