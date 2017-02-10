<?php

/**
* 开联通
*/
class Kltong extends Driver
{
    const CARDBAL_ADD = 'allinpay.ggpt.ecard.cardbal.add';
    const CARDBAL_ADD_RESPONSE = 'ggpt_ecard_cardbal_add_response';
    const ELECARD_BLANCE = 'smartpay.ggpt.elecard.blance';
    const ELECARD_BLANCE_RESPONSE = 'qry_blance_response';
    const CARD_TXNLOG = 'smartpay.ggpt.commoncard.txnlog';
    const CARD_TXNLOG_RESPONSE = 'qry_txnlog_response';
    const CARD_DIMECODE = 'smartpay.ggpt.commoncard.dimecode';
    const CARD_DIMECODE_RESPONSE = 'build_dimecode_response';

    const TIME_OUT = 15;  // 请求超时时间

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
     * 卡券激活
     * reqSeq		String	必须	请求交易流水号	
     * req_dt		String	必须	请求交易日期
     * req_tm		String	必须	请求交易时间
     * custId		String	必须	卡号	
     * prdtNo		String	必须	产品号	
     * openBrh		String	必须	发卡机构号	
     * mer_id		String			商户号
     * sale_opr		String			操作员 				同open_brh
     * note			String			备注
     * amountAt		String	必须	原始金额			单位分	12位	
     * txnDate		String	必须	实际金额			单位分	12位	
     * end_flg		String			有效期标志			（固定）	1
     * endDt		String			卡有效期			Ymd
     * ->reqSeq(请求交易流水号)->openBrh(机构号)->prdtNo(产品号)->custId(卡号)->amountAt(原始金额)->txAt(实际金额)->endDt(卡有效期)->buy();
     */
    public function buy()
    {
    	$this->parse_data['method'] = self::CARDBAL_ADD;
		$post_data = $this->parseData($this->parse_data);
    	return self::parseJSON('Post', array($this->url, $post_data, self::TIME_OUT ,self::CARDBAL_ADD_RESPONSE));  
    }

    /*
     * 卡券余额查询
     * cards 		List<String>	必须	卡号列表	列表长度限制为10'
     * card_brh		String 			必须	发卡机构
     * ->card_no_list(卡号列表)->card_brh(发卡机构)->balance()
     */
    public function balance()
    {
    	$this->parse_data['method'] = self::ELECARD_BLANCE;
		$post_data = $this->parseData($this->parse_data);
    	return self::parseJSON('Post', array($this->url, $post_data, self::TIME_OUT ,self::ELECARD_BLANCE_RESPONSE));  
    }

    /*
     * 卡券交易明细查询
     * cardNo		String	必须	卡号
     * startDt 		String	必须	开始日期	Ymd
     * endDt		String	必须	结束时间	Ymd
	 * pageNum		Intval	必须	页码		默认：1
	 * pageSize		Intval	必须	分页大小	默认：20
	 * card_brh		String 	必须	发卡机构
	 * ->cardNo()->startDt()->endDt()->pageNum()->pageSize()->card_brh()->detail()
     */
    public function detail()
    {
    	$this->parse_data['method'] = self::CARD_TXNLOG;
		$post_data = $this->parseData($this->parse_data);
    	return self::parseJSON('Post', array($this->url, $post_data, self::TIME_OUT ,self::CARD_TXNLOG_RESPONSE));  
    }

	/*    
	 * 卡券二维码生成
	 * cardNo		String		必须	卡号
	 * cardBrh 		String 		必须	发卡机构
	 * prdtNo 		String 		必须	产品号
	 * ->cardNo()->cardBrh()->prdtNo()->QRCode()
	 */
    public function QRCode()
    {
    	$this->parse_data['method'] = self::CARD_DIMECODE;
		$post_data = $this->parseData($this->parse_data);
    	return self::parseJSON('Post', array($this->url, $post_data, self::TIME_OUT ,self::CARD_DIMECODE_RESPONSE));  
    }

    protected static function pageNum($pageNum)
    {
    	$pageNum = intval($pageNum);
    	if ($pageNum <= 0) {
    		$pageNum = 1;
    	}
    	return $pageNum;
    }

    protected static function pageSize($pageSize)
    {
    	$pageSize = intval($pageSize);
    	if ($pageSize <= 0) {
    		$pageSize = 20
    	}
    	return $pageSize;
    }
    protected static function cards($cards)
    {
        if (is_array($cards)) {
            $cards = "'".implode("','", $cards)."'";
        } else {
            $cards = "'".$cards."'";
        }
        return $cards;
    }

    protected static function amountAt($amountAt)
    {
    	if (is_numeric($amountAt)) {
    		return $amountAt * 100;
    	}
    }

    protected static function txAt($txAt)
    {
    	if (is_numeric($txAt)) {
    		return $txAt * 100;
    	}

}