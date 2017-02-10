<?php
/**
* 
*/
class Card
{
	
    private static $instance = array();
    const CARD_NOT_EXIST = '卡券不存在';
    const ERROR_PAY_CODE = '扫码页打开失败，请稍候重试';
    const SELECT_SUCCESS = '查询成功';
    const SELECT_ERROR   = '查询失败';

    // 调用驱动类的方法
    public static function __callStatic($method, $params)
    {
    	$classname = array_shift($params);
        // 自动初始化数据库
        return call_user_func_array(array(self::connect($classname), $method), $params);
    }

    /**
     * 数据库初始化 并取得数据库类实例
     */
    public static function connect($name)
    {
    	if (!isset(self::$instance[$name])) {
    		$class = ucwords($name);
    		require_once  (dirname(__FILE__) . '/Card/Driver.php');
    		require_once  (dirname(__FILE__) . '/Card/' . $class . '.php');
    		self::$instance[$name] = new $class();
    	}
		return self::$instance[$name];
    }

    /**
     * 银商购卡
     *  brh     String  必须  机构号 测试环境为：0227600003
     *  reqSeq  String  必须  请求交易流水号 查询明细复用
     *  txnDate String  必须  请求交易日期  8位  查询明细复用
     *  txnTime String  必须  请求交易时间  6位  
     *  amt     String  必须  购买金额，卡密时为固定100 不大于100000分
     *  endDt   String  必须  卡片有效期   Ymd
     *  city    String  必须  使用城市
     *  prdtId  String  产品号测试环境短连接为7060010001，卡密为：7060020001
     *  buyQty  String  购买数量，不能大于5，只用于卡密
     *  buyType String  购买类型，0短连接，1卡密   默认0
     */
    public static function yinshang_buycard($reqSeq = '', $amount = '', $brh = '', $city = '', $endDt = '', $prdtId = '', $buyType = 0, $buyQty = 1)
    {
        // 卡券有效期
        if (empty($endDt)) {
            $endDt = strtotime('+3 years');
            $endDt = date('Ymd', $endDt);
        }
        $txnDate = date('Ymd');
        $txnTime = date('His');
        $yinshang = self::init('yinshang')->reqSeq($reqSeq)->brh($brh)->prdtId($prdtId)
                ->amt($amount)
                ->city($city)->endDt($endDt)->buyType($buyType)
                ->txnDate($txnDate)->txnTime($txnTime);

        if ($buyType == 1) {
            $yinshang->buyQty($buyQty);
        }

        $data = $yinshang->buy();
        if ($yinshang->getError()) {
            ECTouch::err()->add($yinshang->getError());
            return false;
        }

        $arr = array();
        // 短链接
        if (strpos($data['wbmp'], '|') && $buyType == 0) {
            list($arr['card_sn'], $arr['card_url'], $arr['end_date']) = explode('|', $data['wbmp']);
            $arr['openBrh'] = $brh;
            $arr['city_name'] = $city;
            $arr['is_act'] = 1;
            $arr['add_date'] = date_from_format($txnDate.$txnTime);
            $arr['crc32'] = crc32(AUTH_KEY);
            $arr['card_url'] = encrypt($arr['card_url'], C("DB.URL_KEY"));
            $arr['end_date'] = date_from_format($arr['end_date']);
            $arr['reqSeq'] = $reqSeq;
            $arr['txnDate'] = $txnDate;
            $arr['balance'] = $amount;
            $arr['card_type'] = $buyType;
            return $arr;
        // 卡密
        } elseif($data['wbmp'] && $buyType == 1) {
            $cards = explode('#', $data['wbmp']);
            $add_date = date_from_format($txnDate.$txnTime);
            $end_date = date_from_format($endDt);
            $card_key = C('CARD_KEY');
            foreach ($cards as $key => $value) {
                if (strpos($value, '|')) {
                    list($card_sn, $card_password) = explode('|', $data['wbmp']);
                    $arr[$key]['card_sn'] = encrypt($card_sn, $card_key);
                    if($card_password==''){
                        $arr[$key]['card_password'] =$card_password;
                    }else{
                        $arr[$key]['card_password'] = encrypt($card_password, $card_key);
                    }   
                } else {
                    $arr[$key]['card_sn'] = encrypt($value, $card_key);
                    $arr[$key]['card_password'] = '';
                }
                $arr[$key]['reqSeq'] = $reqSeq;
                $arr[$key]['txnDate'] = $txnDate;
                $arr[$key]['balance'] = $amount;
                $arr[$key]['crc32'] = crc32(AUTH_KEY);
                $arr[$key]['is_act'] = 1;
                $arr[$key]['openBrh'] = $brh;
                $arr[$key]['prdtNo'] = $prdtId;
                $arr[$key]['city_name'] = $city;
                $arr[$key]['add_date'] = $add_date;
                $arr[$key]['end_date'] = $end_date;
                $arr[$key]['card_type'] = $buyType;
            }
            return $arr;
        }
        else{
            return false;
        }
    }

    /**
     * 银商查询短链接记录
     * card_id      intval  必须  卡号ID
     * start_dt     datetime      查询开始时间YmdHis
     * end_dt       datetime      查询结束时间YmdHis
     * mobile       string        手机号
     */
    public static function yinshang_cardetailshort($card_id, $start_dt = '', $end_dt = '', $mobile = '')
    {
        $where['card_id']   = $card_id;
        //$where['card_type'] = 0;
        $field['openBrh']   = 'brh';
        $field['reqSeq']    = 'billReqSeq';
        $field['txnDate']   = 'billTxnDate';
        $field['prdtNo']    = 'prdtId';
        $field['add_date']  = 'start_dt';
        $field['end_date']  = 'end_dt';
        foreach ($field as $key => $value) {
            array_push($field, "`$key` AS $value"); 
            unset($field[$key]);
        }
        $field = implode(',', $field);
        $card_info = model('Common')->model->table('virtual_card')->field($field)->where($where)->find();
        if (empty($card_info)) {
            ECTouch::err()->add(self::CARD_NOT_EXIST);
            return false;
        }
        $card_info['start_dt'] = empty($start_dt) ? date('YmdHis', $card_info['start_dt']) : $start_dt;
        $card_info['end_dt'] = empty($end_dt) ? date('YmdHis', $card_info['end_dt']) : $end_dt;
        foreach ($card_info as $key => $value) {
            $$key = $value;
        }

        if (empty($start_dt)) {
            $start_dt = date('YmdHis', $card_info['start_dt']);
        }
        if (empty($end_dt)) {
            $end_dt = date('YmdHis', $card_info['end_dt']);
        }

        // 用于API日志记录
        session('api_key', array('id' => $card_id, 'val'=> '1'));

        $yinshang = self::init('yinshang')->reqSeq()->brh($brh)->prdtId($prdtId);
        $yinshang->billReqSeq($billReqSeq)->billTxnDate($billTxnDate);
        $data = $yinshang->mobile($mobile)->type($yinshang::DETAIL_CONSUMPTION)->detail();
        if ($yinshang->getError()) {
            ECTouch::err()->add($yinshang->getError());
            return false;
        }

        $info = array();
        if (!empty($data['json'])) {
            $detail_list = $data['json'];
            // 存在消费
            if (isset($detail_list['info']['consumeInfos'])) {
                foreach ($detail_list['info']['consumeInfos'] as $key => $value) {
                    $orderDate = $value['tranDate'] . ' ' . $value['tranTime'];
                    $orderDate = date_from_format($orderDate, 'YmdHis');
                       
                    if ($orderDate > $end_dt) {
                        // 订单时间大于结束时间
                        unset($detail_list['info']['consumeInfos'][$key]);
                    } elseif ($start_dt && $orderDate < $start_dt) {
                        // 订单时间小于开始时间
                        unset($detail_list['info']['consumeInfos'][$key]);
                    } else {
                        $tmp['orderDate'] = date_from_format($orderDate, 'Y-m-d H:i:s');
                        $tmp['orderNo'] = $value['tranid'];
                        $tmp['orderAmount'] = $value['payAmount'];
                        $tmp['orderInfo'] = $value['merName'];
                        array_push($info, $tmp);  
                    }
                }
                // 去除消费记录
                unset($detail_list['info']['consumeInfos']);
            }
            if ($detail_list['info']['orderDate'] > $end_dt) {
            // 订单时间大于结束时间
            } elseif ($start_dt && $detail_list['info']['orderDate'] < $start_dt) {
            // 订单时间小于开始时间
            } else {
                $tmp = $detail_list['info'];
                $tmp['orderDate'] = date_from_format($tmp['orderDate'], 'Y-m-d H:i:s');
                $tmp['cardNo'] = $detail_list['cardNo'];
                $tmp['shortUrl'] = $detail_list['shortUrl'];
                array_push($info, $tmp);
            }
        }
        return $info;      
    }

    /**
     * 银商查询卡密记录
     * card_id      intval  必须  卡号ID
     * mobile       string        手机号
     */
    public static function yinshang_cardetailkami($card_id, $mobile = '')
    {
        $where['card_id']   = $card_id;
        $where['card_type'] = 1;
        $field['openBrh']   = 'brh';
        $field['reqSeq']    = 'billReqSeq';
        $field['txnDate']   = 'billTxnDate';
        $field['prdtNo']    = 'prdtId';
        $field['balance']   = 'cardAmt';
        foreach ($field as $key => $value) {
            array_push($field, "`$key` AS $value"); 
            unset($field[$key]);
        }
        $field = implode(',', $field);
        $card_info = model('Common')->model->table('virtual_card')->field($field)->where($where)->find();
        if (empty($card_info)) {
            ECTouch::err()->add(self::CARD_NOT_EXIST);
            return false;
        }
        foreach ($card_info as $key => $value) {
            $$key = $value;
        }

        // 用于API日志记录
        session('api_key', array('id' => $card_id, 'val'=> '1'));

        $yinshang = self::init('yinshang')->reqSeq()->brh($brh)->prdtId($prdtId);
        $yinshang->billReqSeq($billReqSeq)->billTxnDate($billTxnDate);
        $data = $yinshang->mobile($mobile)->type($yinshang::DETAIL_KAMI)->cardAmt($cardAmt)->detail();
        if ($yinshang->getError()) {
            ECTouch::err()->add($yinshang->getError());
            return false;
        }

        $info = array();
        if (!empty($data['json'])) {        
            $detail_list = $data['json'];
            $card_key = C('CARD_KEY');
            if (strpos($detail_list['ecodes'], ',')) {
                list($card_sn, $card_password) = explode(',', $detail_list['ecodes']);
                $info['cardNo'] = encrypt($card_sn, $card_key);
                $info['card_password'] = encrypt($card_password, $card_key);
            } else {
                $info['cardNo'] = encrypt($detail_list['ecodes'], $card_key);
                $info['card_password'] = '';
            }         
        }
        return $info;        
    }

    /**
     * 银商取消短链接
     * card_id      intval  必须  卡号ID
     * mobile       string        手机号
     */
    public static function yinshang_cardcancel($card_id, $mobile = '')
    {
        $where['card_id']   = $card_id;
        $where['card_type'] = 0;
        $field['openBrh']   = 'brh';
        $field['reqSeq']    = 'billReqSeq';
        $field['txnDate']   = 'billTxnDate';
        $field['prdtNo']    = 'prdtId';
        $field['balance']   = 'cardAmt';
        foreach ($field as $key => $value) {
            array_push($field, "`$key` AS $value"); 
            unset($field[$key]);
        }
        $field = implode(',', $field);
        $card_info = model('Common')->model->table('virtual_card')->field($field)->where($where)->find();
        if (empty($card_info)) {
            ECTouch::err()->add(self::CARD_NOT_EXIST);
            return false;
        }
        foreach ($card_info as $key => $value) {
            $$key = $value;
        }

        // 用于API日志记录
        session('api_key', array('id' => $card_id, 'val'=> '1'));

        $yinshang = self::init('yinshang')->reqSeq()->brh($brh)->prdtId($prdtId);
        $yinshang->billReqSeq($billReqSeq)->billTxnDate($billTxnDate);
        $data = $yinshang->mobile($mobile)->type($yinshang::DETAIL_UNDO)->detail();
        if ($yinshang->getError()) {
            ECTouch::err()->add($yinshang->getError());
            return false;
        }

        return $data['json'];
    }

    /**
     * 银商查询备付金额
     * brh          String  必须  机构号     
     * prdtId       String  必须  产品号
     */
    public static function yinshang_standby($brh = '', $prdtId = '')
    {
        $yinshang = self::init('yinshang')->reqSeq()->brh($brh)->prdtId($prdtId);
        $data = $yinshang->type($yinshang::STANDBY_MONEY)->kami();
        if ($yinshang->getError()) {
            ECTouch::err()->add($yinshang->getError());
            return false;
        }        
        return $data['json'];
    }

    /**
     * 银商查询卡密库存
     * brh          String  必须  机构号     
     * prdtId       String  必须  产品号
     */
    public static function yinshang_stock($brh = '', $prdtId = '')
    {
        $yinshang = self::init('yinshang')->reqSeq()->brh($brh)->prdtId($prdtId);
        $data = $yinshang->type($yinshang::KAMI_STOCK)->kami();
        if ($yinshang->getError()) {
            ECTouch::err()->add($yinshang->getError());
            return false;
        }        
        return $data['json'];
    }

    /**
     * 银商查询余额
     * card_id      array  必须  卡号ID数组
     */
    public static function yinshang_cardbalance($card_id = array())
    {
        if(!function_exists('db_create_in')){
            define('AUTH_KEY', '@#zhifutong666&)G');
            require(BASE_PATH . 'base/function.php');
        }
        $where = db_create_in($card_id, 'card_id');
        $rows = model('Common')->model->field('card_id, card_url, is_used, is_saled, balance, card_type')->table('virtual_card')->where($where)->select();
        if (empty($rows)) {
            ECTouch::err()->add(self::CARD_NOT_EXIST);
            return false;
        }

        $balance = array();
        foreach ($rows as $key => $value) {
            if ($value['is_used'] == 1) {
                $balance[$value['card_id']] = 0;
            /*} elseif ($value['is_saled'] == 1 || $value['is_saled'] == 2) {
                $balance[$value['card_sn']] = $value['balance'];*/
            } else {
                $balance[$value['card_id']] = -1;
                $card_url = decrypt($value['card_url'], C("DB.URL_KEY"));
                preg_match("/^(http|ftp|https):\/\//i", $card_url, $matches);
                if (isset($matches[1])) {

                    $status = '';
                    $data = Http::curlGet($card_url, 10, '', $status);

                    // 记录日志
                    $log['api_key_id'] = $value['card_id'];
                    $log['api_key_val'] = 1; 
                    $log['api_code'] = $status;
                    $log['api_msg'] = $status == '200' ? self::SELECT_SUCCESS : self::SELECT_ERROR;

                    //$log['api_status'] = 2;
                    $log['api_type'] = 'CURL';
                    $log['api_method'] = 'my.yinshang.balance';
                    $log['api_data'] = '';
                    
                    $json = json_decode($data, true);
                    if ($json && isset($json['code'])) {
                        $log['api_data'] = $data;
                        $log['api_code'] = $json['code'];
                        $log['api_msg'] = urldecode(urldecode($json['msg']));
                    }
                    preg_match("/parseJSON\('(.*?)'\);/", $data, $parseJSON);
                    if (isset($parseJSON[1])) {
                        // 解析JSON
                        $parseJSON = json_decode($parseJSON[1], true);
                        if (isset($parseJSON['totalAmount'])) {
                            $balance[$value['card_id']] = (float)$parseJSON['totalAmount'];
                        }
                    }
                    model('ApiBase')->add_api_log($log);                                    
                }
            }
        }
        return $balance;
    }

    /**
     * 银商支付码
     * card_id      intval  必须  卡号ID
     */
    public static function yinshang_paycode($card_id = '')
    {
        $where['card_id'] = $card_id;
        //$where['card_type'] = 0;
        $card_info = model('Common')->model->field('card_url, card_id, card_type')->table('virtual_card')->where($where)->find();
        if (empty($card_info)) {
            ECTouch::err()->add(self::CARD_NOT_EXIST);
            return false;
        }
        $card_url = decrypt($card_info['card_url'], C("DB.URL_KEY"));
        preg_match("/^(http|ftp|https):\/\//i", $card_url, $matches);
        if (isset($matches[1])) {
            $status = '';
            $data =  Http::curlGet($card_url, 10, '', $status);

            // 记录日志
            $log['api_key_id'] = $card_info['card_id'];
            $log['api_key_val'] = 1;
            $log['api_type'] = 'CURL';
            $log['api_method'] = 'my.yinshang.paycode';
            $log['api_data'] = '';
            //$log['api_status'] = 2;
            $log['api_code'] = $status;
            $log['api_msg'] = $status == '200'? '查询成功' : '查询失败';
            $json = json_decode($data, true);
            if ($json && isset($json['code'])) {
                $log['api_data'] = $data;
                $log['api_code'] = $json['code'];
                $log['api_msg'] = urldecode(urldecode($json['msg']));
            }
            model('ApiBase')->add_api_log($log);
            if (empty($data)) {
                ECTouch::err()->add(self::ERROR_PAY_CODE);
                return false;
            }

           /* preg_match_all('#<script[\s\S]*?><\/script>#', $data, $script);
            $script = array_shift($script);
            foreach ($script as $key => $value) {
                preg_match('#http:\/\/[\s\S]*?\.js#', $value, $url);
                $temp = Http::doGet(array_shift($url));
                $data = str_replace($value, '<script>'.$temp.'</script>', $data);
            }

            preg_match_all('#<link[\s\S]*?>#', $data, $link);
            $link = array_shift($link);
            foreach ($link as $key => $value) {
                preg_match('#http:\/\/[\s\S]*?\.css#', $value, $url);

                $temp = Http::doGet(array_shift($url));
                $data = str_replace($value, '<style type="text/css">
    '.$temp.'</style>', $data);
            } */

            return  str_replace("$('body').html($('#txn_record').html())", "window.location.href='".url('couponorder/tradetail', array('id'=>$card_info['card_id']))."'", $data);
        } else {
            ECTouch::err()->add(self::ERROR_PAY_CODE);
            return false;
        }
    }

    /**
     * 银商注册实体卡用户开户
     * brh     string  必须  机构号
     * prdtId  string  必须  产品号
     * poenId  string  必须  微信ID
     * pwd     string  必须  设置用户密码
     * mobile  string        手机号
     */
    public static function yinshang_register_card($brh, $prdtId, $openId, $pwd, $mobile = '')
    {
        $yinshang = self::init('yinshang')->reqSeq()->brh($brh)->prdtId($prdtId)->openId($openId)->mobile($mobile);
        $result = $yinshang->pwd($pwd)->type($yinshang::ENTITY_TYPE_REGISTER)->entity();
        if ($yinshang->getError()) {
            ECTouch::err()->add($yinshang->getError());
            $yinshang->setError();            
            return false;
        }
        return $result;
    }    

    /**
     * 银商实体卡用户绑卡
     * brh     string  必须  机构号
     * prdtId  string  必须  产品号
     * poenId  string  必须  微信ID
     * cardNo  string  必须  绑卡的卡号
     * pwd     string  必须  绑卡的卡密码
     * mobile  string        手机号
     */
    public static function yinshang_bind_card($brh, $prdtId, $openId, $cardNo, $pwd, $mobile = '')
    {
        $yinshang = self::init('yinshang')->reqSeq()->brh($brh)->prdtId($prdtId)->openId($openId)->mobile($mobile);
        //$pwd = decrypt($card_info['card_pass'], C("DB.CARD_KEY"));

        // 用于API日志记录
        session('api_key', array('id' => $cardNo, 'val'=> '3'));

        // 银商绑卡
        $result = $yinshang->reqSeq()->cardNo($cardNo)->pwd($pwd)->type($yinshang::ENTITY_TYPE_BIND)->entity();
        if ($yinshang->getError()) {
            ECTouch::err()->add($yinshang->getError());
            return false;
        }
        return $result;
    }

    /**
     * 银商实体卡用户解绑
     * brh     string  必须  机构号
     * prdtId  string  必须  产品号
     * poenId  string  必须  微信ID
     * cardNo  string  必须  解绑的卡号
     * mobile  string        手机号
     */
    public static function yinshang_unbind_card($brh, $prdtId, $openId, $cardNo, $mobile = '')
    {
        // 银商解绑        
        $yinshang = self::init('yinshang')->reqSeq()->brh($brh)->prdtId($prdtId)->openId($openId)->mobile($mobile);

        // 用于API日志记录
        session('api_key', array('id' => $cardNo, 'val'=> '3'));

        $result = $yinshang->cardNo($cardNo)->type($yinshang::ENTITY_TYPE_UNBIND)->entity();
        if ($yinshang->getError()) {
            ECTouch::err()->add($yinshang->getError());
            return false;
        }
        return $result;
    }

    /**
     * 绑定卡获取余额、code
     * brh     string  必须  机构号
     * prdtId  string  必须  产品号
     * poenId  string  必须  微信ID
     * pwd     string  必须  注册用户密码
     * cardNo  string  必须  卡号
     * mobile  string        手机号
     */
    public static function yinshang_bindcard_balance($brh, $prdtId, $openId, $pwd, $cardNo, $mobile = '')
    {
        // 银商获取余额
        $yinshang = self::init('yinshang')->reqSeq()->brh($brh)->prdtId($prdtId)->openId($openId)->mobile($mobile);

        // 用于API日志记录
        session('api_key', array('id' => $cardNo, 'val'=> '3'));
                
        $result = $yinshang->cardNo($cardNo)->pwd($pwd)->type($yinshang::ENTITY_TYPE_CODE)->entity();
        if ($yinshang->getError()) {
            ECTouch::err()->add($yinshang->getError());
            return false;
        }
        // 获取余额成功
        $data['msg'] = $result['msg'];
        $data['code'] = $result['code'];
        if (isset($result['wbmp']) && !empty($result['wbmp'])) {
            list($data['paycode'], $data['balance'], $data['use_time']) = explode('|', $result['wbmp']);
        }
        return $data;           
    }       
}