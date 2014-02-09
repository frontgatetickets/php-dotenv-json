<?php
require dirname(__DIR__) . '/vendor/autoload.php';

use jimbocoder\DotenvJson;

DotenvJson::load(__DIR__ . '/fixtures');

var_dump($_ENV['fgt']);

