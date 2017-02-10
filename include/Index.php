<?php
defined('BASE_PATH') or define('BASE_PATH', dirname(__FILE__) . '/');//业务目录
defined('ROOT_PATH') or define('ROOT_PATH', realpath(dirname(__FILE__) . '/../') . '/');//网站根目录
defined('STATIC_PATH') or define('STATIC_PATH',ROOT_PATH . 'static/');//静态资源
defined('LIB_PATH') or define('LIB_PATH', BASE_PATH . 'library/');//类库

/* 系统函数 */
require(BASE_PATH . 'Common.php');
/* 配置 */
require(BASE_PATH . 'Config.php');
/* 设置时区 */
date_default_timezone_set(C('TIMEZONE'));
/* 调试配置 */
defined('DEBUG') or define('DEBUG', C('DEBUG'));

/* 错误等级 */
if (DEBUG) {
    set_error_handler('fatalError');
    register_shutdown_function('shutDown');
    ini_set("display_errors", 1);
    error_reporting(E_ALL); // 所有错误都报告
	debug(); // system 运行时间，占用内存开始计算
} else {
    register_shutdown_function('shutDown');
    ini_set("display_errors", 0);
    error_reporting(0); // 把错误报告，全部屏蔽
}

/*自定义常量*/
defined('__HOST__') or define('__HOST__', C('SITE_URL')); //网站域名

/* 自动注册类文件 */
spl_autoload_register('autoload');
/* 网址路由解析 */
require(BASE_PATH . 'library/Router.class.php');
Router::dispatch(); // URL调度
