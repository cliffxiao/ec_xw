<?php
/**
 * Created by xiaowen, 2016/11/11 10:47.
 * @author xiaowen.
 * filename:WechatModel.class.php
 * Copyright (c) 2016 xiaowen. All rights reserved.
 */

class WechatModel  extends CommonModel
{
    /*
     * 获取微信平台信息
     */
    public function getPlatform($orgid)
    {
        $where = array(
            'status'=>1,
        );
        if ($orgid == '') {
            $where['default'] = 1;
        } else {
            $where['orgid'] = $orgid;
        }
        //读取redis缓存
        $key = 'wx_platform_' . $orgid;
        $platform = json_decode(getRedis()->get($key),true);
        if ($platform == false) {
            $platform = $this->model->table('wechat')->where($where)->find();
            getRedis()->set($key,json_encode($platform));
        }
        return $platform;
    }

    /*
     * 根据用户的unionid获取用户
     */
    public function getUserByUnionid($union_id)
    {
        $where = array(
            'unionid'=>$union_id,
        );
        $field = 'uid';
        return $this->model->table('wechat_user')->field($field)->where($where)->getOne();
    }

    /*
     * 获取用户总量
     */
    public function user_count()
    {
        return $this->model->table('wechat_user')->count();
    }

    /*
     * 获取用户信息
     */
    public function wechatUsers($start,$number)
    {
        return $this->model->table('wechat_user')->field('openid')->order('uid')->limit($start.','.$number)->getCol();
    }

    /**
     * 获取配置信息
     */
    function get_weixin_config()
    {
        $sql = "SELECT token, appid, appsecret, type FROM  " . $this->pre . "wechat WHERE status = 1 and default_wx = 1";
        $wechat = $this->row($sql);
        if (empty($wechat)) {
            $wechat = array();
        }
        $config = array();
        $config['token'] = $wechat['token'];
        $config['appid'] = $wechat['appid'];
        $config['appsecret'] = $wechat['appsecret'];
        return $config;
    }

    /**
     * 获取微信账户信息
     */
    function get_weixin_user($openid)
    {
        $sql = 'SELECT openid, ect_uid FROM ' . $this->pre . "wechat_user WHERE openid = '$openid'";
        return $this->row($sql);
    }


} 