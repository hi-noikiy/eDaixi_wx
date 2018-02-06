<?php
error_reporting(0);
define('IN_MOBILE', true);
$get = $_GET;
$_POST['weid'] = 5;
require '../../source/bootstrap.inc.php';
require_once IA_ROOT . '/source/library/edaixi/api_server.class.php';

if(is_array($_W['account']['payment'])) {
	$wechat = $_W['account']['payment']['wechat'];
	if(!empty($wechat)) {
		ksort($get);
		$string1 = '';
		foreach($get as $k => $v) {
			if($v != '' && $k != 'sign') {
				$string1 .= "{$k}={$v}&";
			}
		}
		$sign = strtoupper(md5($string1 . "key={$wechat['key']}"));
		
		if($sign == $get['sign']) {
			$plid = $get['out_trade_no'];
			if(strlen($plid) < 8){
				$sql = 'SELECT * FROM ' . tablename('paylog') . ' WHERE `plid`=:plid';  
			}else{
				$sql = 'SELECT * FROM ' . tablename('paylog') . ' WHERE `tid`=:plid'; 
			}
			
			$params = array();
			$params[':plid'] = $plid;
			$log = pdo_fetch($sql, $params);
			if(!empty($log) && $log['status'] == '0') {
				$record = array();
				$record['status'] = '1';
				$tag = array();
				$tag['transaction_id'] = $get['transaction_id'];
				$record['tag'] = iserializer($tag);
				pdo_update('paylog', $record, array('plid' => $log['plid']));

				$api_server = new ApiServer($_W['config']);
				$ret = $api_server->paylog_success($log['plid']);

				exit('success');
			}
		}
	}
}
exit('fail');
