<?php

error_reporting(0);
define('IN_MOBILE', true);

require '../../framework/bootstrap.inc.php';
global $_W;

$baidu = $_W['config']['payment']['baidu'];
if(empty($baidu)) {
	exit('request failed.');
}

$arr_params = $_GET;
$order_no = $arr_params ['order_no'];
// 检查商户ID是否是自己，如果传过来的sp_no不是商户自己的，那么说明这个百付宝的支付结果通知无效
if ($baidu['sp_no'] != $arr_params ['sp_no']) {
	exit('the id in baifubao notify is wrong, this notify is invaild');
}
// 检查支付通知中的支付结果是否为支付成功
if (1 != $arr_params ['pay_result']) {
	exit('the pay result in baifubao notify is wrong, this notify is invaild');
}

$sign = $arr_params ['sign'];
unset($arr_params ['sign']);
foreach ($arr_params as &$value) {
	$value = urldecode($value); // URL编码的解码
}
unset($value);

// 签名校验
ksort($arr_params);

$arr_temp = array ();

$arr_params['key'] = $baidu['sp_key'];
foreach ($arr_params as $key => $val) {
	$arr_temp [] = $key . '=' . $val;
}

$sign_str = implode('&', $arr_temp);
$my_sign = md5($sign_str);

if ($sign != $my_sign) {
	exit('baifubao notify sign failed');
}



if($arr_params['pay_result'] == 1) {
	$pay_species = $_SESSION['pay_species'];
	if($pay_species == 'discount'){
		header("Location: ".$_W['config']['site']['root'].'/mobile.php?m=wap&act=payment&do=pay_success&type=discount');
		exit;
	}else if($pay_species == 'icard'){
		header("Location: ".$_W['config']['site']['root'].'/mobile.php?m=wap&act=payment&do=pay_success&type=icard_charge');
		exit;
	}else if($pay_species == 'order'){
		header("Location: ".$_W['config']['site']['root'].'/mobile.php?m=wap&act=payment&do=pay_success&type=order');
		exit;
	}else if($pay_species == 'luxury'){
		header("Location: ".$_W['config']['site']['root'].'/mobile.php?m=third&act=luxury&do=success_pay');
		exit;
	}else if($pay_species == 'luxury_coupon'){
		header("Location: ".$_W['config']['site']['root'].'/mobile.php?m=third&act=luxury_coupon&do=pay_success');
		exit;
	}else if($pay_species == 'recharge38'){
		$cid = isset($_SESSION['pay_cid']) ? $_SESSION['pay_cid'] : '77';
		unset($_SESSION['pay_cid']);
		unset($_SESSION['pay_species']);
	    header("Location: ".$_W['config']['site']['root'].'/mobile.php?m=third&act=recharge38&do=recharge_page&cid='.$cid);
	    exit;
	}
	
	$plid = $order_no;
	if(strlen($plid) < 8){
		$sql = 'SELECT * FROM ' . tablename('paylog') . ' WHERE `plid`=:plid';
	}else{
		$sql = 'SELECT * FROM ' . tablename('paylog') . ' WHERE `tid`=:plid'; 
	}
	$params = array();
	$params[':plid'] = $plid;
	$log = pdo_fetch($sql, $params);
	if($log['module'] == 'discount'){
		header("Location: ".$_W['config']['site']['root'].'/mobile.php?m=wap&act=payment&do=pay_success&type=discount');
		exit;
	}else if($log['module'] == 'icard'){
		header("Location: ".$_W['config']['site']['root'].'/mobile.php?m=wap&act=payment&do=pay_success&type=icard_charge');
		exit;
	}else if($log['module'] ==  'order'){
		header("Location: ".$_W['config']['site']['root'].'/mobile.php?m=wap&act=payment&do=pay_success&type=order');
		exit;
	}else if ($log['module' == 'luxury_vip']){
		header("Location: ".$_W['config']['site']['root'].'/mobile.php?m=third&act=luxury&do=success_pay');
		exit;
	}else if ($log['module' == 'luxury_coupon']){
		header("Location: ".$_W['config']['site']['root'].'/mobile.php?m=third&act=luxury_coupon&do=pay_success');
		exit;
	}else if($pay_species == 'recharge38'){
	    header("Location: ".$_W['config']['site']['root'].'/mobile.php?m=third&act=recharge38&do=recharge_page&cid=77');
	    exit;
	}
}
