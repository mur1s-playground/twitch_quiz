<?php

namespace TwitchQuiz;

require_once "../vendor/reinerlanz/frame/src/DB/DBTable.php";

class TwitchQuizQuestionDistributionModel extends \Frame\DBTable {

	const FIELD_ID = 'Id';
	const FIELD_TWITCH_QUIZ_USERS_ID = 'TwitchQuizUsersId';
	const FIELD_QUESTION_NR = 'QuestionNr';
	const FIELD_DATA = 'Data';

	/* int(11) */
	private $Id;

	/* int(11) */
	private $TwitchQuizUsersId;

	/* int(11) */
	private $QuestionNr;

	/* varchar(255) */
	private $Data;


	public function __construct($values = null) {
		parent::__construct('twitch_quiz_question_distribution','{"Id":{"Field":"id","Type":"int(11)","Null":"NO","Key":"PRI","Default":null,"Extra":"auto_increment"},"TwitchQuizUsersId":{"Field":"twitch_quiz_users_id","Type":"int(11)","Null":"NO","Key":"","Default":null,"Extra":""},"QuestionNr":{"Field":"question_nr","Type":"int(11)","Null":"NO","Key":"","Default":null,"Extra":""},"Data":{"Field":"data","Type":"varchar(255)","Null":"NO","Key":"","Default":null,"Extra":""}}', $values);
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
	/* @return int(11) $this->QuestionNr */
	public function getQuestionNr() {
		return $this->QuestionNr;
	}
	/* @param int(11) $QuestionNr */
	public function setQuestionNr($QuestionNr) {
		$this->QuestionNr = $QuestionNr;
	}
	/* @return varchar(255) $this->Data */
	public function getData() {
		return $this->Data;
	}
	/* @param varchar(255) $Data */
	public function setData($Data) {
		$this->Data = $Data;
	}

}