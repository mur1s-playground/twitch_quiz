<?php

namespace TwitchQuiz\Quiz;

use \TwitchQuiz\Users\LoginController as LoginController;

$GLOBALS['Boot']->loadDBExt("Join");
$GLOBALS['Boot']->loadDBExt("Fields");
$GLOBALS['Boot']->loadDBExt("DBFunction");
$GLOBALS['Boot']->loadDBExt("DBFunctionExpression");

use \Frame\Order		as Order;
use \Frame\Join                 as Join;
use \Frame\Condition            as Condition;
use \Frame\Fields               as Fields;
use \Frame\DBFunction           as DBFunction;
use \Frame\DBFunctionExpression as DBFunctionExpression;

$GLOBALS['Boot']->loadModel("TwitchQuizModel");

use \TwitchQuiz\TwitchQuizModel 		as QuizModel;

class IndexController {
    private $DefaultController = true;
    private $DefaultAction = "get";

    public function viewAction() {
	$data = $GLOBALS['POST']->{'question_id'};

	$condition = new Condition("[c1]", array(
		"[c1]" => [
			[QuizModel::class, QuizModel::FIELD_ID],
			Condition::COMPARISON_EQUALS,
			[Condition::CONDITION_CONST, $data]
		]
	));

	$quiz = new QuizModel();
	$quiz->find($condition);

	$result["status"] = false;
	if ($quiz->next()) {
		$result["status"] = true;
		$result["question"] = $quiz->toArray();
	}

	exit(json_encode($result, JSON_INVALID_UTF8_SUBSTITUTE));
    }

    public function getAction() {
	$user = LoginController::requireAuth();

	$result = array();
        $result["status"] = true;

	$condition = new Condition("[c1]", array(
		"[c1]" => [
			[QuizModel::class, QuizModel::FIELD_TWITCH_QUIZ_USERS_ID],
			Condition::COMPARISON_EQUALS,
			[Condition::CONDITION_CONST, $user->getId()]
		]
	));
	$order = new Order(QuizModel::class, QuizModel::FIELD_QUESTION_NR, Order::ORDER_ASC);

	$quiz = new QuizModel();
	$quiz->find($condition, null, $order);

	$result["quiz"] = new \stdClass();
	while ($quiz->next()) {
		$result["quiz"]->{$quiz->getId()} = $quiz->toArray();
	}

	exit(json_encode($result, JSON_INVALID_UTF8_SUBSTITUTE));
    }

    public function insertAction() {
	$user = LoginController::requireAuth();

	$data = $GLOBALS['POST']->{'quiz_item'};

	/* COUNT CURRENT */
	$condition = new Condition("[c1]", array(
                "[c1]" => [
                        [QuizModel::class, QuizModel::FIELD_TWITCH_QUIZ_USERS_ID],
                        Condition::COMPARISON_EQUALS,
                        [Condition::CONDITION_CONST, $user->getId()]
                ]
        ));

        $count_expr = new DBFunctionExpression("[e0]", array(
                "[e0]" => [QuizModel::class, QuizModel::FIELD_ID]
        ));

        $fields = new Fields(array());
        $fields->addFunctionField("Count", DBFunction::FUNCTION_COUNT, $count_expr);

        $quiz = new QuizModel();
        $quiz->find($condition, null, null, null, $fields);

	$count_val = 30;
        if ($quiz->next()) {
                $count_val = $quiz->DBFunctionResult("Count");
        }
	/*----*/

	$result["status"] = true;
	if ($count_val < 30) {
		$quiz = new QuizModel();
		$quiz->setTwitchQuizUsersId($user->getId());
		$quiz->setQuestionNr($data->{QuizModel::FIELD_QUESTION_NR});
		$quiz->setQuestion($data->{QuizModel::FIELD_QUESTION});
		$quiz->setAnswerA($data->{QuizModel::FIELD_ANSWER_A});
		$quiz->setAnswerB($data->{QuizModel::FIELD_ANSWER_B});
		$quiz->setAnswerC($data->{QuizModel::FIELD_ANSWER_C});
		$quiz->setAnswerD($data->{QuizModel::FIELD_ANSWER_D});
		$quiz->setCorrectId($data->{QuizModel::FIELD_CORRECT_ID});
		$quiz->insert();
		$result["quiz_item"] = $quiz->toArray();
	} else {
		$result["status"] = false;
		$result["error"] = "question limit reached";
	}

	exit(json_encode($result, JSON_INVALID_UTF8_SUBSTITUTE));
    }

	public function deleteAction() {
	        $user = LoginController::requireAuth();

        	$data = $GLOBALS['POST']->{'quiz_question_id'};


		$condition_expr = "[c1]";
		$condition_arr = array(
			"[c1]" => [
                                        [QuizModel::class, QuizModel::FIELD_TWITCH_QUIZ_USERS_ID],
                                        Condition::COMPARISON_EQUALS,
                                        [Condition::CONDITION_CONST, $user->getId()]
                                ]
		);
		if ($data > -1) {
			$condition_expr .= " AND [c2]";
			$condition_arr["[c2]"] = [
				[QuizModel::class, QuizModel::FIELD_ID],
                                Condition::COMPARISON_EQUALS,
                                [Condition::CONDITION_CONST, $data]
			];
		}

		$condition = new Condition($condition_expr, $condition_arr);

		$result["status"] = false;

		$quiz = new QuizModel();
		$quiz->find($condition);
		while ($quiz->next()) {
			$quiz->delete();
			$result["status"] = true;
		}

		exit(json_encode($result, JSON_INVALID_UTF8_SUBSTITUTE));
	}
}
