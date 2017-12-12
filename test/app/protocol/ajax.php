<?php
namespace main\app\protocol;

/**
 * Created by PhpStorm.
 * User: sven
 * Date: 2017/6/30 0030
 * Time: 上午 11:10
 */
class ajax implements Iprotocol
{
    public $ret = '200';

    public $msg = '';

    public $data = null;

    public $format = 'json';

    public function __construct (  )
    {
    }

    /**
     * 获取ajax 返回的json格式
     * @return string
     */
    public function getResponse(  ):string
    {
        $obj = new \stdClass();
        $obj->ret  = $this->ret;
        $obj->msg  = $this->msg;
        $obj->data = $this->data;

        return json_encode( $obj  );
    }

    /**
     * 传入数据
     * @param $ret
     * @param $data
     * @param string $msg
     * @param string $format
     */
    public function builder( $ret, $data, $msg = '',$format='json' )
    {
        header( 'Content-type: application/json; charset=utf-8' );
        $this->ret = strval( $ret );
        $this->time = time();
        $this->msg = $msg;
        $this->data = $data;
        $this->format = $format;
    }
}