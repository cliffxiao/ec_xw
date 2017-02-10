<?php
/* 访问控制 */
defined('IN_ZFT') or die('Deny Access');

/**
 * 开联通支付类
 * @author moon 2016.10.17
 */
class Klpay
{
    private $merchantId;//支付商户号
    private $key;//支付key
    private $params;//各项配置参数
	private $payurl,$queryurl,$refundurl;//付款、查询、退款URL

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->merchantId=C("DB.KLT_MERCHANTID");
        $this->key=C("DB.KLT_KEY");
		$this->payurl=C("DB.KLT_PAY_URL");
		$this->queryurl=C("DB.KLT_QUERY_URL");
		$this->refundurl=C("DB.KLT_REFUND_URL");
    }
    /**
     * 下单函数
     * @param pickupUrl页面回调地址
     * @param receiveUrl异步回调地址
     * @param orderNo订单号
     * @param orderAmount订单金额 以分为单位
     * @param orderDatetime下单日期 时间戳
     * @param orderExpireDatetime订单过期时间 单位为分
     * @param productName商品名称
     * @param paytypes 支付类型  1新购一手卡  2二手卡  3团购支付
     * @param attach附加字段
     * @param payType支付类型0-代表全部，即显示该商户开通的所有支付方式 1-个人网银支付借记卡 4-企业网银 11-信用卡支付 20-微信扫码支付 22-支付宝扫码支付 31-银联认证支付
     * @param issuerId支付机构代码
     * @return array下单字段
     * @author moon 2016.10.17
     */
    public function makeOrder($pickupUrl,$receiveUrl,$orderNo,$orderAmount,$orderDatetime,$orderExpireDatetime,$productName,$paytypes,$attach,$payType=36,$issuerId='wechat'){
        $this->params=array(
            'inputCharset'=>1,//utf8编码
            'pickupUrl'=>trim($pickupUrl),//付款成功页面回调地址
            'receiveUrl'=>trim($receiveUrl),//付款成功异步回调地址
            'version'=>'v1.0',//接口版本号
            'language'=>1,//语言为简体中文
            'signType'=>1,//签名类型 请求和接收通知都是用MD5签名
            'merchantId'=>$this->merchantId,//商户号
            'orderNo'=>trim($orderNo),//订单号
            'orderAmount'=>trim($orderAmount),//订单金额
            'orderCurrency'=>156,//比重类型为人民币
            'orderDatetime'=>date("YmdHis",$orderDatetime),//下单日期
            //'orderExpireDatetime'=>$orderExpireDatetime,//订单过期时间
            'orderExpireDatetime'=>0,//订单过期时间
            'productName'=>trim($productName),//商品名称
            'ext1'=>trim($paytypes),//附件字段1 表示支付类型 如团购
            'ext2'=>trim($attach),//attach附加字段
			'payType'=>$payType,//支付类型
            'issuerId'=>trim($issuerId)//支付机构号
        );
        $signMsg=$this->sign();
		//$signMsg=$this->newSign();
        //记录日志
        $log=array();
        $log['api_type']="POST";
        $log['api_data']=json_encode(array_merge($this->params,array('signMsg'=>$signMsg)));
        //$log['api_status']=1;
        $log['api_method']="Klpay.makeorder";
        $log['api_code']=0;
        $log['api_msg']="支付请求";
        $log['api_key_id']=$orderNo;
		$log['api_key_val']=2;
        //$log['api_cardid']=0;
        $log['admin_name']='0';
        model('ApiBase')->add_api_log($log);
        return array('error'=>1,'des'=>'下单成功','order'=>array_merge($this->params,array('signMsg'=>$signMsg)),'payurl'=>$this->payurl);
    }
    /**
     * 支付结果返回
     * @param from klt
     * @return state and data    state=1未支付成功
     * @author moon 2016.10.11
     */
    public function payResult(){
        $this->params=array(
            'merchantId'=>$this->merchantId,
            'version'=>isset($_REQUEST['version'])?$_REQUEST['version']:'',
            'language'=>isset($_REQUEST['language'])?$_REQUEST['language']:'',
            'signType'=>isset($_REQUEST['signType'])?$_REQUEST['signType']:'',
            'payType'=>isset($_REQUEST['payType'])?$_REQUEST['payType']:'',
            'issuerId'=>isset($_REQUEST['issuerId'])?$_REQUEST['issuerId']:'',
            'mchtOrderId'=>isset($_REQUEST['mchtOrderId'])?$_REQUEST['mchtOrderId']:'',//开联通订单号
            'orderNo'=>isset($_REQUEST['orderNo'])?$_REQUEST['orderNo']:'',
            'orderDatetime'=>isset($_REQUEST['orderDatetime'])?$_REQUEST['orderDatetime']:'',
            'orderAmount'=>isset($_REQUEST['orderAmount'])?$_REQUEST['orderAmount']:'',
            'payDatetime'=>isset($_REQUEST['payDatetime'])?$_REQUEST['payDatetime']:'',
            'ext1'=>isset($_REQUEST['ext1'])?$_REQUEST['ext1']:'',
            'ext2'=>isset($_REQUEST['ext2'])?$_REQUEST['ext2']:'',
            'payResult'=>isset($_REQUEST['payResult'])?$_REQUEST['payResult']:''
        );
        $signMsg=isset($_REQUEST['signMsg'])?$_REQUEST['signMsg']:'';
        //str_replace(" ","+",$signMsg);
		if($this->params['signType']==0){//md5验证返回签名
			if($this->sign()!=$signMsg){
				return array('error'=>-1);//签名错误
			}
		}else{//证书验证返回签名
			if(!$this->Certsign($signMsg)){
				return array('error'=>-1);//签名错误
			}
		}
		//记录日志  验证签名后记录日志  防止针对该URL的攻击
        $log=array();
        $log['api_type']="POST";
        $log['api_data']=json_encode(array_merge($this->params,array('signMsg'=>$signMsg)));
        //$log['api_status']=2;
        $log['api_method']="Klpay.notify";
        $log['api_code']=200;
        $log['api_msg']="支付结果";
		$log['api_key_id']=$this->params['orderNo'];
		$log['api_key_val']=2;
        //$log['api_cardid']=0;
        $log['admin_name']='0';
        model('ApiBase')->add_api_log($log);
        return array('error'=>1,'state'=>$_REQUEST['payResult'],'out_trade_no'=>$_REQUEST['orderNo'],'paytype'=>$_REQUEST['ext1'],'attach'=>$_REQUEST['ext2'],'openid'=>$_REQUEST['ext1'],'data'=>$this->params);
    }
    /**
     * 单笔订单查询
     * @param orderNo商户订单号
     * @param orderDatetime订单提交时间 时间戳
     * @param queryDatetime查询提交时间 时间戳
     * @return array用户组合get或者post
     * @author moon 2016.10.17
     */
    public function orderQuery($orderNo,$orderDatetime,$queryDatetime){
		$url=$this->queryurl;
        $this->params=array(
            'merchantId'=>$this->merchantId,
            'version'=>'v1.5',
            'signType'=>1,
            'orderNo'=>$orderNo,
            'orderDatetime'=>date('YmdHis',$orderDatetime),
            'queryDatetime'=>date('YmdHis',$queryDatetime)
        );
        $signMsg=$this->sign();
		//$signMsg=$this->newSign();
        //记录日志
        $log=array();
        $log['api_type']="POST";
        $log['api_data']=json_encode(array_merge($this->params,array('signMsg'=>$signMsg)));
        //$log['api_status']=1;
        $log['api_method']="Klpay.query";
        $log['api_code']=0;
        $log['api_msg']="支付结果查询";
		$log['api_key_id']=$orderNo;
		$log['api_key_val']=2;
        //$log['api_cardid']=0;
        $log['admin_name']='0';
        model('ApiBase')->add_api_log($log);
        $data2=$this->post($url,array_merge($this->params,array('signMsg'=>$signMsg)));
        if(!$data2){
            return array('error'=>-2);//未返回数据
        }
		parse_str($data2,$data);
        //$data=$this->query2array($data2);
        $_REQUEST=array_merge($_REQUEST,$data);
        return $this->payResult();
    }
    /**
     * 单笔订单退款申请
     * @param orderNo商户订单号
     * @param refundAmount退款金额
     * @param mchtRefundOrderNo商户退款订单号
     * @param orderDatetime商户订单提交时间  时间戳
     * @return state true or false
     */
    public function refund($orderNo,$refundAmount,$mchtRefundOrderNo,$orderDatetime){
		$url=$this->refundurl;
        $this->params=array(
			'version'=>'v2.3',
			'signType'=>1,
            'merchantId'=>$this->merchantId,
            'orderNo'=>$orderNo,
			'refundAmount'=>$refundAmount,
			'mchtRefundOrderNo'=>$mchtRefundOrderNo,
            'orderDatetime'=>date('YmdHis',$orderDatetime)
        );
        $signMsg=$this->sign();
		//$signMsg=$this->newSign();
        //记录日志
        $log=array();
        $log['api_type']="POST";
        $log['api_data']=json_encode(array_merge($this->params,array('signMsg'=>$signMsg)));
        //$log['api_status']=1;
        $log['api_method']="Klpay.refund";
        $log['api_code']=0;
        $log['api_msg']="退款申请";
		$log['api_key_id']=$orderNo;
		$log['api_key_val']=2;
        //$log['api_cardid']=0;
        $log['admin_name']='0';
        model('ApiBase')->add_api_log($log);
        $data2=$this->post($url,array_merge($this->params,array('signMsg'=>$signMsg)));
        if(!$data2){
            return array('error'=>-1);//未返回数据
        }
		//parse_str($data2,$data);
        $data=$this->query2array($data2);
       /* $this->params=array(
            'merchantId'=>$this->merchantId,
            'version'=>'v2.3',
            'signType'=>isset($data['signType'])?$data['signType']:'',
            'orderNo'=>isset($data['orderNo'])?$data['orderNo']:'',
            'orderAmount'=>isset($data['orderAmount'])?$data['orderAmount']:'',
            'orderDatetime'=>isset($data['orderDatetime'])?$data['orderDatetime']:'',
            'refundAmount'=>isset($data['refundAmount'])?$data['refundAmount']:'',
            'refundResult'=>isset($data['refundResult'])?$data['refundResult']:'',
            'mchtRefundOrderNo'=>isset($data['mchtRefundOrderNo'])?$data['mchtRefundOrderNo']:'',
            'refundDatetime'=>isset($data['refundDatetime'])?$data['refundDatetime']:''
        );*/
        $this->params=$data;
        $signMsg=isset($data['signMsg'])?$data['signMsg']:'';
        //记录日志
        $log=array();
        $log['api_type']="CURL";
        $log['api_data']=json_encode(array_merge($this->params,array('signMsg'=>$signMsg)));
        //$log['api_status']=2;
        $log['api_method']="Klpay.refund";
        $log['api_code']=200;
        $log['api_msg']="退款结果";
		$log['api_key_id']=$orderNo;
		$log['api_key_val']=2;
        //$log['api_cardid']=0;
        $log['admin_name']='0';
        model('ApiBase')->add_api_log($log);
        /*if($this->sign()!=$signMsg){
            return array('error'=>-2);//签名错误
        }*/
        if(!$this->Certsign($signMsg)){
            return array('error'=>-2);//签名错误
        }
        return array('error'=>1,'result'=>$data['refundResult']);
    }
    /**
     * 查询字符串转为数组
     * @param str
     * @return array
     */
    private function query2array($str){
        if(!$str)
            return null;
        $info=explode("&",$str);
        $a=array();
        foreach($info as $item){
            $i=explode("=",$item);
            if(isset($i[0]) && isset($i[1]))
                $a[$i[0]]=$i[1];
        }
        return $a;
    }
    /**
     * post方法
     */
    private function post($url,$data,$second=30){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);//严格校验
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        $postdata="";
        foreach($data as $k=>$v){
            $postdata.=("&".$k."=".$v);
        }
        $postdata=substr($postdata,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        //运行curl
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
    /**
     * 签名方法
     * @param 类实例
     * @return sign字符串
     * @author moon 2016.10.17
     */
    private function sign(){
        $sign_str="";
        foreach($this->params as $k=>$v){
            $v=trim($v);
            if(strlen($v)>0 && $k!="signMsg"){
                $sign_str.=("&".$k."=".$v);
            }
        }
        $sign_str=substr($sign_str,1);
        $sign_str.=("&key=".$this->key);
        return strtoupper(md5($sign_str));
    }
	/**
     * 证书签名方法
     * @param 类实例
     * @return sign字符串
     * @author moon 2016.10.17
     */
    private function newSign(){
        $sign_str="";
        foreach($this->params as $k=>$v){
            $v=trim($v);
            if(strlen($v)>0 && $k!="signMsg"){
                $sign_str.=("&".$k."=".$v);
            }
        }
        $sign_str=substr($sign_str,1);
        $sign_str.=("&key=".$this->key);
        //return strtoupper(md5($sign_str));
		$publickeycontent = file_get_contents(BASE_PATH."vendor/Klpay/yongkala-rsa.pfx");//读取私钥文件
		openssl_pkcs12_read($publickeycontent,$certs,"yongkala4pay");
		openssl_sign($sign_str,$forsig,$certs['pkey']);
		return base64_encode($forsig);
    }
    /**
     * 证书验证签名
     * @param signMsg签名
     * @return true or false
     */
    private function Certsign($signMsg){
        $sign_str="";
        foreach($this->params as $k=>$v){
            $v=trim($v);
            if(strlen($v)>0 && $k!="signMsg"){
                $sign_str.=("&".$k."=".$v);
            }
        }
        $sign_str=substr($sign_str,1);
        $publickeycontent = file_get_contents(BASE_PATH."vendor/Klpay/CSMTCert.cer");//读取公钥文件
        $sig = base64_decode($signMsg);
        $res = openssl_pkey_get_public($publickeycontent);
        //echo  "验签函数返回值"; var_dump(openssl_verify($sign_str, $sig, $res)); echo "\n";
        //echo  "验签函数返回值"; var_dump(openssl_verify("merchantId=102900161024001&version=v2.3&signType=1&orderNo=1477373672&orderAmount=1&orderDatetime=20161025133432&refundAmount=1&refundResult=20&mchtRefundOrderNo=1477373672&refundDatetime=20161025133658", base64_decode("ugzi8VS UOg4Mkc/EFTwhWY3rI6a p9 Y6iW84Zf0z77Ektg1glhdVUHAgfuIIBWg1WBDSUNGVA3u KBZEWvvrJf7jyGx2UlsjhJzyZejSURDZfmTl8lx0ljdgSxBxmtX0g62DLh0ORRTll5dD4kjSuI8xV3N/otyyUFAjpgxDM0qvEKZQMWBwB3PdeE7ZIkfoAYuRlGdxbjB3bQIqUY9AgmzDKUpNeAVUbrDjhWx wkVd1wDdM/NiHFXxrK9OHcVhws3JDomlkjCvgh1HD/DpgoKovCn42yYeL2ZCRyAHEGdO83sKuiziCq5Btzgovva2ipUZM5yecz9eJAlCegmw=="), $res)); echo "\n";
        return openssl_verify($sign_str, $sig, $res) === 1;

        /*$publickeycontent = file_get_contents(BASE_PATH."vendor/Klpay/publickey-dev.txt");//读取公钥文件
        //echo "<br>".$content;
        $publickeyarray = explode(PHP_EOL, $publickeycontent);
        $publickey = explode('=',$publickeyarray[0]);
        //去掉publickey[1]首尾可能的空字符
        $publickey[1]=trim($publickey[1]);
        $modulus = explode('=',$publickeyarray[1]);
        //去掉modulus[1]首尾可能的空字符
        $modulus[1]=trim($modulus[1]);
        $keylength = 2048;

        return rsa_verify($sign_str,$signMsg, $publickey[1], $modulus[1], $keylength,"sha1");*/
    }

    /**
     * 将xml转为array
     * @param string $xml
     * @return -1
     */
    public function FromXml($xml)
    {
        if(!$xml){
            return -1;
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $values;
    }
}