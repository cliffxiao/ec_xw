<?php
/**
 * Created file confing.php, 2016/11/15 下午3:44.
 * @author xiaowen
 * filename:confing.php
 * Copyright (c) 2016 xiaowen. All rights reserved.
 */
C(null,array(
      'DEBUG' => true, // 是否开启调试模式，true开启，false关闭
      'DEFAULT_CONTROLLER' => 'Index', //默认的控制器
      'DEFAULT_ACTION' => 'Index', //默认的方法
      'ERROR_REDIRECT' => 'Error', //错误页面
      'LOG_PATH' => 'data/logs', //日志存放目录
      'URL_PATHINFO_DEPR' => '/', //路由变量间的间隔符
      'TIMEZONE' => 'PRC', // 时区设置
//      'DOMAIN' => '.xw.com', // 域名
      'SE_CHARSET' => 'utf-8', // 字符编码
      'PLATFORM' => 1, // 平台:1微信，2web
      'SITE_URL' => 'http://test.hemaquan.com',//网站域名

      //DA
      'DB'=>array(
          'master' => array(
              'host' => 'localhost',
              'user' => 'root',
              'pwd' => '',
              'port' => '3306',
              'charset' => 'utf8',
              'name' => 'se',
          ),
          'slave' => array(
              'host' => 'localhost',
              'user' => 'root',
              'pwd' => '',
              'port' => '3306',
              'charset' => 'utf8',
              'name' => 'se',
          ),
          'prefix' => 'se_',
//          'type' => 'mysql', //链接类型mysql原生，pdo和mysqli
          'type' => 'mysqlpdo', //TODO 链接类型mysql原生，pdo和mysqli
//          'type' => 'mysqli', //链接类型mysql原生，pdo和mysqli
      ),

      /*
       * memcache用于存储session和html缓存文件
       * redis数据业务逻辑缓存
       */
      //redis
      'REDIS' => array(
          'host' => '127.0.0.1',
          'port' => '6379',
          'user' => '',
          'pwd' => '',
      ),

      //memcache,用来存储session
      'MEMCACHE' => array(
          'host' => '127.0.0.1',
          'port' => 11211,
      ),
  ));

////其他配置
//C('WECHAT', array(
//    'appid' => 'wx188eab37e9c0d454',
//    'mchid' => '1370577502',
//    'key' => 'TzsKqT5sStMLU296EHyfjOs3QzVv99Gj',
//    'appsecret' => '55a387073ad64a78017b410b619e2248',
//));


C('SESSION',array(
    'save_handler' => 'memcache',
    'save_path' => C('MEMCACHE.host') . ':' . C('MEMCACHE.port'),
    'name' => 'seid',
//    'use_cookies' => 1,
//    'cache_expire' => 180,//分钟，默认
    'cookie_path' => '/',
//    'cookie_domain' => C('DOMAIN'),
    'cookie_lifetime' => 0,
    'gc_maxlifetime' => 1440,//默认24分钟
));

C('SMARTY', array(
    /**
     * 开发环境：caching=>false,debugging=>true
     * 测试环境：caching=>false,debugging=>false
     * 生成环境：caching=>true,debugging=>false
     */
    'caching' => false,//true时会直接读缓存，缓存在memcache
    'debugging' => true,//ture时会重新编译，false当文件被修改时重新编译，没有修改就直接读编译文件。
    'compile_dir' => ROOT_PATH.'data/cache/compiled/',//编译目录
    'template_dir' => ROOT_PATH.'template/',//页面目录
    'cache_lifetime' => 3600,//caching为true时的缓存时间
    'left_delimiter' => '{',
    'right_delimiter' => '}',
));




