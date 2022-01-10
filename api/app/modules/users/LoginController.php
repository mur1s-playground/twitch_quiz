<?php

namespace TwitchQuiz\Users;

$GLOBALS['Boot']->loadDBExt("Condition");

use \Frame\Condition as Condition;


$GLOBALS['Boot']->loadModel("TwitchQuizUsersModel");

use \TwitchQuiz\TwitchQuizUsersModel as UsersModel;


class LoginController {
    private $DefaultController = true;
    private $DefaultAction = "login";

    public static function requireAuth() {
	$user = $GLOBALS['AUTH'];
        $result = array("status" => false);
        if (is_null($user)) {
                $result["error"] = "permission denied";
                exit(json_encode($result, JSON_INVALID_UTF8_SUBSTITUTE));
        }
	return $user;
    }

    public static function validateLogin($login_data) {
	$email = $login_data->{"email"};
	$token = $login_data->{"token"};
	if (!empty($email) && !empty($token)) {
		$condition = new Condition("[c1] AND [c2]", array(
                    "[c1]" =>   [
                                    [UsersModel::class, UsersModel::FIELD_EMAIL],
                                    Condition::COMPARISON_EQUALS,
                                    [Condition::CONDITION_CONST, $email]
                        ],
                    "[c2]" =>   [
                                    [UsersModel::class, UsersModel::FIELD_TOKEN],
                                    Condition::COMPARISON_EQUALS,
                                    [Condition::CONDITION_CONST, $token]
                                ]
                ));

		$user = new UsersModel();
                $user->find($condition);
                if ($user->next()) {
			return $user;
		}
	}
	return null;
    }

    public function loginAction() {
	$data = $GLOBALS['POST'];

	$email = $data->{"email"};
	$password = $data->{"password"};

	$result = array();
	$result["status"] = false;
	if (!empty($email) && !empty($password)) {
		$condition = new Condition("[c1] AND [c2]", array(
	            "[c1]" =>   [
	                            [UsersModel::class, UsersModel::FIELD_EMAIL],
                	            Condition::COMPARISON_EQUALS,
        	                    [Condition::CONDITION_CONST, $email]
                        ],
        	    "[c2]" =>   [
	                            [UsersModel::class, UsersModel::FIELD_PASSWORD],
	                            Condition::COMPARISON_EQUALS,
                        	    [Condition::CONDITION_CONST, hash('sha256', $password)]
                	        ]
        	));

		$user = new UsersModel();
		$user->find($condition);
		if ($user->next()) {
			$token = hash('sha256', random_bytes(256));
			$user->setToken($token);
			$user->save();

			$result["status"] = true;
			$result["login_data"] = new \stdClass();
			$result["login_data"]->{'user_id'} = intval($user->getId());
			$result["login_data"]->{'username'} = $user->getName();
			$result["login_data"]->{'email'} = $user->getEmail();
			$result["login_data"]->{'token'} = $token;

		}
	}

	exit(json_encode($result, JSON_INVALID_UTF8_SUBSTITUTE));
    }

    public function logoutAction() {
	$user = LoginController::requireAuth();

	$user->setToken("");
	$result["status"] = true;

	exit(json_encode($result, JSON_INVALID_UTF8_SUBSTITUTE));
    }

    public function updateAction() {
	$user = LoginController::requireAuth();

	$values = $GLOBALS['POST']->{'allergies'};

	$result["status"] = true;
        exit(json_encode($result, JSON_INVALID_UTF8_SUBSTITUTE));
    }

    public function updatepasswordAction() {
	$user = LoginController::requireAuth();

	$values = $GLOBALS['POST']->{'passwords'};

	$condition = new Condition("[c1] AND [c2]", array(
		"[c1]" =>   [
                                    [UsersModel::class, UsersModel::FIELD_EMAIL],
                                    Condition::COMPARISON_EQUALS,
                                    [Condition::CONDITION_CONST, $user->getEmail()]
                        ],
		"[c2]" =>   [
                                    [UsersModel::class, UsersModel::FIELD_PASSWORD],
                                    Condition::COMPARISON_EQUALS,
                                    [Condition::CONDITION_CONST, hash('sha256', $values->{'current_password'})]
                        ]
	));

	$result = array();
        $result["status"] = false;

	$user_check = new UsersModel();
	$user_check->find($condition);
	if ($user_check->next()) {
		$user->setPassword(hash('sha256', $values->{'password'}));
		$user->save();
		$result["status"] = true;
	} else {
	        $result["error"] = "wrong password";
	}

	exit(json_encode($result, JSON_INVALID_UTF8_SUBSTITUTE));
    }

    public function registerAction() {
	$data = $GLOBALS['POST'];

        $result = array();
        $result["status"] = false;

	$condition = new Condition("[c1]", array(
                    "[c1]" =>   [
                                    [UsersModel::class, UsersModel::FIELD_EMAIL],
                                    Condition::COMPARISON_EQUALS,
                                    [Condition::CONDITION_CONST, $data->{'email'}]
                        ]
		));
	$user_exists = new UsersModel();
	$user_exists->find($condition);
	if ($user_exists->next()) {
		$result["error"] = "User already exists";
	} else {
		$user = new UsersModel();
		$user->setName($data->{"username"});
		$user->setEmail($data->{"email"});
		$user->setPassword(hash('sha256', $data->{"password"}));
		$user->setBirthdate($data->{"birthdate"});
		$user->setGenderId($data->{"gender_id"});
		$user->insert();

		if (!is_null($user->getId())) {
			$result["status"] = true;
			$result["user_id"] = $user->getId();
		} else {
			$result["error"] = "User not created";
		}
	}

	exit(json_encode($result, JSON_INVALID_UTF8_SUBSTITUTE));
    }
}
