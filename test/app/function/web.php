<?php
/**
 * Created by PhpStorm.
 * User: sven
 * Date: 2017/6/9 0009
 * Time: 下午 4:22
 */


/*function getParam($name,$defaultValue=null)
{
    return isset($_GET[$name]) ? ($_GET[$name]) : (isset($_POST[$name]) ? ($_POST[$name]) : $defaultValue);
}

function getQuery($name,$defaultValue=null)
{
    return isset($_GET[$name]) ? ($_GET[$name]) : $defaultValue;
}

function getPost($name,$defaultValue=null)
{
    return isset($_POST[$name]) ? ($_POST[$name]) : $defaultValue;
}*/

/**
 * @todo XssFilter
 * @param $name
 * @param null $defaultValue
 * @return mixed
 */
/*function _REQUEST($name,$defaultValue=null)
{
    return isset($_GET[$name]) ? ($_GET[$name]) : (isset($_POST[$name]) ? ($_POST[$name]) : $defaultValue);
}*/

/**
 * @todo XssFilter
 * @param $name
 * @param null $defaultValue
 * @return mixed
 */
/*function _GET($name,$defaultValue=null)
{
    return isset($_GET[$name]) ? ($_GET[$name]) : $defaultValue;
}*/

/**
 * @todo  XssFilter
 * @param $name
 * @param null $defaultValue
 * @return mixed
 */
/*function _POST($name,$defaultValue=null)
{
    return isset($_POST[$name]) ? ($_POST[$name]) : $defaultValue;
}*/

if (!function_exists('request_get')) {
    /**
     * 根据键名获取过滤的前台传递的值
     *
     * @param $key
     * @param null $defalut
     * @return null
     */
    function request_get($key, $defalut = null)
    {
        safeFilter($_REQUEST);
        if (isset($_REQUEST[$key]) && $_REQUEST[$key] == "") {
            return null;
        }
        return data_get($_REQUEST, $key, $defalut);
    }
}

if (!function_exists('request_all')) {
    /**
     * 获取过滤的前台传递的所有值
     */
    function request_all()
    {
        if (empty($_REQUEST) || count($_REQUEST) == 0) {
            return [];
        } else {
            safeFilter($_REQUEST);
            return $_REQUEST;
        }
    }
}

if (!function_exists('request_only')) {
    /**
     * 批量获取对应key的数组
     *
     * @param array|string $keys
     * @return mixed
     */
    function request_only($keys)
    {
        $keys = (func_num_args() > 1 || (func_num_args() == 1 && !is_array($keys))) ? func_get_args() : $keys;

        $results = [];

        safeFilter($_REQUEST);
        $input = $_REQUEST;

        foreach ($keys as $key) {
            \main\app\classes\support\Arr::set($results, $key, data_get($input, $key));
        }

        return $results;
    }
}

if (!function_exists('request_has')) {
    /**
     * 是否有相对应的key值
     *
     * @param string $key
     * @return bool
     */
    function request_has($key)
    {
        if (empty($_REQUEST) || count($_REQUEST) == 0) {
            return false;
        } else if (isset($_REQUEST[$key])) {
            if (is_null($_REQUEST[$key])) {
                return false;
            } elseif (is_string($_REQUEST[$key]) && trim($_REQUEST[$key]) === '') {
                return false;
            } elseif ((is_array($_REQUEST[$key]) || $_REQUEST[$key] instanceof \Countable) && count($_REQUEST[$key]) < 1) {
                return false;
            } elseif (is_array($_REQUEST[$key])) {
                foreach ($_REQUEST[$key] as $value) {
                    if (empty($value)) {
                        return false;
                    }
                }
            }
            return true;
        } else {
            return array_key_exists($key, $_REQUEST);
        }
    }
}

if (!function_exists('current_url')) {
    /**
     * 获取当前url地址
     *
     * @param array $parameters 请求参数，为空，则只返回当前不带参数的uri地址
     * @param boolean $host 是否带域名，true则放回当前完整的url地址
     */
    function current_url($parameters = [], $host = false)
    {
        //获取当前不带参数的uri地址
        if (($index = strpos($_SERVER['REQUEST_URI'], "?")) !== false) {
            $uri = substr($_SERVER['REQUEST_URI'], 0, $index);
        } else {
            $uri = $_SERVER['REQUEST_URI'];
        }

        //加上请求参数
        if (!empty($parameters) && count($parameters) != 0) {
            $uri .= "?" . http_build_query($parameters);
        }

        //完整url地址
        if ($host) {
            $uri = 'http://' . $_SERVER['HTTP_HOST'] . $uri;
        }

        return $uri;
    }
}


/**
 * 将数据库中的相对路径图片转出 完整url格式
 * @param string $img
 * @return string
 */
function process_img_url($img = '')
{
    return UPLOAD_URL . $img;

}

/**
 * 获取上一页的url
 * @param string $match_url
 * @param string $default_url
 * @return string
 */
function get_last_url($match_url = '', $default_url = '')
{

    if (isset($_SERVER['HTTP_REFERER'])) {

        if (!empty($match_url) && strpos($_SERVER['HTTP_REFERER'], $match_url) === false) {
            return $default_url;
        } else {
            return $_SERVER['HTTP_REFERER'];
        }
    }
    return $default_url;
}


/**
 * 生成下拉菜单的option
 * @param $arr
 * @param $selected_value
 */
function make_select_options($arr, $selected_value)
{

    $html = '';
    foreach ($arr as $k => $v) {
        $selected = ($selected_value !== false && $selected_value == $k) ? 'selected' : '';
        $html .= '<option value="' . htmlspecialchars($k) . '" ' . $selected . '>' . htmlspecialchars($v) . '</option>';
    }
    return $html;

}


/**
 * 是否是AJAx提交的
 * @return bool
 */

function isAjax()
{
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        return true;
    } else {
        return false;
    }
}

/**
 * 是否是GET提交的
 */
function isGet()
{
    return $_SERVER['REQUEST_METHOD'] == 'GET' ? true : false;
}

/**
 * 是否是POST提交
 * @return int
 */
function isPost()
{
    return $_SERVER['REQUEST_METHOD'] == 'POST' ? true : false;
}


/**
 * 下载远程图片保存到本地
 * @param type $url
 * @param string $filename
 * @param type $type
 * @return boolean|string
 */
function get_image($url, $filename = '', $type = 0)
{
    if ($url == '') {
        return false;
    }
    if ($filename == '') {
        $ext = strrchr($url, '.');
        if ($ext != '.gif' && $ext != '.jpg' && $ext != '.png') {
            return false;
        }
        $filename = time() . $ext;
    }
    //文件保存路径
    if ($type) {
        $ch = curl_init();
        $timeout = 5;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $img = curl_exec($ch);
        curl_close($ch);
    } else {
        ob_start();
        readfile($url);
        $img = ob_get_contents();
        ob_end_clean();
    }
    //文件大小
    $fp2 = @fopen($filename, 'a');
    fwrite($fp2, $img);
    fclose($fp2);
    return $filename;
}

function is_image_url($url)
{
    $path = parse_url($url, PHP_URL_PATH);
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $image_exts = array("jpg", "gif", "png", "jpeg", "bmp");
    return in_array($ext, $image_exts);
}

function is_video_url($url)
{
    $path = parse_url($url, PHP_URL_PATH);
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $video_exts = array("mp4", "ogg", "3gp", "mov");
    return in_array($ext, $video_exts);
}


function getCookieHost()
{
    $host = str_replace('www', '', parse_url(ROOT_URL, PHP_URL_HOST));
    //v( $host );
    return $host;
}


/**
 * 获取IP地址
 */
function get_ip()
{
    if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
        $ip = getenv("HTTP_CLIENT_IP");
    else
        if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        else
            if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
                $ip = getenv("REMOTE_ADDR");
            else
                if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
                    $ip = $_SERVER['REMOTE_ADDR'];
                else
                    $ip = "unknown";
    return ($ip);
}
/**
 * ajax分页公共函数
 * @param int $pages 总页数
 * @param int $page 当前页数
 * @param int $page_size 每页显示数
 * @return  string  返回分页的HTML
 */
function getPageStrByAjax($pages, $page, $page_size)
{
    $getLinkTag = function ($page, $page_text = '', $ext = '') {
        if ($page_text == '') {
            $page_text = strval($page);
        }
        return sprintf(
            "<a href='javascript:'  page='%d' %s >%s</a>",
            $page, $ext, $page_text
        );
    };

    $page_str = "";
    if ( $pages > 1) {
        $next = $page + 1;
        if ($page == $pages) $next = $pages;
        $pre = $page - 1;
        if ($page == 1) $pre = 1;
        $page_str = '';
        $page_str .= '<span>' . $page_size . '条/页 当前第' . $page . '页/共 ' . $pages . ' 页</span>';
        $page_str .= $getLinkTag($pre, '&lt;');
        if ( $pages > 9) {
            if ($page <= 3) {
                for ($i = 1; $i < $page; $i++) {
                    $cur = '';
                    if( $i==$page ){
                        $cur = 'class="current"';
                    }
                    $page_str .= $getLinkTag( $i , $i, $cur );
                }
            } else {
                $page_str .= $getLinkTag(intval($page - 3));
                $page_str .= $getLinkTag(intval($page - 2));
                $page_str .= $getLinkTag(intval($page - 1));
            }
            $page_str .= $getLinkTag($page, $page, ' class="current" ');
            if ( ( $pages - $page ) > 3) {
                $page_str .= $getLinkTag(intval($page + 1));
                $page_str .= $getLinkTag(intval($page + 2));
                $page_str .= $getLinkTag(intval($page + 3));

            } else {
                for ($i = $page + 1; $i <= ($pages); $i++) {
                    $page_str .= $getLinkTag(intval($i));
                }
            }
        } else {
            for ($i = 1; $i <= $pages; $i++) {
                $cur = '';
                if( $i==$page ){
                    $cur = 'class="current"';
                }
                $page_str .= $getLinkTag(intval($i) ,$i ,$cur);

            }
        }
        $page_str .= $getLinkTag(intval($next), '&gt;');
        $page_str .= ' 
	         <label>到<input type="text" value="" class="page-num" name="page_go_num" id="page_go_num">页</label>
        <input type="button" value="GO"  class="page-go btn btn-white" >;';

    }
    $page_str .= '';
    return $page_str;

}
