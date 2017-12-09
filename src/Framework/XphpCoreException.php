<?php
/**
 * Created by PhpStorm.
 * User: weichaoduo
 * Date: 17/12/8
 * Time: 下午5:46
 */

namespace Framework;


/**
 * 开发框架本身异常
 * Class XphpException
 * @package main\xphp
 */
class XphpCoreException extends \Exception
{

    public function __construct($code, $msg)
    {

        $this->message = $msg;
        $this->code = $code;

    }

}