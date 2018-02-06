<?php
error_reporting(0);
define('IN_MOBILE', true);
require '../../framework/bootstrap.inc.php';
global $_W;
$pay_species = $_SESSION['pay_species'];
if($pay_species == 'luxury'){
	header("Location: ".$_W['config']['site']['root'].'/mobile.php?m=third&act=luxury&do=fail_pay');
	exit;
}else if($pay_species == 'luxury_coupon'){
    header("Location: ".$_W['config']['site']['root'].'/mobile.php?m=third&act=luxury_coupon&do=pay_failed');
    exit;
}else if($pay_species == 'recharge38'){
	$cid = isset($_SESSION['pay_cid']) ? $_SESSION['pay_cid'] : '77';
    header("Location: ".$_W['config']['site']['root'].'/mobile.php?m=third&act=recharge38&do=recharge_page&cid='.$cid);
    exit;
}else{
	error_report('支付失败, 请稍后重试.', $_W['config']['site']['root'] . create_url('order/order_list'), $_W['config']['site']['root']);
}
