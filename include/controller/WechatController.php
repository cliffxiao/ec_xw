<?php

class WechatController extends CommonController
{
    private $weObj = '';
    private $orgid = '';
    private $wechat_id = '';

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
        // 获取公众号配置
        $this->orgid = I('get.orgid','');
        if (! empty($this->orgid)) {
            $config = model('Wechat')->getPlatform($this->orgid);
            $this->weObj = new Wechat($config);
            $this->weObj->valid();
            $this->wechat_id = $config['id'];
        }
    }

    /**
     * 执行方法
     */
    public function index()
    {
        // 事件类型
        $type = $this->weObj->getRev()->getRevType();
        $wedata = $this->weObj->getRev()->getRevData();
        $keywords = '';
        if( $type == Wechat::MSGTYPE_TEXT ){                 //事件类型：文本
            $keywords = $wedata['Content'];
            if (! empty($keywords)) {
                $rs1 = $this->keywords_reply($keywords);
                if (empty($rs1)) {
                    $this->msg_reply('msg');
                }
            }
        } elseif ( $type == Wechat::MSGTYPE_EVENT ){         //事件类型：事件
            switch ( $wedata['Event'] ){
                case 'subscribe':
                    //关注时回复信息 事件
                    $this->msg_reply('subscribe');
                    $this->subscribe($wedata['FromUserName']);
                    break;
                case 'unsubscribe':
                    //取消关注 事件
                    $this->unsubscribe($wedata['FromUserName']);
                    break;
                case 'MASSSENDJOBFINISH':
                    // 群发结果
                    $data['status'] = $wedata['Status'];
                    $data['totalcount'] = $wedata['TotalCount'];
                    $data['filtercount'] = $wedata['FilterCount'];
                    $data['sentcount'] = $wedata['SentCount'];
                    $data['errorcount'] = $wedata['ErrorCount'];
                    // 更新群发结果
                    $this->model->table('wechat_mass_history')
                        ->data($data)
                        ->where('msg_id = "' . $wedata['MsgID'] . '"')
                        ->update();
                    break;
                case 'CLICK':
                    //$wedata = array( 'ToUserName' => 'gh_1ca465561479', 'FromUserName' => 'oWbbLt4fDrg78mvacsfpvi9Juo4I', 'CreateTime' => '1408944652', 'MsgType' => 'event', 'Event' => 'CLICK', 'EventKey' => 'ffff' );
                    // 点击菜单
                    $keywords = $wedata['EventKey'];
                    if (! empty($keywords)) {
                        $rs1 = $this->keywords_reply($keywords);
                        if (empty($rs1)) {
                            $this->msg_reply('msg');
                        }
                    }
                    break;
                case 'VIEW':
                    $this->redirect($wedata['EventKey']);
                    break;
                case 'TEMPLATESENDJOBFINISH':
                    //在模版消息发送任务完成后，微信服务器会将是否送达成功作为通知，发送到开发者中心中填写的服务器配置地址中。
                    /*$data = array(
                        'touser'        => $wedata['FromUserName'],
                        'msgtype'       => 'text',
                        'text'          => array(
                            'content'    => '无',
                        ),
                    );
                    if( $wedata['Status'] == 'success' ){
                        $data['text']['content'] = '消息发送成功！';
                    }
                    $this->weObj->sendCustomMessage($data);*/
                    break;
                default:
                    $this->msg_reply('msg');
                    exit();
            }
        }else{
            $this->msg_reply('msg');
            exit();
        }

    }

    /**
     * 关注处理
     *
     * @param array $info            
     */
    private function subscribe($openid = '')
    {
        // 用户信息
        $userinfo = $this->weObj->getUserInfo($openid);
        if (empty($userinfo)) {
            $info = array();
			exit;
        }
        $wechat_id=$this->wechat_id;

        $time = time();
        $unionid=isset($userinfo['unionid'])?$userinfo['unionid']:$openid;
        $ret = model('Common')->get_weixin_user_by_unionid($unionid);
        if (empty($ret)) {
            //会员注册
            $user_name = date('YmdHis') . rand(100, 999) . '@hemaquan.com';
            $data['user_name'] = $user_name;
            $data['password'] = md5('zft_wechat_user');
            $data['email'] = '';
            $data['reg_time'] = time();
            $data['user_rank'] = 99;//会员等级
            $data['source'] = 1;//注册来源0-APP；1-微信；2-PC；3-其他
            $data['is_validated'] = 1;//1生效，0为生效
            $data['platform'] = $wechat_id==1?1:2;//1代表卡券商城用户  2代表有折用户  3代表两者都使用的用户
            model('Common')->model->query('start transaction');
            $flag1 = model('Common')->model->table('users')->data($data)->insert();
            if($flag1){
                $data1['wechat_id'] = 1;//统一
                $data1['from_type'] = $wechat_id - 1;//0表示卡券商城来源 1表示天天有折来源
                $data1['subscribe'] = 1;
                $data1['openid'] =$wechat_id==1?$openid:'';
                $data1['nickname'] = isset($userinfo['nickname']) ? htmlspecialchars($userinfo['nickname']) : 'nickname1';
                $data1['nickname_str'] = $data1['nickname'];
                $data1['sex'] = isset($userinfo['sex']) ? $userinfo['sex'] : 3;
                $data1['city'] = isset($userinfo['city']) ? $userinfo['city'] : 'city1';
                $data1['country'] = isset($userinfo['country']) ? $userinfo['country'] : 'country1';
                $data1['province'] = isset($userinfo['province']) ? $userinfo['province'] : 'province1';
                $data1['language'] = $data1['country'];
                $data1['headimgurl'] = isset($userinfo['headimgurl']) ? $userinfo['headimgurl'] : 'headimgurl1';
                $data1['subscribe_time'] = $time;
                if (isset($userinfo['unionid'])) {
                    $data1['unionid'] = $userinfo['unionid'];
                }
                $data1['unionid'] = $unionid;
                $data1['ect_uid'] = $flag1;
                // 获取用户所在分组ID,暂时只有分组0
//                $group_id = $weObj->getUserGroup($userinfo['openid']);
//                $data1['group_id'] = $group_id ? $group_id : 0;
                $data1['group_id'] =  0;
                $flag2 = model('Common')->model->table('wechat_user')->data($data1)->insert();
                if($wechat_id==2){//有折同步更新对应的discount_wechat_user
                    $data2['uid']=$flag1;
                    $data2['subscribe']=1;
                    $data2['openid']=$openid;
                    $unionid=isset($userinfo['unionid'])?$userinfo['unionid']:'';
                    $data2['unionid']=$unionid;
                    $flag3=model('Common')->model->table('discount_wechat_user')->data($data2)->insert();
                }
            }

            if(!$flag1 || !$flag2){
                model('Common')->model->query('rollback');
            }
            model('Common')->model->query('commit');
        } else {
            if($wechat_id==1 && $ret['openid']==""){//补充卡券关注着的openid
                model('Common')->model->table("wechat_user")->data(array('openid'=>$openid))->where(array("ect_uid"=>$ret['ect_uid']))->update();//更新wechat_user表的openid
                model('Common')->model->query("UPDATE zft_users SET platform=(platform | 1) WHERE user_id=".$ret['ect_uid']);//更新平台来源
            }
            if($wechat_id==2 && $openid!="" && $unionid!=""){//处理天天有折的录入
                $dis = model('Common')->get_discount_weixin_user_by_unionid($unionid);
                if(empty($dis)){
                    $data2['uid']=$ret['ect_uid'];
                    $data2['subscribe']=1;
                    $data2['openid']=$openid;
                    $data2['unionid']=$unionid;
                    $flag3=model('Common')->model->table('discount_wechat_user')->data($data2)->insert();
                }
                model('Common')->model->query("UPDATE zft_users SET platform=(platform | 2) WHERE user_id=".$ret['ect_uid']);//更新平台来源
            }

            $info2['subscribe'] = 1;
            if($wechat_id==1){
                $where=array("ect_uid"=>$ret['ect_uid']);
                $this->model->table('wechat_user')
                    ->data($info2)
                    ->where($where)
                    ->update();
            }
            if($wechat_id==2){
                $where=array("uid"=>$ret['ect_uid']);
                $this->model->table('wechat_user')
                    ->data($info2)
                    ->where($where)
                    ->update();
            }

        }
    }

    /**
     * 取消关注
     *
     * @param string $openid            
     */
    public function unsubscribe($openid = '')
    {
        // 未关注
        $where['openid'] = $openid;
        $rs = $this->model->table('wechat_user')
            ->where($where)
            ->count();
        // 修改关注状态
        if ($rs > 0) {
            $data['subscribe'] = 0;
            if($this->wechat_id==1){
                $this->model->table('wechat_user')
                    ->data($data)
                    ->where($where)
                    ->update();
            }
            if($this->wechat_id==2){
                $this->model->table('discount_wechat_user')
                    ->data($data)
                    ->where($where)
                    ->update();
            }

        }
    }

    /**
     * 被动关注，消息回复
     *
     * @param string $type            
     */
    private function msg_reply($type)
    {
        $replyInfo = $this->model->table('wechat_reply')
            ->field('content, media_id')
            ->where('type = "' . $type . '" and wechat_id = ' . $this->wechat_id)
            ->find();
        if (! empty($replyInfo)) {
            if (! empty($replyInfo['media_id'])) {
                $replyInfo['media'] = $this->model->table('wechat_media')
                    ->field('title, content, file, type, file_name')
                    ->where('id = ' . $replyInfo['media_id'])
                    ->find();
                if ($replyInfo['media']['type'] == 'news') {
                    $replyInfo['media']['type'] = 'image';
                }
                // 上传多媒体文件
                $rs = $this->weObj->uploadMedia(array(
                    'media' => '@' . ROOT_PATH . $replyInfo['media']['file']
                ), $replyInfo['media']['type']);
                
                // 回复数据重组
                if ($rs['type'] == 'image' || $rs['type'] == 'voice') {
                    $replyData = array(
                        'ToUserName' => $this->weObj->getRev()->getRevFrom(),
                        'FromUserName' => $this->weObj->getRev()->getRevTo(),
                        'CreateTime' => time(),
                        'MsgType' => $rs['type'],
                        ucfirst($rs['type']) => array(
                            'MediaId' => $rs['media_id']
                        )
                    );
                } elseif ('video' == $rs['type']) {
                    $replyData = array(
                        'ToUserName' => $this->weObj->getRev()->getRevFrom(),
                        'FromUserName' => $this->weObj->getRev()->getRevTo(),
                        'CreateTime' => time(),
                        'MsgType' => $rs['type'],
                        ucfirst($rs['type']) => array(
                            'MediaId' => $rs['media_id'],
                            'Title' => $replyInfo['media']['title'],
                            'Description' => strip_tags($replyInfo['media']['content'])
                        )
                    );
                }
                $this->weObj->reply($replyData);
            } else {
                // 文本回复
                $replyInfo['content'] = strip_tags($replyInfo['content']);
                $this->weObj->text($replyInfo['content'])->reply();
            }
        }
    }

    /**
     * 关键词回复
     *
     * @param string $keywords            
     * @return boolean
     */
    private function keywords_reply($keywords)
    {
        $endrs = false;
        $sql = 'SELECT r.content, r.media_id, r.reply_type FROM ' . $this->model->pre . 'wechat_reply r LEFT JOIN ' . $this->model->pre . 'wechat_rule_keywords k ON r.id = k.rid WHERE k.rule_keywords = "' . $keywords . '" and r.wechat_id = ' . $this->wechat_id . ' order by r.add_time desc LIMIT 1';
        $result = $this->model->query($sql);
        if (! empty($result)) {
            // 素材回复
            if (! empty($result[0]['media_id'])) {
                $mediaInfo = $this->model->table('wechat_media')
                    ->field('title, content, file, type, file_name, article_id, link')
                    ->where('id = ' . $result[0]['media_id'])
                    ->find();
                
                // 回复数据重组
                if ($result[0]['reply_type'] == 'image' || $result[0]['reply_type'] == 'voice') {
                    // 上传多媒体文件
                    $rs = $this->weObj->uploadMedia(array(
                        'media' => '@' . ROOT_PATH . $mediaInfo['file']
                    ), $result[0]['reply_type']);
                    
                    $replyData = array(
                        'ToUserName' => $this->weObj->getRev()->getRevFrom(),
                        'FromUserName' => $this->weObj->getRev()->getRevTo(),
                        'CreateTime' => time(),
                        'MsgType' => $rs['type'],
                        ucfirst($rs['type']) => array(
                            'MediaId' => $rs['media_id']
                        )
                    );
                    // 回复
                    $this->weObj->reply($replyData);
                    $endrs = true;
                } elseif ('video' == $result[0]['reply_type']) {
                    // 上传多媒体文件
                    $rs = $this->weObj->uploadMedia(array(
                        'media' => '@' . ROOT_PATH . $mediaInfo['file']
                    ), $result[0]['reply_type']);
                    
                    $replyData = array(
                        'ToUserName' => $this->weObj->getRev()->getRevFrom(),
                        'FromUserName' => $this->weObj->getRev()->getRevTo(),
                        'CreateTime' => time(),
                        'MsgType' => $rs['type'],
                        ucfirst($rs['type']) => array(
                            'MediaId' => $rs['media_id'],
                            //'Title' => $replyInfo['media']['title'],
                            'Title' => 'text_title',
                            //'Description' => strip_tags($replyInfo['media']['content'])
                            'Description' => strip_tags('media_content')
                        )
                    );
                    // 回复
                    $this->weObj->reply($replyData);
                    $endrs = true;
                } elseif ('news' == $result[0]['reply_type']) {
                    // 图文素材
                    $articles = array();
                    if (!empty($mediaInfo['article_id'])) {
                        $artids = explode(',', $mediaInfo['article_id']);
                        foreach ($artids as $key => $val) {
                            $artinfo = $this->model->table('wechat_media')
                                ->field('title, file, content, link')
                                ->where('id = ' . $val)
                                ->find();
                            $artinfo['content'] = strip_tags(html_out($artinfo['content']));
                            $articles[$key]['Title'] = $artinfo['title'];
                            $articles[$key]['Description'] = $artinfo['content'];
                            $articles[$key]['PicUrl'] = __URL__ . '/' . $artinfo['file'];
                            $articles[$key]['Url'] = $artinfo['link'];
                        }
                    } else {
                        $articles[0]['Title'] = $mediaInfo['title'];
                        //$articles[0]['Description'] = strip_tags(html_out($mediaInfo['content']));
                        $articles[0]['Description'] = $mediaInfo['digest'];
                        $articles[0]['PicUrl'] = __URL__ . '/' . $mediaInfo['file'];
                        $articles[0]['Url'] = $mediaInfo['link'];
                    }
                    // 回复
                    $this->weObj->news($articles)->reply();
                    $endrs = true;
                }
            } else {
                // 文本回复
                $result[0]['content'] = strip_tags($result[0]['content']);
                $this->weObj->text($result[0]['content'])->reply();
                $endrs = true;
            }
        }
        return $endrs;
    }

    /**
     * 获取用户昵称，头像
     *
     * @param unknown $user_id            
     * @return multitype:
     */
    public static function get_avatar($user_id)
    {
        $u_row = model('Common')->model->table('wechat_user')
            ->field('nickname, headimgurl')
            ->where('ect_uid = ' . $user_id)
            ->find();
        if (empty($u_row)) {
            $u_row = array();
        }
        return $u_row;
    }

    /**
     * 微信OAuth操作
     * @param $id表示公众号的id
     */
    static function do_oauth($wxinfo)
    {
        if (!empty($wxinfo) && $wxinfo['type'] == 2) {
            $config['token'] = $wxinfo['token'];
            $config['appid'] = $wxinfo['appid'];
            $config['appsecret'] = $wxinfo['appsecret'];
            // 微信验证
            require LIB_PATH .'Wechat.php';
            $weObj = new Wechat($config);
            if(isWeixin()){
                //微信只能用户访问
                if(!isset($_SESSION['user_id']) || $_SESSION['user_id'] < 1){
                    if (isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI'])) {
                        $redirecturi = __HOST__ . $_SERVER['REQUEST_URI'];
                    } else {
                        $redirecturi = $wxinfo['oauth_redirecturi'];
                    }
                    $url = $weObj->getOauthRedirect($redirecturi, 1);
                    if (isset($_GET['code']) && $_GET['code'] != 'authdeny') {
                        $token = $weObj->getOauthAccessToken();
                        if ($token) {
                            $userinfo = $weObj->getOauthUserinfo($token['access_token'], $token['openid']);
                            self::update_weixin_user($userinfo, $wxinfo['id'], $weObj);
                        } else {
                            header('Location:' . $url, true, 302);
                        }
                    } else {
                        header('Location:' . $url, true, 302);
                        exit;
                    }
                }else{
                    self::user_entrance();
                }
            }else{
                //浏览器只能接口访问
                //允许web访问$controller-$action接口
                $web_allow = array(
                    'CouponorderController-notify', //卡券消费回调
                    'WechatController-index', //微信事件推送
                    'WxpayController-notify', //支付结果通知
                    'GroupbuyController-notify', //支付结果通知
                    'VoucherController-notify', //代金券支付结果通知
                    'ZheController-notify', //天天有折支付结果通知
                );

                $access = CONTROLLER_NAME . 'Controller-' . ACTION_NAME;
                if (!in_array($access, $web_allow)) {
                    echo "<div style='text-align:center;margin:3em 0;'>请使用微信访问！</div>";
                    exit;
                }
            }
        }
    }

    /**
     * 更新微信用户信息
     *
     * @param unknown $userinfo            
     * @param unknown $wechat_id            
     * @param unknown $weObj            
     */
    static function update_weixin_user($userinfo, $wechat_id, $weObj)
    {
        $unionid = isset($userinfo['unionid']) ? $userinfo['unionid'] : '';
        if($unionid==""){
            exit('授权失败，如重试一次还未解决问题请联系管理员');
        }

        $userid = model('Wechat')->getUserByUnionid($userinfo['unionid']);
        if (!$userid) {
            //会员注册
            $data['username'] = uniqid();
            model('Common')->model->start_trans();
            $userid = model('Common')->model->table('users')->data($data)->insert();
            //增加用户
            if($userid){
                $platform = 'platform_'.$wechat_id;
                $wechat_user = array(
                    'uid'=>$userid,
                    'unionid'=> $userinfo['unionid'],
                    $platform=>1,
                );
                $flag1 = model('Common')->model->table('wechat_user')->data($wechat_user)->insert();
                if($flag1){
                    $w_info['subscribe'] = 0;
                    $w_info['openid'] = isset($userinfo['openid']) ? $userinfo['openid'] : 'openid1';
                    $w_info['nickname'] = isset($userinfo['nickname']) ? htmlspecialchars($userinfo['nickname']) : 'nickname1';
                    $w_info['nickname_str'] = $w_info['nickname'];
                    $w_info['sex'] = isset($userinfo['sex']) ? $userinfo['sex'] : 0;
                    $w_info['city'] = isset($userinfo['city']) ? $userinfo['city'] : 'city1';
                    $w_info['country'] = isset($userinfo['country']) ? $userinfo['country'] : 'country1';
                    $w_info['province'] = isset($userinfo['province']) ? $userinfo['province'] : 'province1';
                    $w_info['language'] = $w_info['country'];
                    $w_info['headimgurl'] = isset($userinfo['headimgurl']) ? $userinfo['headimgurl'] : 'headimgurl1';
                    $w_info['unionid'] = $unionid;
                    $w_info['uid'] = $userid;
                    // 获取用户所在分组ID,暂时只有分组0
                    $w_info['group_id'] = isset($userinfo['groupid']) ? $userinfo['groupid'] : 0;

                    $platform_table = 'wechat_user_'.$wechat_id;
                    $flag2 = model('Common')->model->table($platform_table)->data($w_info)->insert();

                    $salt = substr($data['username'], -4, 4);
                    $user_info = array(
                        'uid' => $userid,
                        'username' => $data['username'],
                        'password' => md5('666666' . $salt),
                        'salt' => $salt,
                        'reg_time' => NOW_TIME,
                        'source' => 1, //注册来源0-APP；1-微信；2-PC；3-其他
                        'is_validated' => 1, //1生效，0为生效
                    );
                    $user_table = 'users_'.($userid % 10);
                    echo $user_table;
                    $flag3 = model('Common')->model->table($user_table)->data($user_info)->insert();
                }
            }

            if(!$userid || !$flag1 || !$flag2 || !$flag3){
                model('Common')->model->rollback_trans();
                die('授权失败，如重试一次还未解决问题请联系管理员');
            }
            model('Common')->model->commit_trans();
        }

        //设置session
        $_SESSION['user_id'] = $userid;
        //用户登录控制
        self::user_entrance();

        //登陆成功更新会员信息
        model('Users')->update_user_info();
    }

    /*
     * 用户能否访问判断
     */
    static function user_entrance()
    {
        //浏览器判断
        $user_id = $_SESSION['user_id'];
        //设置cookie
        setcookie("user_id", $user_id, NOW_TIME + 3600, '/', '');
        //TODO 做redis缓存禁止登录的用户
        $user_table = 'users_' . ($user_id % 10);
        $entrance = model('Common')->model->table($user_table)->field('entrance')->where(array('uid' => $user_id))->getOne();
        if ($entrance !== false && $entrance == 0) {
            SE_CONTROLLER::view()->assign('page_title', '访问受限');
            SE_CONTROLLER::view()->display('err/forbidden.dwt');
            exit;
        }
    }

    /*
     * 客服发送消息接口
     */
    public function sendCustomMessage(){
        $data = array(
            'touser'        => 'oGrf0v4I3HIW1O8jf9pFi73Sgf8U',
            'msgtype'       => 'text',
            'text'          => array(
                'content'    => 'Hello World',
            ),
        );
        $this->weObj->sendCustomMessage($data);
    }

    /*
     * 获取需要更新的用户信息，此处取表里所有数据
     */
    public function update_wechat_users()
    {
        set_time_limit(0);
        $wxinfo = model('Common')->model->table('wechat')
            ->field('id, token, appid, appsecret, oauth_redirecturi, type')
            ->where('default_wx = 1 and status = 1')
            ->find();
        $config['token'] = $wxinfo['token'];
        $config['appid'] = $wxinfo['appid'];
        $config['appsecret'] = $wxinfo['appsecret'];

        //微信
        $weObj = new Wechat($config);
        $counts = model('Wechat')->user_count();

        $num = 50;//批次处理数量
        for($i = 0; $i < $counts;$i += 50){
            $wechat_users = model('Wechat')->wechatUsers($i,$num);
            foreach($wechat_users as $openid){
                call_user_func(array('WechatController','update_user_info'), $weObj,$openid);
            }
        }
        writeLog('微信用户信息更新完毕!');
    }

    /*
     * 更新具体openid用户的信息
     */
    public function update_user_info($weObj,$openid)
    {
        $userinfo = $weObj->getUserInfo($openid);
        if($userinfo === false){
            return false;
        }

        if(isset($userinfo['subscribe']) && $userinfo['subscribe'] == 0){
            return false;
        }

        if(isset($userinfo['nickname']) && !empty($userinfo['nickname'])){
            $data3['nickname'] = htmlspecialchars($userinfo['nickname']);
        }else{
            return false;
        }

        $data3['subscribe'] = isset($userinfo['subscribe']);
        $data3['nickname_str'] = $data3['nickname'];
        $data3['sex'] = $userinfo['sex'];
        $data3['language'] = $userinfo['language'];
        $data3['city'] = $userinfo['city'];
        $data3['province'] = $userinfo['province'];
        $data3['country'] = $userinfo['country'];
        $data3['headimgurl'] = $userinfo['headimgurl'];
        $data3['subscribe_time'] = $userinfo['subscribe_time'];
        $data3['unionid'] = $userinfo['unionid'];
        $data3['group_id'] = $userinfo['groupid'];
        $where = array(
            'openid'=>$openid,
        );
        model('Common')->model->table('wechat_user')->data($data3)->where($where)->update();
        writeLog($openid . "用户信息更新完毕.");
    }
}
