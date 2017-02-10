<?php

/**
* 银商卡券操作
*/
class Yinshang extends Driver
{
    const DETAIL_TIMEOUT		= 0; //超时查询
    const DETAIL_CONSUMPTION	= 1; //消费明细查询
    const DETAIL_KAMI			= 4; // 卡密订单查询
    const DETAIL_UNDO			= 6; // 撤消短地址查询

    const KAMI_STOCK 			= 3; // 卡密库存
    const STANDBY_MONEY			= 5; // 备用金查询

    const ENTITY_TYPE_REGISTER	= 0; // 注册
    const ENTITY_TYPE_BIND 		= 1; // 绑卡
    const ENTITY_TYPE_CODE		= 2; // 取码
    const ENTITY_TYPE_UNBIND	= 3; // 解绑

    const ORDER_TYPE_EXPIRED	= 2; // 过期订单 

    const BUY_CARD = 'smartpay.cardVoucher.yinshang.buycard';
    const BUY_CARD_RESPONSE = 'cardVoucher_yinshang_buycard_response';
    const TXN_DETAIL = 'smartpay.cardVoucher.yinshang.txndetail';
    const TXN_DETAIL_RESPONSE = 'cardVoucher_yinshang_txndetail_response';
    const ORDER_EXPIRED = 'smartpay.cardVoucher.yinshang.order.expired';
    const ORDER_EXPIRED_RESPONSE = 'cardVoucher_yinshang_order_expired_response';
    const CARD_KAMI_STOCK = 'smartpay.cardVoucher.yinshang.kami.stock';
    const ENTITY_SERVICE = 'smartpay.cardVoucher.yinshang.entitycard.service';
    const ENTITY_SERVICE_RESPONSE = 'cardVoucher_yinshang_entitycard_bind_response';
	const TIME_OUT = 65;  // 请求超时时间

    public function __call($method, $args)
    {
    	if(method_exists(__CLASS__, $method)) {
    		$this->parse_data[$method] = call_user_func(array(__CLASS__, $method), array_shift($args));
    	} else {
    		$this->parse_data[$method] = array_shift($args);
    	}
        return $this;
    }
    

    /*
     * 卡券购买（短连接、卡密）
     * brh		String	必须	机构号
     * reqSeq	String	必须	请求交易流水号 				查询明细复用
     * txnDate	String	必须	请求交易日期 		Ymd 	查询明细复用
     * txnTime	String	必须	请求交易时间 		His
     * amt		Float	必须	购买金额			12位	不大于100000分
     * prdtId	String	必须	产品号 测试环境短连接为7060010001，卡密为：7060020001，具体请参照产品表
     * endDt	String	必须	卡片有效期			Ymd	
     * city		String	必须	使用城市	
     * buyQty	Intval			购买数量 			不能大于5
     * buyType	Intval	必须	购买类型 			1：卡密、0：短连接（如0N或者0Yhttp://test.hemaquan.com/yinshang/notify），第二位是回调标识，第三位以后是回调地址 默认0
     * mobile		String			手机号
     * ->reqSeq(请求交易流水号)->brh(机构号)->prdtId(产品号)->city(使用城市)->endDt(卡片有效期)->amt(购买金额)->buyType(购买类型，1：卡密；0：短连接)->txnDate(请求交易日期)->txnTime(请求交易时间)->buyQty(卡密购买数量)->buy();
     */
    public function buy($notify = '', $is_notify = true)
    {
    	$this->parse_data['method'] = self::BUY_CARD;

        // 沃尔玛的城市名称 特殊处理
        $WMART_CITY = C('DB.WMART_CITY');
        if (isset($WMART_CITY[$this->parse_data['prdtId']])) {
            $this->parse_data['city'] = $WMART_CITY[$this->parse_data['prdtId']];
        }

    	// 短链接增加回调地址
    	if ($this->parse_data['buyType'] == 0 && $is_notify) {
            $notify = $notify ? $notify : get_domain() . url('yinshang/notify', array('reqseq'=>$this->parse_data['reqSeq']));
            $this->parse_data['buyType'] .= 'Y' . ($notify);
    	}

		$post_data = $this->parseData($this->parse_data);
    	return self::parseJSON('Post', array($this->url, $post_data, self::TIME_OUT ,self::BUY_CARD_RESPONSE));
    }

	/*
	 * 消费明细查询、下单超时查询、卡密订单查询、撤销短地址订单
	 * brh			String	必须	机构号
	 * reqSeq		String	必须	请求交易流水号
	 * billTxnDate	String	必须	下单时间
	 * billReqSeq	String	必须	下单请求交易流水号
	 * prdtId		String	必须	产品号
	 * type			Intval	必须	查询类型	超时查询为0、消费明细查询为1、卡密订单查询为4、撤销短地址订单为6
	 * cardAmt		String			卡密金额	type为4时必填		
	 * mobile		String			手机号
	 * ->reqSeq(流水号)->brh(机构号)->prdtId(产品号)->billReqSeq(下单请求交易流水号)->billTxnDate(下单时间)->type(查询类型:超时查询为0;消费明细查询为1;卡密订单查询为4;撤销短地址订单为6)->cardAmt(卡密金额)->mobile(手机)->detail()*/
    public function detail()
    {
    	$this->parse_data['method'] = self::TXN_DETAIL;
		$post_data = $this->parseData($this->parse_data);
    	return self::parseJSON('Post', array($this->url, $post_data, self::TIME_OUT ,self::TXN_DETAIL_RESPONSE));    	
    }

	/*
	 * 过期订单查询
	 * brh		String	必须	机构号		
	 * type		Intval	必须	查询类型		默认：2
	 * reqSeq	String	必须	请求交易流水号
	 * prdtId	String	必须	产品号
	 * startDt	String	必须	查询起始日期	Ymd
	 * endDt	String	必须	查询截止日期	Ymd
	 * pageNum	Intval	必须	页码			默认：1
	 * pageSize	Intval	必须	分页大小		默认：10
	 * mobile	String			手机号
	 * ->reqSeq(流水号)->brh(机构号)->prdtId(产品号)->startDt(查询起始日期)->endDt(查询截止日期)->type(查询类型)->pageNum(页码)->pageSize(分页大小)->mobile(手机号)->expired()*/
    public function expired()
    {
    	$this->parse_data['method'] = self::ORDER_EXPIRED;
		$post_data = $this->parseData($this->parse_data);
    	return self::parseJSON('Post', array($this->url, $post_data, self::TIME_OUT ,self::ORDER_EXPIRED_RESPONSE)); 
    }

	/*
	 * 卡密、备付金
	 * brh		String	必须	机构号
	 * reqSeq	String	必须	请求交易流水号
	 * prdtId	String	必须	产品号
	 * type		Intval	必须	查询类型，3：库存查询、5：备付金查询
	 * mobile	String			手机号
	 * ->reqSeq(流水号)->brh(机构号)->prdtId(产品号)->type(查询类型)->mobile('手机号')->kami()
    */
    public function kami()
    {
    	$this->parse_data['method'] = self::CARD_KAMI_STOCK;
		$post_data = $this->parseData($this->parse_data);
    	return self::parseJSON('Post', array($this->url, $post_data, self::TIME_OUT ,self::ORDER_EXPIRED_RESPONSE)); 
    }

	/*	
	 * 实体卡接口：注册、绑定、取码、解绑
	 * brh		String	必须	机构号		
	 * reqSeq	String	必须	请求交易流水号	
	 * prdtId	String	必须	产品号
	 * type		Intval	必须	类型		0：注册、1：绑卡、2：取码、3：解绑
	 * openId	String	必须	微信公众号获得的OpenId
     * mobile   String          手机号 
	 * pwd		String			密码 		0：设置支付密码、1：预付卡密码、2、3：可空	
	 * email	String			邮箱
	 * cardNo	String			预付卡卡号
	 * ->reqSeq(流水号)->brh(机构号)->prdtId(产品号)->mobile(手机号)->type(类型)->openId(微信公众号获得的OpenId)->pwd(密码)->email(邮箱)->cardNo(预付卡卡号)->entity();
	*/
    public function entity()
    {
    	$this->parse_data['method'] = self::ENTITY_SERVICE;
		$post_data = $this->parseData($this->parse_data);
    	return self::parseJSON('Post', array($this->url, $post_data, self::TIME_OUT ,self::ENTITY_SERVICE_RESPONSE)); 
    }

    protected static function buyType($buyType)
    {
        if (is_numeric($buyType)) {
            $buyType = intval($buyType);
        } else {
            $buyType = 0;
        }    	
    	return $buyType;
    }

    protected static function buyQty($buyQty)
    {
        if (is_numeric($buyQty)) {
            $buyQty = intval($buyQty);
        } else {
            $buyQty = 1;
        }    	
    	return $buyQty;
    }

    protected static function amt($amt)
    {
        if (is_numeric($amt)) {
            return $amt * 100;
        }
    }

    protected static function brh($brh)
    {
        if (empty($brh)) {
            $brh = C('DB.YS_BRH');
            //self::$error = self::MSG_BRH_NOT_EXIST;
        }
        return $brh;
    }      
}