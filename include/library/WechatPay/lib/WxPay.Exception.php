<?php
/* 访问控制 */
defined('IN_ZFT') or die('Deny Access');
/**
 * 
 * 微信支付API异常类
 * @author widyhu
 *
 */
class WxPayException extends Exception {
	public function errorMessage()
	{
		return $this->getMessage();
	}
}
