<?php

namespace Frame;

require (__DIR__ . "/Boot.php");

use \Frame\Condition as Condition;

use \FooDBar\JobsModel as JobsModel;

class Job extends Boot {
	const JOB_STATUS_RUNNING = "RUNNING";
	const JOB_STATUS_ERROR = "ERROR";
	const JOB_STATUS_FINISHED = "FINISHED";

	private $argv = null;
	private $job_id = null;
	private $job = null;

	public function __construct($config_path, $argv) {
		parent::__construct($config_path);
		$this->argv = $argv;
		$this->job_id = $argv[1];

		$GLOBALS['Boot']->loadDBExt("Condition");

		$GLOBALS['Boot']->loadModel("JobsModel");

		$cond = new Condition("[c1]", array(
			"[c1]" => [
				[JobsModel::class, JobsModel::FIELD_ID],
				Condition::COMPARISON_EQUALS,
				[Condition::CONDITION_CONST, $this->job_id]
			]
		));
		$job = new JobsModel();
		$job->find($cond);

		if ($job->next()) {
			$this->job = $job;
			$this->job->setPid(getmypid());
			$this->setJobStatus(self::JOB_STATUS_RUNNING);
		} else {
			exit();
		}
	}

	public function getParams() {
		$params = $this->job->getParams();
		if (is_null($params)) return null;
		return json_decode($params);
	}

	public function setJobStatus($job_status, $result = null) {
		$date_now = date_create();
                $date_f = $date_now->format("Y-m-d H:i:s");
		if ($job_status == self::JOB_STATUS_RUNNING) {
			$this->job->setDatetimeStart($date_f);
		} else {
			$this->job->setDatetimeEnd($date_f);
			$this->job->setResult(json_encode($result, JSON_INVALID_UTF8_SUBSTITUTE));
		}
		$this->job->setStatus($job_status);
		$this->job->save();
	}
}
