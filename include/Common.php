<?php

/**
 * 记录和统计时间（微秒）和内存使用情况
 * @param string $flag 开始标签
 * @param boolean $end 结束标签
 * @return mixed
 */
function debug($flag = 'system', $end = false) {
    static $arr = array();
    if (!$end) {
        $arr[$flag] = microtime(true);
    } else if ($end && isset($arr[$flag])) {
        echo '<p>' . $flag . ': runtime:' . round((microtime(true) - $arr[$flag]), 6) . 's memory_usage:' . memory_get_usage() / 1000 . 'KB</p>';
    }
}

/**
 * 数据过滤函数
 * @param string|array $data 待过滤的字符串或字符串数组
 * @param boolean $force 为true时忽略get_magic_quotes_gpc
 * @return mixed
 */
function in($data, $force = false) {
    if (is_string($data)) {
        $data = trim(htmlspecialchars($data)); // 防止被挂马，跨站攻击
        if (($force == true) || (!get_magic_quotes_gpc())) {
            $data = addslashes($data); // 防止sql注入
        }
        return $data;
    } else if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = in($value, $force);
        }
        return $data;
    } else {
        return $data;
    }
}

/**
 * 数据还原函数
 * @param unknown $data
 * @return string unknown
 */
function out($data) {
    if (is_string($data)) {
        return $data = stripslashes($data);
    } else if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = out($value);
        }
        return $data;
    } else {
        return $data;
    }
}

/**
 * 文本输入
 * @param unknown $str
 * @return Ambigous <mixed, string>
 */
function text_in($str) {
    $str = strip_tags($str, '<br>');
    $str = str_replace(" ", "&nbsp;", $str);
    $str = str_replace("\n", "<br>", $str);
    if (!get_magic_quotes_gpc()) {
        $str = addslashes($str);
    }
    return $str;
}

/**
 * 文本输出
 * @param unknown $str
 * @return string
 */
function text_out($str) {
    $str = str_replace("&nbsp;", " ", $str);
    $str = str_replace("<br>", "\n", $str);
    $str = stripslashes($str);
    return $str;
}

/**
 * html代码输入
 * @param unknown $str
 * @return string
 */
function html_in($str) {
    $search = array(
        "'<script[^>]*?>.*?</script>'si", // 去掉 javascript
        "'<iframe[^>]*?>.*?</iframe>'si"  // 去掉iframe
    );
    $replace = array("", "");
    $str = @preg_replace($search, $replace, $str);
    $str = htmlspecialchars($str);
    if (!get_magic_quotes_gpc()) {
        $str = addslashes($str);
    }
    return $str;
}

/**
 * html代码输出
 * @param unknown $str
 * @return string
 */
function html_out($str) {
    if (function_exists('htmlspecialchars_decode')) {
        $str = htmlspecialchars_decode($str);
    } else {
        $str = html_entity_decode($str);
    }
    $str = stripslashes($str);
    return $str;
}

/**
 * 获取客户端IP地址
 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @param boolean $adv 是否进行高级模式获取（有可能被伪装）
 * @return mixed
 */
function get_client_ip($type = 0, $adv = false) {
    $type = $type ? 1 : 0;
    static $ip = NULL;
    if ($ip !== NULL)
        return $ip[$type];
    if ($adv) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown', $arr);
            if (false !== $pos)
                unset($arr[$pos]);
            $ip = trim($arr[0]);
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u", ip2long($ip));
    $ip = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
}

/**
 * 截取字符串，字节格式化 把字节数格式为 B K M G T 描述的大小
 * @param unknown $str
 * @param unknown $length
 * @param number $start
 * @param string $charset
 * @param string $suffix
 * @return string
 */
function msubstr($str, $length, $start = 0, $charset = "utf-8", $suffix = true) {
    if (function_exists("mb_substr"))
        $slice = mb_substr($str, $start, $length, $charset);
    elseif (function_exists('iconv_substr')) {
        $slice = iconv_substr($str, $start, $length, $charset);
    } else {
        $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("", array_slice($match[0], $start, $length));
    }
    return $suffix ? $slice . '...' : $slice;
}

/**
 * 字节格式化 把字节数格式为 B K M G T 描述的大小
 * @param unknown $size
 * @param number $dec
 * @return string
 */
function byte_format($size, $dec = 2) {
    $a = array(
        "B", "KB", "MB", "GB", "TB", "PB"
    );
    $pos = 0;
    while ($size >= 1024) {
        $size /= 1024;
        $pos ++;
    }
    return round($size, $dec) . " " . $a[$pos];
}

/**
 * 检查字符串是否是UTF8编码,是返回true,否则返回false
 * @param unknown $string
 * @return boolean
 */
function is_utf8($string) {
    if (!empty($string)) {
        $ret = json_encode(array(
            'code' => $string
        ));
        if ($ret == '{"code":null}') {
            return false;
        }
    }
    return true;
}

/**
 * 自动转换字符集 支持数组转换
 * @param unknown $fContents
 * @param string $from
 * @param string $to
 * @return unknown string Ambigous
 */
function auto_charset($fContents, $from = 'gbk', $to = 'utf-8') {
    $from = strtoupper($from) == 'UTF8' ? 'utf-8' : $from;
    $to = strtoupper($to) == 'UTF8' ? 'utf-8' : $to;
    if (strtoupper($from) === strtoupper($to) || empty($fContents) || (is_scalar($fContents) && !is_string($fContents))) {
        // 如果编码相同或者非字符串标量则不转换
        return $fContents;
    }
    if (is_string($fContents)) {
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($fContents, $to, $from);
        } elseif (function_exists('iconv')) {
            return iconv($from, $to, $fContents);
        } else {
            return $fContents;
        }
    } elseif (is_array($fContents)) {
        foreach ($fContents as $key => $val) {
            $_key = auto_charset($key, $from, $to);
            $fContents[$_key] = auto_charset($val, $from, $to);
            if ($key != $_key)
                unset($fContents[$key]);
        }
        return $fContents;
    } else {
        return $fContents;
    }
}

/**
 * 获取微秒时间，常用于计算程序的运行时间
 * @return number
 */
function utime() {
    list($usec, $sec) = explode(" ", microtime());
    return ((float) $usec + (float) $sec);
}

/*
 *获取时间戳毫秒
 */
function getMillisecond()
{
    list($s1, $s2) = explode(' ', microtime());
    return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
}

/**
 * 生成唯一的值
 * @return string
 */
function getUniqid() {
    return md5(uniqid(rand(), true));
}

/**
 * 获取ip地址的实际地区
 * @param string $ip
 * @return string
 */
function get_ip_info($ip = '') {
    // $obj = new IpArea();
    // return $obj->get($ip);
    $url = "http://ip.taobao.com/service/getIpInfo.php?ip=" . $ip;
    $data = json_decode(file_get_contents($url), true);
    if ($data['code'] === 1) {
        // return $data['data'];
        return 'IPv4地址不符合格式';
    } else {
        // return $data['data']['country'].' '.$data['data']['region'].' '.$data['data']['city'].' '.$data['data']['county'].' '.$data['data']['area'].' '.$data['data']['isp'];
        return $data['data']['country'] . ' ' . $data['data']['city'];
    }
}

/**
 * 加密函数，可用ec_decode()函数解密，$data：待加密的字符串或数组；$key：密钥；$expire 过期时间
 * @param unknown $data
 * @param string $key
 * @param number $expire
 * @return string
 */
function ec_encode($data, $key = '', $expire = 0) {
    $string = serialize($data);
    $ckey_length = 4;
    $key = md5($key);
    $keya = md5(substr($key, 0, 16));
    $keyb = md5(substr($key, 16, 16));
    $keyc = substr(md5(microtime()), - $ckey_length);

    $cryptkey = $keya . md5($keya . $keyc);
    $key_length = strlen($cryptkey);

    $string = sprintf('%010d', $expire ? $expire + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
    $string_length = strlen($string);
    $result = '';
    $box = range(0, 255);

    $rndkey = array();
    for ($i = 0; $i <= 255; $i ++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }

    for ($j = $i = 0; $i < 256; $i ++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }

    for ($a = $j = $i = 0; $i < $string_length; $i ++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }
    return $keyc . str_replace('=', '', base64_encode($result));
}

/**
 * ec_encode之后的解密函数，$string待解密的字符串，$key，密钥
 * @param unknown $string
 * @param string $key
 * @return mixed|string
 */
function ec_decode($string, $key = '') {
    $ckey_length = 4;
    $key = md5($key);
    $keya = md5(substr($key, 0, 16));
    $keyb = md5(substr($key, 16, 16));
    $keyc = substr($string, 0, $ckey_length);

    $cryptkey = $keya . md5($keya . $keyc);
    $key_length = strlen($cryptkey);

    $string = base64_decode(substr($string, $ckey_length));
    $string_length = strlen($string);

    $result = '';
    $box = range(0, 255);

    $rndkey = array();
    for ($i = 0; $i <= 255; $i ++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }

    for ($j = $i = 0; $i < 256; $i ++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }

    for ($a = $j = $i = 0; $i < $string_length; $i ++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }
    if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
        return unserialize(substr($result, 26));
    } else {
        return '';
    }
}

/*
 * 判断是否是微信浏览器打开
 */
function isWeixin(){
	$useragent = addslashes($_SERVER['HTTP_USER_AGENT']);
	if(strpos($useragent, 'MicroMessenger') === false && strpos($useragent, 'Windows Phone') === false ){
		return false;
	}else{
		return true;
	} 
}


 /**
 * URL组装 支持不同URL模式
 * @param string $url URL表达式，格式：'[模块/控制器/操作#锚点@域名]?参数1=值1&参数2=值2...'
 * @param string|array $vars 传入的参数，支持数组和字符串
 * @param string|boolean $suffix 伪静态后缀，默认为true表示获取配置值
 * @param boolean $domain 是否显示域名
 * @return string
 */
function url($url='index/index',$vars,$suffix=false,$domain=false) {
    // 解析URL
    $info   =  parse_url($url);
    $url    =  !empty($info['path'])?$info['path']:ACTION_NAME;

    // 解析参数
    if(is_string($vars)) { // aaa=1&bbb=2 转换成数组
        parse_str($vars,$vars);
    }elseif(!is_array($vars)){
        $vars = array();
    }

    // URL组装
    $depr       =   C('URL_PATHINFO_DEPR');
    $urlCase    =   C('URL_CASE_INSENSITIVE');
    if($url) {
        if(0=== strpos($url,'/')) {// 定义路由
            $route      =   true;
            $url        =   substr($url,1);
            if('/' != $depr) {
                $url    =   str_replace('/',$depr,$url);
            }
        }else{
            if('/' != $depr) { // 安全替换
                $url    =   str_replace('/',$depr,$url);
            }
            // 解析模块、控制器和操作
            $url        =   trim($url,$depr);
            $path       =   explode($depr,$url);
            $var        =   array();
            $varModule      =   C('VAR_MODULE');
            $varController  =   C('VAR_CONTROLLER');
            $varAction      =   C('VAR_ACTION');
            $var[$varAction]       =   !empty($path)?array_pop($path):ACTION_NAME;
            $var[$varController]   =   !empty($path)?array_pop($path):CONTROLLER_NAME;

            if($urlCase) {
                $var[$varController] = parse_name($var[$varController]);
            }
            $module =   '';
            if(!empty($path)) {
                $var[$varModule] = implode($depr,$path);
            }else{
                if(C('MULTI_MODULE')) {
                    if(APP_NAME != C('DEFAULT_APP')){
                        $var[$varModule] = APP_NAME;
                    }
                }
            }
            if(isset($var[$varModule])){
                $module =   $var[$varModule];
                unset($var[$varModule]);
            }
        }
    }

    if(C('URL_MODEL') == 0) { // 普通模式URL转换
        $url        =   __APP__.'?'.$varModule."={$module}&".http_build_query(array_reverse($var), '', '&');
        if($urlCase){
            $url    =   strtolower($url);
        }        
        if(!empty($vars)) {
            $vars   =   http_build_query($vars, '', '&');
            $url   .=   '&'.$vars;
        }
    }else{ // PATHINFO模式或者兼容URL模式
        if(isset($route)) {
            $url    =   __APP__.'/'.rtrim($url,$depr);
        }else{
            $module =   (defined('BIND_MODULE') && BIND_MODULE==$module )? '' : $module;
            $url    =   __APP__.'/'.($module?$module.$depr:'').implode($depr,array_reverse($var));
        }
        if($urlCase){
            $url    =   strtolower($url);
        }
        if(!empty($vars)) { // 添加参数
            foreach ($vars as $var => $val){
                if('' !== trim($val))   $url .= $depr . $var . $depr . urlencode($val);
            }                
        }
        if(false) {
            $suffix   =  $suffix===true ? C('URL_HTML_SUFFIX'):$suffix;
            if($pos = strpos($suffix, '|')){
                $suffix = substr($suffix, 0, $pos);
            }
            if($suffix && '/' != substr($url,-1)){
                $url  .=  '.'.ltrim($suffix,'.');
            }
        }
    }

	$url=str_replace('%28','(',$url);
	$url=str_replace('%29',')',$url);
	return str_replace('%5C%22','"',$url);
    return $url;
}


/**
 * 判断是否SSL协议
 * @return boolean
 */
function isSSL() {
    if(isset($_SERVER['HTTPS']) && ('1' == $_SERVER['HTTPS'] || 'on' == strtolower($_SERVER['HTTPS']))){
        return true;
    }elseif(isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'] )) {
        return true;
    }
    return false;
}

/**
 * URL重定向
 * @param string $url 重定向的URL地址
 * @param integer $time 重定向的等待时间（秒）
 * @param string $msg 重定向前的提示信息
 * @return void
 */
function redirect($url, $time=0, $msg='') {
    //多行URL地址支持
    $url = str_replace(array("\n", "\r"), '', $url);
    if (empty($msg))
        $msg    = "系统将在{$time}秒之后自动跳转到{$url}！";
    if (!headers_sent()) {
        // redirect
        if (0 === $time) {
            header('Location: ' . $url);
        } else {
            header("refresh:{$time};url={$url}");
            echo($msg);
        }
        exit();
    } else {
        $str    = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
        if ($time != 0)
            $str .= $msg;
        exit($str);
    }
}

/**
 * 自动加载
 * @param unknown $className
 * @return boolean
 */
function autoload($className) {
    $array = array(
        BASE_PATH . 'base/' . $className . '.php',
        BASE_PATH . 'base/module/' . $className . '.php',
        BASE_PATH . 'controller/' . $className . '.php',
        BASE_PATH . 'model/' . $className . '.php',
    );
    foreach ($array as $file) {
        if (is_file($file)) {
            require_once ($file);
            return true;
        }
    }
    return false;
}

/**
 * 加载配置文件 支持格式转换 仅支持一级配置
 * @param string $file 配置文件名
 * @param string $parse 配置解析方法 有些格式需要用户自己解析
 * @return array
 */
function load_config($file,$parse=CONF_PARSE){
    $ext  = pathinfo($file,PATHINFO_EXTENSION);
    switch($ext){
        case 'php':
            return include $file;
        case 'xml': 
            return (array)simplexml_load_file($file);
        case 'json':
            return json_decode(file_get_contents($file), true);
        default:
            if(function_exists($parse)){
                return $parse($file);
            }else{
                E('Nonsupport:'.$ext);
            }
    }
}

/**
 * 数据模型
 * @param unknown $model
 * @throws Exception
 * @return Ambigous <unknown>
 */
function model($model) {
    static $objArray = array();
    $className = ucfirst($model) . 'Model';
    isset($objArray[$className]) || $objArray[$className] = array();
    if (!is_object($objArray[$className])) {
        if (!class_exists($className)) {
            throw new Exception($className . '.php 模型类不存在');
        }
        $objArray[$className] = new $className();
    }
    return $objArray[$className];
}

/**
 * 取得当前的域名
 * @return string
 */
function get_domain() {
    /* 协议 */
    $protocol = (isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) != 'off')) ? 'https://' : 'http://';
    /* 域名或IP地址 */
    if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
        $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
    } elseif (isset($_SERVER['HTTP_HOST'])) {
        $host = $_SERVER['HTTP_HOST'];
    } else {
        /* 端口 */
        if (isset($_SERVER['SERVER_PORT'])) {
            $port = ':' . $_SERVER['SERVER_PORT'];
            if ((':80' == $port && 'http://' == $protocol) || (':443' == $port && 'https://' == $protocol)) {
                $port = '';
            }
        } else {
            $port = '';
        }
        if (isset($_SERVER['SERVER_NAME'])) {
            $host = $_SERVER['SERVER_NAME'] . $port;
        } elseif (isset($_SERVER['SERVER_ADDR'])) {
            $host = $_SERVER['SERVER_ADDR'] . $port;
        }
    }
    $host = isset($host) ? $host : 'localhost';
    return $protocol . $host;
}

/**
 * 获取顶级域名
 * @param unknown $url
 * @return unknown
 */
function get_top_domain($url = '')
{
    $url = empty($url) ? get_domain() : $url;
    $host = strtolower($url);
    if (strpos($host, '/') !== false) {
        $parse = @parse_url($host);
        $host = $parse['host'];
    }
    $topleveldomaindb = array(
        'com',
        'edu',
        'gov',
        'int',
        'mil',
        'net',
        'org',
        'biz',
        'info',
        'pro',
        'name',
        'museum',
        'coop',
        'aero',
        'xxx',
        'idv',
        'mobi',
        'cc',
        'me'
    );
    $str = '';
    foreach ($topleveldomaindb as $v) {
        $str .= ($str ? '|' : '') . $v;
    }
    
    $matchstr = "[^\.]+\.(?:(" . $str . ")|\w{2}|((" . $str . ")\.\w{2}))$";
    if (preg_match("/" . $matchstr . "/ies", $host, $matchs)) {
        $domain = $matchs['0'];
    } else {
        $domain = $host;
    }
    return $domain;
}

/**
 * 获取和设置配置参数
 * @param string|array $name 配置变量
 * @param mixed $value 配置值
 * @param mixed $default 默认值
 * @return mixed
 */
function C($name=null, $value=null, $default=null) {
    static $_config = array();
    // 无参数时获取所有
    if (empty($name)) {
        if(empty($value)){
            return $_config;
        }else{
            //无配置变量时，统一配置成全体
            $_config = $value;
            return null;
        }
    }

    // 优先执行设置获取或赋值
    if (is_string($name)) {
        if (!strpos($name, '.')) {
            $name = strtoupper($name);
			if (is_null($value)){
				if(isset($_config[$name])){
					return $_config[$name];
				} else if (isset($_config['SESSION'][$name])) {
					return $_config['SESSION'][$name];
				} else if (isset($_config['COOKIE'][$name])) {
					return $_config['COOKIE'][$name];
				}else{
					return $default;
				}
			}
			if(is_array($value) && isset($_config[$name])){
				$_config[$name] = array_merge($_config[$name], $value);
			}else{
				$_config[$name] = $value;
			}
            return null;
        }
		// 二维数组设置和获取支持
        $name = explode('.', $name);
        $name[0] = strtoupper($name[0]);
        if (is_null($value))
            return isset($_config[$name[0]][$name[1]]) ? $_config[$name[0]][$name[1]] : $default;
		$_config[$name[0]][$name[1]] = is_array($value) ? array_merge($_config[$name[0]][$name[1]], $value) : $value;
        return null;
    }
    return null; // 避免非法参数
}

/**
 * 加载配置文件 支持格式转换 仅支持一级配置
 * @param string $file 配置文件名
 * @param string $parse 配置解析方法 有些格式需要用户自己解析
 * @return void
 */
function load_file($file) {
    if (file_exists($file)) {
        return include $file;
    }
}

/**
 * 抛出异常处理
 * @param string $msg 异常消息
 * @param integer $code 异常代码 默认为0
 * @return void
 */
function E($msg, $code = 0) {
    throw new Exception($msg, $code);
}

/**
 * 记录和统计时间（微秒）和内存使用情况
 * 使用方法:
 * <code>
 * G('begin'); // 记录开始标记位
 * // ... 区间运行代码
 * G('end'); // 记录结束标签位
 * echo G('begin','end',6); // 统计区间运行时间 精确到小数后6位
 * echo G('begin','end','m'); // 统计区间内存使用情况
 * 如果end标记位没有定义，则会自动以当前作为标记位
 * 其中统计内存使用需要 MEMORY_LIMIT_ON 常量为true才有效
 * </code>
 * @param string $start 开始标签
 * @param string $end 结束标签
 * @param integer|string $dec 小数位或者m
 * @return mixed
 */
function G($start, $end = '', $dec = 4) {
    static $_info = array();
    static $_mem = array();
    if (is_float($end)) { // 记录时间
        $_info[$start] = $end;
    } elseif (!empty($end)) { // 统计时间和内存使用
        if (!isset($_info[$end]))
            $_info[$end] = microtime(TRUE);
        if (MEMORY_LIMIT_ON && $dec == 'm') {
            if (!isset($_mem[$end]))
                $_mem[$end] = memory_get_usage();
            return number_format(($_mem[$end] - $_mem[$start]) / 1024);
        } else {
            return number_format(($_info[$end] - $_info[$start]), $dec);
        }
    } else { // 记录时间和内存使用
        $_info[$start] = microtime(TRUE);
        if (MEMORY_LIMIT_ON)
            $_mem[$start] = memory_get_usage();
    }
}

/**
 * 获取输入参数 支持过滤和默认值
 * 使用方法:
 * <code>
 * I('id',0); 获取id参数 自动判断get或者post
 * I('post.name','','htmlspecialchars'); 获取$_POST['name']
 * I('get.'); 获取$_GET
 * </code>
 * @param string $name 变量的名称 支持指定类型
 * @param mixed $default 不存在的时候默认值
 * @param mixed $filter 参数过滤方法
 * @return mixed
 */
function I($name, $default = '', $filter = null) {
    if (strpos($name, '.')) { // 指定参数来源
        list($method, $name) = explode('.', $name, 2);
    } else { // 默认为自动判断
        $method = 'param';
    }
    switch (strtolower($method)) {
        case 'get':
            $input = & $_GET;
            break;
        case 'post':
            $input = & $_POST;
            break;
        case 'put':
            parse_str(file_get_contents('php://input'), $input);
            break;
        case 'param':
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'POST':
                    $input = $_POST;
                    break;
                case 'PUT':
                    parse_str(file_get_contents('php://input'), $input);
                    break;
                default:
                    $input = $_GET;
            }
            break;
        case 'path':
            $input = array();
            if (!empty($_SERVER['PATH_INFO'])) {
                $depr = '/';
                $input = explode($depr, trim($_SERVER['PATH_INFO'], $depr));
            }
            break;
        case 'request':
            $input = & $_REQUEST;
            break;
        case 'session':
            $input = & $_SESSION;
            break;
        case 'cookie':
            $input = & $_COOKIE;
            break;
        case 'server':
            $input = & $_SERVER;
            break;
        case 'globals':
            $input = & $GLOBALS;
            break;
        default:
            return NULL;
    }
    if ('' == $name) { // 获取全部变量
        $data = $input;
        array_walk_recursive($data, 'filter_exp');
        $filters = isset($filter) ? $filter : 'htmlspecialchars';
        if ($filters) {
            if (is_string($filters)) {
                $filters = explode(',', $filters);
            }
            foreach ($filters as $filter) {
                $data = array_map_recursive($filter, $data); // 参数过滤
            }
        }
    } elseif (isset($input[$name])) { // 取值操作
        $data = $input[$name];
        is_array($data) && array_walk_recursive($data, 'filter_exp');
        $filters = isset($filter) ? $filter : 'htmlspecialchars';
        if ($filters) {
            if (is_string($filters)) {
                $filters = explode(',', $filters);
            } elseif (is_int($filters)) {
                $filters = array(
                    $filters
                );
            }

            foreach ($filters as $filter) {
                if (function_exists($filter)) {
                    $data = is_array($data) ? array_map_recursive($filter, $data) : $filter($data); // 参数过滤
                } else {
                    $data = filter_var($data, is_int($filter) ? $filter : filter_id($filter));
                    if (false === $data) {
                        return isset($default) ? $default : NULL;
                    }
                }
            }
        }
    } else { // 变量默认值
        $data = isset($default) ? $default : NULL;
    }
    return $data;
}

/*
 * 获取redis缓存信息
 */

function getRedis()
{
    $key = 'obj_redis';
    if (!extension_loaded('redis')) {
        throw new Exception('Redis扩展不存在');
    }
    if(!C($key)){
        require LIB_PATH . 'RedisCluster.php';
        $redis = new RedisCluster();
        C($key,$redis);
    }
    return C($key);
}

/*
 * 获取WeChat类
 */
function Wechat()
{
    $key = 'obj_wechat';
    if (!C($key)) {
        $config_key = 'common_wechat_config';
        $config = json_decode(getRedis()->get($config_key), true);
        if (!$config) {
            $config = model('Common')->get_weixin_config();
            getRedis()->set($config_key, json_encode($config));
        }

        $wechat = new Wechat($config);
        C($key, $wechat);
    }
    return C($key);
}

/**
 * 数据XML编码
 * @param mixed $data 数据
 * @param string $item 数字索引时的节点名称
 * @param string $id 数字索引key转换为的属性名
 * @return string
 */
function data_to_xml($data, $item = 'item', $id = 'id', $encoding = 'utf-8') {
    $xml = $attr = "<?xml version=\"1.0\" encoding=\"{$encoding}\"?>";
    foreach ($data as $key => $val) {
        if (is_numeric($key)) {
            $id && $attr = " {$id}=\"{$key}\"";
            $key = $item;
        }
        $xml .= "<{$key}{$attr}>";
        $xml .= (is_array($val) || is_object($val)) ? data_to_xml($val, $item, $id) : $val;
        $xml .= "</{$key}>";
    }
    return $xml;
}

/**
 * session管理函数
 * @param string|array $name session名称 如果为数组则表示进行session设置
 * @param mixed $value session值
 * @return mixed
 */
function session($name = '', $value = '') {
    $prefix = C('SESSION_PREFIX');
    if (is_array($name)) { // session初始化 在session_start 之前调用
        if (isset($name['prefix']))
            C('SESSION_PREFIX', $name['prefix']);
        if (C('VAR_SESSION_ID') && isset($_REQUEST[C('VAR_SESSION_ID')])) {
            session_id($_REQUEST[C('VAR_SESSION_ID')]);
        } elseif (isset($name['id'])) {
            session_id($name['id']);
        }
        if ('common' != APP_MODE) { // 其它模式可能不支持
            ini_set('session.auto_start', 0);
        }
        if (isset($name['name']))
            session_name($name['name']);
        if (isset($name['path']))
            session_save_path($name['path']);
        if (isset($name['domain']))
            ini_set('session.cookie_domain', $name['domain']);
        if (isset($name['expire']))
            ini_set('session.gc_maxlifetime', $name['expire']);
        if (isset($name['use_trans_sid']))
            ini_set('session.use_trans_sid', $name['use_trans_sid'] ? 1 : 0);
        if (isset($name['use_cookies']))
            ini_set('session.use_cookies', $name['use_cookies'] ? 1 : 0);
        if (isset($name['cache_limiter']))
            session_cache_limiter($name['cache_limiter']);
        if (isset($name['cache_expire']))
            session_cache_expire($name['cache_expire']);
        // 启动session
        if (C('SESSION_AUTO_START'))
            session_start();
    } elseif ('' === $value) {
        if ('' === $name) {
            // 获取全部的session
            return $prefix ? $_SESSION[$prefix] : $_SESSION;
        } elseif (0 === strpos($name, '[')) { // session 操作
            if ('[pause]' == $name) { // 暂停session
                session_write_close();
            } elseif ('[start]' == $name) { // 启动session
                session_start();
            } elseif ('[destroy]' == $name) { // 销毁session
                $_SESSION = array();
                session_unset();
                session_destroy();
            } elseif ('[regenerate]' == $name) { // 重新生成id
                session_regenerate_id();
            }
        } elseif (0 === strpos($name, '?')) { // 检查session
            $name = substr($name, 1);
            if (strpos($name, '.')) { // 支持数组
                list($name1, $name2) = explode('.', $name);
                return $prefix ? isset($_SESSION[$prefix][$name1][$name2]) : isset($_SESSION[$name1][$name2]);
            } else {
                return $prefix ? isset($_SESSION[$prefix][$name]) : isset($_SESSION[$name]);
            }
        } elseif (is_null($name)) { // 清空session
            if ($prefix) {
                unset($_SESSION[$prefix]);
            } else {
                $_SESSION = array();
            }
        } elseif ($prefix) { // 获取session
            if (strpos($name, '.')) {
                list($name1, $name2) = explode('.', $name);
                return isset($_SESSION[$prefix][$name1][$name2]) ? $_SESSION[$prefix][$name1][$name2] : null;
            } else {
                return isset($_SESSION[$prefix][$name]) ? $_SESSION[$prefix][$name] : null;
            }
        } else {
            if (strpos($name, '.')) {
                list($name1, $name2) = explode('.', $name);
                return isset($_SESSION[$name1][$name2]) ? $_SESSION[$name1][$name2] : null;
            } else {
                return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
            }
        }
    } elseif (is_null($value)) { // 删除session
        if ($prefix) {
            unset($_SESSION[$prefix][$name]);
        } else {
            unset($_SESSION[$name]);
        }
    } else { // 设置session
        if ($prefix) {
            if (!isset($_SESSION[$prefix])) {
                $_SESSION[$prefix] = array();
            }
            $_SESSION[$prefix][$name] = $value;
        } else {
            $_SESSION[$name] = $value;
        }
    }
}

/**
 * Cookie 设置、获取、删除
 * @param string $name cookie名称
 * @param mixed $value cookie值
 * @param mixed $options cookie参数
 * @return mixed
 */
function cookie($name = '', $value = '', $option = null) {
    // 默认设置
    $config = array(
        'prefix' => C('COOKIE_PREFIX'), // cookie 名称前缀
        'expire' => C('COOKIE_EXPIRE'), // cookie 保存时间
        'path' => C('COOKIE_PATH'), // cookie 保存路径
        'domain' => C('COOKIE_DOMAIN'), // cookie 有效域名
        'httponly' => C('COOKIE_HTTPONLY')  // httponly设置
    );
    // 参数设置(会覆盖黙认设置)
    if (!is_null($option)) {
        if (is_numeric($option)) {
            $option = array(
                'expire' => $option
            );
        } elseif (is_string($option)) {
            parse_str($option, $option);
        }
        $config = array_merge($config, array_change_key_case($option));
    }
    if (!empty($config['httponly'])) {
        ini_set("session.cookie_httponly", 1);
    }
    // 清除指定前缀的所有cookie
    if (is_null($name)) {
        if (empty($_COOKIE))
            return; // 要删除的cookie前缀，不指定则删除config设置的指定前缀
        $prefix = empty($value) ? $config['prefix'] : $value;
        if (!empty($prefix)) { // 如果前缀为空字符串将不作处理直接返回
            foreach ($_COOKIE as $key => $val) {
                if (0 === stripos($key, $prefix)) {
                    setcookie($key, '', time() - 3600, $config['path'], $config['domain']);
                    unset($_COOKIE[$key]);
                }
            }
        }
        return;
    } elseif ('' === $name) {
        // 获取全部的cookie
        return $_COOKIE;
    }
    $name = $config['prefix'] . str_replace('.', '_', $name);
    if ('' === $value) {
        if (isset($_COOKIE[$name])) {
            $value = $_COOKIE[$name];
            if (0 === strpos($value, 'ectouch:')) {
                $value = substr($value, 6);
                return array_map('urldecode', json_decode(MAGIC_QUOTES_GPC ? stripslashes($value) : $value, true));
            } else {
                return $value;
            }
        } else {
            return null;
        }
    } else {
        if (is_null($value)) {
            setcookie($name, '', time() - 3600, $config['path'], $config['domain']);
            unset($_COOKIE[$name]); // 删除指定cookie
        } else {
            // 设置cookie
            if (is_array($value)) {
                $value = 'ectouch:' . json_encode(array_map('urlencode', $value));
            }
            $expire = !empty($config['expire']) ? time() + intval($config['expire']) : 0;
            setcookie($name, $value, $expire, $config['path'], $config['domain']);
            $_COOKIE[$name] = $value;
        }
    }
}


/**
 * 不区分大小写的in_array实现
 * @param unknown $value
 * @param unknown $array
 * @return boolean
 */
function in_array_case($value, $array) {
    return in_array(strtolower($value), array_map('strtolower', $array));
}

/**
 * 读结果缓存文件
 * @params  string  $cache_name
 * @return  array   $data
 */
function read_static_cache($cache_name) {
    static $result = array();
    if (!empty($result[$cache_name])) {
        return $result[$cache_name];
    }
    $cache_file_path = ROOT_PATH . 'data/cache/static_caches/' . $cache_name . '.php';
    if (file_exists($cache_file_path)) {
        include_once($cache_file_path);
        $result[$cache_name] = $data;
        return $result[$cache_name];
    } else {
        return false;
    }
}

/**
 * 写结果缓存文件
 * @params  string  $cache_name
 * @params  string  $caches
 * @return
 */
function write_static_cache($cache_name, $caches) {
    //增加目录状态判断 by ecmoban carson
    $static_caches = ROOT_PATH . 'data/cache/static_caches/';
    if (!is_dir($static_caches)) {
        @mkdir($static_caches, 0777);
    }
    $cache_file_path = $static_caches . $cache_name . '.php';
    $content = "<?php\r\n";
    $content .= "\$data = " . var_export($caches, true) . ";\r\n";
    $content .= "?>";
    file_put_contents($cache_file_path, $content, LOCK_EX);
}

/**
 * 致命错误捕获
 * 发生错误的文件名、发生错误的行号 以及发生错误的上下文(一个指向错误发生时活动符号表的 array)
 */
function fatalError($errno,$errstr,$errfile,$errline)
{
//    echo '文件：'.$errfile."<br>".'行号：'.$errline.''."<br>".'错误提示：'.$errstr."<br>".'错误级别值：'.$errno;
    SeError::show($errstr, $errno, $errfile, $errline);
    exit;
}


function shutDown()
{
    if ($e = error_get_last()) {
        switch ($e['type']) {
        	case E_ERROR:
        	case E_PARSE:
        	case E_CORE_ERROR:
        	case E_COMPILE_ERROR:
        	case E_USER_ERROR:
        	    ob_end_clean();
        	    halt($e);
        	    break;
        }
    }
    if(DEBUG){
        debug('system',true);
    }
}

/**
 * 错误输出 
 * @param $e
 */
function halt($e)
{
    SeError::show($e['message'], $e['type'], $e['file'], $e['line']);
    exit();
}

/**
 * 创建类的别名
 */
if (!function_exists('class_alias')) {

    function class_alias($original, $alias) {
        $newclass = create_function('', 'class ' . $alias . ' extends ' . $original . ' {}');
        $newclass();
    }

}

//写日志
function writeLog($content)
{
    $directory = defined('CONTROLLER_NAME') ? CONTROLLER_NAME : 'log';
    $log_path = LOG_PATH . $directory;

    if(!is_dir($log_path)){
        mkdir($log_path,0777,true);
    }

    if(is_array($content)){
        $contents = serialize($content);
    }else{
        $contents = $content;
    }

    file_put_contents($log_path .'/'.date('Ymd').'.log', date("Y-m-d h:i:s").'|'. $contents . PHP_EOL, FILE_APPEND);
}

//防止快速刷新
function repeat_submit()
{
    $key = 'repeatsitetime';
    if (isset($_SESSION[$key])) {
        if ((microtime(true) * 1000 - $_SESSION[$key]) < C('REPEAT_SUBMIT_EXPIRES')) {
            if(!IS_AJAX){
                //页面跳转访问
                $len = strlen(C("SITE_URL"));
                if(!empty($_SERVER['HTTP_REFERER'])){
                    //页面跳转允许访问,且允许来自微信的跳转之后的重定向
                    if(substr($_SERVER['HTTP_REFERER'],0,$len) == C("SITE_URL") || substr($_SERVER['HTTP_REFERER'], 0, strlen('https://open.weixin.qq.com')) == 'https://open.weixin.qq.com'){

                    }else{
                        $_SESSION[$key] = 1000 * microtime(true);
                        exit('请求过于频繁，请稍后1');
                    }
                }else{
                    //页面直接访问
//                    if(isset($_GET['code']) && $_SESSION['user_id'] == 0){
//                        //来自微信的跳转
//                    }elseif(isset($GLOBALS['HTTP_RAW_POST_DATA'])){
//                        //来自微信和支付回调
//                    }elseif(1){
//                        //来自header重定向
//                    }else{
//                        exit('请求过于频繁，请稍后2');
//                    }
                }
            }
        }
    }

    $_SESSION[$key] = 1000 * microtime(true);
}


/*
 * 美化输出
 */
function p($out)
{
    if (is_array($out) || is_object($out)) echo "<pre>";
    if ($out) {
        print_r($out);
        exit;
    }
    var_dump($out);
    exit;
}


// 安全过虑
recurAddslashes($_GET);
recurAddslashes($_POST);
recurAddslashes($_COOKIE);


/**
 * 获取IP
 * 如果有多个IP，只获取一个
 */
function getIp()
{
    if (getenv('HTTP_CLIENT_IP')) {
        $ip = getenv('HTTP_CLIENT_IP');
    } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
        $ip = getenv('HTTP_X_FORWARDED_FOR');
    } elseif (getenv('REMOTE_ADDR')) {
        $ip = getenv('REMOTE_ADDR');
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    } else {
        $ip = '';
    }
    if (true == ($p = strpos($ip, ','))) {
        $ip = substr($ip, 0, $p);
    }
    return $ip;
}

/**
 * addslashes
 * Key不允许出现引号
 */
function recurAddslashes(&$var)
{
    if (is_array($var)) {
        foreach ($var as $key => $value) {
            if (preg_match('/[\"\'\\\]/', $key)) {
                unset($var[$key]);
            } else {
                recurAddslashes($var[$key]);
            }
        }
    } else {
        $var = addslashes($var);
    }
}

/**
 * stripslashes
 */
function recurStripslashes(&$var)
{
    if (is_array($var)) {
        foreach ($var as $key => $value) {
            recurStripslashes($var[$key]);
        }
    } else {
        $var = stripslashes($var);
    }
}

/**
 * 获取REQUEST
 */
function getRequest($key, $default = null, $secure = false)
{
    $v = isset($_POST[$key]) ? getPost($key, $default) : getGet($key, $default);

    return $secure ? vasSecure($v) : $v;
}

/**
 * 获取GET请求
 */
function getGet($key, $default = null, $secure = false)
{
    $v = (isset($_GET[$key]) && $_GET[$key] !== '') ? $_GET[$key] : $default;

    return $secure ? vasSecure($v) : $v;
}

/**
 * 获取POST请求
 */
function getPost($key, $default = null, $secure = false)
{
    $v = (isset($_POST[$key]) && $_POST[$key] !== '') ? $_POST[$key] : $default;

    return $secure ? vasSecure($v) : $v;
}

/**
 * 获取Cookie
 */
function getCookie($key, $default = null)
{
    return isset($_COOKIE[$key]) ? $_COOKIE[$key] : $default;
}

/**
 * 获取Session
 */
function getSession($key, $default = null)
{
    return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
}

function vasSecure($value)
{
    return str_replace(array('"', '\'', '%', '`', '\\', '/'), '', $value);
}