<?php

namespace framework\protocol;

/**
 * Created by PhpStorm.
 * User: sven
 * Date: 2017/6/30 0030
 * Time: 上午 11:10
 */
class Api implements Iprotocol
{


    private $retDirectStr = false;

    private $enableTrace = false;

    public $ret = '200';

    public $msg = '';

    public $data = null;

    public $debug = \stdClass::class;

    public $trace = [];

    public $time = 0;

    public $format = 'json';


    public function __construct($enableTrace = false, $retDirectStr = false)
    {
        $this->enableTrace = $enableTrace;
    }

    public function getResponse()
    {

        $obj = new \stdClass();
        $obj->ret = $this->ret;
        $obj->debug = $this->debug;
        $obj->time = $this->time;
        $obj->trace = $this->trace;
        $obj->data = $this->data;

        return $this->format($obj);
    }

    /**
     * 将对象转换为xml,采用递归算法
     * @param $obj
     * @param int $dom
     * @param int $item
     * @return string
     */
    private function objectToXml($obj, $dom = 0, $item = 0)
    {
        if (!$dom) {
            $dom = new \DOMDocument("1.0");
        }
        if (!$item) {
            $item = $dom->createElement("root");
            $dom->appendChild($item);
        }
        //var_dump($obj);
        foreach ($obj as $key => $val) {
            $itemx = $dom->createElement(is_string($key) ? $key : "item");
            $item->appendChild($itemx);

            if (!is_object($val)) {
                if (is_array($val)) {
                    $val = $this->arrToXml($val, $dom, $itemx);
                } else {
                    $text = $dom->createTextNode($val);
                    $itemx->appendChild($text);
                }
            } else {
                $this->objectToXml($val, $dom, $itemx);
            }
        }
        return $dom->saveXML();
    }

    private function arrToXml($arr, $dom = 0, $item = 0)
    {
        if (!$dom) {
            $dom = new \DOMDocument("1.0");
        }
        if (!$item) {
            $item = $dom->createElement("root");
            $dom->appendChild($item);
        }
        //var_dump($obj);
        foreach ($arr as $key => $val) {
            $itemx = $dom->createElement(is_string($key) ? $key : "item");
            $item->appendChild($itemx);

            if (!is_array($val)) {
                $text = @$dom->createTextNode($val);
                $itemx->appendChild($text);
            } else {
                $this->arrToXml($val, $dom, $itemx);
            }
        }
        return $dom->saveXML();
    }

    private function format($obj)
    {
        if ($this->retDirectStr && $this->format == 'json') {
            $debug = '{}';
            if (!empty($obj->debugs)) {
                $debug = json_encode($obj->debugs);
            }
            $trace = '[]';
            if (!empty($obj->trace)) {
                $trace = json_encode($obj->trace);
            }
            header('Content-type: application/json; charset=utf-8');
            $format = '{"data":%s,"ret":"%s","debug":%s,"time":%d,"trace":%s }';
            return sprintf($format, $obj->data, $obj->ret, $debug, $trace);
        }

        if ($this->format == 'xml') {
            header('Content-type: application/xml; charset=utf-8');
            $arr = (array)$obj;
            return $this->objectToXml($arr);
        }
        // json
        // 如果trace的对象是当期实例则删除掉否则会异常
        //if ($obj->trace[0]['object'] == $this) {
            //echo 'this';
        //}
        if (isset($obj->trace[0]['object'])) {
            unset($obj->trace[0]['object']);
            $obj->trace[0]['object'] = new \stdClass();
        }

        header('Content-type: application/json; charset=utf-8');
        return json_encode($obj);
    }

    public function builder($ret, $data, $msg = '', $format = 'json')
    {
        $debug_obj = new \stdClass();
        if (isset($GLOBALS['__debugs'])) {
            $debug_obj = (object)$GLOBALS['__debugs'];
            unset($GLOBALS['__debugs']);
        }

        $traceArr = [];
        if ($this->enableTrace) {
            $traceArr = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 4);
        }

        $this->ret = strval($ret);
        $this->debug = $debug_obj;
        $this->time = time();
        $this->trace = $traceArr;
        $this->data = $data;
        $this->format = $format;

        if (is_string($data)) {
            // 如果是一个json字符串,则直接绑定到data字段中
            $tmp = trim($data);
            if ($tmp[0] == '{' && substr($tmp, -1) == '}') {
                $this->retDirectStr = true;
            }
            if ($tmp[0] == '[' && substr($tmp, -1) == ']') {
                $this->retDirectStr = true;
            }
        }
    }
}
