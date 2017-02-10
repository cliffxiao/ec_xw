<?php

class CommonController extends Controller
{
    protected static $sess = NULL;
    protected static $view = NULL;

    public function __construct($orgid = '')
    {
        parent::__construct();
        $this->se_init();
        $pt = C('PLATFORM');
        if($pt == 1){
            //微信用户处理
            $platform = model('Wechat')->getPlatform($orgid);
            call_user_func(array('WechatController', 'do_oauth'), $platform);
        }else{
            //web用户相关处理
//            call_user_func(array( 'UserController','check_login'));
        }
    }

    static function sess()
    {
        return self::$sess;
    }

    static function view()
    {
        return self::$view;
    }

    protected function assign($tpl_var, $value = '')
    {
        self::$view->assign($tpl_var, $value);
    }

    protected function display($tpl = '', $cache_id = '', $return = false)
    {
        $tpl = CONTROLLER_NAME.'/'.$tpl;
        self::$view->display($tpl, $cache_id);
    }

    protected function se_init()
    {
        header('Cache-control: private');
        header('Content-type: text/html; charset=utf-8');

        // 初始化session
        self::$sess = new SeSession();
        define('SESSION_ID', self::$sess->getId());

        //网站防刷处理
//        repeat_submit();

        // 创建 Smarty 对象
        self::$view = new SeSmarty();


        // 判断是否支持gzip模式
//        if (gzip_enabled()) {
//            ob_start('ob_gzhandler');
//        }
    }
}

class_alias('CommonController', 'SE_CONTROLLER');
