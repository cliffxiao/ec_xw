<?php
/**
 * Created file XwController.class.php, 2016/09/23 上午11:27.
 * @author xiaowen
 * filename:xw.class.php    个人本地环境使用
 * Copyright (c) 2016 xiaowen. All rights reserved.
 */
/* 访问控制 */
defined('IN_ZFT') or die('Deny Access');
class XwController extends CommonController
{
    /*
     * 导入我们所有品牌对应的全国门店信息
     */
    public function lead_nation_stores()
    {
        set_time_limit(0); //不限制执行时间
        //获取品牌名称
        $brands = model('Xw')->getBrands();
        foreach($brands as $cat_id){
            $this->lead_brand_stores($cat_id,true);
        }
        writeLog('+++++++++++++++++本次所有门店信息记录完成++++++++++++++++');
        exit('ok');
    }

    /*
     * 导入指定品牌信息
     */
    public function lead_stores()
    {
        set_time_limit(0); //不限制执行时间
        $brands = array(
            18 => '沃尔玛',
            28 => '家乐福',
            30 => '京东',
            32 => '中石化',
            34 => 'GAP',
            36 => '携程',
            39 => '唯品会',
            41 => '国美',
            43 => '卓越亚马逊',
//            45 => '当当',
            47 => '蜘蛛网',
            49 => '天天果园',
            51 => '百胜',
            53 => '星巴克'
        );
        foreach($brands as $brand_id=>$brand_name){
            $this->lead_brand_stores($brand_id,true,$brand_name);
            sleep(3);
        }
        writeLog('+++++++++++++++++本次所有门店信息记录完成++++++++++++++++');
        exit('ok');
    }

    /*
     * 导入指定品牌id，cat_id的全国门店信息
     */
    public function lead_brand_stores($brand_id = 0,$return = false, $brand_name = '')
    {
        $brand_id = $brand_id ? $brand_id :I('get.catid',0,'intval');
        if($brand_id == 0){
            if($return){
                writeLog($brand_id . '参数错误');
                return false;
            }else{
                exit('参数错误');
            }
        }

        if($brand_name == ''){
            $brand_name = model('Xw')->getBrandName($brand_id);
            if ($brand_name == false) {
                if ($return) {
                    writeLog($brand_id . '品牌不存在');
                    return false;
                } else {
                    exit('不存在该品牌');
                }
            }
        }


        $map = new MapBaidu();
        //1级区域：中国
        $nation = '中国';
        $stores_province = $map->placeCity($brand_name,$nation);
        if(!empty($stores_province['results'])){
            foreach ($stores_province['results'] as $province) {
                //省级市（北上广深，天津，重庆）,香港 澳门
                if(strpos($province['name'],'市') !== false || strpos($province['name'], '香港') !==false || strpos($province['name'], '香港') !== false){
                    //市级单位去除汉字市
                    if (strpos($province['name'], '市') !== false) {
                        $city = substr($province['name'], 0, strlen($province['name']) - 3);
                    }
                    //香港 澳门特别处理
                    if(strpos($province['name'], '香港') !== false){
                        $city = '香港';
                    }
                    if (strpos($province['name'], '澳门') !== false) {
                        $city = '澳门';
                    }

                    $cityid = model('Trade')->getCityByName($city); //市级
                    if($cityid){
                        $this->recordCityStores($brand_id, $brand_name, $cityid, $province['name']);
                    }else{
                        writeLog($province['name'] . '--' . $city . '城市在region表中不存在');
                    }
                }else{
                    //省份
                    $stores_city  = $map->placeCity($brand_name, $province['name']);
                    if(!empty($stores_city['results'])){
                        foreach ($stores_city['results'] as $city) {
                            //市级单位去除汉字市
                            if (strpos($city['name'], '市') !== false) {
                                $city['name'] = substr($city['name'], 0, strlen($city['name']) - 3);
                            }

                            $cityid = model('Trade')->getCityByName($city['name']); //市级
                            if($cityid){
                                $this->recordCityStores($brand_id, $brand_name, $cityid, $city['name']);
                            }else{
                                writeLog($province['name'] . '--' . $city['name'] . '城市在region表中不存在');
                            }
                        }
                    }
                }
                writeLog($province['name'].'--'.$brand_name.'的门店信息记录完成');
            }
        }
        writeLog('+++++++++++++++++'.$brand_name . '的门店信息记录完成++++++++++++++++');
    }

    //处理城市门店信息
    private function recordCityStores($cat_id, $brand_name,$city_id,$city_name)
    {
        $map = new MapBaidu();
        $stores= $map->placeCity($brand_name, $city_name);

        if($stores['total'] > 10){
            $page_total = $stores['total'] % 10;//从0开始
            for ($i = 0;$i <= $page_total;$i++) {
                $this->recordStores($cat_id, $city_id, $map->placeCity($brand_name, $city_name, $i));
            }
        }else{
            if($stores['total'] > 0){
                $this->recordStores($cat_id,$city_id,$stores);
            }
        }
    }

    /*
     * 记录门店信息
     */
    private function recordStores($cat_id, $city_id, $stores)
    {
        if(!empty($stores['results'])){
            foreach ($stores['results'] as $store) {
                $data = array(
                    'cat_id' => $cat_id,
                    'city_id' => $city_id,
                    'uid' => isset($store['uid']) ? $store['uid'] : '',
                    'store_name' => isset($store['name']) ? $store['name'] : '',
                    'lat' => isset($store['location']['lat']) ? $store['location']['lat'] : 0,
                    'lng' => isset($store['location']['lng']) ? $store['location']['lng'] : 0,
                    'address' => isset($store['address']) ? $store['address'] : '',
                    'street_id' => isset($store['street_id']) ? $store['street_id'] : '',
                    'telephone' => isset($store['telephone']) ? $store['telephone'] : '',
                    'addtime' => time(),
                );
                model('Trade')->recordStores($data);
            }
        }
    }

}
 