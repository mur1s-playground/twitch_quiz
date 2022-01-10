<?php

namespace Frame;

require __DIR__ . "/../Config.php";

class DBBackup {
    private $config = null;

    public function __construct($config_path) {
        $this->config = new Config($config_path);
    }

    public function run() {
        $mysql_con = mysqli_connect($this->config->getConfigValue(array("mysql", "host")), $this->config->getConfigValue(array("mysql", "username")), $this->config->getConfigValue(array("mysql", "password")), $this->config->getConfigValue(array("mysql", "database")));
        $mysql_tables_result = mysqli_query($mysql_con, "SHOW TABLES;");

	$backup_cfg = @file_get_contents(__DIR__ . "/dbbackup/config.json");

	$backup_json = null;
	if ($backup_cfg) {
		$backup_json = json_decode($backup_cfg, true);
	}

	$date_now = date_create();
	$date_f = $date_now->format("YmdHis");

	$data_g = "";

        while (($mysql_tables_row = mysqli_fetch_array($mysql_tables_result)) != null) {
            $table_name = $mysql_tables_row[0];

	    $mysql_table_desc_result = mysqli_query($mysql_con, "DESCRIBE `{$table_name}`;");
	    $cols = mysqli_num_rows($mysql_table_desc_result);

	    $data = "";

	    $track_max_id = false;
	    $max_id = 0;
	    if (!$backup_cfg || !isset($backup_json[$table_name])) {
		$backup_json[$table_name] = array();
		$backup_json[$table_name]["track_max_id"] = false;
	    } else {
		if (isset($backup_json[$table_name]["dont_backup"])) {
			if ($backup_json[$table_name]["dont_backup"] == true) {
				continue;
			}
		}
		if ($backup_json[$table_name]["track_max_id"]) {
			$track_max_id = true;
			$max_id = $backup_json[$table_name]["max_id"];
		}
	    }

	    $mysql_data_result = mysqli_query($mysql_con, "SELECT * FROM `{$table_name}` WHERE id >= {$max_id}");
	    while (($mysql_data_row = mysqli_fetch_array($mysql_data_result)) != null) {
		if ($track_max_id) $max_id = $mysql_data_row[0];
		for ($c = 0; $c < $cols; $c++) {
			if (is_numeric($mysql_data_row[$c])) {
				$data .= $mysql_data_row[$c];
			} else {
				if (is_null($mysql_data_row[$c])) {
					$data .= "null";
				} else {
					$data .= "'" . mysqli_real_escape_string($mysql_con, $mysql_data_row[$c]) . "'";
				}
			}
			if ($c + 1 < $cols) $data .= ",";
		}
		$data .= "\r\n";
	    }
	    if (strlen($data) > 0) {
		    $data_g .= "*" . $table_name . "\r\nDATA_START\r\n";
		    $data_g .= gzcompress($data) . "\r\nDATA_END\r\n";
		    if ($track_max_id) {
			    $backup_json[$table_name]["max_id"] = $max_id+1;
		    } else {
			 $backup_json[$table_name]["max_id"] = $max_id;
		    }
            } else {
		$backup_json[$table_name]["max_id"] = $max_id;
	    }
	}
	file_put_contents(__DIR__ . "/dbbackup/config.json", json_encode($backup_json, JSON_PRETTY_PRINT));
	file_put_contents(__DIR__ . "/dbbackup/" . $date_f . ".gz", $data_g);
    }
}

$env = getenv('FRAME_ENVIRONMENT');

if ($env == "development") {
    $cfg = "development";
} else {
    $cfg = "live";
}

$db_model = new DBBackup("../../../../../app/config/app.{$cfg}.json");
$db_model->run();
