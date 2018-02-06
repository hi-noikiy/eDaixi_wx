<?php
/**
 * 整理下单成功返回url判断
 * 根据$_SESSION中的 pay_species 返回 url
 */
function return_url_by_pay_species(){
	global $_W;
	// 默认返回url
	$url = $_W['config']['site']['root'].'/mobile.php?m=wap&act=payment&do=pay_success&type=order';
	$pay_species = $_SESSION['pay_species'];
	if($_SESSION['user_info']['user_type'] == '18' || $_SESSION['mark'] == 'eservice')
	{
		$url = $_W['config']['xiaoe']['url'].'/order';
	}
	
	if($pay_species == 'discount'){
		$url = $_W['config']['site']['root'].'/mobile.php?m=wap&act=payment&do=pay_success&type=discount';
	}else if($pay_species == 'icard'){
		$url = $_W['config']['site']['root'].'/mobile.php?m=wap&act=payment&do=pay_success&type=icard_charge';
	}else if($pay_species == 'order'){
		$url = $_W['config']['site']['root'].'/mobile.php?m=wap&act=payment&do=pay_success&type=order';
	}else if($pay_species == 'luxury'){
		$url = $_W['config']['site']['root'].'/mobile.php?m=third&act=luxury&do=success_pay';
	}else if($pay_species == 'luxury_coupon'){
		$url = $_W['config']['site']['root'].'/mobile.php?m=third&act=luxury_coupon&do=pay_success';
	}else if($pay_species == 'recharge38'){
		$cid = isset($_SESSION['pay_cid']) ? $_SESSION['pay_cid'] : '77';
		unset($_SESSION['pay_cid']);
		unset($_SESSION['pay_species']);
	    $url = $_W['config']['site']['root'].'/mobile.php?m=third&act=recharge38&do=recharge_page&cid='.$cid;
	}
	return $url;
}
/**
 *
 *
 */

 function http_type() {
	if($_SERVER['HTTPS'] === 1){  //Apache  
        return "https://";
    }elseif($_SERVER['HTTPS'] === 'on'){ //IIS  
        return "https://";
    }elseif ($_SERVER['SERVER_PORT'] == 443){	//other
    	return "https://";
    }elseif($_SERVER['HTTP_X_CLIENT_PROTO'] == 'https'){
    	return "https://";
    }
    return "http://";
 }


/**
 * 转义引号字符串
 * 支持单个字符与数组
 *
 * @param string or array $var
 * @return string or array
 *			 返回转义后的字符串或是数组
 */
function istripslashes($var) {
	if (is_array($var)) {
		foreach ($var as $key => $value) {
			$var[stripslashes($key)] = istripslashes($value);
		}
	} else {
		$var = stripslashes($var);
	}
	return $var;
}

/**
 * 转义字符串的HTML
 * @param string or array $var
 * @return string or array
 *			 返回转义后的字符串或是数组
 */
function ihtmlspecialchars($var) {
	if (is_array($var)) {
		foreach ($var as $key => $value) {
			$var[htmlspecialchars($key)] = ihtmlspecialchars($value);
		}
	} else {
		$var = str_replace('&amp;', '&', htmlspecialchars($var, ENT_QUOTES));
	}
	return $var;
}
/**
 * 构造错误数组
 *
 * @param int $errno 错误码，0为无任何错误。
 * @param string $message 错误信息，通知上层应用具体错误信息。
 * @return array
 */
function error($code, $msg = '') {
	return array(
		'errno' => $code,
		'message' => $msg,
	);
}

/**
 * 生成URL
 * @param string $router
 * @param array $params
 * @return string
 */
function create_url($router, $params = array(), $module = null) {
	global $_GPC;
	list($controller, $function) = explode('/', $router);
	$url = '/mobile.php?m=' . ($module ? $module : 'wap') . '&act=' . $controller . '&do=' . $function;
	// 让 URL 携带 城市ID
	if(empty($params['city_id'])){
		$user_city = get_user_city();
		$city_id = $user_city['city_id'];
		$params['city_id'] = empty($city_id) ? 1 : $city_id;
	}
	// 让 URL 携带 Mark
	if(!isset($params['mark'])){
		$mark = get_mark();
		$params['mark'] = isset($mark) ? $mark : '';
	}
	// 过滤空参数
	$params = array_filter($params, function($val){
		if(!isset($val) || $val === '')
			return false;
		return true;
	});
	if(!empty($params)){
		$query = http_build_query($params, '', '&');
		$url .=  '&' . $query;
	}
	return $url;
}

/**
 * 写入cookie值
 * @param string $key
 *			 cookie名称
 * @param string $value
 *			 cookie值
 * @param int $maxage
 *			 cookie的生命周期,当前时间开始的$maxage秒
 * @return boolean
 */
function isetcookie($key, $value, $maxage = 0) {
	global $_W;
	$expire = $maxage != 0 ? time() + $maxage : 0;
	return setcookie($_W['config']['cookie']['pre'] . $key, $value, $expire, $_W['config']['cookie']['path'], $_W['config']['cookie']['domain']);
}

/**
 * huoqu cookie值
 * @param string $key
 *			 cookie名称
 */
function igetcookie($key) {
	global $_W;
	$key = $_W['config']['cookie']['pre'] . $key;
	$value = $_COOKIE[$key];
	return $value;
}

/**
 * 使用SDK执行接口请求
 * @param unknown $request
 * @param string $token
 * @return Ambigous <boolean, mixed>
 */
function strexists($string, $find) {
	return !(strpos($string, $find) === FALSE);
}

/**
 * 消息提示窗
 * @param string $msg
 * 提示消息内容
 *
 * @param string $redirect
 * 跳转地址
 *
 * @param string $type 提示类型
 * 		success		成功
 * 		error		错误
 * 		question	询问(问号)
 * 		attention	注意(叹号)
 * 		tips		提示(灯泡)
 * 		ajax		json
 */
function message($msg, $redirect = '', $type = '', $button = "", $hidden = "") {
	global $_W;
	if ($redirect == 'refresh') {
		$redirect = $_W['script_name'] . '?' . $_SERVER['QUERY_STRING'];
	}
	if ($redirect == '') {
		$type = in_array($type, array('success', 'error', 'tips', 'ajax', 'sql')) ? $type : 'error';
	} else {
		$type = in_array($type, array('success', 'error', 'tips', 'ajax', 'sql')) ? $type : 'success';
	}
	if (!empty($_W['isajax']) || $type == 'ajax') {
		$vars = array();
		$vars['message'] = $msg;
		$vars['redirect'] = $redirect;
		$vars['type'] = $type;
		exit(json_encode($vars));
	}
	if (defined('IN_MOBILE')) {
		$message = "<script type=\"text/javascript\">alert('$msg');";
		$redirect && $message .= "location.href = \"{$redirect}\";";
		$message .= "</script>";
		include template('base/message');
		exit();
	}
	if (empty($msg) && !empty($redirect)) {
		header('Location: ' . $redirect);
		exit;
	}
	include template('common/message', TEMPLATE_INCLUDEPATH);
	exit();
}

/**
 * 递归创建目录树
 * @param string $path 目录树
 * @return bool
 */
function mkdirs($path) {
	if (!is_dir($path)) {
		mkdirs(dirname($path));
		mkdir($path);
	}
	return is_dir($path);
}

/**
 * 生成长度为length的字符串
 */
function random($length, $numeric = 0) {
	$seed = base_convert(md5(microtime() . $_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
	$seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));
	if ($numeric) {
		$hash = '';
	} else {
		$hash = chr(rand(1, 26) + rand(0, 1) * 32 + 64);
		$length--;
	}
	$max = strlen($seed) - 1;
	for ($i = 0; $i < $length; $i++) {
		$hash .= $seed{mt_rand(0, $max)};
	}
	return $hash;
}

// 记录日志，数据格式支持字符串、数字、一维数组
function logging($title='info', $data='', $mode='a+', $fname='') {
	$fpath = IA_ROOT . '/data/logs/';
	if(!$fname){
		$filename = $fpath . date('Ymd') . '.log';
	}else{
		$filename = $fpath . 'weixin_' . $fname . '.log';
	}
	mkdirs(dirname($filename));
	$content = $title . ' [' . date('Y-m-d H:i:s') . ']';
	$content .= "\n------------------------------------------------------------------------------------------------\n";
	if (is_string($data) || is_numeric($data)) {
		$content .= "{$data}\n";
	}
	if (is_array($data)) {
		foreach ($data as $key => $value) {
			$content .= sprintf("%s : %s ;\n", $key, $value);
		}
	}
	$content .= "\n";
	$mode = $mode == 'a+' ? 'a+' : 'w+';
	$fp = fopen($filename, $mode);
	fwrite($fp, $content);
	fclose($fp);
}

function assets_link($str) {
	global $_W;
	$host = $_W['config']['setting']['assets_host'];
	$setting_ts = $_W['config']['setting']['ts'];
	$link = '';
	if ($_W['config']['setting']['test']) {
		$link = $host . $setting_ts . $str . '?' . time();
	} else {
		$link = $host . $setting_ts . $str;
	}
	return $link;
}

function error_report($message, $url = null, $host = '') {
	include template('error_report', 'wap', 'base');
	exit;
}

/**
* 前后端分离，用于输出指定格式的json
* @param $ret 状态
*        $version 版本
*        $data 数据
*        $error 错误信息
*/
function echo_json($ret, $data="", $error="", $version="1.0"){
	header('Content-type: application/json', true);
	$rt = array();
	$rt['ret'] = $ret;
	if($ret){
		$rt['ts'] = time();
		$rt['version'] = $version;
		$rt['data'] = $data ?: array();
	}else{
		$rt['error'] = $error;
	}
	exit(json_encode($rt));
}

/**
 * 获取客户ip
 * @return string
 *			 返回IP地址
 *			 如果未获取到返回unknown
 */
if (!function_exists('getip')) {
	function getip() {
		static $ip = '';
		$ip = $_SERVER['REMOTE_ADDR'];
		if (isset($_SERVER['HTTP_CDN_SRC_IP'])) {
			$ip = $_SERVER['HTTP_CDN_SRC_IP'];
		} elseif (isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) AND preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
			foreach ($matches[0] AS $xip) {
				if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {
					$ip = $xip;
					break;
				}
			}
		}
		return $ip;
	}
}

/**
 * 返回完整数据表名(加前缀)
 * @param string $table
 * @return string
 */
if (!function_exists('tablename')) {
	function tablename($table) {
		return "`{$GLOBALS['_W']['config']['db']['tablepre']}{$table}`";
	}
}

/**
 * 
 * 解序列化
 * @param array $value
 */

if (!function_exists('iunserializer')) {
	function iunserializer($value) {
		if (empty($value)) {
			return '';
		}
		if (!is_string($value)) {
			return $value;
		}
		$result = unserialize($value);
		return $result;
	}
}

function get_user_type() {
	global $_W, $_GPC;
	$mark = get_mark();

	if($mark == 'eservice')		//	小E管家  	
	{	
		require IA_ROOT . '/framework/library/xiaoe/xiaoe.class.php';
		if(!empty($_SESSION['user_info']['xiaoe_redirectUrl']))
		{
			$redirectUrl = $_SESSION['user_info']['xiaoe_redirectUrl'];
		}else
		{
			$redirectUrl = HTTP_TYPE.$_SERVER['HTTP_HOST'].'/mobile.php?m=wap&act=homepage&do=index&mark=eservice';
		}
		$xiaoe = new xiaoe($_W['config']);
		if(isset($_GPC['code']))
		{
			if($_GPC['code'] == '0')
			{
				unset($_SESSION['user_info']);
			}else
			{
				$user_info = $xiaoe->get_user_info($redirectUrl,$_GPC['code']);
				unset($_SESSION['user_info']['xiaoe_redirectUrl']);
				if($user_info->open_id)
				{
					unset($_SESSION['user_info']);
					$_SESSION['user_info']['from_user'] = empty($user_info->open_id) ? '':$user_info->open_id;
					$_SESSION['user_info']['phone'] = empty($user_info->tel) ? '':$user_info->tel;
				}else if($_SESSION['user_info']['user_type'] != 18)
				{
					unset($_SESSION['user_info']);
				}
			}
		}else
		{	if ((!empty($_SESSION['user_info']['from_user']) &&
				 $_SESSION['user_info']['user_type'] != 18) ||
				empty($_SESSION['user_info']['from_user'])) {
				//没有管家oauth的code, 则每次去拿
				$redirectUrl = HTTP_TYPE.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
				$_SESSION['user_info']['xiaoe_redirectUrl'] = $redirectUrl;
				$xiaoe->get_code($redirectUrl);
			} else {
				unset($_SESSION['user_info']['user_id']);
				unset($_SESSION['user_info']['user_token']);
				unset($_SESSION['user_info']['is_login']);
			}
		}
		$_SESSION['user_info']['user_type'] = 18;
		return;
	}else if(isset($_SESSION['user_info']['user_type']) &&
		     $_SESSION['user_info']['user_type'] == 18)
	{
		unset($_SESSION['user_info']['user_token']);
		unset($_SESSION['user_info']['user_id']);
		unset($_SESSION['user_info']['from_user']);
		unset($_SESSION['user_info']['user_type']);
		unset($_SESSION['user_info']['is_login']);
	}

	//2016双11，h5/app 联合登录渠道专用(app端将user_type拼在session_id后面)
	if(isset($_GET['from']) && $_GET['from']=='app'){
		$session_arr = explode('_', session_id());
		//若没有user_type，证明是旧版app，不作处理
		if(!isset($session_arr[1])){
			return;
		}
		$_SESSION['user_info']['user_type'] = $session_arr[1];
		//若app未登录，则$session_arr[0]为空
		if(!empty($session_arr[0])){
			$open_server = new OpenServer($_W['config']);
			$resp = $open_server->get_user_by_sessionid($session_arr[0], $session_arr[1]);
			if(!empty($resp)){
				$_SESSION['user_info']['user_id'] = $resp['user_id'];
				$_SESSION['user_info']['user_token'] = $resp['user_token'];
				$_SESSION['user_info']['is_login'] = $resp['tel'];
			}else{
				if(is_ajax()){
					//session_id过期，通知前端，前端通知app更新
					echo_json(false, '', '40001');
				}
			}
		}
		return ;
	}

	if(isset($_GPC['open_token'])) {
		if(!empty($_SESSION['putao_token']) && $_SESSION['putao_token'] != $_GPC['open_token']) {
			session_destroy();
		}
		$_SESSION['putao_token'] = $_GPC['open_token'];
	}
	if(isset($_GET['bd_source_light']) && !empty($_GET['bd_source_light'])) {
		$bd = array();
		$bd['bd_from_id'] = !empty($_GET['bd_from_id']) ? $_GET['bd_from_id'] : '';
		$bd['bd_ref_id'] = !empty($_GET['bd_ref_id']) ? $_GET['bd_ref_id'] : '';
		$bd['bd_channel_id'] = !empty($_GET['bd_channel_id']) ? $_GET['bd_channel_id'] : '';
		$bd['bd_sub_page'] = !empty($_GET['bd_sub_page']) ? $_GET['bd_sub_page'] : '';
		$_SESSION['bd_remark'] = $bd;
	}
	if(!empty($_SESSION['bd_remark']) && !empty($_SESSION['user_info']['user_id'])) {
		set_user_remark($_SESSION['user_info']['user_id'],$_SESSION['bd_remark']);
	}

	if(isset($_GET['mid']) && !empty($_GET['mid'])) {
		$remark = array();
		$remark['mid'] = $_GET['mid'];
		$remark['groupId'] = $_GET['groupId'];
		$_SESSION['shihui_remark'] = $remark;
	}
	if(!empty($_SESSION['shihui_remark']) && !empty($_SESSION['user_info']['user_id'])) {
		set_user_remark($_SESSION['user_info']['user_id'],$_SESSION['shihui_remark']);
	}

	if($_SESSION['user_info']['user_type']==34&& !empty($_GET['accessToken'])) {
		if(!empty($_SESSION['user_info']['accessToken']) && !empty($_GET['accessToken']) && $_SESSION['user_info']['accessToken'] != $_GET['accessToken']){
			//unset($_SESSION['user_info']);
			$_SESSION['user_info'] = [];
		}
		$_SESSION['user_info']['accessToken'] = $_GET['accessToken'];
	}
	if(isset($_GET['commId']) && !empty($_GET['commId'])) {
		$remark = array();
		$remark['commId'] = $_GET['commId'];
		$_SESSION['yijiequ_remark'] = $remark;
	}
	if(!empty($_SESSION['yijiequ_remark']) && !empty($_SESSION['user_info']['user_id'])) {
		set_user_remark($_SESSION['user_info']['user_id'],$_SESSION['yijiequ_remark']);
	}
	// 搜狗Mark对应一般浏览器渠道
	if($mark == 'ae891810-4aef-11e5-ade6-f80f41fd4734'){
		if($_SESSION['user_info']['from_user']){
			unset($_SESSION['user_info']);
		}
		$_SESSION['user_info']['user_type'] = 20;
		$hash = '';
		return;
	}
	// 慧社区Mark对应慧社区（微信）渠道
	if($mark == '85cd66ba-72ff-11e5-a990-5cb901892a54'){
		$_SESSION['user_info']['user_type'] = 17;
		$hash = '';
		return;
	}
	$from = isset($_GPC['from']) ? $_GPC['from'] : '';
	if(!empty($_SESSION['user_info']['from_user'])&&$_SESSION['user_info']['user_type']==28&&isset($_GPC['code'])) {
		unset($_SESSION);
	}

	if(!empty($_SESSION['user_info']['from_user'])&&$_SESSION['user_info']['user_type']==29&&isset($_GPC['third_login'])) {
		//unset($_SESSION['user_info']);
		$_SESSION['user_info'] = [];
		//$_SESSION = array();
	}

	if(!empty($_SESSION['user_info']['from_user'])&&$_SESSION['user_info']['user_type']==14&&(isset($_GPC['token']) && empty($_GPC['token']))) {
		$_SESSION['user_info'] = [];
	}

	if (empty($_SESSION['user_info']['from_user'])) {
		$http_user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
		switch ($http_user_agent) {
			//浦发周边通是微信浏览器...提前判断...
			case (strtolower($mark) == strtolower('1470980510nEBvIihl') || $_SESSION['user_info']['user_type'] == 26):
            	$open_userid = $_GPC['open_userid'];
            	$phone = $_GPC['phone'];
            	$sign = $_GPC['sign'];
            	$source = $_GPC['source'];
            	$_SESSION['psdzbt_source'] = $source;
            	$_SESSION['user_info']['user_type'] = 26;
            	$hash = 'psd_zbt&open_userid='.$open_userid.'&phone='.$phone.'&source='.$source.'&sign='.$sign;
            	break;
            case (stripos(strtolower($mark),strtolower('1464841944d74fQXeC')) === 0 || $_SESSION['user_info']['user_type'] == 24):
            	$userId = $_GPC['userId'];
                $userName = $_GPC['userName'];
                $userMobile = $_GPC['userMobile'];
                $commId = $_GPC['commId'];
                $appKey = $_GPC['appKey'];
                $_SESSION['user_info']['user_type'] = 24;
                $hash = 'yijiequ&userId='.$userId.'&userName='.$userName.'&userMobile='.$userMobile.'&appKey='.$appKey.'&commId='.$commId;
                break;
			case strexists($http_user_agent, 'micromessenger'):
				$hash = 'weixin';
				$_SESSION['user_info']['user_type'] = 1;
				break;
			case strexists($http_user_agent, 'alipayclient'):
				$hash = 'alioauth';
				$_SESSION['user_info']['user_type'] = 6;
				break;
			case strexists($http_user_agent, 'miuiyellowpage'):
				$hash = 'xiaomi';
				$_SESSION['user_info']['user_type'] = 11;
				break;
			case strexists($http_user_agent, '360appstore'):
				$_SESSION['user_info']['user_type'] = 12;
				$hash = 'qihoo';
				break;
			case (strexists($http_user_agent, 'baiduboxapp') ||
				  (strtolower($from) == 'zhida') ||
				  (isset($_SESSION['user_info']['user_type']) &&
				   $_SESSION['user_info']['user_type'] == 13)):
                $_SESSION['user_info']['user_type'] = 13;
                if(!allow_visit())
                    $hash = 'zhida';
                else
                	$hash = '';
                break;
			case isset($_GPC['open_token']):
				$_SESSION['user_info']['user_type'] = 15;
				$hash = 'putao';
				break;
			case strexists($http_user_agent, 'qq/'):
				$_SESSION['user_info']['user_type'] = 16;
				$hash = 'shouq';
				break;
			case strexists($http_user_agent, '360around'):
				$_SESSION['user_info']['user_type'] = 19;
				$hash = '';
				break;
			case (strtolower($mark) == strtolower('1457594435nbp8N6Ch') || $_SESSION['user_info']['user_type'] == 21):
                $_SESSION['user_info']['user_type'] = 21;
                $hash = isset($_GPC['code'])?'shihui&code='.$_GPC['code']:'shihui';
                break;
            case (stripos(strtolower($mark),strtolower('1461638616wZxgJCfN')) === 0 || $_SESSION['user_info']['user_type'] == 23):
            	$param = explode('?',$mark);
            	$code = str_replace('code=', '', $param[1]);
                $_SESSION['user_info']['user_type'] = 23;
                $hash = isset($code)?'zhige&code='.$code:'';
                break;
            case (stripos(strtolower($mark),strtolower('1456971417KZ6yt3IN')) === 0 || $_SESSION['user_info']['user_type'] == 28):
                $_SESSION['user_info']['user_type'] = 28;
                $hash = isset($_GPC['code'])?'lidao&code='.$_GPC['code']:'';
                break;
            case (stripos(strtolower($mark),strtolower('1474253355Si5jlUYL')) === 0 || $_SESSION['user_info']['user_type'] == 30):
                $_SESSION['user_info']['user_type'] = 30;
                $hash = isset($_GPC['code'])?'media&code='.$_GPC['code']:'';
                break;
            case (stripos(strtolower($mark),strtolower('14652830996a2dVZ7J')) === 0 || $_SESSION['user_info']['user_type'] == 27):
            	$_SESSION['user_info']['user_type'] = 27;
            	if(!allow_visit() && $_SERVER['HTTP_X_SA_LOGIN']=='true'){
            		$hash = 'samsung';
            	}else{
            		$hash = '';
            	}
            	break;
            case (stripos(strtolower($mark),strtolower('pabc_test')) === 0 || $_SESSION['user_info']['user_type'] == 29):
            	$_SESSION['user_info']['user_type'] = 29;
            	 if($_GET['signature'] && $_GET['timestamp'] && $_GET['SSOTicket']) {
            	 	$hash = "pabc&signature={$_GET['signature']}&timestamp={$_GET['timestamp']}&SSOTicket={$_GET['SSOTicket']}";
            	 } else {
            	 	$hash = 'pabc';
            	 }
                break;
            case (stripos(strtolower($mark),strtolower('1463641183vw9NDaA8')) === 0 || $_SESSION['user_info']['user_type'] == 14):
            	$_SESSION['user_info']['user_type'] = 14;
        		if($_GET['token']) {
            	 	$hash = "nuomi&token={$_GET['token']}";
            	 } else {
            	 	$hash = 'nuomi';
            	 }
                break;
            // 渤海银行
            case (stripos(strtolower($mark),strtolower('1478748685Br4w1To0')) === 0 || $_SESSION['user_info']['user_type'] == 31):
            	$_SESSION['user_info']['user_type'] = 31;
            	if ($_GET['requestdata']){
        			$hash = 'bohai';
            	}else{
            		$hash = '';
            	}
            	break;
            //雅居乐
            case (stripos(strtolower($mark),strtolower('1490695097hNEICqYK')) === 0 || $_SESSION['user_info']['user_type'] == 34):
            	$_SESSION['user_info']['user_type'] = 34;
            	$_SESSION['user_info']['accessToken'] = $_GET['accessToken']?$_GET['accessToken']:$_SESSION['user_info']['accessToken'];
        		if($_GET['accessToken']) {
            	 	$hash = "yajule&accessToken={$_GET['accessToken']}";
            	 } else {
            	 	$hash = '';
            	 }
                break;
			default:
				$_SESSION['user_info']['user_type'] = 20;
				$hash = '';
				break;
		}
		if (empty($hash)) {
			return;
		}
		if (!empty($_GPC['ref_code'])) {
			$user_info_mcache = unserialize(mcache()->get($_GPC['ref_code']));
			$_SESSION['user_info']['from_user'] = $user_info_mcache['from_user'];
			$_SESSION['user_info']['phone'] = $user_info_mcache['phone'];
		}
		// 请求 Oauth 
		if (empty($_SESSION['user_info']['from_user'])) {
			$raw_request = HTTP_TYPE . $_SERVER['HTTP_HOST'] . $_SERVER["REQUEST_URI"];
			header("Location: " . $_W['config']['oauth']['url'] . '/api.php?hash=' . $hash . '&call_back_url=' . urlencode($raw_request));
			exit;
		}
	}
}

/**
 * 判断是否微信渠道访问
 * @return bool
 */
function is_from_weixin() {
	$http_user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
	return strexists($http_user_agent, 'micromessenger');
}

/**
 * 判断是否小e管家渠道访问
 * @return bool
 */
function is_from_eservice() {
	global $_W, $_GPC;
	if(get_mark() == 'eservice' || $_SESSION['user_info']['user_type'] == 18){
		return true;
	}
	return false;
}

/**
 * 格式化日期（判断今天、明天、后天）
 * @param timestamp
 * @return string
 */
function formatDate($timestamp){
	if(!$timestamp){
		return '';
	}
	$date = date('Y-m-d', $timestamp);
	$today = date('Y-m-d');
	$tomorrow = date('Y-m-d', strtotime('1 days'));
	$afert_tomorrow  = date('Y-m-d', strtotime('2 days'));
	switch ($date){
		case $today:
			return '今天';
			break;
		case $tomorrow:
			return '明天';
			break;
		case $afert_tomorrow:
			return '后天';
			break;
		default:
			return '';
			break;
	}
}

/**
 * 设置用户首页城市
 * */
function set_user_city($city_id, $city_name=''){
	$default_city_id = 1;
	$default_city_name = '北京';
	$expire = 3600;
	if(is_numeric($city_id) && $city_id > 0){
		// 获取 city_id 对应的城市
		$this_city_name = check_city_id($city_id);
		if(! $this_city_name){
			// 未找到 city_id 对应城市
			$city_id = $default_city_id;
			$city_name = $default_city_name;
		}else{
			if(isset($city_name)){
				if($this_city_name != $city_name){
					// city_id 与 city_name 对应错误
					$city_id = $default_city_id;
					$city_name = $default_city_name;
				}
			}else{
				$city_name = $this_city_name;
			}
		}
	}else{
		// city_id 格式有误
		$city_id = $default_city_id;
		$city_name = $default_city_name;
	}
	/*	
	// 存 Redis
	$user_id = $_SESSION['user_info']['user_id'];
	if($user_id){
		$redis = redis();
		$redis->hMset('user_city:' . $user_id, array(
				'city_id' => $city_id, 
				'city_name' => $city_name
		));
		$redis->expireAt('user_city:' . $user_id, time() + $expire);
	}
	*/
	//存cookie，存7天
	setcookie("local_user_city[city_id]", $city_id, time()+604800);
	setcookie("local_user_city[city_name]", $city_name, time()+604800);
}

/**
 * 获取用户首页城市(此处增加一个默认参数，当$for_location=1时，仅为首页定位订制)
 * */
function get_user_city($for_location=0){
	global $_W;
	/*
	$user_id = isset($_SESSION['user_info']['user_id']) ?
		$_SESSION['user_info']['user_id'] : 0;
	// 读 Redis
	if($user_id){
		$redis = redis();
		$city_id = redis()->hGet('user_city:' . $user_id, 'city_id');
		$city_name = redis()->hGet('user_city:' . $user_id, 'city_name');
	}*/
	if(isset($_COOKIE['local_user_city'])){
		@extract($_COOKIE['local_user_city']);
	}
	if($for_location == 0){
		$user_city['city_id'] = empty($city_id) ? 1 : $city_id;
		$user_city['city_name'] = empty($city_name) ? '北京' : $city_name;
	}else if($for_location == 1){
		$user_city['city_id'] = empty($city_id) ? 0 : $city_id;
		$user_city['city_name'] = empty($city_name) ? '' : $city_name;
	}
	return $user_city;
}

/**
 * 验证城市ID是否合法，合法则返回城市名称
 * */
function check_city_id($city_id){
	if(!is_numeric($city_id) || $city_id <= 0){
		return false;
	}
	$redis = redis();
	if($redis->exists('city_list:' . $city_id)){
		return $redis->hGet('city_list:' . $city_id, 'city_name');
	}else{
		return false;
	}
}

/**
 * 验证城市名称是否合法，合法则返回城市ID
 * */
function check_city_name($city_name){
	if(empty($city_name)){
		return false;
	}
	$redis = redis();
	$city_id = $redis->get('city_list:city_name:' . $city_name);
	return $city_id;
}

/**
 * 缓存读取城市列表
 * */
function set_city_list($city_list){
	if(empty($city_list)){
		return false;
	}
	$redis = redis();
	$expire = 3600;
	foreach ($city_list as $key => $city) {
		//缓存城市列表，设置 1 小时过期
		$redis->hMset('city_list:' . $city['city_id'], array(
				'city_id' => $city['city_id'],
				'city_name' => $city['city_name'],
		));
		$redis->expireAt('city_list:' . $city['city_id'], time() + $expire);
		$redis->setex('city_list:city_name:' . $city['city_name'], $expire, $city['city_id']);
	}
	$redis->setex('city_list:cache', $expire, 1);
}

/**
 * 缓存验证码请求次数
 * @param string $ident 缓存标识：微信open_id 或 客户端ip 或  手机号码
 * @param string $type 验证码类型：sms(短信验证码) voice(语音验证码)
 * */
function set_captcha_count($ident, $type='sms'){
    if(empty($ident)){
        return;
    }
    $redis = redis();
    $captcha_type = ($type == 'voice' ? 'voice' : 'sms');
    $hash_key = 'captcha:' . $captcha_type . ':' . date('Ymd') ;
    $hash_field = $ident;
    $expire = strtotime(date('Y-m-d', strtotime('+1 day'))); // 次日零时时间戳
    $redis->hIncrBy($hash_key, $hash_field, 1);
    $redis->expireAt($hash_key, $expire);
}

/**
 * 获取验证码请求次数
 * @param string $ident 缓存标识：微信open_id 或 客户端ip
 * @param string $type 验证码类型：sms(短信验证码) voice(语音验证码)
 * @return integer
 * */
function get_captcha_count($ident, $type='sms'){
    if(empty($ident) || !in_array($type, array('sms', 'voice'))){
        return 0;
    }
    $redis = redis();
    $captcha_type = ($type == 'voice' ? 'voice' : 'sms');
    $hash_key = 'captcha:' . $captcha_type . ':' . date('Ymd') ;
    $hash_field = $ident;
    $num = + $redis->hGet($hash_key, $hash_field);
    return $num;
}

/**
 * 缓存订单衣物瑕疵图片
 * @param string $order_id
 * @param string $clothes_index
 * @param string $clothes_name
 * @param array $photos
 * @return boolean
 * */
function set_blemish_photos($order_id, $clothes_index, $clothes_name, $photos){
	if(empty($photos) || empty($order_id) || empty($clothes_name) || !isset($clothes_index)){
		return false;
	}
	$redis = redis();
	$clothes_key = 'blemish_photos:' . $order_id . ':' . $clothes_index;
	$photos_count = count($photos);
	$redis->hMset($clothes_key, array('count' => $photos_count, 'clothes_name' => $clothes_name));
	foreach ($photos as $photo_index => $photo){
		$photo_key = $clothes_key . ':' . $photo_index;
		$photo['clothes_name'] = $clothes_name;
		$redis->hMset($photo_key, $photo);
	}
}

/**
 * 读取订单衣物瑕疵图片缓存
 * @param string $order_id
 * @param string $clothes_index
 * @param string $photo_index
 * @return array
 * */
function get_blemish_photos($order_id, $clothes_index, $photo_index=false){
	if(empty($order_id) || !isset($clothes_index)){
		return array();
	}
	$redis = redis();
	$blemish_photos = array();
	$clothes_key = 'blemish_photos:' . $order_id . ':' . $clothes_index;
	if(false !== $photo_index){
		return $redis->hGetAll($clothes_key . ':' . $photo_index);
	}
	$photos_count = $redis->hGet($clothes_key, 'count');
	for($i = 0; $i < $photos_count; $i ++){
		$photo_key = $clothes_key . ':' . $i;
		$blemish_photos[$i] = $redis->hGetAll($photo_key);
	}
	return $blemish_photos;
}

/**
 * 打印数据结构
 * @param mixed $value
 * @param bool $exit
 * */
function dump($value, $exit = 0) {
	header('content-type:text/html;charset=utf-8');
	echo '<pre>';
	if(empty($value)){
		var_dump($value);
	}else{
		if(is_array($value)){
			var_export($value);
		}else{
			print_r($value);
		}
	}
	echo '</pre>';
	if ($exit) {
		exit;
	}
}

/**
 * 生成随机字符串
 * @param  integer $length [description]
 * @return [type]          [description]
 */
if(!function_exists('create_noncestr')) {
	function create_noncestr($length = 16) {
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$str = "";
		for ($i = 0; $i < $length; $i++) {
			$str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
		}
		return $str;
	}
}


/**
 * http请求
 * @param  [type] $url  [description]
 * @param  string $data [description]
 * @return [type]       [description]
 */
if(!function_exists('http_handle')) {
	function http_handle($url, $data = '', $timeout = 10) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, TRUE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
		if (!empty($data)) {
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($curl);
		curl_close($curl);
		return $output;

	}
}

//允许游客访问的功能列表（不需登录认证）
function allow_visit(){
	global $_W, $_GPC;
	//允许游客访问的功能
	$visitor_list = array(
			//'act' => array('do'),
			//'act' => '*',
			//首页
			'homepage' => array(
					'generate_captcha', #生成图形验证码
					'verify_captcha', #校验图形验证码
					'index', #首页信息
					'city_list', #城市列表
					'get_sms_code', #获取短信验证码
					'get_voice_code', #获取语音验证码
					'bind_user_mobile', #绑定用户手机
					'ajax_locate_city', #经纬度换取城市姓名
			        'ajax_keep_city', #取消城市切换（保留在异地城市）
					'ajax_daily_points', #积分动画判断
					'favourable_comments', #好评列表
					'ajax_next_favourable_comments', #好评分页
					'view_price', #文字价目页重定向
					'hot_sale', #爆品
					'shareRecode',
					'show_price',	#普洗价目页展示的接口
					'index',	#首页的接口
					'index_api',	#首页的接口
					'show_office_price',	#写字楼快洗价目页接口
					// 'comm_order_place',	#普洗下单
					// 'comm_api_test',	#普洗下单接口测试
					'ajax_get_foot'	#获取原foot页面数据
			),
			//订单
			'order' => array(
					'order_list', #订单列表
					'ajax_insurance_desc', #投保描述
					'lingcb_share_page', #零彩宝微信分享页
					//'qrcode_order', #扫码下单
			),
			//地址
			'address' => array(
					'ajax_verify_address', #ajax验证地址服务范围
			),
			//个人中心
			'icard' => array(
					'my_icard',	 #个人中心
					'show_more', #更多信息
					'feedback', #意见反馈
			),
			'recharge917' => array(
				'recharge_page' #活动首页
			),
			'activity' => array(
				'index',        #活动引导页 
				'recharge_page',  #活动首页api
				'homepage' #第三期活动入口    
			),
			// 可配置活动
			'auto_activity' => array(
				'index',
				'recharge_page'
			),
			// 洗衣液
			'mall' => array(
				'detergent_price', #洗衣液数据
				// 'detergent_order_place', #洗衣液下单页
			),
			//银行充值营销活动
			'bank_charge' => array(
				'index'
			),
			//2017年717活动
			'static_charge717' => array(
				'index',          #活动引导页
				'recharge_page',  #活动首页api
				'item_detail'     #商品详情页
				)
	);
	$class = $_GPC['act'];
	$func = $_GPC['do'];
	if(empty($visitor_list[$class])){
		return false;
	}
	if('*' == $visitor_list[$class] || in_array($func, $visitor_list[$class])){
		return true;
	}
	return false;
}

if(!function_exists('set_user_remark')) {
	function set_user_remark($fan_id, $remark){
		// global $_W;
		// $redis = new Redis();
		try {
			// $redis->connect($_W['config']['redis']['host'], $_W['config']['redis']['port']);
			// if(!empty($_W['config']['redis']['password'])) {
			// 	$redis->auth($_W['config']['redis']['password']);
			// }

			redis()->setex('user:remark:' . $fan_id, 60*60*24*3, serialize($remark));
		} catch (Exception $e) {
			logging('set_bd_remark Exception', $e->getMessage() . "\nuser_type：" . $_SESSION['user_info']['user_type'] 
			. "\nRedis Host：" . $_W['config']['redis']['host'] . "\nRedis Port：" . $_W['config']['redis']['port']);
		}
	}
}

/*
 * 压缩 html 
 * */
function compress_html($string) {
	$string = str_replace("\r\n", '', $string);//清除换行符
	$string = str_replace("\n", '', $string);  //清除换行符
	$string = str_replace("\t", '', $string);  //清除制表符
	$pattern = array(
	   "/> *([^ ]*) *</", //去掉注释标记
	   "/[\s]+/",
	   "/<!--[^!]*-->/",
	   "/\" /",
	   "/ \"/",
	   "'/\*[^*]*\*/'"
	);
	$replace = array(
	   ">\\1<",
	   " ",
	   "",
	   "\"",
	   "\"",
	   ""
	);
	return preg_replace($pattern, $replace, $string);
}

/*
 * 手机号码简单验证
 * */
function check_mobile($str){
	$pattern = "/^1[3-8]\d{9}$/";
	if (preg_match($pattern, $str)){
		return true;
	}else{
		return false;
	}
}

/**
 * seesion 缓存 Mark
 * */
function set_mark($mark){
	if(isset($mark)){
		$_SESSION['mark'] = $mark;
		$_SESSION['mark_time'] = time();
	}
}

/**
 * 读取 Mark
 * 优先级： 1 get/post/cookie， 2 session
 * */
function get_mark(){
	global $_GPC;
	$mark = '';
	if (isset($_GPC['mark'])) {
		$mark = $_GPC['mark'];
	} else if(isset($_SESSION['mark'])) {
		$mark_time = isset($_SESSION['mark_time']) ? 
		               $_SESSION['mark_time'] : 0;
		$now = time();
		//mark有效期30分钟
		if ($now < $mark_time + 1800) {
			$mark = $_SESSION['mark'];
		}
	}
	return $mark;
}

/**
 * session 缓存登录返回地址
 * */
function set_loginback_url($loginback_url){
	if(isset($loginback_url)){
		$_SESSION['loginback_url'] = $loginback_url;
	}
}

/**
 * session 读取 缓存登录返回地址
 * */
function get_loginback_url(){
	$loginback_url = $_SESSION['loginback_url'];
	remove_loginback_url();
	if(isset($loginback_url)){
		return $loginback_url;
	}
	return '';
}

/**
 * 删除 session 中的登录返回地址
 * */
function remove_loginback_url(){
	unset($_SESSION['loginback_url']);
}

/*
 * 为URL拼接额外参数
 * */
function add_params($url, $query_data){
	if(empty($url)){
		return '';
	}
	$query_data = array_filter($query_data, function($v){
		if(! isset($v) || $v === ''){
			return false;
		}
		return true;
	});
	$param = http_build_query($query_data);
	if($param){
		if(strpos($url, '?') === false){
			$url .= ('?' . $param);
		}else{
			$url = (rtrim($url, '&')) . '&' . $param;
		}
	}
	return $url;
}


// 格式化优惠券数据（接口未提供）
// 已废弃
function format_coupon($coupon_list){
	if(false === $coupon_list['ret'] || $coupon_list['error']){
		return array();
	}
	if(!count($coupon_list)){
		return array();
	}
	foreach ($coupon_list as $key => $coupon){
		$category_id = (60 == $coupon['category_id']) ? 61 : $coupon['category_id'];
		switch ($category_id){
			case 1 :	# 洗衣券
				$coupon_list[$key]['coupon_style'] = 'coupon-style-0';
				$coupon_list[$key]['coupon_color'] = 'color-cloth';
				$coupon_list[$key]['coupon_image'] = assets_link('/framework/style/images/img_xiyijiafang.png');
				$coupon_list[$key]['choose_image'] = assets_link('/framework/style/images/choose_xiyijiafang.png');
				break;

			case 2 :	# 洗鞋券
				$coupon_list[$key]['coupon_style'] = 'coupon-style-2';
				$coupon_list[$key]['coupon_color'] = 'color-shoes';
				$coupon_list[$key]['coupon_image'] = assets_link('/framework/style/images/img_shoes.png');
				$coupon_list[$key]['choose_image'] = assets_link('/framework/style/images/choose_shoes.png');
				break;

			case 3 :	# 窗帘券
				$coupon_list[$key]['coupon_style'] = 'coupon-style-1';
				$coupon_list[$key]['coupon_color'] = 'color-chuanglian';
				$coupon_list[$key]['coupon_image'] = assets_link('/framework/style/images/img_chuanglian.png');
				$coupon_list[$key]['choose_image'] = assets_link('/framework/style/images/choose_chuanglian.png');
				break;

			case 4 :	# 高端衣物券
				$coupon_list[$key]['coupon_style'] = 'coupon-style-3';
				$coupon_list[$key]['coupon_color'] = 'color-shechipin';
				$coupon_list[$key]['coupon_image'] = assets_link('/framework/style/images/img_shechipin.png');
				$coupon_list[$key]['choose_image'] = assets_link('/framework/style/images/choose_shechipin.png');
				break;

			case 5 :	# 奢侈品券
				$coupon_list[$key]['coupon_style'] = 'coupon-style-3';
				$coupon_list[$key]['coupon_color'] = 'color-shechipin';
				$coupon_list[$key]['coupon_image'] = assets_link('/framework/style/images/img_shechipin.png');
				$coupon_list[$key]['choose_image'] = assets_link('/framework/style/images/choose_shechipin.png');
				break;
			
			case 13 :# 酒店快洗
				$coupon_list[$key]['coupon_style'] = 'coupon-style-13';
				$coupon_list[$key]['coupon_color'] = 'color-kuaixi';
				$coupon_list[$key]['coupon_image'] = assets_link('/framework/style/images/img_kuaixi.png');
				$coupon_list[$key]['choose_image'] = assets_link('/framework/style/images/choose_jiudiankuaixi.png');
				break;

			case 61 :# 服装修改
				$coupon_list[$key]['coupon_style'] = 'coupon-style-60';
				$coupon_list[$key]['coupon_color'] = 'color-gaixifu';
				$coupon_list[$key]['coupon_image'] = assets_link('/framework/style/images/img_fuzhuangxiugai.png');
				$coupon_list[$key]['choose_image'] = assets_link('/framework/style/images/choose_fuzhuangxiugai.png');
				break;
					
			default:	# 不限品类券
				$coupon_list[$key]['coupon_style'] = 'coupon-style-5';
				$coupon_list[$key]['coupon_color'] = 'color-tongy';
				$coupon_list[$key]['coupon_image'] = assets_link('/framework/style/images/img_yin07.png');
				$coupon_list[$key]['choose_image'] = assets_link('/framework/style/images/choose_07.png');
				break;
		}
	}
	return $coupon_list;
}

// 获取最近可用下单日期时段
function first_order_time($time=''){
	$time_bucket = array();
	$time_bucket['8'] = '10:00-12:00';
	$time_bucket['10'] = '12:00-14:00';
	$time_bucket['12'] = '14:00-16:00';
	$time_bucket['14'] = '16:00-18:00';
	$time_bucket['16'] = '18:00-20:00';
	$time_bucket['18'] = '20:00-22:00';
	$time_bucket['20'] = '22:00-24:00';
	$order_time = array();
	$h = $time ? $time : intval(date('H'));
	$hour = ($h % 2 == 0) ? $h : $h - 1;
	if($hour <= 8){
		$order_time['washing_date'] = date('Y-m-d');
		$order_time['washing_time'] = $time_bucket['8'];
	}else if($hour > 8 && $hour < 22){
		$order_time['washing_date'] = date('Y-m-d');
		$order_time['washing_time'] = $time_bucket[$hour];
	}else if($hour >= 22){
		$order_time['washing_date'] = date('Y-m-d', strtotime('+1 days'));
		$order_time['washing_time'] = $time_bucket['8'];
	}
	return $order_time;
}

// 判断是否 AJAX 请求
function is_ajax(){
	return isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest";
}

/**
 * URL 参数加密/解密
 * @param string $handle 	  'EN'加密, 'DE'解密
 * @param array/string $data  加密前数据/加密后数据
 * @param string $salt  	  加盐(混淆数据)
 * return string/array        加密后数据/解密后数据
 */
function encrypt($handle, $data, $salt='wudi'){
	if(!$handle || !$data){
		return $data;
	}

	if('EN' == $handle){
		return urlencode(json_encode($data));
	}elseif('DE' == $handle){
		return json_decode(urldecode($data), true);
	}
	/*
	$salt = md5($salt);
	$salt_length = strlen($salt);
	if('EN' == $handle){
		if(!is_array($data)){
			return $data;
		}
		$data = array_filter($data, function($v){
			if(! isset($v) || '' === $v){
				return false;
			}
			return true;
		});
		$string = json_encode($data);
		$string = substr(md5($string  .$salt), 0, 8) . $string;
	}else if('DE' == $handle){
		if(!is_string($data)){
			return $data;
		}
		$string = base64_decode($data);
	}else{
		return $data; 
	}
	
	$string_length = strlen($string);
	$rndkey = $box = array();
	$result = '';
	for($i=0; $i<=255; $i++){
		$rndkey[$i] = ord($salt[$i % $salt_length]);
		$box[$i] = $i;
	}
	for($j=$i=0; $i<256; $i++){
		$j = ($j + $box[$i] + $rndkey[$i]) % 256;
		$tmp = $box[$i];
		$box[$i] = $box[$j];
		$box[$j] = $tmp;
	}
	for($a=$j=$i=0; $i<$string_length; $i++){
		$a = ($a + 1) % 256;
		$j = ($j + $box[$a]) % 256;
		$tmp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $tmp;
		$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
	}
	
	if('DE' == $handle){
		if(substr($result, 0, 8) == substr(md5(substr($result, 8).$salt), 0, 8)){
			return json_decode(substr($result, 8), true);
		}else{
			return '';
		}
	}else if('EN' == $handle){
		return str_replace('=', '', base64_encode($result));
	}
	*/
}

// 计算含中文字符串长度
function wordlen($str){
	if(function_exists(mb_strlen)) {
		return mb_strlen($str, 'utf-8');
	}else{
		preg_match_all("/./us", $str, $matches);
		return count(current($matches));
	}
}

//判断是否来自平安app（浦发）
function is_from_psdzbt_pingan(){
	if(isset($_SESSION['psdzbt_source']) && $_SESSION['psdzbt_source']=='microCarpenter001'){
		return true;
	}
	return false;
}
