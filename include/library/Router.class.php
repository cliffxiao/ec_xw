<?php
class Router
{
    protected static $_controller = '';
    protected static $_action = '';
    protected static $_error_redirect = '';

    function  __construct()
    {
        self::$_controller = C('DEFAULT_CONTROLLER');
        self::$_action = C('DEFAULT_ACTION');
        self::$_error_redirect = C('ERROR_REDIRECT');
    }
    /**
     * 获取路径
     */
    public static function getRequestPath()
    {
        $base_url = self::getBaseUrl();
        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $_SERVER['REQUEST_URL'];
        $script_url = self::getScriptUrl();
        if (($pos = strpos($request_uri, '?')) !== false) {
            $request_uri = substr($request_uri, 0, $pos);
        }
        if ($script_url && strpos($request_uri, $script_url) === 0) {
            $path = substr($request_uri, strlen($script_url));
        } elseif ($base_url && strpos($request_uri, $base_url) === 0) {
            $path = substr($request_uri, strlen($base_url));
        } else {
            $path = $request_uri;
        }
        // 去除斜杠
        $path = trim($path, '/');
        // 去除多重无意义的重复性斜杠
        $path = preg_replace('/\/+/', '/', $path);
        return $path;
    }


    /**
     * 路由分发
     * @param
     */
    public static function dispatch()
    {
        $path = self::getRequestPath();
        $path = explode('/', $path);
        self::$_controller = empty($path[0]) ? ucfirst(C('DEFAULT_CONTROLLER')) : trim(strtolower($path[0]));
        self::$_action = empty($path[1]) ? ucfirst(C('DEFAULT_ACTION')) : trim(strtolower($path[1]));
        unset($path[0], $path[1]);

        //控制器定义
        define('CONTROLLER_NAME', self::$_controller);
        define('ACTION_NAME', self::$_action);

        $controller_path =  BASE_PATH. 'controller/' . self::$_controller . 'Controller.php';
        if (is_file($controller_path)) {
            require_once $controller_path;
        }
        $controller_class = self::$_controller. 'Controller';
        if (!class_exists($controller_class)) {
            self::notFound();
        }
        $controller_class = new $controller_class();
        $controller_action = strtolower(self::$_action);
        if (!method_exists($controller_class, $controller_action)) {
            self::notFound();
        }

        $controller_class->$controller_action();
    }

    /**
     * 获取根路径
     */
    public static function getBaseUrl()
    {
        static $base_url = null;
        if (null === $base_url) {
            $base_url = rtrim(dirname(self::getScriptUrl()), '\\/');
        }
        return $base_url;
    }

    /**
     * 获取脚本路径
     */
    public static function getScriptUrl()
    {
        return isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : $_SERVER['PHP_SELF'];
    }


    /**
     * 设置错误显示页面
     */
    public static function setErrorRedirect($redirect)
    {
        self::$_error_redirect = $redirect;
    }

    /**
     * 处理和生成按规则的链接
     * @param string $path
     * @return string
     */
    public static function parseLink($path)
    {
        if (strpos($path, '/') === 0) {
            $path = substr($path, 1);
        }
        return '/' . $path;
    }

    /**
     * 控制器不存在处理
     * @todo 这里需要可重写处理，先简单实现
     */
    public static function notFound()
    {
        if (self::$_error_redirect) {
            self::redirect(self::$_error_redirect);
        } else {
            echo '404 Page Not Found';
            exit;
        }
    }

    /**
     * url转向
     * @param string $url
     */
    public static function redirect($url)
    {
        if (!strpos($url, '://')) {
            $url = self::parseLink($url);
        }
        header('Location: ' . $url);
        exit;
    }

    /**
     * 获取controller
     */
    public static function getController()
    {
        return self::$_controller;
    }

    /**
     * 获取action
     */
    public static function getAction()
    {
        return self::$_action;
    }

    /**
     * 设置action
     */
    public static function setAction($action)
    {
        self::$_action = $action;
    }
}
