<?php
/**
 * 支付宝服务窗引擎
 * @author Administrator
 *
 */

require_once dirname(__FILE__) .  '/config.php';
require_once dirname(__FILE__) .  '/AopSdk.php';

require_once IA_ROOT . '/source/library/edaixi/api_server.class.php';

class AliEngine{
	public $_get;
	public $_post;
	public $_request;
	public $config;
	public $config_file = '';
	public $token;
	public $modules;
	public $params;

	public function __construct($config_file=''){
		global $_W;
		if (get_magic_quotes_gpc ()) {
			foreach ( $_POST as $key => $value ) {
				$this->_post = $_POST [$key] = stripslashes ( $value );
			}
			foreach ( $_GET as $key => $value ) {
				$this->_get = $_GET [$key] = stripslashes ( $value );
			}
			foreach ( $_REQUEST as $key => $value ) {
				$this->_request = $_REQUEST [$key] = stripslashes ( $value );
			}
		}

		$this->token = $_W['account']['token'];
		$this->modules = array_keys($_W['account']['modules']);
		$this->modules[] = 'cover';
		$this->modules[] = 'welcome';
		$this->modules[] = 'default';
		$this->modules = array_unique($this->modules);
		
		
		if(!$config_file){
			$this->config_file = ALI_PATH . "/config.php";
		}
		
		$this->load_config($this->config_file);
		
		
		//AliUtility::logging ( 'info', 'get' );
		//AliUtility::logging ( 'info', 'post' );
		//AliUtility::logging ( 'info', 'request' );
	}
	
	//载入config配置
	public function load_config($configfile='config.php'){


		if(file_exists($configfile)){
			require($configfile);
			//global $config;
		}
		else{
			AliUtility::logging('error', $configfile . " config file is not exists");
			exit('config file is not exists');
		}
		//AliUtility::logging('config', $config);

		$this->config = $config;

	}
	
	//开始处理
	public function start(){


		/*$biz_content = HttpRequest::getRequest ( "biz_content" );
		$msg = new AliMessage ( $biz_content );	
*/


		$sign = HttpRequest::getRequest ( "sign" );
		$sign_type = HttpRequest::getRequest ( "sign_type" );
		$biz_content = HttpRequest::getRequest ( "biz_content" );
		$service = HttpRequest::getRequest ( "service" );
		$charset = HttpRequest::getRequest ( "charset" );
		
		if (empty ( $sign ) || empty ( $sign_type ) || empty ( $biz_content ) || empty ( $service ) || empty ( $charset )) {
			echo "miss some parameters.";
			exit ();
		}
		
		//验证阿里签名
		$this->alipay_sign();		
		
		// 验证网关请求
		$gw = new AliGateway ();
		
		if (HttpRequest::getRequest ( "service" ) == "alipay.service.check") {
			// Gateway::verifygw();			
			$gw->verifygw ();
			
		} else if (HttpRequest::getRequest ( "service" ) == "alipay.mobile.public.message.notify") {
			$gw->verify_notify($biz_content);			
			// 处理收到的消息
			require_once ALI_PATH . '/AopSdk.php';
			// require_once ALI_PATH . '/Message.php';
			$msg = new AliMessage ( $biz_content );			
			
		}


		
	}
	
	
	
	public function alipay_sign(){
		// 收到请求，先验证签名
		$as = new AlipaySign ();

		$sign_verify = $as->rsaCheckV2 ( $_REQUEST, $this->config ['alipay_public_key_file'] );
		if (! $sign_verify) {
			echo "sign verfiy fail.";			
			AliUtility::logging('error', 'sign verfiy failed');
			exit ();
		}
	}

	public function oauth(){
		global $_GPC;

		$code = $_GPC['auth_code'];

		$request = new AlipaySystemOauthTokenRequest();
		$request->setGrantType('authorization_code');
		$request->setCode($code);

		require ALI_PATH . '/config.php';
		$aop = new AopClient ();
		$aop->appId = $config ['app_id'];
		$aop->rsaPrivateKeyFilePath = $config ['merchant_private_key_file'];
		$result = $aop->execute($request);

		$access_token = $result['alipay_system_oauth_token_response']['access_token'];

		$userinfo = new UserInfo();
		$userinfo->getUserInfo($access_token);

		return $result;
	}

}

abstract class AliModuleProcessor {
	public $inContext;
	protected function beginContext($expire = 1800) {
		if($this->inContext) {
			return false;
		}
		$expire = intval($expire);
		WeSession::$expire = $expire;
		$_SESSION['__contextmodule'] = $this->module['name'];
		$_SESSION['__contextrule'] = $this->rule;
		$_SESSION['__contextexpire'] = TIMESTAMP + $expire;
		$_SESSION['__contextpriority'] = $this->priority;
		$this->inContext = true;
		return true;
	}
	protected function refreshContext($expire = 1800) {
		if(!$this->inContext) {
			return false;
		}
		$expire = intval($expire);
		WeSession::$expire = $expire;
		$_SESSION['__contextexpire'] = TIMESTAMP + $expire;
		return true;
	}
	protected function endContext() {
		unset($_SESSION['__contextmodule']);
		unset($_SESSION['__contextrule']);
		unset($_SESSION['__contextexpire']);
		unset($_SESSION['__contextpriority']);
		unset($_SESSION);
		session_destroy();
	}
	public $priority;
	public $message;
	public $rule;
	public $module;
	abstract function respond();
	protected function respText($content) {
		preg_match_all("/(mobile\.php(?:.*?))['|\"]/", $content, $urls);
		if (!empty($urls[1])) {
			foreach ($urls[1] as $url) {
				$content = str_replace($url, $this->buildSiteUrl($url), $content);
			}
		}
		$content = str_replace("\r\n", "\n", $content);
		$response = array();
		$response['FromUserName'] = $this->message['to'];
		$response['ToUserName'] = $this->message['from'];
		$response['MsgType'] = 'text';
		$response['Content'] = htmlspecialchars_decode($content);
		return $response;
	}
	protected function respImage($mid) {
		$response = array();
		$response['FromUserName'] = $this->message['to'];
		$response['ToUserName'] = $this->message['from'];
		$response['MsgType'] = 'image';
		$response['Image']['MediaId'] = $mid;
		return $response;
	}
	protected function respVoice($mid) {
		$response = array();
		$response['FromUserName'] = $this->message['to'];
		$response['ToUserName'] = $this->message['from'];
		$response['MsgType'] = 'voice';
		$response['Voice']['MediaId'] = $mid;
		return $response;
	}
	protected function respVideo(array $video) {
		$response = array();
		$response['FromUserName'] = $this->message['to'];
		$response['ToUserName'] = $this->message['from'];
		$response['MsgType'] = 'video';
		$response['Video']['MediaId'] = $video['video'];
		$response['Video']['ThumbMediaId'] = $video['thumb'];
		return $response;
	}
	protected function respMusic(array $music) {
		global $_W;
		$music = array_change_key_case($music);
		$response = array();
		$response['FromUserName'] = $this->message['to'];
		$response['ToUserName'] = $this->message['from'];
		$response['MsgType'] = 'music';
		$response['Music'] = array(
			'Title'	=> $music['title'],
			'Description' => $music['description'],
			'MusicUrl' => strpos($music['musicurl'], 'http://') === FALSE ? $_W['attachurl'] . $music['musicurl'] : $music['musicurl'],
			);
		if (empty($music['hqmusicurl'])) {
			$response['Music']['HQMusicUrl'] = $response['Music']['MusicUrl'];
		} else {
			$response['Music']['HQMusicUrl'] = strpos($music['hqmusicurl'], 'http://') === FALSE ? $_W['attachurl'] . $music['hqmusicurl'] : $music['hqmusicurl'];
		}
		if($music['thumb']) {
			$response['Music']['ThumbMediaId'] = $music['thumb'];
		}
		return $response;
	}
	protected function respNews(array $news) {
		$news = array_change_key_case($news);
		if (!empty($news['title'])) {
			$news = array($news);
		}
		$response = array();
		$response['FromUserName'] = $this->message['to'];
		$response['ToUserName'] = $this->message['from'];
		$response['MsgType'] = 'news';
		$response['ArticleCount'] = count($news);
		$response['Articles'] = array();
		foreach ($news as $row) {
			$response['Articles'][] = array(
				'Title' => $row['title'],
				'Description' => ($response['ArticleCount'] > 1) ? '' : $row['description'],
				'PicUrl' => !empty($row['picurl']) && !strexists($row['picurl'], 'http://') ? $GLOBALS['_W']['attachurl'] . $row['picurl'] : $row['picurl'],
				'Url' => $this->buildSiteUrl($row['url']),
				'TagName' => 'item',
				);
		}
		return $response;
	}

	protected function buildSiteUrl($url) {
		global $_W;
		if (!strexists($url, 'mobile.php')) {
			return $url;
		}

		$mapping = array(
			'[from]' => $this->message['from'],
			'[to]' => $this->message['to'],
			'[rule]' => $this->rule,
			'[weid]' => $GLOBALS['_W']['weid'],
			);
		$url = str_replace(array_keys($mapping), array_values($mapping), $url);

		$vars = array();
		$pass = array();
		$pass['fans'] = $this->message['from'];

		$row = fans_search($pass['fans'], array('salt'));
		if(!is_array($row) || empty($row['salt'])) {
			$row = array('salt' => '');
		}
		//$pass['user_type'] = 6;
		$pass['time'] = TIMESTAMP;
		$pass['hash'] = md5("{$pass['fans']}{$pass['time']}{$row['salt']}{$_W['config']['setting']['authkey']}");
		$auth = base64_encode(json_encode($pass));
		$vars['weid'] = $_W['weid'];
		$vars['__auth'] = $auth;
		$vars['forward'] = base64_encode($url);
		return $_W['siteroot'] . create_url('mobile/auth', $vars);
	}

	protected function createMobileUrl($do, $querystring = array()) {
		$querystring['name'] = strtolower($this->module['name']);
		$querystring['do'] = $do;
		$querystring['weid'] = $GLOBALS['_W']['weid'];
		return create_url('mobile/module', $querystring);
	}

	protected function createWebUrl($do, $querystring = array()) {
		$querystring['name'] = strtolower($this->module['name']);
		$querystring['do'] = $do;
		$querystring['weid'] = $GLOBALS['_W']['weid'];
		return create_url('site/module', $querystring);
	}
}
//AliModuleReceiver   180
abstract class AliModuleReceiver {
	public $message;
	public $params;
	public $response;
	public $keyword;
	public $module;
	abstract function receive();
}

class AliUtility{
	
	/**
	 * 递归创建目录树
	 * @param string $path 目录树
	 * @return bool
	 */
	public static function mkdirs($path) {   
		if(!is_dir($path)) {
			mkdirs(dirname($path));
			mkdir($path);   
		}   
		return is_dir($path);   
	}
	
	public static function rootPath() {
		static $path;
		if(empty($path)) {
			$path = dirname(__FILE__);
			$path = str_replace('\\', '/', $path);
		}
		$path .= "/../modules/";
		$path = realpath($path);
		return $path;
	}
	
	public function get_config_file(){
		return ALI_PATH . "/config.php";
	}
	
	public static function createModuleProcessor($name) {
		$classname = "{$name}ModuleProcessor";
		
		if(!class_exists($classname)) {
			$file = AliUtility::rootPath() . "/{$name}/aliprocessor.php";
			
			if(!is_file($file)) {
				trigger_error('ModuleProcessor Definition File Not Found '.$file, E_USER_WARNING);
				return null;
			}
			//AliUtility::logging('eee', $file);			
			require $file;
		}
		if(!class_exists($classname)) {
			trigger_error('ModuleProcessor Definition Class Not Found', E_USER_WARNING);
			return null;
		}
		$o = new $classname();
		if($o instanceof AliModuleProcessor) {
			return $o;
		} else {
			trigger_error('ModuleProcessor Class Definition Error', E_USER_WARNING);
			return null;
		}
	}
//createModuleReceiver start 180
	public static function createModuleReceiver($name) {
		$classname = "{$name}ModuleReceiver";
		if(!class_exists($classname)) {
			$file = WeUtility::rootPath() . "/{$name}/alireceiver.php";
			if(!is_file($file)) {
				trigger_error('ModuleReceiver Definition File Not Found '.$file, E_USER_WARNING);
				return null;
			}
			require $file;
		}
		if(!class_exists($classname)) {
			trigger_error('ModuleReceiver Definition Class Not Found', E_USER_WARNING);
			return null;
		}
		$o = new $classname();
		if($o instanceof AliModuleReceiver) {
			return $o;
		} else {
			trigger_error('ModuleReceiver Class Definition Error', E_USER_WARNING);
			return null;
		}
	}

	
	//获取xml的节点
	public function getNode($xml, $node) {
		$xml = "<?xml version=\"1.0\" encoding=\"GBK\"?>" . $xml;
		$dom = new DOMDocument ( "1.0", "GBK" );
		$dom->loadXML ( $xml );
		$event_type = $dom->getElementsByTagName ( $node );
		return $event_type->item ( 0 )->nodeValue;
	}
	
	public static function logging($level = 'info', $message = '') {
		if(!DEVELOPMENT) {
			return true;
		}
		$filename = ALI_PATH . '/logs/' . date('Ymd') . '.log';
		self::mkdirs(dirname($filename));
		$content = date('Y-m-d H:i:s') . " {$level} :\n------------\n";
		if(is_string($message)) {
			$content .= "String:\n{$message}\n";
		}
		if(is_array($message)) {
			$content .= "Array:\n";
			foreach($message as $key => $value) {
				$content .= sprintf("%s : %s ;\n", $key, $value);
			}
		}
		if($message == 'get') {
			$content .= "GET:\n";
			foreach($_GET as $key => $value) {
				$content .= sprintf("%s : %s ;\n", $key, $value);
			}
		}
		if($message == 'post') {
			$content .= "POST:\n";
			foreach($_POST as $key => $value) {
				$content .= sprintf("%s : %s ;\n", $key, $value);
			}
		}
		if($message == 'request') {
			$content .= "REQUEST:\n";
			foreach($_REQUEST as $key => $value) {
				$content .= sprintf("%s : %s ;\n", $key, $value);
			}
		}
		
		$content .= "\n";

		$fp = fopen($filename, 'a+');
		fwrite($fp, $content);
		fclose($fp);
	}
}

class HttpRequest {
	public static function sendPostRequst($url, $data) {
		$postdata = http_build_query ( $data );
		// 		print_r($postdata);
		$opts = array (
			'http' => array (
				'method' => 'POST',
				'header' => 'Content-type: application/x-www-form-urlencoded',
				'content' => $postdata
				)
			);


		$context = stream_context_create ( $opts );

		$result = file_get_contents ( $url, false, $context );
		return $result;
	}

	public static function getRequest($key) {
		$request = null;
		if (isset ( $_GET [$key] ) && ! empty ( $_GET [$key] )) {
			$request = $_GET [$key];
		} elseif (isset ( $_POST [$key] ) && ! empty ( $_POST [$key] )) {
			$request = $_POST [$key];
		}
		return $request;
	}
}

//验证gw
class AliGateway {
	public function verifygw() {
		$biz_content = HttpRequest::getRequest ( "biz_content" );
		$as = new AlipaySign ();
		$xml = simplexml_load_string ( $biz_content );
		// print_r($xml);
		$EventType = ( string ) $xml->EventType;
		// echo $EventType;
		if ($EventType == "verifygw") {
			require ALI_PATH . '/config.php';
			// global $config;
			// print_r ( $config );
			//file_put_contents ( "log_check.txt", var_export($config,true), FILE_APPEND );
			//AliUtility::logging('info', $config);

			$response_xml = "<success>true</success><biz_content>" . $as->getPublicKeyStr($config ['merchant_public_key_file']) . "</biz_content>";
			// echo $response_xml;
			$return_xml = $as->sign_response ( $response_xml, $config ['charset'], $config ['merchant_private_key_file'] );
			//file_put_contents ( "log_check.txt", $response_xml, FILE_APPEND );
			//file_put_contents ( "log.txt", $return_xml, FILE_APPEND );
			AliUtility::logging('info', $response_xml);
			AliUtility::logging('info', $return_xml);
			
			echo $return_xml;
			exit ();
		}
		
	}
	
	/**
	 * 验证消息
	 */
	public function verify_notify($biz_content) {
		//$biz_content = HttpRequest::getRequest ( "biz_content" );
		$as = new AlipaySign ();
		//$xml = simplexml_load_string ( $biz_content );
		// print_r($xml);
		require ALI_PATH . '/config.php';
		
		$EventType = AliUtility::getNode($biz_content, "EventType");
		$FromUserId = AliUtility::getNode($biz_content, "FromUserId");
		$AppId = AliUtility::getNode($biz_content, "AppId");
		if(!$AppId){
			$AppId = $config['app_id'];
		}
		
		//$EventType = ( string ) $xml->EventType;
		//$FromUserId = ( string ) $xml->FromUserId;
		//$appid = $config['app_id'];
		// echo ;
		//AliUtility::logging ( 'notify', ($EventType ."\t". $FromUserId ."\t". $AppId) );
		
		

		$response_xml = "<XML><ToUserId><![CDATA[" . $FromUserId . "]]></ToUserId><AppId><![CDATA[" . $AppId . "]]></AppId><CreateTime>" . time () . "</CreateTime><MsgType><![CDATA[ack]]></MsgType></XML>";
		
		$return_xml = $as->sign_response ( $response_xml, $config ['charset'], $config ['merchant_private_key_file'] );
		
		//AliUtility::logging ( 'notify', $response_xml );		
		
		echo $return_xml;
		
		//AliUtility::logging ( 'return_xml', $return_xml );
	}

}


//签名
class AlipaySign {
	public function rsa_sign($data, $rsaPrivateKeyFilePath) {
		$priKey = file_get_contents ( $rsaPrivateKeyFilePath );
		$res = openssl_pkey_get_private ( $priKey );
		$rs = function_exists('openssl_pkey_get_private');
		//error_log(var_export($res, true) . "\t" . $rs, 3, "rsa.log");
		openssl_sign ( $data, $sign, $res );
		openssl_free_key ( $res );
		$sign = base64_encode ( $sign );
		//error_log($data . "\r\n" . $sign . "\r\n", 3, "rsa.log");
		return $sign;
	}
	
	public function sign_request($params, $rsaPrivateKeyFilePath) {
		return $this->rsa_sign ( $this->getSignContent ( $params ), $rsaPrivateKeyFilePath );
	}
	
	public function sign_response($bizContent, $charset, $rsaPrivateKeyFilePath) {
		$sign = $this->rsa_sign ( $bizContent, $rsaPrivateKeyFilePath );
		$response = "<?xml version=\"1.0\" encoding=\"$charset\"?><alipay><response>$bizContent</response><sign>$sign</sign><sign_type>RSA</sign_type></alipay>";
		return $response;
	}
	
	public function rsa_verify($data, $sign, $rsaPublicKeyFilePath) {
		// 读取公钥文件
		$pubKey = file_get_contents ( $rsaPublicKeyFilePath );

		// 转换为openssl格式密钥
		$res = openssl_get_publickey ( $pubKey );
		
		// 调用openssl内置方法验签，返回bool值
		$result = ( bool ) openssl_verify ( $data, base64_decode ( $sign ), $res );
		
		// 释放资源
		openssl_free_key ( $res );

		return $result;
	}
	public function rsaCheckV2($params, $rsaPublicKeyFilePath) {
		$sign = $params ['sign'];
		$params ['sign'] = null;

		return $this->rsa_verify ( $this->getSignContent ( $params ), $sign, $rsaPublicKeyFilePath );
	}
	protected function getSignContent($params) {
		ksort ( $params );

		$stringToBeSigned = "";
		$i = 0;
		foreach ( $params as $k => $v ) {
			if (false === $this->checkEmpty ( $v ) && "@" != substr ( $v, 0, 1 )) {
				if ($i == 0) {
					$stringToBeSigned .= "$k" . "=" . "$v";
				} else {
					$stringToBeSigned .= "&" . "$k" . "=" . "$v";
				}
				$i ++;
			}
		}
		unset ( $k, $v );		
		return $stringToBeSigned;
	}

	/**
	 * 校验$value是否非空
	 * if not set ,return true;
	 * if is null , return true;
	 */
	protected function checkEmpty($value) {
		if (! isset ( $value ))
			return true;
		if ($value === null)
			return true;
		if (trim ( $value ) === "")
			return true;

		return false;
	}
	public function getPublicKeyStr($pub_pem_path) {
		$content = file_get_contents ( $pub_pem_path );
		$content = str_replace ( "-----BEGIN PUBLIC KEY-----", "", $content );
		$content = str_replace ( "-----END PUBLIC KEY-----", "", $content );
		$content = str_replace ( "\r", "", $content );
		$content = str_replace ( "\n", "", $content );
		return $content;
	}
}


/**
	 送优惠券
	 parameters $coupon_list_id   优惠券id
	 parameters $weid   weid
	 parameters $from_user   fromuser
	 parameters $num   多少张优惠券
 */
	 function send_coupon($coupon_list_id, $from_user){
	 	global $_W;
	 	$api_server = new ApiServer($_W['config']);

	 	$api_server->acquire_coupon($coupon_list_id, $from_user, 6);
	 	return true;
	 }

//处理信息
	 class AliMessage {
	 	public $serverhost = '';
	 	public $push_class;
	 	public $token;
	 	public $modules;
	 	public $params;
	 	public $keyword;
	 	public $response;
	 	public $message;
	 	public $type;

	// 处理信息
	 	public function match($pars) {

	 		foreach ( $pars as $par ) {

	 			if (empty ( $par ['module'] )) {
	 				continue;
	 			}

	 			$this->params = $par;
	 			$this->response = $this->process ();
			//AliUtility::logging('recivertext', var_export($this->response, true));
	 			if (is_array ( $this->response ) && (($this->response ['MsgType'] == 'text' && ! empty ( $this->response ['Content'] )) || ($this->response ['MsgType'] == 'news' && ! empty ( $this->response ['Articles'] )) /*|| !in_array($this->type, array('text', 'news'))*/)) {
	 				if (! empty ( $par ['keyword'] )) {
	 					$this->keyword = $par ['keyword'];
	 				}
	 				break;
	 			}
	 		}

	 		if (! $this->response) {
	 			return false;
	 		}


		// 发送处理后的信息
	 		switch ($this->response ['MsgType']) {
	 			case "text" :				
	 			$return_msg = $this->send_text ( $this->response ['ToUserName'], $this->response ['Content'] );
	 			break;
	 			case "news" :
	 			$return_msg = $this->send_news ( $this->response ['ToUserName'], $this->response ['Articles'] );
	 			break;
	 		}
	 		return $return_msg;
	 	}

	// 处理返回
	 	private function process() {
	 		global $_W;
	 		$response = false;

	 		if (empty ( $this->params ['module'] ) || ! in_array ( $this->params ['module'], $this->modules )) {
	 			return false;
	 		}

	 		$processor = AliUtility::createModuleProcessor ( $this->params ['module'] );

	 		$processor->message = $this->message;
	 		$processor->rule = $this->params ['rule'];
	 		$processor->priority = intval ( $this->params ['priority'] );
	 		$processor->module = $_W ['account'] ['modules'] [$this->params ['module']];
	 		$processor->inContext = $this->params ['context'] === true;

	 		$response = $processor->respond ();

	 		if (empty ( $response )) {
	 			return false;
	 		}
	 		return $response;
	 	}

	// 发送文本消息
	 	public function send_text($FromUserId, $text) {
	 		if (! $this->push_class) {
	 			$this->push_class = new AliPushMsg ();
	 		}
	 		if(!$text){
	 			return false;
	 		}
	 		$text_msg = $this->push_class->mkTextMsg ( $text );
	 		$biz_content = $this->push_class->mkTextBizContent ( $FromUserId, $text_msg );
	 		AliUtility::logging ( 'biz_content', var_export ( $biz_content, true ) );
	 		$return_msg = $this->push_class->sendRequest ( $biz_content );
	 		return $return_msg;
	 	}

	/**
	 * //发送图文消息
	 * 
	 * @param string $FromUserId        	
	 * @param array $news
	 *        	可以为内嵌数组，一位数组为 array('Title', 'Description','PicUrl','Url')
	 * @return boolean
	 */
	public function send_news($FromUserId, $news) {
		if (! $this->push_class) {
			$this->push_class = new AliPushMsg ();
		}
		if (! is_array ( $news )) {
			return false;
		}
		foreach ( $news as $value ) {
			
			$title = $value ['Title'];
			$desc = $value ['Description'];
			if (! $desc) {
				$desc = $title;
			}
			$imgurl = $value ['PicUrl'];
			$url = $value ['Url'];
			// AliUtility::logging('www', var_export($image_text_msg, true));
			$image_text_msg [] = $this->push_class->mkImageTextMsg ( $title, $desc, $url, $imgurl, "loginAuth" );
		}
		
		// 发给这个关注的用户
		$biz_content = $this->push_class->mkImageTextBizContent ( $FromUserId, $image_text_msg );
		AliUtility::logging ( 'biz_content', var_export ( $biz_content, true ) );
		$return_msg = $this->push_class->sendMsgRequest ( $biz_content );
		return $return_msg;
	}
	public function matcherText($order = -1) {
		global $_W;
		
		$pars = array ();
		$input = $this->message ['content'];
		if (! isset ( $input )) {
			return $pars;
		}
		
		$order = intval ( $order );
		$condition = "`status`=1 AND (`weid`='{$_W['weid']}' OR `weid`=0 " . (! empty ( $_W ['account'] ['subwechats'] ) ? " OR `weid` IN ({$_W['account']['subwechats']})" : '') . ") AND `displayorder`>{$order}";
		$condition .= " AND (((`type` = '1' OR `type` = '2') AND `content` = :c1) OR (`type` = '4') OR (`type` = '3' AND :c2 REGEXP `content`) OR (`type` = '2' AND INSTR(:c3, `content`) > 0))";
		$params = array ();
		$params [':c1'] = $input;
		$params [':c2'] = $input;
		$params [':c3'] = $input;
		
		$keywords = rule_keywords_search ( $condition, $params );
		
		if (empty ( $keywords )) {
			return $pars;
		}
		foreach ( $keywords as $kwd ) {
			$params = array (
				'module' => $kwd ['module'],
				'rule' => $kwd ['rid'],
				'priority' => $kwd ['displayorder'],
				'keyword' => $kwd 
				);
			$pars [] = $params;
		}		
		return $pars;
	}
	public function AliMessage($biz_content) {
		global $_W;
		
		$this->token = $_W ['account'] ['token'];
		$this->modules = array_keys ( $_W ['account'] ['modules'] );
		$this->modules [] = 'cover';
		$this->modules [] = 'welcome';
		$this->modules [] = 'default';
		$this->modules = array_unique ( $this->modules );
		
		// AliUtility::logging('Message', $biz_content);
		
		$UserInfo = $this->getNode ( $biz_content, "UserInfo" );
		$this->message ['from'] = $FromUserId = $this->getNode ( $biz_content, "FromUserId" );
		$AppId = $this->getNode ( $biz_content, "AppId" );
		$this->message ['time'] = $CreateTime = $this->getNode ( $biz_content, "CreateTime" );
		$this->message ['type'] = $MsgType = $this->getNode ( $biz_content, "MsgType" );
		$this->message ['event'] = $EventType = $this->getNode ( $biz_content, "EventType" );
		$AgreementId = $this->getNode ( $biz_content, "AgreementId" );
		$ActionParam = $this->getNode ( $biz_content, "ActionParam" );
		$AccountNo = $this->getNode ( $biz_content, "AccountNo" );
		
		$push = new AliPushMsg ();
		
		$weid = $_W ['weid'];
		$this->serverhost = $_W ['siteroot'];

		$vars = array();
		$pass = array();
		$pass['fans'] = $FromUserId;

		$row = fans_search($pass['fans'], array('salt'));
		if(!is_array($row) || empty($row['salt'])) {
			$row = array('salt' => '');
		}
		$pass['time'] = TIMESTAMP;
		$pass['hash'] = md5("{$pass['fans']}{$pass['time']}{$row['salt']}{$_W['config']['setting']['authkey']}");
		$auth = base64_encode(json_encode($pass));
		$vars['weid'] = $weid;
		$vars['__auth'] = $auth;

		// 收到用户发送的对话消息
		if ($MsgType == "text") {
			$this->message ['content'] = trim ( $this->getNode ( $biz_content, "Text" ) );
			$pars = $this->matcherText ();
			$this->match ( $pars );
		}

		if ($MsgType == "image") {
			$this->message ['content'] = trim ( $this->getNode ( $biz_content, "MediaId" ) );
		}
		
		// 收到用户发送的关注消息
		if ($EventType == "follow") {
			// $title = "欢迎进入荣昌e袋洗";
			// $desc = "洗衣、洗鞋包、洗家纺\r\n洗空调、奢侈品养护\r\n每日10:00-24:00上门服务";
			// $url = $this->serverhost . "mobile.php?act=alioauth&eid=463&weid=5";

			// AliUtility::logging ('info', $url);

			// $imgurl = "http://assets0-edaixi.qiniudn.com/resource/attachment/images/5/2014/07/Y2ROHu7grCih2Ob7Mvi59ODb7HuuR6.jpg";
			// $image_text_msg1 = $push->mkImageTextMsg ( $title, $desc, $url, $imgurl, "loginAuth" );

			// $image_text_msg = array (
			// 	$image_text_msg1 
			// 	);
			// $biz_content = $push->mkImageTextBizContent ( $FromUserId, $image_text_msg );
			
			// $return_msg = $push->sendMsgRequest ( $biz_content );
			
			$users = json_decode ( $UserInfo, true );

			$data = array (
				'weid' => $weid,
				'from_user' => $FromUserId,
				'nickname' => $users ['user_name'],
				'email' => $users ['logon_id'],
				'follow' => 1,
				'user_type' => 6 
				)
			;

			AliUtility::logging ('info', $data);

			fans_update($FromUserId, $data);

			send_coupon ( 314, $FromUserId);
		} elseif ($EventType == "unfollow") {
		} elseif ($EventType == "enter") {
			$get_msg = new AliPushMsg();

			$UserId = json_encode(array("UserId"=>$FromUserId));
			$method = 'alipay.mobile.public.gis.get';

			$res = $get_msg->sendAliRequest($UserId,$method);

			$res = iconv ("GBK","UTF-8",$res);
			$res = json_decode($res);
			$city = $res->alipay_mobile_public_gis_get_response->city;
			WeUtility::logging(' user_city ',$city);
			
			$area_text = array('0' => '北京城区','1' => '天津城区','2' => '上海城区','3' => '深圳市');
			if(in_array($city, $area_text)){
				$text[$area_text[0]] = '北京取送范围：五环内全境覆盖；五环外覆盖以下区域：海淀区上地、清河、西二旗地区；昌平区天通苑、回龙观地区；朝阳区北苑、东坝、管庄、双桥、二外、传媒大学地区 ；通州区，通州城区；大兴区亦庄开发区西区；石景山区古城以东区域。';
				$text[$area_text[1]] = '天津取送范围：南开区、河西区、和平区、红桥区';
				$text[$area_text[2]] = '上海取送范围：中环以内';
				$text[$area_text[3]] = '深圳取送范围：南山区全区；福田区全区；罗湖区全区；宝安区（仅限宝安中心，西乡街道，新安街道，民治街道，龙华街道）；龙岗区（仅限龙城街道，龙岗中心城，爱联，南联，陇西）';
				$memcache = new Memcache();
				$memcache->connect($_W['config']['site']['memcache_host'],$_W['config']['site']['memcache_port']);
				//$memcache->connect('127.0.0.1',11211);
				$get_city = $memcache->get($FromUserId);
				if(empty($get_city) || $get_city != $city){
					$memcache->set($FromUserId, $city,MEMCACHE_COMPRESSED,2591000);
					$push_msg = new AliMessage();
					$biz_content = $push_msg->send_text($FromUserId,$text[$city]);
					$res = $push_msg->sendRequest($biz_content);
				}
				$memcache->close();
			}
		} elseif ($EventType == "click") {
		}

		//f180

		$subscribes = array();
		foreach($_W['account']['modules'] as $m) {
			if(in_array($m['name'], $this->modules) && is_array($m['subscribes']) && !empty($m['subscribes'])) {
				$subscribes[] = $m;
			}
		}
			// WeUtility::logging('subscribes', var_export($subscribes,true));
		if(!empty($subscribes)) {
			$this->subscribe($subscribes);
		}

		//f180
	}
	public function getNode($xml, $node) {
		$xml = "<?xml version=\"1.0\" encoding=\"GBK\"?>" . $xml;
		$dom = new DOMDocument ( "1.0", "GBK" );
		$dom->loadXML ( $xml );
		$event_type = $dom->getElementsByTagName ( $node );
		return $event_type->item ( 0 )->nodeValue;
	}

	private function subscribe($subscribes) {
		global $_W;
		foreach($subscribes as $m) {
			$obj = AliUtility::createModuleReceiver($m['name']);
			$obj->message = $this->message;
			$obj->params = $this->params;
			$obj->response = $this->response;
			$obj->keyword = $this->keyword;
			$obj->module = $m;
			if (method_exists($obj, 'receive')) {
				$obj->receive();
			}
		}
	}

}

//发送消息
class AliPushMsg {
	//测试
	public function test() {
		$image_text_msg1 = $this->mkImageTextMsg ( "标题", "描述", "http://wap.taobao.com", "https://i.alipayobjects.com/e/201310/1H9ctsy9oN_src.jpg", "loginAuth" );
		$image_text_msg2 = $this->mkImageTextMsg ( "标题", "描述", "http://wap.taobao.com", "https://i.alipayobjects.com/e/201310/1H9ctsy9oN_src.jpg", "loginAuth" );
		// 组装多条图文信息
		$image_text_msg = array (
			$image_text_msg1,
			$image_text_msg2
			);


		$toUserId = "xLF-4RvtNKGlYDC8xLgTnI97w0QKRHRl-OmymTOxsGHnKDWiwQekMHiEi06tEbjg01";
		// $toUserId="BM7PjM8f8-v6VFqeTlFUqo97w0QKRHRl-OmymTOxsGHnKDWiwQekMHiEi06tEbjg01";
		$biz_content = $this->mkImageTextBizContent ( $toUserId, $image_text_msg );
		// echo $biz_content;exit();
		// echo $this->sendMsgRequest ( $biz_content );
		print_r ( $this->sendRequest ( $biz_content ) );
	}

	// 纯文本消息
	public function mkTextMsg($content) {
		$text = array (
			'content' => $content
			);
		return $text;
	}

	public function mKMediaContent($media_content){

		return $this->JSON($media_content);

	}
	
	
	public function downAliMediaRequest($biz_content) {
		require ALI_PATH . '/config.php';
		date_default_timezone_set ( PRC );
		$paramsArray = array (
				'method' => "alipay.mobile.public.multimedia.download",
				'biz_content' => $biz_content,
				'charset' => $config ['charset'],
				'sign_type' => 'RSA',
				'app_id' => $config ['app_id'],
				'timestamp' => date ( 'Y-m-d H:i:s', time () ),
				'version' => "1.0" 
		);
		// print_r($paramsArray);
		// require_once 'AlipaySign.php';
		$as = new AlipaySign ();
		$sign = $as->sign_request ( $paramsArray, $config ['merchant_private_key_file'] );
		$paramsArray ['sign'] = $sign;
		// $url=$as->getSignContent ( $paramsArray );
		$url = "https://openfile.alipay.com/chat/multimedia.do?";
		foreach ( $paramsArray as $key => $value ) {
			$url .= "$key=" . urlencode ( $value ) . "&";
		}
		
		// print_r ( $url );
		// 日志记录下受到的请求
		// writeLog ( "请求图片地址：" . $url );
		 AliUtility::logging('url',$url);
		return  $url;
		// file_put_contents ( $fileName, file_get_contents ( $url ) );
	}
	// 获取支付宝地理位置
	public function sendAliRequest($biz_content,$send_method) {
		require ALI_PATH . '/config.php';
		$paramsArray = array (
				// 'method' => "alipay.mobile.public.message.single.send",
				'biz_content' => $biz_content,
				'charset' => $config ['charset'],
				'sign_type' => 'RSA',
				'app_id' => $config ['app_id'],
				'timestamp' => date ( 'Y-m-d H:i:s', time () ) 
		);
		$paramsArray['method'] = $send_method;
		$as = new AlipaySign ();
		$sign = $as->sign_request ( $paramsArray, $config ['merchant_private_key_file'] );
		$paramsArray ['sign'] = $sign;
	
		$resp= HttpRequest::sendPostRequst ( $config ['gatewayUrl'], $paramsArray );
		
		return $resp;
	}
	// 图文消息，
	// $authType=loginAuth时，用户点击链接会将带有auth_code，可以换取用户信息
	public function mkImageTextMsg($title, $desc, $url, $imageUrl, $authType, $actionName='立即查看') {
		$articles_arr = array (
			'actionName' => iconv ( "UTF-8", "GBK", $actionName ),
			'desc' => iconv ( "UTF-8", "GBK", $desc ),
			'imageUrl' => $imageUrl,
			'title' => iconv ( "UTF-8", "GBK", $title ),
			'url' => $url,
			'authType' => $authType
			);
		return $articles_arr;
	}

	/**
	 * 返回图文消息的biz_content
	 *
	 * @param string $toUserId
	 * @param array $articles
	 * @return string
	 */
	public function mkImageTextBizContent($toUserId, $articles) {
		$biz_content = array (
			'msgType' => 'image-text',
			'createTime' => time (),
			'articles' => $articles
			);
		return $this->toBizContentJson ( $biz_content, $toUserId );
	}
	/**
	 * 返回纯文本消息的biz_content
	 *
	 * @param unknown $toUserId
	 * @param unknown $text
	 * @return string
	 */
	public function mkTextBizContent($toUserId, $text) {
		$biz_content = array (
			'msgType' => 'text',
			'text' => $text
			);
		return $this->toBizContentJson ( $biz_content, $toUserId );
	}
	private function toBizContentJson($biz_content, $toUserId) {
		// 如果toUserId为空，则是发给所有关注的而用户，且不可删除，慎用
		if (isset ( $toUserId ) && ! empty ( $toUserId )) {
			$biz_content ['toUserId'] = $toUserId;
		}

		$content = $this->JSON ( $biz_content );
		return $content;
	}
	public function sendRequest($biz_content) {
		$custom_send = new AlipayMobilePublicMessageCustomSendRequest ();
		$custom_send->setBizContent ( $biz_content );

		require ALI_PATH . '/config.php';
		$aop = new AopClient ();
		$aop->appId = $config ['app_id'];
		$aop->rsaPrivateKeyFilePath = $config ['merchant_private_key_file'];
		$result = $aop->execute ( $custom_send );
		return $result;
	}
	function is_utf8($text) {
		$e = mb_detect_encoding ( $text, array (
			'UTF-8',
			'GBK'
			) );
		switch ($e) {
			case 'UTF-8' : // 如果是utf8编码
			return 1;
			case 'GBK' : // 如果是gbk编码
			return 2;
		}
	}

	/**
	 * 异步发送消息给用户
	 *
	 * @param string $biz_content
	 * @param string $isMultiSend
	 *        	如果发给所有人，则此参数必须为true，且biz_content中的toUserId必须为空
	 * @return string
	 */
	public function sendMsgRequest($biz_content, $isMultiSend = FALSE) {
		require ALI_PATH . '/config.php';
		$paramsArray = array (
			'method' => "alipay.mobile.public.message.custom.send",
			'biz_content' => $biz_content,
			'charset' => $config ['charset'],
			'sign_type' => 'RSA',
			'app_id' => $config ['app_id'],
			'timestamp' => date ( 'Y-m-d H:i:s', time () )
			);
		if ($isMultiSend) {
			$paramsArray ['method'] = "alipay.mobile.public.message.total.send";
		}
		//require_once 'AlipaySign.php';
		$as = new AlipaySign ();
		$sign = $as->sign_request ( $paramsArray, $config ['merchant_private_key_file'] );
		$paramsArray ['sign'] = $sign;
		// print_r ( $paramsArray );
		// 日志记录下受到的请求
		//file_put_contents ( "log.txt", var_export ( $paramsArray, true ) . "\r\n", FILE_APPEND );
		//AliUtility::logging('info', $paramsArray);
		return HttpRequest::sendPostRequst ( $config ['gatewayUrl'], $paramsArray );
	}

	/**
	 * ************************************************************
	 *
	 * 使用特定function对数组中所有元素做处理
	 *
	 * @param
	 *        	string &$array 要处理的字符串
	 * @param string $function
	 *        	要执行的函数
	 * @return boolean $apply_to_keys_also 是否也应用到key上
	 * @access public
	 *
	 *         ***********************************************************
	 */
	protected function arrayRecursive(&$array, $function, $apply_to_keys_also = false) {
		foreach ( $array as $key => $value ) {
			if (is_array ( $value )) {
				$this->arrayRecursive ( $array [$key], $function, $apply_to_keys_also );
			} else {
				$array [$key] = $function ( $value );
			}

			if ($apply_to_keys_also && is_string ( $key )) {
				$new_key = $function ( $key );
				if ($new_key != $key) {
					$array [$new_key] = $array [$key];
					unset ( $array [$key] );
				}
			}
		}
	}

	/**
	 * ************************************************************
	 *
	 * 将数组转换为JSON字符串（兼容中文）
	 *
	 * @param array $array
	 *        	要转换的数组
	 * @return string 转换得到的json字符串
	 * @access public
	 *
	 *         ***********************************************************
	 */
	protected function JSON($array) {
		$this->arrayRecursive ( $array, 'urlencode', true );
		$json = json_encode ( $array );
		return urldecode ( $json );
	}
}
