<?php
	// getAccessToken();
 function sendPostRequst($url, $data) {
 	// $data = http_build_query($data);
	$opts = array (
		'http' => array (
		'method' => 'POST',
		// 'header' => 'Content-type: application/x-www-form-urlencoded',
		'header' => 'Content-type: application/json',
		'content' => $data
		) 
	);
	var_dump($data);
	$context = stream_context_create ( $opts );
	$result = file_get_contents ( $url, false, $context );

	return $result;
}
 function getAccessToken() {
	global $_W;
	/*$account['key'] = $_W['config']['app']['appid'];
	$account['secret'] = $_W['config']['app']['secret'];
	$access_token =  account_weixin_token($account);*/
	 $url = "http://wx.rongchain.com/mobile.php?act=module&name=washing&do=iosapi&weid=5";
	$ts = time();
	$params = array('op' =>'access_token', 'ts' => $ts, 'app_token' => 'rongchainapi', 'user_type' => 2);
	$ts .= 'rongchainapi'; 
	$params['sign'] = md5($ts);
	$res = sendPostRequst($url, json_encode($params));
	$access_token = json_decode(json_decode($res)->data)->access_token;
	var_dump($access_token);
	//return $access_token;
}

$res = sendPostRequst('http://127.0.0.1:8092/index.php?r=country/index',json_encode(array('qw' => '11111')));

var_dump($res);

	/*function sendPostRequst($url, $data) {
		$opts = array (
			'http' => array (
				'method' => 'POST',
				'header' => 'Content-type: application/x-www-form-urlencoded',
				'content' => $data
			) 
			);
		$context = stream_context_create ( $opts );
		$result = file_get_contents ( $url, false, $context );

		return $result;
	  }
  function getAccessToken() {
     
    	$url = "http://localhost:8080/mobile.php?m=third&act=getinfo&do=get_access_token";
	$ts = time();
	$params = array('op' =>'access_token', 'ts' => $ts, 'app_token' => 'rongchainapi', 'user_type' => 2);
	$ts .= 'rongchainapi'; 
	$params['sign'] = md5($ts);
	$res = sendPostRequst($url, json_encode($params));
	var_dump($res);
	$access_token = json_decode(json_decode($res)->data)->access_token;
	var_dump($access_token);
//	return $access_token;
  }*/