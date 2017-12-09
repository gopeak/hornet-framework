<?php

namespace Framework;

/**
 * Created by PhpStorm.
 * User: sven
 * Date: 2017/7/13 0013
 * Time: 下午 4:17
 */
class FilterSqlInject
{
    /**
     * 正则匹配 url 参数
     * @var string
     */
    public $getFilter = "'|(and|or)\b.+?(>|<|=|in|like)|\/\*.+?\*\/|<\s*script\b|\bEXEC\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\s+(TABLE|DATABASE)";

    /**
     * 正则匹配 post请求参数
     * @var string
     */
    public $postFilter = "\b(and|or)\b.{1,6}?(=|>|<|\bin\b|\blike\b)|\/\*.+?\*\/|<\s*script\b|\bEXEC\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\s+(TABLE|DATABASE)";

    /**
     * 正则匹配 cookie请求参数
     * @var string
     */
    public $cookieFilter = "\b(and|or)\b.{1,6}?(=|>|<|\bin\b|\blike\b)|\/\*.+?\*\/|<\s*script\b|\bEXEC\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\s+(TABLE|DATABASE)";


    /**
     * @var string
     */
    public $logPath = '';

    public function __construct($logPath)
    {
        $this->logPath = $logPath;

    }

    /**
     * 检查参数是否合法
     * @param $strFilterKey
     * @param $strFilterValue
     * @param $arrFilterReq
     * @throws \Exception
     */
    public function checkAttack($strFilterKey, $strFilterValue, $arrFilterReq)
    {
        if ( is_array($strFilterValue) ) {
            $strFilterValue = @implode('', $strFilterValue);
        }
        if ( preg_match("/" . $arrFilterReq . "/is", $strFilterValue) == 1 ) {

            $logData = $_SERVER["REMOTE_ADDR"] . " " . strftime("%Y-%m-%d %H:%M:%S") . " " . $_SERVER["PHP_SELF"]
                . " " . $_SERVER["REQUEST_METHOD"] . "  " . $strFilterKey . "  " . $strFilterValue . "\n";
            file_put_contents($this->logPath . '/' . date('Y-m-d') . '.log', $logData, FILE_APPEND);
            throw new \Exception("Sql inject attack risk : req " . $strFilterKey . " " . $strFilterValue);
        }
    }

    /**
     * 全局过滤处理
     */
    public function filterInput()
    {
        foreach ( $_GET as $key => $value ) {
            $this->checkAttack($key, $value, $this->getFilter);
        }
        foreach ( $_POST as $key => $value ) {
            $this->checkAttack($key, $value, $this->postFilter);
        }
        foreach ( $_COOKIE as $key => $value ) {
            $this->checkAttack($key, $value, $this->cookieFilter);
        }
        foreach ( $_REQUEST as $key => $value ) {
            $this->checkAttack($key, $value, $this->postFilter);
        }

    }


}