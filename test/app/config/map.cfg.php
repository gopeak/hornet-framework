<?php


/**
 * 开发框架定义api和控制器的可访问设置
 */

// Api
$_config['api']['FirstApi'] = [ 'get' ];
$_config['api']['Framework'] = '*';
$_config['api']['mod_test.Framework'] = '*';
$_config['api']['RestfulExample'] = '*';


// 控制器
$_config['ctrl']['Index'] = "*";
$_config['ctrl']['Passport'] = "*";
$_config['ctrl']['Framework'] = '*';
$_config['ctrl']['mod_test.Framework'] = '*';
$_config['ctrl']['Log'] = [ 'index', '_list', 'detail', 'test_add' ];
$_config['ctrl']['UnitTest'] = '*';
$_config['ctrl']['User'] = '*';

// 路由处理
$_config['url'] = array( //url 路径访问
    'router' => 'default', //是否支持路由(default 智能模式 path 原生模式 rewrite 重写模式)
    'suffix' => 'html', //生成地址的结尾符，网址后缀
    'map' => array( //url映射
        'cn' => 'com/prod_abls', //访问cn 相当于访问com/prod_abls
        't' => 'com/prod_mbl' //访问cn 相当于访问com/prod_mbl
    ),
);
return $_config;
