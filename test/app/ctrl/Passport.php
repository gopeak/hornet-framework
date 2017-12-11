<?php
/**
 * Created by PhpStorm. 
 */

namespace main\app\ctrl;
 

/**
 * Class Passport
 
 * 用户账号相关功能
 */
class Passport extends BaseUserCtrl
{

    public function logout()
    { 
        $this->auth->logout(); 
        $this->redirect('/');
    }

    /**
     * index
     */
    public function login()
    {
        echo 'login';
    }

}
