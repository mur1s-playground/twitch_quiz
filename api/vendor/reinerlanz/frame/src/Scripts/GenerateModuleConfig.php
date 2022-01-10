<?php

namespace Frame;

require "../Config.php";

class GenerateModuleConfig {
    private $config = null;

    public function __construct($config_path) {
        $this->config = new Config($config_path);
    }

    public function run() {
        $module_directories = scandir($this->config->getConfigValue(array("modules", "path")));

        $modules_config = array();
        foreach ($module_directories as $module_directory) {
            if ($module_directory == '.' || $module_directory == '..') continue;

            $controller_filenames = scandir($this->config->getConfigValue(array("modules", "path")) . $module_directory);
            $modules_config[$module_directory] = array('controllers' => array());

            foreach ($controller_filenames as $controller_filename) {
                if ($controller_filename == '.' || $controller_filename == '..') continue;
                $controller_name = substr($controller_filename, 0, strlen($controller_filename) - strlen("Controller.php"));
                $controller_config[$module_directory]['controllers'][$controller_name] = array('actions' => array());

                $class_contents = file_get_contents($this->config->getConfigValue(array("modules", "path")) . $module_directory . '/' . $controller_filename);

                preg_match("/private[\s]+\\\$DefaultController[\s]+=[\s]+true[\s]*;/", $class_contents, $default_controller);
                if (sizeof($default_controller) > 0) {
                    $modules_config[$module_directory]['defaultController'] = $controller_name;
                }

                preg_match("/private[\s]+\\\$DefaultAction[\s]+=[\s]+[\"a-zA-z0-9]+;/", $class_contents, $default_action);
                if (sizeof($default_action) > 0) {
                    $default_action = explode('=', $default_action[0])[1];
                    $default_action = rtrim($default_action, ';');
                    $default_action = trim($default_action, '" ');
                    if (strtolower($default_action) !== "null") {
                        $modules_config[$module_directory]['controllers'][$controller_name]['defaultAction'] = $default_action;
                    }
                }

                preg_match_all("/public[\s]+function[\s]+[a-zA-z0-9]+Action[\s]*\(\)/", $class_contents, $function_name_lines);
                foreach ($function_name_lines[0] as $function_name_line) {
                    preg_match("/[a-zA-Z0-9]+Action/", $function_name_line, $function_names);
                    foreach ($function_names as $function_name) {
                        $modules_config[$module_directory]['controllers'][$controller_name]['actions'][] = substr($function_name, 0, strlen($function_name) - strlen("Action"));
                    }
                }
            }
        }
        file_put_contents($this->config->getConfigValue(array("modules", "config")), json_encode($modules_config, JSON_PRETTY_PRINT));
    }
}

$env = getenv('FRAME_ENVIRONMENT');

if ($env == "development") {
    $cfg = "development";
} else {
    $cfg = "live";
}

$db_model = new GenerateModuleConfig("../../../../../app/config/app.{$cfg}.json");
$db_model->run();
