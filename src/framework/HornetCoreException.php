<?php

namespace framework;

/**
 * 开发框架本身异常
 * Class XphpException
 * @package main\xphp
 */
class HornetCoreException extends \Exception
{

    public function __construct($code, $msg)
    {
        $this->message = $msg;
        $this->code = $code;
    }
}
