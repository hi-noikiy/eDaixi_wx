function setupWebViewJavascriptBridge(callback) {
    if (window.WebViewJavascriptBridge) { return callback(WebViewJavascriptBridge); }
    if (window.WVJBCallbacks) { return window.WVJBCallbacks.push(callback); }
    window.WVJBCallbacks = [callback];
    var WVJBIframe = document.createElement('iframe');
    WVJBIframe.style.display = 'none';
    WVJBIframe.src = 'wvjbscheme://__BRIDGE_LOADED__';
    document.documentElement.appendChild(WVJBIframe);
    setTimeout(function() { document.documentElement.removeChild(WVJBIframe) }, 0)
}

/**
 * app环境下获取价目页的通用数据
 * @return {object} {
    city_id: 1,
    app_key: app_client,
    client_name: ios_client,
    mark: yy,
    version: 1.0,
    user_type: 2
 }
 */
function getPriceParam() {
    var headers;
    setupWebViewJavascriptBridge(function(bridge) {
        bridge.callHandler('getCommonRequestParams', {}, function responseCallback(responseData) {
            headers = responseData;
        })
    })
    return headers;
}

/**
 * 当sessionid过期时，需要重新获取sessionid并刷新页面
 * @param  {number} errorCode sessionid过期时的状态码，固定为40001
 */
function exceptionHandle(errorCode) {
    setupWebViewJavascriptBridge(function(bridge) {
        bridge.callHandler('exceptionHandle', {"exceptionId": errorCode}, function responseCallback(responseData) {})
    })
}
/**
 * 跳转到下单页
 * @param  {string} categoryIdList category_id 的字符串，用逗号分隔
 */
function goShoppingPage(categoryIdList) {
    setupWebViewJavascriptBridge(function(bridge) {
        bridge.callHandler('shoppingHandle', {'categories': categoryIdList}, function responseCallback(responseData) {
            // console.log("JS received response:", responseData)
        })
    })
}

/**
 * 点击banner后，跳转到原生的页面&充值跳转
 * @param  {object} url banners字段中的url字段

 */

function hitBanner(url, business_id) {
    setupWebViewJavascriptBridge(function(bridge) {
        bridge.callHandler('routerHandle', url, function responseCallback(responseData) {
            // console.log("JS received response:", responseData)
            var rechargeFee = responseData.recharge_fee;
            
            if (responseData.recharge_result == 1) {
                if(business_id != ''){
                    location.href = "/mobile.php?m=third&act=auto_activity&do=recharge_success" + "&fee="+rechargeFee + "&from=app&act_id=" + business_id;
                }else{
                    location.href = "/mobile.php?m=third&act=activity&do=recharge_success" + "&fee="+rechargeFee + "&from=app";
                }
            }else{
                var rechargeAndroid = $.parseJSON(responseData);
                var rechargeAndroidFee = rechargeAndroid.recharge_fee;
                
                if (rechargeAndroid.recharge_result == 1) {
                    if(business_id != ''){
                        location.href = "/mobile.php?m=third&act=auto_activity&do=recharge_success"+ "&fee="+rechargeAndroidFee +"&from=app&act_id=" + business_id;
                    }else{
                        location.href = "/mobile.php?m=third&act=activity&do=recharge_success"+ "&fee="+rechargeAndroidFee +"&from=app";
                    }
                };
            };
           
        })
    })
}

/**
 * 获取页面运行环境：ios，android，web
 * @return {[type]} [description]
 */
function getUserAngent() {

    var ua;
    setupWebViewJavascriptBridge(function(bridge) {
        bridge.callHandler('getUserAngent', function responseCallback(responseData) {
            ua = responseData;
        })
    })
    
    return ua;
}

/**
 * 当sessionId过期，价目页将后端返回的40001传给app后，app处理失败后重新登录
 */
function loginHandle() {
    setupWebViewJavascriptBridge(function(bridge) {
        
        bridge.callHandler("loginHandle", {}, function responseCallback(responseData) {
            
        });
    })
}

function log(message, data) {
    var log = document.getElementById('log')
    var el = document.createElement('div')
    el.className = 'logLine'
    el.innerHTML = uniqueId++ + '. ' + message + ':<br/>' + JSON.stringify(data)
    if (log.children.length) { log.insertBefore(el, log.children[0]) }
    else { log.appendChild(el) }
}

/**
 * 异常回调处理
 * @param  {number} exceptionId 40001， 表未授权，需重新登陆
 */
function exceptionHandle(exceptionId) {
    setupWebViewJavascriptBridge(function(bridge) {
        bridge.callHandler("exceptionHandle", {"exceptionId": exceptionId});
    })
}

/**
 * 活动跳转
 * @param  {string} klass  "order"(下单)、 "balance"（余额）、 ”invite“（邀请好友）、 ”coupon“（优惠券）、”points“（积分）、”points_mall“（积分商城）、”recharge“（充值）、”pay“（支付）
 * @param  {string} type   ”create“（新打开一个页面）、”list“（列表，主要为了区分下单和订单列表）
 * @param  {object} params 
 */
function activityHandler(klass, type, params) {
    setupWebViewJavascriptBridge(function(bridge) {
        bridge.callHandler("activityHandler", {
            "url": {
                "klass": klass,
                "type": type,
                "params": params
            },
            "url_type": "in_app"
        }, function(response) {
            alert(response);
        })
    })
}

/**
 * 活动成功回调
 */
function activitySucessHandler() {
    setupWebViewJavascriptBridge(function(bridge) {
        bridge.callHandler("activitySucessHandler");
    })
}