<?php

define('DS', DIRECTORY_SEPARATOR);
define('FRAMEWORK_ROOT_PATH', realpath(dirname(__FILE__)) . DS);

require_once FRAMEWORK_ROOT_PATH . '/function.php';
require_once FRAMEWORK_ROOT_PATH . '/FilterSqlInject.php';
require_once FRAMEWORK_ROOT_PATH . '/ErrorHandler.php';
