<?php

if (!function_exists('p')) {
    /**
     * print_r short name
     * @param $v1
     */
    function p($v1)
    {
        print_r($v1);
    }
}

if (!function_exists('v')) {
    /**
     * var_dump short name
     * @param $v1
     */
    function v($v1)
    {
        var_dump($v1);
    }
}

if (!function_exists('f')) {
    /**
     * same as file_put_contents
     * @param $filename
     * @param $data
     * @param int $flags
     * @return bool|int
     */
    function f($filename, $data, $flags = 0)
    {
        return file_put_contents($filename, $data, $flags);
    }
}

if (!function_exists('trimStr')) {
    /**
     * remove whitespace
     * @param $str
     * @return string
     */
    function trimStr($str)
    {
        $str = trim($str);
        $ret_str = '';
        for ($i = 0; $i < strlen($str); $i++) {
            if (substr($str, $i, 1) != " ") {
                $ret_str .= trim(substr($str, $i, 1));
            } else {
                while (substr($str, $i, 1) == " ") {
                    $i++;
                }
                $ret_str .= " ";
                $i--; // ***
            }
        }
        return $ret_str;
    }
}

if (!function_exists('randString')) {
    /**
     * get random string
     * @param $len
     * @param  $chars
     * @return string
     */
    function randString($len, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789')
    {
        $string = '';
        for ($i = 0; $i < $len; $i++) {
            $pos = rand(0, strlen($chars) - 1);
            $string .= $chars[$pos];
        }
        return $string;
    }
}

if (!function_exists('closeResources')) {
    /**
     * close resources
     */
    function closeResources()
    {
        if (isset($GLOBALS['global_pdo']) && !empty($GLOBALS['global_pdo'])) {
            foreach ($GLOBALS['global_pdo'] as $k => &$pdo) {
                $GLOBALS['global_pdo'][$k] = NULL;
                unset($pdo);
                unset($GLOBALS['global_pdo'][$k]);
            }
        }
        if (function_exists('get_resources')) {

            $res_types = [
                'curl' => 'curl_close', 
                'imap' => 'imap_close',
                'shmop' => 'shmop_close',
                'stream' => 'fclose',
                'xml' => 'xml_parser_free',
                'zlib' => 'gzclose',
                'pdf' => 'PDF_close',

            ];
            if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
                //echo 'php 版本: ' . PHP_VERSION . " 忽略\n";
                return;
            }
            foreach ($res_types as $res_name => $close_function) {
                if (!function_exists($close_function)) {
                    break;
                } 
				try{
					$resources = get_resources($res_name);
					if (!empty($resources)) {
						foreach ($resources as $res) {
							@$close_function($res);
						}
					}
				}catch(\Exception $e){
					// inogre
				}
            }

        }
        //f(TMP_PATH.'/get_resources.log',var_export( get_resources(), true ));
    }
}

if (!function_exists('safeStr')) {
    /**
     * request param filter
     * @param string $str
     * @return string
     */
    function safeStr($str)
    {
        if (!empty($str) && is_string($str)) {
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
}

if (!function_exists('unEs')) {
    /**
     * @param string $str
     * @return string
     */
    function unEs($str)
    {
        if (!empty($str) && is_string($str)) {
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
}

if (!function_exists('es')) {
    /**
     * safe_str short name
     * @param string $str
     * @return string
     */
    function es($str)
    {
        return safeStr($str);
    }
}


if (!function_exists('escapeString')) {
    /**
     *
     * @param string $str
     * @return string
     */
    function escapeString($str)
    {
        return addslashes($str);
    }

}

if (!function_exists('isAjaxReq')) {
    /**
     * check ajax request
     * @return bool
     */
    function isAjaxReq()
    {
        if (isset($_SERVER['CONTENT_TYPE']) && strtolower($_SERVER['CONTENT_TYPE']) == 'application/json') {
            return true;
        }
        $key = 'HTTP_X_REQUESTED_WITH';
        if (isset($_SERVER[$key]) && strtolower($_SERVER[$key]) == 'xmlhttprequest') {
            return true;
        } else {
            return false;
        }
    }
}

if (!function_exists('SafeFilter')) {
    function SafeFilter(&$arr)
    {
        $ra = array('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/', '/script/', '/javascript/', '/vbscript/', '/expression/', '/applet/', '/meta/', '/xml/', '/blink/', '/link/', '/style/', '/embed/', '/object/', '/frame/', '/layer/', '/title/', '/bgsound/', '/base/', '/onload/', '/onunload/', '/onchange/', '/onsubmit/', '/onreset/', '/onselect/', '/onblur/', '/onfocus/', '/onabort/', '/onkeydown/', '/onkeypress/', '/onkeyup/', '/onclick/', '/ondblclick/', '/onmousedown/', '/onmousemove/', '/onmouseout/', '/onmouseover/', '/onmouseup/', '/onunload/');

        if (is_array($arr)) {
            foreach ($arr as $key => $value) {
                if (!is_array($value)) {
                    if (!get_magic_quotes_gpc()) {             //不对magic_quotes_gpc转义过的字符使用addslashes(),避免双重转义。
                        $value = addslashes($value);           //给单引号（'）、双引号（"）、反斜线（\）与 NUL（NULL 字符）加上反斜线转义
                    }
                    $value = preg_replace($ra, '', $value);     //删除非打印字符，粗暴式过滤xss可疑字符串
                    $arr[$key] = htmlentities(strip_tags($value)); //去除 HTML 和 PHP 标记并转换为 HTML 实体
                } else {
                    SafeFilter($arr[$key]);
                }
            }
        }
    }
}

    
