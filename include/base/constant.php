<?php

/* 业务常量 */
define('SE_CHARSET', 'utf-8');
define('AUTH_KEY', '@#zhifutong666&)G');

/* 图片处理相关常数 */
/* 插件相关常数 */
/* 商品属性类型常数 */
/* 会员整合相关常数 */
define('ERR_USERNAME_EXISTS', 1); // 用户名已经存在
define('ERR_EMAIL_EXISTS', 2); // Email已经存在
define('ERR_INVALID_USERID', 3); // 无效的user_id
define('ERR_INVALID_USERNAME', 4); // 无效的用户名
define('ERR_INVALID_PASSWORD', 5); // 密码错误
define('ERR_INVALID_EMAIL', 6); // email错误
define('ERR_USERNAME_NOT_ALLOW', 7); // 用户名不允许注册
define('ERR_EMAIL_NOT_ALLOW', 8); // EMAIL不允许注册

/* 加入购物车失败的错误代码 */
/* 购物车商品类型 */

/* 订单状态 */
define('OS_UNCONFIRMED', 0); // 未确认（待付款）
define('OS_CONFIRMED', 1); // 已确认（发货中）
define('OS_CANCELED', 2); // 已取消
define('OS_INVALID', 3); // 无效（已关闭）
define('OS_FINISHED', 4); // 已完成
define('OS_SPLITED', 5); // 已分单
define('OS_SPLITING_PART', 6); // 部分分单

/* 支付类型 */
/* 配送状态 */
define('SS_UNSHIPPED', 0); // 未发货
define('SS_SHIPPED', 1); // 已发货
define('SS_RECEIVED', 2); // 已收货
define('SS_PREPARING', 3); // 备货中
define('SS_SHIPPED_PART', 4); // 已发货(部分商品)
define('SS_SHIPPED_ING', 5); // 发货中(处理分单)
define('OS_SHIPPED_PART', 6); // 已发货(部分商品)

/* 支付状态 */
define('PS_UNPAYED', 0); // 未付款
define('PS_PAYING', 1); // 付款中
define('PS_PAYED', 2); // 已付款

/* 综合状态 */
/* 缺货处理 */
/* 帐户明细类型 */
/* 评论状态 */
define('COMMENT_UNCHECKED', 0); // 未审核
define('COMMENT_CHECKED', 1); // 已审核或已回复(允许显示)
define('COMMENT_REPLYED', 2); // 该评论的内容属于回复

/* 红包发放的方式 */
/* 广告的类型 */
define('IMG_AD', 0); // 图片广告
define('FALSH_AD', 1); // flash广告
define('CODE_AD', 2); // 代码广告
define('TEXT_AD', 3); // 文字广告

/* 用户中心留言类型 */
define('M_MESSAGE', 0); // 留言
define('M_COMPLAINT', 1); // 投诉
define('M_ENQUIRY', 2); // 询问
define('M_CUSTOME', 3); // 售后
define('M_BUY', 4); // 求购
define('M_BUSINESS', 5); // 商家
define('M_COMMENT', 6); // 评论

/* 团购活动状态 */
/* 红包是否发送邮件 */
/* 商品活动类型 */
/* 帐号变动类型 */
/* 密码加密方法 */
define('PWD_MD5', 1);  //md5加密方式
define('PWD_PRE_SALT', 2);  //前置验证串的加密方式
define('PWD_SUF_SALT', 3);  //后置验证串的加密方式

/* 活动状态 */
define('PRE_START', 0); // 未开始
define('UNDER_WAY', 1); // 进行中
define('FINISHED', 2); // 已结束
define('SETTLED', 3); // 已处理

/* 验证码 */
define('CAPTCHA_REGISTER', 1); //注册时使用验证码
define('CAPTCHA_LOGIN', 2); //登录时使用验证码
define('CAPTCHA_COMMENT', 4); //评论时使用验证码
define('CAPTCHA_ADMIN', 8); //后台登录时使用验证码
define('CAPTCHA_LOGIN_FAIL', 16); //登录失败后显示验证码
define('CAPTCHA_MESSAGE', 32); //留言时使用验证码

/* 优惠活动的优惠范围 */
/* 优惠活动的优惠方式 */
define('FAT_GOODS', 0); // 送赠品或优惠购买
define('FAT_PRICE', 1); // 现金减免
define('FAT_DISCOUNT', 2); // 价格打折优惠

/* 减库存时机 */
/* 加密方式 */
define('ENCRYPT_ZC', 1); //zc加密方式
define('ENCRYPT_UC', 2); //uc加密方式

/* 商品类别 */
/* 积分兑换 */
/* 支付宝商家账户 */
/* 配送方式 */
define('SHIP_LIST', 'cac|city_express|ems|flat|fpd|post_express|post_mail|presswork|sf_express|sto_express|yto|zto');
