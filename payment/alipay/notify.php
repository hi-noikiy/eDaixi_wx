<?php
error_reporting(0);
define('IN_MOBILE', true);

require '../../source/bootstrap.inc.php';
require_once IA_ROOT . '/source/library/edaixi/api_server.class.php';

$obj = simplexml_load_string($_POST['notify_data'], 'SimpleXMLElement', LIBXML_NOCDATA);
if($obj instanceof SimpleXMLElement && $obj->out_trade_no) {
	$out_trade_no = strval($obj->out_trade_no);
	$pieces = explode('alipay', $out_trade_no);
	if(is_array($pieces) && count($pieces) > 2) {
		$_GET['weid'] = $pieces[1];
		if(is_array($_W['account']['payment'])) {
			$alipay = $_W['account']['payment']['alipay'];
			if(!empty($alipay)) {
				$string = "service={$_POST['service']}&v={$_POST['v']}&sec_id={$_POST['sec_id']}&notify_data={$_POST['notify_data']}";
				$string .= $alipay['secret'];
				$sign = md5($string);
				if($sign == $_POST['sign']) {
					$plid = $pieces[2];
					$trade_no = strval($obj->trade_no);

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
						$tag['transaction_id'] = $trade_no;
						$record['tag'] = iserializer($tag);
						pdo_update('paylog', $record, array('plid' => $log['plid']));

						$api_server = new ApiServer($_W['config']);
						$ret = $api_server->paylog_success($log['plid']);

						exit('success');
					}
				}
			}
		}
	}
}
exit('fail');
