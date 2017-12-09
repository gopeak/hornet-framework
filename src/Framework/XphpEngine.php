<?php

namespace Framework;

require_once realpath(dirname(__FILE__))  . '/bootstrap.php';

/**
 * 开发框架核心文件
 */
class XphpEngine
{
    /**
     * 页面上下文所要执行的控制器类名称
     * @var string
     */
    public $ctrl = 'index';

    /**
     * 页面上下文所要执行的控制器方法名称
     * @var string
     */
    public $action = 'index';

    /**
     * 同$_action
     * @var string
     */
    public $method = 'index';

    /**
     * 伪静态的请求参数
     * @var array
     */
    public $target = [];

    /**
     * Api请求要执行的类名称
     * @var string
     */
    public $cmd;

    /**
     * 控制器或Api模块名称
     * @var string
     */
    public $mod;

    /**
     * xphp所在的绝对路径
     * @var string
     */
    public $xphp_root_path = '';

    /**
     * 项目的绝对路径
     * @var string
     */
    public $app_path = '';

    /**
     * 项目的存储绝对路径,多服务前期负载均衡时建议使用NFS
     * @var string
     */
    public $storage_path = '';

    /**
     * 项目的开发状态
     * @var string
     */
    public $app_status = 'development';

    /**
     * session_start是否启动
     * @var bool
     */
    private $session_started = false;

    /**
     * 当前的项目目录名称,如app site
     * @var string
     */
    private $current_app = 'app';

    /**
     * 在api调用时是否返回调用堆栈
     * @var bool
     */
    private $enable_trace = false;

    /**
     * 是否记录api请求日志
     * @var bool
     */
    private $enable_write_req_log = false;

    /**
     * 是否启用xphpof性能分析
     * @var bool
     */
    private $enable_xhprof = false;

    /**
     * 触发xphprof性能分析的概率,千分之..
     * @var int
     */
    private $xhprof_rate = 1;

    /**
     * xphprof 日志存储路径
     * @var string
     */
    private $xhprof_save_path = '';

    /**
     * xhprof web分析库目录
     * @var string
     */
    private $xhprof_root = '';

    /**
     * 是否启用访问路由检查,如果启用,只有在condig/{$app_status}/map.cfg.php定义的路由才允许访问
     * @var bool
     */
    private $enable_security_map = true;

    /**
     * 是否启用反射功能
     * @var bool
     */
    private $enable_reflect_method = true;


    /**
     * Api调用时返回的数据格式
     * @var string
     */
    private $format = 'json';

    /**
     * 处理返回值的协议类
     * @var string
     */
    private $api_protocol_class = 'api';


    /**
     * 处理返回值的协议类
     * @var string
     */
    private $ajax_protocol_class = 'ajax';

    /**
     *  是否自动检查sql注入检查
     * @var bool
     */
    private $enable_filter_sql_inject = true;

    /**
     * 显示页面
     * @var string
     */
    private $exception_page = 'exception.php';

    /**
     *  当路由以api开头则表明为api请求
     */
    const MOD_API_NAME = 'api';


    public function __construct($config)
    {
        $curPath = realpath(dirname(__FILE__));
        // 先初始化app_path
        $this->app_path = $curPath . '/app/';

        // 进行外部注入内部属性
        foreach ( $config as $k => $v ) {
            if ( isset($this->$k) ) {
                $this->$k = $v;
            }
        }
        if ( !in_array(substr($this->app_path, -1), [ '/', '\\' ]) ) {
            $this->app_path .= '/';
        }
        // 初始化存储路径
        if ( !isset($config->storage_path) ) {
            $this->storage_path = $this->app_path . 'storage/';
        }
        // 初始化xhprof日志路径
        if ( !isset($config->xhprof_save_path) ) {
            $this->xhprof_save_path = $this->storage_path . 'xhprof/';
        }

        if ( !isset($config->xphp_root_path) ) {
            $this->xphp_root_path = realpath(dirname(__FILE__)) . '/';
        }
        // 异常显示页
        if ( !isset($config->exception_page) ) {
            $this->exception_page = $this->xphp_root_path . $config->exception_page;
        }

        // 返回格式检查
        if ( isset($_REQUEST['format']) ) {
            $this->format = es(trimStr($_REQUEST['format']));
        }

        // 自定义错误日志处理 ,@todo 考虑替代方案: register_shutdown_function + error_get_last
        $errHandler = new ErrorHandler($this);
        set_error_handler(array( $errHandler, 'errorHandler' ));

        // api参数
        $this->cmd = isset($_REQUEST['cmd']) ? trim($_REQUEST['cmd']) : '';

        // url重写
        $this->rewrite();

        // 伪静态参数放入全局变量,这是使用全局变量两个地方之一
        $_GET['_target'] = es($this->target);

        // 性能分析日志
        $this->xhprofHandler();

        spl_autoload_register([$this,'autoload'] );


    }

    /**
     * 获取属性值
     * @param $name
     * @return mix
     */
    public function getProperty($name)
    {
        if ( isset($this->$name) ) {
            return $this->$name;
        }
        if ( isset(static::$name) ) {
            return static::$name;
        }
        return null;
    }

    /**
     * 重写url
     */
    private function rewrite()
    {
        $ret = [];
        $ret ['control'] = 'index';
        $ret ['method'] = 'index';
        $ret ['action'] = 'index';
        $ret ['mod'] = '';
        $ret ['target'] = '';

        $uri = isset($_SERVER['REQUEST_URI']) ? trim($_SERVER['REQUEST_URI'], '?') : '';

        $base = isset($_SERVER['SCRIPT_NAME']) ? dirname($_SERVER['SCRIPT_NAME']) : '';
        if ( false !== ( $qm = strpos($uri, '?') ) ) {
            $uri = substr($uri, 0, $qm);
        }
        $rewrite = trim(substr($uri, strlen($base)), '/');
        $params = false !== strpos($rewrite, '/') ? explode('/', $rewrite) : ( $rewrite ? array( 0 => $rewrite ) : array() );
        $ret['target'] = $ret['params'] = $params;

        $common_config = static::getCommonConfigVar('common');

        if ( !empty($params) && count($params) > 0 ) {
            // 模块是否已经在common.cfg.php中定义
            $common_config['mods'][] = static::MOD_API_NAME;
            if ( in_array($params[0], $common_config['mods']) ) {
                // 支持 api的子模块
                if ( $params[0] == static::MOD_API_NAME ) {
                    if ( count($params) > 3 && in_array($params[1], $common_config['mods']) ) {
                        $ret['mod'] = $params[1];
                        $ret['control'] = !empty($params[2]) ? $params[2] : 'index';
                        $ret['action'] = !empty($params[3]) ? $params[3] : 'index';
                    } else {
                        $ret['mod'] = '';
                        $ret['control'] = !empty($params[1]) ? $params[1] : 'index';
                        $ret['action'] = !empty($params[2]) ? $params[2] : 'index';
                    }
                    $this->cmd = $ret['control'] . '.' . $ret['action'];
                } else {
                    $ret['mod'] = $params[0];
                    $ret['control'] = !empty($params[1]) ? $params[1] : 'index';
                    $ret['action'] = !empty($params[2]) ? $params[2] : 'index';
                }


            } else {
                $ret['mod'] = '';
                if ( !isset($params[1]) || strpos($params[1], '.html') !== false ) {
                    $params[1] = 'index';
                }
                $ret['control'] = !empty($params[0]) ? $params[0] : 'index';
                $ret['action'] = !empty($params[1]) ? $params[1] : 'index';
            }
        }
        $ret['method'] = $ret['action'];
        $this->ctrl = $ret['control'];
        $this->action = $ret['action'];
        $this->method = $ret['method'];
        $this->mod = $ret['mod'];
        $this->target = $ret['target'];
        //p($ret);
        return $ret;
    }


    /**
     * 开发框架 路由分发，动态调用方法以及构建返回
     */
    public function route()
    {
        if ( !empty($this->mod) ) {
            if ( $this->mod == static::MOD_API_NAME ) {
                $this->cmd = $this->ctrl . '.' . $this->action;
            }
        }
        if ( $this->cmd != '' ) {
            $cmdParams = explode('.', $this->cmd);
            if ( count($cmdParams) > 2 ) {
                $this->mod = $cmdParams[0];
                $this->cmd = $cmdParams[1] . '.' . $cmdParams[2];
            }
            $this->routeApi();
        } else {
            $this->routeCtrl();
        }
    }

    private function autoload($class)
    {
        //var_dump($class);
        $class = str_replace( 'main\\', '', $class );

        $file = realpath(dirname( $this->app_path. '/../../')) .DS. $class . '.php';
        //var_dump($file);
        //$file = str_replace( 'main\\', '', $file );
        $file = str_replace( ['\\','//'], [DS,DS], $file );
        //var_dump($file);
        if ( is_file($file) ) {
            require_once $file;
            return;
        }
    }

    /**
     * 处理api请求路由
     * @throws \Exception \PDOException logicException
     */
    private function routeApi()
    {

        try {

            $api_protocol_class = sprintf("main\\%s\\protocol\\%s", $this->current_app, $this->api_protocol_class);
            if ( !class_exists($api_protocol_class) ) {
                throw new XphpCoreException($api_protocol_class . ' no found', 500);
            }
            $apiProtocol = new $api_protocol_class($this->enable_trace);

            // sql注入检查
            if ( $this->enable_filter_sql_inject ) {
                $filterSqlInject = new FilterSqlInject($this->storage_path . '/log/sql_inject/');
                $filterSqlInject->filterInput();
            }
            if ( !strpos($this->cmd, '.') ) {
                throw new \Exception('Api invoker error: cmd param error!', 500);
            }
            list ($service, $method) = explode('.', $this->cmd);
            $service = preg_replace_callback('/_+([a-z])/', function ($matches) {
                return strtoupper($matches[1]);
            }, $service);
            $serviceClass = sprintf("main\\%s\\api\\%s", $this->current_app, $service);
            if ( !empty($this->mod) && $this->mod != 'api' ) {
                $serviceClass = sprintf("main\\%s\\api\\%s\\%s", $this->current_app, $this->mod, $service);
            }

            $service_obj = new $serviceClass();

            if ( !method_exists($service_obj, $method) ) {
                $method = preg_replace_callback('/_+([a-z])/', function ($matches) {
                    return strtoupper($matches[1]);
                }, $method);
                if ( !method_exists($service_obj, $method) ) {
                    throw new \Exception($this->ctrl . '->' . $method . ' no found;', 404);
                }
            }

            // 安全映射机制
            $this->securityMapCheck(static::MOD_API_NAME, $service, $method);


            if ( ( $this->app_status == 'development' || $this->app_status == 'test' ) && $this->enable_write_req_log ) {
                f($this->storage_path . 'tmp/' . date('Y-m-d') . '_request.log', date('H:i:s') . ': ' . var_export($_GET, true)
                    . var_export($_POST, true) . var_export($_COOKIE, true) . "\n\n", FILE_APPEND);
            }
            $reflectMethod = null;
            // 通过反射获取调用方法的参数列表
            if ( $this->enable_reflect_method ) {
                $reflectMethod = new \ReflectionMethod($service_obj, $method);
                $args = [];
                $defaults = [];
                foreach ( $reflectMethod->getParameters() as $param ) {
                    $args[] = $param->getName();
                    $defaults[] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
                }
                if ( empty($args) ) {
                    // 开始执行业务逻辑流程
                    $result = call_user_func_array([ $service_obj, $method ], []);
                } else {
                    $params = array();
                    foreach ( $args as $ak => $arg ) {
                        $params[$ak] = isset($_REQUEST[$arg]) ? $_REQUEST[$arg] : $defaults[$ak];
                    }
                    // 开始执行业务逻辑流程
                    $result = call_user_func_array([ $service_obj, $method ], $params);
                }
            } else {
                $result = call_user_func_array([ $service_obj, $method ], []);
            }

            // 执行结束检验结果类型
            $apiProtocol->builder('200', $result, '', $this->format);
            $jsonStr = $apiProtocol->get_response();
            if ( $this->format == 'json' && $this->enable_reflect_method) {
                $return_obj = json_decode(json_encode($result));
                $this->validReturnJson($reflectMethod, $apiProtocol, $return_obj, $jsonStr);
            }
            echo $jsonStr;
            if ( ( $this->app_status == 'development' || $this->app_status == 'test' ) && $this->enable_write_req_log ) {
                f($this->app_path . 'tmp/' . date('Y-m-d') . 'response.log', date('H:i:s') . ': '
                    . var_export($result, true) . "\n\n", FILE_APPEND);
            }
            closeResources();

        } catch ( XphpCoreException  $e ) { // 捕获开发框架异常

            $this->handleXphpException($e);

        } catch ( XphpLogicException  $e ) { // 捕获自定义异常

            $this->handleApiException($apiProtocol, $e);

        } catch ( \PDOException $e ) { // 捕获数据库异常

            $this->handleApiException($apiProtocol, $e);

        } catch ( \Exception $e ) {   // 捕获全局异常
            $this->handleApiException($apiProtocol, $e);
        }
    }

    /**
     * 处理网页的路由
     * @throws \Exception \PDOException logicException
     */
    private function routeCtrl()
    {
        if ( PHP_SAPI !== "cli" ) {
            header('Content-type: text/html; charset=utf-8');
            // 禁止缓存
            header('Expires: 0');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
            header('Cache-Control: no-store, no-cache, must-revalidate, pre-check=0, post-check=0, max-age=0');
            header('Pragma: no-cache');
        }

        // 自定义session处理
        $this->sessionHandler();
        try {
            // sql注入检查
            if ( $this->enable_filter_sql_inject ) {
                $filterSqlInject = new FilterSqlInject($this->storage_path . '/log/sql_inject/');
                $filterSqlInject->filterInput();
            }
            $ctrlArr = explode('.', $this->ctrl);
            $ctrl = is_array($ctrlArr) ? end($ctrlArr) : $this->ctrl;
            unset($ctrlArr);
            $ctrl = ucfirst($ctrl);
            $ctrl = preg_replace_callback('/_+([a-z])/', function ($matches) {
                return strtoupper($matches[1]);
            }, $ctrl);

            $ctrlClass = 'main\\' . $this->current_app . '\\ctrl\\' . $ctrl;
            if ( !empty($this->mod) ) {
                $ctrlClass = 'main\\' . $this->current_app . '\\ctrl\\' . $this->mod . '\\' . $ctrl;
            }
            //var_dump($ctrlClass);
            if ( !class_exists($ctrlClass) ) {
                throw new \Exception($ctrlClass . ' class  no found;', 500);
            }

            $ctrlObj = new $ctrlClass();
            $method = $this->method;
            unset($ctrlClass);

            // 检查对象方法是否存在
            if ( !method_exists($ctrlObj, $method) ) {
                $method = preg_replace_callback('/_+([a-z])/', function ($matches) {
                    return strtoupper($matches[1]);
                }, $method);
                if ( !method_exists($ctrlObj, $method) ) {
                    throw new \Exception($this->ctrl . '->' . $method . ' no found;', 404);
                }
            }

            // 是否启用安全映射机制
            $this->securityMapCheck('ctrl', $ctrl, $method);


            // 通过反射获取调用方法的参数列表
            if ( $this->enable_reflect_method ) {
                $reflectMethod = new \ReflectionMethod($ctrlObj, $method);
                $args = [];
                $defaults = [];
                foreach ( $reflectMethod->getParameters() as $param ) {
                    $args[] = $param->getName();
                    $defaults[] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
                }
                if ( empty($args) ) {
                    // 开始执行业务逻辑流程
                    $ctrlRet = call_user_func_array([ $ctrlObj, $method ], []);
                } else {
                    $params = array();
                    foreach ( $args as $ak => $arg ) {
                        $params[$ak] = isset($_REQUEST[$arg]) ? $_REQUEST[$arg] : $defaults[$ak];
                    }
                    // 开始执行业务逻辑流程
                    $ctrlRet = call_user_func_array([ $ctrlObj, $method ], $params);
                }
            } else {
                $ctrlRet = call_user_func_array([ $ctrlObj, $method ], []);
            }
            $this->handleCtrlResult($ctrlRet);
            unset($ctrlObj, $method);
            register_shutdown_function("closeResources");

        } catch ( XphpLogicException $e ) {

            $this->handleCtrlException($e);

        } catch ( \PDOException $e ) {

            $this->handleCtrlException($e);

        } catch ( \Exception $e ) {

            $this->handleCtrlException($e);

        }

    }

    /**
     * 处理Api捕获到的异常
     * @param $api_protocol
     * @param \Exception $exception
     */
    private function handleXphpException($exception)
    {
        $obj = new \stdClass();
        $obj->ret = 500;
        $obj->debug = new \stdClass();
        $obj->time = time();
        $obj->trace = [];
        $obj->data = [ 'key' => $exception->getCode(), 'value' => $exception->getMessage() ];

        if ( $this->format == 'xml' ) {
            header('Content-type: application/xml; charset=utf-8');
            $xmlstr = "<?xml version='1.0'  ?>
                <root> 
                  <ret>%s</ret>
                  <data>%s</data>
                  <time>%d</time>
                  <debug></debug>
                  <trace></trace>
                </root>";
            echo sprintf($xmlstr, '500', $exception->getCode() . ' ' . $exception->getMessage(), time());
            die;
        }
        header('Content-type: application/json; charset=utf-8');
        echo json_encode($obj);
        die;
    }

    private function objectToXml($obj, $dom = 0, $item = 0)
    {
        if ( !$dom ) {
            $dom = new \DOMDocument("1.0");
        }
        if ( !$item ) {
            $item = $dom->createElement("root");
            $dom->appendChild($item);
        }
        foreach ( $obj as $key => $val ) {
            $itemx = $dom->createElement(is_string($key) ? $key : "item");
            $item->appendChild($itemx);
            if ( !is_object($val) ) {
                $text = $dom->createTextNode($val);
                $itemx->appendChild($text);
            } else {
                $this->objectToXml($val, $dom, $itemx);
            }
        }
        return $dom->saveXML();
    }

    private function handleCtrlResult($ret)
    {
        if ( $ret === null ) {
            return;
        }
        register_shutdown_function("closeResources");
        if ( isset($_GET['format']) && $_GET['format'] == 'xml' ) {
            header('Content-type: application/xml; charset=utf-8');
            $ret = (object) $ret;
            echo $this->objectToXml($ret);
            die;
        }
        header('Content-type: application/json; charset=utf-8');
        echo json_encode($ret);
        die;
    }

    /**
     * 处理Api捕获到的异常
     * @param $apiProtocol
     * @param \Exception $exception
     */
    private function handleApiException($apiProtocol, $exception)
    {
        register_shutdown_function("closeResources");
        $apiProtocol->builder((string) $exception->getCode(), [ 'key' => $exception->getCode(), 'value' => $exception->getMessage() ]);
        echo $apiProtocol->get_response();
        // 逻辑异常不记录日志
        if ( strpos(get_class($exception), 'LogicException') !== false ) {
            return;
        }
        $errMsg = $this->cmd . ' ' . $exception->getCode() . ':' . $exception->getMessage() . ",trace:\n" . print_r(debug_backtrace(false, 3), true) . "\n\n";

        $this->logExceptionErr($errMsg);
    }

    /**
     * 处理控制器捕获到的异常
     * @param  \Exception $exception
     */
    private function handleCtrlException($exception)
    {
        register_shutdown_function("closeResources");

        $ajax_protocol_class = sprintf("main\\%s\\protocol\\%s", $this->current_app, $this->ajax_protocol_class);

        if ( isAjaxReq() && class_exists($ajax_protocol_class) || ( isset($_GET['format']) && $_GET['format'] == 'json' ) ) {

            $ajaxProtocol = new $ajax_protocol_class();
            $ajaxProtocol->builder($exception->getCode(), [], $exception->getMessage());
            echo $ajaxProtocol->get_response();
        } else {
            $traces = [];
            if ( $this->enable_trace ) {
                $traces = var_export($exception->getTrace(), true);
            }
            $vars = [];
            $vars['traces']  = $traces;
            $vars['code']    = $exception->getCode();
            $vars['message'] = $exception->getMessage();
            $this->render($this->exception_page, $vars);
        }
        // 逻辑异常不记录日志
        if ( strpos(get_class($exception), 'LogicException') !== false ) {
            return;
        }
        $errMsg = $this->cmd . ' ' . $this->ctrl . '->' . $this->method . ' ' . $exception->getCode() . ':' . $exception->getMessage() . ",trace:\n" . print_r(debug_backtrace(false, 3), true) . "\n\n";
        $this->logExceptionErr($errMsg);
    }

    /**
     * 显示视图
     * @param $tpl
     * @param array $vars
     */
    private function render($tpl, $vars = [])
    {
        extract($vars);
        require_once $tpl;

    }

    /**
     * 检验返回值
     * @param $refectMethod
     * @param $api_protocol
     * @param $json_str
     */
    private function validReturnJson($refectMethod, $api_protocol, $return_obj, &$json_str)
    {
        // 检查属性是否存在并且类型一致
        $comment_string = $refectMethod->getDocComment();
        if ( !$comment_string ) {
            return;
        }

        $pattern = "#@require_type\s+([^*/].*)#";
        preg_match_all($pattern, $comment_string, $matches, PREG_PATTERN_ORDER);

        if ( isset($matches[1][0]) ) {
            $require_obj = json_decode($matches[1][0]);
            if ( $require_obj !== null ) {
                list($valid_ret, $valid_msg) = $this->compareReturnJson($require_obj, $return_obj);
                if ( !$valid_ret ) {
                    $api_protocol->builder('600', [ 'key' => 'return_type_err', 'value' => $valid_msg ]);
                    $json_str = $api_protocol->get_response();
                }
            }
        }

    }

    /**
     * 检查返回值是否符合格式要求
     * @param $require_type_obj
     * @param $return_obj
     * @return array
     */
    private function compareReturnJson($require_type_obj, $return_obj)
    {
        // 检查属性是否存在并且类型一致
        if ( empty($require_type_obj)
            && gettype($require_type_obj) != gettype($return_obj)
        ) {
            return [ false, 'expect  type is ' . gettype($return_obj) . ', but get ' . gettype($require_type_obj) ];
        }
        if ( !empty($require_type_obj) && is_object($require_type_obj) ) {
            foreach ( $require_type_obj as $k => $v ) {
                if ( !isset($return_obj->$k) ) {
                    return [ false, 'property:' . $k . ' not exist' ];
                }
                if ( gettype($v) != gettype($return_obj->$k) ) {
                    return [ false, 'expect ' . $k . ' type is ' . gettype($v) . ', but get ' . gettype($return_obj->$k) ];
                }

                if ( !empty($return_obj->$k) && ( is_array($return_obj->$k) || is_object($return_obj->$k) ) ) {
                    list($ret, $msg) = $this->compareReturnJson($v, $return_obj->$k);
                    if ( !$ret ) {
                        return [ $ret, $msg ];
                    }

                }
            }
        }

        return [ true, '' ];

    }

    /**
     * 控制会话有效期
     */
    private function sessionHandler()
    {
        if ( PHP_SAPI == 'cli' ) {
            return;
        }
        $sessionConfig = $this->getConfigVar('session');

        if ( !empty($sessionConfig['no_session_cmd']) && !in_array($this->cmd, $sessionConfig['no_session_cmd']) ) {

            if ( preg_match('/([^.]+)\.(\D+)$/sim', $_SERVER['HTTP_HOST'], $regs) ) {
                $arr = explode('.', $_SERVER['HTTP_HOST']);
                $cookie_domain = '.' . $arr[count($arr) - 2] . '.' . $arr[count($arr) - 1];
            } else {
                $cookie_domain = $_SERVER['HTTP_HOST'];
            }

            ini_set('session.cookie_domain', $cookie_domain);
            if ( isset($sessionConfig['session.cache_expire']) ) {
                ini_set('session.cache_expire', $sessionConfig['session.cache_expire']);
            }

            if ( isset($sessionConfig['session.gc_maxlifetime']) ) {
                ini_set('session.gc_maxlifetime', $sessionConfig['session.gc_maxlifetime']);
            }

            if ( isset($sessionConfig['session.cookie_lifetime']) ) {
                ini_set('session.cookie_lifetime', $sessionConfig['session.cookie_lifetime']);
            }

            if ( !empty($sessionConfig['session.save_path']) ) {
                ini_set('session.save_path', $sessionConfig['session.save_path']);
            }

            if ( isset($_GET['session_id']) && !empty($_GET['session_id']) ) {
                session_id($_GET['session_id']);
            }

            @session_start();

            $this->session_started = true;
        }
    }

    /**
     * 使用Xhprof记录程序的执行效率
     */
    private function xhprofHandler()
    {
        $outputDir = $this->xhprof_save_path;
        // 判断是否载入xhprof扩展
        if ( !extension_loaded('xhprof') ) {
            return false;
        }

        // 判断是否开启
        if ( !$this->enable_xhprof ) {
            return false;
        }

        if ( mt_rand(1, 100) < $this->xhprof_rate ) {
            if ( isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'xhprof') !== false ) {
                return;
            }
            // start profiling
            xhprof_enable(XHPROF_FLAGS_MEMORY, array(
                'ignored_functions' => array(
                    'call_user_func',
                    'call_user_func_array'
                )
            ));

            $xhprofRoot = $this->xhprof_root ;
            if ( $this->cmd ) {
                $name = $this->cmd;
            } else {
                $name = $this->ctrl . '.' . $this->action;
            }
            $name = str_replace('.', '_', $name);
            if(file_exists($xhprofRoot . "xhprof_lib/utils/xhprof_lib.php")){
                $saveXhprof = create_function('$xhprof_root,$name', '$xhprof_data = xhprof_disable();
                                                                ini_set("xhprof.output_dir",$outputDir);
                                                                include_once $xhprofRoot . "xhprof_lib/utils/xhprof_lib.php";
                                                                include_once $xhprofRoot . "xhprof_lib/utils/xhprof_runs.php";
                                                                $xhprof_runs = new \XHProfRuns_Default();
                                                                $run_id = $xhprof_runs->save_run($xhprof_data, $name);  
        
            ');
                register_shutdown_function($saveXhprof, $xhprofRoot, $name);
            }
            }

    }


    /**
     * 获取配置文件的变量
     * @param string $file
     * @return array
     */
    public function getConfigVar($file)
    {
        $_config = [];

        $absFile = $this->app_path . 'config/' . $this->app_status . '/' . $file . '.cfg.php';

        if ( file_exists($absFile) ) {
            include $absFile;
        }
        return $_config;
    }

    /**
     * 获取通用的配置
     * @param $file
     * @return array
     */
    public function getCommonConfigVar($file)
    {
        $_config = [];

        $absFile = $this->app_path . 'config/' . $file . '.cfg.php';

        if ( file_exists($absFile) ) {
            include $absFile;
        }
        return $_config;
    }

    /**
     * 是否启用安全映射机制
     * @param string $route api|ctrl
     * @param $class
     * @param string $method
     * @throws \Exception
     */
    private function securityMapCheck($route, $class, $method)
    {
        if ( $this->enable_security_map ) {

            $mapConfig = static::getCommonConfigVar('map');

            if ( !isset($mapConfig[$route]) ) {
                unset($mapConfig);
                throw new \Exception("Error: $route in map config undefined ", 500);
            }
            $srvMapConfig = $mapConfig[$route];
            //var_dump($srvMapConfig,$class,$method);
            if ( !empty($this->mod) && $this->mod != 'api' ) {
                $class = $this->mod . '.' . $class;
            }
            if ( !isset($srvMapConfig[$class]) ) {
                unset($mapConfig);
                throw new \Exception("Error: $class in map config undefined ", 501);
            }

            // v($srv_map_config);
            unset($mapConfig);
            if ( $srvMapConfig[$class] == '*' || empty($srvMapConfig[$class]) ) {
                // 可以继续
            } else {
                // 否则被禁止调用
                if ( !in_array($method, $srvMapConfig[$class]) ) {
                    throw new \Exception("Error: {$this->cmd} access forbidden ", 503);
                }
            }
        }
    }


    public function logExceptionErr($errMsg)
    {

        $logPath = $this->storage_path . '/log/exception_err/' . date('y-m');

        if ( !file_exists($logPath) ) {
            @mkdir($logPath);
        }
        if ( is_writable($logPath) ) {
            @file_put_contents($logPath . '/' . date('d') . '.log', $errMsg . "\n");
        }
    }

    public function logErr($errMsg)
    {

        $log_path = $this->storage_path . '/log/runtime_error/' . date('y-m');

        if ( !file_exists($log_path) ) {
            @mkdir($log_path);
        }
        if ( is_writable($log_path) ) {
            @file_put_contents($log_path . '/' . date('d') . '.log', $errMsg . "\n");
        }
    }


}

