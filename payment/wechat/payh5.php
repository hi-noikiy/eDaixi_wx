<?php
/**
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 * $sn: htdocs/payment/wechat/pay.php : v abc6abb4cbaf : 2014/03/20 06:54:44 : veryinf $
 */
error_reporting(0);
define('IN_MOBILE', true);
require '../../framework/bootstrap.inc.php';
global $_W,$_GPC;
$sl = $_GPC['ps'];
$params = @json_decode(base64_decode($sl), true);

$pay_species = $_SESSION['pay_species'];
if($pay_species == 'discount'){
	header("Location: ".$_W['config']['site']['root'].'/mobile.php?m=wap&act=payment&do=pay_success&type=discount');
	exit;
}else if($pay_species == 'icard'){
	header("Location: ".$_W['config']['site']['root'].'/mobile.php?m=wap&act=payment&do=pay_success&type=icard_charge');
	exit;
}else if($pay_species == 'order'){
	if(!empty($_SESSION['user_info']['is_xiaoe']))
	{
		unset($_SESSION['user_info']['is_xiaoe']);
		$_SESSION['user_info']['user_type'] = 18;
		unset($_SESSION['user_info']['from_user']);
		header("Location: ".$_W['config']['xiaoe']['url'].'/order');
		exit;
	}
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

$sql = 'SELECT * FROM ' . tablename('paylog') . ' WHERE `tid`=:tid';
$log = pdo_fetch($sql, array(':tid' => $params['tid']));

if(!empty($log) && $log['status'] != '0') {
	if($log['module'] == 'discount'){
		header("Location: ".$_W['config']['site']['root'].'/mobile.php?m=wap&act=payment&do=pay_success&type=discount');
		exit;
	}else if($log['module'] == 'icard'){
		header("Location: ".$_W['config']['site']['root'].'/mobile.php?m=wap&act=payment&do=pay_success&type=icard_charge');
		exit;
	}else if($log['module'] ==  'order'){
		if($_SESSION['user_info']['user_type'] == 18)
		{
			header("Location: ".$_W['config']['xiaoe']['url'].'/order');
		}else
		{
			header("Location: ".$_W['config']['site']['root'].'/mobile.php?m=wap&act=payment&do=pay_success&type=order');
		}
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
} else {
	header("Location: ".$_W['config']['site']['root'].'/payment/alipay/merchant.php');
	exit;
}

?>