<?php
/* 访问控制 */
defined('IN_ZFT') or die('Deny Access');

class UserController extends CommonController
{

    protected $user_id;

    protected $action;

    protected $back_act = '';

    protected $info;

    protected $key = 'User';

    protected $tokenid;

    /**
     * 构造函数
     */
    public function __construct(){
        parent::__construct();
        // 属性赋值
        $this->action = ACTION_NAME;
        // 验证登录
        $this->check_login();
//        if (!IS_POST) {
//        	exit();
//        }
        $tokenid = I('request.tokenid');
        if (isset($tokenid)){
            $this->user_id = ec_decode($tokenid,$this->key);
            $_SESSION['user_id'] = $this->user_id;
        }
        writeLog('request:action:'.$this->action.';user_id:'.$this->user_id.''.json_encode(I('request.')),$this->log_flag);
    }

    /**
     * 会员中心欢迎页
     */
    public function index(){
        // 用户信息
        $info = model('Users')->get_user_default($this->user_id);

        $this->msg->show(true,$info);
    }

    /**
     * 资金管理
     */
    public function account_detail()
    {
        // 获取剩余余额
        $surplus_amount = model('Users')->get_user_surplus($this->user_id);
        if (empty($surplus_amount)) {
            $surplus_amount = 0;
        }

        // 获取余额记录
        $account_log = array();
        $where['user_id'] = $this->user_id;
        $where['user_money'] = array("NEQ", 0);
        $res = M('account_log')->where($where)
                               ->order('log_id DESC')
                               ->select();
        if (empty($res)) {
            $res = array();
        }

        foreach ($res as $k => $v) {
            $res[$k]['change_time'] = date(C('date_format'), $v['change_time']);
            $res[$k]['type'] = $v['user_money'] > 0 ? '增加' : '减少';
            $res[$k]['user_money'] = abs($v['user_money']);
            $res[$k]['frozen_money'] = abs($v['frozen_money']);
            $res[$k]['rank_points'] = abs($v['rank_points']);
            $res[$k]['pay_points'] = abs($v['pay_points']);
            $res[$k]['short_change_desc'] = sub_str($v['change_desc'], 60);
            $res[$k]['amount'] = $v['user_money'];
        }
        $result['account_list'] = $res;
        $result['surplus_amount'] = $surplus_amount;
        $this->msg->show(false,$result);
    }

    /**
     * 登录
     */
    public function login(){
        // 登录处理
        $user_name = I('post.username', '13621543148');
        $password = I('post.password', '12345a');
		$this->back_act = urldecode(I('post.back_act'));
		if(model('Users')->check_login($user_name, $password) != true) {
            $this->msg->show(false);
		}
        $username = model('Users')->get_username($user_name);
		if (self::$user->login($username, $password, isset($_POST['remember']))) {
			model('Users')->update_user_info();
            $this->user_id = I('session.user_id');
			$result[] = '登录成功';
			$result['code'] = '0000';
			$result['is_gesture'] = I('session.is_gesture', 0, 'intval');
			$result['user_gesture'] = I('session.user_gesture');
            $result['user_id'] = $this->user_id;
            $result['tokenid'] = ec_encode($this->user_id,$this->key);
            $this->msg->show(true,$result);
		} else {
            model('Users')->set_count_inc('error');
			$result[] = '手机号与密码不匹配';
			$result['code'] = '0007';
            $this->msg->show(false,$result);
		}
    }

    /**
     * 验证手机
     */
    public function mobile(){
		$mobile = I('post.mobile');
		$back_act = I('post.back_act', '', 'trim');

		$rules[] = array(Check::regex($mobile, 'require'), array('手机号不能为空', 'code'=>'0001'));
		$rules[] = array(Check::regex($mobile, 'mobile'), array('手机号码不是一个有效号码', 'code'=>'0002'));

		$result = Check::rule($rules);
		if(is_array($result)) {
            $this->msg->show(false,$result);
		}else{
			$_SESSION['mobile'] = $mobile;
			$array['code'] = '0000';
            $array['count'] = model('Users')->check_userinfo('mobile_phone',$mobile);
            $this->msg->show(true,$array);
		}
    }

    /**
     * 注册
     */
    public function register(){
		$enabled_sms = I('post.enabled_sms', 1, 'intval');
		$password = I('post.password');
		$email = I('post.email');
		$agreement = I('post.agreement');
		$this->back_act = I('post.back_act');
		$other = array();

		$rules[] = array(Check::regex($agreement, 'require'), array('您没有接受协议', 'code'=>'0001'));
        $other['mobile_phone'] = $username = I('post.mobile');

        $username = 'app_'.uniqid();
        $other['user_attribute'] = 0; // 个人属性

        $mobile_code = I('post.mobile_code');
        $rules[] = array(Check::regex($mobile_code, 'require'), array('短信验证码不能为空', 'code'=>'0005'));
        $rules[] = array(Check::same($mobile_code, I('session.sms_mobile_code')), array('手机验证码错误', 'code'=>'0006'));
		$rules[] = array(Check::regex($password, 'require'), array('登录密码不能为空', 'code'=>'0012'));
		$rules[] = array(Check::len($password, 6, 20), array('登录密码长度在 6 到 20 个字符之间', 'code'=>'0013'));
		$rules[] = array(!Check::regex($password, 'number'), array('登录密码为6-20位字母、数字组合', 'code'=>'0014'));
		$rules[] = array(!Check::regex($password, 'english'), array('登录密码为6-20位字母、数字组合', 'code'=>'0014'));
        $rules[] = array(Check::regex($password, 'password'), array('登录密码为6-20位字母、数字组合', 'code'=>'0014'));
		//$rules[] = array(!Check::regex($password, 'symbol'), array('登录密码为6-20位字母、数字组合', 'code'=>'0014'));
		$rules[] = array(!strpos($password, ' '), array('密码中不能包含空格', 'code'=>'0015'));

		$result = Check::rule($rules);
		if(is_array($result)) {
            $this->msg->show(false,$result);
		}

		if (model('Users')->register($username, $password, $email, $other) !== false) {
			$ucdata = empty(self::$user->ucdata) ? "" : self::$user->ucdata;
            $this->user_id = I('session.user_id');
			$array[] = '注册成功';
			$array['code'] = '0000';
			$array['is_gesture'] = I('session.is_gesture');
			$array['user_gesture'] = I('session.user_gesture');
            $array['user_id'] = $this->user_id;
            $array['tokenid'] = ec_encode($this->user_id,$this->key);
            $this->msg->show(true,$array);
		} else {
            $array[] = '注册失败';
		    $array['code'] = '0002';
            $this->msg->show(false,$array);
		}
    }

    /**
     * 手机找回密码
     */
    public function get_password_phone()
    {
		$mobile = I('post.mobile');
		$password = I('post.password');
		$mobile_code = I('post.mobile_code');
		//$sms_code = I('post.sms_code');
		//$rules[] = array(Check::same($sms_code, I('session.sms_code')), array('验证码不匹配', 'code'=>'0001'));
		$rules[] = array(Check::regex($mobile_code, 'require'), array('手机验证码错误', 'code'=>'0002'));
		$rules[] = array(Check::same($mobile_code, I('session.sms_mobile_code')), array('手机验证码错误', 'code'=>'0003'));
		$rules[] = array(Check::regex($password, 'require'), array('登录密码不能为空', 'code'=>'0004'));
		$rules[] = array(Check::len($password, 6, 20), array('登录密码长度在 6 到 20 个字符之间', 'code'=>'0005'));
		$rules[] = array(!Check::regex($password, 'number'), array('登录密码为6-20位字母、数字组合', 'code'=>'0006'));
		$rules[] = array(!Check::regex($password, 'english'), array('登录密码为6-20位字母、数字组合', 'code'=>'0006'));
        $rules[] = array(Check::regex($password, 'password'), array('登录密码为6-20位字母、数字组合', 'code'=>'0006'));
		$rules[] = array(!strpos($password, ' '), array('密码中不能包含空格', 'code'=>'0007'));

		$result = Check::rule($rules);
		if(is_array($result)) {
            $this->msg->show(false,$result);
		}
        $user_name = model('Users')->get_username($mobile);
        if (empty($user_name)){
            $array[] = '账号不存在';
            $array['code'] = '0008';
            $this->msg->show(false,$array);
        }
		$cfg['user_name'] = $user_name;
		$cfg['password'] = $password;
		if(self::$user->edit_user($cfg)){
			self::$user->logout();
            model('Users')->clear_count('error');
			$array[] = '设置成功，请使用新密码登录';
			$array['code'] = '0000';
            $this->msg->show(true,$array);
		} else {
			$array[] = '您输入的密码不正确';
			$array['code'] = '0008';
            $this->msg->show(false,$array);
		}
    }

    /**
     * 修改密码
     */
    public function edit_password(){
		$old_password = I('post.old_password');
		$new_password = I('post.new_password');
		$re_password = I('post.re_password');
		$user_id = I('post.uid', $this->user_id);
		$code = I('post.code'); // 邮件code
		$mobile = isset($_POST['mobile']) ? base64_decode(in($_POST['mobile'])) : ''; // 手机号
		$question = isset($_POST['question']) ? base64_decode(in($_POST['question'])) : ''; // 问题

		$rules[] = array(Check::regex($old_password, 'require'), array('请输入您的原密码', 'code'=>'0001'));
		$rules[] = array(self::$user->check_user($_SESSION['user_name'], $old_password), array('原登录密码错误', 'code'=>'0002'));
		$rules[] = array(Check::regex($new_password, 'require'), array('请输入您的新密码', 'code'=>'0003'));
		$rules[] = array(Check::len($new_password, 6, 20), array('登录密码为6-20位字母、数字组合', 'code'=>'004'));
		$rules[] = array(!Check::regex($new_password, 'number'), array('登录密码为6-20位字母、数字组合', 'code'=>'0005'));
		$rules[] = array(!Check::regex($new_password, 'english'), array('登录密码为6-20位字母、数字组合', 'code'=>'0005'));
        $rules[] = array(Check::regex($new_password, 'password'), array('登录密码为6-20位字母、数字组合', 'code'=>'0005'));
		$rules[] = array(!strpos($new_password, ' '), array('密码中不能包含空格', 'code'=>'0006'));
		$rules[] = array(Check::same($new_password, $re_password), array('两次登录密码设置不一致', 'code'=>'0007'));
		$result = Check::rule($rules);
		if(is_array($result)) {
            $this->msg->show(false,$result);
		}

		$user_info = self::$user->get_profile_by_id($user_id); // 论坛记录

		// 短信找回，邮件找回，问题找回，登录修改密码
		if ((! empty($mobile) && $user_info['mobile'] == $mobile) || ($user_info && (! empty($code) && md5($user_info['user_id'] . C('hash_code') . $user_info['reg_time']) == $code)) || (! empty($question) && $user_info['passwd_question'] == $question) || ($_SESSION['user_id'] > 0 && $_SESSION['user_id'] == $user_id)) {

			$cfg['user_name'] = $user_info['user_name'];
			$cfg['old_password'] = $old_password;
			$cfg['password'] = $new_password;
			if (self::$user->edit_user($cfg)) {
				//self::$user->logout();
				$array[] = '登录密码修改成功';
				$array['code'] = '0000';
                $this->msg->show(true,$array);
			} else {
				$array[] = '您输入的密码不正确';
				$array['code'] = '0008';
                $this->msg->show(false,$array);
			}
		} else {
			$array[] = '您输入的密码不正确';
			$array['code'] = '0008';
            $this->msg->show(false,$array);
		}
    }

    /**
     * 退出
     */
    public function logout()
    {
        if ((! isset($this->back_act) || empty($this->back_act)) && isset($GLOBALS['_SERVER']['HTTP_REFERER'])) {
            $this->back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'c=user') ? url('index') : $GLOBALS['_SERVER']['HTTP_REFERER'];
        } else {
            $this->back_act = url('login');
        }

        self::$user->logout();
        sleep(1);
        self::$user->logout();
        sleep(1);
        self::$user->logout();
        $ucdata = empty(self::$user->ucdata) ? "" : self::$user->ucdata;

        $result[] = '退出' . $ucdata;
        $result['code'] = '0000';
        $this->msg->show(true,$result);
    }

    /**
     * 登录验证
     */
    public function check_login(){
        return I('session.user_id',0,'intval') > 0 ? true : false;
    }

    /* 随机码 */
    protected function sms_code(){
        $sms_code = mt_rand(1000, 9999);
        $sms_code = md5($sms_code);
        return $_SESSION['sms_code'] = $sms_code;
    }

    /**
     * 手势密码
     */
    public function gesture(){
        $user_gesture = I('post.user_gesture', '', 'stripslashes');
        $is_gesture = I('post.is_gesture', 0, 'intval');
        if (!empty($user_gesture)){
            $user_gesture = md5($user_gesture);
        }
        if($is_gesture == -1) {
            $is_gesture = 1;
            $result[] = '添加手势密码成功';
        }
        elseif($is_gesture == 1) {
            $result[] = '修改手势密码成功';
        }
        elseif($is_gesture == 0) {
            $result[] = '关闭手势密码成功';
            $user_gesture = '';
        }elseif ($is_gesture == 2) {//忘记手势密码
            $is_gesture = 0;
            $user_gesture = '';
        }

        if(model('Users')->edit_gesture($is_gesture, $user_gesture)){
            $result['code'] = '0000';
            $this->msg->show(true,$result);
        }else{
            $result[] = '设置手势密码错误';
            $result['code'] = '0001';
            $this->msg->show(false,$result);
        }
    }

    /**
     * 手势密码登录
     */
    public function gesture_login(){
        $user_gesture = I('post.user_gesture', '', 'stripslashes');
        $user_id = I('post.uid', $this->user_id);
        $user_gesture = md5($user_gesture);
        if(model('Users')->check_gesture($user_gesture,$this->user_id)){
            model('Users')->clear_gesture(1);
            $result['code'] = '0000';
            $this->msg->show(true,$result);
        }else{
            model('Users')->set_count_inc('gesture');
            $gesture_count = model('Users')->get_count('gesture');
            if ($gesture_count < 5){
                $count = 5 - $gesture_count;
                $result[] = sprintf('密码错误，还可以再输入%d次', $count);
                $result['code'] = '0001';
            }else{
                model('Users')->clear_gesture();
                $result['code'] = '0002';
            }
            $this->msg->show(false,$result);
        }
    }

    /**
     * 添加、修改支付密码
     */
    public function pay_password(){
        $sms_mobile_code = I('session.sms_mobile_code');
        if(!empty($sms_mobile_code)) {
            $mobile_code = I('post.mobile_code');
            $rules[] = array(Check::regex($mobile_code, 'require'), array('手机验证码错误', 'code'=>'0001'));
            $rules[] = array(Check::same($mobile_code, I('session.sms_mobile_code')), array('手机验证码错误', 'code'=>'0001'));
            $result = Check::rule($rules);
            if(is_array($result)) {
                $this->msg->show(false,$result);
            }
        }

        $pay_password = I('post.pay_password');
        $pay_password = md5($pay_password);
        if(model('Users')->edit_pay_password($pay_password)){
            $res[] = '支付密码修改成功';
            $res['code'] = '0000';
            $this->msg->show(true,$res);
        }else{
            $res[] = '支付密码更新失败';
            $res['code'] = '0002';
            $this->msg->show(false,$res);
        }
    }

    /**
     * 验证支付密码
     */
    public function check_pay_password(){
        $pay_password = I('post.pay_password');
        $pay_password = md5($pay_password);
        $pay_pwd = model('Users')->get_pay_password();
        if($pay_pwd == $pay_password){
            model('Users')->clear_count('paypw');
            $result[] = '支付密码正确';
            $result['code'] = '0000';
            $this->msg->show(true,$result);
        }else{
            model('Users')->set_count_inc('paypw');
            $count = model('Users')->get_count('paypw');
            if ($count < 5){
                $count = 5 - $count;
                $result[] = sprintf('支付密码错误，还可以再输入%d次', $count);
                $result['code'] = '0001';
            }else{
                model('Users')->clear_count('paypw');
                $result['code'] = '0002';
                $result[] = '支付密码错误次数已达上限，请尝试找回密码';
            }
            $this->msg->show(false,$result);
        }
    }

    /**
     * 验证验证码
     */
    public function check_mobile_code(){
        $mobile_code = I('post.mobile_code');
        if(!empty($mobile_code)) {
            $rules[] = array(Check::regex($mobile_code, 'require'), array('手机验证码错误', 'code'=>'0001'));
            $rules[] = array(Check::same($mobile_code, I('session.sms_mobile_code')), array('手机验证码错误', 'code'=>'0001'));
            $result = Check::rule($rules);
            if(is_array($result)) {
                $this->msg->show(false,$result);
            }else{
                $_SESSION['sms_mobile_code'] = null;
                $array['code'] = '0000';
                $this->msg->show(true,$array);
            }
        }
    }

    /**
     * 验证登录密码
     */
    public function check_password(){
        $password = I('post.password');
        $user_info = model('Jytapi')->get_user_info($this->user_id);
        $rules[] = array(Check::regex($password, 'require'), array('登录密码不能为空', 'code'=>'0001'));
        $rules[] = array(self::$user->check_user($user_info['user_name'], $password), array('登录密码错误', 'code'=>'0002'));
        $result = Check::rule($rules);
        if(is_array($result)) {
            $this->msg->show(false,$result);
        }else{
            $array['code'] = '0000';
            $this->msg->show(true,$array);
        }
    }

    /**
     * 验证真实信息
     */
    function check_user_real_info(){
        $true_name = I('post.true_name');
        $user_idcard = I('post.user_idcard');
        $rules[] = array(Check::regex($true_name, 'require'), array('姓名不能为空', 'code'=>'0001'));
        $rules[] = array(Check::regex($user_idcard, 'require'), array('身份证号不能为空', 'code'=>'0002'));
        $result = Check::rule($rules);
        if(is_array($result)) {
            $this->msg->show(false,$result);
        }

        $row = model('Users')->get_user_real_info($this->user_id);
        if($row['md5'] == md5($true_name . $user_idcard)) {
            $array['code'] = '0000';
            $this->msg->show(true,$array);
        } else {
            $array[] = '身份信息有误，请重新输入';
            $array['code'] = '0003';
            $this->msg->show(false,$array);
        }
    }

    /**
     *  会员充值和提现申请记录
     */
    public function  account_log(){

        $size = 5;
        $page = I('post.page', 1, 'intval');

        $where['user_id'] = $this->user_id;
        $where['process_type'] = array('IN', array(SURPLUS_SAVE, SURPLUS_RETURN));
        $count = M('user_account')->where($where)->count();

        $this->pageLimit(url('user/account_log'), $size);
        $result['pager'] = $this->pageShow($count);


        //获取剩余余额
        $result['surplus_amount'] = model('Users')->get_user_surplus($this->user_id);
        if (empty($surplus_amount)) {
            $surplus_amount = 0;
        }
        //获取余额记录
        $result['account_log'] = model('Users')->get_account_log($this->user_id, $size, ($page-1)*$size);

        $this->msg->show(true,$result);
    }


}