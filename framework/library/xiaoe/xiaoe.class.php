<?php

/*****************************************
 *	第三方请求小E管家 post请求 暂不供get请求方式
 *****************************************/

require_once 'xiaoe_oauth/config.php';
require_once 'xiaoe_oauth/http.php';
require_once 'xiaoe_oauth/oauth.php';

class Xiaoe{

	public $access_token;
	public $version = 1.0;
	private $appid = 'xiyi';
	private $host;

	public function __construct($config)
	{
		$this->oauth = new Oauth(new Config($config));
		$this->xiaoe_host = $config['xiaoe_open']['host'];
		$this->appid = $config['xiaoe']['appid'];
	}

	function get_access_token()
	{
		
		$this->access_token = $this->oauth->getApiToken()->access_token;
		return $this->access_token;
	}

	function get_user_info($redirectUrl,$code)
	{
		$this->user_info = $this->oauth->getTokenByCode($redirectUrl,$code);
		return $this->user_info;
	}

	function get_code($redirectUrl,$scope = null)
	{
		$location = $this->oauth->getXiaoeLocation($redirectUrl,$scope);
		header("Location: ".$location);
		exit;
	}

	function create_order($post_data)
	{
		$time_arr = explode('-', $post_data['washing_time']);
		$this->access_token = $this->get_access_token();
		$order_data = array(
			'order_sn' => $post_data['order_sn'],
			'address_id' => $post_data['address_id'],
			'order_id' => $post_data['order_id'],
			'category_id' => $post_data['category_id'],
			'service_start_time' => $post_data['washing_date'].' '.$time_arr[0].':00',
			'service_end_time' => $post_data['washing_date'].' '.$time_arr[1].':00',
			'status' => '101',
			'notice' => $post_data['notice']
			);
		$order_data['ext_att'] = array('nub' => 5,'unit' => '件');
		$url = $this->xiaoe_host.'/order/create?access_token='.$this->access_token.'&version='.$this->version;
		// $url = 'http://127.0.0.1:9502/order/create?access_token=9ed6798d1ef6816cfd5b36a506ad3041c99349a7&version=1.0';
		$order = array(
			'appid' => $this->appid,
			'openid' => $post_data['from_user'],
			'order' => $order_data,
			);
		return $this->_post($url,$this->JSON($order));
	}

	function address_default($data)
	{
		$this->access_token = $this->get_access_token();
		$url = $this->xiaoe_host.'/address/address_default?access_token='.$this->access_token.'&version='.$this->version;
		// $url = 'http://127.0.0.1:9502/address/address_default?access_token=******&version=1.0';
		
		$address = array(
			'appid' => $this->appid,
			'openid' => $data['from_user'],
			);
		return json_decode($this->_post($url,$this->JSON($address)),true);
	}

	function order_comment($data)
	{
		$this->access_token = $this->get_access_token();
		$url = $this->xiaoe_host.'/order/modify?access_token='.$this->access_token.'&version='.$this->version;
		$order_data = array(
			'order_id' => $data['order_id'],
			'order_sn' => $data['order_sn'],
			'status' => '108'
			);
		$order = array(
			'appid' => $this->appid,
			'openid' => $data['from_user'],
			'order' => $order_data,
			);
		return json_decode($this->_post($url,$this->JSON($order)),true);
	}

	function arrayRecursive(&$array, $function) {
		foreach ($array as $key => $value ) {
			if (is_array($value)) {
				$this->arrayRecursive($array[$key], $function);
			} else {
				$array[$key] = $function($value);
			}
			
			if ($apply_to_keys_also && is_string($key)) {
				$new_key = $function($key);
				if ($new_key != $key) {
					$array[$new_key] = $array[$key];
					unset($array[$key]);
				}
			}
		}
	}

	function JSON($array) {
		$this->arrayRecursive($array, 'urlencode');
		$json = json_encode($array);
		return urldecode ($json);
	}
	
	function _post($url,$post_data)
	{
		$opts = array(
			'http' => array(
				'method' => 'POST',
				'header' =>	 'Content-type: application/x-www-form-urlencoded',
				'Content-Length' => strlen($post_data),
				'content' => $post_data
				)
			);
		$content = stream_context_create($opts);
		$result = file_get_contents($url, false, $content);
		return $result;
	}
}


// create_order();
// address_default();