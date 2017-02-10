<?php
/**
 * Created file smarty.php, 2016/12/23 上午10:58.
 * @author xiaowen
 * filename:smarty.php
 * Copyright (c) 2016 xiaowen. All rights reserved.
 */
class SeSmarty
{
    protected static $_smarty_config = array();
    protected static $_assign_config = array();
    protected static $_smarty = null;
    protected static $_smarty_function = array(
        'url'=>'url',
    );
    public function __construct()
    {
        if(self::$_smarty === null){
            self::getInstance();
        }
    }

    public static function getInstance()
    {
        require_once dirname(__FILE__).'/smarty3/Smarty.class.php';
        $smarty = new Smarty();
        self::$_smarty_config = C('SMARTY');
        foreach (self::$_smarty_config as $key => $value) {
            $smarty->$key = $value;
        }
        self::$_smarty = $smarty;
        self::register_function();
        return self::$_smarty;
    }

    public static function register_function()
    {
        //自定义smarty所用函数
        foreach(self::$_smarty_function as $p_func => $t_func){
            self::$_smarty->registerPlugin('function',$t_func,$p_func);
        }
    }

    public static function setAssign($assign)
    {
        self::$_assign_config = array_merge(self::$_assign_config, $assign);
    }

    public static function assign($key, $value)
    {
        self::setAssign(array($key => $value));
    }

    public function display($tpl, $cache_id)
    {
        //to_be_here
        foreach (self::$_assign_config as $key => $value) {
            self::$_smarty->assign($key, $value);
        }

        self::$_smarty->display($tpl, $cache_id);
    }
}