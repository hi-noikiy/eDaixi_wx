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
if($_GPC['done'] == '1') {
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
	$plid = $params['tid'];
	if(strlen($plid) < 8){
		$sql = 'SELECT * FROM ' . tablename('paylog') . ' WHERE `plid`=:plid';  
	}else{
		$sql = 'SELECT * FROM ' . tablename('paylog') . ' WHERE `tid`=:plid'; 
	}
	$pars = array();
	$pars[':plid'] = $plid;
	$log = pdo_fetch($sql, $pars);
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
	exit;
}
if($_SESSION['user_info']['user_type'] == 18)
{
	unset($_SESSION['user_info']['from_user']);
	unset($_SESSION['user_info']['user_type']);
	unset($_SESSION['mark']);
	$_SESSION['user_info']['is_xiaoe'] = true;
}
get_user_type();

$sql = 'SELECT * FROM ' . tablename('paylog') . ' WHERE `tid`=:tid';
$log = pdo_fetch($sql, array(':tid' => $params['tid']));

if( empty($log) ) {
	header("Location: ".$_W['config']['site']['root'].'payment/alipay/merchant.php');
	exit;
}

if(!empty($log) && $log['status'] != '0') {
	if($log['module'] == 'icard'){
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
} 

// $auth = sha1($sl . $log['weid'] . $_W['config']['setting']['authkey']);
// if($auth != $_GPC['auth']) {
// 	exit('参sudo数传输错误.');
// }


// require_once IA_ROOT.'/framework/model/payment.mod.php';

// //$wOpt = wechat_build($params, $_W['account']['payment']['wechat']);
// $params['fee'] = number_format($log['fee'], 2, '.', '');
// $wOpt = wechat_build_v4($params, $_W['account']['payment']['wechat_v4']);
?>
<!-- <script type="text/javascript">
	document.addEventListener('WeixinJSBridgeReady', function onBridgeReady() {
		WeixinJSBridge.invoke('getBrandWCPayRequest', <?php echo $wOpt;?>, function(res) {
			if(res.err_msg == 'get_brand_wcpay_request:ok') {
				location.search += '&done=1';
			} else {
				if(res.err_msg == "get_brand_wcpay_request:cancel"){
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
</script> -->