<?php
abstract class BaseModule {

	protected $model_name;
	public $user_info;

	public function __construct(){
		global $_W,$_GPC;
		$this->set_url_flag();
		$this->api_server = new ApiServer($_W['config']);
		$this->user = new User();
		$this->get_user_info();
		if(!allow_visit()){
			$this->check_login();
		}
	}

	// 设置访问标识信息
	public function set_url_flag() {
		global $_W, $_GPC;
		if(isset($_GPC['mark'])){
			// 如果从官网或第三方商城、第三方统计等返回，mark丢失则取之前缓存的mark
			set_mark($_GPC['mark']);
		}
		if(!allow_visit()){
			if(isset($_GPC['loginback_url'])){
				set_loginback_url($_GPC['loginback_url']);
			}else{
				remove_loginback_url();
			}
		}
	}
	
	protected function get_user_info(){
		global $_W,$_GPC;
		$this->user_info = $this->user->getUserInfo();
	}

	protected function template($filename, $type='third') {
		global $_W;
		$source = IA_ROOT."/framework/view/{$type}/"."$this->model_name/$filename.html";
		$compile = IA_ROOT."/data/tpl/{$type}/"."$this->model_name/$filename.tpl.php";
		if(!is_file($source)) {
			exit("Error: template source '{$filename}' is not exist!");
		}
		if (!is_file($compile) || filemtime($source) > filemtime($compile)) {
			template_compile($source, $compile, true);
		}
		return $compile;
	}

	public function check_login(){
		global $_W, $_GPC;
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$weixin_captcha = $_W['config']['captcha']['weixin'];
		$web_captcha = $_W['config']['captcha']['web'];
		$from_user = $this->user_info['from_user'] ?: '';
		$img_captcha = + (!$from_user && $web_captcha);
		
		if (!$this->user_info['is_login']) {
			if ($_GPC['loginback_url']) {
				$raw_request = $_GPC['loginback_url'];
			} else {
				$loginback_url = get_loginback_url();
				if($loginback_url){
					$raw_request = $loginback_url;
				}else{
					//此处跳转到homepage
					$raw_request = create_url('homepage/index');
				}
			}
			if (in_array($user_type, array('11', '12', '13', '15', '17', '18', '21','23','24','26','28','30','34')) && !empty($this->user_info['phone'])) {
				$tel = $this->user_info['phone'];
				$this->open_server = new OpenServer($_W['config'], $this->user_info);
				// ---获取真实用户ID，绑定用户手机，设置登录状态---
				$resp = $this->open_server->bind_user($tel, '', $user_type, $user_id, '', get_mark(), true);
				if ($resp['ret']) {
					$_SESSION['user_info']['user_id'] = '';
					$_SESSION['user_info']['phone'] = $tel;
					header('Location: ' . $raw_request);
					exit;
				}
			}

			if(get_mark() == 'eservice' || $this->user_info['user_type'] == 18) 
			{
				require_once IA_ROOT . '/framework/library/xiaoe/xiaoe.class.php';
				$redirectUrl = 'https://'.$_SERVER['HTTP_HOST'].$_SERVER["REQUEST_URI"];
				$_SESSION['user_info']['xiaoe_redirectUrl'] = $redirectUrl;
				$xiaoe = new xiaoe($_W['config']);
				$xiaoe->get_code($redirectUrl,'tel');
			}
			$this->model_name = 'base';
			include $this->template('check_login', 'wap');
			exit;
		}
	}

	/**
	 * 返回json格式数据
	 */
	public function retJson($data)
	{
		@ob_clean();
		echo json_encode($data);
		die;
	}

	/**
	 * url跳转
	 */
	public function jumpUrl($url)
	{
		if (!empty($url)) {
			header('Location: ' . $url);
		}
		die;
	}			
}
