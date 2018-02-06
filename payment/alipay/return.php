<?php

error_reporting(0);
define('IN_MOBILE', true);
if(empty($_GET['out_trade_no'])) {
	exit('request failed.');
}
$_POST['weid'] = 5;
require '../../framework/bootstrap.inc.php';
global $_W;
if(empty($_W['config']['payment'])) {
	exit('request failed.');
}
$alipay = $_W['config']['payment']['alipay'];
if(empty($alipay)) {
	exit('request failed.');
}
$prepares = array();
foreach($_GET as $key => $value) {
	if($key != 'sign' && $key != 'sign_type') {
		$prepares[] = "{$key}={$value}";
	}
}
sort($prepares);
$string = implode($prepares, '&');
$string .= $alipay['secret'];
$sign = md5($string);
if($sign == $_GET['sign'] && $_GET['result'] == 'success') {
	$pay_species = $_SESSION['pay_species'];
	if($_SESSION['user_info']['user_type'] == '18' || $_SESSION['mark'] == 'eservice')
	{
		$url = $_W['config']['xiaoe']['url'].'/order';
		header('Location: ' . $url);	
	}
	
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
	
	$plid = $pieces[2];
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
		$cid = isset($_SESSION['pay_cid']) ? $_SESSION['pay_cid'] : '77';
		unset($_SESSION['pay_cid']);
		unset($_SESSION['pay_species']);
	    header("Location: ".$_W['config']['site']['root'].'/mobile.php?m=third&act=recharge38&do=recharge_page&cid='.$cid);
	    exit;
	}
}
