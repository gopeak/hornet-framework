<?php 

namespace main\app\model;
class UnitTestUnitModel extends DbModel{
    public $prefix = "test_";
    public $table = "user";
    public $fields = "*";
    public $primary_key = "id";
}

