<?php


function unit_set($key)
{

    return false;
}

function getConfigVar($file)
{
    $_config = [];
    $_file = APP_PATH . 'config/' . APP_STATUS . '/' . $file . '.cfg.php';

    if (file_exists($_file)) {
        include $_file;
    } else {
        if (APP_STATUS == 'development') {
            include APP_PATH . 'config/development/' . $file . '.cfg.php';
        } else {
            include APP_PATH . 'config/deploy/' . $file . '.cfg.php';
        }
    }
    return $_config;
}

function get_config_var($file)
{
    return getConfigVar($file);
}


function dump($vars, $output = TRUE, $show_trace = FALSE)
{

    if (TRUE == $show_trace) { // 显示变量运行路径
        $content = htmlspecialchars(print_r($vars, true));
    } else {
        $content = "<div align=left><pre>\n" . htmlspecialchars(print_r($vars, true)) . "\n</pre></div>\n";
    }
    if (TRUE != $output) {
        return $content;
    } // 直接返回，不输出。 
    echo "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"></head><body>{$content}</body></html>";
    return;
}


/**
 * 判断是否来自微信
 * @return bool
 */
function is_weixin()
{
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
        return true;
    }
    return false;
}


/*
 * @author lory
 * @email 1609405705@qq.com
 * @date 2015-09-16
 *
 */
function send_mail($to = "121642038@qq.com", $subject = '', $body = '')
{

    $config = getConfigVar('mail');
    header("content-type:text/html;charset=utf-8");
    ini_set("magic_quotes_runtime", 0);
    require_once PRE_ROOT_PATH . '/vendor/phpmailer/phpmailer/PHPMailerAutoload.php';
    $ret = false;
    $msg = '';

    //file_put_contents( APP_PATH. 'test/email.log',$to.$subject.$body ,FILE_APPEND );
    try {
        $mail = new \PHPMailer(true);
        $mail->IsSMTP();
        $mail->CharSet = 'UTF-8'; //设置邮件的字符编码，这很重要，不然中文乱码
        $mail->SMTPAuth = true; //开启认证
        $mail->Port = $config['port'];
        $mail->SMTPDebug = 0;
        $mail->Host = $config['host'];    //"smtp.exmail.qq.com";
        $mail->Username = $config['username'];     // "chaoduo.wei@ismond.com";
        $mail->Password = $config['password'];     // "Simarui123";
        $mail->Timeout = isset($config['timeout']) ? $config['timeout'] : 20;
        $mail->From = $config['username'];
        $mail->FromName = $config['username'];
        $mail->AddAddress($to);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = "To view the message, please use an HTML compatible email viewer!"; //当邮件不支持html时备用显示，可以省略
        $mail->WordWrap = 80; // 设置每行字符串的长度
        //$mail->AddAttachment("f:/test.png"); //可以添加附件
        $mail->IsHTML(true);
        $ret = $mail->Send();
        if (!$ret) {
            $msg = 'Mailer Error: ' . $mail->ErrorInfo;
        }

    } catch (phpmailerException $e) {
        $msg = "邮件发送失败：" . $e->errorMessage();
    }

    return [$ret, $msg];
}


function send_sms($phone, $message)
{
    $url = 'https://sms.yunpian.com/v2/sms/single_send.json';
    $apikey = 'b2cd304e3232001b15621c414021dc80';
    //$data = array('apikey'=>$apikey, 'mobile'=>$sales_phone, 'text'=>'【闪盟珠宝】万宝提醒：收到了新的订单，请速查看。');
    $data = array('apikey' => $apikey, 'mobile' => $phone, 'text' => $message);
    $res = yunpian_curl($url, $data);
}


/**
 * 价格格式化，四舍五入的方式
 * @param $price              价格，纯数字形式
 * @param int $decimals 规定多少个小数
 * @param null $format 单位换算，如输入数字10000，则换算为XX万，null则表示不进行单位换算
 * @param string $separator 千位分隔符，空字符则不显示分隔符.
 * @return bool|string
 */
function price_format($price, $decimals = 2, $format = null, $separator = "")
{
    if (!is_numeric($price)) {
        return false;
    }
    if (!empty($format) && !is_integer($format)) {
        return false;
    }

    $unit = "";
    if ($format != null) {
        $cnygrees = array("拾", "佰", "仟", "万", "拾万", "佰万", "仟万", "亿");
        $price = round($price / $format, $decimals);
        $index = 0;
        $quotient = $format / 10;
        while ($quotient >= 10) {
            $quotient /= 10;
            $index++;
        }
        $unit = $cnygrees[$index];
    }

    return number_format($price, $decimals, '.', $separator) . $unit;
}

if (!function_exists('tap')) {
    /**
     * Call the given Closure with the given value then return the value.
     *
     * @param  mixed $value
     * @param  callable|null $callback
     * @return mixed
     */
    function tap($value, $callback = null)
    {
        if (is_null($callback)) {
            return new \main\app\classes\support\HigherOrderTapProxy($value);
        }

        $callback($value);

        return $value;
    }
}

if (!function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @param  mixed $value
     * @return mixed
     */
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}

if (!function_exists('safeFilter')) {
    /**
     * 防注入和XSS攻击通用过滤
     * @param $arr
     */
    function safeFilter(&$arr)
    {
        $ra = array('/[\\x00-\\x08\\x0b-\\x0c\\x0e-\\x1f]/', '/script/', '/javascript/', '/vbscript/', '/expression/', '/applet/', '/meta/', '/xml/', '/blink/', '/link/', '/style/', '/embed/', '/object/', '/frame/', '/layer/', '/title/', '/bgsound/', '/base/', '/onload/', '/onunload/', '/onchange/', '/onsubmit/', '/onreset/', '/onselect/', '/onblur/', '/onfocus/', '/onabort/', '/onkeydown/', '/onkeypress/', '/onkeyup/', '/onclick/', '/ondblclick/', '/onmousedown/', '/onmousemove/', '/onmouseout/', '/onmouseover/', '/onmouseup/', '/onunload/');
        if (is_array($arr)) {
            foreach ($arr as $key => $value) {
                if (!is_array($value)) {
                    //json格式不进行转义
                    if (is_json($value)) {
                        continue;
                    }
                    //不对magic_quotes_gpc转义过的字符使用addslashes(),避免双重转义。
                    if (!get_magic_quotes_gpc()) {
                        //给单引号（'）、双引号（"）、反斜线（\）与 NUL（NULL 字符）加上反斜线转义
                        $value = addslashes($value);
                    }
                    //删除非打印字符，粗暴式过滤xss可疑字符串
                    $value = preg_replace($ra, '', $value);
                    //去除 HTML 和 PHP 标记并转换为 HTML 实体
                    $arr[$key] = htmlentities(strip_tags($value));
                } else {
                    safeFilter($arr[$key]);
                }
            }
        }
    }
}

if (!function_exists('price')) {
    /**
     * 价格格式化
     * 个位数逢4,7加一
     *
     * @param $price
     */
    function price($price)
    {
        if (!is_integer($price)) {
            $price = intval($price);
        }

        $num = substr((string)$price, -1);
        if ($num == 4 || $num == 7) {
            $price++;
        }

        return $price;
    }
}