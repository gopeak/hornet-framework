<?php

namespace framework;

/**
 * 业务逻辑异常处理类
 */
class HornetLogicException extends \Exception
{

    /**
     * 那些用户可以trace
     * @var array
     */
    protected $trace_uids;

    /**
     *是否trace
     */
    protected $is_trace = true;


    public function __construct($code, $msg)
    {

        $this->message = $msg;
        $this->code = $code;

    }

}
