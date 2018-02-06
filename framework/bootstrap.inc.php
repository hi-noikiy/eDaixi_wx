<?php
define('IN_IA', true);
define('IA_ROOT', str_replace("\\", '/', dirname(dirname(__FILE__))));
define('MAGIC_QUOTES_GPC', (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) || @ini_get('magic_quotes_sybase'));
$_W = $_GPC = array();
require IA_ROOT . '/framework/class/db.class.php';
require IA_ROOT . '/framework/function/global.func.php';
require IA_ROOT . '/framework/function/pdo.func.php';
//require IA_ROOT . '/framework/controller/wap/base.class.php';
require IA_ROOT . '/framework/function/communication.func.php';
require IA_ROOT . '/framework/function/memcached.fun.php';
require IA_ROOT . '/framework/class/session.class.php';
require IA_ROOT . '/framework/function/template.func.php';
require IA_ROOT . '/framework/library/edaixi/open_server.class.php';
require IA_ROOT . '/framework/library/edaixi/api_server.class.php';
require IA_ROOT . '/framework/model/user.class.php';
require IA_ROOT . '/framework/library/edaixi/alioauth.class.php';
require IA_ROOT . '/framework/library/edaixi/modelLoader.class.php';
require IA_ROOT . '/framework/library/edaixi/helper.class.php';
require IA_ROOT . '/framework/model/edxModel.class.php';

define('HTTP_TYPE', http_type());

if(MAGIC_QUOTES_GPC) {
	$_GET = istripslashes($_GET);
	$_POST = istripslashes($_POST);
	$_COOKIE = istripslashes($_COOKIE);
}
$_GPC = array_merge($_GET, $_POST, $_GPC);
$_GPC = ihtmlspecialchars($_GPC);
require IA_ROOT . '/data/config.php';

$_W['config'] = $config;
date_default_timezone_set($_W['config']['setting']['timezone']);
$_W['account']['payment'] = $config['payment'];
$_W['weid'] = '5';
WeSession::start();

