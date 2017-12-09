<?php

define('DS', DIRECTORY_SEPARATOR);
define('XPHP_ROOT_PATH', realpath(dirname(__FILE__)) . DS);

require_once XPHP_ROOT_PATH . '/function.php';
require_once XPHP_ROOT_PATH . '/FilterSqlInject.php';
require_once XPHP_ROOT_PATH . '/ErrorHandler.php';
