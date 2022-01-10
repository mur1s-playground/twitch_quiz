<?php

namespace Frame;

require "../Config.php";

class GenerateDBModel {
    private $config = null;

    public function __construct($config_path) {
        $this->config = new Config($config_path);
    }

    public function run() {
        $mysql_con = mysqli_connect($this->config->getConfigValue(array("mysql", "host")), $this->config->getConfigValue(array("mysql", "username")), $this->config->getConfigValue(array("mysql", "password")), $this->config->getConfigValue(array("mysql", "database")));
        $mysql_tables_result = mysqli_query($mysql_con, "SHOW TABLES;");

        $classname_prefix = $this->config->getConfigValue(array("dbmodel", "classname_prefix"));
        if (is_null($classname_prefix)) $classname_prefix = "";
        while (($mysql_tables_row = mysqli_fetch_array($mysql_tables_result)) != null) {
            $table_name = $mysql_tables_row[0];
            $mysql_table_desc_result = mysqli_query($mysql_con, "DESCRIBE `{$table_name}`;");

            $class_contents = "<?php\r\n\r\n";
            if (($namespace = $this->config->getConfigValue(array("dbmodel", "namespace"))) != null) {
                $class_contents .= "namespace {$namespace};\r\n\r\n";
            }

            $parent_path = $this->config->getConfigValue(array("dbmodel", "parentpath"));
            $class_contents .= "require_once \"{$parent_path}DBTable.php\";\r\n\r\n";

            $class_name = $this->underscoreToCamelcase($table_name, true);
            $class_contents .= "class {$classname_prefix}{$class_name}Model extends \Frame\DBTable {\r\n\r\n";

            $class_const = "";
            $class_vars = "";
            $class_functions = "";

            $table_fields = array();

            while (($mysql_table_desc_row = mysqli_fetch_assoc($mysql_table_desc_result)) != null) {
                $table_fields[$this->underscoreToCamelcase($mysql_table_desc_row['Field'], true)] = $mysql_table_desc_row;
                $camel_name = $this->underscoreToCamelcase($mysql_table_desc_row['Field'], true);
                $class_const .= "\tconst FIELD_" . strtoupper($mysql_table_desc_row['Field']) . " = '" . $camel_name ."';\r\n";
                $class_vars .= "\t/* {$mysql_table_desc_row['Type']} */\r\n";
                $class_vars .= "\tprivate \${$camel_name};\r\n\r\n";
                $class_functions .= "\t/* @return {$mysql_table_desc_row['Type']} \$this->{$camel_name} */\r\n";
                $class_functions .= "\tpublic function get{$camel_name}() {\r\n";
                $class_functions .= "\t\treturn \$this->{$camel_name};\r\n";
                $class_functions .= "\t}\r\n";
                $class_functions .= "\t/* @param {$mysql_table_desc_row['Type']} \${$camel_name} */\r\n";
                $class_functions .= "\tpublic function set{$camel_name}(\${$camel_name}) {\r\n";
                $class_functions .= "\t\t\$this->{$camel_name} = \${$camel_name};\r\n";
                $class_functions .= "\t}\r\n";
            }

            $table_fields_json = json_encode($table_fields);

            $class_contents .= "{$class_const}\r\n";

            $class_contents .= "{$class_vars}\r\n";

            $class_contents .= "\tpublic function __construct(\$values = null) {\r\n";
            $class_contents .= "\t\tparent::__construct('{$table_name}','{$table_fields_json}', \$values);\r\n";
            $class_contents .= "\t}\r\n\r\n";

            $class_contents .= "{$class_functions}\r\n";
            $class_contents .= "}";

            file_put_contents($this->config->getConfigValue(array("dbmodel", "path")) . "{$class_name}Model.php", $class_contents);
        }
	}

	private function underscoreToCamelcase($str, $first_is_capital = false) {
        $result = "";
        $next_is_capital = $first_is_capital;
        for ($i = 0; $i < strlen($str); $i++) {
            if ($str[$i] == "_") {
                $next_is_capital = true;
                continue;
            } else {
                if ($next_is_capital) {
                    $result .= strtoupper($str[$i]);
                    $next_is_capital = false;
                } else {
                    $result .= $str[$i];
                }
            }
        }
        return $result;
    }
}

$env = getenv('FRAME_ENVIRONMENT');

if ($env == "development") {
    $cfg = "development";
} else {
    $cfg = "live";
}

$db_model = new GenerateDBModel("../../../../../app/config/app.{$cfg}.json");
$db_model->run();
