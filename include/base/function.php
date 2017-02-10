<?php

/**
 * 获得当前格林威治时间的时间戳
 *
 * @return  integer
 */
function gmtime() {
    return (time() - date('Z'));
}

/**
 * 获得服务器的时区
 *
 * @return  integer
 */
function server_timezone() {
    if (function_exists('date_default_timezone_get')) {
        return date_default_timezone_get();
    } else {
        return date('Z') / 3600;
    }
}


/* * ********************************************************
 * 基础函数库
 * ******************************************************** */

/**
 * 截取UTF-8编码下字符串的函数
 *
 * @param   string      $str        被截取的字符串
 * @param   int         $length     截取的长度
 * @param   bool        $append     是否附加省略号
 *
 * @return  string
 */
function sub_str($str, $length = 0, $append = true) {
    $str = trim($str);
    $strlength = strlen($str);

    if ($length == 0 || $length >= $strlength) {
        return $str;
    } elseif ($length < 0) {
        $length = $strlength + $length;
        if ($length < 0) {
            $length = $strlength;
        }
    }

    if (function_exists('mb_substr')) {
        $newstr = mb_substr($str, 0, $length, EC_CHARSET);
    } elseif (function_exists('iconv_substr')) {
        $newstr = iconv_substr($str, 0, $length, EC_CHARSET);
    } else {
        //$newstr = trim_right(substr($str, 0, $length));
        $newstr = substr($str, 0, $length);
    }

    if ($append && $str != $newstr) {
        $newstr .= '...';
    }

    return $newstr;
}

/**
 * 获得用户的真实IP地址
 *
 * @access  public
 * @return  string
 */
function real_ip() {
    static $realip = NULL;

    if ($realip !== NULL) {
        return $realip;
    }

    if (isset($_SERVER)) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

            /* 取X-Forwarded-For中第一个非unknown的有效IP字符串 */
            foreach ($arr AS $ip) {
                $ip = trim($ip);

                if ($ip != 'unknown') {
                    $realip = $ip;

                    break;
                }
            }
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $realip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            if (isset($_SERVER['REMOTE_ADDR'])) {
                $realip = $_SERVER['REMOTE_ADDR'];
            } else {
                $realip = '0.0.0.0';
            }
        }
    } else {
        if (getenv('HTTP_X_FORWARDED_FOR')) {
            $realip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('HTTP_CLIENT_IP')) {
            $realip = getenv('HTTP_CLIENT_IP');
        } else {
            $realip = getenv('REMOTE_ADDR');
        }
    }

    preg_match("/[\d\.]{7,15}/", $realip, $onlineip);
    $realip = !empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0';

    return $realip;
}

/**
 * 计算字符串的长度（汉字按照两个字符计算）
 *
 * @param   string      $str        字符串
 *
 * @return  int
 */
function str_len($str) {
    $length = strlen(preg_replace('/[\x00-\x7F]/', '', $str));

    if ($length) {
        return strlen($str) - $length + intval($length / 3) * 2;
    } else {
        return strlen($str);
    }
}

/**
 * 获得用户操作系统的换行符
 *
 * @access  public
 * @return  string
 */
function get_crlf() {
    /* LF (Line Feed, 0x0A, \N) 和 CR(Carriage Return, 0x0D, \R) */
    if (stristr($_SERVER['HTTP_USER_AGENT'], 'Win')) {
        $the_crlf = '\r\n';
    } elseif (stristr($_SERVER['HTTP_USER_AGENT'], 'Mac')) {
        $the_crlf = '\r'; // for old MAC OS
    } else {
        $the_crlf = '\n';
    }

    return $the_crlf;
}


/**
 * 获得服务器上的 GD 版本
 *
 * @access      public
 * @return      int         可能的值为0，1，2
 */
function gd_version() {
    return SeImage::gd_version();
}

/**
 * 获得系统是否启用了 gzip
 *
 * @access  public
 *
 * @return  boolean
 */
function gzip_enabled() {
    static $enabled_gzip = NULL;

    if ($enabled_gzip === NULL) {
        $enabled_gzip = function_exists('ob_gzhandler');
    }

    return $enabled_gzip;
}

/**
 * 递归方式的对变量中的特殊字符进行转义
 *
 * @access  public
 * @param   mix     $value
 *
 * @return  mix
 */
function addslashes_deep($value) {
    if (empty($value)) {
        return $value;
    } else {
        return is_array($value) ? array_map('addslashes_deep', $value) : addslashes($value);
    }
}

/**
 * 将对象成员变量或者数组的特殊字符进行转义
 *
 * @access   public
 * @param    mix        $obj      对象或者数组
 *
 * @return   mix                  对象或者数组
 */
function addslashes_deep_obj($obj) {
    if (is_object($obj) == true) {
        foreach ($obj AS $key => $val) {
            $obj->$key = addslashes_deep($val);
        }
    } else {
        $obj = addslashes_deep($obj);
    }

    return $obj;
}

/**
 * 递归方式的对变量中的特殊字符去除转义
 *
 * @access  public
 * @param   mix     $value
 *
 * @return  mix
 */
function stripslashes_deep($value) {
    if (empty($value)) {
        return $value;
    } else {
        return is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
    }
}

/**
 *  将一个字串中含有全角的数字字符、字母、空格或'%+-()'字符转换为相应半角字符
 *
 * @access  public
 * @param   string       $str         待转换字串
 *
 * @return  string       $str         处理后字串
 */
function make_semiangle($str) {
    $arr = array('０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4',
        '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
        'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E',
        'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J',
        'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O',
        'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T',
        'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y',
        'Ｚ' => 'Z', 'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd',
        'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i',
        'ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n',
        'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's',
        'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x',
        'ｙ' => 'y', 'ｚ' => 'z',
        '（' => '(', '）' => ')', '〔' => '[', '〕' => ']', '【' => '[',
        '】' => ']', '〖' => '[', '〗' => ']', '“' => '[', '”' => ']',
        '‘' => '[', '’' => ']', '｛' => '{', '｝' => '}', '《' => '<',
        '》' => '>',
        '％' => '%', '＋' => '+', '—' => '-', '－' => '-', '～' => '-',
        '：' => ':', '。' => '.', '、' => ',', '，' => '.', '、' => '.',
        '；' => ',', '？' => '?', '！' => '!', '…' => '-', '‖' => '|',
        '”' => '"', '’' => '`', '‘' => '`', '｜' => '|', '〃' => '"',
        '　' => ' ');

    return strtr($str, $arr);
}

/**
 * 检查文件类型
 *
 * @access      public
 * @param       string      filename            文件名
 * @param       string      realname            真实文件名
 * @param       string      limit_ext_types     允许的文件类型
 * @return      string
 */
function check_file_type($filename, $realname = '', $limit_ext_types = '') {
    if ($realname) {
        $extname = strtolower(substr($realname, strrpos($realname, '.') + 1));
    } else {
        $extname = strtolower(substr($filename, strrpos($filename, '.') + 1));
    }

    if ($limit_ext_types && stristr($limit_ext_types, '|' . $extname . '|') === false) {
        return '';
    }

    $str = $format = '';

    $file = @fopen($filename, 'rb');
    if ($file) {
        $str = @fread($file, 0x400); // 读取前 1024 个字节
        @fclose($file);
    } else {
        if (stristr($filename, ROOT_PATH) === false) {
            if ($extname == 'jpg' || $extname == 'jpeg' || $extname == 'gif' || $extname == 'png' || $extname == 'doc' ||
                    $extname == 'xls' || $extname == 'txt' || $extname == 'zip' || $extname == 'rar' || $extname == 'ppt' ||
                    $extname == 'pdf' || $extname == 'rm' || $extname == 'mid' || $extname == 'wav' || $extname == 'bmp' ||
                    $extname == 'swf' || $extname == 'chm' || $extname == 'sql' || $extname == 'cert' || $extname == 'pptx' ||
                    $extname == 'xlsx' || $extname == 'docx') {
                $format = $extname;
            }
        } else {
            return '';
        }
    }

    if ($format == '' && strlen($str) >= 2) {
        if (substr($str, 0, 4) == 'MThd' && $extname != 'txt') {
            $format = 'mid';
        } elseif (substr($str, 0, 4) == 'RIFF' && $extname == 'wav') {
            $format = 'wav';
        } elseif (substr($str, 0, 3) == "\xFF\xD8\xFF") {
            $format = 'jpg';
        } elseif (substr($str, 0, 4) == 'GIF8' && $extname != 'txt') {
            $format = 'gif';
        } elseif (substr($str, 0, 8) == "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A") {
            $format = 'png';
        } elseif (substr($str, 0, 2) == 'BM' && $extname != 'txt') {
            $format = 'bmp';
        } elseif ((substr($str, 0, 3) == 'CWS' || substr($str, 0, 3) == 'FWS') && $extname != 'txt') {
            $format = 'swf';
        } elseif (substr($str, 0, 4) == "\xD0\xCF\x11\xE0") {   // D0CF11E == DOCFILE == Microsoft Office Document
            if (substr($str, 0x200, 4) == "\xEC\xA5\xC1\x00" || $extname == 'doc') {
                $format = 'doc';
            } elseif (substr($str, 0x200, 2) == "\x09\x08" || $extname == 'xls') {
                $format = 'xls';
            } elseif (substr($str, 0x200, 4) == "\xFD\xFF\xFF\xFF" || $extname == 'ppt') {
                $format = 'ppt';
            }
        } elseif (substr($str, 0, 4) == "PK\x03\x04") {
            if (substr($str, 0x200, 4) == "\xEC\xA5\xC1\x00" || $extname == 'docx') {
                $format = 'docx';
            } elseif (substr($str, 0x200, 2) == "\x09\x08" || $extname == 'xlsx') {
                $format = 'xlsx';
            } elseif (substr($str, 0x200, 4) == "\xFD\xFF\xFF\xFF" || $extname == 'pptx') {
                $format = 'pptx';
            } else {
                $format = 'zip';
            }
        } elseif (substr($str, 0, 4) == 'Rar!' && $extname != 'txt') {
            $format = 'rar';
        } elseif (substr($str, 0, 4) == "\x25PDF") {
            $format = 'pdf';
        } elseif (substr($str, 0, 3) == "\x30\x82\x0A") {
            $format = 'cert';
        } elseif (substr($str, 0, 4) == 'ITSF' && $extname != 'txt') {
            $format = 'chm';
        } elseif (substr($str, 0, 4) == "\x2ERMF") {
            $format = 'rm';
        } elseif ($extname == 'sql') {
            $format = 'sql';
        } elseif ($extname == 'txt') {
            $format = 'txt';
        }
    }

    if ($limit_ext_types && stristr($limit_ext_types, '|' . $format . '|') === false) {
        $format = '';
    }

    return $format;
}

/**
 * 对 MYSQL LIKE 的内容进行转义
 *
 * @access      public
 * @param       string      string  内容
 * @return      string
 */
function mysql_like_quote($str) {
    return strtr($str, array("\\\\" => "\\\\\\\\", '_' => '\_', '%' => '\%', "\'" => "\\\\\'"));
}

/**
 * 获取服务器的ip
 *
 * @access      public
 *
 * @return string
 * */
function real_server_ip() {
    static $serverip = NULL;

    if ($serverip !== NULL) {
        return $serverip;
    }

    if (isset($_SERVER)) {
        if (isset($_SERVER['SERVER_ADDR'])) {
            $serverip = $_SERVER['SERVER_ADDR'];
        } else {
            $serverip = '0.0.0.0';
        }
    } else {
        $serverip = getenv('SERVER_ADDR');
    }

    return $serverip;
}

function se_iconv($source_lang, $target_lang, $source_string = '') {
    static $chs = NULL;

    /* 如果字符串为空或者字符串不需要转换，直接返回 */
    if ($source_lang == $target_lang || $source_string == '' || preg_match("/[\x80-\xFF]+/", $source_string) == 0) {
        return $source_string;
    }

    if ($chs === NULL) {
        $chs = new SeIconv(ROOT_PATH);
    }

    return $chs->Convert($source_lang, $target_lang, $source_string);
}

/**
 * 去除字符串右侧可能出现的乱码
 *
 * @param   string      $str        字符串
 *
 * @return  string
 */
function trim_right($str) {
    $len = strlen($str);
    /* 为空或单个字符直接返回 */
    if ($len == 0 || ord($str{$len - 1}) < 127) {
        return $str;
    }
    /* 有前导字符的直接把前导字符去掉 */
    if (ord($str{$len - 1}) >= 192) {
        return substr($str, 0, $len - 1);
    }
    /* 有非独立的字符，先把非独立字符去掉，再验证非独立的字符是不是一个完整的字，不是连原来前导字符也截取掉 */
    $r_len = strlen(rtrim($str, "\x80..\xBF"));
    if ($r_len == 0 || ord($str{$r_len - 1}) < 127) {
        return sub_str($str, 0, $r_len);
    }

    $as_num = ord(~$str{$r_len - 1});
    if ($as_num > (1 << (6 + $r_len - $len))) {
        return $str;
    } else {
        return substr($str, 0, $r_len - 1);
    }
}

/**
 * 将上传文件转移到指定位置
 *
 * @param string $file_name
 * @param string $target_name
 * @return blog
 */
function move_upload_file($file_name, $target_name = '') {
    if (function_exists("move_uploaded_file")) {
        if (move_uploaded_file($file_name, $target_name)) {
            @chmod($target_name, 0755);
            return true;
        } else if (copy($file_name, $target_name)) {
            @chmod($target_name, 0755);
            return true;
        }
    } elseif (copy($file_name, $target_name)) {
        @chmod($target_name, 0755);
        return true;
    }
    return false;
}

/**
 * 将JSON传递的参数转码
 *
 * @param string $str
 * @return string
 */
function json_str_iconv($str) {
    if (EC_CHARSET != 'utf-8') {
        if (is_string($str)) {
            return addslashes(stripslashes(ecs_iconv('utf-8', EC_CHARSET, $str)));
        } elseif (is_array($str)) {
            foreach ($str as $key => $value) {
                $str[$key] = json_str_iconv($value);
            }
            return $str;
        } elseif (is_object($str)) {
            foreach ($str as $key => $value) {
                $str->$key = json_str_iconv($value);
            }
            return $str;
        } else {
            return $str;
        }
    }
    return $str;
}

/**
 * 循环转码成utf8内容
 *
 * @param string $str
 * @return string
 */
function to_utf8_iconv($str) {
    if (SE_CHARSET != 'utf-8') {
        if (is_string($str)) {
            return se_iconv(SE_CHARSET, 'utf-8', $str);
        } elseif (is_array($str)) {
            foreach ($str as $key => $value) {
                $str[$key] = to_utf8_iconv($value);
            }
            return $str;
        } elseif (is_object($str)) {
            foreach ($str as $key => $value) {
                $str->$key = to_utf8_iconv($value);
            }
            return $str;
        } else {
            return $str;
        }
    }
    return $str;
}

/* * ********************************************************
 * 公共函数库
 * ******************************************************** */

/**
 *  清除指定后缀的模板缓存或编译文件
 *
 * @access  public
 * @param  bool       $is_cache  是否清除缓存还是清出编译文件
 * @param  string     $ext       需要删除的文件名，不包含后缀
 *
 * @return int        返回清除的文件个数
 */
function clear_tpl_files($is_cache = true, $ext = '') {
    $dirs = array();

    if (isset($GLOBALS['shop_id']) && $GLOBALS['shop_id'] > 0) {
        $tmp_dir = DATA_DIR;
    } else {
        $tmp_dir = 'temp';
    }
    if ($is_cache) {
        $cache_dir = ROOT_PATH . $tmp_dir . '/caches/';
        $dirs[] = ROOT_PATH . $tmp_dir . '/query_caches/';
        $dirs[] = ROOT_PATH . $tmp_dir . '/static_caches/';
        for ($i = 0; $i < 16; $i++) {
            $hash_dir = $cache_dir . dechex($i);
            $dirs[] = $hash_dir . '/';
        }
    } else {
        $dirs[] = ROOT_PATH . $tmp_dir . '/compiled/';
        $dirs[] = ROOT_PATH . $tmp_dir . '/compiled/admin/';
    }

    $str_len = strlen($ext);
    $count = 0;

    foreach ($dirs AS $dir) {
        $folder = @opendir($dir);

        if ($folder === false) {
            continue;
        }

        while ($file = readdir($folder)) {
            if ($file == '.' || $file == '..' || $file == 'index.htm' || $file == 'index.html') {
                continue;
            }
            if (is_file($dir . $file)) {
                /* 如果有文件名则判断是否匹配 */
                $pos = ($is_cache) ? strrpos($file, '_') : strrpos($file, '.');

                if ($str_len > 0 && $pos !== false) {
                    $ext_str = substr($file, 0, $pos);

                    if ($ext_str == $ext) {
                        if (@unlink($dir . $file)) {
                            $count++;
                        }
                    }
                } else {
                    if (@unlink($dir . $file)) {
                        $count++;
                    }
                }
            }
        }
        closedir($folder);
    }

    return $count;
}

/**
 * 清除模版编译文件
 *
 * @access  public
 * @param   mix     $ext    模版文件名， 不包含后缀
 * @return  void
 */
function clear_compiled_files($ext = '') {
    return clear_tpl_files(false, $ext);
}

/**
 * 清除缓存文件
 *
 * @access  public
 * @param   mix     $ext    模版文件名， 不包含后缀
 * @return  void
 */
function clear_cache_files($ext = '') {
    return clear_tpl_files(true, $ext);
}

/**
 * 清除模版编译和缓存文件
 *
 * @access  public
 * @param   mix     $ext    模版文件名后缀
 * @return  void
 */
function clear_all_files($ext = '') {
    del_dir(ROOT_PATH . 'data/cache');
    @mkdir(ROOT_PATH . 'data/cache', 0777);
}

/**
 * 调用array_combine函数
 *
 * @param   array  $keys
 * @param   array  $values
 *
 * @return  $combined
 */
if (!function_exists('array_combine')) {

    function array_combine($keys, $values) {
        if (!is_array($keys)) {
            user_error('array_combine() expects parameter 1 to be array, ' .
                    gettype($keys) . ' given', E_USER_WARNING);
            return;
        }

        if (!is_array($values)) {
            user_error('array_combine() expects parameter 2 to be array, ' .
                    gettype($values) . ' given', E_USER_WARNING);
            return;
        }

        $key_count = count($keys);
        $value_count = count($values);
        if ($key_count !== $value_count) {
            user_error('array_combine() Both parameters should have equal number of elements', E_USER_WARNING);
            return false;
        }

        if ($key_count === 0 || $value_count === 0) {
            user_error('array_combine() Both parameters should have number of elements at least 0', E_USER_WARNING);
            return false;
        }

        $keys = array_values($keys);
        $values = array_values($values);

        $combined = array();
        for ($i = 0; $i < $key_count; $i++) {
            $combined[$keys[$i]] = $values[$i];
        }

        return $combined;
    }

}

/* * ********************************************************
 * 用户相关函数库
 * ******************************************************** */

/**
 *  获取用户的tags
 *
 * @access  public
 * @param   int         $user_id        用户ID
 *
 * @return array        $arr            tags列表
 */
function get_user_tags($user_id = 0) {
    if (empty($user_id)) {
        $GLOBALS['error_no'] = 1;

        return false;
    }

    $tags = model('ClipsBase')->get_tags(0, $user_id);

    if (!empty($tags)) {
        color_tag($tags);
    }

    return $tags;
}

/**
 * 标签着色
 *
 * @access   public
 * @param    array
 * @author   Xuan Yan
 *
 * @return   none
 */
function color_tag(&$tags) {
    $tagmark = array(
        array('color' => '#666666', 'size' => '0.8em', 'ifbold' => 1),
        array('color' => '#333333', 'size' => '0.9em', 'ifbold' => 0),
        array('color' => '#006699', 'size' => '1.0em', 'ifbold' => 1),
        array('color' => '#CC9900', 'size' => '1.1em', 'ifbold' => 0),
        array('color' => '#666633', 'size' => '1.2em', 'ifbold' => 1),
        array('color' => '#993300', 'size' => '1.3em', 'ifbold' => 0),
        array('color' => '#669933', 'size' => '1.4em', 'ifbold' => 1),
        array('color' => '#3366FF', 'size' => '1.5em', 'ifbold' => 0),
        array('color' => '#197B30', 'size' => '1.6em', 'ifbold' => 1),
    );

    $maxlevel = count($tagmark);
    $tcount = $scount = array();

    foreach ($tags AS $val) {
        $tcount[] = $val['tag_count']; // 获得tag个数数组
    }
    $tcount = array_unique($tcount); // 去除相同个数的tag

    sort($tcount); // 从小到大排序

    $tempcount = count($tcount); // 真正的tag级数
    $per = $maxlevel >= $tempcount ? 1 : $maxlevel / ($tempcount - 1);

    foreach ($tcount AS $key => $val) {
        $lvl = floor($per * $key);
        $scount[$val] = $lvl; // 计算不同个数的tag相对应的着色数组key
    }

    $rewrite = intval(C('rewrite')) > 0;

    /* 遍历所有标签，根据引用次数设定字体大小 */
    foreach ($tags AS $key => $val) {
        $lvl = $scount[$val['tag_count']]; // 着色数组key

        $tags[$key]['color'] = $tagmark[$lvl]['color'];
        $tags[$key]['size'] = $tagmark[$lvl]['size'];
        $tags[$key]['bold'] = $tagmark[$lvl]['ifbold'];
        if ($rewrite) {
            if (strtolower(EC_CHARSET) !== 'utf-8') {
                $tags[$key]['url'] = 'tag-' . urlencode(urlencode($val['tag_words'])) . '.html';
            } else {
                $tags[$key]['url'] = 'tag-' . urlencode($val['tag_words']) . '.html';
            }
        } else {
            $tags[$key]['url'] = 'search.php?keywords=' . urlencode($val['tag_words']);
        }
    }
    shuffle($tags);
}

/* * ********************************************************
 * 加密解密函数
 * ******************************************************** */

/**
 * 加密函数
 * @param   string  $str    加密前的字符串
 * @param   string  $key    密钥
 * @return  string  加密后的字符串
 */
function encrypt($str, $key = AUTH_KEY) {
    $coded = '';
    $keylength = strlen($key);

    for ($i = 0, $count = strlen($str); $i < $count; $i += $keylength) {
        $coded .= substr($str, $i, $keylength) ^ $key;
    }

    return str_replace('=', '', base64_encode($coded));
}

/**
 * 解密函数
 * @param   string  $str    加密后的字符串
 * @param   string  $key    密钥
 * @return  string  加密前的字符串
 */
function decrypt($str, $key = AUTH_KEY) {
    $coded = '';
    $keylength = strlen($key);
    $str = base64_decode($str);

    for ($i = 0, $count = strlen($str); $i < $count; $i += $keylength) {
        $coded .= substr($str, $i, $keylength) ^ $key;
    }

    return $coded;
}

/* * ********************************************************
 * LICENSE 相关函数库
 * ******************************************************** */

/**
 * 功能：生成certi_ac验证字段
 * @param   string     POST传递参数
 * @param   string     证书token
 * @return  string
 */
function make_ac($post_params, $token) {
    if (!is_array($post_params)) {
        return;
    }
    // core
    ksort($post_params);
    $str = '';
    foreach ($post_params as $key => $value) {
        $str .= $value;
    }

    return md5($str . $token);
}

/* * ********************************************************
 * 模版相关公用函数库
 * ******************************************************** */
