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
 * 充值页通信
 * @param  充值native所需参数
 */
function rechargePay(params) {
    setupWebViewJavascriptBridge(function(bridge) {
        bridge.callHandler('rechargePay',params , function responseCallback(responseData) {
            // console.log("JS received response:", responseData)
            if (responseData == true) {
                //充值成功
            } else {
                //充值失败
            }
        })
    })
}

/**
 * 点击banner后，跳转到原生的页面
 * @param  {object} url banners字段中的url字段
 */
function hitBanner(url) {
    setupWebViewJavascriptBridge(function(bridge) {
        bridge.callHandler('routerHandle', url, function responseCallback(responseData) {
            // console.log("JS received response:", responseData)
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
        bridge.callHandler("loginHandle");
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