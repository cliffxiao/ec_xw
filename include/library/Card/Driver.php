<?php
/**
* 
*/
class Driver
{
    protected $app_key 		= ''; // AOP分配给应用统一的AppKey
    protected static $method 		= ''; // API接口名称
    protected $timestamp 	= ''; // 时间戳，格式为yyyyMMddHHmmss
    protected $v 			= '1.0';  // API协议版本，可选值:1.0
    protected $sign_v 		= '1'; // 签名版本号，每次更新后+1递增，可选值：1
    protected $sign 		= ''; // API输入参数签名结果，使用md5加密
    protected $format		= 'json'; // 指定响应格式。默认json,目前支持格式为xml,json
    protected $req_seq		= ''; // 请求交易流水号，10位数字
    protected $req_dt		= ''; // 请求交易日期，YYYYMMDD
    protected $req_tm		= ''; // 请求交易时间，HHMMSS
    protected $partner_id	= 'aop-sdk-java-20110125'; // 操作员，如网站和app为不同值
    protected $card_sn		= ''; // 卡号
	protected $parse_data	= '';
	protected $url 			= '';
	protected static $error;

    const MSG_DATA_EMPTY        = '接口没有返回数据';
    const MSG_SELECT_SUCCESS    = '查询成功';
    const MSG_BUY_SUCCESS       = '激活成功';
    const MSG_CARD_BIND         = '注册或绑卡成功';
    const MSG_REQSEQ_NOT_EXIST  = '流水号不存在';
    const MSG_BRH_NOT_EXIST     = '机构号不存在';
    const MSG_PRDTID_NOT_EXIST  = '产品号不存在';

	function __construct()
	{
		# code...
	}

	public function init()
	{
        $this->app_key	= C('KL_APPKEY');
        $this->sign 	= C('KL_SIGN');
        $this->url 		= C('KL_URL');
        return $this;
	}

    // 处理参数
    protected function parseData($config = array())
    {
        $data['app_key']	= $this->app_key;
        //$data['method']		= $this->method;
        $data['timestamp']	= date('YmdHis');
        $data['v']			= $this->v;
        $data['sign_v']		= $this->sign_v;
        $data['format']		= $this->format;
        $data['req_seq']	= NOW_TIME;
        $data['req_dt']		= date('Ymd');
        $data['req_tm']		= date('His');
        $data['partner_id'] = $this->partner_id;
        $data = array_merge($data, $config);
        $data = array_filter($data);
        // 还原type为0的过滤
        if (isset($config['type']) && $config['type'] == 0) {
            $data['type'] = $config['type'];
        }
        // 还原buyType为0的过滤
        if (isset($config['buyType']) && $config['buyType'] == 0) {
            $data['buyType'] = $config['buyType'];
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

    // 解析
    protected static function parseJSON($method, array $args)
    {
        if (self::$error) {
            return false;
        }
        return call_user_func_array(array(__CLASS__ , $method), $args);
    }

    // 检验数据
    public function check_data()
    {

    }

    // POST请求
    protected static function Post($url, $post_data, $time_out = 10, $type)
    {
        self::add_log($post_data, 'POST', 1);
        $data = Http::doPost($url, $post_data, $time_out);
        return self::callback($data, $type);
    }

    //解析结果
    protected static function callback($data, $type='')
    {
        if(empty($data)){
            self::$error = self::MSG_DATA_EMPTY;
            return false;
        }
        $api_id = self::add_log($data, 'JSON', 2);

        if (strpos($data, 'html') && strpos($data, 'body')) {
            $this->error = $api_msg = strip_tags($data);
            preg_match("/\d+/", $api_msg, $match);
            $api_code = $match[0];
            self::update_log($api_id, $api_code, $api_msg);
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
            self::$error = $api_code . ':' . $api_msg;
           	self::update_log($api_id, $api_code, $api_msg);
            return false;
        }
        elseif (isset($data[$type]['rsp_code']) && $data[$type]['rsp_code'] == '0000')
        {
            if (isset($data[$type]['json']['code'])) {
                $api_code = $data[$type]['json']['code'];
                $api_msg = isset($data[$type]['json']['msg']) ? $data[$type]['json']['msg'] : self::MSG_SELECT_SUCCESS;
                if ($api_code != '00') {
                    self::$error = $api_code . ':' . $api_msg;
                }
            } elseif ($type == 'cardVoucher_yinshang_buycard_response') {
                $api_code = $data[$type]['rsp_code'];
                $api_msg = self::MSG_BUY_SUCCESS;
            } else {
                $api_code = $data[$type]['rsp_code'];
                $api_msg = isset($data[$type]['msg']) ? $data[$type]['msg'] : self::MSG_SELECT_SUCCESS;
            }
            self::update_log($api_id, $api_code, $api_msg);
            return $data[$type];
        }
        elseif(isset($data[$type]['code']) && $data[$type]['code'] == '0000')
        {
            //绑卡注册
            $api_code = $data[$type]['code'];
            $api_msg = isset($data[$type]['msg']) ? $data[$type]['msg'] : self::MSG_CARD_BIND;
            self::update_log($api_id, $api_code, $api_msg);
            return $data[$type];
        }
        else
        {
            $api_msg = $data[$type]['msg'];
            $api_code = isset($data[$type]['rsp_code']) ? $data[$type]['rsp_code'] : '';
            $api_code = isset($data[$type]['code']) ? $data[$type]['code'] : $api_code;
            self::$error = $api_code . ':' . $api_msg;
            self::update_log($api_id, $api_code, $api_msg);
            return false;
        }
    }

    // 替换隐藏字符串
    protected static function get_hidden($string, $start = 4, $type = '*')
    {
        $len = strlen($string);
        $string = substr($string, $len - $start);
        return sprintf("%'".$type.$len."s", $string);
    }

    protected static function add_log($post_data, $api_type = 'JSON', $api_status = 0)
    {
        if ($api_type == 'POST') {
            parse_str($post_data, $output);
            if (isset($output['type']) && $output['type'] >= 0 ) {
                self::$method = $output['method'] .'.'. $output['type'];
            } elseif (isset($output['buyType'])) {
                self::$method = $output['method'] .'.'. intval($output['buyType']);
            }

            if (isset($output['cardNo'])) {
                $post_data = str_replace($output['cardNo'], self::get_hidden($output['cardNo']), $post_data);
            }
            if (isset($output['pwd'])) {
                $post_data = str_replace($output['pwd'], self::get_hidden($output['pwd']), $post_data);
            }
            if (isset($output['mobile'])) {
                $post_data = str_replace($output['mobile'], self::get_hidden($output['mobile']), $post_data);
            }
            if (isset($output['openId'])) {
                $post_data = str_replace($output['openId'], self::get_hidden($output['openId']), $post_data);
            }                             
        } elseif ($api_type == 'JSON') {
            $json = stripslashes($post_data);
            preg_match("'\"shortUrl\":\"(.*?)\"'is", $json, $temp);
            if (isset($temp[1])) {
                $start = strripos($temp[1], '/');
                if ($start) { // 最后出现的位置
                    $pattern = substr($temp[1], $start + 1);
                    $post_data = str_replace($pattern, self::get_hidden($pattern), $post_data);
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
                    $post_data = str_replace($pattern, self::get_hidden($pattern), $post_data);
                } else { // 卡密
                    $array = explode('#', $temp[1]);
                    $pattern = array();
                    foreach ($array as $key => $value) {
                        $string = explode('|', $value);
                        foreach ($string as $k => $v) {
                            $pattern[$v] = self::get_hidden($v);
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
                    $pattern[1] = self::get_hidden($array[1]);
                } else {
                    $pattern[0] = self::get_hidden($array[0]);
                }
                if (!empty($pattern)) {
                    $post_data = str_replace($array, $pattern, $post_data);
                }
            }

            preg_match("'\"mobile\":(.*?)[}|,]'is", $json, $temp);
            if (isset($temp[1])) {
                $post_data = str_replace($temp[1], self::get_hidden($temp[1]), $post_data);
            }            
        }
        

        $data['api_method'] = self::$method;
        $data['api_data'] = $post_data;
        $data['api_type'] = $api_type;
        //$data['api_status'] = $api_status;
        return model('ApiBase')->add_api_log($data);
    }

    protected static function update_log($api_id = 0, $api_code = '', $api_msg = '') {
        return model('ApiBase')->save_api_log($api_id, $api_code, $api_msg);
    }
    // 获取错误提示
    public function getError()
    {
    	return self::$error;
    }
    public function setError($value='')
    {
        self::$error = $value;
    }

    protected static function reqSeq($reqSeq)
    {
    	if (empty($reqSeq)) {
    		$reqSeq = date('YmdHis') . rand(10000, 99999);
    	}
    	return $reqSeq;
    }

    protected static function prdtId($prdtId)
    {
        if (empty($prdtId)) {
            self::$error = self::MSG_PRDTID_NOT_EXIST;
        }
        return $prdtId;
    }
}