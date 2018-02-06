<?php

class Luxury extends BaseModule{

	public function __construct(){
		//parent::__construct();
		global $_W;
		$this->api_server = new Apiserver($_W['config']);
		$this->model_name = 'luxury';
	}

	public function apply(){
		$http_user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
		if(strexists($http_user_agent, 'micromessenger')){
			$wx_pay =2;
		}else{
			$wx_pay = '';
		}
		include $this->template('apply');

	}

	public function fail_pay(){
		include $this->template('fail_pay');
	}

	public function success_pay(){
		include $this->template('success_pay');
	}

	public function second_pay(){
		include $this->template('success_pay');
	}

	public function luxury_create_pay(){
		global $_GPC;
		$tel = str_replace(PHP_EOL, '', $_GPC['tel']);
		$code = str_replace(PHP_EOL, '', $_GPC['code']);
		$name = $_GPC['name'];
		$paytype = $_GPC['paytype'];
		$res = $this->api_server->luxury_create_pay($tel,$code,$paytype,$name);
		
		if(empty($res['data'])){
			$result['state'] = '2';
			$result['msg'] = $res['error'];
		}else{
			$_SESSION['pay_species'] = 'luxury';
			$uuid = md5(time().random(8));
			//$res['data']['fee'] = 0.01;
			$params['tid'] = $res['data']['trade_no'];
			$params['fee'] = $res['data']['fee'];
			$params['title'] = '用户充值'.$params['fee'];
			$params['user'] = $tel;
			$params['paytype'] = $paytype;
			mcache()->set($uuid,serialize($params), 1800);
			$result['state'] = '1';
			$result['msg'] = '~';
			$result['url'] = create_url('luxury/pay',array('uuid' =>  $uuid),'third');
		}
		message($result,'',"ajax");
	}

	public function pay(){
		global $_W, $_GPC;
		if(!empty($_GPC['uuid'])){
			$params = unserialize(mcache()->get($_GPC['uuid']));
			if(empty($params)){
				include $this->template('fail_pay');
				exit;
			}
		}else{
			include $this->template('fail_pay');
			exit;
		}
		if ($params['paytype'] == 6) {	#支付宝支付
			$http_user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
			# 如果微信用户选择支付宝支付
			if(strexists($http_user_agent, 'micromessenger')){
				# 重定向到支付宝支付页
				$url = create_url('luxury/ali_pay',array('uuid' => $_GPC['uuid']),'third');
				header('Location: '.$url);
				exit;
			}
			$_SESSION['pay_species'] = 'luxury';
			require_once IA_ROOT . '/framework/model/payment.mod.php';
			$ret = alipay_build($params, $_W['account']['payment']['alipay']);
			if ($ret['html']) {
				echo $ret['html'];
				exit();
			}
			if ($ret['url']) {
				header("location: " . $ret['url']);
				exit();
			}
		}else if($params['paytype'] == 2){ #微信支付
			require_once IA_ROOT . '/framework/model/payment.mod.php';
			$sl = base64_encode(json_encode($params));
			$auth = sha1($sl . $_W['weid'] . $_W['config']['setting']['authkey']);
			header("location: {$_W['config']['site']['root']}payment/wechat/pay.php?weid={$_W['weid']}&auth={$auth}&ps={$sl}");
			exit();
		}
	}

	public function ali_pay(){
		$http_user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
		if(strexists($http_user_agent, 'micromessenger')){
			include $this->template('ali_pay');
		}else{
			$this->pay();
		}
	}

	public function verification_code(){
		global $_GPC;
		$tel = str_replace(PHP_EOL, '', $_GPC['tel']);
		if(!check_mobile($tel)){
			$result['state'] = 0;
			$result['msg'] = '请正确填写手机号';
			message($result, '', 'ajax');
			exit;
		}
		$res = $this->api_server->luxury_send_code($tel);
		//$res['data'] = ture;
		if($res['data']){
			$result['state'] = 1;
			$result['msg'] = '验证码发送成功';
			message($result, '', 'ajax');
		}else{
			$result['state'] = 0;
			$result['msg'] = '验证码发送失败';
			message($result, '', 'ajax');
		}
	}

}