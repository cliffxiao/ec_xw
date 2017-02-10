<?php
/**
 * Created file XwModel.class.php, 2016/09/23 上午11:30.
 * @author xiaowen
 * filename:XwModel.class.php
 * Copyright (c) 2016 xiaowen. All rights reserved.
 */
/* 访问控制 */
defined('IN_ZFT') or die('Deny Access');
 class XwModel extends BaseModel
 {
    /*
     * 获取所有品牌的名称
     */
     public function getBrands()
     {
         $condition['parent_id'] = 16;
         return $this->model->table('category')->field('cat_id')->where($condition)->getCol();
     }

     /*
      * 获取指定品牌id的名称
      */
     public function getBrandName($brand_id)
     {
         $condition['cat_id'] = $brand_id;
         return $this->model->table('category')->field('cat_name')->where($condition)->getOne();
     }
 }