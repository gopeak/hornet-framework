<?php

namespace main\app\protocol;


/**
 * Created by PhpStorm.
 * User: sven
 * Date: 2017/6/30 0030
 * Time: 上午 11:10
 */
class api implements Iprotocol
{


    private $ret_direct_str = false;

    private $enable_trace = false;

    public $ret = '200';

    public $msg = '';

    public $data = null;

    public $debug = \stdClass::class;

    public $trace = [];

    public $time = 0;

    public $format = 'json';


    public function __construct ( $enable_trace )
    {
        $this->enable_trace = $enable_trace;
    }

    public function get_response(  ):string
    {

        $obj = new \stdClass();
        $obj->ret = $this->ret;
        $obj->debug = $this->debug;
        $obj->time = $this->time;
        $obj->trace = $this->trace;
        $obj->data = $this->data;

        return $this->format( $obj  );
    }

    /**
     * 将对象转换为xml,采用递归算法
     * @param $obj
     * @param int $dom
     * @param int $item
     * @return string
     */
    private function objectToXml( $obj, $dom = 0, $item = 0 )
    {
        if ( !$dom ) {
            $dom = new \DOMDocument( "1.0" );
        }
        if ( !$item ) {
            $item = $dom->createElement( "root" );
            $dom->appendChild( $item );
        }
        foreach ( $obj as $key => $val ) {
            $itemx = $dom->createElement( is_string( $key ) ? $key : "item" );
            $item->appendChild( $itemx );
            if ( !is_object( $val ) ) {
                $text = $dom->createTextNode( $val );
                $itemx->appendChild( $text );
            } else {
                $this->objectToXml( $val, $dom, $itemx );
            }
        }
        return $dom->saveXML();
    }

    private function format( $obj  ) :string
    {

        if ( $this->ret_direct_str && $this->format == 'json' ) {
            $debug = '{}';
            if ( !empty($obj->debugs) ) {
                $debug = json_encode( $obj->debugs );
            }
            $trace = '[]';
            if ( !empty($obj->trace) ) {
                $trace = json_encode( $obj->trace );
            }
            header( 'Content-type: application/json; charset=utf-8' );
            return sprintf( '{"data":%s,"ret":"%s","debug":%s,"time":%d,"trace":%s }',
                $obj->data, $obj->ret, $debug, $trace
            );

        }

        if ( $this->format == 'xml' ) {
            header( 'Content-type: application/xml; charset=utf-8' );
            return $this->objectToXml( $obj );
        }
        // json
        header( 'Content-type: application/json; charset=utf-8' );
        return json_encode( $obj );
    }

    public function builder( $ret, $data, $msg = '', $format='json' )
    {
        $debug_obj = new \stdClass();
        if ( isset($GLOBALS['__debugs']) ) {
            $debug_obj = (object)$GLOBALS['__debugs'];
            unset($GLOBALS['__debugs']);
        }

        $trace_arr = [];
        if ( $this->enable_trace ) {
            $trace_arr = debug_backtrace();
        }

        $this->ret = strval( $ret );
        $this->debug = $debug_obj;
        $this->time = time();
        $this->trace = $trace_arr;
        $this->data = $data;
        $this->format = $format;

        if ( is_string( $data ) ) {
            // 如果是一个json字符串,则直接绑定到data字段中
            $tmp = trim( $data );
            if ( $tmp[0] == '{' && $tmp[len( $tmp ) - 1] == '}' ) {
                $this->ret_direct_str = true;
            }
            if ( $tmp[0] == '[' && $tmp[len( $tmp ) - 1] == ']' ) {
                $this->ret_direct_str = true;
            }
        }
    }

}