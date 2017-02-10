<?php

/**
 * 
 * ============================================================================
 * Copyright (c) 2015-2016 http://hemaquan.com All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：EcTouch.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTouch公共入口文件
 * ----------------------------------------------------------------------------
 * 
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ZFT') or die('Deny Access');

//if (version_compare(PHP_VERSION, '5.2.0', '<')) die('require PHP > 5.2.0 !');
defined('BASE_PATH') or define('BASE_PATH', dirname(__FILE__) . '/');
defined('ROOT_PATH') or define('ROOT_PATH', realpath(dirname(__FILE__) . '/../') . '/');
defined('APP_PATH') or define('APP_PATH', BASE_PATH . 'apps/');
defined('DEFAULT_APP') or define('DEFAULT_APP', 'default');
defined('DEFAULT_CONTROLLER') or define('DEFAULT_CONTROLLER', 'Index');
defined('DEFAULT_ACTION') or define('DEFAULT_ACTION', 'index');
defined('LOG_PATH') or define('LOG_PATH', ROOT_PATH . 'data/logs/');
defined('BIND_MODULE') or define('BIND_MODULE','default');
//禁止query访问方式
if(isset($_GET['m']) || isset($_GET['c'])  || isset($_GET['a']) || (isset($_GET['r']) && strpos('index.php',$_GET['r'])!==false)){
	echo '非法访问';
	exit;
}
/* 系统函数 */
require(BASE_PATH . 'Common.php');

/* 默认配置 */
C(load_file(BASE_PATH . 'Convention.php'));
/* 数据库配置 */
C('DB', load_file(ROOT_PATH . 'data/config.php'));
/* 设置时区 */
date_default_timezone_set(C('TIMEZONE'));
/* 调试配置 */
defined('DEBUG') or define('DEBUG', C('DEBUG'));
/* 版本信息 */
//load_file(ROOT_PATH . 'data/version.php');

/* 错误等级 */
if (DEBUG) {
    set_error_handler('fatalError1');
//    register_shutdown_function('fatalError');
    ini_set("display_errors", 1);
//    error_reporting(E_ALL ^ E_NOTICE); // 除了notice提示，其他类型的错误都报告
    error_reporting(E_ALL); // 所有错误都报告
	debug(); // system 运行时间，占用内存开始计算
} else {
    register_shutdown_function('fatalError');
    ini_set("display_errors", 0);
    error_reporting(0); // 把错误报告，全部屏蔽
}

/* 自动注册类文件 */
spl_autoload_register('autoload');
/* 网址路由解析 */
urlRoute();

try {
    /* 常规URL */
    defined('__HOST__') or define('__HOST__', get_domain());
    defined('__ROOT__') or define('__ROOT__', rtrim(dirname($_SERVER["SCRIPT_NAME"]), '\\/'));
    defined('__URL__') or define('__URL__', __HOST__ . __ROOT__);
    defined('__ASSETS__') or define('__ASSETS__', __ROOT__ . '/data/assets/' . APP_NAME);

    /* 控制器和方法 */
    $controller = CONTROLLER_NAME . 'Controller';
    $action = ACTION_NAME;

    /* 控制器类是否存在 */
    if (! class_exists($controller)) {
        //E(APP_NAME . '/' . $controller . '.class.php 控制器类不存在', 404);
        E('非法访问！', 404);
    }

    $obj = new $controller();//需要先初始化控制器(common)否则后续授权后 调用common类的静态方法出错  by moon 2016.08.25

    /* 是否非法操作 */
    if (! preg_match('/^[A-Za-z](\w)*$/', $action)) {
        //E(APP_NAME . '/' . $controller . '.class.php的' . $action . '() 方法不合法', 404);
        E('非法访问！', 404);
    }

    /* 控制器类中的方法是否存在 */
    if (! method_exists($obj, $action)) {
        //E(APP_NAME . '/' . $controller . '.class.php的' . $action . '() 方法不存在', 404);
        E('非法访问！', 404);
    }

    /* 执行当前操作 */
    $method = new ReflectionMethod($obj, $action);
    if ($method->isPublic() && ! $method->isStatic()) {
        $obj->$action();
    } else {
        /* 操作方法不是Public 抛出异常 */
        //E(APP_NAME . '/' . $controller . '.class.php的' . $action . '() 方法没有访问权限', 404);
        E('非法访问！', 404);
    }
} catch (Exception $e) {
    EcError::show($e->getMessage(), $e->getCode());
}
