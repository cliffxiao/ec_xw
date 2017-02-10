<?php
/**
*  加解密
*/
class Api
{
	private $self_pub_key = '';
	private $self_priv_key = '';
    private $mem_pub_key = '';
	private $post_params = '';
	private $sign = '';
	
	function __construct($api_name = '')
	{
		if (!empty($api_name)) {
			$this->name($api_name);
		}
	}

	public function name($api_name = 'har'){
		$path = ROOT_PATH . "data/certificate";
		$this->self_pub_key = $path . "/zft.pem" ;
		$this->self_priv_key = $path . "/zft.pfx" ;
        $this->mem_pub_key = $path . "/". $api_name . ".pem" ;
		return $this;
	}

	//格式化数据
	public function data($data){
		if (is_array($data)) {
			if (isset($data['sign'])) {
				$this->sign = $data['sign'];
                    unset($data['sign']);
			}
			//ksort($data);
			$data = json_encode($data);
		}
		$this->post_params = $data;
		return $this;
	}

  	// 加密数据
	public function encrypt(){
		$pub_key = file_get_contents($this->mem_pub_key);
		openssl_public_encrypt($this->post_params, $encrypted, $pub_key);
	    return base64_encode($encrypted);  
	} 

	// 解密数据 
	public function decrypt() {
		$priv_key = file_get_contents($this->self_priv_key);
		$data = base64_decode($this->post_params);
		openssl_private_decrypt($data, $decrypted, $priv_key);
		$value = json_decode($decrypted, true);
	    return empty($value) ? $decrypted : $value;
	} 

	// 创建sign
	public function create_sign() {
		$priv_key = file_get_contents($this->self_priv_key);
	    openssl_sign($this->post_params, $sign, $priv_key,OPENSSL_ALGO_SHA1); //注册生成加密信息
	    return base64_encode($sign); //base64转码加密信息 
	}

	// 验证sign
	function check_sign($post_params){
		$pub_key = file_get_contents($this->mem_pub_key);
        $sign = base64_decode($this->sign);//base64解码加密信息
	    $res = openssl_verify($post_params, $sign, $pub_key); //验证
		if($res == true) {
			$value = json_decode($this->post_params, true);
			return empty($value) ? $this->post_params : $value;
		}
	}	
}