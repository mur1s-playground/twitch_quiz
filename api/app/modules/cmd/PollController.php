<?php

namespace TwitchQuiz\Cmd;

use \TwitchQuiz\Users\LoginController as LoginController;

$GLOBALS['Boot']->loadDBExt("Join");
$GLOBALS['Boot']->loadDBExt("Condition");

use \Frame\Condition as Condition;
use \Frame\Join as Join;
use \Frame\Order                as Order;

$GLOBALS['Boot']->loadModel("TwitchQuizCmdModel");
$GLOBALS['Boot']->loadModel("TwitchQuizUsersModel");

use \TwitchQuiz\TwitchQuizUsersModel 		as UsersModel;
use \TwitchQuiz\TwitchQuizCmdModel 		as CmdModel;

class PollController {
    private $DefaultController = true;
    private $DefaultAction = "get";

    public function getAction() {
	$user = LoginController::requireAuth();

	if ($user->getIsAdmin() != 1) {
		$result["status"] = false;
		exit(json_encode($result, JSON_INVALID_UTF8_SUBSTITUTE));
	}

	$result = array();
        $result["status"] = true;

	$join = new Join(new UsersModel(), "[j1]", array(
		"[j1]" => [
			[UsersModel::class, UsersModel::FIELD_ID],
			Condition::COMPARISON_EQUALS,
			[CmdModel::class, CmdModel::FIELD_TWITCH_QUIZ_USERS_ID]
		]
	));

	$order = new Order(CmdModel::class, CmdModel::FIELD_ID, Order::ORDER_ASC);

	$cmd = new CmdModel();
	$cmd->find(null, $join, $order);

	$result = "";
	while ($cmd->next()) {
		$result .= implode(";", $cmd->toArray()) . ";" . $cmd->joinedModelByClass(UsersModel::class)->getChannel() . "\n";
		$cmd->delete();
	}

	exit($result);
    }

    public function insertAction() {
	$user = LoginController::requireAuth();

	$data = $GLOBALS['POST']->{'cmd_item'};

	$GLOBALS['Boot']->loadModule("cmd", "State");
        StateController::update($user->getId(), $data->{'cmd'}, $data->{'param'});

	$cmd = new CmdModel();
	$cmd->setTwitchQuizUsersId($user->getId());
	$cmd->setCmd($data->{"cmd"});
	$cmd->setParam($data->{"param"});
	$cmd->insert();

	$result["status"] = true;
	$result["cmd_item"] = $cmd->toArray();

	exit(json_encode($result, JSON_INVALID_UTF8_SUBSTITUTE));
    }
}
