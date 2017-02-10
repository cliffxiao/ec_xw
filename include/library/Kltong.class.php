<?php

/* 访问控制 */
defined('IN_ZFT') or die('Deny Access');

/**
 * 开联通卡
 */
class Kltong {

    private $app_key = ''; //AOP分配给应用统一的AppKey
    // 测试app_key：zz901
    //private $app_key = 'QBL001';
    private $method = ''; // API接口名称
    private $timestamp = ''; //时间戳，格式为yyyyMMddHHmmss
    private $v = '1.0';  // API协议版本，可选值:1.0
    private $sign_v = '1'; // 签名版本号，每次更新后+1递增，可选值：1
    private $sign = ''; // API输入参数签名结果，使用md5加密
    // 测试sign：zz9011aopreq201211071748130tnFzL7a
    //private $sign = 'QBL0011aopreq20160704181305x5IutrOz';
    private $format = 'json'; //指定响应格式。默认json,目前支持格式为xml,json
    private $req_seq = ''; // 请求交易流水号，10位数字
    private $req_dt = ''; // 请求交易日期，YYYYMMDD
    private $req_tm = ''; // 请求交易时间，HHMMSS
    private $partner_id = 'aop-sdk-java-20110125'; // 操作员，如网站和app为不同值
    private $card_sn = ''; //卡号

    const CARD_URL      = 1; // 短链接
    const CARD_PASS     = 4; // 卡密
    const CARD_CANCEL   = 6; // 撤消

    // 测试地址：http://mscnew.koolyun.cn/aop/rest
    //private $url = http://180.153.20.175/aop/rest;
    private $url = '';

    public  $error = ''; // 错误代码

    private $card_status = array(
            '0' => '正常',
            '1' => '挂失',
            '3' => '销卡',
            '4' => '止付',
            '5' => '临时挂失',
        );
    private $txn_status = array(
            '0' => '挂起', //（暂无使用）
            '1' => '失败', //（暂无使用）
            '2' => '成功',
            '3' => '已冲正', //（消费、撤销、圈存写）
            '4' => '已取消', //（消费填写）
        );

    public function __construct()
    {
        $this->app_key = C('KL_APPKEY');
        $this->sign = C('KL_SIGN');
        $this->url = C('KL_URL');
    }

    // 处理参数
    public function parseData($config = array())
    {
        $data['app_key'] = $this->app_key;
        $data['method'] = $this->method;
        $data['timestamp'] = date('YmdHis');
        $data['v'] = $this->v;
        $data['sign_v'] = $this->sign_v;
        $data['format'] = $this->format;
        $data['req_seq'] = NOW_TIME;
        $data['req_dt'] = date('Ymd');
        $data['req_tm'] = date('His');
        $data['partner_id'] = $this->partner_id;
        $data = array_merge($data, $config);
        $data = array_filter($data);
        //type=0时
        if(!isset($data['type']) && isset($config['type'])){
            $data['type'] = $config['type'];
        }
        ksort($data);
        foreach ($data as $key => $value) {
            $parseData[] = $key .'='. $value;
            $sign[] = $key.$value;
        }

        $parseData = implode('&', $parseData);
        $sign = md5($this->sign.implode('', $sign).$this->sign);
        return 'sign='.$sign . '&' . $parseData;
    }

    //卡券激活
    public function card_active($card_info = array())
    {
        if(empty($card_info['custId']) || empty($card_info['amountAt']))
        {
            $this->error = '没有卡号或金额。';
            return false;
        }elseif(empty($card_info['openBrh']) || empty($card_info['prdtNo']))
        {
            $this->error = '机构号或产品号不存在。';
            return false;            
        }
        $this->method = 'allinpay.ggpt.ecard.cardbal.add';
        $config['reqSeq'] = time() + rand(10000, 99999);
        $config['custId'] = $card_info['custId'];
        $config['prdtNo'] = $card_info['prdtNo'];
        $config['openBrh'] = $card_info['openBrh'];
        $config['amountAt'] = $config['txAt'] = $card_info['amountAt'] * 100;
        $post_data = $this->parseData($config);
        $data = $this->Post($post_data);
        return $this->callback($data, "ggpt_ecard_cardbal_add_response");      
    }

    //卡券余额
    public function card_balance($cards = array(), $card_brh = '')
    {
        $this->method = 'smartpay.ggpt.commoncard.blance';
        if(empty($cards))
        {
            $this->error = '卡号不能为空。';
            return false;            
        }
        elseif(empty($card_brh))
        {
            $this->error = '机构号不能为空。';
            return false;            
        }          
        if(is_array($cards)){
            $config['cards'] = "'".implode("','", $cards)."'";
        }else{
            $config['cards'] = "'".$cards."'";
        }
        $config['card_brh'] = $card_brh;

        $post_data = $this->parseData($config);
        $data = $this->Post($post_data);
        return $this->callback($data, "qry_blance_response");
    }

    //卡券交易明细查询
    public function card_details($card_no = '', $card_brh = '', $start_dt ='', $end_dt = '', $page_num = 1, $page_size = 20)
    {
        if(empty($card_no))
        {
            $this->error = '卡号不能为空。';
            return false;            
        }
        elseif(empty($card_brh))
        {
            $this->error = '机构号不能为空。';
            return false;            
        }        
        elseif(empty($start_dt) || empty($end_dt))
        {
            $this->error = '查询开始日期或结束日期不存在。';
            return false;            
        }        
        $this->method = 'smartpay.ggpt.commoncard.txnlog';
        $config['cardNo'] = $card_no;
        $config['startDt'] = $start_dt;
        $config['endDt'] = $end_dt;
        $config['pageNum'] = $page_num;
        $config['pageSize'] = $page_size;
        $config['card_brh'] = $card_brh;
        $post_data = $this->parseData($config);
        $data = $this->Post($post_data);
        return $this->callback($data, "qry_txnlog_response");
    }

    // 卡券二维码生成
    public function card_paycode($card_no = '', $prdt_no = '', $card_brh = '')
    {     
        if(empty($card_no))
        {
            $this->error = '卡号不能为空。';
            return false;            
        }        
        $this->method = 'smartpay.ggpt.commoncard.dimecode';
        $config['cardNo'] = $card_no;
        $config['cardBrh'] = $card_brh;
        $config['prdtNo'] = $prdt_no;
        $post_data = $this->parseData($config);
        $data = $this->Post($post_data);
        return $this->callback($data, "build_dimecode_response");
    }

    // 购卡
    public function yinshang_buy_card ($reqSeq = '', $amt = 0, $endDt = '', $brh = '', $city = '', $prdtId = '', $buyType = 0, $buyQty = 1)
    {
    	$wmart = C('WMART_CITY');
    	if(isset($wmart[$prdtId])){//沃尔玛的城市名称 特殊处理
            $city=$wmart[$prdtId];
        }
        if (is_numeric($buyType)) {
            $buyType = intval($buyType);
        } else {
            $buyType = 0;
        }

        if ($buyType == 0) {
            $notify = get_domain() . url('yinshang/notify', array('reqseq'=>$reqSeq));
            $buyType .= 'Y' . ($notify);
        }

        if (is_numeric($buyQty)) {
            $buyQty = intval($buyQty);
        } else {
            $buyQty = 1;
        }

        if (!is_numeric($amt)) {
            $this->error = '金额不正确';
            return false;  
        } elseif ($amt > 1000) {
            $this->error = '金额超出购买上限';
            return false;  
        } elseif (empty($brh)) {
            $this->error = '机构号不能为空';
            return false;           
        } elseif (empty($endDt)) {
            $this->error = '卡片有效期不存在';
            return false;   
        } elseif (empty($reqSeq)) {
            $this->error = '流水号不存在';
            return false;
        } elseif (empty($prdtId)) {
            $this->error = '产品号不能为空';
            return false;
        } elseif ($buyQty > 5) {
            $this->error = '购买最大数量不能超过 5 张';
            return false;
        }

        $this->method = 'smartpay.cardVoucher.yinshang.buycard';
        $config['reqSeq'] = $reqSeq;
        $config['txnDate'] = date('Ymd');
        $config['txnTime'] = date('His');
        $config['amt'] = $amt * 100;
        $config['brh'] = $brh;
        $config['buyType'] = $buyType;
        if ($buyType == 1) {
            $config['buyQty'] = $buyQty;
        }
        $config['prdtId'] = $prdtId;        
        $config['endDt'] = $endDt;
        $config['city'] = $city;  
        $post_data = $this->parseData($config);
        $data = $this->Post($post_data);
        $array = $this->callback($data, "cardVoucher_yinshang_buycard_response");
        $array['reqSeq'] = $config['reqSeq'];
        $array['txnDate'] = $config['txnDate'];
        $array['txnTime'] = $config['txnTime'];
        return $array;
    }

    //消费明细查询
    public function yinshang_card_detail($brh = '',$billReqSeq = '', $billTxnDate = '', $prdtId = '', $type = 1, $cardAmt = 1, $mobile = '')
    {
        if (empty($billReqSeq)) {
            $this->error = '下单请求交易流水号不存在';
            return false;   
        } elseif (empty($billTxnDate)) {
            $this->error = '下单请求交易时间不存在';
            return false;  
        } elseif (empty($brh)) {
            $this->error = '机构号不能为空';
            return false;   
        } elseif (empty($prdtId)) {
            $this->error = '产品号不能为空';
            return false;
        }

        if (!is_numeric($type)) {
            $type = self::CARD_URL;
        } elseif($type == self::CARD_PASS) { // 卡密查询
            $config['cardAmt'] = $cardAmt;
        }
        $this->method = 'smartpay.cardVoucher.yinshang.txndetail';
        $config['reqSeq'] = date('YmdHis') + rand(10000, 99999);
        $config['billTxnDate'] = $billTxnDate;
        $config['billReqSeq'] = $billReqSeq;
        $config['type'] = $type;
        $config['brh'] = $brh;
        $config['prdtId'] = $prdtId;
        $post_data = $this->parseData($config);
        $data = $this->Post($post_data);
        return $this->callback($data, "cardVoucher_yinshang_txndetail_response");   
    }

    // 查询卡密
    public function yinshang_card_detail_kami($brh = '', $billReqSeq = '', $billTxnDate = '', $prdtId = '', $cardAmt = '', $mobile = '')
    {
        return $this->yinshang_card_detail($brh, $billReqSeq, $billTxnDate, $prdtId, 4, $cardAmt, $mobile);
    }

    // 撤销短地址订单
    public function yinshang_card_cancel($brh = '', $billReqSeq = '', $billTxnDate = '', $prdtId = '', $mobile = '')
    {
        return $this->yinshang_card_detail($brh, $billReqSeq, $billTxnDate, $prdtId, 6, 1, $mobile);
    }

    // 过期订单查询
    public function yinshang_order_expired($brh='', $prdtId = '', $startDt = '', $endDt = '', $type = 2, $pageNum = 1, $pageSize = 10)
    {
        if (empty($brh)) {
            $this->error = '机构号不能为空';
            return false;            
        } elseif (empty($prdtId)) {
            $this->error = '产品号不能为空';
            return false;
        } elseif (empty($startDt)) {
            $this->error = '查询起始日期不存在';
            return false; 
        } elseif (empty($endDt)) {
            $this->error = '查询截止日期不存在';
            return false; 
        }

        if (!is_numeric($pageNum)) {
            $pageNum = 1;
        }
        if (!is_numeric($pageSize)) {
            $pageSize = 10;
        }

        $this->method = 'smartpay.cardVoucher.yinshang.order.expired';
        $config['reqSeq'] = date('YmdHis') + rand(10000, 99999);
        $config['prdtId'] = $prdtId;
        $config['startDt'] = $startDt;
        $config['endDt'] = $endDt;
        $config['type'] = $type;
        $config['brh'] = $brh;
        $config['pageNum'] = $pageNum;
        $config['pageSize'] = $pageSize;
        $post_data = $this->parseData($config);
        $data = $this->Post($post_data);
        return $this->callback($data, "cardVoucher_yinshang_order_expired_response");      
    }

    // 卡密库存、备付金额查询
    public function yinshang_kami_stock($brh = '', $prdtId = '', $type = 3, $mobile = '')
    {
        if (empty($brh)) {
            $this->error = '机构号不能为空';
            return false;            
        } elseif (empty($prdtId)) {
            $this->error = '产品号不能为空';
            return false;
        } elseif (!is_numeric($type)) {
            $this->error = '查询类型必需为数字';
            return false;
        }

        $this->method = 'smartpay.cardVoucher.yinshang.kami.stock';
        $config['reqSeq'] = date('YmdHis') + rand(10000, 99999);
        $config['brh'] = $brh;
        $config['prdtId'] = $prdtId;
        $config['type'] = $type;
        $config['mobile'] = $mobile;
        $post_data = $this->parseData($config);
        $this->method .= '.'.$config['type'];
        $data = $this->Post($post_data);
        return $this->callback($data, "cardVoucher_yinshang_order_expired_response");         
    }

    // 绑卡
    public function yinshang_entity_card($data,$type)
    {
        if (empty($data['prdtId'])) {
            $this->error = '产品号不能为空';
            return false;
        } elseif (!is_numeric($type)) {
            $this->error = '查询类型必需为数字';
            return false;
        }

        $this->method = 'smartpay.cardVoucher.yinshang.entitycard.service';
        $config['reqSeq'] = date('YmdHis') + rand(10000, 99999);
        $config['brh'] = C("DB.YS_BRH");
        $config['prdtId'] = $data['prdtId'];
        $config['type'] = $type;
        $config['cardNo'] = $data['cardNo'];
        $config['pwd'] = $data['pwd'];
        $config['openId'] = $data['openId'];

        $post_data = $this->parseData($config);
        $this->method .= '.'.$config['type'];
        $data = $this->Post($post_data);
        return $this->callback($data, "cardVoucher_yinshang_entitycard_bind_response");
    }

    //解析结果
    private function callback($data, $type='')
    {
        if(empty($data)){
            $this->error = '接口没有返回数据';
            return false;
        }
        $api_id = $this->log($data, 'JSON', 2);
        if (strpos($data, 'html') && strpos($data, 'body')) {
            $this->error = $api_msg = strip_tags($data);
            preg_match("/\d+/", $api_msg, $match);
            $api_code = $match[0];
            $this->update_log($api_id, $api_code, $api_msg);
            return false;
        }
        $data = str_replace(array('\n', '\t'), array('', ''), $data);
        $data = stripslashes($data);
        $data = preg_replace( "/:(\d+)(,|})/", ':"$1"$2', $data);
        $data = preg_replace( '/"{"(.*?)"}"/', '{"$1"}', $data);
        //$data = preg_replace( '/\\\":"(\d+)"(,|})/', '\":\"$1\"$2', $data);
        $data = json_decode($data, true);
        if (isset($data['error_response'])) {
            if (isset($data['error_response']['sub_msg'])) {
            	$api_code = isset($data['error_response']['sub_code']) ? $data['error_response']['sub_code'] : '';
            	$api_msg = $data['error_response']['sub_msg'];
            } else {
            	$api_code = isset($data['error_response']['code']) ? $data['error_response']['code'] : '';
            	$api_msg = $data['error_response']['msg'];
            }

            $this->error = $api_code . ':' . $api_msg;
            $this->update_log($api_id, $api_code, $api_msg);
            return false;
        }
        elseif (isset($data[$type]['rsp_code']) && $data[$type]['rsp_code'] == '0000')
        {
            if (isset($data[$type]['json']['code'])) {
                $api_code = $data[$type]['json']['code'];
                $api_msg = isset($data[$type]['json']['msg']) ? $data[$type]['json']['msg'] : '查询成功';
                if ($api_code != '00') {
                    $this->error = $api_code . ':' . $api_msg;
                }
            } elseif ($type == 'cardVoucher_yinshang_buycard_response') {
                $api_code = $data[$type]['rsp_code'];
                $api_msg = '激活成功';
                $this->error = ''; 
            } else {
                $api_code = $data[$type]['rsp_code'];
                $api_msg = isset($data[$type]['msg']) ? $data[$type]['msg'] : '查询成功';
                $this->error = '';    
            }
            $this->update_log($api_id, $api_code, $api_msg);
            return $data[$type];
        }
        elseif(isset($data[$type]['code']) && $data[$type]['code'] == '0000')
        {
            //绑卡注册
            $api_code = $data[$type]['code'];
            $api_msg = isset($data[$type]['msg']) ? $data[$type]['msg'] : '注册或绑卡成功';
            $this->error = '';
            $this->update_log($api_id, $api_code, $api_msg);
            if($type == 'cardVoucher_yinshang_entitycard_bind_response'){
                return array('code'=>1,'data'=> $data[$type]);
            }
            return $data[$type];
        }
        else
        {
            $api_msg = $data[$type]['msg'];
            $api_code = isset($data[$type]['rsp_code']) ? $data[$type]['rsp_code'] : $data[$type]['code'];
            $this->error = $api_code . ':' . $api_msg;
            $this->update_log($api_id, $api_code, $api_msg);
            if($type == 'cardVoucher_yinshang_entitycard_bind_response'){
                return array('code'=>0, 'data' => $data[$type]);
            }
            return false;
        }
    }

    //检验数据
    public function check_data()
    {
    }


    private function Post($post_data){
        $this->log($post_data, 'POST', 1);
        return Http::doPost($this->url, $post_data, 65);
    }

    // 替换隐藏字符串
    private function get_hidden($string, $start = 4, $type = '*')
    {
        $len = strlen($string);
        $string = substr($string, $len - $start);
        return sprintf("%'".$type.$len."s", $string);
    }

    private function log($post_data, $api_type = 'JSON', $api_status = 0){

        $json = stripslashes($post_data);
        preg_match("'\"shortUrl\":\"(.*?)\"'is", $json, $temp);
        if (isset($temp[1])) {
            $start = strripos($temp[1], '/');
            if ($start) { // 最后出现的位置
                $pattern = substr($temp[1], $start + 1);
                $post_data = str_replace($pattern, $this->get_hidden($pattern), $post_data);
            }
        }

        preg_match("'\"wbmp\":\"(.*?)\"'is", $json, $temp);
        if (isset($temp[1])) {
            // 存在短链接
            if (preg_match("/(http|https):\/\//is", $temp[1])) {
                $start = strripos($temp[1], '/');
                $pattern = substr($temp[1], $start + 1);
                $end = strpos($pattern, '|');
                if ($end) {
                    $pattern = substr($pattern, 0, $end);
                }
                $post_data = str_replace($pattern, $this->get_hidden($pattern), $post_data);
            } else { // 卡密
                $array = explode('#', $temp[1]);
                $pattern = array();
                foreach ($array as $key => $value) {
                    $string = explode('|', $value);
                    foreach ($string as $k => $v) {
                        $pattern[$v] = $this->get_hidden($v);
                    }
                    
                }
                if (!empty($pattern)) {
                    $post_data = str_replace(array_keys($pattern), array_values($pattern), $post_data);
                }
            }
        }

        preg_match("'\"ecodes\":\"(.*?)\"'is", $json, $temp);
        if (isset($temp[1])) {
            $array = explode(',', $temp[1]);
            if (isset($array[1])) {
                $pattern[0] = $array[0];
                $pattern[1] = $this->get_hidden($array[1]);
            } else {
                $pattern[0] = $this->get_hidden($array[0]);
            }
            if (!empty($pattern)) {
                $post_data = str_replace($array, $pattern, $post_data);
            }
        }

        //$data['user_id'] = I('session.user_id', 0, 'intval');
        //$data['api_url'] = $this->url;
        $data['api_method'] = $this->method;
        $data['api_data'] = $post_data;
        //$data['api_date'] = NOW_TIME;
        $data['api_type'] = $api_type;
        $data['api_status'] = $api_status;
        return model('Common')->add_api_log($data);
    }

    public function update_log($api_id = 0, $api_code = '', $api_msg = '') {
        return model('Common')->save_api_log($api_id, $api_code, $api_msg);
    }

}

?>