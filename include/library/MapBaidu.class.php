<?php
/**
 * Created file MapBaidu.php, 2016/08/08 下午3:28.
 * @author xiaowen
 * filename:MapBaidu.php，百度地图相关接口
 * Copyright (c) 2016 xiaowen. All rights reserved.
 */

/*
 * 百度地图相关接口
 */
class MapBaidu
{
    const PLACE_SEARCH_URL = 'http://api.map.baidu.com/place/v2/search';
    const PLACE_GEO_URL = 'http://api.map.baidu.com/geocoder/v2/';
    const PLACE_AK = '4eab3a9c2bf9079b4373860667bc7337'; //用户的访问密钥，必填项。v2之前该属性为key。
    const PLACE_SCOPE = 1; //检索结果详细程度。取值为1 或空，则返回基本信息；取值为2，返回检索POI详细信息
    const PLACE_COORD_TYPE = 3; //请求参数中坐标的类型，1(wgs84ll即GPS经纬度),2(gcj02ll即国测局经纬度坐标),3（bd09ll即百度经纬度坐标),4(bd09mc即百度米制坐标）
    const PLACE_PAGE_SIZE = 10; //范围记录数量，默认为10条记录，最大返回20条。多关键字检索时，返回的记录数为关键字个数*page_size。
    const PLACE_OUTPUT = 'json'; //输出格式为json或者xml
    const PLACE_RADIUS = 5000; //周边检索半径，单位为米

    /*
     * 获取圆形范围内的商家
     */
    public function placeLocation($keyword,$location='31.206488,121.600628'){
        $res = array();
        if(!$keyword || !$location){
            return $res;
        }

        $url = self::PLACE_SEARCH_URL.'?query='.$keyword.'&scope='.self::PLACE_SCOPE. '&output=' . self::PLACE_OUTPUT . '&ak=' . self::PLACE_AK . '&location=' . $location . '&radius=' . self::PLACE_RADIUS;

        $shops = $this->get_curl($url);
        if(isset($shops['status']) && $shops['status'] == 0){
            $res = isset($shops['results']) ? $shops['results'] : array();
        }

        return $res;
    }

    /*
     * 获取城市门店信息
     */
    public function placeCity($keyword,$city_name,$page=0)
    {
        $res = array();
        if (!$keyword || !$city_name) {
            return $res;
        }

        $url = self::PLACE_SEARCH_URL . '?query=' . $keyword . '&region='.$city_name.'&scope=' . self::PLACE_SCOPE . '&output=' . self::PLACE_OUTPUT . '&ak=' . self::PLACE_AK . '&page_size=10&page_num='.$page;

        $shops = $this->get_curl($url);

        if (isset($shops['status']) && $shops['status'] == 0) {
            $res['total'] = isset($shops['total']) ? $shops['total'] : 0;
            $res['results'] = isset($shops['results']) ? $shops['results'] : array();
        }

        return $res;
    }

    /*
     * 根据经纬度获取城市信息
     */
    public function placeInfo($lat, $lng)
    {
        $res = array();
        if(!$lat || !$lng){
            return array();
        }

        $coordtype = 'wgs84ll';//wgs84ll（ GPS经纬度）
        $location = $lat.','.$lng;//根据经纬度坐标获取地址
        $pois = 0;//是否显示指定位置周边的poi，0为不显示，1为显示。当值为1时，显示周边100米内的poi。

        $url = self::PLACE_GEO_URL.'?ak='.self::PLACE_AK.'&callbace=renderReverse&location='.$location.'&output='.self::PLACE_OUTPUT.'&coordtype='.$coordtype.'&pois='.$pois;

        $place = $this->get_curl($url);

        if(isset($place['status']) && $place['status'] == 0){
            $res = isset($place['result']['addressComponent']) ? $place['result']['addressComponent'] : array();
        }

        return $res;
    }

    /*
       * curl的get方式
       */

    function get_curl($url)
    {
        //初始化
        $ch = curl_init();
        //设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1); // 检查证书中是否设置域名
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:')); //避免data数据过长问题

        //执行并获取HTML文档内容
        $output = curl_exec($ch);
        //释放curl句柄
        curl_close($ch);
        //返回获得的数据
        return json_decode($output, true);
    }
}


 