<?php
/**
 * Created by PhpStorm.
 * User: weichaoduo
 * Date: 17/12/8
 * Time: 下午5:46
 */

namespace Framework;



/**
 * 业务逻辑异常处理类
 */
class XphpLogicException extends \Exception
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
