<?php
/* Smarty version 3.1.30, created on 2016-12-23 15:56:09
  from "D:\wamp\www\personal_business\se.framework\template\index.dwt" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_585cd8999a1010_32454568',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '7de63c873f73eea97b680439ebb86560c171b9cb' => 
    array (
      0 => 'D:\\wamp\\www\\personal_business\\se.framework\\template\\index.dwt',
      1 => 1482479760,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_585cd8999a1010_32454568 (Smarty_Internal_Template $_smarty_tpl) {
if (!is_callable('smarty_modifier_date_format')) require_once 'D:\\wamp\\www\\personal_business\\se.framework\\include\\base\\smarty3\\plugins\\modifier.date_format.php';
?>
<!-- #BeginLibraryItem "/library/page_header.lbi" --><!-- #EndLibraryItem -->
<div class="con home" id="container">
    <!-- 首页List[2]导航 -->
    <div class="top-index">
        <ul>
            <li class="top-index-l ui-border-r ui-border-r-ffffff" onclick="couponPocketButtonTapped()">
                <i>
                    <img src="/themes/default/images/coupon_top.png">
                </i>
                <p class="text-center">
                    卡包
                </p>
            </li>
            <li class="top-index-r" onclick="payButtonTapped()">
                <i>
                    <img src="/themes/default/images/pay_top.png">
                </i>
                <p class="text-center">
                    付款
                </p>
            </li>
        </ul>
    </div>
    <!--首页List[4]导航-->
    <!--
    <nav class="container-fluid">
        <ul class="row ect-row-nav">
            <li class="col-sm-6 col-xs-6 ui-border-r ui-border-r-dde4e6" onclick="donateButtonTapped()">
                <i>
                    <img src="/themes/default/images/nav_1.png">
                    <p class="text-center">
                        转赠
                    </p>
                </i>
            </li>
            <li class="col-sm-6 col-xs-6" onclick="saleCardButtonTapped()">
                <i>
                    <img src="/themes/default/images/nav_2.png">
                    <p class="text-center">
                        转卖
                    </p>
                </i>
            </li>
        </ul>
    </nav>
    -->
    <!-- 间隔 -->
    <div class="indexseperator ui-border-tb ui-border-tb-dde4e6">
    </div>
    <!-- 活动入口 -->
    <div class="index-activity clearfix">
        <div class="activity-left" onclick="voucherButtonTapped()">
            <img src="/themes/default/images/activity_1.png">
        </div>
        <div class="activity-right" onclick="groupBuyButtonTapped()">
            <img src="/themes/default/images/activity_2.png">
        </div>
    </div>
    <!-- 间隔 -->
    <div class="indexseperator ui-border-tb ui-border-tb-dde4e6">
    </div>
    <!-- 推荐商户 -->
    <nav class="container-fluid" date="<?php echo smarty_modifier_date_format('1482479253','Y-m-d');?>
">
        <?php if (isset($_smarty_tpl->tpl_vars['recommend_category']->value)) {?>
            <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['recommend_category']->value, 'category', false, 'key');
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['key']->value => $_smarty_tpl->tpl_vars['category']->value) {
?>
            <?php if ($_smarty_tpl->tpl_vars['key']->value == 0 || ($_smarty_tpl->tpl_vars['key']->value%3) == 0) {?>
            <ul class="row rec-row-nav ui-border-b ui-border-b-dde4e6">
            <?php }?>
                <li class="col-sm-4 col-xs-4 ui-border-r ui-border-r-dde4e6" onclick="brandButtonTapped(<?php echo $_smarty_tpl->tpl_vars['category']->value['cat_id'];?>
,<?php echo $_smarty_tpl->tpl_vars['category']->value['is_show'];?>
,(<?php echo $_smarty_tpl->tpl_vars['key']->value;?>
+1))">
                    <i>
                        <img src="<?php echo $_smarty_tpl->tpl_vars['category']->value['cat_image'];?>
">
                        <p class="text-center">
                            <?php echo $_smarty_tpl->tpl_vars['category']->value['cat_name'];?>

                        </p>
                    </i>
                </li>
            <?php if (($_smarty_tpl->tpl_vars['key']->value%3) == 2) {?>
            </ul>
            <?php }?>
            <?php
}
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl);
?>

        <?php } else { ?>
            敬请期待.
        <?php }?>
    </nav>
    <div style="padding-bottom:4em;background-color:#f9f9f9">
    </div>
</div>
<!-- ToolBar导航栏 -->
<footer>
    <nav class="ect-nav ui-border-t ui-border-t-dde4e6">
        <!-- #BeginLibraryItem "/library/page_menu.lbi" --><!-- #EndLibraryItem -->
    </nav>
</footer>
<!-- #BeginLibraryItem "/library/page_footer.lbi" --><!-- #EndLibraryItem -->
<?php echo '<script'; ?>
 language="javascript">
    //卡包
    var couponPocketButtonTapped = function () {
        umeng.Index.index_clickwallet();
        window.location.href= 'couponorder/index';
    };
    //付款
    var payButtonTapped = function () {
        umeng.Index.index_clickpay();
        window.location.href= 'couponorder/couponorderlist/status/3';
    };
    //代金券
    var voucherButtonTapped = function () {
        window.location.href= 'voucher/index';
    };
    //团购
    var groupBuyButtonTapped = function () {
        window.location.href= 'groupbuy/index';
    };
    //商户
    var brandButtonTapped = function (index,is_show,No) {
        if( is_show == 0 ){
            indicator.show('敬请期待！');
            return false;
        }
        umeng.Index.index_clickbrand(No);
        window.location.href= 'coupon/info/id/' + index;
    };
    //导航后退处理
    pushHistory();
    window.addEventListener('load', function() {
        setTimeout(function() {
            window.addEventListener('popstate', function() {
                WeixinJSBridge.call('closeWindow');
            });
        }, 0);
    });
    function pushHistory() {
        var state = {
            title: "title",
            url: "#"
        };
        window.history.pushState(state, "title", "#");
    };
    //分享
    function wxReady() {
        var  title = '用卡啦商城';
        //分享到朋友圈
        wx.onMenuShareTimeline({
            title: title, // 分享标题
            link: '<?php echo $_smarty_tpl->tpl_vars['url']->value;?>
', // 分享链接
            imgUrl: '<?php echo $_smarty_tpl->tpl_vars['url_img']->value;?>
',// 分享图标
            success: function () {
                // 用户确认分享后执行的回调函数
            },
            cancel: function () {
                // 用户取消分享后执行的回调函数
            }
        });
        //分享到朋友
        wx.onMenuShareAppMessage({
            title: title, // 分享标题
            desc: '用卡啦商城欢迎你',  // 分享描述
            link: '<?php echo $_smarty_tpl->tpl_vars['url']->value;?>
',   // 分享链接
            imgUrl: '<?php echo $_smarty_tpl->tpl_vars['url_img']->value;?>
',// 分享图标
            type: '', // 分享类型,music、video或link，不填默认为link
            dataUrl: '', // 如果type是music或video，则要提供数据链接，默认为空
            success: function () {
                // 用户确认分享后执行的回调函数
            },
            cancel: function () {
                // 用户取消分享后执行的回调函数
            }
        });
    }
<?php echo '</script'; ?>
>
</body>
</html><?php }
}
