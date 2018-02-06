<?php

class Thirdparty extends BaseModule{

	public function __construct(){
		parent::__construct();
		global $_W;
		$this->api_server = new Apiserver($_W['config']);
	}

	public function Kaola(){

		global $_W, $_GPC;
		$from_user = $this->user_info['from_user'];
		$this->api_server->acquire_coupon(134, $from_user, $_W['user_type']);
		header("Location:".HTTP_TYPE.$_SERVER['HTTP_HOST']."/mobile.php?m=wap&act=homepage&do=index");
		//include $this->template('wap_index');
	}

	public function game(){
		global $_W, $_GPC;
		$kind = $_GPC['extra'];
		$from_user = $this->user_info['from_user'];
		if(empty($this->user_info['user_id'])){
			$this ->user_info = $this->user->open_server_user_info();
		}
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		
		//$coupon_lists = array('10' => 351, '30' => 352, '50' => 353);
		/*
		40-10，优惠券ID2405；（原10元档）
        99-30，优惠券ID2404,；（原30元档）
        199-50，优惠券ID2403。（原50元档）
		 */
		$coupon_lists = array('10' => 2405, '30' => 2404, '50' => 2403);
		
		if (array_key_exists($kind, $coupon_lists)) {
			$coupon_list_id = $coupon_lists[$kind];
			$this->api_server->acquire_coupon($coupon_list_id, $from_user, $user_type);
		}
		header("Location: http://game.edaixi.com/index.html?user_id=".$user_id);
	}

	public function gameLogin(){
		global $_W, $_GPC;

	 	$game = $_GPC['game'];
	 	if(empty($this->user_info['user_id'])){
			$this ->user_info = $this->user->open_server_user_info();
		}
		$user_id = $this->user_info['user_id'];
		$from_user = $this->user_info['from_user'];
		
	 	if(!empty($game)){
			$callback_url = $_GPC['callback_url'];
			$token = account_weixin_token();
			$url='https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$token.'&openid='.$from_user;
			$json=ihttp_get($url);
			$userinfo=json_decode($json['content'], true);
			header('Location: '.$callback_url."?nickname=".urlencode($userinfo['nickname']).'&openid='.$from_user.'&headimgurl='.urlencode($userinfo['headimgurl']).'&code=qwe');	
		}else{
	 		$customer = pdo_fetch("SELECT * FROM ".tablename('fans')." WHERE id = :id", array(':id' => $user_id));
	 		$data = 'nickname='.$customer['nickname'].'&openid='.$from_user.'&headimgurl='.$_W['config']['upload']['attachurl'].$customer['avatar'];
	 		header("Location: http://game4.edaixi.com/game/index.html?data=".base64_encode($data));	
	 	}
	}

	public function game2(){
		global $_W, $_GPC;

		$kind = $_GPC['extra'];
		$from_user = $this->user_info['from_user'];
		$user_id = $this->user_info['user_id'];
		$coupon_lists = array('10' => 306, '30' => 307, '50' => 308);
		if (array_key_exists($kind, $coupon_lists)) {
			$coupon_list_id = $coupon_lists[$kind];
			$this->api_server->acquire_coupon($coupon_list_id, $from_user, $this->user_info['user_type']);
		}
		header("Location: http://game4.edaixi.com/game/index.html?user_id=".$user_id);
	}
}