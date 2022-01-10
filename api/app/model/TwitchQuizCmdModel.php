<?php

namespace TwitchQuiz;

require_once "../vendor/reinerlanz/frame/src/DB/DBTable.php";

class TwitchQuizCmdModel extends \Frame\DBTable {

	const FIELD_ID = 'Id';
	const FIELD_TWITCH_QUIZ_USERS_ID = 'TwitchQuizUsersId';
	const FIELD_CMD = 'Cmd';
	const FIELD_PARAM = 'Param';

	/* int(11) */
	private $Id;

	/* int(11) */
	private $TwitchQuizUsersId;

	/* text */
	private $Cmd;

	/* varchar(255) */
	private $Param;


	public function __construct($values = null) {
		parent::__construct('twitch_quiz_cmd','{"Id":{"Field":"id","Type":"int(11)","Null":"NO","Key":"PRI","Default":null,"Extra":"auto_increment"},"TwitchQuizUsersId":{"Field":"twitch_quiz_users_id","Type":"int(11)","Null":"NO","Key":"","Default":null,"Extra":""},"Cmd":{"Field":"cmd","Type":"text","Null":"NO","Key":"","Default":null,"Extra":""},"Param":{"Field":"param","Type":"varchar(255)","Null":"NO","Key":"","Default":null,"Extra":""}}', $values);
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
	/* @return text $this->Cmd */
	public function getCmd() {
		return $this->Cmd;
	}
	/* @param text $Cmd */
	public function setCmd($Cmd) {
		$this->Cmd = $Cmd;
	}
	/* @return varchar(255) $this->Param */
	public function getParam() {
		return $this->Param;
	}
	/* @param varchar(255) $Param */
	public function setParam($Param) {
		$this->Param = $Param;
	}

}