<?php

/**
 * 开发过程中常用的函数
 * @author 部分代码来之互联网
 */



/**
 * +----------------------------------------------------------
 * 系统自动加载类库
 * +----------------------------------------------------------
 * 
 * @param string $classname
 * 对象类名
 * @return void 
 */
function xphp_autoload($class)
{
   
    $file = APP_PATH .DS. $class . '.php';
    $file = str_replace( 'main\\', '', $file );
    $file = str_replace( '\\', DS, $file );
   var_dump($file);
    if ( is_file($file) ) {
        require_once $file;
        return;
    }
    
}


/**
 * 简化print_r
 */
function p($v1)
{
    print_r($v1);
}

/**
 * 简化var_dump
 */
function v($v1)
{
    var_dump($v1);
}

/**
 * 简化版 file_put_contents
 */
function f($filename, $data, $flags = 0)
{
    return file_put_contents($filename, $data, $flags);
}

/**
 * 去除空格函数
 * 
 * @param $str
 * @return string
 */
function trimStr($str)
{
    $str = trim($str);
    $ret_str = '';
    for ($i = 0; $i < strlen($str); $i ++) {
        if (substr($str, $i, 1) != " ") {
            $ret_str .= trim(substr($str, $i, 1));
        } else {
            while (substr($str, $i, 1) == " ") {
                $i ++;
            }
            $ret_str .= " ";
            $i --; // ***
        }
    }
    return $ret_str;
}

/**
 * 获取随机字符串
 * @param $len
 * @param  $chars
 * @return string
 */
function rand_string($len, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789')
{
    $string = '';
    for ($i = 0; $i < $len; $i ++) {
        $pos = rand(0, strlen($chars) - 1);
        $string .= $chars{$pos};
    }
    return $string;
}

/**
 * 手动关闭数据库连接对象
 */
function closeResources()
{
    // 关闭主数据库PDO连接对象
    if (isset($GLOBALS['global_pdo']) && ! empty($GLOBALS['global_pdo'])) {
        foreach ($GLOBALS['global_pdo'] as $k=> &$pdo) {
            $GLOBALS['global_pdo'][$k] = NULL;
            unset( $pdo );
            unset( $GLOBALS['global_pdo'][$k] );
        }
    }
    // 关闭其他资源 php7才有此函数
    if( function_exists('get_resources') ){

        $res_types = [
            'curl'=>'curl_close',
            'gd'=>'imagedestroy',
            'imap'=>'imap_close',
            'pdf'=>'PDF_close',
            'shmop'=>'shmop_close',
            'stream'=>'fclose',
            'xml'=>'xml_parser_free',
            'zlib'=>'gzclose',
            'pdf'=>'PDF_close',

        ];
        foreach ( $res_types as $res_name => $close_function ){
            if( !function_exists( $close_function ) ){
                break;
            }
            $resources = get_resources( $res_name ) ;
            if( !empty($resources) ){
                foreach( $resources as $res ){
                    @$close_function( $res );
                }
            }
        }

    }
    //f(TMP_PATH.'/get_resources.log',var_export( get_resources(), true ));
}


/**
 * 自定义安全函数
 * 
 * @param string $str            
 * @return string
 */
function safe_str($str)
{
    if (! empty($str) && is_string($str)) {
        return str_replace(array(
            '\\',
            "\0",
            "\n",
            "\r",
            "'",
            '"',
            "\x1a"
        ), array(
            '\\\\',
            '\\0',
            '\\n',
            '\\r',
            "\\'",
            '\\"',
            '\\Z'
        ), $str);
    }
    
    return $str;
}

/**
 * 反解es
 * 
 * @param string $str            
 * @return string
 */
function un_es($str)
{
    if (! empty($str) && is_string($str)) {
        return str_replace(array(
            '\\\\',
            '\\0',
            '\\n',
            '\\r',
            "\\'",
            '\\"',
            '\\Z'
        ), array(
            '\\',
            "\0",
            "\n",
            "\r",
            "'",
            '"',
            "\x1a"
        ), $str);
    }
    
    return $str;
}

/**
 * mysql_escape_string简写
 * 
 * @param string $str            
 * @return string
 */
function es($str)
{
    return @safe_str($str);
}



/**
 * SQL指令安全过滤
 * 
 * @access public
 * @param string $str SQL指令
 * @return string
 */
function escapeString($str)
{
    return addslashes($str);
}

/**
 * 是否是AJAx提交的
 * @return bool
 */

function isAjaxReq()
{
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        return true;
    } else {
        return false;
    }
}
    
