<?php
/**
 * Created file LocationModel.php, 2016/12/23 上午11:28.
 * @author xiaowen
 * filename:LocationModel.php
 * Copyright (c) 2016 xiaowen. All rights reserved.
 */ 
 class LocationModel extends CommonModel
{
    /**
     * 获取城市列表
     */
    function get_city_list()
    {
        $res = $this->query('select * from ' . $this->pre . 'region');
        $res_province = array();
        $flag_i = 0;
        $flag_j = 0;
        foreach ($res as $key => $val) {
            if ($val['parent_id'] == 1) {
                $res_province[$flag_i]['region_id'] = $val['region_id'];
                $res_province[$flag_i]['region_name'] = $val['region_name'];
                $flag_i++;
            }
        }
        foreach ($res as $key => $val) {
            foreach ($res_province as $pkey => $pval) {
                if ($val['parent_id'] == $pval['region_id']) {
                    $res_province[$pkey]['region_city'][$flag_j]['region_id'] = $val['region_id'];
                    $res_province[$pkey]['region_city'][$flag_j]['region_name'] = $val['region_name'];
                    $flag_j++;
                }
            }
        }
        return $res_province;
    }

    //获取region的各级地区列表
    function get_region($region_type)
    {
        $condition['region_type'] = $region_type;
        $data = $this->model->table('region')->where($condition)->select();
        $zone = array();
        foreach ($data as $value) {
            $tmp = array();
            $tmp['id'] = $value['region_id'];
            $tmp['value'] = urlencode($value['region_name']);
            $tmp['parentId'] = $value['parent_id'];
            $zone[$value['parent_id']][] = $tmp;
        }

        return $zone;
    }

    /**
     * 获取当前定位城市
     */
    function GetIpLookup($ip = '')
    {
        if (empty($ip)) {
            $ip = real_ip();
        }
        $res = @file_get_contents('http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=js&ip=' . $ip);
        if (empty($res)) {
            return false;
        }
        $jsonMatches = array();
        preg_match('#\{.+?\}#', $res, $jsonMatches);
        if (!isset($jsonMatches[0])) {
            return false;
        }
        $json = json_decode($jsonMatches[0], true);
        if (isset($json['ret']) && $json['ret'] == 1) {
            $json['ip'] = $ip;
            unset($json['ret']);
        } else {
            return false;
        }
        return $json;
    }
}