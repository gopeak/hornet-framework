<?php

namespace framework;

use framework\protocol\Api;
use framework\protocol\Ajax;

/**
 * 开发框架核心文件
 *
 */
class HornetEngine
{
    /**
     * 页面上下文所要执行的控制器类名称
     *
     * @var string
     */
    public $ctrl = 'index';

    /**
     * 页面上下文所要执行的控制器方法名称
     *
     * @var string
     */
    public $action = 'index';

    /**
     * Same as $action
     *
     * @var string
     */
    public $method = 'index';

    /**
     * 伪静态的请求参数
     *
     * @var array
     */
    public $target = [];

    /**
     * Api请求要执行的类名称
     *
     * @var string
     */
    public $cmd;

    /**
     * 控制器或Api模块名称
     *
     * @var string
     */
    public $mod;

    /**
     * 所在的绝对路径
     *
     * @var string
     */
    public $frameworkRootPath = '';

    /**
     * 项目的绝对路径
     *
     * @var string
     */
    public $appPath = '';

    /**
     * 项目的存储绝对路径,多服务前期负载均衡时建议使用NFS
     *
     * @var string
     */
    public $storagePath = '';

    /**
     * 项目的开发状态
     *
     * @var string
     */
    public $appStatus = 'development';

    /**
     * Session_start是否启动
     *
     * @var bool
     */
    private $sessionStarted = false;


    /**
     * 自定义重写URL的类
     *
     * @var string
     */
    public $customRewriteClass = '';

    /**
     * 自定义重写URL的函数
     *
     * @var string
     */
    public $customRewriteFunction = '';

    /**
     * 当前的项目目录名称,如app site
     *
     * @var string
     */
    public $currentApp = 'app';

    /**
     * 模板引擎
     * @var string
     */
    public $tplEngine = 'php';

    /**
     * 控制器方法前缀
     * @var string
     */
    public $ctrlMethodPrefix = '';

    /**
     * 在api调用时是否返回调用堆栈
     *
     * @var bool
     */
    public $enableTrace = false;

    /**
     * 是否记录api请求日志
     *
     * @var bool
     */
    public $enableWriteReqLog = false;

    /**
     * 是否启用xphpof性能分析
     *
     * @var bool
     */
    public $enableXhprof = false;

    /**
     * 触发xphprof性能分析的概率,千分之..
     *
     * @var int
     */
    public $xhprofRate = 1;

    /**
     * Xphprof 日志存储路径
     *
     * @var string
     */
    public $xhprofSavePath = '';

    /**
     * Xhprof web分析库目录
     *
     * @var string
     */
    public $xhprofRoot = '';

    /**
     * Xhprof 日志文件
     *
     * @var string
     */
    public $xhprofRunId = '';

    /**
     * 是否启用访问路由检查,如果启用,只有在 condig/{$app_status}/map.cfg.php 定义的路由才允许访问
     *
     * @var bool
     */
    public $enableSecurityMap = true;

    /**
     * 是否进行Xss攻击过滤
     *
     * @var bool
     */
    public $enableXssFilter = false;

    /**
     * 是否启用反射功能
     *
     * @var bool
     */
    public $enableReflectMethod = true;

    /**
     * Whether the write error log is enabled
     *
     * @var bool
     */
    public $enableErrorLog = true;

    /**
     * Api调用时返回的数据格式
     *
     * @var string
     */
    private $format = 'json';

    /**
     * 处理返回值的协议类
     *
     * @var string
     */
    public $apiProtocolClass = 'framework\\protocol\\Api';


    /**
     * 处理返回值的协议类
     *
     * @var string
     */
    public $ajaxProtocolClass = 'framework\\protocol\\Ajax';

    /**
     *  是否自动检查sql注入检查
     *
     * @var bool
     */
    public $enableFilterSqlInject = true;

    /**
     * 显示页面
     *
     * @var string
     */
    public $exceptionPage = 'exception.php';

    /**
     *  当路由以api开头则表明为api请求
     *
     * @var string
     */
    public $modApiName = 'api';


    /**
     * HornetEngine constructor.
     *
     * @param object $config this object property
     */
    public function __construct($config)
    {
        $curPath = realpath(dirname(__FILE__));
        // init app path
        $this->appPath = $curPath . '/app/';

        // 进行外部注入内部属性
        foreach ($config as $k => $v) {
            if (isset($this->$k)) {
                $this->$k = $v;
            }
        }
        if (!in_array(substr($this->appPath, -1), ['/', '\\'])) {
            $this->appPath .= '/';
        }
        // init storage path
        if (!isset($config->storagePath)) {
            $this->storagePath = $this->appPath . 'storage/';
        }
        // init xhprof log path
        if (!isset($config->xhprofSavePath)) {
            $this->xhprofSavePath = $this->storagePath . 'xhprof/';
        }

        if (!isset($config->xphpRootPath)) {
            $this->frameworkRootPath = realpath(dirname(__FILE__)) . '/';
        }
        // exception display page
        if (!isset($config->exceptionPage)) {
            $this->exceptionPage = $this->frameworkRootPath . $config->exceptionPage;
        }

        // require response format
        if (isset($_REQUEST['format'])) {
            $this->format = es(trimStr($_REQUEST['format']));
        }

        if ($this->enableXssFilter) {
            $_GET && SafeFilter($_GET);
            $_POST && SafeFilter($_POST);
            $_POST && SafeFilter($_REQUEST);
            $_COOKIE && SafeFilter($_COOKIE);
        }

        // custom error handler
        $errHandler = new ErrorHandler($this);
        set_error_handler(array($errHandler, 'errorHandler'));

        // api param
        $this->cmd = isset($_REQUEST['cmd']) ? trim($_REQUEST['cmd']) : '';

        // url rewrite
        $this->rewrite();

        // 伪静态参数放入全局变量,这是使用全局变量两个地方之一
        $_GET['_target'] = $this->target;

        // 性能分析日志
        $this->xhprofHandler();

        spl_autoload_register([$this, 'autoload']);
    }

    /**
     * fetch current property value
     *
     * @param $name
     *
     * @return mixed
     */
    public function getProperty($name)
    {
        if (isset($this->$name)) {
            return $this->$name;
        }
        if (isset(static::$name)) {
            return static::$name;
        }
        return null;
    }

    /**
     * Url rewrite
     *
     * @return array
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
        if (false !== ($qm = strpos($uri, '?'))) {
            $uri = substr($uri, 0, $qm);
        }
        $rewrite = trim(substr($uri, strlen($base)), '/');
        $params = false !== strpos($rewrite, '/') ? explode('/', $rewrite) : ($rewrite ? [0 => $rewrite] : []);
        $ret['target'] = $ret['params'] = $params;

        $common_config = static::getCommonConfigVar('common');

        if (!empty($params) && count($params) > 0) {
            // 模块是否已经在common.cfg.php中定义
            $common_config['mods'][] = $this->modApiName;
            if (in_array($params[0], $common_config['mods'])) {
                // 支持 api的子模块
                if ($params[0] == $this->modApiName) {
                    if (count($params) > 3 && in_array($params[1], $common_config['mods'])) {
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
                if (!isset($params[1]) || strpos($params[1], '.html') !== false) {
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
        return $ret;
    }

    /**
     * 开发框架 路由分发，动态调用方法以及构建返回
     * @return void
     * @throws \Exception
     */
    public function route()
    {
        if (!empty($this->mod)) {
            if ($this->mod == $this->modApiName) {
                $this->cmd = $this->ctrl . '.' . $this->action;
            }
        }
        if ($this->cmd != '') {
            $cmdParams = explode('.', $this->cmd);
            if (count($cmdParams) > 2) {
                $this->mod = $cmdParams[0];
                $this->cmd = $cmdParams[1] . '.' . $cmdParams[2];
            }
            $this->routeApi();
        } else {
            $this->routeCtrl();
        }
    }

    /**
     * Autoload class
     *
     * @param string $class Class name
     *
     * @return void
     */
    private function autoload($class)
    {
        $class = str_replace('main\\', '', $class);
        //var_dump($class );
        $file = realpath(dirname($this->appPath . '/../../')) . '/' . $class . '.php';
        $file = str_replace(['\\', '//'], ['/', '/'], $file);
        //var_dump($file );
        if (is_file($file)) {
            include_once $file;
            return;
        }
    }

    /**
     * 处理api请求路由
     *
     * @return void
     * @throws \Exception \PDOException logicException
     *
     */
    private function routeApi()
    {
        // get protocol class
        if ($this->apiProtocolClass != "framework\\protocol\\Api") {
            try {
                $api_protocol_class = sprintf("main\\%s\\protocol\\%s", $this->currentApp, $this->apiProtocolClass);
                if (!class_exists($api_protocol_class)) {
                    throw new HornetCoreException($api_protocol_class . ' no found', 500);
                }
            } catch (HornetCoreException $exception) {
                $apiProtocol = new Api($this->enableTrace);
                $this->handleApiException($apiProtocol, $exception);
                return;
            }
            $apiProtocol = new $api_protocol_class($this->enableTrace);
        } else {
            $apiProtocol = new Api($this->enableTrace);
        }

        // url route to controller
        try {
            // sql注入检查
            if ($this->enableFilterSqlInject) {
                $filterSqlInject = new FilterSqlInject($this->storagePath . '/log/sql_inject/');
                $filterSqlInject->filterInput();
            }
            if (!strpos($this->cmd, '.')) {
                throw new \Exception('Api invoker error: cmd param error!', 500);
            }
            list ($service, $method) = explode('.', $this->cmd);
            $service = ucfirst($service);
            $service = $this->underlineToUppercase($service);
            $serviceClass = sprintf("main\\%s\\api\\%s", $this->currentApp, $service);
            if (!empty($this->mod) && $this->mod != 'api') {
                $serviceClass = sprintf("main\\%s\\api\\%s\\%s", $this->currentApp, $this->mod, $service);
            }

            $service_obj = new $serviceClass();

            if (!method_exists($service_obj, $method)) {
                $method = $this->underlineToUppercase($method);
                if (!method_exists($service_obj, $method)) {
                    throw new \Exception($this->ctrl . '->' . $method . ' no found;', 404);
                }
            }

            // 安全映射机制
            $this->securityMapCheck($this->modApiName, $service, $method);

            if (($this->appStatus == 'development' || $this->appStatus == 'test') && $this->enableWriteReqLog) {
                $reqLogPath = $this->storagePath . 'tmp/' . date('Y-m-d') . '_request.log';
                $dateTime = date('H:i:s');
                $getStr = var_export($_GET, true);
                $postStr = var_export($_POST, true);
                $cookieStr = var_export($_COOKIE, true);
                $logContent = $dateTime . ': ' . $getStr . $postStr . $cookieStr . "\n\n";
                f($reqLogPath, $logContent, FILE_APPEND);
                unset($dateTime, $getStr, $postStr, $cookieStr, $logContent);
            }
            $reflectMethod = null;
            // 通过反射获取调用方法的参数列表
            if ($this->enableReflectMethod) {
                $reflectMethod = new \ReflectionMethod($service_obj, $method);
                $args = [];
                $defaults = [];
                foreach ($reflectMethod->getParameters() as $param) {
                    $args[] = $param->getName();
                    $defaults[] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
                }
                if (empty($args)) {
                    // 开始执行业务逻辑流程
                    $result = call_user_func_array([$service_obj, $method], []);
                } else {
                    $params = array();
                    foreach ($args as $ak => $arg) {
                        $params[$ak] = isset($_REQUEST[$arg]) ? $_REQUEST[$arg] : $defaults[$ak];
                    }
                    // 开始执行业务逻辑流程
                    $result = call_user_func_array([$service_obj, $method], $params);
                }
            } else {
                $result = call_user_func_array([$service_obj, $method], []);
            }

            // 执行结束检验结果类型
            $apiProtocol->builder('200', $result, '', $this->format);
            $jsonStr = $apiProtocol->getResponse();
            if ($this->format == 'json' && $this->enableReflectMethod) {
                $return_obj = json_decode(json_encode($result));
                $this->validReturnJson($reflectMethod, $apiProtocol, $return_obj, $jsonStr);
            }
            echo $jsonStr;
            if (($this->appStatus == 'development' || $this->appStatus == 'test') && $this->enableWriteReqLog) {
                $reqLogPath = $this->storagePath . 'tmp/' . date('Y-m-d') . '_request.log';
                $datetime = date('H:i:s');
                $getStr = var_export($_GET, true);
                $postStr = var_export($_POST, true);
                $cookieStr = var_export($_COOKIE, true);
                $logContent = $datetime . ': ' . $getStr . $postStr . $cookieStr . "\n\n";
                unset($datetime, $getStr, $postStr, $cookieStr);
                f($reqLogPath, $logContent, FILE_APPEND);
            }
            closeResources();
        } catch (HornetCoreException  $e) {
            $this->handleApiException($apiProtocol, $e);
        } catch (HornetLogicException  $e) {
            $this->handleApiException($apiProtocol, $e);
        } catch (\PDOException $e) {
            $this->handleApiException($apiProtocol, $e);
        } catch (\Exception $e) {
            $this->handleApiException($apiProtocol, $e);
        }
    }

    /**
     * 处理网页的路由
     *
     * @return void
     * @throws \Exception \PDOException logicException
     *
     */
    private function routeCtrl()
    {
        if (PHP_SAPI !== "cli") {
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
            if ($this->enableFilterSqlInject) {
                $filterSqlInject = new FilterSqlInject($this->storagePath . '/log/sql_inject/');
                $filterSqlInject->filterInput();
            }
            $this->customRewrite();

            $ctrlArr = explode('.', $this->ctrl);
            $ctrl = is_array($ctrlArr) ? end($ctrlArr) : $this->ctrl;
            unset($ctrlArr);
            $ctrl = ucfirst($ctrl);
            $ctrl = $this->underlineToUppercase($ctrl);

            $ctrlClass = 'main\\' . $this->currentApp . '\\ctrl\\' . $ctrl;
            if (!empty($this->mod)) {
                $ctrlClass = 'main\\' . $this->currentApp . '\\ctrl\\' . $this->mod . '\\' . $ctrl;
            }
            //var_dump($ctrlClass);
            if (!class_exists($ctrlClass)) {
                throw new \Exception($ctrlClass . ' class  no found;', 500);
            }

            $ctrlObj = new $ctrlClass();
            $method = $this->method;
            $dataType = 'html';
            if (isset($_GET['data_type'])) {
                $dataType = $_GET['data_type'];
            }

            //var_dump($method);die;
            unset($ctrlClass);

            // 检查对象方法是否存在
            if (!method_exists($ctrlObj, $method)) {
                $method = $this->underlineToUppercase($method);
                if (!method_exists($ctrlObj, $method)) {
                    if (isset($this->ctrlMethodPrefix)) {
                        $method = $this->ctrlMethodPrefix . ucfirst($method);
                    }
                }
                if (!method_exists($ctrlObj, $method)) {
                    throw new \Exception($this->ctrl . '->' . $method . ' no found;', 404);
                }
            }

            // 是否启用安全映射机制
            $this->securityMapCheck('ctrl', $ctrl, $method);

            // 通过反射获取调用方法的参数列表
            if ($this->enableReflectMethod) {
                $reflectMethod = new \ReflectionMethod($ctrlObj, $method);
                $args = [];
                $defaults = [];
                foreach ($reflectMethod->getParameters() as $param) {
                    $args[] = $param->getName();
                    $defaults[] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
                }
                if (empty($args)) {
                    // 开始执行业务逻辑流程
                    $ctrlRet = call_user_func_array([$ctrlObj, $method], []);
                } else {
                    $params = array();
                    foreach ($args as $ak => $arg) {
                        $params[$ak] = isset($_REQUEST[$arg]) ? $_REQUEST[$arg] : $defaults[$ak];
                    }
                    // 开始执行业务逻辑流程
                    $ctrlRet = call_user_func_array([$ctrlObj, $method], $params);
                }
            } else {
                $ctrlRet = call_user_func_array([$ctrlObj, $method], []);
            }

            $this->handleCtrlResult($ctrlRet);
            unset($ctrlObj, $method);
            register_shutdown_function("closeResources");
        } catch (HornetLogicException $e) {
            $this->handleCtrlException($e);
        } catch (\PDOException $e) {
            $this->handleCtrlException($e);
        } catch (\Exception $e) {
            $this->handleCtrlException($e);
        }
    }

    private function customRewrite()
    {
        $getRetFunc = function ($callRet) {
            if (!is_array($callRet)) {
                return;
            }
            if (count($callRet) == 2) {
                $this->ctrl = $callRet[0];
                $this->method = $callRet[1];
            }
            if (count($callRet) == 3) {
                $this->ctrl = $callRet[0];
                $this->mod = $callRet[1];
                $this->method = $callRet[2];
            }
        };
        if (empty($this->customRewriteClass) && !empty($this->customRewriteFunction)) {
            $fnc = $this->customRewriteFunction;
            if (!function_exists($fnc)) {
                return false;
            }
            $callRet = $fnc($this);
            $getRetFunc($callRet);
        }
        if (!empty($this->customRewriteClass) && !empty($this->customRewriteFunction)) {
            if (!class_exists($this->customRewriteClass)) {
                return false;
            }
            $classObj = new $this->customRewriteClass();
            $fnc = $this->customRewriteFunction;
            if (!method_exists($classObj, $fnc)) {
                return false;
            }
            $callRet = $classObj->$fnc($this);
            $getRetFunc($callRet);
        }
    }

    /**
     * Underline to uppercase
     *
     * @param string $str string
     *
     * @return string
     */
    private function underlineToUppercase($str)
    {
        $fnc = function ($matches) {
            return strtoupper($matches[1]);
        };
        $str = preg_replace_callback('/_+([a-z])/', $fnc, $str);
        return $str;
    }

    /**
     * Object to xml
     *
     * @param object $obj object
     * @param int $dom dom
     * @param int $item item
     *
     * @return string item
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
        foreach ($obj as $key => $val) {
            $itemXml = $dom->createElement(is_string($key) ? $key : "item");
            $item->appendChild($itemXml);
            if (!is_object($val)) {
                $text = $dom->createTextNode($val);
                $itemXml->appendChild($text);
            } else {
                $this->objectToXml($val, $dom, $itemXml);
            }
        }
        return $dom->saveXML();
    }

    /**
     * Handle controller's result
     *
     * @param string $ret result
     *
     * @return void
     */
    private function handleCtrlResult($ret)
    {
        if ($ret === null) {
            return;
        }
        register_shutdown_function("closeResources");
        if (isset($_GET['format']) && $_GET['format'] == 'xml') {
            header('Content-type: application/xml; charset=utf-8');
            $ret = (object)$ret;
            echo $this->objectToXml($ret);
            die;
        }
        header('Content-type: application/json; charset=utf-8');
        echo json_encode($ret);
        die;
    }

    /**
     * 处理Api捕获到的异常
     *
     * @param protocol\Api $apiProtocol Protocol object
     * @param \Exception $e Exception object
     *
     * @return null
     */
    private function handleApiException($apiProtocol, $e)
    {
        register_shutdown_function("closeResources");
        $apiProtocol->builder((string)$e->getCode(), ['key' => $e->getCode(), 'value' => $e->getMessage()]);
        echo $apiProtocol->getResponse();
        // 逻辑异常不记录日志
        if (strpos(get_class($e), 'LogicException') !== false) {
            return;
        }

        if ($this->enableErrorLog) {
            $cmd = $this->cmd;
            $code = $e->getCode();
            $msg = $e->getMessage();
            $trace = print_r(debug_backtrace(false, 3), true);
            $errMsg = $cmd . ' ' . $code . ':' . $msg . ",trace:\n" . $trace . "\n\n";
            $this->logExceptionErr($errMsg);
        }
    }


    /**
     * 处理控制器捕获到的异常
     * @param \Exception $e
     * @throws \Exception
     */
    private function handleCtrlException(\Exception $e)
    {
        register_shutdown_function("closeResources");

        if (isAjaxReq() || (isset($_GET['format']) && $_GET['format'] == 'json')) {
            $ajax_protocol_class = sprintf("main\\%s\\protocol\\%s", $this->currentApp, $this->ajaxProtocolClass);
            if ($this->ajaxProtocolClass != "framework\\protocol\\Ajax" && class_exists($ajax_protocol_class)) {
                $ajaxProtocol = new $ajax_protocol_class();
            } else {
                $ajaxProtocol = new Ajax();
            }
            $ajaxProtocol->builder($e->getCode(), [], $e->getMessage());
            echo $ajaxProtocol->getResponse();
        } else {
            $traces = [];
            if ($this->enableTrace) {
                $traces = var_export($e->getTrace(), true);
            }
            $vars = [];
            $vars['traces'] = $traces;
            $vars['code'] = $e->getCode();
            $vars['message'] = $e->getMessage();
            $this->render($this->exceptionPage, $vars);
        }
        // 逻辑异常不记录日志
        if (strpos(get_class($e), 'LogicException') !== false) {
            return;
        }
        if ($this->enableErrorLog) {
            $cmd = $this->cmd;
            $code = $e->getCode();
            $msg = $e->getMessage();
            if (isset($_SERVER['argv']) && $_SERVER['argv']) {
                $trace = print_r($_SERVER['argv'], true);
            } else {
                $trace = print_r(debug_backtrace(false, 3), true);

            }
            $errMsg = $cmd . ' ' . $code . ':' . $msg . ",trace:\n" . $trace . "\n\n";
            $this->logExceptionErr($errMsg);
        }
    }

    /**
     * 显示视图
     *
     * @param string $tpl view  name
     * @param array $vars params
     *
     * @return void
     */
    private function render($tpl, $vars = [])
    {
        extract($vars);
        include_once $tpl;
    }

    /**
     * 检验返回值
     *
     * @param \ReflectionMethod $reflectMethod 反射方法
     * @param protocol\Api $apiProtocol protocol object
     * @param object $returnObj Object
     * @param string $jsonStr Match Json string
     *
     * @return void
     */
    private function validReturnJson($reflectMethod, $apiProtocol, $returnObj, &$jsonStr)
    {
        // 检查属性是否存在并且类型一致
        $commentString = $reflectMethod->getDocComment();
        if (!$commentString) {
            return;
        }
        $pattern = "#@require_type\s+([^*/].*)#";
        preg_match_all($pattern, $commentString, $matches, PREG_PATTERN_ORDER);
        if (isset($matches[1][0])) {
            $requireObj = json_decode($matches[1][0]);
            if ($requireObj !== null) {
                list($validRet, $validMsg) = $this->compareReturnJson($requireObj, $returnObj);
                if (!$validRet) {
                    $apiProtocol->builder('600', ['key' => 'return_type_err', 'value' => $validMsg]);
                    $jsonStr = $apiProtocol->getResponse();
                }
            }
        }
    }

    /**
     * 检查返回值是否符合格式要求
     *
     * @param object $requireTypeObj type object
     * @param object $returnObj return object
     *
     * @return array
     */
    private function compareReturnJson($requireTypeObj, $returnObj)
    {
        // 检查属性是否存在并且类型一致
        if (empty($requireTypeObj)
            && gettype($requireTypeObj) != gettype($returnObj)
        ) {
            return [false, 'expect  type is ' . gettype($returnObj) . ', but get ' . gettype($requireTypeObj)];
        }
        if (!empty($requireTypeObj) && is_object($requireTypeObj)) {
            foreach ($requireTypeObj as $k => $v) {
                if (!isset($returnObj->$k)) {
                    return [false, 'property:' . $k . ' not exist'];
                }
                if (gettype($v) != gettype($returnObj->$k)) {
                    return [false, 'expect ' . $k . ' type is ' . gettype($v) . ', but get ' . gettype($returnObj->$k)];
                }
                if (!empty($returnObj->$k) && (is_array($returnObj->$k) || is_object($returnObj->$k))) {
                    list($ret, $msg) = $this->compareReturnJson($v, $returnObj->$k);
                    if (!$ret) {
                        return [$ret, $msg];
                    }
                }
            }
        }
        return [true, ''];
    }

    /**
     * 控制会话有效期
     *
     * @return void
     */
    private function sessionHandler()
    {
        if (PHP_SAPI == 'cli') {
            return;
        }
        $sessionConfig = $this->getCommonConfigVar('session');

        if (!empty($sessionConfig['no_session_cmd']) && !in_array($this->cmd, $sessionConfig['no_session_cmd'])) {
            if (preg_match('/([^.]+)\.(\D+)$/sim', $_SERVER['SERVER_NAME'], $regs)) {
                $arr = explode('.', $_SERVER['SERVER_NAME']);
                $cookieDomain = '.' . $arr[count($arr) - 2] . '.' . $arr[count($arr) - 1];
            } else {
                $cookieDomain = $_SERVER['SERVER_NAME'];
            }
            ini_set('session.cookie_domain', $cookieDomain);
            if (isset($sessionConfig['session.cache_expire'])) {
                ini_set('session.cache_expire', $sessionConfig['session.cache_expire']);
            }

            if (isset($sessionConfig['session.gc_maxlifetime'])) {
                ini_set('session.gc_maxlifetime', $sessionConfig['session.gc_maxlifetime']);
            }

            if (isset($sessionConfig['session.cookie_lifetime'])) {
                ini_set('session.cookie_lifetime', $sessionConfig['session.cookie_lifetime']);
            }

            if (!empty($sessionConfig['session.save_path'])) {
                ini_set('session.save_path', $sessionConfig['session.save_path']);
            }
            if (!empty($sessionConfig['session.gc_probability'])) {
                ini_set('session.gc_probability', $sessionConfig['session.gc_probability']);
            }
            if (isset($_GET['session_id']) && !empty($_GET['session_id'])) {
                session_id($_GET['session_id']);
            }

            @session_start();
            $this->sessionStarted = true;
        }
    }

    /**
     * 使用Xhprof记录程序的执行效率
     */
    private function xhprofHandler()
    {
        // 判断是否载入xhprof扩展
        if (!extension_loaded('xhprof')) {
            return;
        }
        // 判断是否开启
        if (!$this->enableXhprof) {
            return;
        }
        if (mt_rand(1, 1000) < $this->xhprofRate) {
            if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'xhprof') !== false) {
                return;
            }
            $xhprofRoot = $this->xhprofRoot;
            if ($this->cmd) {
                $name = $this->cmd;
            } else {
                $name = $this->ctrl . '.' . $this->action;
            }
            $name = str_replace('.', '_', $name);
            $outputDir = $this->xhprofSavePath;
            // start profiling
            xhprof_enable(XHPROF_FLAGS_NO_BUILTINS | XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);
            // 业务逻辑执行中...
            register_shutdown_function(array(&$this, 'saveXhprof'), $xhprofRoot, $name, $outputDir);
        }
    }

    public function saveXhprof($xhprofRoot, $name, $outputDir)
    {
        $xhprof_data = xhprof_disable();
        if (!file_exists($xhprofRoot . "xhprof_lib/utils/xhprof_lib.php")) {
            return false;
        }
        $child_dir = date('Y-m-d') . '/' . date('H');
        if (!file_exists($outputDir . $child_dir)) {
            @mkdir($outputDir . $child_dir, 0755, true);
        }
        ini_set("xhprof.output_dir", $outputDir . $child_dir);
        include_once $xhprofRoot . "/xhprof_lib/utils/xhprof_lib.php";
        include_once $xhprofRoot . "/xhprof_lib/utils/xhprof_runs.php";
        $xhprof_runs = new \XHProfRuns_Default($outputDir . $child_dir);
        $this->xhprofRunId = $xhprof_runs->save_run($xhprof_data, $name);
        return true;
    }

    /**
     * 获取配置文件的变量
     * @param string $file
     * @return array
     */
    public function getConfigVar($file)
    {
        $_config = [];
        if($file=='session'){
            $_config = $this->getCommonConfigVar($file);
        }
        $absFile = $this->appPath . 'config/' . $this->appStatus . '/' . $file . '.cfg.php';
        if (file_exists($absFile)) {
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

        $absFile = $this->appPath . 'config/' . $file . '.cfg.php';

        if (file_exists($absFile)) {
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
        if ($this->enableSecurityMap) {
            $mapConfig = static::getCommonConfigVar('map');

            if (!isset($mapConfig[$route])) {
                unset($mapConfig);
                throw new \Exception("Error: $route in map config undefined ", 500);
            }
            $srvMapConfig = $mapConfig[$route];
            //var_dump($srvMapConfig,$class,$method);
            if (!empty($this->mod) && $this->mod != 'api') {
                $class = $this->mod . '.' . $class;
            }
            if (!isset($srvMapConfig[$class])) {
                unset($mapConfig);
                throw new \Exception("Error: {$route} {$class} in map config undefined ", 501);
            }

            // v($srv_map_config);
            unset($mapConfig);
            if ($srvMapConfig[$class] == '*' || empty($srvMapConfig[$class])) {
                // 可以继续
            } else {
                // 否则被禁止调用
                if (!in_array($method, $srvMapConfig[$class])) {
                    throw new \Exception("Error: {$this->cmd} access forbidden ", 503);
                }
            }
        }
    }

    public function logExceptionErr($errMsg)
    {

        $logPath = $this->storagePath . '/log/exception_err/' . date('y-m');

        if (!file_exists($logPath)) {
            @mkdir($logPath);
        }
        if (is_writable($logPath)) {
            @file_put_contents($logPath . '/' . date('d') . '.log', $errMsg . "\n");
        }
    }

    public function logErr($errMsg)
    {

        $log_path = $this->storagePath . '/log/runtime_error/' . date('y-m');

        if (!file_exists($log_path)) {
            @mkdir($log_path);
        }
        if (is_writable($log_path)) {
            @file_put_contents($log_path . '/' . date('d') . '.log', $errMsg . "\n");
        }
    }
}
