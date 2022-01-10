<?php

namespace Frame;

class DBO {
    private $db_con = null;

    public function __construct($config) {
        $this->db_con = mysqli_connect($config->getConfigValue(array("mysql", "host")), $config->getConfigValue(array("mysql", "username")), $config->getConfigValue(array("mysql", "password")), $config->getConfigValue(array("mysql", "database")));
    }

    public function query($query) {
        return mysqli_query($this->db_con, $query);
    }

    public function real_escape_string($string) {
        return mysqli_real_escape_string($this->db_con, $string);
    }
}