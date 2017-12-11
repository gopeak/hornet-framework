<?php
namespace main\app\model;


/**
 * 数据库操作的新写法
 * @package main\app\model
 */
class DemoModel extends DbModel{

    public $prefix = 'xphp_';

    public $table = 'user';

    public $fields = ' * ';

    public $primary_key = 'id';

}