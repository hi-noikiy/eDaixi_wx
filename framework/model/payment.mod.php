<?php
/**
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */
defined('IN_IA') or exit('Access Denied');

define('ALIPAY_GATEWAY', 'http://wappaygw.alipay.com/service/rest.htm');
define('ALIPAY_INPUT_CHARSET', 'utf-8');

/**
 * $params['tid']
 * $params['title']
 * $params['fee']
 * $params['user']
 *
 * $alipay['account']
 * $alipay['partner']
 * $alipay['secret']
 *
 * $ret['url']
 */
function alipay_build($params, $alipay = array()) {
	global $_W;
	$tid = $params['tid'];
	$set = array();
	$set['service'] = 'alipay.wap.trade.create.direct';
	$set['format'] = 'xml';
	$set['v'] = '2.0';
	$set['partner'] = $alipay['partner'];
	$set['req_id'] = $tid;
	$set['sec_id'] = 'MD5';
	$callback = $_W['config']['site']['root'] . 'payment/alipay/return.php';
	//$notify = $_W['siteroot'] . 'payment/alipay/notify.php';
	$notify = $_W['config']['pay']['domain']. 'payment/ali_notify';
	$merchant =  $_W['config']['site']['root'] . 'payment/alipay/merchant.php';
	$expire = 10;
	$set['req_data'] = "<direct_trade_create_req><subject>{$params['title']}</subject><out_trade_no>{$tid}</out_trade_no><total_fee>{$params['fee']}</total_fee><seller_account_name>{$alipay['account']}</seller_account_name><call_back_url>{$callback}</call_back_url><notify_url>{$notify}</notify_url><out_user>{$_SESSION['user_info']['user_id']}</out_user><merchant_url>{$merchant}</merchant_url><pay_expire>{$expire}</pay_expire></direct_trade_create_req>";
	$prepares = array();
	// logging('alipay', var_export($set,true), 'a+', 'open_error');
	// logging('alipay', var_export($_SESSION,true), 'a+', 'open_error');
	// logging('alipay', var_export($params,true), 'a+', 'open_error');
	foreach($set as $key => $value) {
		if($key != 'sign') {
			$prepares[] = "{$key}={$value}";
		}
	}
	sort($prepares);
	$string = implode($prepares, '&');
	$string .= $alipay['secret'];
	$set['sign'] = md5($string);
	
	//$response = ihttp_get(ALIPAY_GATEWAY . '?' . http_build_query($set));
	$response = ihttp_post(ALIPAY_GATEWAY, $set);
	$ret = array();
	
	@parse_str($response['content'], $ret);
	
	foreach($ret as &$v) {
		$v = str_replace('\"', '"', $v);
	}
	if(is_array($ret)) {
		if($ret['res_error']) {
			$error = simplexml_load_string($ret['res_error'], 'SimpleXMLElement', LIBXML_NOCDATA);
			if($error instanceof SimpleXMLElement && $error->detail) {
				error_report("发生错误, 无法继续支付. 详细错误为: " . strval($error->detail));
			}
		}

		if($ret['partner'] == $set['partner'] && $ret['req_id'] == $set['req_id'] && $ret['sec_id'] == $set['sec_id'] && $ret['service'] == $set['service'] && $ret['v'] == $set['v']) {
			$prepares = array();
			foreach($ret as $key => $value) {
				if($key != 'sign') {
					$prepares[] = "{$key}={$value}";
				}
			}
			sort($prepares);
			$string = implode($prepares, '&');
			$string .= $alipay['secret'];
			if(md5($string) == $ret['sign']) {
				$obj = simplexml_load_string($ret['res_data'], 'SimpleXMLElement', LIBXML_NOCDATA);
				if($obj instanceof SimpleXMLElement && $obj->request_token) {
					$token = strval($obj->request_token);
					$set = array();
					$set['service'] = 'alipay.wap.auth.authAndExecute';
					$set['format'] = 'xml';
					$set['v'] = '2.0';
					$set['partner'] = $alipay['partner'];
					$set['sec_id'] = 'MD5';
					$set['req_data'] = "<auth_and_execute_req><request_token>{$token}</request_token></auth_and_execute_req>";
					$prepares = array();
					foreach($set as $key => $value) {
						if($key != 'sign') {
							$prepares[] = "{$key}={$value}";
						}
					}
					sort($prepares);
					$string = implode($prepares, '&');
					$string .= $alipay['secret'];
					$set['sign'] = md5($string);
					$url = ALIPAY_GATEWAY . '?' . http_build_query($set);
					return array('url' => $url);
					//$html = buildRequestForm($set, 'get', '支付宝付款');
					//return array('html' => $html);
				}
			}
		}
	}
	error_report('非法访问.');
}


/**
 * 建立请求，以表单HTML形式构造（默认）
 * @param $para_temp 请求参数数组
 * @param $method 提交方式。两个值可选：post、get
 * @param $button_name 确认按钮显示文字
 * @return 提交表单HTML文本
 */
function buildRequestForm($para, $method='get', $button_name='确定') {
	//待请求参数数组
	//$para = $this->buildRequestPara($para_temp);

	$sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='".ALIPAY_GATEWAY."?_input_charset=".trim(strtolower(ALIPAY_INPUT_CHARSET))."' method='".$method."'>";
	//while (list ($key, $val) = each ($para)) {
	foreach ($para as $key => $val){
		$sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";		
	}

	//submit按钮控件请不要含有name属性
	$sHtml = $sHtml."<input type='submit' value='".$button_name."'></form>";

	$sHtml = $sHtml."<script>document.forms['alipaysubmit'].submit();</script>";

	return $sHtml;
}

/**
 * $params['tid']
 * $params['title']
 * $params['fee']
 * $params['user']
 *
 * $alipay['account']
 * $alipay['partner']
 * $alipay['secret']
 *
 * @return js payment object
 */
function wechat_build($params, $wechat) {
	global $_W;
	$timeStamp = time();
	$ip = getip();
	$wOpt['appId'] = $wechat['appid'];
	$wOpt['timeStamp'] = $timeStamp;
	$wOpt['nonceStr'] = random(8);
	$package = array();
	$package['bank_type'] = 'WX';
	$package['body'] = $params['title'];
	$package['attach'] = $_W['weid'];
	$package['partner'] = $wechat['partner'];
	$package['out_trade_no'] = $params['tid'];
	$package['total_fee'] = $params['fee'] * 100;
	$package['fee_type'] = '1';
	$package['notify_url'] = $_W['config']['pay']['domain'] . 'payment/wechat_notify'; #todo: 这里调用$_W['siteroot']是在子目录下. 获取的是当前二级目录
	$package['spbill_create_ip'] = $ip;
	$package['time_start'] = date('YmdHis', $timeStamp);
	$package['time_expire'] = date('YmdHis', $timeStamp + 600);
	$package['input_charset'] = 'UTF-8';
	ksort($package);
	$string1 = '';
	foreach($package as $key => $v) {
		$string1 .= "{$key}={$v}&";
	}
	$string1 .= "key={$wechat['key']}";
	$sign = strtoupper(md5($string1));

	$string2 = '';
	foreach($package as $key => $v) {
		$v = urlencode($v);
		$string2 .= "{$key}={$v}&";
	}
	$string2 .= "sign={$sign}";
	$wOpt['package'] = $string2;

	$string = '';
	$keys = array('appId', 'timeStamp', 'nonceStr', 'package', 'appKey');
	sort($keys);
	foreach($keys as $key) {
		$v = $wOpt[$key];
		if($key == 'appKey') {
			$v = $wechat['signkey'];
		}
		$key = strtolower($key);
		$string .= "{$key}={$v}&";
	}
	$string = rtrim($string, '&');

	$wOpt['signType'] = 'SHA1';
	$wOpt['paySign'] = sha1($string);
	return $wOpt;
}


function baidu_build($params, $config) {
	global $_W;
	//$pay_url = 'https://www.baifubao.com/api/0/pay/0/wapdirect';
	$pay_url = 'https://www.baifubao.com/api/0/pay/0/wapdirect/0';
	$order_create_time = date("YmdHis");
	$expire_time = date('YmdHis', strtotime('+10 minutes'));
	$order_no = $params['tid'];
	$good_name = $params['title'];
	$total_amount = $params['fee'] * 100;
	$buyer_sp_username = $params['user'];
	$return_url = $_W['config']['pay']['domain'] . 'payment/baidu_notify';
	$page_url =  $_W['config']['site']['root'] . 'payment/baidu/return.php';
	$pay_type = 2;

	/*
	 * 字符编码转换，百付宝默认的编码是GBK，商户网页的编码如果不是，请转码。涉及到中文的字段请参见接口文档
	 * 步骤：
	 * 1. URL转码
	 * 2. 字符编码转码，转成GBK
	 * 
	 * $good_name = iconv("UTF-8", "GBK", urldecode($good_name));
	 * $good_desc = iconv("UTF-8", "GBK", urldecode($good_desc));
	 * 
	 */

	// 用于测试的商户请求支付接口的表单参数，具体的表单参数各项的定义和取值参见接口文档
	$data = array (
			'service_code' => 1,
			'sp_no' => $config['sp_no'],
			'order_create_time' => $order_create_time,
			'order_no' => $order_no,
			'goods_name' => $good_name,
			'total_amount' => $total_amount,
			'currency' => 1,
			'buyer_sp_username' => $buyer_sp_username,
			'return_url' => $return_url,
			'page_url' => $page_url,
			'pay_type' => $pay_type,
			'expire_time' => $expire_time,
			'input_charset' => 1,
			'version' => 2,
			'sign_method' => 1,
			//'sp_pass_through'=>'%7B%22offline_pay%22%3A%221%22%7D',
	);
	
	if($_SESSION['user_info']['user_type'] == 13) {
		$data['sp_pass_through'] = '%7B%22offline_pay%22%3A%221%22%7D';
	}

	foreach ($data as $k => $v) {
		$data[$k] = iconv("UTF-8", "GBK",$v);
	}

	ksort($data);

	$arr_temp = array ();
	$tmp = $data;
	$tmp['key'] = $config['sp_key'];
	foreach ($tmp as $key => $val) {
		$arr_temp [] = $key . '=' . $val;
	}
	
	$sign_str = implode('&', $arr_temp);
	$sign = md5($sign_str);
	$data ['sign'] = $sign;
	$params_str = http_build_query($data);
	$order_url = $pay_url . '?' . $params_str;
	//return $order_url;

	return "<script>window.location=\"" . $order_url . "\";</script>";

}


function wechat_build_v4($params, $wechat) {
	global $_W;
	$spbill_create_ip = getip();

	$data = array();
	$data['appid'] = $wechat['appid'];
	$data['body'] = $params['title'];
	$data['mch_id'] = $wechat['partner'];
	$data['nonce_str'] = create_noncestr();
	$data['notify_url'] = $_W['config']['pay']['domain'] . 'payment/wechat_v4';
	$data['out_trade_no'] = $params['tid'];
	$data['openid'] = $_SESSION['user_info']['from_user'];
	$data['spbill_create_ip'] = $spbill_create_ip;
	$data['total_fee'] = $params['fee'] * 100;
	$data['trade_type'] = 'JSAPI';

	ksort($data);
	$str = '';
	foreach($data as $k => $v) {
		if (null != $v && "null" != $v && "sign" != $k)
			$str .= "{$k}={$v}&";
	}
	$str .= "key={$wechat['key']}";
	$sign = strtoupper(md5($str));

	$data['sign'] = $sign;

	$text_tpl = "<xml>
				<appid>{$data['appid']}</appid>
			   	<body>{$data['body']}</body>
			   	<mch_id>{$data['mch_id']}</mch_id>
			   	<nonce_str>{$data['nonce_str']}</nonce_str>
			   	<notify_url>{$data['notify_url']}</notify_url>
			   	<out_trade_no>{$data['out_trade_no']}</out_trade_no>
			   	<openid>{$data['openid']}</openid>
			   	<spbill_create_ip>{$data['spbill_create_ip']}</spbill_create_ip>
			   	<total_fee>{$data['total_fee']}</total_fee>
			   	<trade_type>{$data['trade_type']}</trade_type>
			   	<sign>{$data['sign']}</sign>
				</xml>";
	//超时时间设为20秒
	$res = http_handle('https://api.mch.weixin.qq.com/pay/unifiedorder', $text_tpl, 20);

	$obj = simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA);
	$return = json_decode(json_encode((array) $obj), true);

	ksort($return);
	$str = '';
	foreach($return as $k => $v) {
		if (null != $v && "null" != $v && "sign" != $k)
			$str .= "{$k}={$v}&";
	}
	$str .= "key={$wechat['key']}";
	$pull_sign = strtoupper(md5($str));

	if ($pull_sign != $return['sign']) {
		//网络超时、openid错误、或签名错误
		unset($_SESSION['user_info']['from_user']);
		logging('微信支付出错', $text_tpl . "\n" . $res . "\n",
		        'a+', 'payError');
		error_report('微信支付出错啦');
	}

	$time_stamp = time();
	$js_data = array();
	$js_data['appId'] = $data['appid'];
	$js_data['timeStamp'] = (string)$time_stamp;
	$js_data['nonceStr'] = create_noncestr();
	$js_data['package'] = 'prepay_id=' . $return['prepay_id'];
	$js_data['signType'] = 'MD5';

	ksort($js_data);
	$str = '';
	foreach($js_data as $k => $v) {
		if (null != $v && "null" != $v && "sign" != $k)
			$str .= "{$k}={$v}&";
	}
	$str .= "key={$wechat['key']}";
	$sign = strtoupper(md5($str));

	$js_data['paySign'] = $sign;
	$js_data =  json_encode($js_data);

	$html = "<script type='text/javascript'>
	document.addEventListener('WeixinJSBridgeReady', function onBridgeReady() {
		WeixinJSBridge.invoke('getBrandWCPayRequest', {$js_data}, function(res) {
			if(res.err_msg == 'get_brand_wcpay_request:ok') {
				location.href = '{$_W['config']['site']['root']}payment/wechat/pay.php?done=1';
			} else {
				if(res.err_msg == 'get_brand_wcpay_request:cancel'){
					//alert('启动微信支付失败, 请重新点击支付. 详细错误为: ' + res.err_msg);
					history.go(-1);
				}else{
					//alert('启动微信支付失败, 请重新点击支付. 详细错误为: ' + res.err_msg);
					history.go(-1);
				}
				//alert('启动微信支付失败, 请重新点击支付. 详细错误为: ' + res.err_msg);
			}
		});
	}, false);
</script>";
	return $html;
}

//浦发周边通支付
function psd_zbt_build($params, $config){
	global $_W;
	//先创建周边通订单
	$data = array();
	$data['source'] = $_SESSION['psdzbt_source'];
	$data['app_id'] = $config['app_id'];
	$data['open_userid'] = $params['open_userid'];
	//此处为了解决浦发的奇葩创建订单返回
	$params['tid'] .= 'A' . time();
	$data['out_order_number'] = $params['tid'];
	$data['type_status'] = 1;
	$data['city_codes'] = $params['city_id'];
	$data['consignee_address'] = $params['address'];
	$data['order_title'] = $params['title'];
	$data['total_amount'] = $params['fee'];
	$data['return_url'] = $_W['config']['site']['root'] . 'mobile.php?m=wap&act=order&do=order_list';
	$data['notify_url'] = $_W['config']['pay']['domain'] . 'payment/zbt';
	$data['push_type'] = 2;
	$data['sign'] = makeSignature($data, $config);

	$res = ihttp_post($config['order_url'], $data);
    $res = json_decode($res['content'], true);
    if(is_array($res) && !empty($res['data'])){
    	$push = array();
    	$push['foreign_name'] = 'psd_zbt';
    	$push['foreign_order_id'] = $res['data']['order_number'];
    	$push['order_id'] = $res['data']['out_order_number'];
    	$push['foreign_status'] = $res['data']['type_status'];
    	$push['foreign_money'] = $res['data']['total_amount'];
    	$push['foreign_detail'] = $res['data']['source'];

    	$push_url = $_W['config']['edaixi']['sw_server'] . '/foreignorder/create_order';
    	$ret = ihttp_post($push_url, $push);
    	$ret = json_decode($ret['content'], true);
    	if(!is_array($ret) || $ret['code']){
    		error_report('预创建订单保存失败');
    	}
    }else{
    	error_report('预创建订单失败');
    }

	//发起支付url
	$url = $_W['config']['edaixi']['sw_server'] . '/foreignorder/get_order';
	$resp = ihttp_get($url.'?order_id='.$params['tid']);
	$resp = json_decode($resp['content'], true);
	if(is_array($resp) && $resp['code'] == 0 && $resp['data']['foreign_order_id']){
		$arrData = array();
		$arrData['source'] = $res['data']['source'];
		$arrData['order_number'] = $resp['data']['foreign_order_id'];
		$arrData['app_id'] = $config['app_id'];
		$arrData['open_userid'] = $params['open_userid'];
		$arrData['sign'] = makeSignature($arrData, $config);
		return $config['pay_url'].'?'.http_build_query($arrData);
	}
	error_report('支付操作异常');
}

//浦发周边通签名
function makeSignature($params, $config){
	ksort($params);
    $buff = '';
    foreach ($params as $key => $val) {
        if($val !== '' && !is_array($val)){
            $buff .= $key.'='.$val.'&';
        }
    }
    $buff = trim($buff, '&');
    $buff .= $config['app_secret'];
    return md5($buff);
}

function wechat_build_h5($params, $wechat){
	global $_W;
	$spbill_create_ip = getip();

	$data = array();
	$data['appid']        = $wechat['appid'];
	$data['mch_id']       = $wechat['partner'];
	$data['nonce_str']    = create_noncestr();
	$data['body']         = $params['title'];
	$data['out_trade_no'] = $params['tid'];
	$data['total_fee']    = $params['fee'] * 100;
	$data['spbill_create_ip'] = getip();
	$data['notify_url']   = $_W['config']['pay']['domain'] . 'payment/wechat_h5';
	$data['trade_type']   = 'MWEB';

	ksort($data);
	
	$string1 = '';
	foreach($data as $k => $v) {
	    if (null != $v && "null" != $v && "sign" != $k)
	        $string1 .= "{$k}={$v}&";
	}
	
	$string1 .= 'key='.$wechat['key'];
	$data['sign'] = strtoupper(MD5($string1));

	$postXml = "<xml>";
     $postXml .= "<appid>{$data['appid']}</appid>";
     $postXml .= "<body>{$data['body']}</body>";
     $postXml .= "<mch_id>{$data['mch_id']}</mch_id>";
     $postXml .= "<nonce_str>{$data['nonce_str']}</nonce_str>";
     $postXml .= "<notify_url>{$data['notify_url']}</notify_url>";
     $postXml .= "<out_trade_no>{$data['out_trade_no']}</out_trade_no>";
     $postXml .= "<spbill_create_ip>{$data['spbill_create_ip']}</spbill_create_ip>";
     $postXml .= "<total_fee>{$data['total_fee']}</total_fee>";
     $postXml .= "<trade_type>{$data['trade_type']}</trade_type>";
     $postXml .= "<sign>{$data['sign']}</sign>";
    $postXml .= "</xml>";
	
	//超时时间设为20秒
	$res = http_handle('https://api.mch.weixin.qq.com/pay/unifiedorder', $postXml, 20);

	$obj = simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA);
	$return = json_decode(json_encode((array) $obj), true);

	ksort($return);
	$string2 = '';
	foreach($return as $k => $v) {
		if (null != $v && "null" != $v && "sign" != $k)
			$string2 .= "{$k}={$v}&";
	}
	$string2 .= "key={$wechat['key']}";
	$pull_sign = strtoupper(md5($string2));

	if ($pull_sign != $return['sign']) {
		//网络超时、openid错误、或签名错误
		logging('微信支付H5出错', $postXml . "\n" . $res . "\n",
		        'a+', 'payError');
		error_report('微信支付H5出错啦');
	}

	return  array('url' => $return['mweb_url']);
}

function nuomi_build($params, $config) {
	global $_W;
	$url = 'http://comout.nuomi.com/component/nuomi_cashier/order_create/order_create.html?tpData=';
	
	$displayData = array(
		"cashierTopBlock" => array(
			array(
				array(
					"leftCol" => "商品名称",
					"rightCol" => "e袋洗",
					),
				array(
					"leftCol" => "价格",
					"rightCol" => "{$params['fee']}",
					),
				),
			// array(
			// 	)
			)
		);
	    $data = array();
        $data['appKey'] = $config['appkey'];
        $data['tpOrderId'] = (string)$params['tid'];
        $data['dealId'] = $config['dealid'];
        $data['totalAmount'] =(string)$params['fee']*100;
        $data['payResultUrl'] = $config['return_url'];
        //$data['returnData'] = array('nuomi'=>1);
        $data['displayData'] = $displayData;
        $data['dealTitle'] = $params['title'];
        $data['dealSubTitle'] = $params['title'];
        $data['dealThumbView'] ='http://7xjove.com1.z0.glb.clouddn.com/edaixi.jpg';
        $data['rsaSign'] =  nuomi_sign($data,$config['prikey']);

        

        $json = json_encode(url_encode($data),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
       // header("Location: http://comout.nuomi.com/component/nuomi_cashier/order_create/order_create.html?tpData={$json}");

    $html = "<script>
    var tpData = $json;
    strTpData = encodeURIComponent(JSON.stringify(tpData));
//console.log(strTpData);
//      location.href = 'http://comout.nuomi.com/component/nuomi_cashier/order_create/order_create.html?tpData='+strTpData;
    location.href = 'bainuo://component?compid=nuomi_cashier&comppage=order_create&tpData={$json}';
    </script>";
	return $html;
	//return $url.json_encode($data);
}

function nuomi_sign(array $params,$prikey)
    {
        $sign = '';
        if (empty($prikey) || empty($params)) {
            return $sign;
        }

        if (!function_exists('openssl_pkey_get_private') || !function_exists('openssl_sign')) {
            throw new Exception("openssl扩展不存在");
        }

        $priKey = openssl_pkey_get_private($prikey);

        if (isset($params['sign'])) {
            unset($params['sign']);
        }

        ksort($params); //按字母升序排序

        $parts = array();
        foreach ($params as $k => $v) {
        	if(in_array($k, ['appKey','dealId','tpOrderId'])) {
        		$parts[] = $k . '=' . $v;
        	}
            
        }
        $str = implode('&', $parts);
        openssl_sign($str, $sign, $priKey);
        openssl_free_key($priKey);

        return base64_encode($sign);
    }

if (!function_exists('encode_json')) {
	function encode_json($str) {
		return urldecode(json_encode(url_encode($str),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
	}
}

/**
 *
 */
if (!function_exists('url_encode')) {
	function url_encode($str) {
		if (is_array($str)) {
			foreach ($str as $key => $value) {
				$str[urlencode($key)] = url_encode($value);
			}
		} else {
			$str = urlencode($str);
		}
		return $str;
	}
}


function yajule_build($params, $yajule) {
	global $_W;

	$requestData = array(
		'orderNo' => $params['tid'],
		'appId' => $yajule['app_id'],
		'beneficiaryUid' => $yajule['beneficiaryUid'],
		'userId' => $_SESSION['user_info']['from_user'],
		'orderTotalMoney' => (string)$params['fee']*100,
		'subject' => $params['title'],
		'body' => $params['title'],
		'callUrl' => $yajule['callback'],
		'randomCode' => create_noncestr(),
		);
	//logging('雅生活支付requestData', $requestData, 'a+', 'payError');
	$data = array();
	$data['timestamp'] = time();
	$data['app_id'] = $yajule['app_id'];
	$data['method'] = 'base.paymentCoop.unifiedorder';
	$data['params'] = json_encode(['appId'=>$yajule['app_id'],'requestData'=>base64_encode(rsa_enc(json_encode($requestData), $yajule['publickey']))]);
	//logging('雅生活支付params', $data['params'], 'a+', 'payError');
	$data['sign'] = yajule_sign($data,$yajule['app_secret']);
	//logging('雅生活支付url', $yajule['url'].'?'.http_build_query($data), 'a+', 'payError');
	$res = http_handle($yajule['url'].'?'.http_build_query($data));

	$return = json_decode($res, true);

	if(!isset($return['data']['responseData'])) {
		logging('雅生活支付出错', $data . "\n" . $res . "\n",
		        'a+', 'payError');
		error_report('雅生活支付出错');
	}

	$responseData = $return['data']['responseData'];
	$responseData = rsa_dec_pub(base64_decode($responseData),$yajule['publickey']);
	$responseArr = json_decode($responseData, true);
	$payOrderNo = $responseArr['payOrderNo'];

	$html = "<script src='/framework/style/js/jquery-1.11.1.min.js'></script>
			<script src='http://www.4006983383.com/h5/pay/js/prepay.js'></script>
			<script>
			$.agilePay.toPay({
				hostName: 'api.4006983383.com',
				payOrderNo: '{$payOrderNo}',
				accessToken: '{$_SESSION['user_info']['accessToken']}', 
				successUrl: '{$_W['config']['site']['domain']}', 
				homepageUrl: '{$_W['config']['site']['domain']}'
				});
			</script>
			";

	return $html;
}


function yajule_sign(array $params, $app_secret)
{
    $str = '';
    ksort($params);

    foreach ($params as $k => $v) {
        if ($k != 'sign') {
            $str .= "{$k}={$v}&";
        }
    }
    $str = trim($str,'&').$app_secret;
    $sign = md5($str);
    return $sign;
}

function rsa_enc($data,$pub) {
    $pub_key = openssl_pkey_get_public($pub);
    $crypt_res = '';
    $sec = '';
    for ($i = 0; $i < ((strlen($data) - strlen($data) % 117) / 117 + 1); $i++) {
        $sec = '';
        openssl_public_encrypt(substr($data, $i * 117, 117), $sec, $pub_key);
        $crypt_res = $crypt_res . $sec;
    }
    return $crypt_res;
}

function rsa_dec($data,$pri) {
    if (empty($data)) {
        return '';
    }
    $priv_key = openssl_pkey_get_private($pri);
    $decrypt_res = '';
    $sec = '';
    for ($i = 0; $i < ((strlen($data) - strlen($data) % 128) / 128 + 1); $i++) {
        $slip = '';
        openssl_private_decrypt(substr($data, $i * 128, 128), $sec, $priv_key);
        $decrypt_res = $decrypt_res . $sec;
    }
    return $decrypt_res;
}

function rsa_dec_pub($data,$pub) {
    if (empty($data)) {
        return '';
    }
    $publickey = openssl_pkey_get_public($pub);
    $decrypt_res = '';
    openssl_public_decrypt($data,$decrypt_res,$publickey);
    return $decrypt_res;
}