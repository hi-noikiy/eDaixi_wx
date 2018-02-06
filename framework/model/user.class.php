<?php
class User {
	private $user_id;
	private $from_user;
	private $user_token;
	private $is_login;
	public $user_info;
	function __construct() {
		$this->initVar();
	}

	/**
	 * 初始化内部变量
	 */
	private function initVar()
	{
		//初始化user_info
		$this->user_info = array(
			//用户id
			'user_id' => 0,
			//用户类型
			'user_type' => 0,
			//第三方用户标识(如微信给的open_id, 小e管家给的open_id 等)
			'from_user' => null,
			//暂时没用到
			'client_id' => 0,
			//用户级别标识, open接口签名用
			'user_token' => null,
			//登录后存储用户手机号
			'is_login' => null
		);
	}

	/**
	 * 获取用户信息
	 */
	public function getUserInfo()
	{
		global $_GPC;
		$user_info = $this->user_info;
		// 从 session 中获取用户信息
		$session_user_info = $this->session_user_info();
		// 合并session中的用户信息, 入口处会判断并在session里写入user_type
		$user_info = array_merge($user_info, $session_user_info);
		$this->user_info = $user_info;
		// 从 open_server 中获取用户信息并存入 session
		if ((empty($user_info['user_id']) &&
		    !empty($user_info['from_user'])) || $_GPC['m'] == 'third') {
			//-- 存 session 在 open_server_user_info 中实现
			$this->open_server_user_info();
		}
		return $this->user_info;
	}
	
	// 从 session 中获取用户信息赋值给 User::user_info
	public function session_user_info() {
		global $_W,$_GPC;
		if ($_W['config']['setting']['development'] == 51 &&
		 	empty($_SESSION['user_info']['user_id'])) {	# 模拟微信公众号

			// $this->user_info['from_user'] = 'qwqeqwerwerwedfs8897997';
			$this->user_info['from_user'] = 'ouCgTws60YGBtnvsJPe77C8kjYuQ';


			$this->user_info['user_type'] = '1';
			return $this->user_info;
		}else if ($_W['config']['setting']['development'] == 61 &&
				  empty($_SESSION['user_info']['user_id'])) {	# 模拟百度直达号
			$this->user_info['from_user'] = '';
			$this->user_info['user_type'] = '13';
			return $this->user_info;
		}
		// 从 session 中获取用户信息
		if (!empty($_SESSION['user_info'])) {
			$this->user_info = $_SESSION['user_info'];
		}
		return $this->user_info;
	}
	
	// 从 open_server 中获取用户信息并存入 session
	public function open_server_user_info() {
		global $_W;
		if (empty($this->user_info['from_user'])) {
			return array();
		}
		$this->open_server = new OpenServer($_W['config'], $this->user_info);
		$res = $this->open_server->bind_http_user($this->user_info['from_user'], $this->user_info['user_type']);
		$this->get_open_server_user = $res['data'];
		$this->user_info['user_id'] = $this->get_open_server_user['user_id'];
		$this->user_info['client_id'] = $this->get_open_server_user['client_id'];
		$this->user_info['user_token'] = $this->get_open_server_user['user_token'];
		$this->user_info['is_login'] = $this->get_open_server_user['mobile'];
		if(empty($this->user_info['is_login']) && !empty($_SESSION['user_info']['phone']) && (get_mark() == 'eservice' || $this->user_info['user_type'] == 18))
		{
			$this->bind_user();
		}
		// 把用户信息存入 session
		$_SESSION['user_info'] = $this->user_info;
		return $this->user_info;
	}

	private function bind_user()
	{
		global $_W;
		$this->open_server = new OpenServer($_W['config'], $this->user_info);
		$resp = $this->open_server->bind_user($_SESSION['user_info']['phone'], '', 18, $this->user_info['user_id'], '', get_mark(), true);
		
		if ($resp['ret']) {
			//$data  = json_decode($resp['data'],true);
			
			$res = $this->open_server->bind_http_user($this->user_info['from_user'], $this->user_info['user_type']);
			$this->get_open_server_user = $res['data'];
			$this->user_info['user_id'] = $this->get_open_server_user['user_id'];
			$this->user_info['client_id'] = $this->get_open_server_user['client_id'];
			$this->user_info['user_token'] = $this->get_open_server_user['user_token'];
			$this->user_info['is_login'] = $this->get_open_server_user['mobile'];
		}
	}
}
