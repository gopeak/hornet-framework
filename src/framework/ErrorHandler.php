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
     * @var \xphp|null
     */
    private $xphp = null;


    /**
     * ErrorHandler constructor.
     *
     * @param  \xphp  $xphp
     */
    public function __construct($xphp)
    {
        $this->xphp = $xphp;

    }


    /**
     * swoole client 单例
     * @var self
     */
    protected static $swoole_client_instance;

    /**
     * swoole client 是否已经连接
     * @var bool
     */
    private static $swoole_client_connected = false;


    /**
     * 自定义错误处理
     */
    public function errorHandler (  $errno ,  $errstr ,  $errfile ,  $errline )
    {
        if (!( error_reporting () &  $errno )) {
            return false;
        }
        $errorType = array (
            E_ERROR            => 'ERROR',
            E_WARNING        => 'WARNING',
            E_PARSE          => 'PARSING ERROR',
            E_NOTICE         => 'NOTICE',
            E_CORE_ERROR     => 'CORE ERROR',
            E_CORE_WARNING   => 'CORE WARNING',
            E_COMPILE_ERROR  => 'COMPILE ERROR',
            E_COMPILE_WARNING => 'COMPILE WARNING',
            E_USER_ERROR     => 'USER ERROR',
            E_USER_WARNING   => 'USER WARNING',
            E_USER_NOTICE    => 'USER NOTICE',
            E_STRICT         => 'STRICT NOTICE',
            E_RECOVERABLE_ERROR  => 'RECOVERABLE ERROR'
        );
        // match error message
        if (array_key_exists($errno, $errorType)) {
            $err = $errorType[$errno];
        } else {
            $err = 'CAUGHT EXCEPTION';
        }

        $err_msg  = "$err: $errstr in $errfile on line $errline";

        // 写入日志操作
        $error_config = $this->xphp->getConfigVar( 'error' );
        if( empty($error_config) ){
            return false;
        }
        if( isset($error_config['enable_write_log']) && $error_config['enable_write_log']  ){
            $this->xphp->logErr( $err_msg );
        }

        // 是否启用发送错误邮件
        if( !isset($error_config['enable_send_email']) || !$error_config['enable_send_email']  ){
            return false;
        }

        // 判断服务状态是否可用
        $server_status_config = $this->xphp->getConfigVar( 'server_status' );
        // 如果异步服务器swoole不可用则写入文件
        if( !isset($server_status_config['swoole']) || !$server_status_config['swoole'] ){
            return false;
        }
        $traces   = print_r( debug_backtrace() ,true );
        // 限制最大行数50行,防止获取代码
        $max_source_line = min( 50, max( 10,intval($error_config['max_source_line']) ) );
        $source   = $this->readSource( $errfile, $errline, $max_source_line );
        $this->sendMailAsync( $err_msg, $traces, $source );

        return  true;
    }



    /**
     * 创建一个swoole client 单例对象
     * @return  swoole client
     */
    public   function getSwooleClientInstance(   )
    {

        if ( !isset(static::$swoole_client_instance) || !is_object( static::$swoole_client_instance ) ) {

            static::$swoole_client_instance = $this->createSwooleClient();
        }
        return static::$swoole_client_instance;
    }

    /**
     * 创建连接到swoole 服务器的客户端实例
     * @return swoole_client| null
     */
    private   function createSwooleClient()
    {
        if ( !extension_loaded('swoole') ){
            return null;
        }

        $client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC); //异步非阻塞

        $client->on("connect", function(swoole_client $cli) {
            echo "Server connected\n";
            static::$swoole_client_connected  = true;
        });

        $client->on("error", function(swoole_client $cli){
            echo "error\n";
            static::$swoole_client_instance = null;
            static::$swoole_client_connected  = false;
        });

        $client->on("close", function(swoole_client $cli){
            echo "Connection close\n";
            static::$swoole_client_instance = null;
            static::$swoole_client_connected  = false;
        });
        $async_config = $this->xphp->getConfigVar('async');
        if(!$client->connect( $async_config['async']['server']['host'], $async_config['async']['server']['port']))
        {
            static::$swoole_client_connected  = false;
            return null;
        }
        $client->timer = swoole_timer_after(1000, function () use ($client) {
            echo "socket timeout\n";
            $client->close();
            static::$swoole_client_connected  = false;
            $client = null;
        });
        static::$swoole_client_connected  = true;
        return $client;
    }


    /**
     * 异步的发送数据给swoole server,swoole server再交给 worker 执行
     * @param $err_msg
     * @param $traces
     */
    private function sendMailAsync( $err_msg, $traces , $source )
    {
        $error_config = $this->xphp->getConfigVar( 'error' );

        $subject = substr( $err_msg,0,20 );
        $content = $err_msg;
        if( isset($error_config['mail_tpl']) ){
            $content = str_replace(
                [ '{{err_msg}}', '{{traces}}','{{source}}' ],
                [ $err_msg, $traces ,$source ] ,
                $error_config['mail_tpl'] );
        }
        $json_data = json_encode( ['cmd'=>'email.send','subject'=>$subject,'content'=>$content] );

        $swoole_client =  $this->getSwooleClientInstance();
        if( static::$swoole_client_connected && !empty($swoole_client) ){
            $swoole_client->send( $json_data );
        }

    }

    private function readSource( $file, $line, $max_source_line )
    {
        $i = 0;
        $handle  =  fopen ( $file,  "rb" );
        $source  =  "<?php \n" ;
        $start_line = max( 0, $line-intval($max_source_line/2) );
        $end_line = max( $line, $line+intval($max_source_line/2) );
        while (! feof ( $handle )) {
            $i ++;
            $tmp  =  fgets ( $handle ,  4096 );
            if( $i>$start_line  ||  $end_line<$i ){
                $source  .= "/*{$i}*/  ". $tmp;
            }
            if( $i>$end_line ){
                break;
            }
        }
        fclose ( $handle );
        return $source;

    }



}