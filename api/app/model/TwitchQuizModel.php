<?php

namespace TwitchQuiz;

require_once "../vendor/reinerlanz/frame/src/DB/DBTable.php";

class TwitchQuizModel extends \Frame\DBTable {

	const FIELD_ID = 'Id';
	const FIELD_TWITCH_QUIZ_USERS_ID = 'TwitchQuizUsersId';
	const FIELD_QUESTION_NR = 'QuestionNr';
	const FIELD_QUESTION = 'Question';
	const FIELD_ANSWER_A = 'AnswerA';
	const FIELD_ANSWER_B = 'AnswerB';
	const FIELD_ANSWER_C = 'AnswerC';
	const FIELD_ANSWER_D = 'AnswerD';
	const FIELD_CORRECT_ID = 'CorrectId';

	/* int(11) */
	private $Id;

	/* int(11) */
	private $TwitchQuizUsersId;

	/* int(11) */
	private $QuestionNr;

	/* varchar(255) */
	private $Question;

	/* varchar(255) */
	private $AnswerA;

	/* varchar(255) */
	private $AnswerB;

	/* varchar(255) */
	private $AnswerC;

	/* varchar(255) */
	private $AnswerD;

	/* int(11) */
	private $CorrectId;


	public function __construct($values = null) {
		parent::__construct('twitch_quiz','{"Id":{"Field":"id","Type":"int(11)","Null":"NO","Key":"PRI","Default":null,"Extra":"auto_increment"},"TwitchQuizUsersId":{"Field":"twitch_quiz_users_id","Type":"int(11)","Null":"NO","Key":"","Default":null,"Extra":""},"QuestionNr":{"Field":"question_nr","Type":"int(11)","Null":"NO","Key":"","Default":null,"Extra":""},"Question":{"Field":"question","Type":"varchar(255)","Null":"NO","Key":"","Default":null,"Extra":""},"AnswerA":{"Field":"answer_a","Type":"varchar(255)","Null":"NO","Key":"","Default":null,"Extra":""},"AnswerB":{"Field":"answer_b","Type":"varchar(255)","Null":"NO","Key":"","Default":null,"Extra":""},"AnswerC":{"Field":"answer_c","Type":"varchar(255)","Null":"NO","Key":"","Default":null,"Extra":""},"AnswerD":{"Field":"answer_d","Type":"varchar(255)","Null":"NO","Key":"","Default":null,"Extra":""},"CorrectId":{"Field":"correct_id","Type":"int(11)","Null":"NO","Key":"","Default":null,"Extra":""}}', $values);
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
	/* @return varchar(255) $this->Question */
	public function getQuestion() {
		return $this->Question;
	}
	/* @param varchar(255) $Question */
	public function setQuestion($Question) {
		$this->Question = $Question;
	}
	/* @return varchar(255) $this->AnswerA */
	public function getAnswerA() {
		return $this->AnswerA;
	}
	/* @param varchar(255) $AnswerA */
	public function setAnswerA($AnswerA) {
		$this->AnswerA = $AnswerA;
	}
	/* @return varchar(255) $this->AnswerB */
	public function getAnswerB() {
		return $this->AnswerB;
	}
	/* @param varchar(255) $AnswerB */
	public function setAnswerB($AnswerB) {
		$this->AnswerB = $AnswerB;
	}
	/* @return varchar(255) $this->AnswerC */
	public function getAnswerC() {
		return $this->AnswerC;
	}
	/* @param varchar(255) $AnswerC */
	public function setAnswerC($AnswerC) {
		$this->AnswerC = $AnswerC;
	}
	/* @return varchar(255) $this->AnswerD */
	public function getAnswerD() {
		return $this->AnswerD;
	}
	/* @param varchar(255) $AnswerD */
	public function setAnswerD($AnswerD) {
		$this->AnswerD = $AnswerD;
	}
	/* @return int(11) $this->CorrectId */
	public function getCorrectId() {
		return $this->CorrectId;
	}
	/* @param int(11) $CorrectId */
	public function setCorrectId($CorrectId) {
		$this->CorrectId = $CorrectId;
	}

}