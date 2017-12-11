<?php

// 通用的加载文件
define('TEST_PATH', realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR);
define('TEST_LOG', TEST_PATH.'data/log' );

require_once TEST_PATH.'../globals.php';
require_once TEST_PATH.'BaseTestCase.php';
define('APP_URL', ROOT_URL);

function autoload($class)
{
    $class = str_replace('main\\', '', $class);
    //var_dump($class );
    $file = realpath(dirname($this->appPath . '/../../')) . '/' . $class . '.php';
    $file = str_replace(['\\', '//'], ['/', '/'], $file);
    //var_dump($file );
    if (is_file($file)) {
        include_once $file;
        return;
    }
}
spl_autoload_register( 'autoload');