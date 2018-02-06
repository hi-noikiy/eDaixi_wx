<?php
/**
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 * $sn$
 */
$input = file_get_contents('php://input');
/*$input = "
<xml><OpenId><![CDATA[oMWhZtzsiB64NWO-8AlMhUf8Z89g]]></OpenId>
<AppId><![CDATA[wx1defeb5ede48566f]]></AppId>
<TimeStamp>1397358158</TimeStamp>
<MsgType><![CDATA[reject]]></MsgType>
<FeedBackId>13200121e633144966643</FeedBackId>
<TransId><![CDATA[1218405551201404133203605474]]></TransId>
<Reason><![CDATA[我的衣服清洗不干净]]></Reason>
<Solution><![CDATA[希望重洗]]></Solution>
<ExtInfo><![CDATA[ 18635132526]]></ExtInfo>
<AppSignature><![CDATA[4953d9de35fdd255210b9a2d6e7af46d205d280f]]></AppSignature>
<SignMethod><![CDATA[sha1]]></SignMethod>
</xml>";
*/

$obj = simplexml_load_string($input, 'SimpleXMLElement', LIBXML_NOCDATA);
if($obj instanceof SimpleXMLElement && !empty($obj->FeedBackId)) {
	$feed_data = array(
		'openid' => trim($obj->OpenId),
		'appid' => trim($obj->AppId),
		'timestamp' => trim($obj->TimeStamp),
		'msgtype' => trim($obj->MsgType),
		'feedbackid' => trim($obj->FeedBackId),
		'reason' => trim($obj->Reason),
		'appsignature' => trim($obj->AppSignature),
		'signmethod' => trim($obj->SignMethod),
		);

	if($feed_data['msgtype'] == 'request'){
		$feed_data['solution'] =trim($obj->Solution);
		$feed_data['extinfo'] = trim($obj->ExtInfo);
		$feed_data['transid'] = trim($obj->TransId);
	}

	/*if (!empty($obj->PicInfo) && !empty($obj->PicInfo->item)) {
		foreach ($obj->PicInfo->item as $item) {
			$data['picinfo'][] = trim($item->PicUrl);
		}
	}*/
	require '../../source/bootstrap.inc.php';
	WeUtility::logging('pay-rights', $input);
	$wechat = pdo_fetch("SELECT weid, payment, `key`, secret FROM ".tablename('wechats')." WHERE `key` = :key", array(':key' => $feed_data['appid']));
	$_W['weid'] = $wechat['weid'];
	if (empty($wechat['payment'])) {
		exit('failed');
	}
	$wechat['payment'] = iunserializer($wechat['payment']);
	$feed_data['appkey'] = $wechat['payment']['wechat']['signkey'];
	if (!checkSign($feed_data)) {
		exit('failed');
	}

	$fans = pdo_fetch ( "SELECT * FROM " . tablename ( 'fans' ) . " WHERE from_user = :from_user", array (
		':from_user' => $feed_data['openid'] ) );
	if (!empty($fans)) {
		$client = pdo_fetch ( "SELECT * FROM " . tablename ( 'clients' ) . " WHERE openid = :openid", array (
        ':openid' => $feed_data['openid'] ) );

		$data = array(
			"secret_key"=>"rongchangApi333",
			"right" => array(
				"user_id" => $fans['id'],
				"phone" => $fans['mobile'],
				"token" => $fans['from_user'],
        "channel" => "weixin",//微信维权
        "user_name" => $fans['nickname'],
       'feedbackid' => $feed_data['feedbackid'],//是 维权单号
       'client_id' => $client['id'],
       'message_type' => 0,
		  ) 
			);
		$data['right']['feed_status'] =  $feed_data['msgtype'];
		if($feed_data['msgtype'] == 'request') {
			$data['right']['transid'] =  $feed_data['transid'];
			$data['right']['reason'] =  $feed_data['reason'];
			$data['right']['solution'] =  $feed_data['solution'];
			$data['right']['extinfo'] =  $feed_data['extinfo'];
		}elseif($feed_data['msgtype'] == 'confirm' || $feed_data['msgtype'] == 'reject' ) {
			$data['right']['reason'] =  $feed_data['reason'];
		}else{
			exit('feed status empty.');
		}

		$url = $_W['config']['site']['kefu']."rightapi/v1/rights/".$feed_data['msgtype'];
		$return_content = ihttp_post($url, $data);
	}else{
		exit('fans empty');
	}
}
function checkSign($data) {
	$string = '';
	$keys = array('appid', 'timestamp', 'openid', 'appkey');
	sort($keys);
	foreach($keys as $key) {
		$v = $data[$key];
		$key = strtolower($key);
		$string .= "{$key}={$v}&";
	}
	$string = sha1(rtrim($string, '&'));
	if ($data['appsignature'] == $string) {
		return true;
	} else {
		return false;
	}
}
