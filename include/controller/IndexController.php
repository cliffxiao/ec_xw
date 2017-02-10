<?php

class IndexController extends CommonController {

    protected $user_id = '';

    public function __construct() {
        parent::__construct();

        $this->user_id = I('session.user_id', 0, 'intval');
    }
    /**
     * 首页信息
     */
    public function index() {
        if(getRedis()->get("indexpage")===false){
            ob_start();
            //动态获取券商列表
            //$recommend_category = model('Index')->recommend_category();
            $this->assign('page_title', "用卡啦商城欢迎你");
            //$this->assign('recommend_category', $recommend_category);
            $this->assign('url', C('SITE_URL'));
            $this->assign('url_img', C('SITE_URL').'themes/default/images/ykl-logo.png');
            $this->display('index.dwt');
            getRedis()->set("indexpage",ob_get_contents());
            ob_end_flush();
        }else{
            echo getRedis()->get("indexpage");
        }
        exit;
    }

    /*
     *  获取微信配置
     */
    public function wxconfig(){
        $url = I('post.url','','');   //获取url
        $url = str_replace('#','',$url);
        $signPackage = Wechat()->GetSignPackage(0,$url);
        //获取用户的  $openid 然后回去用户是否关注公众号
        $sql = "SELECT openid FROM " .$this->model->pre . "wechat_user WHERE ect_uid = " .$this->user_id;
        $openid_list = $this->model->query($sql);
        $openid = $openid_list?$openid_list[0]['openid']:'';
        $wx_user_info = Wechat()->getUserInfo( $openid );
        $is_gz = $wx_user_info?$wx_user_info['subscribe']:'';
        exit(json_encode(array('code' => 0,'data' => $signPackage,'is_gz' => $is_gz)));
    }
    
	/**
     * 城市列表
     */
    public function citylist() {
    	$ipInfos = model('Common')->GetIpLookup(); //baidu.com IP地址  
		if($ipInfos){
			$city = $ipInfos['city'];
		}
		//获取城市列表
    	$city_list = model('Common')->get_city_list();
    	$this->assign('city_list', $city_list);
    	$this->assign('cur_city', $city);
        $this->display('citylist.dwt');
    }
    
	/**
     * 城市选择
     */
    public function setcity() {
    	//获取城市列表
    	setcookie('curcity',$_REQUEST['cityname'],time()+60*60*24);
		header('Location: '.url());
    }
    
	/**
     * 浏览器访问提示
     */
    public function browser() {
    	$this->display('browser.dwt');
    }

    /**
     * 获取有几张卡和金额   ajax获取
     */
    public function get_card_total()
    {
    	$where = " AND user_id = '" . $this->user_id . "'";
        $where .= " AND is_used = 0 ";// 未用完
        $where .= " AND end_date >= '" . NOW_TIME . "' ";// 有效期内

        $sql = "SELECT card_id,card_sn,is_act,is_used,is_don,is_saled,balance FROM " . 
               $this->model->pre . "virtual_card as v " .
               "WHERE goods_id " .$where;

        $card_list = $this->model->query($sql);
        if( $card_list ){
            $card_total['count'] = count($card_list);
            $balance_total = 0;
            foreach ($card_list as $key => $value) {
                $balance_total += intval($value['balance']);
            }
            $card_total['amount'] = $balance_total;
            exit(json_encode(array('code' => 0,'msg' => '获取卡和金额成功','data' => $card_total)));
        }else{
            $card_total['count'] = 0;
            $card_total['amount'] = 0;
            exit(json_encode(array('code' => 0,'msg' => '获取卡和金额失败!','data' => $card_total)));
        }
    }
}
