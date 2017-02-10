<?php
require_once "WechatPay/lib/WxPay.Api.php";
/**
 * 
 * EcsWweixinpay实现订单的提交  支付  返回通知
 * 使用者需传递必要的参数
 *
 *
 */
class Weixinpay
{
	
	private $input;
	private $order;
	
	/**
	 *构造函数 实现微信官方支付类的实例化
	 *@author moon
	*/
	public function __construct(){
		$this->input=new WxPayUnifiedOrder();
	}
	
	/**
	 *统一下单方法调用
	 *$tag为可选字段  订单有效时间默认为600秒
	 *@param body 商品描述信息
	 *@param attach 附加字段 原样返回
	 *@param orderid 订单号
	 *@param totalfee 总金额 以分为单位
	 *@param notifyurl 支付成功回调url
	 *@param openid 付款人的openid
	 *@param starttime  订单支付开始时间
	 *@param tag 商品标签 可选字段
	 *@param lasttime 订单从当前时间开始 持续有效的秒数
	 *@return prepay_id  if fail -1
	 *@author moon
	*/
	public function makepreorder($body,$attach,$orderid,$totalfee,$notifyurl,$openid,$starttime=0,$tag="tag",$lasttime=600){
		$starttime=($starttime==0?time():$starttime);
		$this->input->SetBody($body);
		$this->input->SetAttach($attach);
		$this->input->SetOut_trade_no($orderid);
		$this->input->SetTotal_fee($totalfee);
		$this->input->SetTime_start(date("YmdHis",$starttime));
		$this->input->SetTime_expire(date("YmdHis", $starttime + $lasttime));
		$this->input->SetGoods_tag($tag);
		$this->input->SetNotify_url($notifyurl);
		$this->input->SetTrade_type("JSAPI");
		$this->input->SetOpenid($openid);
		$this->order=$fororder = WxPayApi::unifiedOrder($this->input);
		if(!array_key_exists("prepay_id", $fororder))
		{
			return -1;
		}
		return $fororder["prepay_id"];
		
	}
	/**
	 *处理支付结果回调
	 *@author moon
	*/
	public function processresult(){
		$result = WxpayApi::notify();
		if($result==false)
			return false;
		if(array_key_exists("return_code", $result)
			&& array_key_exists("result_code", $result)
			&& $result["return_code"] == "SUCCESS"
			&& $result["result_code"] == "SUCCESS")
		{
			return $result;
		}else{
			return false;
		}
	}
	/**
	*根据订单号主动查询支付结果
	*@param orderid 应用生成的订单号
	*@return 支付成功返回true  否则返回false
	*@author moon
	*/
	public function queryresult($orderid){
		$input = new WxPayOrderQuery();
		$input->SetOut_trade_no($orderid);
		$result=WxPayApi::orderQuery($input);
		if($result==false)
			return false;
		if(array_key_exists("return_code", $result)
			&& array_key_exists("result_code", $result)
			&& $result["return_code"] == "SUCCESS"
			&& $result["result_code"] == "SUCCESS")
		{
			//return true;
			return $result;
		}else{
			return false;
		}
	}
	/**
	 *向微信支付平台发送支付成功信息
	 *成功时  state传递 SUCCESS   msg 传递OK
	 *失败时  state 传递 FAIL  msg传递原因
	 *@author moon
	*/
	public function tellwechat($state,$msg){
		WxpayApi::replyNotify("<xml><return_code><![CDATA[$state]]></return_code><return_msg><![CDATA[$msg]]></return_msg></xml>");
	}
	/**
	 * 
	 * 获取jsapi支付的参数
	 * @param array $UnifiedOrderResult 统一支付接口返回的数据
	 * @throws WxPayException
	 * 
	 * @return json数据，可直接填入js函数作为参数
	 */
	public function GetJsApiParameters()
	{
		$UnifiedOrderResult=$this->order;
		if(!array_key_exists("appid", $UnifiedOrderResult)
		|| !array_key_exists("prepay_id", $UnifiedOrderResult)
		|| $UnifiedOrderResult['prepay_id'] == "")
		{
			throw new WxPayException("参数错误");
		}
		$jsapi = new WxPayJsApiPay();
		$jsapi->SetAppid($UnifiedOrderResult["appid"]);
		$timeStamp = time();
		$jsapi->SetTimeStamp("$timeStamp");
		$jsapi->SetNonceStr(WxPayApi::getNonceStr());
		$jsapi->SetPackage("prepay_id=" . $UnifiedOrderResult['prepay_id']);
		$jsapi->SetSignType("MD5");
		$jsapi->SetPaySign($jsapi->MakeSign());
		$parameters = json_encode($jsapi->GetValues());
		return $parameters;
	}
	/**
	 * 
	 * 获取地址js参数
	 * 
	 * @return 获取共享收货地址js函数需要的参数，json格式可以直接做参数使用
	 */
	public function GetEditAddressParameters($accesstoken)
	{	
		$data = array();
		$data["appid"] = C("DB.WX_APPID");
		$data["url"] = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$time = time();
		$data["timestamp"] = "$time";
		$data["noncestr"] = "1234568";
		$data["accesstoken"] = $accesstoken;
		ksort($data);
		$params = $this->ToUrlParams($data);
		$addrSign = sha1($params);
		
		$afterData = array(
			"addrSign" => $addrSign,
			"signType" => "sha1",
			"scope" => "jsapi_address",
			"appId" => C("DB.WX_APPID"),
			"timeStamp" => $data["timestamp"],
			"nonceStr" => $data["noncestr"]
		);
		$parameters = json_encode($afterData);
		return $parameters;
	}
	
	
	/**
	 * 
	 * 拼接签名字符串
	 * @param array $urlObj
	 * 
	 * @return 返回已经拼接好的字符串
	 */
	private function ToUrlParams($urlObj)
	{
		$buff = "";
		foreach ($urlObj as $k => $v)
		{
			if($k != "sign"){
				$buff .= $k . "=" . $v . "&";
			}
		}
		
		$buff = trim($buff, "&");
		return $buff;
	}

    /**
     *给具体用户退款
     */
    public function PayToUser($openid,$partner_trade_no, $amount,$desc)
    {
        $params = array(
            'mch_appid' => C('DB.WX_APPID'), //微信分配的公众账号ID
            'mchid' => C('DB.WX_MCHID'), //微信支付分配的商户号
            'nonce_str' => WxPayApi::getNonceStr(), //随机字符串，不长于32位
            'partner_trade_no' => $partner_trade_no, //商户订单号，需保持唯一性
            'openid' => $openid, //商户appid下，某用户的openid
            'check_name' => 'NO_CHECK', //NO_CHECK：不校验真实姓名
            'amount' => $amount, //企业付款金额，单位为分
            'desc' => $desc, //企业付款操作说明信息
            'spbill_create_ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '180.168.186.65', //调用接口的机器Ip地址
        );

        $result = WxPayApi::transfers($params);
        return $result;
    }
	/**
	 申请退款处理
	 *@param out_trade_no订单号
	 *@param out_refund_no退款订单号 由系统生成并且保持唯一
	 *@param total_fee原订单总金额 以分为单位
	 *@param refund_fee 退款金额 以分为单位
	 *@return true if success  ,false if fail
	 *@author moon
	*/
	public function refundtouser($out_trade_no,$out_refund_no,$total_fee,$refund_fee){
		$inputObj=new WxPayRefund();
		$inputObj->SetOp_user_id(C("DB.WX_MCHID"));//设置操作编号 即商户号
		$inputObj->SetOut_trade_no($out_trade_no);
		$inputObj->SetOut_refund_no($out_refund_no);
		$inputObj->SetTotal_fee($total_fee);
		$inputObj->SetRefund_fee($refund_fee);
		$result = WxPayApi::refund($inputObj);
		if($result===false)
			return false;
		if(array_key_exists("return_code", $result)
			&& array_key_exists("result_code", $result)
			&& $result["return_code"] == "SUCCESS"
			&& $result["result_code"] == "SUCCESS")
		{
			return true;
		}
		return false;
	}

}