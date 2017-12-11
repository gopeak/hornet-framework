<?php
/**
 * Created by PhpStorm.
 * User: sven
 * Date: 2017/7/7 0007
 * Time: 下午 3:01
 */

namespace main\app\ctrl;

use \main\app\model\UserModel;
use \main\app\classes\UserAuth;

/**
 * 配合单元测试的控制器类
 * Class unitTest
 * @package main\app\ctrl
 */
class UnitTest extends BaseCtrl
{
    public function auth()
    {
        if (!isset($_REQUEST['openid'])) {
            die('param error');
        }
        $openid = $_REQUEST['openid'];

        $userModel = new UserModel();
        $conditions['openid'] = $openid;
        $user = $userModel->getRow('*', $conditions);

        if (!isset($user['uid'])) {
            die('user is empty');
        }

        $auth = UserAuth::getInstance();
        $auth->login($user) ;
        echo 'ok';
    }
}
