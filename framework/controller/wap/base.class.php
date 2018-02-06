<?php
abstract class BaseModule {

	protected $model_name;
	public $user_info;
	public function __construct() {
		global $_W, $_GPC;
		$this->set_url_flag();
		$this->get_user_info();
		if(!allow_visit()){
			$this->check_login();
		}
		//第三方对接【免登陆状态】，不用点击【登录】按钮就可以直接登录
		$user_type = $this->user_info['user_type'];
		$user_id = $this->user_info['user_id'];
		if (in_array($user_type, array('11', '12', '13','14', '15', '17', '18', '21','23','24','26','28','29','30','31','34')) && !empty($this->user_info['phone']) && !$this->user_info['is_login']) {
                $tel = $this->user_info['phone'];
                $this->open_server = new OpenServer($_W['config'], $this->user_info);
                // ---获取真实用户ID，绑定用户手机，设置登录状态---
                $resp = $this->open_server->bind_user($tel, '', $user_type, $user_id, '', get_mark(), true);
                if ($resp['ret']) {
                    $_SESSION['user_info']['user_id'] = '';
                    $_SESSION['user_info']['phone'] = $tel;
                }
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
	
	protected function get_user_info() {
		global $_W, $_GPC;
		$this->user = new User();
		$this->user_info = $this->user->getUserInfo();
		//积分处理
		if(get_mark() == 'eservice' || $this->user_info['user_type'] == 18)
		{
			return;
		}
		if($this->user_info['user_id']){
			// 每日积分
			$memkey = 'daily_points_' . $this->user_info['user_id'] . '_' . date('Ymd');
			if(! mcache()->get($memkey)){
				require_once IA_ROOT . '/framework/library/edaixi/SwServer.class.php';
				$points = new SwServer($this->user_info['user_id']);
				$points->daily_points();
			}
		}
		//$this->user_info['user_id'] = 6669353;//6667011;//;
		//$this->user_info['is_login'] = 121223232232;
	}
	
	public function check_login() {
		global $_W, $_GPC;
		
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$weixin_captcha = $_W['config']['captcha']['weixin'];
		$web_captcha = $_W['config']['captcha']['web'];
		$from_user = $this->user_info['from_user'] ?: '';
		$img_captcha = + (!$from_user && $web_captcha);
		
		if (!$this->user_info['is_login']) {
			// 来自红包（记录手机号）
			$hongbao_tel = isset($_GPC['hongbao_tel']) ? $_GPC['hongbao_tel'] : '';
			if ($_GPC['loginback_url']) {
				$raw_request = $_GPC['loginback_url'];
			} else {
				$loginback_url = get_loginback_url();
				if($loginback_url){
					$raw_request = $loginback_url;
				}else{
					$raw_request = HTTP_TYPE . $_SERVER['HTTP_HOST'] . $_SERVER["REQUEST_URI"];
				}
			}
			// 洗衣液价目页情况
			$is_detergent_order_place =  stripos($raw_request, '/api.php?m=wap&act=mall&do=detergent_order_place');
			if(false !== $is_detergent_order_place){
				$loginback_url = add_params('/new_weixin/view/detergent_order.html', array(
						'city_id'	=>	$_GPC['city_id'],
					));
				$url = create_url('homepage/dear_login_error', array(
						'loginback_url'	=>	$loginback_url,
					));
			}
			// 普洗多品类的情况
			$is_comm_order_place = stripos($raw_request, '/api.php?m=wap&act=homepage&do=comm_order_place');
			if (false !== $is_comm_order_place){
				$loginback_url = add_params('/new_weixin/view/order_place.html', array(
						'category_id'	=>	$_GPC['category_id'],
						'price_read'	=>	$_GPC['price_read'],
						'city_id'	=>	get_user_city()['city_id'],
					));
				$url = create_url('homepage/dear_login_error', array(
						'hongbao_tel'	=>	$_GPC['hongbao_tel'],
						'loginback_url'	=>	$loginback_url,
					));
			}
			// 开发票的入口
			$is_invoice = stripos($raw_request, '/api.php?m=wap&act=invoice&do=get_invoice_details');
			if (false !== $is_invoice){
				$loginback_url = '/new_weixin/view/invoice_order_list.html';
				$url = create_url('homepage/dear_login_error', array(
						'hongbao_tel'	=>	$_GPC['hongbao_tel'],
						'loginback_url'	=>	$loginback_url,
					));
			}
			// 写字楼快洗情况
			$is_office = stripos($raw_request, '/api.php?m=wap&act=homepage&do=office_order_place');
			if (false !== $is_office){
				$loginback_url = add_params('/new_weixin/view/offices_fast_place_order.html', array(
						'category_id'	=>	$_GPC['category_id'],
						'price_read'	=>	$_GPC['price_read'],
						'city_id'	=>	$_GPC['city_id'],
					));
				$url = create_url('homepage/dear_login_error', array(
						'hongbao_tel'	=>	$_GPC['hongbao_tel'],
						'loginback_url'	=>	$loginback_url,
					));
			}/*
			if (in_array($user_type, array('11', '12', '13', '15', '17', '18', '21','23','24','26','28','30')) && !empty($this->user_info['phone'])) {
				$tel = $this->user_info['phone'];
				$this->open_server = new OpenServer($_W['config'], $this->user_info);
				// ---获取真实用户ID，绑定用户手机，设置登录状态---
				$resp = $this->open_server->bind_user($tel, '', $user_type, $user_id, '', get_mark(), true);
				if ($resp['ret']) {
					$_SESSION['user_info']['user_id'] = '';
					$_SESSION['user_info']['phone'] = $tel;
					// 普洗情况
					if (false !== $is_comm_order_place || false !== $is_invoice || false !== $is_office){
						echo_json(false, '', array(
								'message'	=>	'',
								'url'	=>	$loginback_url,
							));
					}
					header('Location: ' . $raw_request);
					exit;
				}
			}*/
			if(get_mark() == 'eservice' || $this->user_info['user_type'] == 18) 
			{
				require_once IA_ROOT . '/framework/library/xiaoe/xiaoe.class.php';
				$redirectUrl = HTTP_TYPE.$_SERVER['HTTP_HOST'].$_SERVER["REQUEST_URI"];
				$_SESSION['user_info']['xiaoe_redirectUrl'] = $redirectUrl;
				$xiaoe = new xiaoe($_W['config']);
				$xiaoe->get_code($redirectUrl,'tel');
			}
			// 普洗情况 || 开发票 || 快洗 || 洗衣液
			if (false !== $is_comm_order_place || false !== $is_invoice || false !== $is_office || false !== $is_detergent_order_place){
				echo_json(false, '', array(
						'message'	=>	'请先登录',
						'url'	=>	$url,
					));
			}
			$this->model_name = 'base';
			include $this->template('check_login');
			exit;
		}
	}
	
	public function login_back() {
		global $_W, $_GPC;
		if ($_GPC['loginback_url']) {
			$raw_request = $_GPC['loginback_url'];
		} else {
			$loginback_url = get_loginback_url();
			if($loginback_url){
				$raw_request = $loginback_url;
			}else{
				$raw_request = HTTP_TYPE . $_SERVER['HTTP_HOST'] . $_SERVER["REQUEST_URI"];
			}
		}
		header('Location: ' . $raw_request);
		exit;
	}

/**
 * 分享的数据
 * @return array [
 *         'call_back_url' => '',
 *         'from_user_id' => '',
 *         'from_active_id' => '',
 *         'share_user_id' => '',
 *         'share_active_id' => '',
 *         'depth' => '',
 *         'active_type' => ''
 *         ]
 */
	protected function shareRecodeData()
	{
		$call_back['is_weixin'] = is_from_weixin() ? 1 : 0;
		$call_back['url'] = $this->getShareUrl();
		$call_back['call_back_url'] = $this->getCallBackUrl();
		$call_back['from_user_id'] = $this->getFromUserId();
		$call_back['from_active_id'] = $this->getFromActiveId();
		$call_back['share_user_id'] = $this->getShareUserId();
		$call_back['share_active_id'] = $this->getShareActiveId();
		$call_back['depth'] = $this->getDepth()+1;
		$call_back['active_type'] = $this->getActiveType();
		return $call_back;
	}

	protected function getShareUrl($url)
	{
		return  HTTP_TYPE.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'&daf='.$this->getShareActiveId().'&duf='.$this->encryptionUserId().'&depth='.($this->getDepth()+1);
	}

	protected function getCallBackUrl()
	{
		return create_url('homepage/shareRecode');
	}

	protected function getFromUserId()
	{
		global $_GPC;
		if(empty($_GPC['duf'])){
			return 0;
		}
		if((strlen($_GPC['duf'])%2) != 0){
			return 0;
		}
		$user_id_arr = str_split($_GPC['duf']);
		$from_user_id = '';
		foreach ($user_id_arr as $key => $value) {
			if($key%2 == 0 ) continue;
			$from_user_id .= $value;
		}
		return intval($from_user_id);
	}

	protected function encryptionUserId()
	{
		if(empty($this->user_info['user_id'])){
			return 0;
		}
		$user_id_arr = str_split($this->user_info['user_id']);
		$user_id_str = '';
		foreach ($user_id_arr as $key => $value) {
			$user_id_str .= rand(1,9).$value;
		}
		return $user_id_str;
	}

	protected function  getFromActiveId()
	{
		return isset($_GPC['daf']) ? $_GPC['daf'] : 0;
	}

	protected function getShareActiveId()
	{
		return isset($this->active_id) ? $this->active_id : 0;
	}

	protected function getShareUserId()
	{
		return isset($this->user_info['user_id']) ? $this->user_info['user_id'] : 0;
	}

	protected function getDepth()
	{
		global $_GPC;	
		if(isset($_GPC['depth'])){
			return $_GPC['depth'];
		}else{
			return 0;
		}
	}

	protected function getActiveType()
	{
		return isset($this->active_type) ? $this->active_type : 0;
	}

	protected function getWxShareJs()
	{
		if(is_from_weixin()){
			require IA_ROOT.'/framework/library/wxshare/include.php';
		}
	}
/**
 * 分享的记录
 * @return ''
 */
	public function shareRecode()
	{
		global $_GPC;
		require_once IA_ROOT . '/framework/library/edaixi/SwServer.class.php';
		$this->sw_server = new SwServer($this->user_info['user_id']);
		$data['depth'] = $this->getDepth();
		$data['from_user_id'] = $_GPC['fud'];
		$data['from_active_id'] = $_GPC['fad'];
		$data['share_user_id'] = $_GPC['ud'];
		$data['share_active_id'] = $_GPC['ad'];
		$data['type'] = $_GPC['active_type'];
		isset($_GPC['is_fail']) && $data['is_fail'] = $_GPC['is_fail'];
		$this->sw_server->activeShareRecode($data);
		return ;
	}

	protected function template($filename, $model_name = '') {
		global $_W, $_GPC;
		$class = $_GPC['act'];
		$func = $_GPC['do'];

		if (empty($model_name)) {
			$model_name = $this->model_name;
		}
		$source = IA_ROOT . "/framework/view/wap/" . "$model_name/$filename.html";
		$compile = IA_ROOT . "/data/tpl/" . "$model_name/$filename.tpl.php";
		if (!is_file($source)) {
			exit("Error: template source '{$filename}' is not exist!");
		}

		if (!is_file($compile) || filemtime($source) > filemtime($compile)) {
			template_compile($source, $compile, true);
		}
		return $compile;
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
