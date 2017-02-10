<?php
/**
 * Created file Controller.php, 2016/12/23 上午11:06.
 * @author xiaowen
 * filename:Controller.php
 * Copyright (c) 2016 xiaowen. All rights reserved.
 */
class Controller
{
    protected $model = NULL; // 数据库模型
    protected $layout = NULL; // 布局视图

    public function __construct()
    {
        $this->model = model('Common')->model;
        // 定义当前请求的系统常量
        defined('NOW_TIME') || define('NOW_TIME', $_SERVER ['REQUEST_TIME']);
        defined('REQUEST_METHOD') || define('REQUEST_METHOD', $_SERVER ['REQUEST_METHOD']);
        defined('IS_GET') || define('IS_GET', REQUEST_METHOD == 'GET' ? true : false);
        defined('IS_POST') || define('IS_POST', REQUEST_METHOD == 'POST' ? true : false);
        defined('IS_PUT') || define('IS_PUT', REQUEST_METHOD == 'PUT' ? true : false);
        defined('IS_DELETE') || define('IS_DELETE', REQUEST_METHOD == 'DELETE' ? true : false);
        defined('IS_AJAX') || define('IS_AJAX', (isset($_SERVER ['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER ['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'));

        $this->_initialize();
    }

    private function _initialize()
    {
        //初始化设置
        @ini_set('memory_limit', '64M');
        @ini_set("arg_separator.output", "&amp;");
        @ini_set('include_path', '.;' . BASE_PATH);

        //加载系统常量和函数库
        require(BASE_PATH . 'base/constant.php');
        require(BASE_PATH . 'base/function.php');
        //对用户传入的变量进行转义操作
        if (!get_magic_quotes_gpc()) {
            if (!empty($_GET)) {
                $_GET = addslashes_deep($_GET);
            }
            if (!empty($_POST)) {
                $_POST = addslashes_deep($_POST);
            }
            $_COOKIE = addslashes_deep($_COOKIE);
            $_REQUEST = addslashes_deep($_REQUEST);
        }
        //载入系统参数
//        C('CFG', model('Common')->load_config());
    }

    // 直接跳转
    protected function redirect($url, $code = 302)
    {
        header('location:' . $url, true, $code);
        exit();
    }

    // 操作成功之后跳转,默认三秒钟跳转
    protected function message($msg, $url = NULL, $type = 'succeed', $waitSecond = 2)
    {
        if ($url == NULL)
            $url = 'javascript:history.back();';
        if ($type == 'error') {
            $title = '错误信息';
        } else {
            $title = '提示信息';
        }
        $data ['title'] = $title;
        $data ['message'] = $msg;
        $data ['type'] = $type;
        $data ['url'] = $url;
        $data ['second'] = $waitSecond;
        $this->assign('data', $data);
        $this->display('message');
        exit();
    }

    // 页面跳转，默认跳回前一页
    public function alert($message = '', $url = '')
    {
        if ($url == '') {
            $url = empty($_SERVER['HTTP_REFERER']) ? '/' : $_SERVER['HTTP_REFERER'];
        }

        if ($message) {
            echo '<script>alert("', $message, '");document.location.href="', $url, '";</script>';
        } else {
            echo '<script>document.location.href="', $url, '";</script>';
        }

        exit;
    }

    /**
     * json返回数据格式
     * @param array $arr
     */
    public function returnJson($arr)
    {
        exit(json_encode($arr));
    }


    // 弹出信息
    protected function alertNotice($type, $msg, $url1 = '', $url2 = '', $btn = '')
    {
        if ($type == 2 && empty($url1)) {
            $len = strlen(C("DB.SITE_URL"));
            if (isset($_SERVER['HTTP_REFERER']) && substr($_SERVER['HTTP_REFERER'], 0, $len) == C("DB.SITE_URL")) {
                $url1 = $_SERVER['HTTP_REFERER'];
            } else {
                $url1 = C("DB.SITE_URL");
            }
        }

        if ($btn == '') {
            $btn = '确定';
        }

        echo '
            <!DOCTYPE html>
            <html lang="zh-CN">
                <head>
                    <meta charset="utf-8">
                    <meta name="format-detection" content="email=no" />
                    <meta name="format-detection" content="telephone=no" />
                    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
                    <title>提示</title>
                    <script type="text/javascript" src="/themes/default/js/jquery.min.js" ></script>
                </head>
                <body>
                    <style>
                        .notice { position:fixed; top:0; left:0; z-index:1000; width:100%; height:100%; background:rgba(0,0,0,0.4); display:none; }
                        .notice i{font-style: normal;}
                        .notice h3.scale-title{position:relative;text-align: left;text-indent: 1rem;padding: 1rem 0;margin: 0;background: #fff;border-bottom: 1px solid #ccc;display: none;}
                        .notice h3 .noticemend{position:absolute; right:7px; top:10px; width:30px; height:30px; border: 0; font-weight:400; background: transparent; cursor: pointer; -webkit-appearance: none; font-size:30px;}
                        .notice h3 .noticemend::before, .noticemend::after{position:absolute; left:5px; top:15px; content:""; width:18px; height:1px; background-color:#999; transform:rotate(45deg); -webkit-transform:rotate(45deg); border-radius: 5px;}
                        .notice h3 .noticemend::after{transform:rotate(-45deg);  -webkit-transform:rotate(-45deg);}
                        .notice center { position:absolute; left:50%; width:60%; z-index:1001; text-align:center; top:50%; background:#fff; border-radius:5px; overflow:hidden; margin:-80px 0 0 -30%; }
                        .notice center .notice-content { line-height:22px; color:#222; font-size:14px;display:block; padding:20px 10px; }
                        .notice center .notice-btn { border-top:1px solid #ededed;line-height:40px; font-size:14px; cursor:pointer;display: none;}
                        .notice center .notice-btn-group{position:relative; height:40px; line-height:40px; font-size:0; text-align:center;border-top:1px solid #ededed; display: none;}
                        .notice center .notice-btn-group:before{content:"\20"; position:absolute; width:1px; height:39px; left:50%; top:0; background-color:#EBEBEB;}
                        .notice center .notice-btn-group span{ text-overflow:ellipsis; overflow:hidden; white-space:nowrap;}
                        .notice center .notice-btn-group span{position:relative; display:inline-block; width:50%; text-align:center; font-size:14px; cursor:pointer; border-radius: 0 5px 0 0;}
                        .notice center .notice-btn-group span:first-child{height:39px; background-color:#fff; border-radius: 0 0 0 5px;}
                        .scale { -webkit-animation:scale .3s ease both; -moz-animation:scale .3s ease both; animation:scale .3s ease both; }
                        @-webkit-keyframes scale { 0% { -webkit-transform:scale(1.5); transform:scale(1.5); opacity:0; } 100% { -webkit-transform:scale(1); transform:scale(0%); opacity:1; } }
                        @keyframes scale { 0% { -webkit-transform:scale(1.5); transform:scale(1.5); opacity:0;} 100% { -webkit-transform:scale(1); transform:scale(0%); opacity:1; } }
                        .notice-loading{  width: 100%;  padding-top: 1em;  text-align: center;  height: 4em;  line-height: 4rem;  }
                        #circle{  position: relative;  top:0.6em;  margin-right: 1em;  display: inline-block;  background-color: rgba(0,0,0,0);  border:5px solid rgba(20,20,20,0.9);  opacity:.9;  border-right:5px solid rgba(0,0,0,0);  border-radius:50%;  box-shadow: 0 0 1em #202020;  width:2em;  height:2em;  -webkit-animation:spinoffPulse 1s infinite linear;  -ms-animation:spinoffPulse 1s infinite linear;  }
                        @-webkit-keyframes spinoffPulse { 0% { -webkit-transform:rotate(0deg); } 100% { -webkit-transform:rotate(360deg); } }
                    </style>
                    <!--弹窗开始-->
                    <div id="center" class="notice">
                        <center class="scale">
                            <div class="notice-content">
                            </div>
                            <span class="notice-btn notice-clo" id="notice-btn"></span>
                            <div class="notice-btn-group" id="notice-btn-group">
                                <span class="notice-clo notice-cancel">取消</span>
                                <span class="notice-clo notice-sure">确认</span>
                            </div>
                        </center>
                    </div>
                    <!--弹窗结束-->
                    <script>
                        /** 参数 type 弹窗类型 1、只有内容的弹窗 2、内容加一个“确认”按钮的弹窗 3、内容加两个按钮（“确认”，“取消”）的弹窗
                         * 弹窗重复调用会覆盖，不会生成新的弹窗
                         * msg 弹窗里面消息类型
                         * url1 弹窗点击确认 跳转的url 可不填    如果需要添加点击调用函数，url1就填写函数名字（暂不支持传参） example:openTc(2,"弹窗",noticeCallBack);
                         * url2 弹窗点击取消 跳转的url 可不填
                         * title 带标题的弹窗（现在没有）
                         * example:openTc(1,"弹窗");openTc(2,"弹窗","http://www.baidu.com");openTc(3,"弹窗","http://www.baidu.com","http://www.baidu.com");
                         */
                        function openTc(type,msg,url1,url2,btn){
                            if( type == 1 ){                                                                    //第一种弹窗
                                $("#center").css({display:"block"});                  //弹窗显示
                                $("#center .notice-content").css({padding:"2px","background-color":"rgba(0,0,0,0.6)",color:"#fff"}).html(msg);
                                setTimeout(function() {
                                    $("#center").css("display","none");//两秒后关闭
                                }, 2000);
                            }else if( type == 2 ){                                                             //第二种弹窗
                                $("#center").css("display","block");                                        //弹窗显示
                                $("#notice-btn").css("display","block");                                   //单个按钮显示
                                $("#notice-btn-group").css("display","none");                             //多个按钮隐藏
                                $("#center .notice-content").html(msg);
                                $("#center .notice-btn").html(btn);
                                $("#notice-btn").click(function(){
                                    noticeUrl(url1);
                                });
                            }else if( type == 3 ){                                                              //第三种弹窗
                                $("#center").css("display","block");                                        //弹窗显示
                                $("#notice-btn").css("display","none");                                   //弹窗显示
                                $("#notice-btn-group").css("display","block");                             //多个按钮隐藏
                                $("#center .notice-content").html(msg);
                                $(".notice-sure").click(function(){
                                    noticeUrl(url1);
                                });
                                $(".notice-cancel").click(function(){
                                    noticeUrl(url2);
                                });
                            }
                            //点击单个按钮确认 弹窗隐藏
                            var len = document.querySelectorAll(".notice-clo").length; //获取有几个按钮可以关闭弹窗
                            for( var i = 0; i< len;i++ ){
                                document.querySelectorAll(".notice-clo")[i].onclick = function(){
                                    document.querySelector("#center").style.display = "none";
                                };
                            }
                        }
                        /* url跳转 */
                        function noticeUrl(url) {
                            if( url && typeof(url) == "function" ){ //判断是否含有回调函数
                                url();
                            }else if( url ){
                                window.location.href = url;
                            }
                        }
                        var type = "' . $type . '";
                        var msg = "' . $msg . '";
                        var url1 = "' . $url1 . '";
                        var url2 = "' . $url2 . '";
                        var btn = "' . $btn . '";
                        openTc(type,msg,url1,url2,btn);
                    </script>
                </body>
            </html>
        ';
        exit();
    }

    // 弹出信息可选消息点击关闭页面
    protected function alertMsg($msg, $closeWin = false)
    {
        $signPackage = Wechat()->GetSignPackage();
        $this->assign('signPackage', $signPackage);
        $this->assign('msg', $msg);
        $this->assign('closeWin', $closeWin);
        $this->display('alert_msg.dwt');
        exit();
    }

    // 出错之后返回json数据
    protected function jserror($msg)
    {
        echo json_encode(array(
                             "msg" => $msg,
                             "result" => '0'
                         ));
        exit();
    }

    // 成功之后返回json
    protected function jssuccess($msg, $data =array())
    {
        echo json_encode(array(
                             "msg" => $msg,
                             "data" => $data,
                             "result" => '1'
                         ));
        exit();
    }

    // 获取分页查询limit
    protected function pageLimit($url, $num = 10)
    {
        $url = str_replace(urlencode('{page}'), '{page}', $url);
        $page = is_object($this->pager ['obj']) ? $this->pager ['obj'] : new Page ();
        $cur_page = $page->getCurPage($url);
        $limit_start = ($cur_page - 1) * $num;
        $limit = $limit_start . ',' . $num;
        $this->pager = array(
            'obj' => $page,
            'url' => $url,
            'num' => $num,
            'cur_page' => $cur_page,
            'limit' => $limit
        );
        return $limit;
    }

    // 分页结果显示
    protected function pageShow($count)
    {
        return $this->pager ['obj']->show($this->pager ['url'], $count, $this->pager ['num']);
    }

}