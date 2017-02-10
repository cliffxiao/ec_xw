<?php

/**
 * SESSION
 */
class SeSession {
    // 可配置的参数
    private static $_valid_options = array(
        'save_path', 'name', 'save_handler', 'gc_probability', 'gc_divisor', 'gc_maxlifetime', 'serialize_handler',
        'cookie_lifetime', 'cookie_path', 'cookie_domain', 'cookie_secure', 'cookie_httponly', 'use_cookies',
        'use_only_cookies', 'referer_check', 'entropy_file', 'entropy_length', 'cache_limiter', 'cache_expire', 'use_trans_sid'
    );
    // 是否已启动
    protected static $_started = 0;

    function __construct() {
        $session_config = C('SESSION');
        $handler = $session_config['save_handler'];
        $handler_config = C(strtoupper($session_config['save_handler']));
        self::start($session_config,$handler,$handler_config);
//        self::test();
    }

    public static function test()
    {
        if (!isset($_SESSION['TEST'])) {
            $_SESSION['TEST'] = time();
        }
        $_SESSION['TEST3'] = time();

        print $_SESSION['TEST'];
        print "<br><br>";
        print $_SESSION['TEST3'];
        print "<br><br>";
        $session_id = self::getId();
        print $session_id;

        $memcache = memcache_connect('127.0.0.1', 11211);
        var_dump($memcache->get($session_id));
        $memcache->set('aaaa', 'hello everyone');
        var_dump($memcache->get('aaaa'));
        p($GLOBALS['_SESSION']);
    }

    /**
     * 开启
     * @param array $options
     */
    public static function start(array $options = array(), $handler = '', $handler_options = null)
    {
        if (self::$_started) {
            return;
        }

        if (!empty($options)) {
            self::setOptions($options);
        }
        if ($handler) {
            self::setSaveHandler($handler, $handler_options);
        }
        self::$_started = session_start();
    }

    /**
     * 配置设置
     * @param array $options
     */
    public static function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            if (!in_array($key, self::$_valid_options)) {
                throw new Exception('未知的Session配置参数：' . $key);
            }
            ini_set('session.'.$key, $value);
        }
    }

    /**
     * 设置session save handler
     * @param Session_SaveHandlerInterface|string $handler
     */
    public static function setSaveHandler($handler, $options = null)
    {
        if (!$handler instanceof Session_Save_Interface) {
            if (is_string($handler)) {
                $file_name = ucfirst(strtolower($handler));
                $class_name = 'Session_Save_' .$file_name;
                require dirname(__FILE__).'/session/'. $file_name.'.php';
                $handler = new $class_name($options);
            }
        }
        session_set_save_handler(
            array(&$handler, 'open'),
            array(&$handler, 'close'),
            array(&$handler, 'read'),
            array(&$handler, 'write'),
            array(&$handler, 'destroy'),
            array(&$handler, 'gc')
        );
    }

    /**
     * 获取session id
     * @return string
     */
    public static function getId()
    {
        return session_id();
    }

    /**
     * 关闭Session
     */
    public static function close()
    {
        session_write_close();
    }

    /**
     * Session销毁
     */
    public static function destroy()
    {
        session_unset();
        session_destroy();
        // cookie设为过期
        if (isset($_COOKIE[session_name()])) {
            $cp = session_get_cookie_params();
            setcookie(session_name(), md5(microtime() . mt_rand(0, 999999)), 1, $cp['path'], $cp['domain'], $cp['secure']);
        }
    }
}

?>