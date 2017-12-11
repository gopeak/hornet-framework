<?php


// 项目中常用到的常量

// 项目状态:deploy | development 
define('APP_STATUS', "development");

// 获取所在目录名称
define("APP_NAME", basename(__DIR__));

// 网站名称
define('SITE_NAME', 'Hornet framework');

// 当前版本号
define('VERSION', "1.0.0");

// DIRECTORY_SEPARATOR short name
define('DS', DIRECTORY_SEPARATOR);


// 项目程序控制器所在根目录
define('CTRL_PATH', APP_PATH . 'ctrl/');

// 项目程序模型文件所在根目录
define('MODEL_PATH', APP_PATH . 'model/');

// 项目程序服务文件所在根目录
define('API_PATH', APP_PATH . 'api/');

// 项目程序服务文件所在根目录
define('SERVICE_PATH', API_PATH);

// 项目程序视图文件所在根目录
define('VIEW_PATH', APP_PATH . 'view/');

// 项目程序视图文件所在根目录
define('PUBLIC_PATH', APP_PATH . 'public/');

// 项目程序上传目录
define('STORAGE_PATH', APP_PATH . 'storage/');

// 临时文件存储目录
define('TMP_PATH', STORAGE_PATH . 'tmp/');

// 启用过滤接口机制
define('SECURITY_MAP_ENABLE', true);

// 加密秘钥（用于图片加密，一旦上线，此值不可修改，修改后无法解密）
define('ENCRYPT_KEY', '1234567890abc');


