<?php

class Getinfo {

	private $usertoken;
	private $ts;
	private $sign;

	public function __construct(){
		
	}

	public function get_access_token(){
		global $token,$usertoken,$data_json,$ts,$sign,$version;
		$_GPC = file_get_contents('php://input');
		$_GPC = json_decode($_GPC,true);
   
		$this->token = "rongchainapi";
		$this->sign= $_GPC['sign'];
		$this->ts = $_GPC['ts'];

		$result = $this->checkapi();
		
		if($result['ret']){
			$token = account_weixin_token();
			$data_ret['access_token'] = $token;	
			$result['ret'] = true;
			$result['error'] = "";
			$result['error_code'] = "0";
			$result['version'] = $version;
			$result['ts'] = time();
			$data_ret = json_encode($data_ret);
			//$data_ret = urlencode($data_ret);
			$result['data'] = $data_ret;
		}
		echo json_encode($result);
	}

	private function checkapi(){
		$sign_check = md5($this->ts.$this->token);
		if ($this->sign != $sign_check)  {
			$result['ret'] = false;
			$result['error'] = "加载失败，请重试";
			$result['error_code'] = "10001";
			if (!empty($usertoken)) {
				$result['error_code'] = "10002";
			}
			$result['ts'] = time();
			$result['data'] = "";
			$result['sign'] = md5($result['data'].$result['ts'].$token.$usertoken);
		}else {
			$result['ret'] = true;
		}
		return $result;
	}
}