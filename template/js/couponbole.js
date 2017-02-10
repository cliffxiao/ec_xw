/* 搜索验证 */
function check(Id){
    var strings = document.getElementById(Id).value;
    if(strings.replace(/(^\s*)|(\s*$)/g, "").length == 0){
        return false;
    }
    return true;
}
/*商品分类页*/
$(function($) {
    $(".ect-category-all ul li .panel-body").click(function(){
        if(!$(this).parent("li").hasClass("category-select")){
            $(this).parent("li").addClass("category-select");
        }else{
            $(this).parent("li").removeClass("category-select");
        }
    });
});
/*商品列表页面点击显示/隐藏下拉*/
$(".category-list").click(function(){
    if($(".category-list-show")){
        if($(".ect-wrapper").hasClass("select")){
            $(".ect-wrapper").removeClass("select");
        }else{
            $(".ect-wrapper").addClass("select");
        }
    }
});
/*商品列表页面点击隐藏下拉*/
$(".ect-pro-list,.ect-wrapper div a.select").click(function(){
    if($(".ect-wrapper").hasClass("select")){
        $(".ect-wrapper").removeClass("select");
    }
});
/*点击下拉菜单*/
function openMune(){
    if($(".ect-nav").is(":visible")){
        $(".ect-nav").hide();
    }else{
        $(".ect-nav").show();
    }
}
/**
 * jquery Begin
 * @returns {undefined}
 */
$(function(){

    /**绑定手机的所有的js写在下面**/
    //获取验证码
    $('.btn-get-code').click(function () {
        if(navigator.userAgent.indexOf('Android') > -1 || navigator.userAgent.indexOf('Adr') > -1){
            //获取焦点
            $('#aaa').focus();
        }
        var tc = $(this).hasClass('btn-tc')?1:0;
        var phone = $('input[name=phone]').val();
		var hereurl=$(this).val();
        if( validatemobile(phone,tc) ){
            if( $(this).hasClass("btn-disabled") ) return false;
            $.ajax({
                url: hereurl,
                type: 'POST',
                data:{phone:phone},
                dataType: 'JSON',
                success: function(data) {
                    if(data.code == 1){
                        if( tc ){
                            $('.phone-err-tip').html(data.msg);
                        }else{
                            indicator.show(data.msg);
                        }
                    }else {
                        timer(60);
                        setCookie('phone',phone,'s60');
                        setCookie('dead_time',Date.parse(new Date()),'s60');
                    }
                }
            });
        }
    });
    $('.btn-bind-phone').click(function () {
        var tc = $(this).hasClass('btn-tc')?1:0;
        var phone = $('input[name=phone]').val();
        var msg_code = $('input[name=msg_code]').val();
        var hereurl = $(this).val();
        if( validatemobile(phone,tc) ){
            if( msg_code == '' || msg_code == null){
                if( tc ){
                    $('.validate-err-tip').html('请先填写您的短信验证码!');
                }else {
                    indicator.show('请先填写您的短信验证码');
                }
            }else if( msg_code.length != 6 ){
                if( tc ){
                    $('.validate-err-tip').html('请填写6位短信验证码!');
                }else {
                    indicator.show('请填写6位短信验证码');
                }
            }else{
                $('.validate-err-tip').html('');
                //绑定手机号
                $.ajax({
                    url: hereurl,
                    type: 'POST',
                    data:{phone:phone,msg_code:msg_code},
                    dataType: 'JSON',
                    success: function(data) {
                        if(data.code == 0){
                            backUrl();//使用回调
                        }else{
                            if( tc ){
                                $('.validate-err-tip').html(data.msg);
                            }else {
                                indicator.show(data.msg);
                            }
                        }
                    }
                });
            }
        }
    });
});
/*
* 设置cookie
*/
function setCookie(name,value,time)
{
    var strsec = getsec(time);
    var exp = new Date();
    exp.setTime(exp.getTime() + strsec*1);
    document.cookie = name + "="+ escape (value) + ";expires=" + exp.toGMTString();
}
/*
* 获取cookie
*/
function getCookie(objName){//获取指定名称的cookie的值
    var arrStr = document.cookie.split("; ");
    for(var i = 0;i < arrStr.length;i ++){
        var temp = arrStr[i].split("=");
        if(temp[0] == objName) return unescape(temp[1]);
    }
}
/*设置cookie的秒*/
function getsec(str) {
    var str1 = str.substring(1, str.length) * 1;
    var str2 = str.substring(0, 1);
    if (str2 == "s") {
        return str1 * 1000;
    } else if (str2 == "h") {
        return str1 * 60 * 60 * 1000;
    } else if (str2 == "d") {
        return str1 * 24 * 60 * 60 * 1000;
    }
}
/*
*   定时器 绑定用户手机
*/
function timer(wait){
    $('.btn-get-code').hasClass("btn-disabled")?'':$('.btn-get-code').addClass("btn-disabled");
    if(wait == 0){
        $('.btn-get-code').removeClass("btn-disabled").html('获取验证码');
    }else{
        $('.btn-get-code').html('重发('+wait+'秒)');
        wait--;
        setTimeout(function () {
            timer(wait);
        },1000);
    }
}
/*js验证手机号码*/
function validatemobile(mobile,tc){
    if( tc ){     //如果是弹窗
        if(mobile.length==0) {
            $('.phone-err-tip').html('请先填写您的手机号码!');
            return false;
        }else if(mobile.length!=11) {
            $('.phone-err-tip').html('请填写11位手机号码!');
            return false;
        }else if(!(/^1[3|4|5|7|8]\d{9}$/.test(mobile))){
            $('.phone-err-tip').html('手机号码格式错误!');
            return false;
        }
        $('.phone-err-tip').html('');
    }else{
        if(mobile.length==0) {
            indicator.show('请先填写您的手机号码！');
            return false;
        }else if(mobile.length!=11) {
            indicator.show('请填写11位手机号码！');
            return false;
        }else if(!(/^1[3|4|5|7|8]\d{9}$/.test(mobile))){
            indicator.show('手机号码格式错误！');
            return false;
        }
    }
    return true;
}
/*点击返回顶部*/
$(window).scroll(function () {
    var screenHeight = window.screen.height;

	if ($(this).scrollTop() > (screenHeight * 1.5)) {
		$('#scrollUp').fadeIn();
	} else {
		$('#scrollUp').fadeOut();
	}
});

// scroll body to 0px on click
$('#scrollUp').click(function () {
	$('#scrollUp').tooltip('hide');
	$('body,html').animate({
		scrollTop: 0
	}, 200);
	return false;
});

/*
 * 参数 type 弹窗类型 1、只有内容的弹窗 2、内容加一个“确认”按钮的弹窗 3、内容加两个按钮（“确认”，“取消”）的弹窗
 * 弹窗重复调用会覆盖，不会生成新的弹窗
 * msg 弹窗里面消息类型
 * url1 弹窗点击确认 跳转的url 可不填    如果需要添加点击调用函数，url1就填写函数名字（暂不支持传参） example:openTc(2,'弹窗',noticeCallBack);
 * url2 弹窗点击取消 跳转的url 可不填
 * title 带标题的弹窗（现在没有）
 * example:openTc(1,'弹窗');openTc(2,'弹窗',"http://www.baidu.com");openTc(3,'弹窗',"http://www.baidu.com","http://www.baidu.com");
 */
function openTc(type,msg,url1,url2,title){
    if( type == 1 ){                                                                    //第一种弹窗
        indicator.show(msg);
    }else if( type == 2 ){                                                              //第二种弹窗
        $("#center").css("display","block");                                        //弹窗显示
        $("#notice-btn").css("display","block");                                   //单个按钮显示
        $("#notice-btn-group").css("display","none");                             //多个按钮隐藏
        $("#center .notice-content").html(msg);
        $("#notice-btn").unbind('click').bind('click',function(){
            noticeUrl(url1);
        });
    }else if( type == 3 ){                                                              //第三种弹窗
        $("#center").css("display","block");                                        //弹窗显示
        $("#notice-btn").css("display","none");                                   //弹窗显示
        $("#notice-btn-group").css("display","block");                             //多个按钮隐藏
        $("#center .notice-content").html(msg);
        $(".notice-sure").unbind('click').bind('click',function(){
            noticeUrl(url1);
        });
        $(".notice-cancel").unbind('click').bind('click',function(){
            noticeUrl(url2);
        });
    }
    //点击单个按钮确认 弹窗隐藏
    var len = document.querySelectorAll(".notice-clo").length; //获取有几个按钮可以关闭弹窗
    for( var i = 0; i< len;i++ ){
        document.querySelectorAll(".notice-clo")[i].onclick = function(){
            document.querySelector("#center").style.display = 'none';
        };
    }
}
/* url跳转 */
function noticeUrl(url) {
    if( url && typeof(url) == "function" ){ //判断是否含有回调函数
        url();
    }else if( url ){
        window.location.href = url;
    }
}
/*弹出评论层并隐藏其他层*/
function openSearch(){
    if($(".con").is(":visible")){
        $(".con").hide();
        $(".search").show();
    }
}
/*弹出其他层并隐藏评论层*/
function closeSearch(){
    if($(".con").is(":hidden")){
        $(".con").show();
        $(".search").hide();
    }
}

/*
 * 提示框
 * 进度框
 * 确认框
 */

//进度指示器类
function Pregress(){
    //进度节点对象
    this.progress;
    //显示
    this.show = function(){
        //判断是否存在Dom节点
        if(window.document.getElementById("Progress")){
            window.document.getElementById("Progress").style.display = "block";
        }
        else{
            this.progress = document.createElement("div");
            this.progress.setAttribute("id", "Progress");
            this.progress.setAttribute("class", "spinner");
            this.progress.innerHTML ='<div class="cutecontainer"><span></span><div class="cube1"></div><div class="cube2"></div></div>';
            window.document.body.appendChild(this.progress);
        }
    };
    //隐藏
    this.hide = function(){
        if(window.document.getElementById("Progress")){
            this.progress.style.display = "none";
        }
    };
}
//提示框
function Indicator(){
    //显示
    this.show = function(content, apple){
        var userAgent = navigator.userAgent;
        //判断是否存在Dom节点
        if(window.document.getElementById("Indicator")){
            if (userAgent.indexOf('Android') > -1 || userAgent.indexOf('Linux') > -1) {
                //Android
            }
            else if(userAgent.indexOf('iPhone') > -1){
                //iOS
                window.document.getElementById("Indicator").style.display = "block";
            }
        }
        else{
            var indicator = document.createElement("div");
            indicator.setAttribute("id", "Indicator");
            indicator.innerHTML ='<span>' + content + '</span>';
            if (userAgent.indexOf('Android') > -1 || userAgent.indexOf('Linux') > -1) {
                //Android
                indicator.setAttribute("class", "indicatorAndroid");
            }
            else if(userAgent.indexOf('iPhone') > -1){
                //iOS
                if(apple){
                    indicator.setAttribute("class", "indicator apple");
                }
                else{
                    indicator.setAttribute("class", "indicator");
                }
            }
            window.document.body.appendChild(indicator);
            //2.5S自动消失
            setTimeout( function(){
                window.document.body.removeChild(document.getElementById("Indicator"));
            },1400);
        }
    };
    //隐藏
    this.hide = function(){
        if(window.document.getElementById("Indicator")){
            window.document.body.removeChild(document.getElementById("Indicator"));
        }
    };
}
//确认框
function Confirm(){
    //显示
    this.show = function(title, content, actions){

        var backdrop = document.createElement("div");

        backdrop.setAttribute("id", "Backdrop");
        backdrop.setAttribute("class", "dialog_backdrop");

        window.document.body.appendChild(backdrop);

        var dialog = document.createElement("div");

        dialog.setAttribute("id", "Dialog");
        dialog.setAttribute("class", "dialog");

        var contentDom = '';

        var titleBool = false;
        var contentBool = false;

        //判断是否存在Title
        if(title != undefined && title != ''){
            titleBool = true;
        }
        //判断是否存在Content
        if(content != undefined && content != ''){
            contentBool = true;
        }

        //title content
        if(titleBool&&contentBool){
            contentDom = contentDom + '<h1>' + title + '</h1>' + '<h2>' + content + '</h2>';
        }
        //content
        else if(!titleBool&&contentBool){
            contentDom = contentDom + '<p>' + content + '</p>';
        }

        //判断是否存在actions,以及actions长度
        if(actions != undefined && actions.length != 0){

            contentDom = contentDom + '<div class="actions ui-border-t ui-border-t-dde4e6">';

            if(actions.length == 1){
                var tempDom = '<a class="action_middle ' + actions[0].textColor + '">' + actions[0].text + '</a>';

                contentDom = contentDom + tempDom;
            }
            else{
                var tempDom = '<a class="action_left ' + actions[0].textColor + ' ui-border-r ui-border-r-dde4e6">' + actions[0].text + '</a>' +
                              '<a class="action_right ' + actions[1].textColor + '">' + actions[1].text + '</a>';

                contentDom = contentDom + tempDom;
            }

            contentDom = contentDom + '</div>';
        }
        else{
            throw new Error('actions handler format error!');
        }

        dialog.innerHTML = contentDom;

        window.document.body.appendChild(dialog);

        $("#Dialog a").each(function (index, element) {

            $(element).bind("click", function (e) {

                //回调函数执行
                actions[index].action.call(actions[index].action);

                $("#Backdrop").fadeOut(300, function () {
                    window.document.body.removeChild(document.getElementById("Backdrop"));
                });
                $("#Dialog").fadeOut(300, function () {
                    window.document.body.removeChild(document.getElementById("Dialog"));
                });
            })
        });
    };
    //隐藏
    this.hide = function(){
        if(window.document.getElementById("Dialog")){
            window.document.body.removeChild(document.getElementById("Backdrop"));
            window.document.body.removeChild(document.getElementById("Dialog"));
        }
    };
}
//初始化实例对象
var progress = new Pregress();
var indicator = new Indicator();
var confirm = new Confirm();

//友盟统计实例
function Umeng(){
    //首页
    this.Index = {
        index_show: function () {           //页面展现
            _czc.push(["_trackEvent",'index_show',' ']);
        },
        index_clickwallet: function () {    //点击“卡包”按钮
            _czc.push(["_trackEvent",'index_clickwallet',' ']);
        },
        index_clickpay: function () {       //点击“付款”按钮
            _czc.push(["_trackEvent",'index_clickpay',' ']);
        },
        index_clickgive: function () {      //点击“转赠”按钮
            _czc.push(["_trackEvent",'index_clickgive',' ']);
        },
        index_clickresell: function () {    //点击“转卖”按钮
            _czc.push(["_trackEvent",'index_clickresell',' ']);
        },
        index_clickbanner: function (No) {    //点击banner
            _czc.push(["_trackEvent",'index_clickbanner',No]);
        },
        index_clickbrand: function (No) {     //点击商户button
            _czc.push(["_trackEvent",'index_clickbrand',No]);
        },
        index_clicktab: function (No) {       //点击底部tab首页/购卡/二手卡/卡包/我的
            _czc.push(["_trackEvent",'index_clicktab',No]);
        }
    };
    //自营购卡商户列表页
    this.MerchantList = {
        brandlist_show: function () {         //页面展现
            _czc.push(["_trackEvent",'brandlist_show',' ']);
        },
        brandlist_clickbrand: function (No) {   //点击商户入口
            _czc.push(["_trackEvent",'brandlist_clickbrand',No]);
        }
    };
    //自营购卡卡券详情页
    this.CouponDetail = {
        carddetail_show: function () {       //页面展现
            _czc.push(["_trackEvent",'carddetail_show',' ']);
        },
        carddetail_clickpay: function () {   //点击“立即支付”
            _czc.push(["_trackEvent",'carddetail_clickpay',' ']);
        }
    };
    //购卡说明页面
    this.BuyCouponDes = {
        cardrules_show: function () {        //页面展现
            _czc.push(["_trackEvent",'cardrules_show',' ']);
        }
    };
    //自营订单信息页面
    this.OrderDetail = {
        order_show: function () {           //页面展现
            _czc.push(["_trackEvent",'order_show',' ']);
        },
        order_clickwechat: function () {    //点击“微信支付”
            _czc.push(["_trackEvent",'order_clickwechat',' ']);
        },
        order_clicktheothers: function () { //点击“找人代付”
            _czc.push(["_trackEvent",'order_clicktheothers',' ']);
        }
    };
    //二手卡商户大类页
    this.SecondMerchant = {
        c2cbrandlist_show: function () {        //页面展现
            _czc.push(["_trackEvent",'c2cbrandlist_show',' ']);
        },
        c2cbrandlist_clickpost: function () {   //点击“发布”按钮
            _czc.push(["_trackEvent",'c2cbrandlist_clickpost',' ']);
        }
    };
    //二手卡卡券列表页
    this.SecondCouponList = {
        c2ccardlist_show: function () {         //页面展现
            _czc.push(["_trackEvent",'c2ccardlist_show',' ']);
        },
        c2ccardlist_clicksort: function (No) {  //点击“余额”,“折扣”,“售价”,“默认”排序按钮
            _czc.push(["_trackEvent",'c2ccardlist_clicksort',No]);
        },
        c2ccardlist_clickbuy: function () {     //点击“购卡”按钮
            _czc.push(["_trackEvent",'c2ccardlist_clickbuy',' ']);
        },
        c2ccardlist_clickpost: function () {    //点击“发布”按钮
            _czc.push(["_trackEvent",'c2ccardlist_clickpost',' ']);
        }
    };
    //二手卡卡券详情页
    this.SecondCouponDetail = {
        c2ccarddetail_show: function () {       //页面展现
            _czc.push(["_trackEvent",'c2ccarddetail_show',' ']);
        },
        c2ccarddetail_clickbuy: function () {   //点击“购买“按钮
            _czc.push(["_trackEvent",'c2ccarddetail_clickbuy',' ']);
        }
    };
    //二手卡支付订单页面
    this.SecondPayOrder = {
        c2corder_show: function () {            //页面展现
            _czc.push(["_trackEvent",'c2corder_show',' ']);
        },
        c2corder_clickwechat: function () {     //点击“微信支付”按钮
            _czc.push(["_trackEvent",'c2corder_clickwechat',' ']);
        },
        c2corder_clicktheothers: function () {  //点击“找人代付”按钮
            _czc.push(["_trackEvent",'c2corder_clicktheothers',' ']);
        },
        c2corder_clickcancel: function () {     //点击“取消订单”按钮
            _czc.push(["_trackEvent",'c2corder_clickcancel',' ']);
        }
    };
    //卡包列表页
    this.CouponPocketList = {
        purselist_show: function () {           //页面展现
            _czc.push(["_trackEvent",'purselist_show',' ']);
        },
        purselist_clickinvalid: function () {   //点击“已失效”按钮
            _czc.push(["_trackEvent",'purselist_clickinvalid',' ']);
        },
        purselist_clickcard: function () {      //点击“卡券”
            _czc.push(["_trackEvent",'purselist_clickcard',' ']);
        },
        purselist_clickcardall: function () {   //点击电子卡“查看全部”按钮
            _czc.push(["_trackEvent",'purselist_clickcardall',' ']);
        },
        purselist_clickcodedall: function () {  //点击电子码“查看全部”按钮
            _czc.push(["_trackEvent",'purselist_clickcodedall',' ']);
        }
    };
    //卡包无卡提醒页面
    this.NoCoupon = {
        nocard1_show: function () {             //页面展现
            _czc.push(["_trackEvent",'nocard1_show',' ']);
        },
        nocard1_clickbuy: function () {         //点击“开始购卡”按钮
            _czc.push(["_trackEvent",'nocard1_clickbuy',' ']);
        }
    };
    //所有电子卡页
    this.CouponCardAll = {
        cardall_show: function () {             //页面展示
            _czc.push(["_trackEvent",'cardall_show',' ']);
        },
        cardall_clickcard: function () {        //点击“卡券”
            _czc.push(["_trackEvent",'cardall_clickcard',' ']);
        }
    };
    //所有电子码页
    this.CouponCodeAll = {
        codeall_show: function () {             //页面展示
            _czc.push(["_trackEvent",'codeall_show',' ']);
        },
        codeall_clickcard: function () {        //点击“卡券”
            _czc.push(["_trackEvent",'codeall_clickcard',' ']);
        }
    };
    //失效电子卡码页
    this.DisabledCouponAll = {
        usedcard_show: function () {            //页面展示
            _czc.push(["_trackEvent",'usedcard_show',' ']);
        },
        usedcard_clickcard: function () {       //点击“卡券”
            _czc.push(["_trackEvent",'usedcard_clickcard',' ']);
        }
    };
    //卡包电子卡详情页
    this.CouponPocketDetail = {
        pursedetail_show: function () {         //页面展现
            _czc.push(["_trackEvent",'pursedetail_show',' ']);
        },
        pursedetail_clickpay: function () {     //点击“扫码付款”按钮
            _czc.push(["_trackEvent",'pursedetail_clickpay',' ']);
        },
        pursedetail_clicklist: function (No) {  //点击“转卖卡券”,“转赠好友”按钮
            _czc.push(["_trackEvent",'pursedetail_clicklist',No]);
        }
    };
    //电子码详情页
    this.ConponCodeDetail = {
        codedetail_show: function () {          //页面展现
            _czc.push(["_trackEvent",'codedetail_show',' ']);
        },
        codedetail_clicklist: function (No) {   //点击“删除”,"使用说明"按钮
            _czc.push(["_trackEvent",'codedetail_clicklist',No]);
        }
    };
    //我的页面
    this.Usercenter = {
        mine_show: function () {                //页面展现
            _czc.push(["_trackEvent",'mine_show',' ']);
        },
        mine_clicktopay: function () {          //点击“待付款”按钮
            _czc.push(["_trackEvent",'mine_clicktopay',' ']);
        },
        mine_clickall: function () {            //点击“全部订单”按钮
            _czc.push(["_trackEvent",'mine_clickall',' ']);
        },
        mine_clickpost: function () {           //点击“我发布的”
            _czc.push(["_trackEvent",'mine_clickpost',' ']);
        },
        mine_clickmysetting: function () {      //点击“账户设置”
            _czc.push(["_trackEvent",'mine_clickmysetting',' ']);
        }
    };
    //我的订单页面
    this.UserOrderList = {
        myorders_show: function () {            //页面展现
            _czc.push(["_trackEvent",'myorders_show',' ']);
        },
        myorders_showtab: function (No) {         //“待付款”tab页展示 “已关闭”tab页展示 “全部订单”tab页展示
            _czc.push(["_trackEvent",'myorders_showtab',No]);
        },
        myorders_clicktab: function (No) {        //点击“待付款”tab 点击“已关闭”tab 点击“全部订单”tab
            _czc.push(["_trackEvent",'myorders_clicktab',No]);
        },
        myorders_clickpay: function () {        //点击订单“付款”按钮
            _czc.push(["_trackEvent",'myorders_clickpay',' ']);
        },
        myorders_clickcancel: function () {     //点击订单“取消”按钮
            _czc.push(["_trackEvent",'myorders_clickcancel',' ']);
        }
    };
    //我发布的页面
    this.MyPublishList = {
        mypost_show: function () {              //页面展现
            _czc.push(["_trackEvent",'mypost_show',' ']);
        },
        mypost_showtab: function (No) {           //“在卖卡券”tab页展示 “下架卡券”tab页展示 “已卖出的”tab页展示
            _czc.push(["_trackEvent",'mypost_showtab',No]);
        },
        mypost_clicktab: function (No) {          //点击“在卖卡券”tab 点击“下架卡券”tab 点击“已卖出的”tab
            _czc.push(["_trackEvent",'mypost_clicktab',No]);
        },
        mypost_clickdown: function () {         //点击在卖卡券“下架”按钮
            _czc.push(["_trackEvent",'mypost_clickdown',' ']);
        },
        mypost_clickedit: function () {         //点击在卖卡券“编辑”按钮
            _czc.push(["_trackEvent",'mypost_clickedit',' ']);
        },
        mypost_clickrepost: function () {       //点击下架卡券“重新上架”按钮
            _czc.push(["_trackEvent",'mypost_clickrepost',' ']);
        },
        mypost_clickdelete: function () {       //点击下架卡券“彻底删除”按钮
            _czc.push(["_trackEvent",'mypost_clickdelete',' ']);
        }
    };
    //账户设置页面
    this.UserSetting = {
        mysetting_show: function () {           //页面展现
            _czc.push(["_trackEvent",'mysetting_show',' ']);
        }
    };
    //支付成功
    this.PaySuccess = {
        paysuccess_show: function () {           //页面展现
            _czc.push(["_trackEvent",'paysuccess_show',' ']);
        },
        paysuccess_clickcheck: function () {     //点击“查看卡券”按钮
            _czc.push(["_trackEvent",'paysuccess_clickcheck',' ']);
        },
        paysuccess_clickactivated: function () { //点击“努力激活中”按钮
            _czc.push(["_trackEvent",'paysuccess_clickactivated',' ']);
        },
        paysuccess_clickwechat: function () {    //点击“放入微信卡包”按钮
            _czc.push(["_trackEvent",'paysuccess_clickwechat',' ']);
        }
    };
    //转赠列表页
    this.DonateList = {
        givelist_show: function () {             //转赠列表页展现
            _czc.push(["_trackEvent",'givelist_show',' ']);
        },
        givelist_clickgive: function () {        //点击“转赠”按钮
            _czc.push(["_trackEvent",'givelist_clickgive',' ']);
        }
    };
    //转卖列表页
    this.SecondSellList = {
        postlist_show: function () {             //转卖列表页展现
            _czc.push(["_trackEvent",'postlist_show',' ']);
        },
        postlist_clickpost: function () {        //点击“转卖”按钮
            _czc.push(["_trackEvent",'postlist_clickpost',' ']);
        }
    };
    //列表页无卡展示页面
    this.NoCouponList = {
        nocard2_show: function (No) {              //页面展示
            _czc.push(["_trackEvent",'nocard2_show',No]);
        },
        nocard2_clickbuy: function (No) {          //点击“开始购卡”按钮
            _czc.push(["_trackEvent",'nocard2_clickbuy',No]);
        }
    };
    //转赠信息编辑页
    this.DonateEdit = {
        giveedit_show: function () {             //页面展示
            _czc.push(["_trackEvent",'giveedit_show',' ']);
        },
        giveedit_clickgive: function () {        //点击“转赠”按钮
            _czc.push(["_trackEvent",'giveedit_clickgive',' ']);
        }
    };
    //转赠成功页面
    this.DonateSuccess = {
        givesuccess_show: function () {          //页面展示
            _czc.push(["_trackEvent",'givesuccess_show',' ']);
        },
        givesuccess_clickcheck: function () {    //点击“查看卡券”按钮
            _czc.push(["_trackEvent",'givesuccess_clickcheck',' ']);
        }
    };
    //卡券领取页面
    this.CouponReceive = {
        receive_show: function () {              //页面展示
            _czc.push(["_trackEvent",'receive_show',' ']);
        }
    };
    //编辑发布信息页
    this.EditPublishCoupon = {
        postedit_show: function () {             //页面展现
            _czc.push(["_trackEvent",'postedit_show',' ']);
        },
        postedit_clicksubmit: function () {      //点击“提交”按钮
            _czc.push(["_trackEvent",'postedit_clicksubmit',' ']);
        },
        postedit_clickcancel: function () {      //点击“取消”按钮
            _czc.push(["_trackEvent",'postedit_clickcancel',' ']);
        }
    };
    //发布成功页面
    this.PublishSuccess = {
        postsuccess_show: function () {          //页面展现
            _czc.push(["_trackEvent",'postsuccess_show',' ']);
        }
    };
}

//统计实例
var umeng = new Umeng();

//弹窗提示
var noticErr = localStorage.noticErr;
if( noticErr == '-1' ){
    var type = localStorage.type,msg = localStorage.msg,url1 = localStorage.url1,url2 = localStorage.url2;
    console.log(localStorage);
    if( type == 1 ){
        openTc(1,msg);
    }else if( type == 2 ){
        openTc(2,msg,url1);
    }else if( type == 3 ){
        openTc(3,msg,url1,url2);
    }
    localStorage.removeItem('noticErr');
    localStorage.removeItem('type');
    localStorage.removeItem('msg');
    localStorage.removeItem('url1');
    localStorage.removeItem('url2');
}
/*加载更多*/
function addLoading( obj ){
    obj.append("<div class='notice-loading'><div id='circle'></div>加载中...</div>");
    $("#circle1").fadeOut(70000);
}
/*加载更多*/
function removeLoading(){
    $('.notice-loading').remove();
}