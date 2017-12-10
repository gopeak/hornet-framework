<?php

if (!defined('FRAMEWORK_ROOT_PATH')) {
    define('FRAMEWORK_ROOT_PATH', realpath(dirname(__FILE__)) . '/');
}

require_once FRAMEWORK_ROOT_PATH . '/function.php';
require_once FRAMEWORK_ROOT_PATH . '/FilterSqlInject.php';
require_once FRAMEWORK_ROOT_PATH . '/ErrorHandler.php';
require_once FRAMEWORK_ROOT_PATH . '/HornetEngine.php';
require_once FRAMEWORK_ROOT_PATH . '/protocol/Iprotocol.php';
require_once FRAMEWORK_ROOT_PATH . '/protocol/Api.php';
require_once FRAMEWORK_ROOT_PATH . '/protocol/Ajax.php';
