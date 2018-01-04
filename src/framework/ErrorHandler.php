<?php

namespace framework;

/**
 * Created by PhpStorm.
 * User: sven
 * Date: 2017/7/13 0013
 * Time: 下午 4:17
 */
class ErrorHandler
{

    /**
     * @var HornetEngine | null
     */
    private $engine = null;

    /**
     * ErrorHandler constructor.
     * @param  HornetEngine $engine
     */
    public function __construct($engine)
    {
        $this->engine = $engine;
    }


    /**
     * swoole client 单例
     * @var self
     */
    protected static $swooleClientInstance;

    /**
     * swoole client 是否已经连接
     * @var bool
     */
    private static $swooleClientConnected = false;


    /**
     * 自定义错误处理
     */
    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        if (!(error_reporting() & $errno)) {
            return false;
        }
        $errorType = array(
            E_ERROR => 'ERROR',
            E_WARNING => 'WARNING',
            E_PARSE => 'PARSING ERROR',
            E_NOTICE => 'NOTICE',
            E_CORE_ERROR => 'CORE ERROR',
            E_CORE_WARNING => 'CORE WARNING',
            E_COMPILE_ERROR => 'COMPILE ERROR',
            E_COMPILE_WARNING => 'COMPILE WARNING',
            E_USER_ERROR => 'USER ERROR',
            E_USER_WARNING => 'USER WARNING',
            E_USER_NOTICE => 'USER NOTICE',
            E_STRICT => 'STRICT NOTICE',
            E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR'
        );
        // match error message
        if (array_key_exists($errno, $errorType)) {
            $err = $errorType[$errno];
        } else {
            $err = 'CAUGHT EXCEPTION';
        }

        $err_msg = "$err: $errstr in $errfile on line $errline";

        // 写入日志操作
        $error_config = $this->engine->getConfigVar('error');
        if (empty($error_config)) {
            return false;
        }
        if (isset($error_config['enable_write_log']) && $error_config['enable_write_log']) {
            $this->engine->logErr($err_msg);
        }

        // 是否启用发送错误邮件
        if (!isset($error_config['enable_send_email']) || !$error_config['enable_send_email']) {
            return false;
        }

        // 判断服务状态是否可用
        $server_status_config = $this->engine->getConfigVar('server_status');
        // 如果异步服务器swoole不可用则写入文件
        if (!isset($server_status_config['swoole']) || !$server_status_config['swoole']) {
            return false;
        }
        $reqData = substr(var_export($_GET, true),0, 1000)."\n<br>".substr(var_export($_POST, true),0, 10000);
        // 限制最大行数50行,防止获取代码
        $max_source_line = min(50, max(10, intval($error_config['max_source_line'])));
        $source = $this->readSource($errfile, $errline, $max_source_line);
        $this->sendMailAsync($err_msg, $reqData, $source);

        return true;
    }


    /**
     * 创建一个swoole client 单例对象
     * @return  swoole client
     */
    public function getSwooleClientInstance()
    {

        if (!isset(static::$swooleClientInstance) || !is_object(static::$swooleClientInstance)) {

            static::$swooleClientInstance = $this->createSwooleClient();
        }
        return static::$swooleClientInstance;
    }

    /**
     * 创建连接到swoole 服务器的客户端实例
     * @return swoole_client| null
     */
    private function createSwooleClient()
    {
        if (!extension_loaded('swoole')) {
            return null;
        }

        $client = new \swoole_client(SWOOLE_SOCK_TCP); //异步非阻塞
        $client->set(array(
            'package_max_length'    => 2000000,  //协议最大长度
            'socket_buffer_size'     => 1024*1024*2, //2M缓存区
        ));
        $async_config = $this->engine->getConfigVar('async');
        if (!$client->connect($async_config['async']['server']['host'], $async_config['async']['server']['port'])) {
            static::$swooleClientConnected = false;
            return null;
        }

        static::$swooleClientConnected = true;
        return $client;
    }


    /**
     * 异步的发送数据给swoole server,swoole server再交给 worker 执行
     * @param $err_msg
     * @param $reqData
     * @param $source
     */
    private function sendMailAsync($err_msg, $reqData, $source)
    {
        $error_config = $this->engine->getConfigVar('error');

        $subject = substr($err_msg, 0, 20);
        $content = $err_msg;
        if (isset($error_config['mail_tpl'])) {
            $content = str_replace(
                ['{{err_msg}}', '{{req_data}}', '{{source}}'],
                [$err_msg, $reqData, $source],
                $error_config['mail_tpl']);
        }
        $json_data = json_encode([ 'to'=>$error_config['email_notify'],'config'=>$this->engine->getConfigVar('email'), 'cmd' => 'email.send_by_api', 'subject' => $subject, 'content' => $content]);

        $swoole_client = $this->getSwooleClientInstance();
        if (static::$swooleClientConnected && !empty($swoole_client)) {
            $swoole_client->send($json_data);
        }
    }

    private function readSource($file, $line, $max_source_line)
    {
        $i = 0;
        $handle = fopen($file, "rb");
        $source = "<?php \n";
        $start_line = max(0, $line - intval($max_source_line / 2));
        $end_line = max($line, $line + intval($max_source_line / 2));
        while (!feof($handle)) {
            $i++;
            $tmp = fgets($handle, 4096);
            if ($i > $start_line || $end_line < $i) {
                $source .= "/*{$i}*/  " . $tmp;
            }
            if ($i > $end_line) {
                break;
            }
        }
        fclose($handle);
        return $source;
    }
}
