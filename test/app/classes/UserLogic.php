<?php
/**
 * Created by PhpStorm.
 * User: sven
 * Date: 2017/7/7 0007
 * Time: 下午 3:56
 */

namespace main\app\classes;
use main\app\model\UserModel;

class UserLogic
{

    const STATUS_OK = 1;
    const STATUS_DELETE= 2;
    static  public $STATUS = [
        self::STATUS_OK=>'正常',
        self::STATUS_DELETE=>'已删除'
    ];

    public function getUserLimit( $limit = 100 )
    {
        $userModel = new UserModel();
        $conditions['status'] = '1';
        $sort = " id desc ";
        $append_sql = "";
        $users = $userModel->getRows( "*", $conditions , $append_sql,  $sort, $limit  );
        return $users;
    }

}