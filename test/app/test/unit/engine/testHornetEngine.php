<?php

/**
 * DbModel测试类
 * User: sven
 * Date: 2017/7/15 0015
 * Time: 下午 5:24
 */
class testHornetEngine extends PHPUnit_Framework_TestCase
{

    public static function setUpBeforeClass()
    {

    }

    public static function tearDownAfterClass()
    {

    }

    /**
     * 测试构造函数
     */
    public function testConstruct(  )
    {
        // 初始化开发框架基本设置
        $config = new \stdClass();
        $config->currentApp = APP_NAME;
        $config->appPath = APP_PATH;
        $config->appStatus = APP_STATUS;
        $config->enableTrace = ENABLE_TRACE;
        $config->enableXhprof = ENABLE_XHPROF;
        $config->xhprofRate = XHPROF_RATE;
        $config->enableWriteReqLog = WRITE_REQUEST_LOG;
        $config->enableSecurityMap = SECURITY_MAP_ENABLE;
        $config->exceptionPage = VIEW_PATH.'exception.php';

        // 实例化开发框架对象
        require_once PRE_APP_PATH.'/../src/framework/bootstrap.php';
        $engine = new  \framework\HornetEngine( $config );

        foreach(  $config as $k=>$v ) {

            if(   $engine->getProperty($k)===null ) {
                $this->fail( '$engine '. $k.' no found' );
            }
            if( $engine->getProperty($k)!==null &&  $v != $engine->getProperty($k) ) {
                $this->fail( '$engine '.$k.' expect equal '.$v.',but get '.$engine->$k );
            }
        }


    }

}
