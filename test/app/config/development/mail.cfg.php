<?php
 
$_config = array(


    'host' => 'smtp.vip.163.com',
    'port' => 25,
    'from' => array('address' => 'ismond@vip.163.com', 'name' => 'Administrator'),
    'encryption' => 'ssl',
    'username' => 'ismond@vip.163.com',
    'password' => 'ismond163vip',
    'sendmail' => '/usr/sbin/sendmail -bs', 
    // 管理员邮箱 
    'amdin_email' => 'ismond@vip.163.com',
    'timeout'=>30
);


return $_config;
