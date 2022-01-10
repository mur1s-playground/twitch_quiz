<?php

namespace Frame;

require __DIR__ . "/../Config.php";

class DBImport {
    private $config = null;

    public function __construct($config_path) {
        $this->config = new Config($config_path);
    }

    public function run() {
        $mysql_con = mysqli_connect($this->config->getConfigValue(array("mysql", "host")), $this->config->getConfigValue(array("mysql", "username")), $this->config->getConfigValue(array("mysql", "password")), $this->config->getConfigValue(array("mysql", "database")));

	$backup_cfg = @file_get_contents(__DIR__ . "/dbbackup/config.json");

        $backup_json = null;
        if ($backup_cfg) {
                $backup_json = json_decode($backup_cfg, true);
        }

	$import_cfg = @file_get_contents(__DIR__ . "/dbbackup/config_import.json");
	$import_json = null;
	if ($import_cfg) {
		$import_json = json_decode($import_cfg, true);
	} else {
		$import_json = array("imported" => []);
	}

	$files = scandir(__DIR__ . "/dbbackup/");
	sort($files, SORT_STRING);
	for ($f = 0; $f < count($files); $f++) {
		if ($files[$f] == "." || $files[$f] == ".." || strpos($files[$f], ".json") !== false || strpos($files[$f], ".csv") !== false) {
			continue;
		}
		if (!in_array($files[$f], $import_json["imported"])) {
			$file_contents = file_get_contents(__DIR__ . "/dbbackup/" . $files[$f]);

			$mysql_tables_result = mysqli_query($mysql_con, "SHOW TABLES;");

			while (($mysql_tables_row = mysqli_fetch_array($mysql_tables_result)) != null) {
		            $table_name = $mysql_tables_row[0];

			    $search_str_start = "*{$table_name}\r\nDATA_START\r\n";
			    $search_str_start_pos = strpos($file_contents, $search_str_start);
			    if ($search_str_start_pos !== false) {
				$search_str_end = "\r\nDATA_END\r\n";
				$search_str_end_pos = strpos($file_contents, $search_str_end, $search_str_start_pos);

				$data_range_start = $search_str_start_pos + strlen($search_str_start);
				$data_range_len = $search_str_end_pos - $data_range_start;
				$gz_data = gzuncompress(substr($file_contents, $data_range_start, $data_range_len));

				if ($backup_json != null && isset($backup_json[$table_name]) && $backup_json[$table_name]["track_max_id"] == false) {
		                    $mysql_table_trunc = mysqli_query($mysql_con, "TRUNCATE `{$table_name}`;");
				}

				$rows = explode("\r\n", $gz_data);
				for ($r = 0; $r < count($rows); $r++) {
					$query = "";
					$row = $rows[$r];
					mysqli_query($mysql_con, "INSERT INTO {$table_name} VALUES ({$row});");
				}
			    }
			}

			$import_json["imported"][] = $files[$f];
		}
	}
	file_put_contents(__DIR__ . "/dbbackup/config_import.json", json_encode($import_json, JSON_PRETTY_PRINT));
    }
}

$env = getenv('FRAME_ENVIRONMENT');

if ($env == "development") {
    $cfg = "development";
} else {
    $cfg = "live";
}

$db_model = new DBImport("../../../../../app/config/app.{$cfg}.json");
$db_model->run();
