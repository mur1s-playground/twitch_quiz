<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: OPTIONS, GET, POST");

require "../vendor/reinerlanz/frame/src/Boot.php";

$host = $_SERVER['HTTP_HOST'];

if ($host == "development.localhost") {
    $cfg = "development";
} else {
    $cfg = "live";
}

$boot = new Frame\Boot("../app/config/app.{$cfg}.json");
$boot->run();
