<?php

namespace TwitchQuiz\Cmd;

use \TwitchQuiz\Users\LoginController as LoginController;

$GLOBALS['Boot']->loadDBExt("Join");
$GLOBALS['Boot']->loadDBExt("Condition");

use \Frame\Condition as Condition;
use \Frame\Join as Join;
use \Frame\Order                as Order;


$GLOBALS['Boot']->loadModel("TwitchQuizStateModel");
$GLOBALS['Boot']->loadModel("TwitchQuizQuestionDistributionModel");
$GLOBALS['Boot']->loadModel("TwitchQuizQuestionResultModel");
$GLOBALS['Boot']->loadModel("TwitchQuizResultModel");
$GLOBALS['Boot']->loadModel("TwitchQuizCmdModel");
$GLOBALS['Boot']->loadModel("TwitchQuizUsersModel");

use \TwitchQuiz\TwitchQuizStateModel     as StateModel;
use \TwitchQuiz\TwitchQuizResultModel     as ResultModel;
use \TwitchQuiz\TwitchQuizQuestionResultModel     as QuestionResultModel;
use \TwitchQuiz\TwitchQuizQuestionDistributionModel     as DistributionModel;
use \TwitchQuiz\TwitchQuizUsersModel            as UsersModel;
use \TwitchQuiz\TwitchQuizCmdModel              as CmdModel;


class StateController {
    private $DefaultController = false;
    private $DefaultAction = "view";

    public function viewAction() {
	$data = $GLOBALS['POST']->{'channel'};
	$question_result = $GLOBALS['POST']->{'question_result'};
	$quiz_result = $GLOBALS['POST']->{'quiz_result'};

	$cond = new Condition("[c1]", array(
		"[c1]" => [
			[UsersModel::class, UsersModel::FIELD_CHANNEL],
			Condition::COMPARISON_EQUALS,
			[Condition::CONDITION_CONST, $data]
		]
	));

	$join = new Join(new StateModel(), "[j1]", array(
		"[j1]" => [
			[UsersModel::class, UsersModel::FIELD_ID],
			Condition::COMPARISON_EQUALS,
			[StateModel::class, StateModel::FIELD_TWITCH_QUIZ_USERS_ID]
		]
	));

	$user = new UsersModel();
	$user->find($cond, $join);

	$result["status"] = false;
	if ($user->next()) {
		$result["status"] = true;

		$state = $user->joinedModelByClass(StateModel::class);

		$result["state"] = $state->toArray();

		if ($state->getState() == 0) {
			$distribution_cond = new Condition("[c1] AND [c2]", array(
				"[c1]" => [
					[DistributionModel::class, DistributionModel::FIELD_TWITCH_QUIZ_USERS_ID],
					Condition::COMPARISON_EQUALS,
					[Condition::CONDITION_CONST, $state->getTwitchQuizUsersId()]
				],
				"[c2]" => [
					[DistributionModel::class, DistributionModel::FIELD_QUESTION_NR],
					Condition::COMPARISON_EQUALS,
					[Condition::CONDITION_CONST, $state->getParam()]
				]
			));

			$order = new Order(DistributionModel::class, DistributionModel::FIELD_ID, Order::ORDER_DESC);

			$dist = new DistributionModel();
			$dist->find($distribution_cond);
			if ($dist->next()) {
				$result["distribution"] = $dist->toArray();
			}
		} else if ($state->getState() == 1) {
			if ($question_result == false) {
				$param = $state->getParam();

				$question_result_cond = new Condition("[c1]", array(
					"[c1]" => [
						[QuestionResultModel::class, QuestionResultModel::FIELD_QUESTION_NR],
						Condition::COMPARISON_EQUALS,
						[Condition::CONDITION_CONST, $param]
					]
				));

				$order = new Order(QuestionResultModel::class, QuestionResultModel::FIELD_ID, Order::ORDER_DESC);

				$question_result = new QuestionResultModel();
				$question_result->find($question_result_cond);

				if ($question_result->next()) {
					$result["question_result"] = $question_result->toArray();
				}
			}
		} else if ($state->getState() == 2) {
			if ($quiz_result == false) {
				$quiz_result_cond = new Condition("[c1]", array(
					"[c1]" => [
						[ResultModel::class, ResultModel::FIELD_TWITCH_QUIZ_USERS_ID],
                                                Condition::COMPARISON_EQUALS,
                                                [Condition::CONDITION_CONST, $state->getTwitchQuizUsersId()]
					]
				));

				$order = new Order(ResultModel::class, ResultModel::FIELD_ID, Order::ORDER_DESC);

				$quiz = new ResultModel();
				$quiz->find($quiz_result_condition);

				if ($quiz->next()) {
					$result["quiz_result"] = $quiz->toArray();
				}
			}
		}
	}

	exit(json_encode($result, JSON_INVALID_UTF8_SUBSTITUTE));
    }

    public static function update($user_id, $cmd, $param) {
	$state = new StateModel();

	if ($cmd == "ask") {
		/* DELETE DISTS */
		$cond = new Condition("[c1] AND [c2]", array(
                        "[c1]" => [
                                [DistributionModel::class, DistributionModel::FIELD_TWITCH_QUIZ_USERS_ID],
                                Condition::COMPARISON_EQUALS,
                                [Condition::CONDITION_CONST, $user_id]
                        ],
                        "[c2]" => [
                                [DistributionModel::class, DistributionModel::FIELD_QUESTION_NR],
                                Condition::COMPARISON_EQUALS,
                                [Condition::CONDITION_CONST, $param]
                        ]
                ));

                $d_model_d = new DistributionModel();
                $d_model_d->find($cond);
                while ($d_model_d->next()) $d_model_d->delete();

		$state->setTwitchQuizUsersId($user_id);
		$state->setState(0);
		$state->setParam($param);
		$state->insert();
	} else if ($cmd == "end") {

		$question_nr = explode("_", $param)[0];

		/* DELETE QUESTION RESULTS */
		$cond = new Condition("[c1] AND [c2]", array(
                        "[c1]" => [
                                [QuestionResultModel::class, QuestionResultModel::FIELD_TWITCH_QUIZ_USERS_ID],
                                Condition::COMPARISON_EQUALS,
                                [Condition::CONDITION_CONST, $user_id]
                        ],
                        "[c2]" => [
                                [QuestionResultModel::class, QuestionResultModel::FIELD_QUESTION_NR],
                                Condition::COMPARISON_EQUALS,
                                [Condition::CONDITION_CONST, $question_nr]
                        ]
                ));

                $q_model_d = new QuestionResultModel();
                $q_model_d->find($cond);
                while ($q_model_d->next()) $q_model_d->delete();

		$state->setTwitchQuizUsersId($user_id);
                $state->setState(1);
                $state->setParam($question_nr);
                $state->insert();
	} else if ($cmd == "total") {
		/* DELETE RESULTS */
			$cond = new Condition("[c1]", array(
                        "[c1]" => [
                                [ResultModel::class, ResultModel::FIELD_TWITCH_QUIZ_USERS_ID],
                                Condition::COMPARISON_EQUALS,
                                [Condition::CONDITION_CONST, $user_id]
                        ]
                ));

                $q_model_d = new ResultModel();
                $q_model_d->find($cond);
                while ($q_model_d->next()) $q_model_d->delete();

		$state->setTwitchQuizUsersId($user_id);
                $state->setState(2);
		$state->setParam(0);
                $state->insert();
	} else if ($cmd == "clear") {
		/* DELETE ALL DISTS */
		$cond = new Condition("[c1]", array(
                        "[c1]" => [
                                [DistributionModel::class, DistributionModel::FIELD_TWITCH_QUIZ_USERS_ID],
                                Condition::COMPARISON_EQUALS,
                                [Condition::CONDITION_CONST, $user_id]
                        ]
                ));

                $d_model_d = new DistributionModel();
                $d_model_d->find($cond);
                while ($d_model_d->next()) $d_model_d->delete();

		/* DELETE ALL QUESTION RESULTS */
                $cond = new Condition("[c1]", array(
                        "[c1]" => [
                                [QuestionResultModel::class, QuestionResultModel::FIELD_TWITCH_QUIZ_USERS_ID],
                                Condition::COMPARISON_EQUALS,
                                [Condition::CONDITION_CONST, $user_id]
                        ]
                ));

                $q_model_d = new QuestionResultModel();
                $q_model_d->find($cond);
                while ($q_model_d->next()) $q_model_d->delete();

		/* DELETE RESULTS */
                        $cond = new Condition("[c1]", array(
                        "[c1]" => [
                                [ResultModel::class, ResultModel::FIELD_TWITCH_QUIZ_USERS_ID],
                                Condition::COMPARISON_EQUALS,
                                [Condition::CONDITION_CONST, $user_id]
                        ]
                ));

                $q_model_d = new ResultModel();
                $q_model_d->find($cond);
                while ($q_model_d->next()) $q_model_d->delete();

		$state->setTwitchQuizUsersId($user_id);
                $state->setState(-1);
                $state->setParam(0);
                $state->insert();
	}

	/* DELETE OLD STATES */
	$cond = new Condition("[c1] AND [c2]", array(
                        "[c1]" => [
                                [StateModel::class, StateModel::FIELD_TWITCH_QUIZ_USERS_ID],
                                Condition::COMPARISON_EQUALS,
                                [Condition::CONDITION_CONST, $user_id]
                        ],
                        "[c2]" => [
                                [StateModel::class, StateModel::FIELD_ID],
                                Condition::COMPARISON_NOT_EQUAL,
                                [Condition::CONDITION_CONST, $state->getId()]
                        ]
                ));

        $d_model_d = new StateModel();
        $d_model_d->find($cond);
        while ($d_model_d->next()) $d_model_d->delete();
    }
}
