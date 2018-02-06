<?php
require_once 'HttpRequst.php';
require_once 'AopSdk.php';
require_once 'function.inc.php';
class PushMsg {
	// ali纯文本消息
	public function mkTextMsg($content) {
		$text = array (
				'content' => iconv( "UTF-8", "GBK", $content ),
		);
		return $text;
	}
	/**
	 * ali返回纯文本消息的biz_content
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

	//微信纯文本消息JSON
	public function mkWxTextFields($req){
		$post['touser']= $req->from_user;
		// $post['touser']= 'o_SpIt4VFR-3Jty8IIzN4Ru6FPXg';
		$post['msgtype'] =  'text';
		$post['text'] = array('content' => $req->request_params['content']);
		$content = $this->JSON ( $post );
		return $content;
	}

	function getWxMediaID($val){

	global $_W;
	$token = account_weixin_token($_W['account']);
	// $token = '-g_kjq7Hcy7JVK90kv12Xl-nySNLzj7x1yVTU75mGoubg_ZqWPPZiCg1OTzViKRIRGZG27ANxX2mQ_QpN-OY7SNct4rYNEsQtZwwvE5QxmU';
	 $_url = '/cgi-bin/media/upload?access_token='.$token.'&type=image';
	 $_host = 'file.api.weixin.qq.com';
	 $errno = '';
	 $errstr = '';
	 $_fp = fsockopen($_host, 80, $errno,  $errstr, 15);
	 if($_fp){

        // 设置分割标识
        srand((double)microtime()*1000000);
        $boundary = '---------------------------'.substr(md5(rand(0,32000)),0,10);
        $data = '--'.$boundary."\r\n";
		$filedata = '';
		$filedata .= "content-disposition: form-data; name=\"".$val['name']."\"; filename=\"".$val['filename']."\"\r\n";
		$filedata .= "content-type: ".'image/jpeg'."\r\n\r\n";
		$filedata .= implode('', file($val['path']))."\r\n";
		$filedata .= '--'.$boundary."\r\n";
		
		 $data .= $filedata."--\r\n\r\n";
			$out = "POST ".$_url." http/1.1\r\n";
        $out .= "host: ".$_host."\r\n";
        $out .= "content-type: multipart/form-data; boundary=".$boundary."\r\n";
        $out .= "content-length: ".strlen($data)."\r\n";
        $out .= "connection: close\r\n\r\n";
        $out .= $data;
		 fputs($_fp, $out);
		 // 读取返回数据
			$response = '';
			while($row = fread($_fp, 4096)){
					$response .= $row;
			}
			$pos = strpos($response, "\r\n\r\n");
			$response = substr($response, $pos+4);
			$res = json_decode($response,true);
			writeLog ( var_export ( $res, true ) );
			if(isset($res['media_id'])){
				return $res['media_id'];
			}else{
				return false;
			}
		}else{

			return false;

		}
	}

	public function mkWxImageFields($req){
		$post['touser']= $req->from_user;
		// $post['touser']= 'o_SpIt4VFR-3Jty8IIzN4Ru6FPXg';
		$post['msgtype'] =  'image';
		// $media_id = $this->getWXMediaId();
		$val = array(
	        'name' => 'media',
	        'filename' => 'test.jpg',
	        'path' => $req->request_params['content']
    	);
		$mediaId = $this->getWxMediaID( $val );
		// $media_id  = 'PXHlY3YIOAPnNctxF6DEdoX5rlltulBgz6MTkNk3CSXmvY_4wBya07jRdmGvX16P';
		$post['image'] = array('media_id' => $mediaId );
		$content = $this->JSON ( $post );
		return $content;
	}

	
	// 图文消息，
	// $authType=loginAuth时，用户点击链接会将带有auth_code，可以换取用户信息
	public function mkImageTextMsg($title, $desc, $url, $imageUrl, $authType) {
		$articles_arr = array (
				'actionName' => iconv ( "UTF-8", "GBK", "立即查看" ),
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

	public function uploadWxMedia($file, $type){

		$token = account_weixin_token($_W['account']);
		$url = "http://file.api.weixin.qq.com/cgi-bin/media/upload?access_token=".$token."Q&type=".$type;
		// "http://file.api.weixin.qq.com/cgi-bin/media/upload?access_token=65v5YTHowrD3DE-mLl9zcFmuknVPSdXv0MgReljh0K1pmymDI-2dhgZDqoAuQXHHq5ZDs1m76FNqzC1_MKJ9JVDnyLihIYFivCnCgtKDZNQ&type=image"
		$file_data = array('media' =>'@'.$file);


	}
	public function mkImageTextBizContent($toUserId, $articles) {
		$biz_content = array (
				'msgType' => 'image-text',
				'createTime' => time (),
				'articles' => $articles 
		);
		return $this->toBizContentJson ( $biz_content, $toUserId );
	}
	//ali模板消息
	public function mkTemplateContext($params,$color_config,$order_id){


		$template_context = array(
		
			'headColor'=>$color_config['headColor'],
			'url'=> $params['url'].$order_id,
			'first'=>array(
				'color'=>$color_config['first'],
				'value'=>iconv( "UTF-8", "GBK",$params['first']),
			),
			'remark'=>array(
				'color'=>$color_config['remark'],
				'value'=>iconv( "UTF-8", "GBK",$params['remark']),
			),
		);
					
		foreach ($params['params_valuse'] as $k =>$v){

				$template_context[$k] = array(
					'color' => $color_config['fields_colors'][$k],
					'value' => iconv( "UTF-8", "GBK",$v),
				);
		}
	
		return $template_context;
	}
//ali模板消息
	public function mkTemplateBizContent($templateId,$toUserId,$Context){
		
		$template_msg = array(
			// 'toUserId'=>$toUserId,
			'template' =>array(
				'context'=>$Context,
				'templateId' => $templateId,
			)
		);
	
		return $this->toBizContentJson ( $template_msg, $toUserId );
		
	}

	public function mkWxIcoupontemplateData(){

		



	}


	//微信模板消息使用JSON方法 未测试
	public function mkWxTemplateData($params,$color_config,$order_id){
	
		$data = array(
			'first'=>array(
				'color'=>$color_config['first'],
				'value'=>$params['first'],
			),
			'remark'=>array(
				'color'=>$color_config['remark'],
				'value'=>$params['remark'],
			),
			'OrderSn'=>array(
				'color'=> $color_config['fields_colors']['OrderSn'],
				'value'=> $params['params_valuse']['OrderSn'],
				),
			'OrderStatus'=>array(
				'color'=>$color_config['fields_colors']['OrderStatus'],
				'value'=> $params['params_valuse']['OrderStatus'],
				),
		);
		$s = '';		
		foreach ($params['orthers'] as $k =>$v){
			if (!empty($params['params_valuse'][$k])) {
				$s = $s.$v.':'.$params['params_valuse'][$k].';';
			}
		}

		if(!empty($s)){
				$s =  substr($s, 0,-1).'。';
				$remark = $s.$data['remark']['value'];
		}
		$data['remark']['value'] = $remark;
		// $data['remark']['value']=urlencode('查看订单');
		$url = $params['url'].$order_id;
		$t_data = array(
			'url'=>$url,
			'topcolor'=>$color_config['topcolor'],
			'data'=>$data,
			);
		return $t_data;
	}
	//微信模板消息使用JSON方法 未测试
	public function mkWxTemplateContent($templateId,$touser,$template_data){
		$template_data['touser'] = $touser;
		$template_data['template_id'] = $templateId;
		$template_data_tojson = $this->JSON($template_data);
		return $template_data_tojson;
	}
	//ali通用方法
	private function toBizContentJson($biz_content, $toUserId) {
		// 如果toUserId为空，则是发给所有关注的而用户，且不可删除，慎用
		if (isset ( $toUserId ) && ! empty ( $toUserId )) {
			$biz_content ['toUserId'] = $toUserId;
		}
		$content = $this->JSON ( $biz_content );
		return $content;
	}
	/**
	 * 使用sdk中的异步单发消息接口，发送组装好的信息
	 *
	 * @param unknown $biz_content        	
	 */
	public function sendRequestSdk($biz_content) {
		$custom_send = new AlipayMobilePublicMessageCustomSendRequest ();
		$custom_send->setBizContent ( $biz_content );
		
		return aopclient_request_execute ( $custom_send );
	}
	public function sendTemplateRequestSdk($biz_content){
	
		$custom_send = new AlipayMobilePublicMessageSingleSendRequest ();
		$custom_send->setBizContent ( $biz_content );
		
		return aopclient_request_execute ( $custom_send );
	}
	public function sendAliRequestSdk($biz_content) {
		$custom_send = new AlipayMobilePublicMessageCustomSendRequest ();
		$custom_send->setBizContent ( $biz_content );

		$aop = new AopClient ($config);
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
				return true;
			case 'GBK' : // 如果是gbk编码
				return false;
		}
	}
	
	/**
	 * 下载用户发送过来的图片
	 *
	 * @param unknown $biz_content        	
	 * @param unknown $fileName        	
	 */
	public function downMediaRequest($biz_content, $fileName) {
		require 'config.php';
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
		// echo $biz_content;
		
		// print_r($paramsArray);
		require_once 'AlipaySign.php';
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
		//writeLog ( "请求图片地址：" . $url );
		file_put_contents ( $fileName, file_get_contents ( $url ) );
	}
	
	/**
	 * 异步发送消息给用户，未使用SDK,功能与sendRequest一样,如果有接口需要自己组装数据可以这样组装
	 *
	 * @param string $biz_content        	
	 * @param string $isMultiSend
	 *        	如果发给所有人，则此参数必须为true，且biz_content中的toUserId必须为空
	 * @return string
	 */
	public function sendMsgRequest($biz_content, $isMultiSend = FALSE) {
		require 'config.php';
		
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
		require_once 'AlipaySign.php';
		$as = new AlipaySign ();
		$sign = $as->sign_request ( $paramsArray, $config ['merchant_private_key_file'] );
		$paramsArray ['sign'] = $sign;
		// print_r ( $paramsArray );
		// 日志记录下受到的请求
		//writeLog ( var_export ( $paramsArray, true ) );
		return HttpRequest::sendPostRequst ( $config ['gatewayUrl'], $paramsArray );
	}

	/**
	 * 异步发送消息给用户，未使用SDK,功能与sendRequest一样,如果有接口需要自己组装数据可以这样组装
	 *
	 * @param string $biz_content        	
	 * @param string $isMultiSend
	 *        	如果发给所有人，则此参数必须为true，且biz_content中的toUserId必须为空
	 * @return string
	 */

	public function sendAliRequest($biz_content,$send_method) {
		require 'config.php';
		// echo $biz_content;

		$paramsArray = array (
				// 'method' => "alipay.mobile.public.message.single.send",
				'biz_content' => $biz_content,
				'charset' => $config ['charset'],
				'sign_type' => 'RSA',
				'app_id' => $config ['app_id'],
				'timestamp' => date ( 'Y-m-d H:i:s', time () ) 
		);
		$paramsArray['method'] = $send_method;
		require_once 'AlipaySign.php';
		$as = new AlipaySign ();
		$sign = $as->sign_request ( $paramsArray, $config ['merchant_private_key_file'] );
		$paramsArray ['sign'] = $sign;
		// print_r ( $paramsArray );
		// 日志记录下受到的请求
		writeLog ( var_export ( $paramsArray, true ) );
		$resp= HttpRequest::sendPostRequst ( $config ['gatewayUrl'], $paramsArray );
		$resp=iconv("GB2312", "UTF-8//IGNORE", $resp);
		$resp = json_decode($resp);
		writeLog ( var_export ( $resp, true ) );
		$resp->r_method = str_replace('.', '_', $send_method).'_response';
		return $resp;
	}

	public function sendWxPost($poststr,$url){
		global $_W;
		$token = account_weixin_token($_W['account']);
		// $token = 'A7e9fS12Ovn1nkicfCkz4xUOZ-S2yVCrxZ53fMHbfrElCSYCHiUKsvJlAjkY5870CfPUUCa-dSpDvmlLDfcaoRGvmtloar-YShsH5DxYSgo';
		$url = $url.$token;
		writeLog ( var_export ( $poststr, true ) );
		$aop = new AopClient ();
		$result = $aop->curl2($url, $poststr);
		$res = json_decode( $result);
		writeLog ( var_export ( $res, true ) );
		return $res;
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
