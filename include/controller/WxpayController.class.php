<?php
/**
 * 微信支付处理
 */
/* 访问控制 */
defined('IN_ZFT') or die('Deny Access');

class WxpayController extends CommonController {
    protected $user_id = '';

    public function __construct() {
        parent::__construct();
        
        $this->user_id = I('session.user_id', 0, 'intval');
    }
	/**
	 *处理支付订单请求
	 *orderid 传递订单号
	 *type=2提交二手卡支付  不传递为新购支付
	*/
	public function index(){
		//验证是否在登录状态
		if($this->user_id<1){
			header("location:/");
			exit;
		}
		if(!isset($_SESSION['openid'])){
			header("location:/");
			exit;
		}
		if(!isset($_GET['orderid']) || !is_numeric($_GET['orderid'])){
			$orderid=0;
		}else{
			$orderid = $_GET['orderid'];
		}

		$totalprice=$this->model->table("order_info")->field("order_status,order_amount,order_id,pay_status,order_type,user_id,pay_time,pay_user_id,add_time,shipping_fee")->where(array("order_sn"=>$orderid))->find();
		//订单号不存在时的处理
		if($totalprice===false){
            $this->alertNoticeMsg(2, '订单不存在',url('coupon/index'));
			exit;
		}
        //判断订单有效性
        if($totalprice['order_status'] && $totalprice['order_status'] == 2){
            $this->alertNoticeMsg(2,'订单已经失效，请重新购买',url('coupon/index'));
        }

        //不支持代购
		if($this->user_id!=$totalprice['user_id']){
            $this->alertNoticeMsg(2, '不是你的订单哟');
			exit;
		}

		if($totalprice['pay_status']==2){
			//已成功支付
            $this->alertNoticeMsg(2, '购买成功', url('Order/success', array('id' => $orderid)));
			exit;
		}

        //超时
        if(time() - $totalprice['add_time'] > 7200){
            CronController::order_cancel($totalprice['order_id'], $totalprice['add_time'], $totalprice['pay_time']);
            $this->alertNoticeMsg(2, '订单支付超时，请重新购买',url('Coupon/index'));
        }

		$order_goods=$this->model->table("order_goods")->field("goods_id,goods_name,goods_number,city_name")->where(array("order_id"=>$totalprice['order_id']))->find();

		$goods_info=$this->model->table("goods")->field("goods_name,goods_name_style,goods_img,goods_thumb,shop_price,market_price")->where(array("goods_id"=>$order_goods['goods_id']))->find();


        $zk = ((intval($goods_info['shop_price']) / intval($goods_info['market_price'])) * 100);
        if($zk == 100){
            $zekou = '无折扣';
        }else{
            $zekou = $zk.'折';
        }

        $this->assign("signPackage", Wechat()->GetSignPackage());
		$this->assign('baseurl',C("DB.SITE_URL"));
		$this->assign('orderid',$orderid);
		$this->assign('goods_number',$order_goods['goods_number']);
		$this->assign('goods_name',$order_goods['goods_name']);
		$this->assign('order_amount',$totalprice['order_amount']);
		$this->assign('order_zk', $zekou);
		$this->assign('shipping_fee',$totalprice['shipping_fee']);
		$this->assign('goods_info',$goods_info);
		$this->assign('city_name',$order_goods['city_name']);
		$this->assign("page_title","订单信息");
		
		$this->display('wechat_jsapi_pay.dwt');
	}

    /*
     * 微信支付js
     */
    public function wx_pay()
    {
        if(!isset($_POST['orderid']) || !is_numeric($_POST['orderid'])){
			$orderid=0;
		}else{
			$orderid = $_POST['orderid'];
		}
        $result = array(
            'status' => 0,
            'params' => '',
        );
        $orderid || $this->returnJson($result = array('status' => 0, 'params' => '订单号错误',));

        $openid = $_SESSION['openid'];
        $totalprice = $this->model->table("order_info")->field("order_status,order_amount,order_id,pay_status,order_type,user_id,pay_time,add_time,pay_user_id")->where(array("order_sn" => $orderid))->find();
        //订单号不存在时的处理
        if ($totalprice === false) {
            $this->returnJson($result = array('status' => 0, 'params' => '订单不存在','url'=>url('Index/index')));
        }
        //判断订单有效性
        if ($totalprice['order_status'] && $totalprice['order_status'] == 2) {
            $this->returnJson($result = array('status' => 0, 'params' => '订单已经失效，请重新购买', 'url' => url('Coupon/index')));
        }

        if ($totalprice['pay_status'] == 2) {
            //已成功支付
            $this->returnJson($result = array('status' => 0, 'params' => '购买成功','url'=> url('Order/success', array('id' => $orderid))));
            exit;
        }

        //买家本人才能付款
        if ($this->user_id != $totalprice['user_id']) {
            $this->returnJson($result = array('status' => 0, 'params' => '只有买家本人才能付款',));
        }

        $tm = time();
        //过期取消处理 订单过期
        if (time() - $totalprice['add_time'] > 7200) {
            CronController::order_cancel($totalprice['order_id'], $totalprice['add_time'], $totalprice['pay_time']);
            $this->alertNoticeMsg(2, '订单支付超时，请重新购买', url('Coupon/index'));
        }

        //判断支付锁
        if ($totalprice['pay_user_id'] != $this->user_id && $totalprice['pay_user_id'] > 0 && $totalprice['pay_time'] > $tm - 240) {
            $time_last = 240 - ($tm - $totalprice['pay_time']);
            $this->returnJson($result = array('status' => 0, 'params' => '其他人正在支付,请' . $time_last . '秒后重试',));
        }

        //支付锁
        $this->model->table('order_info')->data(array('pay_user_id' => $this->user_id, 'pay_openid' => $openid, 'pay_time' => $tm))->where(array('order_sn' => $orderid))->update();

        $order_goods = $this->model->table("order_goods")->field("goods_id,goods_name,goods_number,city_name")->where(array("order_id" => $totalprice['order_id']))->find();
        $price = 100 * $totalprice['order_amount'];
        if(C("DB.IS_FULLPAY")==1){
			$price = C("DB.FULLPAY_MONEY");//临时支付 一分钱
		}
        //判断二手卡支付 or  新购支付
        $attach = $totalprice['order_type'] . "*0"; //代付时辅助传递原始订单号,本人支付传0
        $WechatPay = new Weixinpay();
        $start_time = $totalprice['add_time'] - 180;
        $endtime = $tm + 180;
        $lasttime = $endtime - $start_time;
        $pre_payid = $WechatPay->makepreorder($order_goods['goods_name'] . '(' . $order_goods['city_name'] . ')' . $order_goods['goods_number'] . "张", $attach, $orderid, $price, rtrim(C("DB.SITE_URL"),"/").url('wxpay/notify'), $openid, $start_time, "buy", $lasttime);
        if ($pre_payid == -1) {
            $this->returnJson($result = array('status' => 0, 'params' => '等待支付中,请稍后再试',));
        }
        $this->returnJson($result = array('status' => 1, 'params' => json_decode($WechatPay->GetJsApiParameters(), true),));

    }


	/**
	 发起代付时解锁
	*/
	public function payunlock(){
		exit;
		if(!isset($_GET['orderid']) || !is_numeric($_GET['orderid'])){
			$orderid=0;
		}else{
			$orderid = $_GET['orderid'];
		}
		$this->model->query("UPDATE zft_order_info SET pay_user_id=0 WHERE order_sn='$orderid' AND user_id=".$this->user_id." AND pay_user_id=".$this->user_id);
		echo 1;
	}
	/**
	 *代付订单
	 *orderid 传递订单号
	*/
	public function helppay(){
		//验证是否在登录状态
		if($this->user_id<1){
			header("location:/");
			exit;
		}
		if(!isset($_SESSION['openid'])){
			header("location:/");
			exit;
		}
		$openid=$_SESSION['openid'];
		if(!isset($_GET['orderid']) || !is_numeric($_GET['orderid'])){
			$orderid=0;
		}else{
			$orderid = $_GET['orderid'];
		}

		$totalprice=$this->model->table("order_info")->field("order_status,order_amount,order_id,pay_status,order_type,user_id,pay_user_id,pay_time,add_time")->where(array("order_sn"=>$orderid))->find();
		//订单号不存在时的处理
		if($totalprice===false){
			header("location:/");
			exit;
		}
        //判断订单有效性
        if($totalprice['order_status'] && $totalprice['order_status'] == 2){
			header("location:".url('Wxpay/helppayerror', array('type' => 1)));
			exit;
        }
		if($totalprice['pay_status']==2){
			//已成功支付
			header("location:".url('Wxpay/helppayok', array('o' => $orderid)));
			exit;
		}

        $ordertype=$totalprice['order_type'];
		$ori_user_id=$totalprice['user_id'];
		if($this->user_id==$ori_user_id){//本人点开支付页面 跳转到常规订单支付
            if($ordertype == 1 || $ordertype == 3){
				header("location:".url('Wxpay/index', array('orderid' => $orderid)));
            }elseif($ordertype == 2){
                $trade_info = model('Trade')->getTradeInfo($orderid);
                $sale_id = $this->model->table('sales')->field('sale_id')->where(array('card_id'=>$trade_info['card_id']))->getOne();
				header("location:".url('Trade/trade_order_confirm', array('card'=>$sale_id)));
            }
			exit;
		}
		$timenow=time();
		
		$lefttime2=7200;
        if($ordertype == 2){
			$forlefttime=model('Trade')->getTradeInfo($orderid);

			$lefttime2=1200 - (time() - $forlefttime['start_time']);
        }
		$lefttime1= $totalprice['add_time'] + 7200 - time();
		$lefttime=$lefttime1<$lefttime2?$lefttime1:$lefttime2;

		 if($lefttime<0){
			header("location:".url('Wxpay/helppayerror', array('type' => 1)));
			exit;
        }

		$order_goods=$this->model->table("order_goods")->field("goods_name,goods_number,city_name,market_price")->where(array("order_id"=>$totalprice['order_id']))->find();
		
		//获取订单主人的头像和昵称
		$userinfo=$this->model->table("wechat_user")->field("nickname,headimgurl")->where(array("ect_uid"=>$ori_user_id))->find();
		//获取卡号和卡券余额
		$vcard=array();
		if($ordertype==2){
			$vcard=$this->model->table("user_card")->field("balance")->where(array("order_id"=>$orderid))->find();
		}
		
		$this->assign('baseurl',C("DB.SITE_URL"));
		$this->assign('orderid',$orderid);
		$this->assign("queryid",$orderid.$this->user_id);
		$this->assign('goods_number',$order_goods['goods_number']);
		$this->assign('goods_name',$order_goods['goods_name']);
		$this->assign('order_amount',$totalprice['order_amount']);
		$this->assign('city_name',$order_goods['city_name']);
		$this->assign("page_title","订单信息");
		
		$this->assign("userinfo",$userinfo);
		$this->assign("vcard",$vcard);
		$this->assign("ordertype",$ordertype);
		$this->assign("market_price",$order_goods['market_price']);
		
		$this->assign("lefttime",$lefttime);
		
		$this->display('wechat_jsapi_helppay.dwt');
	}
	/**
	 点击微信支付button时发起的异步验证请求
	 *@param orderid 需要查询的订单id
	 *@return 1为可发起支付  2为刷新指令  3被锁定中
	*/
	public function cheak_order_state(){
		if(IS_AJAX){
			if(!isset($_GET['orderid']) || !is_numeric($_GET['orderid'])){
				$order_sn=0;
			}else{
				$order_sn= $_GET['orderid'];
			}
			
			//登录状态的验证
			if($this->user_id<1){
				echo json_encode(array("error"=>2));
				exit;
			}
			if(!isset($_SESSION['openid'])){
				echo json_encode(array("error"=>2));
				exit;
			}
			$openid=$_SESSION['openid'];
			
			$orderinfo=$this->model->table("order_info")->field("order_id,pay_status,order_status,pay_time,pay_user_id,order_type,order_amount,add_time")->where(array("order_sn"=>$order_sn))->find();
			if(empty($orderinfo)){
				echo json_encode(array("error"=>2));
				exit;
			}
			
			if($orderinfo['pay_status']==2){//已成功支付
				echo json_encode(array("error"=>2));
				exit;
			}
			if($orderinfo['order_status']==2){//订单已取消
				echo json_encode(array("error"=>2));
				exit;
			}
			
			if($orderinfo['order_type']==2){//二手卡订单
				$forlefttime=model('Trade')->getTradeInfo($order_sn);
				$lefttime2=1200 - (time() - $forlefttime['start_time']);
				if($lefttime2<0){//订单已过期
					echo json_encode(array("error"=>2));
					exit;
				}
			}
			//可继续执行支付的状态
			$timenow=time();
			if($orderinfo['pay_user_id']==0 || $timenow - $orderinfo['pay_time']>=240){//订单无人占有
				$this->model->query("UPDATE zft_order_info SET pay_time=$timenow,pay_user_id=".$this->user_id.",pay_openid='$openid' WHERE order_sn='$order_sn'");
				if($this->model->db->affectedRows()>0){
					
					$order_goods=$this->model->table("order_goods")->field("goods_name,goods_number,city_name,market_price")->where(array("order_id"=>$orderinfo['order_id']))->find();
					$price = 100*$orderinfo['order_amount'];
					if(C("DB.IS_FULLPAY")==1){
						$price = C("DB.FULLPAY_MONEY");//临时支付 一分钱
					}
					//判断二手卡支付 or  新购支付
					$attach = $orderinfo['order_type']."*".$order_sn;
					//$WechatPay=new Weixinpay();
                    $start_time = $orderinfo['add_time'] - 120;
                    $endtime = time() + 180;
                    $lasttime = $endtime - $start_time;
					/*$pre_payid=$WechatPay->makepreorder($order_goods['goods_name']."(".$order_goods['city_name'].")".$order_goods['goods_number']."张",$attach,$order_sn.$this->user_id,$price,rtrim(C("DB.SITE_URL"),"/").url('wxpay/notify'),$openid, $start_time,"test",$lasttime);
					if($pre_payid==-1){
						echo json_encode(array("error"=>2));
						exit;
					}
					echo json_encode(array("error"=>1,"content"=>json_decode($WechatPay->GetJsApiParameters(),true)));
					exit;*/
					$klpay=new Klpay();
					$pay_request=$klpay->makeOrder( rtrim(C("DB.SITE_URL"),"/").url('wxpay/helppayok',array("o"=>$order_sn,"q"=>$this->user_id,'p'=>$start_time)), rtrim(C("DB.SITE_URL"),"/").url('wxpay/notify'),$order_sn.$this->user_id,$price,$start_time,5,$order_goods['goods_name'] . '(' . $order_goods['city_name'] . ')' . $order_goods['goods_number'] . "张",$openid,$attach);
					echo json_encode($pay_request);
					
				}else{
					echo json_encode(array("error"=>3,"content"=>240-($timenow - $orderinfo['pay_time'])));
					exit;
				}
			}elseif($timenow - $orderinfo['pay_time']<240 && $orderinfo['pay_user_id']!=$this->user_id){
				echo json_encode(array("error"=>3, "content" => 240 - ($timenow - $orderinfo['pay_time'])));
				exit;
			}elseif($timenow - $orderinfo['pay_time']<240 && $orderinfo['pay_user_id']==$this->user_id){
                $this->model->query("UPDATE zft_order_info SET pay_time=$timenow,pay_user_id=" . $this->user_id . ",pay_openid='$openid' WHERE order_sn='$order_sn'");
				$order_goods=$this->model->table("order_goods")->field("goods_name,goods_number,city_name,market_price")->where(array("order_id"=>$orderinfo['order_id']))->find();
				$price = 100*$orderinfo['order_amount'];
				if(C("DB.IS_FULLPAY")==1){
                	$price = C("DB.FULLPAY_MONEY");//临时支付 一分钱
				}
				//判断二手卡支付 or  新购支付
				$attach = $orderinfo['order_type']."*".$order_sn;
				/*$WechatPay=new Weixinpay();
				$pre_payid=$WechatPay->makepreorder($order_goods['goods_name']."(".$order_goods['city_name'].")".$order_goods['goods_number']."张",$attach,$order_sn.$this->user_id,$price,rtrim(C("DB.SITE_URL"),"/").url('wxpay/notify'),$openid,$orderinfo['pay_time']-120,"test",300);
				if($pre_payid==-1){
					echo json_encode(array("error"=>2));
					exit;
				}

				echo json_encode(array("error"=>1,"content"=>json_decode($WechatPay->GetJsApiParameters(),true)));
				exit;
				exit;*/
				$klpay=new Klpay();
				$pay_request=$klpay->makeOrder( rtrim(C("DB.SITE_URL"),"/").url('wxpay/helppayok',array("o"=>$order_sn,"q"=>$this->user_id,'p'=>$orderinfo['add_time']-120)), rtrim(C("DB.SITE_URL"),"/").url('wxpay/notify'),$order_sn.$this->user_id,$price,$orderinfo['add_time']-120,5,$order_goods['goods_name'] . '(' . $order_goods['city_name'] . ')' . $order_goods['goods_number'] . "张",$openid,$attach);
				echo json_encode($pay_request);
			}else{
				echo json_encode(array("error"=>2));
				exit;
			}
		}else{
			header("location:/");
			exit;
		}
	}
	/**
	 代付成功页面
	*/
	public function helppayok(){
		if(!isset($_GET['o']) || !is_numeric($_GET['o'])){
			$order_sn=0;
		}else{
			$order_sn = $_GET['o'];
		}
		if(!isset($_GET['q']) || !is_numeric($_GET['q'])){
			$queryid=0;
		}else{
			$queryid = $_GET['q'];
		}
		if(!isset($_GET['p']) || !is_numeric($_GET['p'])){
			$paytime=0;
		}else{
			$paytime = $_GET['p'];
		}
		$queryid=$order_sn.$queryid;
		$orderinfo=$this->model->table("order_info")->field("order_id,pay_status,user_id,order_type,pay_user_id")->where(array("order_sn"=>$order_sn))->find();
		if(!$orderinfo){
			header("location:/");
			exit;
		}
		if($orderinfo['pay_status']!=2){
			//$WechatPay=new Weixinpay();
			//$result=$WechatPay->queryresult($queryid);
			$klpay=new Klpay();
			$result=$klpay->orderQuery($queryid,$paytime,time());
			if($result['error']!=1 || $result['state']!=1){//支付未成功
				header("location:".url('Wxpay/helppayerror', array('type' => 3)));
				exit;
			}
		}
		if($this->user_id!=$orderinfo['pay_user_id']){
			header("location:".url('Wxpay/helppayerror', array('type' => 6)));
			exit;
		}
		
		$goods=$this->model->table("order_goods")->field("goods_name,goods_number")->where(array("order_id"=>$orderinfo['order_id']))->find();
		if($orderinfo['order_type']==1 || $orderinfo['order_type']==3){//同时处理一手 短连接卡 和 卡密卡
			$vcard=$this->model->table("virtual_card")->field("balance")->where(array("order_sn"=>$order_sn))->find();
		}
		if($orderinfo['order_type']==2){
			$vcard=$this->model->table("user_card")->field("balance")->where(array("order_id"=>$order_sn))->find();
		}
		
		$this->assign("signPackage",Wechat()->GetSignPackage());
		$this->assign("detail",$goods['goods_name']." ¥".$vcard['balance']." x".$goods['goods_number']);
		$this->assign("page_title",'支付成功');
		
		$this->display('helppay_success.dwt');
	}
	/**
	 代付出问题页面
	*/
	public function helppayerror(){
		$type=I("get.type",0,"intval");
		$isrefresh=0;
		$btntext="返回";
		switch($type){
			case 1:
				$errortext="订单已失效";
			break;
			case 2:
				$errortext="等待支付中,请稍后";
				$btntext="重试";
				$isrefresh=1;
			break;
			case 3:
				$errortext="支付请求生成失败";
			break;
			case 4:
				$errortext="支付失败";
			break;
			case 5:
				$errortext="其他人付款中,请5分钟后重试";
				$btntext="重试";
				$isrefresh=1;
			break;
			case 6:
				$errortext="订单已被他人成功支付";
				$btntext="返回";
				$isrefresh=0;
			break;
			default:
			header("location:/"); exit;
		}
		
		$this->assign("signPackage",Wechat()->GetSignPackage());
		$this->assign("errortext",$errortext);
		$this->assign("isrefresh",$isrefresh);
		$this->assign("btntext",$btntext);
		$this->assign("type",$type);
		$this->assign("page_title",'订单信息');
		
		$this->display('helppay_error.dwt');
	}
	/**
	 *支付结果通知
	*/
	public function notify(){
		ignore_user_abort(true);
		//$WechatPay=new Weixinpay();
		//$result=$WechatPay->processresult();
		//if($result!==false && array_key_exists("return_code", $result) && array_key_exists("result_code", $result) && $result["return_code"] == "SUCCESS" && $result["result_code"] == "SUCCESS"){
		$klpay=new Klpay();
		$result=$klpay->payResult();
		if($result['error']==1 && $result['state']==1){
			//支付回调通过验证
			$trade_no = $result['out_trade_no'];
			$attach=$result['attach'];
			$attach_temp=explode("*",$attach);
			$attach=$attach_temp[0];
			$ori_orderid=$attach_temp[1];
			
			if($ori_orderid>0){//是否为代付
				$trade_no=$ori_orderid;
			}else{
                //我们的订单号为18位
                $trade_no = $result['out_trade_no'] = substr($trade_no,0,14);
            }
			$is_pay = $this->model->table("order_info")->field("order_id,user_id,pay_status,pay_user_id")->where(array("order_sn" => $trade_no))->find();
			if(!$is_pay){
				//订单号不存在 直接退出
				exit;
			}
			if ($is_pay['pay_status']==0 || $is_pay['pay_status']==1) {//订单状态未成功时执行
				if($attach=="1" || $attach=="3"){//新购卡
					$this->newcard_payresult($result,0,$is_pay,$ori_orderid);
				}
				if($attach=="2"){//二手卡
					$this->handcard_payresult($result,0,$is_pay,$ori_orderid);
				}
			}else{
				//$WechatPay->tellwechat("SUCCESS", "OK");
				echo "success";
			}
			//支付回调通过验证
		}
	}
	/**
	 新购卡支付回调处理
	 @param result微信回调携带的接口数据
	 @param ori_orderid代付时 原始的订单号
	*/
	function newcard_payresult($result,$WechatPay,$is_pay,$ori_orderid=0){
		$trade_no = $result['out_trade_no'];
		//$transaction_id = $result['transaction_id'];
		$pay_openid=$result['openid'];
		$is_helppay=false;
		if($ori_orderid>0){
			$trade_no=$ori_orderid;
			$is_helppay=true;
		}
		
		$wechatinfo=$this->model->table("wechat_user")->field("openid")->where(array("ect_uid"=>$is_pay['user_id']))->find();
		$this->model->query("UPDATE zft_order_info SET version=1 WHERE order_sn='$trade_no' AND version=0");
		if ($this->model->db->affected_rows() < 1) {//订单已被其他入口在处理
			exit;
		}
		$pay_time = time();
		
		$order_info = $this->model->table("order_info")->field("user_id")->where(array("order_sn" => $trade_no))->find();
        if(self::do_active($trade_no, $order_info['user_id'],$is_helppay)){
			
			//$WechatPay->tellwechat("SUCCESS", "OK");
			echo "success";
		
			if($is_helppay && $wechatinfo){
				$msgopenid=$wechatinfo['openid'];//  推送代付成功模板消息
				$pay_user_id=$is_pay['pay_user_id'];
				
				$pay_user_info=$this->model->table("wechat_user")->field("nickname")->where(array("ect_uid"=>$pay_user_id))->find();
				$goods_info=$this->model->table("order_goods")->field("goods_name,goods_price,goods_number")->where(array("order_id"=>$is_pay['order_id']))->find();
				$url=rtrim(C("DB.SITE_URL"),"/").url('Order/success', array('id' => $trade_no));
				$data=' {"touser":"'.$msgopenid.'","template_id":"'.C("DB.T_HELPPAY_OK").'","url":"'.$url.'", "data":{"first": {"value":"恭喜您,您的找人代付订单,对方已支付成功!~","color":"#173177"},"keyword1":{"value":"'.$goods_info['goods_name'].'","color":"#173177"},"keyword2": {"value":"'.$goods_info['goods_price'].'元","color":"#173177"},"keyword3": {"value":"'.$goods_info['goods_number'].'","color":"#173177"},"keyword4": {"value":"'.($goods_info['goods_number']*$goods_info['goods_price']).'元","color":"#173177"},"remark":{"value":"付款人:'.$pay_user_info['nickname'].'","color":"#173177"}}}';
				$data=json_decode($data,true);
				$status_msg=Wechat()->sendTemplateMessage($data);
			}
		}
		
		$this->model->query("UPDATE zft_order_info SET version=0 WHERE order_sn='$trade_no'");
	}
	/**
	 二手卡支付回调处理
	 @param result微信回调携带的接口数据
	 @param ori_orderid代付时 原始的订单号
	*/
	function handcard_payresult($result,$WechatPay,$is_pay,$ori_orderid=0){
		$trade_no = $result['out_trade_no'];
		//$transaction_id = $result['transaction_id'];
		$pay_openid=$result['openid'];
		$is_helppay=false;
		if($ori_orderid>0){
			$trade_no=$ori_orderid;
			$is_helppay=true;
		}
		
		$wechatinfo=$this->model->table("wechat_user")->field("openid")->where(array("ect_uid"=>$is_pay['user_id']))->find();
		$this->model->query("UPDATE zft_order_info SET version=1 WHERE order_sn='$trade_no' AND version=0");
		if ($this->model->db->affected_rows() < 1) {//订单已被其他入口在处理
			exit;
		}
		$pay_time = time();
		
		if(!$is_helppay){//是否为代付
			$updateorder = $this->model->table('order_info')->data(array('order_status' => 1, 'pay_status' => 2, 'pay_time' => $pay_time,"pay_type"=>0,'shipping_status'=>SS_SHIPPED))->where(array('order_sn' => $trade_no))->update();
		}else{
			$updateorder = $this->model->table('order_info')->data(array('order_status' => 1, 'pay_status' => 2, 'pay_time' => $pay_time,"pay_type"=>1, 'shipping_status'=>SS_SHIPPED))->where(array('order_sn' => $trade_no))->update();
		}
		
		if($updateorder){
			self::secondDeal($trade_no);//二手卡交易处理逻辑
			
			
			if($is_helppay && $wechatinfo){
				$msgopenid=$wechatinfo['openid'];//  推送代付成功模板消息
				$pay_user_id=$is_pay['pay_user_id'];
				
				$pay_user_info=$this->model->table("wechat_user")->field("nickname")->where(array("ect_uid"=>$pay_user_id))->find();
				$goods_info=$this->model->table("order_goods")->field("goods_name,goods_price,goods_number")->where(array("order_id"=>$is_pay['order_id']))->find();
				$url=rtrim(C("DB.SITE_URL"),"/").url('Order/success', array('id' => $trade_no));
				$data=' {"touser":"'.$msgopenid.'","template_id":"'.C("DB.T_HELPPAY_OK").'","url":"'.$url.'", "data":{"first": {"value":"恭喜您,您的找人代付订单,对方已支付成功!~","color":"#173177"},"keyword1":{"value":"'.$goods_info['goods_name'].'","color":"#173177"},"keyword2": {"value":"'.$goods_info['goods_price'].'元","color":"#173177"},"keyword3": {"value":"'.$goods_info['goods_number'].'","color":"#173177"},"keyword4": {"value":"'.($goods_info['goods_number']*$goods_info['goods_price']).'元","color":"#173177"},"remark":{"value":"付款人:'.$pay_user_info['nickname'].'","color":"#173177"}}}';
				$data=json_decode($data,true);
				$status_msg=Wechat()->sendTemplateMessage($data);
			}

			//$WechatPay->tellwechat("SUCCESS", "OK");
			echo "success";
		}
		
		$this->model->query("UPDATE zft_order_info SET version=0 WHERE order_sn='$trade_no'");
	}
	/**
	 *激活卡券函数  
	 *来自原init方法调整
	*/
	static function do_active($trade_no,$user_id,$is_helppay=false){
		//生成卡券
		//$mm=model('Common');
		$_SESSION['user_id']=$user_id;
		$_SESSION['order_sn_for_api']=$trade_no;//传递订单号给api_log
		$_SESSION['api_key'] = array('id' =>$trade_no, 'val'=> '2');////传递订单号给api_log
		$pay_time=time();
		model('Common')->model->start_trans();//开始事务
		if(!$is_helppay){//是否为代付
			$updateorder = model('Common')->model->table('order_info')->data(array('order_status' => 1, 'pay_status' => 2, 'pay_time' => $pay_time,"pay_type"=>0))->where(array('order_sn' => $trade_no))->update();
		}else{
			$updateorder = model('Common')->model->table('order_info')->data(array('order_status' => 1, 'pay_status' => 2, 'pay_time' => $pay_time,"pay_type"=>1))->where(array('order_sn' => $trade_no))->update();
		}
		
		$order_goods = model('Common')->model->query("select g.goods_id as goodsid,g.goods_number as goods_number,g.market_price as market_price,g.city_id as city_id,g.city_name as city_name,g.merchant_id as merchant_id,o.order_id as order_id,o.order_type as order_type from zft_order_info as o left join zft_order_goods as g on o.order_id=g.order_id where o.order_sn='$trade_no'");
		$goodsid = $order_goods[0]['goodsid'];
		$orderid = $order_goods[0]['order_id'];
		$goods_number = $order_goods[0]['goods_number'];
		$market_price = $order_goods[0]['market_price'];
		$city_id = $order_goods[0]['city_id'];
		$city_name = $order_goods[0]['city_name'];
        $city_name_api=$city_name;
		$order_type=$order_goods[0]['order_type'];
		$merchant_id=$order_goods[0]['merchant_id'];//产品号动态调用商户号
        /*if($merchant_id=='7060010001'){//沃尔玛的城市名称 特殊处理  moon 2016.10.10  second motify 2016.10.13
            $city_name_api='WMHVM';
        }*/  //在购卡接口一处理了
		$brh = C("DB.YS_BRH");//固定机构号
		$lastid=0;
		$reqSeq_array=array();//跟踪流水号
		$lastid_array=array();//跟踪主键id
		$prdtId=$merchant_id;//产品号测试环境短连接为7060010001
		$endDt='';//暂时留空
		//$card_type=($order_type==3?1:0);//卡类型  0为电子卡  1为卡密卡
        if($order_type==3 || $order_type==5)
            $card_type=1;
        else
            $card_type=0;
		$_SESSION['card_type_for_api']=$card_type;//传递卡类型给api_log
		for($num_i=0;$num_i<$goods_number;$num_i++){			
			//先执行插入所有的卡券信息
			$openBrh =$brh;
			$reqSeq = "0";
			$txnDate = date("Ymd");
			$card_sn = $orderid.$lastid.rand(10000,9999);
			$card_sn_wx = "0";
			$card_url = "0";
			$add_date = time();
			$end_date = strtotime('+3 years');
			$crc32 = "0";
			$is_act = 0;
			
			
			$card_res = model('Common')->model->query("insert into zft_virtual_card (goods_id,user_id,card_sn,card_type,add_date,update_time,end_date,order_sn,is_act,crc32,openBrh,prdtNO,card_sn_wx,balance,city_id,city_name,card_url,reqSeq,txnDate) values ($goodsid,$user_id,'$card_sn',$card_type,'$add_date','$add_date','$end_date','$trade_no',$is_act,'$crc32','$openBrh','$prdtId','$card_sn_wx','$market_price',$city_id,'$city_name','$card_url','$reqSeq','$txnDate')");
			if($card_res){
				$lastid= model('Common')->model->db->insert_id();
				$new_reqSeq=$orderid.$lastid;
				$card_sn_wx=($lastid."0000");
				$update_res=model('Common')->model->query("UPDATE zft_virtual_card SET reqSeq='$new_reqSeq',card_sn_wx='$card_sn_wx' WHERE card_id=$lastid");
				if($update_res){
					array_push($reqSeq_array,$new_reqSeq);
					array_push($lastid_array,$lastid);
				}
			}
		}
		$state=model('Common')->model->commit_trans();
		model('Common')->model->query("UPDATE zft_order_info SET version=0 WHERE order_sn='$trade_no'");
		$i=0;
		if($state){//在前述card都成功插入数据表的前提下 进行请求银商接口购卡
			if($card_type==0){//短连接
				foreach($reqSeq_array as $k=>$v){
					//$result=model("Couponorder")->yinshang_buycard($v, $market_price, $brh, $city_name_api,$endDt,$prdtId,$card_type);
					$result=Card::yinshang_buycard($v, $market_price, $brh, $city_name_api, $endDt, $prdtId, $card_type);
					if($result){
						++$i;
						$txnDate=$result['txnDate'];
						$card_sn=$result['card_sn'];
						$card_sn_wx=$card_sn."0000";
						$card_url=$result['card_url'];
						$add_date=$result['add_date'];
						$end_date=$result['end_date'];
						$crc32=$result['crc32'];
						$is_act=$result['is_act'];
						
						$card_id=$lastid_array[$k];
						$timenow=time();
						model('Common')->model->query("UPDATE zft_virtual_card SET txnDate='$txnDate',card_sn='$card_sn',card_sn_wx='$card_sn_wx',card_url='$card_url',add_date='$add_date',end_date='$end_date',crc32='$crc32',is_act=$is_act,update_time=$timenow WHERE card_id=$card_id");
					}
				}
			}
			$base_j=0;  //一次购买多张时  每个循环的基础$i
			$j=0;
			$forseq=0;
			if($card_type==1){//卡密
				foreach($reqSeq_array as $k=>$v){
					if($j==$base_j){//以5为步长 请求银商购卡接口
						$result=NULL;
						$nums=$goods_number - $j;
						if($nums<1){
							break;
						}
						$nums=$nums>5?5:$nums;
						//$result=model("Couponorder")->yinshang_buycard($v, $market_price, $brh, $city_name_api,$endDt,$prdtId,$card_type,$nums);
						$result=Card::yinshang_buycard($v, $market_price, $brh, $city_name_api, $endDt, $prdtId, $card_type,$nums);
						$base_j+=5;
						$forseq=$v;
					}
					$card_id=$lastid_array[$k];
					model('Common')->model->query("UPDATE zft_virtual_card SET reqSeq='$forseq' WHERE card_id=$card_id");
					++$j;
					if($result){
						$forkey=$j-$base_j + 4;
						++$i;
						$txnDate=$result[$forkey]['txnDate'];
						//$card_sn=$result['card_sn'];
						//$card_sn_wx=$card_sn."0000";
						//$card_url=$result['card_url'];
						$add_date=$result[$forkey]['add_date'];
						$end_date=$result[$forkey]['end_date'];
						$crc32=$result[$forkey]['crc32'];
						$is_act=$result[$forkey]['is_act'];
						$card_sn=$result[$forkey]['card_sn'];
						$card_password=$result[$forkey]['card_password'];
						
						$timenow=time();
						model('Common')->model->query("UPDATE zft_virtual_card SET txnDate='$txnDate',card_sn='$card_sn',card_sn_wx='0',card_url='0',card_password='$card_password',add_date='$add_date',end_date='$end_date',crc32='$crc32',is_act=$is_act,update_time=$timenow WHERE card_id=$card_id");
					}
				}
			}
		}
		if($i==$goods_number){
			model('Common')->model->query("UPDATE zft_order_info SET shipping_status=".SS_SHIPPED." WHERE order_sn='$trade_no'");
		}elseif($i>0){
			model('Common')->model->query("UPDATE zft_order_info SET shipping_status=".SS_SHIPPED_PART." WHERE order_sn='$trade_no'");
		}
		return $state;
	}

    static public  function secondDeal($order_id)
    {
        $order_info = model('Common')->model->table('user_card')->field('sale_id,card_id,send_user,get_user')->where(array('order_id' => $order_id))->find();
        if(empty($order_info)){
            return false;
        }
        $tm = time();
        //事务处理
        model('Common')->model->query('start transaction');
        //修改卡券状态
        $flag1 = model('Common')->model->table('virtual_card')->data(array('is_saled' => 0,'user_id'=> $order_info['get_user'],'update_time'=>$tm))->where(array('card_id'=> $order_info['card_id']))->update();
        if ($flag1) {
            //增加一条退款记录
            $data2 = array(
                'user_id'=> $order_info['send_user'],
                'order_id'=>$order_id,
                'add_time'=>$tm,
                'pay_status'=>0, //0未退款,1退款成功,2退款失败
            );
            $flag2 = model('Common')->model->table('user_pay')->data($data2)->insert();

            //修改记录表卡券转卖状态
            $data3 = array(
                'status'=>1,
                'get_tm'=>$tm,
            );
            $flag3 = model('Common')->model->table('user_card')->data($data3)->where(array('order_id'=>$order_id))->update();
        }

        if(!$flag1 || !$flag2 || !$flag3){
            model('Common')->model->query('rollback');
            return false;
        }

        model('Common')->model->query('commit');

        //从售卖表中移除
        $condition = array(
            'sale_id' => $order_info['sale_id'],
        );

        model('Common')->model->table('sales')->where($condition)->delete();

        //微信卡券核销
        Wechat()->cardConsume($order_info['card_id']);
        //更新缓存
        getRedis()->hdel('trade_on_paying', $order_info['card_id']);

        //TODO 给买家，卖家推送消息
    }
	
}