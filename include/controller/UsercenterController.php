<?php

class UsercenterController extends CommonController
{

    protected $user_id = '';

    protected $action;

    protected $back_act = '';

    protected $page = 1;

    protected $count = 0;
    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
        // 属性赋值
        $this->user_id = I('session.user_id', 0, 'intval');

    }

    /**
     * 会员中心欢迎页
     */
    public function index()
    {
        $where['ect_uid'] = $this->user_id;
        $wechat_info = $this->model->table('wechat_user')
                            ->field('nickname, headimgurl')
                            ->where($where)
                            ->find();
        $this->assign('page_title', '我的');
    	$this->assign('wechat_info', $wechat_info);
		$this->assign('onsale_nums', model("Mine")->getonsalenums($this->user_id));    //我发布的数量
		$this->assign('mygroup_nums', model("Mine")->getmygroupnums($this->user_id));  //我的团购数量
        $this->display('usercenter.dwt');
    }
	/**
	 * 账户设置
	 */
	public function user_setting()
	{
		$where['user_id'] = $this->user_id;
		//获取用户的绑定的手机号码
		$users_info = $this->model->table('users')
									->field('mobile_phone')
									->where($where)
									->find();
		if( $users_info['mobile_phone'] ){
			$users_info['mobile_phone'] = substr_replace($users_info['mobile_phone'],'****',3,4);
		}
		$this->assign('page_title', '账户设置');
		$this->assign('users_info', $users_info);
		$this->display('user_setting.dwt');
	}
	/**
	 * 账户设置
	 */
	public function setting_phone()
	{
		$new = I('new',0,'intval');   //是否是绑定新手机
		$title = '绑定手机';
		if( $new ){
			$title = '绑定新手机';
		}
		$this->assign('page_title', $title);
		$this->display('user_setting_phone.dwt');
	}
	/*
	 *  获取验证码
	 */
	public function get_validate_code(){
		$phone = I('phone');   //获取手机号码
		$user_id = $this->user_id;
		if( !$user_id ){
			exit(json_encode(array('code' => 1,'msg' => '您还没有登录')));
		}elseif( !$this->isMobile($phone) ){       //c)	若不符合正则规则，则提示
			exit(json_encode(array('code' => 1,'msg' => '手机号码格式错误')));
		}
		$key = 'user_validate_'.$user_id;          //redis_key
		if( getRedis()->ttl($key) > 240 ){           //该用户还处于60秒内，不发送验证码
			exit(json_encode(array('code' => 1,'msg' => '验证码已发送')));
		}
		$where['mobile_phone'] = $phone;
		//判断今天给这个用户发送了几个短信
		$sql = 'SELECT count(1) as c FROM ' . $this->model->pre .'sms_log WHERE mobile_phone = "'.$phone.'" AND add_time >= "'.date('Y-m-d').'" AND add_time < "'.date('Y-m-d',strtotime('+1 day')).'"';
		$res = $this->model->query($sql);
		if( $res && $res[0]['c'] > 5 ){
			exit(json_encode(array('code' => 1,'msg' => '发送过于频繁，稍后在发送。')));
		}
		//判断是否号码被用过
		$users_info = $this->model->table('users')
									->field('user_id, mobile_phone')
									->where($where)
									->find();
		if( $users_info ){
			if( $users_info['user_id'] == $user_id ){     //a)	若和原手机号一致，则提示
				exit(json_encode(array('code' => 1,'msg' => '号码没变化哦')));
			}else{                                              //b)	若该号码已被绑定，则提示
				exit(json_encode(array('code' => 1,'msg' => '该号码已被其他账号绑定')));
			}
		}else{
			$msg_code = rand(100000,999999);
			$message = "您的验证码是：" . $msg_code . "，请不要把验证码泄露给其他人，如非本人操作，可不用理会！";
			$sms = new EcsSms();
			$sms_error = '';
			$send_result = $sms->send($phone, $message, $sms_error);   //发送验证码
			if( $send_result ){
				$over_time = NOW_TIME + (5*60);
				//保存到数据表
				$data = array(
					'mobile_phone'    => $phone,
					'msg_code'         => $msg_code,
					'content'          => $message,
					'add_time'         => date('Y-m-d H:i:s',NOW_TIME),
					'over_time'        => date('Y-m-d H:i:s',$over_time),
				);
				$this->model->table('sms_log')->data($data)->insert();
				//redis保存手机号码和验证码
				$value['phone'] = $phone;
				$value['msg_code'] = $msg_code;
				getRedis()->set($key,json_encode($value),300);  //存进redis   5*60秒
				exit(json_encode(array('code' => 0,'msg' => '验证码发送成功!')));
			}else{
				exit(json_encode(array('code' => 1,'msg' => '对不起,验证码发送失败!')));
			}
		}
	}
	/*
	 *  绑定手机号
	 */
	public function bind_phone(){
		$phone = I('phone');                           //获取手机号码
		$msg_code = I('msg_code',0,'intval');    //获取验证码
		$user_id = $this->user_id;
		if( !$user_id ){
			exit(json_encode(array('code' => 1,'msg' => '您还没有登录')));
		}elseif( !$this->isMobile($phone) ){       //c)	若不符合正则规则，则提示
			exit(json_encode(array('code' => 1,'msg' => '手机号码格式错误')));
		}
		$where['mobile_phone'] = $phone;
		//判断是否号码被用过
		$users_info = $this->model->table('users')
									->field('user_id, mobile_phone')
									->where($where)
									->find();
		if( $users_info ){
			if( $users_info['user_id'] == $user_id ){     //a)	若和原手机号一致，则提示
				exit(json_encode(array('code' => 1,'msg' => '号码没变化哦')));
			}else{                                              //b)	若该号码已被绑定，则提示
				exit(json_encode(array('code' => 1,'msg' => '该号码已被其他账号绑定')));
			}
		}else{
			//判断验证码是否正确
			$key = 'user_validate_'.$user_id;
			$code_value = json_decode(getRedis()->get($key),true);
			if( $code_value ){
				if( $code_value['phone'] == $phone && $code_value['msg_code'] == $msg_code ){   //验证
					$where = array( 'user_id' => $user_id );
					$updata = array( 'mobile_phone' => $phone );
					$this->model->table('users')->where($where)->data($updata)->update();
					exit(json_encode(array('code' => 0,'msg' => '手机号码绑定成功')));
				}else{
					exit(json_encode(array('code' => 1,'msg' => '短信验证码错误')));
				}
			}else{
				exit(json_encode(array('code' => 1,'msg' => '短信验证码已过期')));
			}
		}
	}

     /**
	 * 领取说明页
	 */
	public function introduction()
	{
		$where['ect_uid'] = $this->user_id;
		$wechat_info = $this->model->table('wechat_user')
							->field('nickname, headimgurl')
							->where($where)
							->find();
		$this->assign('page_title', '我的');
		$this->assign('wechat_info', $wechat_info);
		$this->display('receive_introduction.dwt');
	}
	//转赠成功页面
	/* ------------------------------------------------------ */
	public function success1() {
		$this->assign('page_title', "用卡啦商城");
		/* 显示模板 */
		$this->display('donate_success.dwt');
	}

   	/* ------------------------------------------------------ */
    /**
     * 会员中心欢迎页
     */
    public function order()
    {
        $status = I('get.status', 1, 'intval');
        $size = C('page_size');

        $filter['status'] = $status;
        $filter['page'] = '{page}';

        $limit = $this->pageLimit(url('order', $filter), $size);

        $orders = $this->get_user_orders($status, $limit);
        foreach ($orders as $key => $value) {
            if ($value['order_type'] == 2 && time() - $value['add_time'] > 1200) {
                //过滤掉超过20分钟没有回收的二手卡订单
                if ($status == 1) {
                    $this->count--;
                    ECTouch::view()->_var['total_fee'] -= $value['total_fee'];
                    unset($orders[$key]);
                    continue;
                } else {
                    //20分钟没有回收的卡，直接处理成已过期的卡
                    $orders[$key]['order_status'] = OS_CANCELED;
                    $orders[$key]['pay_status'] = PS_UNPAYED;
                }
            }
            if ($value['order_type'] == 6 && time() - $value['add_time'] > 15 * 60) {
                //过滤掉超过15分钟没有回收的代金券订单
                if ($status == 1) {
                    $this->count--;
                    ECTouch::view()->_var['total_fee'] -= $value['total_fee'];
                    unset($orders[$key]);
                    continue;
                } else {
                    //20分钟没有回收的卡，直接处理成已过期的卡
                    $orders[$key]['order_status'] = OS_CANCELED;
                    $orders[$key]['pay_status'] = PS_UNPAYED;
                }
            }
            $where['order_id'] = $value['order_id'];
            $orders[$key]['goods_info'] = $this->model->table('order_goods')
                           ->field('goods_name, goods_price, market_price, goods_number,card_id,city_name')
                           ->where($where)->find();

            //过滤异常订单,通过判断order_goods表是否存在来判断
            if(empty($orders[$key]['goods_info'])){
                unset($orders[$key]);
                continue;
            }

            if ($orders[$key]['order_status'] != OS_CANCELED && $orders[$key]['pay_status'] != PS_PAYED && $value['order_type'] == 2) {
                $orders[$key]['goods_info']['sale_id'] = $this->model->table('sales')->field('sale_id')->where(array('card_id' => $orders[$key]['goods_info']['card_id']))->getOne();
            }
        }

        $this->assign('page_title', '我的订单');
        $this->assign('status', $status);
        $this->assign('count', $this->count);
        $this->assign('pager', $this->pageShow($this->count));
        $this->assign('orders_list', $orders);
        if(IS_AJAX){
            $this->display('user_order_list_ajax.dwt');
        }else{
            $this->display('user_order_list.dwt');
        }
    }
    /**
	 *我发布的状态改变 异步请求状态
	*/
	public function mytradeajax(){
		if(IS_AJAX){
			$type=I('get.type', 0, 'intval');
			$card_id=I('get.id', 0, 'floor');
			if($type==1){//下架
				if(model("Mine")->offsale($this->user_id,$card_id)){
					echo 1;//正常操作
				}else{
					$state=model("Mine")->getstate($card_id);
					echo $state;
				}
			}
			if($type==2){//编辑前检查状态
				$state=model("Mine")->getstate($card_id);
				echo $state;
			}
			if($type==3){//删除
				echo model("Mine")->deleteoffsale($this->user_id,$card_id)?1:0;
			}
			if($type==4){//重新上架校验
				$info=model("Mine")->getbalance($card_id,$this->user_id);
				if(!$info){
					echo 0;
					exit;
				}
				echo $info['balance'].",".$info['isend'];
			}
			if($type==5){//异步获取数量
				$data['onsale_nums'] =   model("Mine")->getonsalenums($this->user_id);
				$data['mygroup_nums'] =   model("Mine")->getmygroupnums($this->user_id);
				exit(json_encode(array('data'=>$data)));
			}
		}else{
			header("location:/");
			exit;
		}
	}
    
    /**
     * 我的-我发布的
     */
    public function mytrade(){
		if(IS_AJAX){
			$type=I('get.type', 0, 'intval');
			$startpage=I('get.start', 0, 'intval');
			$startpage=$startpage<0?0:$startpage;
			$start=$startpage;
			if(!in_array($type,array(1,2,3))){
				echo "";
				exit;
			}
			if($type==1){
				$onsale=model("Mine")->getmyonsale($this->user_id,$start,20);
				if(empty($onsale)){
					echo "";
					exit;
				}
				$nums=count($onsale);
				for($i=0;$i<$nums;++$i){
					$temp=intval($onsale[$i]['sale_discount']*10000);
					$onsale[$i]['sale_discount']=$discount=intval($temp / 1000) /10;
					$onsale[$i]['sale_discount']=$discount==0?0.1:$discount;
					$onsale[$i]['sign']=1;   //1正常显示  2有约字  3无折扣
					if($discount * 10000!=$temp){
						$onsale[$i]['sign']=2;
					}
					if(intval($discount)==10){
						$onsale[$i]['sign']=3;
					}
					$rem=round(($onsale[$i]['sale_time'] +7776000 - time()) / 86400);
					if($rem>2){
						$onsale[$i]['remind_time']=$rem."天后";
					}elseif($rem>1){
						$onsale[$i]['remind_time']="后天";
					}else{
						$onsale[$i]['remind_time']="明天";
					}
					
					$onsale[$i]['sale_time']=date("Y-m-d",$onsale[$i]['sale_time']);
					
				}
			}
			if($type==2){
				$onsale=model("Mine")->getmypresale($this->user_id,$start,20);
				if(empty($onsale)){
					echo "";
					exit;
				}
				$nums=count($onsale);
				for($i=0;$i<$nums;++$i){
					$temp=intval($onsale[$i]['sale_discount']*10000);
					$onsale[$i]['sale_discount']=$discount=intval($temp / 1000) /10;
					$onsale[$i]['sale_discount']=$discount==0?0.1:$discount;
					$onsale[$i]['sign']=1;   //1正常显示  2有约字  3无折扣
					if($discount * 10000!=$temp){
						$onsale[$i]['sign']=2;
					}
					if(intval($discount)>=10){
						$onsale[$i]['sign']=3;
					}
					
				}
			}
			if($type==3){
				$onsale=model("Mine")->getmysaled($this->user_id,$start,20);
				if(empty($onsale)){
					echo "";
					exit;
				}
				$nums=count($onsale);
				for($i=0;$i<$nums;++$i){
					$temp=intval($onsale[$i]['sale_discount']*10000);
					$onsale[$i]['sale_discount']=$discount=intval($temp / 1000) /10;
					$onsale[$i]['sale_discount']=$discount==0?0.1:$discount;
					$onsale[$i]['sign']=1;   //1正常显示  2有约字  3无折扣
					if($discount * 10000!=$temp){
						$onsale[$i]['sign']=2;
					}
					if(intval($discount)==10){
						$onsale[$i]['sign']=3;
					}
					$onsale[$i]['get_tm']=date("Y-m-d",$onsale[$i]['get_tm']);
					
				}
			}
			$this->assign('type', $type);
			$this->assign('onsale', $onsale);
			$this->display('mytrade_ajax.dwt');
		}else{
			$this->assign('page_title', '我发布的');
			$this->assign('status', 1);
			$this->display('mytrade.dwt');
		}
    	
    	
    }

    /**
     * 取消一个用户订单
     */
    public function cancel_order()
    {
        $order_id = I('get.order_id', 0, 'intval');
		//代付中不可取消
		$states=$this->model->table("order_info")->field("pay_time,pay_user_id,pay_status,order_sn,order_type")->where(array("order_id"=>$order_id))->find();
		if(empty($states)){
			 $this->jserror('订单取消失败');
		}

        if ($states['pay_status'] == 2) {
            if($states['order_type']!=6) {
                echo json_encode(array(
                    "msg" => '订单已支付',
                    "result" => '2',
                    "url" => url('Order/success', array('id' => $states['order_sn'])),
                ));
                exit;
            }else{
                echo json_encode(array(
                    "msg" => '订单已支付',
                    "result" => '2',
                    "url" => url('Voucher/success', array('id' => $states['order_sn'])),
                ));
                exit;
            }
        }

		$timeinterval=time() - intval($states['pay_time']);
		if($timeinterval< 240 && $states['pay_user_id']!=0 && $states['pay_user_id']!=$this->user_id){
			$this->jserror('代付中,不可取消');
			exit;
		}

        if(model('Users')->cancel_order($order_id, $this->user_id)){
            $this->jssuccess("订单取消成功", url('usercenter/order'));
        }else{
            $message = ECTouch::err()->last_message();
            if(empty($message[0])){
                $message[0] = '订单取消失败';
            }
            $this->jserror($message[0]);
        }
    }

    /**
     * 获取订单
     */
    private function get_user_orders($status = '', $limit = '')
    {
        $field = 'order_id, order_sn, shipping_id, order_status,order_type, shipping_status, pay_status, add_time, order_amount AS total_fee';

        $where = '(order_type <= 3 OR order_type=6) ';
        if($status == 1){
            $outtime = time() - 1200;
            $vouchertime=time() - 15 * 60;
            $where .= "and `pay_status` = '". PS_UNPAYED."' AND `order_status` = '". OS_UNCONFIRMED."' AND `user_id` = '". $this->user_id ."' and ((`order_type` <> 2 and order_type <> 6)  or (`order_type` = 2 and `add_time` > ".$outtime. ") or (order_type=6 and add_time>$vouchertime))";

            $total_fee = $this->model->table('order_info')->field('SUM(order_amount) AS total_fee')->where($where)->order('order_id DESC')->select();
            $this->assign('total_fee', $total_fee[0]['total_fee']);

        }elseif($status == 2){
            $outtime = time() - 1200;
            $vouchertime=time() - 15 * 60;
            $where .= 'and `user_id` = '.$this->user_id.' and (`order_status` = "'.OS_CANCELED.'" or (`order_type` = 2 and `add_time` < '.$outtime.") or (order_type = 6 AND add_time<$vouchertime))";
        }elseif($status == 3){
            $where .= "and `user_id` = '". $this->user_id ."'";
        }else{
            return array();
        }

        $this->count = $this->model->table('order_info')->where($where)->count();

        return $this->model->table('order_info')
                           ->field($field)
                           ->where($where)
                           ->order('add_time desc')
                           ->limit($limit)->select();      
    }

	/**
	 * 检查用户是否绑定手机号
	 */
	public function checkPhone(){
		$user_id = $this->user_id;
		if (  model('Users')->check_user_phone($user_id) ){
			exit(json_encode(array('code' => 0,'msg' => '您已经绑定手机号')));
		}else{
			exit(json_encode(array('code' => 1,'msg' => '您还没有绑定手机号')));
		}
	}
	
	/**
	 * 我的团购
	 */
	public function mygroupbuy(){
		//获取该用户参与的所有团(条件：参团id不能为零 付款或退款 团队购买 购买状态：团购短连接订单、团购卡密订单)
		$sql = 'SELECT action_id FROM ' . $this->model->pre . 'order_info WHERE action_id != 0 AND (pay_status = 2 OR pay_status = 4)'
			.' AND extension_code = "group_buy" AND (order_type = 4 OR order_type = 5) AND user_id ='.$this->user_id;
		$order_info = $this->model->query($sql);
		$order_group = array();
		if( $order_info ){
			$action_id = array();
			foreach ( $order_info as $k => $v ){
				$action_id[] = $v['action_id'];
			}
			$action_id_str = implode(',',array_filter(array_unique($action_id)));   //获取string类型的数据
			//获取团购标题、团购状态、购买状态、团购图片
			$sql = 'SELECT action_id,ga.goods_name,ga.price,act_end_time,oa.order_status,oa.part_number as pn,oa.join_number as jn,price,shipping_status,g.goods_thumb,g.goods_name_style '.
				' ,CASE WHEN (oa.part_number < oa.join_number AND oa.act_end_time < unix_timestamp()) THEN 2 ELSE oa.order_status END as order_status_px'.
				' FROM ' . $this->model->pre . 'goods_activity AS ga'.
				' JOIN '. $this->model->pre .'order_action AS oa ON ga.act_id = oa.act_id '.
				' JOIN '. $this->model->pre .'goods AS g ON ga.goods_id = g.goods_id '.
				' WHERE action_id in('.$action_id_str.') ORDER BY field(order_status_px,0,2,1),oa.log_time DESC';
			$order_group = $this->model->query($sql);
			//获取团购所有成员
			$sql = 'SELECT action_id,user_id,money_paid,order_amount FROM '.$this->model->pre.'order_info WHERE action_id in('.$action_id_str.') and action_id != 0 AND (pay_status = 2 OR pay_status = 4)'
				.' AND extension_code = "group_buy" AND (order_type = 4 OR order_type = 5) ORDER BY order_id ASC';
			$order_group_info = $this->model->query($sql);
			foreach ( $order_group_info as $k => $v ){
				$user_id_arr[] = $v['user_id'];
			}
			$user_id_str = implode(',',array_filter(array_unique($user_id_arr)));   //获取string类型的user_id数据
			$sql = 'SELECT headimgurl,ect_uid FROM '.$this->model->pre.'wechat_user WHERE ect_uid in('.$user_id_str.')';
			$wechat_user_info = $this->model->query($sql);

			foreach ( $order_group_info as $k => $v ){
				foreach ( $wechat_user_info as $key => $value ){
					if( $v['user_id'] == $value['ect_uid'] ){
						$order_group_info[$k]['img'] = $value['headimgurl']?$value['headimgurl']:'/themes/default/images/user_no.png';
					}
				}
			}
			foreach ($order_group as $k_o => $v_o){
				foreach ( $order_group_info as $k => $v ){
					if( $v_o['action_id'] == $v['action_id'] ){
						$order_group[$k_o]['tgj'] = intval($v['order_amount']*10)/10;
						if( $v['order_amount'] == $v_o['price'] ){                      //相等
							$order_group[$k_o]['zk'] = '无折扣';
						}elseif( is_int( intval($v['order_amount']*100)/intval($v_o['price']) ) ) {    //如果是整数
							$order_group[$k_o]['zk'] = (($v['order_amount']/$v_o['price'])*10).'折';
						}elseif ( ( $v['order_amount']/$v_o['price'] ) < 0.1 ){
							$order_group[$k_o]['zk'] = '约0.1折';
						}else{
							$order_group[$k_o]['zk'] = '约'.(floor(($v['order_amount']/intval($v_o['price']))*100)/10).'折';
						}
						$order_group[$k_o]['user'][] = $v['img'];
					}
				}
				if( $v_o['act_end_time'] < time() && $v_o['pn'] < $v_o['jn'] ){
					$order_group[$k_o]['order_status'] = 2;
				}
				$order_group[$k_o]['price'] = intval($v_o['price']) > 0 ? intval($v_o['price']).'元':'';//转换价格数据类型
			}
		}
	    $this->assign('page_title', '我的团购');
		$this->assign('order_group', $order_group);
		$this->display('user_groupbuy.dwt');
	}

	
	/**
	 * 意见反馈
	 */
	public function feedback(){
		//获取用户绑定的手机号
		$user_phone = model('Users')->check_user_phone($this->user_id);

	    $this->assign('page_title', '意见反馈');
		$this->assign('user_phone', $user_phone);
		$this->display('user_feedback.dwt');
	}

	/*
	 * 提交意见反馈
	 */
	public function submit_feedback(){
		if( IS_AJAX ){
			$user_phone   = I('post.user_phone');   //用户手机号
			$msg_content  = I('post.msg_content');  //用户输入内容
			if ( $user_phone && !$this->isMobile($user_phone) ){
				exit(json_encode(array('code' => 1,'msg' => '请填写正确手机号码!')));
			}elseif ( !$msg_content ){
				exit(json_encode(array('code' => 1,'msg' => '请输入反馈内容!')));
			}else{
				//获取用户基本信息
				$user_info = $this->model->table("users")->field("user_name,mobile_phone")->where(array("user_id"=>$this->user_id))->find();
				!$user_info && exit(json_encode(array('code' => 1,'msg' => '获取信息错误!')));
				$user_phone = $user_phone?$user_phone:$user_info['mobile_phone'];
				$data = array(
					'user_id'         => $this->user_id,
					'user_name'      => $user_info['user_name'],
					'user_phone'     => $user_phone,
					'msg_content'    => $msg_content,
					'msg_time'        => time(),
				);
				$this->model->table('feedback')->data($data)->insert();
				exit(json_encode(array('code' => 0,'msg' => '已提交!')));
			}
		}else{
			exit(json_encode(array('code' => 1,'msg' => '提交错误!')));
		}
	}
}
