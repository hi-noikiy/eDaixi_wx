<?php
/**
 * 微站管理
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */
define('IN_MOBILE', true);
if (0) {
	error_reporting(-1);
	ini_set('display_errors', 1);
} else {
	error_reporting(-1);
	ini_set('display_errors', 0);
	/*
    ini_set('log_errors', 1);
    $log_name = str_replace("\\", '/', dirname(__FILE__)) . '/data/logs/weixin_500error.log';
    ini_set('error_log', $log_name);
    */
}

require 'framework/bootstrap.inc.php';
require 'framework/controller/rout.ctrl.php';
global $_GPC;

if($_GPC['do'] == 'iosapi' && $_GPC['name'] = 'washing' && $_GPC['act'] = 'module'){
	$_GPC['m'] = 'third';
	$_GPC['act'] = 'getinfo';
	$_GPC['do'] = 'get_access_token';
}
if($_GPC['m'] && $_GPC['act'] && $_GPC['do']){
	$module = $_GPC['m'];
	$controller = $_GPC['act'];
	$method = $_GPC['do'];
}else{
	$module = $_GPC['m'] = 'wap';
	$controller = $_GPC['act'] = 'homepage';
	$method = $_GPC['do'] = 'index';
}
if(in_array($module, array('wap', 'third'))){
	get_user_type();
}
Rout::create_module($module, $controller, $method);
