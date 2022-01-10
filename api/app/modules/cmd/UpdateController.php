<?php

namespace TwitchQuiz\Cmd;

use \TwitchQuiz\Users\LoginController as LoginController;

$GLOBALS['Boot']->loadDBExt("Join");
$GLOBALS['Boot']->loadDBExt("Condition");

use \Frame\Condition as Condition;
use \Frame\Join as Join;
use \Frame\Order                as Order;

$GLOBALS['Boot']->loadModel("TwitchQuizQuestionDistributionModel");
$GLOBALS['Boot']->loadModel("TwitchQuizQuestionResultModel");
$GLOBALS['Boot']->loadModel("TwitchQuizResultModel");
$GLOBALS['Boot']->loadModel("TwitchQuizCmdModel");
$GLOBALS['Boot']->loadModel("TwitchQuizUsersModel");

use \TwitchQuiz\TwitchQuizResultModel     as ResultModel;
use \TwitchQuiz\TwitchQuizQuestionResultModel     as QuestionResultModel;
use \TwitchQuiz\TwitchQuizQuestionDistributionModel     as DistributionModel;
use \TwitchQuiz\TwitchQuizUsersModel 		as UsersModel;
use \TwitchQuiz\TwitchQuizCmdModel 		as CmdModel;

class UpdateController {
    private $DefaultController = false;
    private $DefaultAction = "distribution";

    public function distributionAction() {
	$user = LoginController::requireAuth();

	if ($user->getIsAdmin() != 1) {
		$result["status"] = false;
		exit(json_encode($result, JSON_INVALID_UTF8_SUBSTITUTE));
	}

	$data = $GLOBALS['POST']->{'data'};

	foreach ($data as $key => $dataset) {
		$user_id = $dataset->{"user_id"};
		$question_nr = $dataset->{"param"};
		$dist_data = $dataset->{"participants"} . ";" . $dataset->{"answer_0"} . ";" . $dataset->{"answer_1"} . ";" . $dataset->{"answer_2"} . ";" . $dataset->{"answer_3"};

		$d_model = new DistributionModel();
		$d_model->setTwitchQuizUsersId($user_id);
		$d_model->setQuestionNr($question_nr);
		$d_model->setData($dist_data);
		$d_model->insert();

		$cond = new Condition("[c1] AND [c2] AND [c3]", array(
			"[c1]" => [
				[DistributionModel::class, DistributionModel::FIELD_TWITCH_QUIZ_USERS_ID],
				Condition::COMPARISON_EQUALS,
				[Condition::CONDITION_CONST, $user_id]
			],
			"[c2]" => [
                                [DistributionModel::class, DistributionModel::FIELD_QUESTION_NR],
                                Condition::COMPARISON_EQUALS,
                                [Condition::CONDITION_CONST, $question_nr]
                        ],
			"[c3]" => [
				[DistributionModel::class, DistributionModel::FIELD_ID],
                                Condition::COMPARISON_NOT_EQUAL,
                                [Condition::CONDITION_CONST, $d_model->getId()]
			]
		));

		$d_model_d = new DistributionModel();
		$d_model_d->find($cond);
		while ($d_model_d->next()) $d_model_d->delete();
	}

	exit("");
    }

public function questionAction() {
        $user = LoginController::requireAuth();

        if ($user->getIsAdmin() != 1) {
                $result["status"] = false;
                exit(json_encode($result, JSON_INVALID_UTF8_SUBSTITUTE));
        }

        $data = $GLOBALS['POST']->{'data'};

	$user_id = $data->{'user_id'};
	$question_nr = $data->{'param'};
	$csv = $data->{'csv'};

	$q_model = new QuestionResultModel();
	$q_model->setTwitchQuizUsersId($user_id);
	$q_model->setQuestionNr($question_nr);
	$q_model->setResult($csv);
	$q_model->insert();

	$cond = new Condition("[c1] AND [c2] AND [c3]", array(
                        "[c1]" => [
                                [QuestionResultModel::class, QuestionResultModel::FIELD_TWITCH_QUIZ_USERS_ID],
                                Condition::COMPARISON_EQUALS,
                                [Condition::CONDITION_CONST, $user_id]
                        ],
                        "[c2]" => [
                                [QuestionResultModel::class, QuestionResultModel::FIELD_QUESTION_NR],
                                Condition::COMPARISON_EQUALS,
                                [Condition::CONDITION_CONST, $question_nr]
                        ],
                        "[c3]" => [
                                [QuestionResultModel::class, QuestionResultModel::FIELD_ID],
                                Condition::COMPARISON_NOT_EQUAL,
                                [Condition::CONDITION_CONST, $q_model->getId()]
                        ]
                ));

                $q_model_d = new QuestionResultModel();
                $q_model_d->find($cond);
                while ($q_model_d->next()) $q_model_d->delete();

        exit("");
    }

public function quizAction() {
        $user = LoginController::requireAuth();

        if ($user->getIsAdmin() != 1) {
                $result["status"] = false;
                exit(json_encode($result, JSON_INVALID_UTF8_SUBSTITUTE));
        }

        $data = $GLOBALS['POST']->{'data'};

        $user_id = $data->{'user_id'};
        $csv = $data->{'csv'};

        $q_model = new ResultModel();
        $q_model->setTwitchQuizUsersId($user_id);
        $q_model->setResult($csv);
        $q_model->insert();

        $cond = new Condition("[c1] AND [c3]", array(
                        "[c1]" => [
                                [ResultModel::class, ResultModel::FIELD_TWITCH_QUIZ_USERS_ID],
                                Condition::COMPARISON_EQUALS,
                                [Condition::CONDITION_CONST, $user_id]
                        ],
                        "[c3]" => [
                                [ResultModel::class, ResultModel::FIELD_ID],
                                Condition::COMPARISON_NOT_EQUAL,
                                [Condition::CONDITION_CONST, $q_model->getId()]
                        ]
                ));

                $q_model_d = new ResultModel();
                $q_model_d->find($cond);
                while ($q_model_d->next()) $q_model_d->delete();

        exit("");
    }

}
