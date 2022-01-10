<?php

namespace Frame;

class Config {
	private $config = array();

	public function __construct($config_path) {
		$this->config = json_decode(file_get_contents($config_path), true);
	}

	public function getConfigValue($name) {
	    if (is_array($name)) {
	        $val = $this->config;
	        foreach ($name as $part) {
	            if (array_key_exists($part, $val)) {
	                $val = $val[$part];
                } else {
	                return null;
                }
            }
	        return $val;
        } else {
	        if (array_key_exists($name, $this->config)) {
                return $this->config[$name];
            }
        }
	    return null;
    }
}
