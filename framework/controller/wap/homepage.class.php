<?php
use Gregwar\Captcha\CaptchaBuilder;
class Homepage extends BaseModule {
	function __construct(){
		global $_W;
		parent::__construct();
		$this->model_name = 'homepage';
		$this->default_city_id = 1;
		$this->default_city_name = '北京';
		$this->open_server = new OpenServer($_W['config'],$this->user_info);
	}

	// 生成图形验证码
	public function generate_captcha(){
		require IA_ROOT . '/framework/library/Captcha/CaptchaBuilderInterface.php';
		require IA_ROOT . '/framework/library/Captcha/PhraseBuilderInterface.php';
		require IA_ROOT . '/framework/library/Captcha/CaptchaBuilder.php';
		require IA_ROOT . '/framework/library/Captcha/PhraseBuilder.php';
		header('Content-type: image/jpeg');
		$builder = CaptchaBuilder::create()->build();
		$_SESSION['captcha'] = $builder->getPhrase();
		$_SESSION['captcha_time'] = time();
		$builder->output();
	}
	
	// 校验图形验证码
	public function verify_captcha($captcha){
		if(!$captcha || !$_SESSION['captcha'] || (strtoupper($captcha) != strtoupper($_SESSION['captcha']))){
			return array(
				'state' => 0,
				'msg' => '图形验证码错误',
			);
		}
		// 验证码5分钟有效
		if(time() - intval($_SESSION['captcha_time']) > 300){
			unset($_SESSION['captcha_time']);
			unset($_SESSION['captcha']);
			return array(
				'state' => 0,
				'msg' => '图形验证码已失效'
			);
		}
		if(strtoupper($captcha) == strtoupper($_SESSION['captcha'])){
			unset($_SESSION['captcha_time']);
			unset($_SESSION['captcha']);
			return array(
				'state' => 1,
				'msg' => '图形验证码正确'
			);
		}
	}
	
	// 获取短信验证码
	public function get_sms_code(){
		global $_W,$_GPC;
		$weixin_captcha = $_W['config']['captcha']['weixin'];
		$web_captcha = $_W['config']['captcha']['web'];
		
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$from_user = $this->user_info['from_user'] ?: '';
		$mark = get_mark();
		$ip = getip();
		$captcha = $_GPC['captcha'];
		// 记录短信验证码日志
		logging('谁在获取短信验证码', array(
			'user_id' => $user_id,
			'from_user' => $this->user_info['from_user'],
			'tel' => $_GPC['tel'],
			'IP' => $ip
		), 'a+', 'get_sms_code');
		
		$tel = str_replace(PHP_EOL, '', $_GPC['tel']);
		//此处写死一个手机号，不需要输入图形验证码，做自动化测试使用
		if($tel == '13439072813'){
			$web_captcha = false;
		}
		if(!check_mobile($tel)){
			$result['state'] = 0;
			$result['msg'] = '请正确填写手机号';
			message($result, '', 'ajax');
			exit;
		}
		if($from_user && $weixin_captcha){// 验证请求次数（每天限制十次）
			$request_count = get_captcha_count($from_user, 'sms');
			if($request_count >= 10){
				$result['state'] = 0;
				$result['msg'] = '操作过于频繁，请联系客服';
				message($result, '', 'ajax');
				return;
			}
		}else if($web_captcha){
			$result = $this->verify_captcha($captcha);
			if(1 != $result['state']){
				message($result, '', 'ajax');
				return;
			}
		}
		// 调用接口
		$resp = $this->open_server->send_sms($tel, $user_id);
		if($resp['data']){
			if($from_user && $weixin_captcha){
				set_captcha_count($from_user, 'sms'); // 缓存请求次数
			}
			$result['state'] = 1;
			$result['msg'] = '验证码已发送';
			message($result, '', 'ajax');
		}else{
			$result['state'] = 0;
			$result['msg'] = $resp['error'] ?: "重试次数过多,请10分钟后重试";
			message($result, '', 'ajax');
		}
	}
	
	// 获取验语音证码
	public function get_voice_code(){
		global $_W,$_GPC;
		$weixin_captcha = $_W['config']['captcha']['weixin'];
		$web_captcha = $_W['config']['captcha']['web'];
		
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$from_user = $this->user_info['from_user'] ?: '';
		$mark = get_mark();
		$ip = getip();
		$captcha = $_GPC['captcha'];
		// 记录短信验证码日志
		logging('谁在获取语音验证码', array(
			'user_id' => $user_id,
			'from_user' => $this->user_info['from_user'],
			'tel' => $_GPC['tel'],
			'IP' => $ip
		), 'a+', 'get_voice_code');
		
		$tel = str_replace(PHP_EOL, '', $_GPC['tel']);
		if(!check_mobile($tel)){
			$result['state'] = 0;
			$result['msg'] = '请正确填写手机号';
			message($result, '', 'ajax');
			exit;
		}
		if($from_user && $weixin_captcha){// 验证请求次数（每天限制十次）
			$request_count = get_captcha_count($from_user, 'voice');
			if($request_count >= 10){
				$result['state'] = 0;
				$result['msg'] = '操作过于频繁，请联系客服';
				message($result, '', 'ajax');
				return;
			}
		}else if($web_captcha){
			$result = $this->verify_captcha($captcha);
			if(1 != $result['state']){
				message($result, '', 'ajax');
				return;
			}
		}
        // 调用接口
		$resp = $this->open_server->send_voice_sms($tel, $user_id);
		if($resp['data']){
			if($from_user && $weixin_captcha){
				set_captcha_count($from_user, 'voice'); // 缓存请求次数
			}
			$result['state'] = 1;
			$result['msg'] = $resp['data']['text'] ?: '验证码已发送';
			message($result, '', 'ajax');
		}else{
			$result['state'] = 0;
			$result['msg'] = $resp['error'] ?: "系统繁忙,请稍后重试";
			message($result, '', 'ajax');
		}
	}
	
	// 获取真实用户ID，绑定用户手机，设置登录状态
	public function bind_user_mobile (){
		global $_W,$_GPC;
		$temp_user_id = $this->user_info['user_id']; # 临时用户ID
		$user_type = $this->user_info['user_type'];
		$client_id = $this->user_info['client_id'];
		$from_user = $this->user_info['from_user'] ?: '';
	
		// 记录绑定用户手机日志
		logging('绑定用户手机', array(
			'temp_user_id' => $temp_user_id,
			'from_user' => $from_user,
			'mobile' => $_GPC['tel'],
			'IP' => getip()
		));
	
		$tel = str_replace(PHP_EOL, '', $_GPC['tel']);
		$code = $_GPC['code'];
		$mark = get_mark();
	
		// 获取用户首页城市
		$user_city = get_user_city();
		$city_id = $user_city['city_id'];
		$city_name = $user_city['city_name'];
	
		// ---获取真实用户ID，绑定用户手机，设置登录状态---
		$resp = $this->open_server->bind_user($tel, $code, $user_type, $temp_user_id, $city_id, $mark);
		if($resp['ret']){
			// 修改微信用户绑定时手机号生成token错误
			if($temp_user_id){
				$this->user_info = $this->user->open_server_user_info();
			}else{
				$res = $resp['data'];
				$_SESSION['user_info']['user_id'] = $res['user_id'];
				$_SESSION['user_info']['user_token'] = $res['user_token'];
				$_SESSION['user_info']['client_id'] = $res['client_id'];
				$_SESSION['user_info']['is_login'] = $tel;
			}
			// 处理显示投保信息
			if ($temp_user_id){
				// 删除临时的,替换成真实的
				$has_show_city = redis()->keys('insurance:'. $temp_user_id . ':*' );
				if ($has_show_city){
					foreach ($has_show_city as $key => $value) {
						redis()->delete($value);
						$temp = str_ireplace($temp_user_id, $_SESSION['user_info']['user_id'], $value);
						redis()->set($temp, $_SERVER['REQUEST_TIME']);
					}
				}
			}elseif($_COOKIE['insurance']){
				$insurance = unserialize($_COOKIE['insurance']);
				foreach ($insurance as $key => $value) {
					redis()->set('insurance:' . $user_id . ':' . $value, $_SERVER['REQUEST_TIME']);
				}
				setcookie('insurance', '', time()-1);
				unset($_COOKIE['insurance']);
			}
			$result['state'] = 1;
			$result['msg'] = "登录成功";
			message($result, '', 'ajax');
		}else{
			$result['state'] = 0;
			$result['msg'] = $resp['error'] ?: "验证码错误";
			message($result, '', 'ajax');
		}
	}

	public function view_api_log(){
		global $_W,$_GPC;
		$data = $_POST;
		logging('前端接口统计', $data, 'a+', 'view_api');
	}

	public function index(){
		global $_W,$_GPC;
		include IA_ROOT . "/new_weixin/index.html";
		// if($_SESSION['user_info']['user_type'] == '14') {
			echo '<script type="text/javascript" src="https://u.nuomi.com/skeleton.js?appId=10160"></script>
					<script type="text/javascript">
					try
					{
					  NMJS.ui.setTitle("e袋洗-洗衣");
					}
					catch(err)
					{
					  //txt="此页面存在一个错误。\n\n"
					  //txt="错误描述: " + err.description + "\n\n"
					  //txt+="点击OK继续。\n\n"
					  //alert(txt)
					  //}
					}
					</script>';
		// }

	}

	// 平台首页
	public function index_api(){
		global $_W,$_GPC;
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$is_login = $this->user_info['is_login'];
		$client_id = $this->user_info['client_id'];
		$from_user = $this->user_info['from_user'] ?: '';
		$mark = get_mark();
		$is_from_eservice = is_from_eservice();
		if($is_from_eservice){
			$title = '洗衣';
		}else{
			$title = 'e袋洗';
		}
		$city_id = '';
		$city_name = '';
		$switch_city = '';
		$loc_flag = 'do';	# 默认进入首页需要自动定位
		
		// 初始化城市列表
		$redis = redis();
		if(! $redis->get('city_list:cache')){
			$open_city_list = $this->open_server->get_citys();
			foreach ($open_city_list as $key => $value) {
				$city['city_id'] = $value['city_id'];
				$city['city_name'] = $value['city_name'];
				$city_list[] = $city;
			}
			set_city_list($city_list);
		}
		// 用户手动切换首页城市
		if($_GPC['city_id']){
			$switch_city = 'switch_city';
			$loc_flag = 'donot'; # 不定位
			$city_id = $_GPC['city_id'];
			$city_name = check_city_id($city_id);
			set_user_city($city_id, $city_name);
		}else{
			// 获取用户首页城市,此处增加一个默认参数，此处专用定位
			$user_city = get_user_city(1);
			$city_id = $user_city['city_id'];
			$city_name = $user_city['city_name'];
			if($city_id && $city_name){
				$loc_flag = 'donot'; # 不定位
			}else{
				$loc_flag = 'do'; # 执行定位
			}
		}

		// 城市列表url
		$city_list_url = create_url('homepage/city_list', array('state'=>'userSw', 'city_id'=>$city_id));
		// 来自红包（记录手机号）
		$hongbao_tel = !empty($_GPC['hongbao_tel']) ? $_GPC['hongbao_tel'] : '';
				
		if(empty($city_id) || !is_numeric($city_id) || empty($city_name)){
			$city_id = 	$this->default_city_id;
			$city_name = $this->default_city_name;
		}
			
		$query_data = array();
		$query_data['city_id'] = $city_id;
		$query_data['mark'] = $mark;
		if(empty($this->user_info['is_login']) && $hongbao_tel){
			$query_data['hongbao_tel'] = $hongbao_tel;
		}

		//open缓存时间(此处获取的结果，至少与user_type和city_id有关)
 		$lg_expire_time = 900;

		// banner 轮转图
		$banner = $redis->hget('lg_banner', $user_type.'_'.$city_id);
		if(!$banner){
			$did = 'index'; $button_width = ''; $button_height = ''; $banner_width = ''; $banner_height = '';
			$banner_list = $this->open_server->get_banner_list($banner_width,$banner_height,$user_type,$mark,$city_id,$user_id);
			if ($banner_list['error_code'] == 40001){
				echo_json(false, '', array(
					'message' =>  '网络问题，请稍后重试',
					'url' =>  create_url('homepage/index', array(
							'city_id'	=>	$city_id,
							'mark'	=>	$mark,
							'hongbao_tel'	=>	$hongbao_tel,
						)),
				));
			}
			$banner = $this->in_web_url($banner_list, 'banner', $query_data);

			$use_cache = empty($banner) ? false : true;
			foreach ($banner as $key => $val) {
				//异常情况是图片未显示，暂且先判断图片
				if(empty($val['image_url'])){
					$use_cache = false;
				}
			}
			if($use_cache){
				$redis->hset('lg_banner', $user_type.'_'.$city_id, serialize($banner));
	 			//初始时,设置过期时间
	 			if($redis->ttl('lg_banner') == -1){
	 				$redis->settimeout('lg_banner', $lg_expire_time);
	 			}
			}
		}else{
			$banner = unserialize($banner);
		}

		$recharge_banner = '';
		// 页面中间宣传活动banner (如:充值, 洗衣液促销,洗鞋促销等活动)
		foreach ($banner as $key => $val){
			if($this->filter_app_url($banner[$key]['url'])){
				unset($banner[$key]);
				continue;
			}
			if($val['put_under']){
				$recharge_banner = $banner[$key];
				unset($banner[$key]);
				continue;
			}
		}
		// 品类列表（新接口）
		$category_list = $redis->hget('lg_category_list', $user_type.'_'.$city_id);
		if(!$category_list){
			$category_list  = $this->open_server->get_category_buttons($city_id, $user_type, $mark, $user_id);
			// -- 普通品类
			$category_list[0]['list'] = $category_list[0]['list'] ? $this->in_web_url($category_list[0]['list'], 'new', $query_data) : (object)array();
			//讲道理，没有城市会不显示普洗内容
			if(empty((array)$category_list[0]['list'])){
				echo_json(false, '', array(
					'message' =>  '网络问题，请稍后重试',
					'url' =>  create_url('homepage/index', array(
							'city_id'	=>	$city_id,
							'mark'	=>	$mark,
							'hongbao_tel'	=>	$hongbao_tel,
						)),
				));
			}
			// -- 奢侈品类
			$category_list[1]['list'] = $category_list[1]['list'] ? $this->in_web_url($category_list[1]['list'], 'category', $query_data) : (object)array();
			// -- 快洗品类
			$category_list[2]['list'] = $category_list[2]['list'] ? $this->in_web_url($category_list[2]['list'], 'category', $query_data) : (object)array();

			$use_cache = true;
			foreach ($category_list as $key => $val) {
				foreach ($val['list'] as $k => $v) {
					if(empty($v['image_url'])){
						$use_cache = false;
					}
				}
			}
			if($use_cache){
				$redis->hset('lg_category_list', $user_type.'_'.$city_id, serialize($category_list));
	 			//初始时,设置过期时间
	 			if($redis->ttl('lg_category_list') == -1){
	 				$redis->settimeout('lg_category_list', $lg_expire_time);
	 			}
			}
		}else{
			$category_list = unserialize($category_list);
		}

		// 获取爆品接口
		$promotional_list = $redis->hget('lg_promotional_list', $user_type.'_'.$city_id);
		if(!$promotional_list){
			$promotional_list = $this->open_server->get_promotional_offers($city_id, $user_type, 1, 3, $user_id);
			if ((isset($promotional_list['ret']) && !$promotional_list['ret']) || !$promotional_list['details']){
				$promotional_list = (object)array();
			}else{
				$promotional_list['details'] = $this->in_web_url($promotional_list['details']);
			}

			$use_cache = true;
			if(empty((array)$promotional_list)){
				$use_cache = false;
			}else{
				foreach ($promotional_list['details'] as $key => $val) {
					if(empty($val['image_url'])){
						$use_cache = false;
					}
				}
			}
			if($use_cache){
				$redis->hset('lg_promotional_list', $user_type.'_'.$city_id, serialize($promotional_list));
	 			//初始时,设置过期时间
	 			if($redis->ttl('lg_promotional_list') == -1){
	 				$redis->settimeout('lg_promotional_list', $lg_expire_time);
	 			}
			}

		}else{
			$promotional_list = unserialize($promotional_list);
		}

		// 页底功能列表
		$bottom_button = $redis->hget('lg_bottom_button', $user_type.'_'.$city_id);
		if(!$bottom_button){
			$bottom_list  = $this->open_server->get_func_button_list($button_width,$button_height,$user_type,$mark,$city_id,$user_id);
			$bottom_button = $this->in_web_url($bottom_list, 'menu', $query_data);
				
			if(!$is_from_eservice){
				// 用户反馈
				$bottom_button[3]['url'] = create_url('icard/feedback');
			}

			$use_cache = empty($bottom_button) ? false : true;
			foreach ($bottom_button as $key => $val) {
				if(empty($val['image_url'])){
					$use_cache = false;
				}
			}
			if($use_cache){
				$redis->hset('lg_bottom_button', $user_type.'_'.$city_id, serialize($bottom_button));
	 			//初始时,设置过期时间
	 			if($redis->ttl('lg_bottom_button') == -1){
	 				$redis->settimeout('lg_bottom_button', $lg_expire_time);
	 			}
			}
		}else{
			$bottom_button = unserialize($bottom_button);
		}

		// 积分动画缓存键
		$show_points = false;
		if($user_id && $is_login && $user_type != 14){
			$points_key = 'homepage_points_' . $user_id . '_' . date('Ymd');
			if(!mcache()->get($points_key)){ # 未显示首页积分
				$expire = strtotime(date('Y-m-d', strtotime('+1 day'))); # 失效时间：次日零时
				mcache()->set($points_key, 1, $expire - time());
				$show_points = true;
			}
		}

		// 意见反馈链接
		$feedback_url = create_url('icard/feedback');

		// 首页三条好评
		$favourable_comments = $redis->hget('lg_favourable_comments', $city_id);
		if(!$favourable_comments){
			$favourable_comments = $this->open_server->get_favourable_comments(true, 1, 3, $city_id)['comments'];

			$use_cache = empty($favourable_comments) ? false : true;
			foreach ($favourable_comments as $key => $val) {
				if(empty($val['comment'])){
					$use_cache = false;
				}
			}
			if($use_cache){
				$redis->hset('lg_favourable_comments', $city_id, serialize($favourable_comments));
 	 			//初始时,设置过期时间
 	 			if($redis->ttl('lg_favourable_comments') == -1){
 	 				$redis->settimeout('lg_favourable_comments', $lg_expire_time);
 	 			}
			}
		}else{
			$favourable_comments = unserialize($favourable_comments);
		}

		$favourable_comments_url = create_url('homepage/favourable_comments', array('city_id'=>$city_id));
		// 是否显示下单投保提示
		if ($user_id){
			$show_insurance = redis()->get('insurance:' . $user_id . ':' . $city_id) ? false : true;
		}elseif($_COOKIE['insurance']){
			$insurance_city = unserialize($_COOKIE['insurance']);
			$show_insurance = $insurance_city[$city_id] ? false : true;
		}else{
			$show_insurance = true;
		}
		// 投保的数据
		$insurance_data = '';
		if($show_insurance){
			$insurance_data = $this->open_server->get_insurance_claims_info($city_id);
			if($insurance_data) {
				if($user_id && $city_id){
					redis()->set('insurance:' . $user_id . ':' . $city_id, $_SERVER['REQUEST_TIME']);
				}else{
					$insurance_city = $_COOKIE['insurance'] ? unserialize($_COOKIE['insurance']): array();
					$insurance_city[$city_id] = $city_id;
					$res = setcookie('insurance', serialize($insurance_city), time()+315360000);
				}
			}
		}
		// 城市定位ajax urldecode(str)
		$locateCityUrl = create_url('homepage/ajax_locate_city');
		// 保持异地城市ajax url
		$keepCityUrl = create_url('homepage/ajax_keep_city');
		// 判断是否显示平安免责的弹窗
		$show_pingan_flag = is_from_psdzbt_pingan();
		echo_json(true, array(
				'title'	=>	$title,
				'is_from_eservice'	=>	$is_from_eservice,
				'city_id'	=>	$city_id,
				'city_name'	=>	$city_name,
				'user_type'	=>	$user_type,
				'loc_flag'	=>	$loc_flag,
				'locateCityUrl'	=>	$locateCityUrl,
				'keepCityUrl'	=>	$keepCityUrl,
				'city_list_url'	=>	$city_list_url,
				'banner'	=>	$banner,
				'recharge_banner'	=>	$recharge_banner,
				'category_list'	=>	$category_list,
				'promotional_list'	=>	$promotional_list,
				'bottom_button'	=>	$bottom_button,
				'show_points'	=>	$show_points,
				'favourable_comments'	=>	$favourable_comments,
				'favourable_comments_url'	=>	$favourable_comments_url,
				'show_insurance'	=>	$show_insurance,
				'insurance_data'	=>	$insurance_data,
				'show_pingan_flag'  =>  $show_pingan_flag
			));
	}
	// ajax 获取原foot页面数据
	public function ajax_get_foot(){
		global $_W,$_GPC;
        $userId = false;
		if($_SESSION['user_info']["is_login"] && $_SESSION['user_info']["user_id"]){
            $userId = $_SESSION['user_info']["user_id"]; 
        }
		echo_json(true, array(
				'user_type'	=>	$this->user_info['user_type'],
				'statistics'	=>	$_W['config']['statistics'],
				'piwik'	=>	$_W['config']['piwik']['setting'],
				'userId'	=>	$userId,
				'mark'	=>	get_mark(),
			));
	}
	
	//首页 AJAX 经纬度换取当前城市
	public function ajax_locate_city(){
		global $_W,$_GPC;
		// 定位状态（默认失败）
		$state = 'failed';
		
		if(!empty($_GPC['city'])){
			$locate_city = rtrim($_GPC['city'], '市');
		}else{
			// 根据经纬度获取城市（百度地图）
			$url = 'http://api.map.baidu.com/geocoder/v2/?ak=ng34qw8fcqE9k8GNI5Bgs1N5&callback=renderReverse&location='
					. $_GPC['latitude'].','.$_GPC['longitude'].'&output=xml&pois=1';
			$resp = ihttp_get($url);
			$resp_obj = simplexml_load_string($resp['content'], 'SimpleXMLElement', LIBXML_NOCDATA);
			$resp_arr = json_decode(json_encode((array) $resp_obj), true);
			$locate_city = $resp_arr['result']['addressComponent']['city'];
			
			// 经纬度查询城市失败
			if(!$locate_city){
				$result['state'] = $state;
				$result['city_id'] = '';
				$result['city_name'] = '';
				message($result, '', 'ajax');
				exit;
			}
			
			// 经纬度查询城市成功
			$len = mb_strlen($locate_city, 'utf-8');
			$char = mb_substr($locate_city, $len-1, $len, 'utf-8');
			if($char == '市'){
				$locate_city = mb_substr($locate_city, 0, $len-1, 'utf-8');
			}
		}
	
		// 定位完成，假设不在服务范围
		$state = 'unServ';
				
		// 判断定位城市是否在服务范围
		$return = check_city_name($locate_city);
		if($return){
			// 在服务范围
			$state = 'inServ';
			$city_id = $return;
			$city_name = $locate_city;
			if(! $_GPC['unchange']){
				// 设置首页当前城市为定位城市
				set_user_city($city_id, $city_name);
			}
		}
	    
		// 返回定位城市状态（unServ-不在服务范围、 inServ-在服务范围）
		$result['state'] = $state;
		$result['city_id'] = $city_id;
		$result['city_name'] = $city_name;
		message($result, '', 'ajax');
		exit;
	}
	
	// 城市列表页
	public function city_list(){
		global $_W,$_GPC;
		$user_type = $this->user_info['user_type'];
		$open_city_list = $this->open_server->get_citys();
		foreach ($open_city_list as $key => $value) {
			$city['city_id'] = $value['city_id'];
			$city['city_name'] = $value['city_name'];
			$city_list[] = $city;
		}
		set_city_list($city_list);
		
		$city_id = $_GPC['city_id'];
		$state = $_GPC['state'];
		//$state 值介绍：
		//--userSw 用户自行切换
		//--failed 定位失败，自动跳转
		//--unServ 定位城市不在服务范围，自动跳转
		//--inServ 定位城市在服务范围，用户确认后跳转
		include $this->template('city_list');
	}
	
	// 取消城市切换（保留在异地城市）
	public function ajax_keep_city(){
		global $_W,$_GPC;
		$city_id = $_GPC['kcity_id'];
		$city_name = $_GPC['kcity_name'];
		if($city_id){
			set_user_city($city_id, $city_name);
		}
	}

	// public function comm_api_test(){
	// 	global $_W,$_GPC;
	// 	// 下单展示信息
	// 	$city_id = 1;
	// 	$user_type = 1;
	// 	$user_id = 10206498;
	// 	// $user_id = 6674202;
	// 	$order_page_info = $this->open_server->hybrid_order_page($city_id, $user_type, -1, $user_id);
	// 	echo_json($order_page_info['ret']===false?false:true);
	// }
	
	/**************************** 新版普洗接口 begin ******************************/
	// 普洗价目页展示的接口
	public function show_price(){
		global $_W,$_GPC;
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$mobile = $this->user_info['is_login'];
		$from_user = $this->user_info['from_user'] ?: '';
		$mark = get_mark();
		$hongbao_tel = !empty($_GPC['hongbao_tel']) ? $_GPC['hongbao_tel'] : '';
		if($_GPC['city_id']){
			$city_id = intval($_GPC['city_id']);
		}else{
			// 用户当前首页城市
			$city_info = get_user_city();
			$city_id = $city_info['city_id'];
			$city_name = $city_info['city_name'];
		}
		$error_url = create_url('homepage/index',array(
					'mark'	=>	$mark,
					'city_id'	=>	$city_id,
					'hongbao_tel'	=>	$hongbao_tel,
				));
		if(!$city_id && !$city_name){
			// 返回接口错误
			echo_json(false, '', array('message' => '城市信息错误', 'url'	=>	$error_url));
		}
	
		// 价目页数据信息走缓存 10分钟
		$price_list = mcache()->get('price_list:' . $user_id  . ":" . $city_id);
		if( false == $price_list ){
			// 价目数据
			$price_list = $this->open_server->get_normal_categories_price($city_id, $user_type, $user_id, $mark);
			if(false === $price_list['ret']){
				// 返回接口错误
				echo_json(false, '', array('message' => '网络出错', 'url'	=>	$error_url));
			}
			$query_data = array();
			$query_data['city_id'] = $city_id;
			$query_data['mark'] = $mark;
			if(empty($this->user_info['is_login']) && $hongbao_tel){
				$query_data['hongbao_tel'] = $hongbao_tel;
			}
			foreach ($price_list as $key => &$value) {
				if ($value['banners']){
					$value['banners'] = $this->in_web_url($value['banners'], 'banners', $query_data);
				}
				//临时给雅居乐、浦发、三星使用，屏蔽价目页顶部的banner
				if(in_array($user_type, array(34, 26, 27))){
                    $value['banners'] = array();
                }
			}
			mcache()->set('price_list:' . $user_id  . ":" . $city_id, $price_list, 600);
		}
		echo_json(true, $price_list);
	}
	// 用来处理用户签名冲突的中转方法
	public function dear_login_error(){
		global $_W,$_GPC;
		header('Location: ' . urldecode($_GPC['loginback_url']));
		exit;
	}

	// 普洗下单接口
	public function comm_order_place(){
		global $_W,$_GPC;
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$mobile = $this->user_info['is_login'];
		$from_user = $this->user_info['from_user'] ?: '';
		$mark = get_mark();
		$price_read = $_GPC['price_read'] ?: 0;
		$category_id = $_GPC['category_id'] ?: '';
		// 用户当前首页城市
		// if($_GPC['city_id']){
		// 	$city_id = $_GPC['city_id'];
		// }else{
		$city_info = get_user_city();
		$city_id = $city_info['city_id'];
		$city_name = $city_info['city_name'];
		// }
		$error_index_url = create_url('homepage/index', array(
				'mark'	=>	$mark,
				'city_id'	=>	$city_id,
			));
		$error_price_url = add_params('/new_weixin/view/washing_prices.html', array(
							'mark'	=>	$mark,
							'city_id'	=>	$city_id,
						));
		if(!$city_id){
			echo_json(false, '', array(
					'message'	=>	'城市信息错误',
					'url'	=>	$error_index_url,
				));
		}
		// 品类校验
		if(empty($category_id)){
			echo_json(false, '', array(
					'message'	=>	'品类信息错误',
					'url'	=>	$error_price_url
				));
		}
		// 价目页
		if(empty($price_read)){
			echo_json(false, '', array(
					'message'	=>	'请先查看价目页',
					'url'	=>	$error_price_url
				));
		}
		// 下单展示信息
		$order_page_info = $this->open_server->hybrid_order_page($city_id, $user_type, -1, $user_id);
		if(false === $order_page_info['ret']){
			$error = array(
					'message'	=>	'网络问题，请稍后重试',
					'url'	=>	'',
				);
			if($order_page_info['error_code'] == 40001){
				$loginback_url = add_params('/new_weixin/view/order_place.html', array(
						'category_id'	=>	$_GPC['category_id'],
						'price_read'	=>	$_GPC['price_read'],
						'city_id'	=>	$_GPC['city_id'],
					));
				$error['url'] = create_url('homepage/dear_login_error', array(
						'hongbao_tel'	=>	$_GPC['hongbao_tel'],
						'loginback_url'	=>	$loginback_url,
					));
			}
			echo_json(false, '', $error);
		}
		// 处理默认地址,不出现接口数据为null
		$order_page_info['default_address'] = $order_page_info['default_address'] ?: (object)array();
		/**
		 * 下单来源:
		 *  首页下单 
		 * */
		$back_params = array(
				'city_id' => $city_id, 
				'mark' => $mark,
				'price_read' => 1,
			);
		// 下单页-->地址列表页 URL
		$select_addr_url = create_url('address/order_address_list', array(
				'link_from'	=>	'comm_order_place',
				'back'	=>	urlencode(add_params('/new_weixin/view/order_place.html', $back_params)),
		));
		// 提交订单的url

		$submit_url = create_url('homepage/do_order');
		echo_json(true, array(
			'order_page_info'	=>	$order_page_info,
			'select_addr_url'	=>	$select_addr_url,
			'submit_url'	=>	$submit_url,
			));
	}
	/**************************** 新版普洗接口 end ******************************/

	// 预约取件
	public function order_place(){
		global $_W,$_GPC;
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$mobile = $this->user_info['is_login'];
		$from_user = $this->user_info['from_user'] ?: '';
		$mark = get_mark();
		$price_read = $_GPC['price_read'] ?: 0;
		$category_id = $_GPC['category_id'] ?: '';
		$sub_id = $_GPC['sub_id'] ?: '';
		$select_address = $_GPC['select_address'] ?: '';
		if(! $select_address){
			$address_id = $_GPC['address_id'] ?: '';
		}
		$washing_date = $_GPC['washing_date'] ?: '';
		$washing_time = $_GPC['washing_time'] ?: '';
		$time_range = $_GPC['time_range'] ?: '';
		$take_soon = $_GPC['take_soon'] ?: '';
		$comment = $_GPC['comment'] ?: '';
		$order_id = $_GPC['order_id'] ?: '';
		$continue_order = $order_id;  // 是否来自"继续下单"
		$guide = $_GPC['guide'];	  // 是否来自"品类引导"
		$replace_order = $_GPC['replace_order'];	// 代下单
		// 品类校验
		if(empty($category_id)){
		    error_report('品类信息错误');
		}
		// 用户当前首页城市
		$city_info = get_user_city();
		$city_id = $city_info['city_id'];
		$city_name = $city_info['city_name'];
		if(!$city_id || !$city_name){
			header('Location: ' . create_url('homepage/index'));
			exit;
		}
		// 下单展示信息 -- 默认地址/是否新用户/可用优惠券/品类引导/运费公告
		$order_page_info = $this->open_server->create_order_page($city_id, $user_type, $category_id, $user_id, $sub_id, $address_id, $order_id);
		// -- 城市价目信息 --
		$price_info = $order_page_info['price'];
		// === 初始化官网价目URL ===
		$query_data =  array(
				'city_id' => $city_id, 
				'mark' => $mark, 
				'sub_id' => $sub_id, 
				'uid' => $this->user_info['user_id']
		);
		if($continue_order){
			$query_data['continue_order'] = 1;
			$washing_date = $order_page_info['washing_date'];
			$washing_time = $order_page_info['washing_time'];
			$time_range = json_encode($order_page_info['time_range']);
		}
		$price_url = $price_info['price_url'] = add_params($price_info['price_url'], $query_data);
		if($continue_order || $guide){ // 官网价目URL(携带参数)
			if($continue_order){
				$query_data['continue_order'] = 1;
				$query_data['order_id'] = $order_id;
			}elseif($guide){
				$query_data['guide'] = 1;
			}
			$query_data['address_id'] = $address_id;
			$query_data['washing_date'] = $washing_date;
			$query_data['washing_time'] = $washing_time;
			$query_data['time_range'] = $time_range;
		    $price_url_with_params = add_params($price_url, $query_data);
		}
		if(in_array($category_id, array(60, 61))){	// 如果是改衣品类,则必须跳转价目页勾选衣物
			$categories_ids = $_GPC['categories_ids'] ?: '';
			$clothes_ids = $_GPC['clothes_ids'] ?: '';
			if(empty($categories_ids) || empty($clothes_ids)){
			    header('Location: ' . ($price_url_with_params ?: $price_url));
				exit;
			}
		}else{	// 除改衣品类外,如果是当前品类的新用户，则先跳转官网价目页
			$new_user = !$order_page_info['is_old'];
			if(!$price_read && $new_user && $price_url){
				header('Location: ' . ($price_url_with_params ?: $price_url));
				exit;
			}
		}

		// === 初始化下单用户地址 ===
		if($category_id == 13){ // 酒店快洗
			$refer = $_GPC['refer'] ?: '';
			$hotel_tip = '酒店用户';
			$hotel_id = $_GPC['hotel_id'] ?: '';
			$hotel_name = $_GPC['hotel_name'] ?: '';
			$area = $_GPC['hotel_area'] ?: '';
			$room = $_GPC['room'] ?: '';
			$uname = $_GPC['uname'] ?: '';
			$mobile = $_GPC['mobile'] ?: $mobile;
			$btn_status = 'disabled="disabled"';
			$btn_style = 'noBook';
			$serach_url = create_url('address/hotel_address', array('category_id'=>13, 'sub_id'=>$sub_id, 'city_id'=>$city_id));
		}else{
			/**
			 * 下单来源:
			 *  首页下单 
			 * 	继续下单
			 *  扫码揽收(单独页面)
			 * 	小e管家
			 * */
			if(is_from_eservice()){ // 小e管家
				$addr_info = $this->get_eservice_address($from_user, $category_id, $sub_id);
				$select_addr_url = $addr_info['select_addr_url'];
			}else{
				if($select_address){
					$addr_info = encrypt('DE', $select_address);
				}else{
					$addr_info = $order_page_info['default_address'];
				}
				$back_params = array(
						'city_id' => $city_id, 
						'mark' => $mark,
						'category_id' => $category_id, 
						'sub_id' => $sub_id,
						// 'select_address' => $select_address,
						// 'address_id' => $address_id,
						// 'washing_date' => $washing_date,
						// 'washing_time' => $washing_time,
						// 'time_range' => $time_range,
						'take_soon' => $take_soon,
						'comment' => $comment,
						'guide' => $guide,
						'replace_order' => $replace_order,
						'price_read' => 1,
					);
				// 下单页-->地址列表页 URL
				$select_addr_url = create_url('address/order_address_list', array(
						'address_id' => $addr_info['address_id'] ?: '',
						'category_id' => $category_id,
						'sub_id' => $sub_id,
						'link_from'	=>	'order_place',
						'back'	=>	urlencode(create_url('homepage/order_place', $back_params)),
				));
			}
			$address_id = $addr_info['address_id'] ?: '';
			$area = $addr_info['area'] ?: '';
		}

		if(!empty($replace_order) && !isset($_GPC['address_id'])){
			$addr_info = array();
			$address_id = '';
		}
		
		// === 初始化下单日期时段 ===
		$service_time = $this->open_server->get_service_time($category_id, $city_id, $area);
		$service_time_arr = $this->format_service_time($service_time, $category_id);
		$service_date = $service_time_arr['dk'];
		$service_time_bucket = $service_time_arr['tk'];
		$service_time_usable = count($service_time_bucket) > 0 ? 1 : 0;
		$select_datetime = '';
		if($washing_date){
		    if($service_date[$washing_date]['selectable']){
		        $date_text = $service_date[$washing_date]['date_text'];
		    }else{
		        $washing_date = '';
		    }
		}
		if($washing_time){
		    if($service_time_bucket[$washing_date][$washing_time]['selectable']){
		        $time_text = $service_time_bucket[$washing_date][$washing_time]['time_text'];
		    }else{
		        $washing_time = '';
		    }
		}
		if($date_text && $time_text){
		    $select_datetime = $date_text . ' ' . $time_text;
		}else{
			$take_soon = '';
		}
		$washing_date = empty($washing_date) ? date('Y-m-d') : $washing_date; // 默认预约当天时段
		
		// -- 品类引导信息 --
		$guide_category_url = create_url('homepage/order_place', array(
		    'city_id' => $city_id,
		    'category_id' => $order_page_info['recommend']['category'],
		    'sub_id' => $order_page_info['recommend']['sub_id']
		));
		$guide_category_text = $order_page_info['recommend']['text'];
		// -- 运费公告信息 --
		$delivery_fee = $order_page_info['delivery_fees'];
		// -- 价目链接title --
		if(in_array($category_id, array(4, 5))){
		    $price_info['price_title'] = ($city_id == 12 || $city_id == 15) ? '预约成功后请等待上门取件' : '预约成功请等待奢护管家上门取件';
		}else{
		    $price_info['price_title'] = '预约成功请等待小e上门取件';
		}
		// -- 优惠券展示 --
		$coupon_tips = $order_page_info['coupon'];
		// -- title 及 皮肤 --
		$skin = '';
		if (!empty($order_page_info['title'])) {
			$title = $order_page_info['title'];
		} else {
			if($category_id == 1){
			    $title = '专业清洗—洗衣';
			}else if($category_id == 2){
			    $title = '专业清洗—洗鞋';
			}elseif($category_id == 3){
			    $title = '专业清洗—洗窗帘';
			}elseif($category_id == 4){
			    $title = '高端服饰家纺精洗';
			    $skin = 'highOrder';
			}elseif($category_id == 5){
			    $title = '奢侈品皮具养护';
			    $skin = 'highOrder';
			}elseif($category_id == 13){
			    $title = '专业清洗—酒店快洗';
			}elseif(in_array($category_id, array(60, 61))){
			    $title = '服装修改';
			    $skin = 'gaiyifu';
			}else{
			    $title = 'e袋洗';
			}
		}
		// 提交订单的url
		$submit_url = create_url('homepage/do_order');
		if($category_id == 13){
			include $this->template('hotel_order');
		}else{
			if(!empty($replace_order)){
				$page_conf = $this->getReplaceOrderPageConf($replace_order);
				$help_conf = $this->getPageHelpConf($page_conf['default_help_confs'], $coupon_tips);
				$share_data = $this->shareRecodeData();
				$share_data['title'] = $page_conf['share_title'];
				$share_data['desc'] = $page_conf['share_desc'];
				$share_data['img'] = $page_conf['share_img'];
				// echo '<pre>';	var_dump($share_data);exit;
				if(empty($page_conf)){
					header('Location: ' . HTTP_TYPE . $_SERVER['HTTP_HOST']);
					exit;
				}
				include $this->template('replace_order');
			}else{
				include $this->template('comm_order');
			}
		}
	}
	/**************************** 写字楼快洗 begin ******************************/
	
	// 写字楼快洗目页展示的接口
	public function show_office_price(){
		global $_W,$_GPC;
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$mark = get_mark();
		$hongbao_tel = !empty($_GPC['hongbao_tel']) ? $_GPC['hongbao_tel'] : '';
		$category_id = $_GPC['category_id'] ?: '';	// 写字楼快洗为17
		if($_GPC['city_id']){
			$city_id = intval($_GPC['city_id']);
		}else{
			// 用户当前首页城市
			$city_info = get_user_city();
			$city_id = $city_info['city_id'];
			$city_name = $city_info['city_name'];
		}		
		$error_url = create_url('homepage/index',array(
				'mark'	=>	$mark,
				'city_id'	=>	$city_id,
				'hongbao_tel'	=>	$hongbao_tel,
			));
		if(!$city_id && !$city_name){
			// 返回接口错误
			echo_json(false, '', array('message' => '城市信息错误', 'url'	=>	$error_url));
		}
		if(!$category_id){
			// 返回接口错误
			echo_json(false, '', array('message' => '品类信息错误', 'url'	=>	$error_url));
		}
		// 价目数据
		$offic_price_list = $this->open_server->get_price_by_category_id($user_id, $city_id, $user_type, $category_id);
		if(false === $offic_price_list['ret']){
			// 返回接口错误
			echo_json(false, '', array('message' => '网络出错', 'url'	=>	$error_url));
		}
		$query_data = array();
		$query_data['city_id'] = $city_id;
		$query_data['mark'] = $mark;
		if(empty($this->user_info['is_login']) && $hongbao_tel){
			$query_data['hongbao_tel'] = $hongbao_tel;
		}
		foreach ($offic_price_list as $key => &$value) {
			if ($value['banners']){
				$value['banners'] = $this->in_web_url($value['banners'], 'banners', $query_data);
			}
		}
		echo_json(true, $offic_price_list);
	}

	// 写字楼快洗下单页数据接口
	public function office_order_place(){
		global $_W,$_GPC;
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$mobile = $this->user_info['is_login'];
		$category_id = $_GPC['category_id'] ?: '';	// 写字楼快洗为17
		$price_read = $_GPC['price_read'] ?: '';
		if($_GPC['city_id']){
			$city_id = intval($_GPC['city_id']);
		}else{
			// 用户当前首页城市
			$city_info = get_user_city();
			$city_id = $city_info['city_id'];
			$city_name = $city_info['city_name'];
		}
		$home_url = create_url('homepage/index',array(
				'mark'	=>	$mark,
				'city_id'	=>	$city_id,
			));
		$price_url = add_params('/new_weixin/view/offices_fast_prices.html', array(
				'mark'	=>	$mark,
				'city_id'	=>	$city_id,
			));
		if(!$city_id && !$city_name){
			echo_json(false, '', array('message' => '城市信息错误', 'url'	=>	$home_url));
		}
		if(!$category_id){
			echo_json(false, '', array('message' => '品类信息错误', 'url'	=>	$home_url));
		}
		if(!$price_read){
			echo_json(false, '', array('message' => '请先查看价目页', 'url'	=>	$price_url));
		}
		$page_info = $this->open_server->get_order_page($user_id, $city_id, $user_type, $category_id);
		if(false === $page_info['ret']){
			$error = array(
					'message'	=>	'网络问题，请稍后重试',
					'url'	=>	$home_url,
				);
			if($page_info['error_code'] == 40001){
				$loginback_url = add_params('/new_weixin/view/offices_fast_prices.html', array(
						'category_id'	=>	$_GPC['category_id'],
						'price_read'	=>	$_GPC['price_read'],
						'city_id'	=>	$_GPC['city_id'],
					));
				$error['url'] = create_url('homepage/dear_login_error', array(
						'hongbao_tel'	=>	$_GPC['hongbao_tel'],
						'loginback_url'	=>	$loginback_url,
					));
			}
			echo_json(false, '', $error);
		}
		// 用户手机号
		$page_info['tel'] = $this->user_info['is_login'];
		$page_info['office_address_url'] = create_url('address/office_address');
		echo_json(true, $page_info);
	}

	/**************************** 写字楼快洗 end ******************************/

	private function getPageHelpConf($default_help_confs = array(), $coupon_tips = '')
	{
		!empty($coupon_tips) && $default_help_confs[] = $coupon_tips;
		$delivery_fee_1 = $this->getDeliveryFeeSettings(1);
		$delivery_fee_2 = $this->getDeliveryFeeSettings(2);
		!empty($delivery_fee_1) && $default_help_confs[] = $delivery_fee_1;
		!empty($delivery_fee_2) && $default_help_confs[] = $delivery_fee_2;
		return $default_help_confs;
	}

	private function getReplaceOrderPageConf($url)
	{
		if(empty($url)){
			return false;
		}
		require_once IA_ROOT . '/framework/library/edaixi/SwServer.class.php';
		$this->getWxShareJs();
		$swapi = new SwServer($this->user_info['user_id']);
		$config =  $swapi->getReplaceOrderPageConf($url);
		$this->active_id = $config['id'];
		$this->active_type = 9; 
		return $config;
	}

	private  function getDeliveryFeeSettings($category_id = 1)
	{
		global $_W;
		$api_server = new ApiServer($_W['config']);
		$res = $api_server->getDeliveryFeeSettings(get_user_city()['city_id'], $category_id);
		usort($res['data'], function($a, $b){
			if($a['sentinel_min'] == $b['sentinel_min']) return 0;
			return $a['sentinel_min'] > $b['sentinel_min'] ? 1 : -1;
		});
		switch ($category_id) {
			case 1:	$category_name = '洗衣订单';break;
			case 2:	$category_name = '洗鞋订单';break;
		}
		$delivery_fee = '';
		if(count($res['data']) < 2){
			$delivery_fee .= '免运费'; 
		}else{
			foreach ($res['data'] as $key => $value) {
				if(isset($res['data'][$key+1]['sentinel_min'])){
					$delivery_fee .= empty($delivery_fee) ? '' : '；'; 
					$delivery_fee .= '满'.$res['data'][$key+1]['sentinel_min'].'，免收'.$value['delivery_fee'].'元运费';
				}else{
					break;
				}
			}
		}
		return $category_name.$delivery_fee;
	}
			
    // 提交预约订单 (修改请注意，代下单功能中创建订单用到此方法！！！)
    public function do_order(){
    	global $_W,$_GPC;
    	$user_id = $this->user_info['user_id'];
    	$user_type = $this->user_info['user_type'];
    	$client_id = $this->user_info['client_id'];
    	$from_user = $this->user_info['from_user'] ?: '';
    	$mark = get_mark();
    	$is_from_eservice = is_from_eservice();
    	
    	// 获取用户首页城市
    	$user_city = get_user_city();
    	$city_id = $user_city['city_id'];
    	
		// 异常情况未获取city_id时，默认1（北京），暂不影响下单
        if(empty($city_id) || !is_numeric($city_id) || $city_id < 0 ){
	    	$city_id = 1;
	    }
	    
    	// 获取选择品类ID
    	if(isset($_GPC['category_id'])){
    		// 区分普洗(可以多品类下单)和其他
    	    if(is_numeric($_GPC['category_id'])){
    	    	$categories = '';
    	    	$category_id = $_GPC['category_id'];
    	    }else{
    	    	$category_id = -1;
    	    	$categories = $_GPC['category_id'];
    	    }
    	}
    	if(empty($category_id) && empty($categories)){
    	    $result['msg'] = '品类信息错误，请重试';
    	    $result['state'] = 0;
    	    message($result, '' ,"ajax");
    	}
    	$comment = $_GPC['comment'] ?: '';
    	if($category_id == 17){ // 写字楼快洗
    	    $office_building_id = $_GPC['office_building_id'];
    	    $room = $_GPC['room'];
    	    $user_name = $_GPC['user_name'];
    	    $tel = $_GPC['tel'];
    	    if(empty($office_building_id) || empty($room)|| empty($user_name) || empty($tel)){
    	        $result['msg'] = '请填写酒店信息、姓名和手机号';
    	        $result['state'] = 0;
    	        message($result,'',"ajax");
    	    }
    	}else{
    		if ($category_id == 61){ // 改衣服务
    			$clothes_ids = $_GPC['clothes_ids'] ?: '';
    			$categories_ids = $_GPC['categories_ids'] ?: '';
    			if(empty($clothes_ids) || empty($categories_ids)){
    				$result['msg'] = '请选择要修改的衣务';
    				$result['state'] = 0;
    				message($result,'',"ajax");
    			}
    		}
    		$address_id = intval($_GPC['address_id']);
    		if(empty($address_id)){
    			$result['msg'] = '请选择/填写地址';
    			$result['state'] = 0;
    			message($result,'',"ajax");
    		}
    		$take_soon = intval($_GPC['take_soon']) ? true : false;
    		if($take_soon){
    			$comment = '立即取件 ' . $comment;
    		}
    	}
    	$washing_date = isset($_GPC['washing_date']) ? $_GPC['washing_date'] : '';
    	$washing_time = isset($_GPC['washing_time']) ? $_GPC['washing_time'] : '';
    	$time_range = isset($_GPC['time_range']) ? $_GPC['time_range'] : '';
    	if(empty($washing_date) || empty($washing_time) || empty($time_range)){
    	    $result['msg'] = '请选择预约时间';
    	    $result['state'] = 0;
    	    message($result,'',"ajax");
    	}
    	if($comment == '请填写注意事项（选填）' || urldecode($comment) == '请填写注意事项（选填）'){
    	    $comment = '';
    	}
    	$comment = urldecode($comment);
    	if(isset($_GPC['replace_order'])){
    		$comment = '代下单活动；下单用户手机号：'.$this->user_info['is_login']; 
    	}
    	
    	if($category_id == 17){ // 写字楼快洗
    	    $res = $this->open_server->create_office_order($user_id, $user_type, $category_id, $time_range, $washing_date, $washing_time, $office_building_id, $tel, $user_name, $room, $comment, $mark);
    	}else if($category_id == 61){ // 改衣服务
    	    $res = $this->open_server->create_tailor_order($user_id, $user_type, $category_id, $address_id, $washing_date, $washing_time, $time_range, $clothes_ids, $categories_ids,
    	        $comment, $city_id, $mark, $take_soon, $time_range);
    	}else if($is_from_eservice){
    		require_once IA_ROOT . '/framework/library/xiaoe/xiaoe.class.php';
			$xiaoe = new xiaoe($_W['config']);
			$address = $xiaoe->address_default(array('from_user'=>$this->user_info['from_user']));
			if(!empty($address['error_code']))
			{
				$result['msg'] = '系统异常';
	    		$result['state'] = '1';
	    		$result['url'] = HTTP_TYPE.'wx.rongchain.com/mobile.php?m=wap&act=homepage&do=index&mark=eservice';
	    		message($result,'',"ajax");
			}
    	 	$eservice_params = array(
				'city' => $address['city'],
				'area' => $address['area'],
				'address' => $address['details'],
				'tel' => $address['tel'],
				'customer_lng' => $address['lng'],
				'customer_lat' => $address['lat'],
				'user_type' => 18,
				'user_id' => $this->user_info['user_id'],
				'category_id' => $_GPC['category_id'],
				'washing_date' => $washing_date,
				"washing_time" => $washing_time,
				'totalnum' => 1,
				'good' => '18',
				'username' => $address['user_name'],
				'pay_type' => 3,
				'remark' => $comment,
				'need_detail' => 'true'
			);
    	 	$api_server = new ApiServer($_W['config']);

    	 	$res = $api_server->create_order($eservice_params);
    	 	if(empty($res['data'])){
				$res['ret'] = false;
			}else{
				switch ($category_id) {
					case '1':	$eservice_params['category_id'] = 15;	break;	//	洗衣洗家纺
					case '2':	$eservice_params['category_id'] = 13;	break;	//	洗鞋
					case '3':	$eservice_params['category_id'] = 16;	break;	//	窗帘
					case '4':	$eservice_params['category_id'] = 17;	break;	//	高端	
					case '13':	$eservice_params['category_id'] = 18;	break;	//	酒店快洗	
					case '5':	$eservice_params['category_id'] = 14;	break;	//	奢饰品	
					default:	$eservice_params['category_id'] = 15;	break;
				}
				$eservice_params['from_user'] = $this->user_info['from_user'];
				$eservice_params['address_id'] = $address['id'];
				$eservice_params['order_id'] = $res['data']['id'];
				$eservice_params['order_sn'] = $res['data']['ordersn'];
				$eservice_params['notice'] = $comment;
				$res['ret'] = true;
			}
    	}
    	else{
    		//此处增加client_id，用于解决第三方多appid绑定同一mobile的订单回传bug
    	    $res = $this->open_server->create_order($user_id, $user_type, $time_range, $washing_date, $washing_time, $category_id, $categories, $address_id, $comment, $client_id, $mark);

    	}
    	if(false === $res['ret']) {
    		$result['msg'] = $res['error'] ? $res['error'] : '预约失败，请稍后重试';
    		$result['state'] = $res['error_code'] ?: 0;
    		message($result,'',"ajax");
    	}else {
    		// 删除价目页缓存,因为有优惠券信息要更新
			mcache()->delete('price_list:' . $user_id  . ":" . $city_id);

			// 多单取最后一个id
			if (is_array($res['data']))
				$order_id = array_reverse($res['data'])[0];
			else
    			$order_id = $res['data'];
    		$url = create_url('order/order_list', array('order_id'=>$order_id, 'order_success'=>'success'));
			if($is_from_eservice) { // 小E管家同步订单
				$xiaoe->create_order($eservice_params);
				$url = $_W['config']['xiaoe']['url'].'/order';
			}else{
				// $order_id = $res['data'];
				$url = create_url('order/order_list',array('order_id' => $order_id,'order_success' => 'success'));
				$_SESSION['washing_date'] = $washing_date;
				$_SESSION['washing_time'] = $washing_time;
				$_SESSION['order_success'] = 'success';
				if(isset($_GPC['replace_order'])){
					$res = $this->replaceOrderNotice($address_id, $_GPC['replace_order'], $_GPC['user_name'], $_GPC['message'], $order_id);
				}
			}
    		$result['msg'] = '下单成功';
    		$result['state'] = '1';
    		$result['user_type'] = intval($user_type);
    		$result['order_id'] = $order_id;
    		$result['url'] = $url;
    		message($result,'',"ajax");
    	}
    }

    private function replaceOrderNotice($address_id, $url, $user_name = '', $message = '', $order_id)
    {
		require_once IA_ROOT . '/framework/library/edaixi/SwServer.class.php';
		$swapi = new SwServer($this->user_info['user_id']);
		return $swapi->replaceOrderNotice($address_id, $url, $user_name, $message , $this->user_info['is_login'], $order_id); 
    }
	
	// 格式化 首页 Banner/品类/菜单 数据 
    private function in_web_url($data, $type='', $query_data=array()){
    	global $_W;
    			$query_data = array_filter($query_data, function($v){
			if(! isset($v) || $v === ''){
				return false;
			}
			return true;
		});

		foreach ($data as $index => $value) {
			// 站内链接
			if($value['url_type'] == 'in_app'){
				// 处理充值
				if($value['url']['klass'] == 'balance'){
					if ($value['url']['type'] == 'recharge') {
						$data[$index]['url'] = create_url('icard/icard_charge', $query_data); 
					}
				}else if ($value['url']['klass'] == 'order') {
					// 处理下单
					if ($value['url']['type'] == 'create') {
					    $query_data['category_id'] = $value['url']['id'];
					    $query_data['sub_id'] = $value['url']['sub_id'];
						// 这里还是原来的逻辑
					    $order_url = create_url('homepage/order_place', $query_data);
					    if(isset($data[$index]['inner_url'])){
					        $price_url = add_params($data[$index]['inner_url'], array(
					            'sub_id' => $query_data['sub_id'],
					            'city_id' => $query_data['city_id'],
					            'mark' => $query_data['mark']
					        ));
					    }else{
					        $price_url = $order_url;
					    }
						if(empty($this->user_info['is_login'])){
							// 用户未登录
							if($query_data['hongbao_tel']){ // 扫码领红包记录登录回调URL
								$query_data['loginback_url'] = create_url('homepage/order_place', array(
									'city_id' => $query_data['city_id'],
									'category_id' => $value['url']['id'],
									'sub_id' => $value['url']['sub_id'],
								));
								$data[$index]['url'] = create_url('homepage/order_place', $query_data);;
							}else{
								$data[$index]['url'] = $price_url;
							}
						}else{
							// 用户已登录
							$data[$index]['url'] = $order_url;
						}
					}
				}elseif ($value['url']['klass'] == 'coupon') {
					if ($value['url']['type'] == 'list') {
						$data[$index]['url'] = create_url('payment/coupon_list'); 
					}
				}elseif ($value['url']['klass'] == 'points_mall') {
					if ($value['url']['type'] == 'points_mall') {
						$data[$index]['url'] = create_url('icard/redirect_points_mall'); 
					}
				}
			}
			
			// 站外链接
			else if($value['url_type'] == 'web')
			{
				// 追加参数
				if($data[$index]['put_under']){
					$query_data = array();
				}

				if (false !== stripos($data[$index]['url'], '#')){
					$url_sub = explode("#", $data[$index]['url']);
					$data[$index]['url'] = $str = add_params($url_sub[0], $query_data) . '#' . $url_sub[1];
				}else{
					$data[$index]['url'] = add_params($data[$index]['url'], $query_data);
				}

			}
			
			$data[$index]['url'] = $data[$index]['url'] ?: '';
		}
		return $data;
    }

	// 检查是否已获取登录积分（在登录状态判断）
	public function ajax_daily_points(){
		$result = array();
		$user_id = $this->user_info['user_id'];
		$is_login = $this->user_info['is_login'];
		if(!$user_id || !$is_login){
			$result['state'] = 0;
			message($result, '', 'ajax');
		}
		$points_key = 'homepage_points_' . $user_id . '_' . date('Ymd');
		if(mcache()->get($points_key)){ # 已显示首页积分
			$result['state'] = 2;
		}else{ # 还未显示首页积分
			$expire = strtotime(date('Y-m-d', strtotime('+1 day'))); # 失效时间：次日零时
			mcache()->set($points_key, 1, $expire - time());
			$result['state'] = 1;
		}
		message($result, '', 'ajax');
	}
	
	// 非微信公众号，首页过滤APP广告
	public function filter_app_url($url){
		$user_type = $this->user_info['user_type'];
		$is_from_weixin = (1 == $user_type);
		$app_download_url = 'a.app.qq.com/o/simple.jsp';
		$app_guide_url = 'mp.weixin.qq.com/s?__biz=MzA3NjA4OTkwNQ==&mid=247185928&idx=1&sn=c76d0edd9925899ca2f5926ed42a04f6';
		if(!$is_from_weixin && (stripos($url, $app_download_url) !== false || stripos($url, $app_guide_url) !== false)){
			return true;
		}
		return false;
	}
		
	// 格式化服务时间数组
	public function format_service_time($service_time, $category_id){
	    if(empty($service_time)){
	        return null;
	    }
	    $format_arr = array();
	    foreach ($service_time as $k => $v){
	        $format_arr['dk'][$v['date']]['date'] = $v['date'];
	        $format_arr['dk'][$v['date']]['weekday'] = $v['weekday'];
	        $format_arr['dk'][$v['date']]['date_str'] = $v['date_str'];
	        if($category_id == 13){
	        	$format_arr['dk'][$v['date']]['date_text'] = $v['weekday'];
	        }else{
	        	$format_arr['dk'][$v['date']]['date_text'] = $v['date_str'] . ' ' . $v['weekday'];
	        }
	        $format_arr['dk'][$v['date']]['selectable'] = 0;
	        foreach ($v['service_times'] as $k1 =>$v1){
	            $format_arr['tk'][$v['date']][$v1['text']]['time'] = $v1['text'];
	            $format_arr['tk'][$v['date']][$v1['text']]['time_str'] = $v1['view_text'];
	            $format_arr['tk'][$v['date']][$v1['text']]['time_text'] = $v1['view_text'];
	            //此处增加time_range  v5改版所需
	            $format_arr['tk'][$v['date']][$v1['text']]['time_range'] = json_encode($v1['time_range']);
	            if($category_id == 13){
	                $format_arr['tk'][$v['date']][$v1['text']]['back_text'] = $v1['kuai_description'];
	            }
	            $format_arr['tk'][$v['date']][$v1['text']]['quick_take'] = $v1['quick_take'] + 0;
	            $format_arr['tk'][$v['date']][$v1['text']]['quick_text'] = $v1['quick_text'] ?: '';
	            $format_arr['tk'][$v['date']][$v1['text']]['is_available'] = intval($v1['is_available']);
	            $format_arr['tk'][$v['date']][$v1['text']]['is_overtime'] = intval($v1['is_passed']);
	            $format_arr['tk'][$v['date']][$v1['text']]['selectable'] = intval($v1['is_available'] && !$v1['is_passed']);
	            if($format_arr['tk'][$v['date']][$v1['text']]['selectable']){
	                $format_arr['dk'][$v['date']]['selectable'] = 1;
	            }
	        }
	    }
	    return $format_arr;
	}
	
	// 好评列表
	public function favourable_comments(){
		global $_W,$_GPC;
		// 用户手动切换首页城市
		if($_GPC['city_id']){
			$city_id = $_GPC['city_id'];
		}else{
			// 获取用户首页城市
			$user_city = get_user_city();
			$city_id = $user_city['city_id'];
		}
		$random = false;
		$page = 1;
		$per_page = 12;
		$result  = $this->open_server->get_favourable_comments($random, $page, $per_page, $city_id);
		$total_pages = $result['total_pages'];
		$favourable_comments = $result['comments'];
		include $this->template('favourable_comments');
	}
	
	// 好评列表 -- 下一页
	public function ajax_next_favourable_comments(){
		global $_W,$_GPC;
		$random = false;
		$page = intval($_GPC['page']); // 页码
		$per_page = intval($_GPC['per_page']); // 偏移量
		$city_id = $_GPC['city_id'];
			
		$resp = $this->open_server->get_favourable_comments($random, $page, $per_page, $city_id);
		$comments = $resp['comments'];
		$total_pages = $resp['total_pages'];
		$rows_count = count($comments);
		
		$result = array();
		$result['page'] = $page;
		$result['total_pages'] = $total_pages;
		if($rows_count < 1){
			$result['rows_count'] = 0;
			$result['html'] = '';
			message($result, '', 'ajax');
		}
		$html = '';
		foreach ($comments as $key => $item){
			$html .= '<div class="comment-item user_appraise">
					      <div class="user_list">
						      <div class="appraise_list">
						         <div class="area_phone"><span class="nbsp">' . $item['user'] . '</span> <span>' . $item['tel'] . '</span></div>
						         <div class="borderD"></div>
						         <div class="appraise_detail">
						           <span><img class="front_quote" src="' . assets_link('/framework/style/images/front_quote.png') . '" alt=""></span>
						           <span>' . $item['comment'] . '</span>
						           <span class="back_quote"><img src="' . assets_link('/framework/style/images/back_quote.png') .'" alt=""></span>
						         </div>
						         <div class="borderD"></div>
						         <div class="service_date"><span class="nbsp">' . $item['category'] . '</span> <span>' . $item['date'] . '</span></div>
						      </div>
					     </div>
					  </div>';
		}
		$result['rows_count'] = $rows_count;
		$result['html'] = compress_html($html);
		message($result, '', 'ajax');
	}
	
	// 微信价目介绍图文消息
	public function view_price(){
		$price_url = 'http://www.edaixi.com/washing_prices/price_word';
		if($price_url){
			$city_id = get_user_city()['city_id'];
			$price_url = add_params($price_url, array('city_id' => $city_id));
			header('Location: ' . $price_url);
			exit;
		}
		header('Location: ' . create_url('homepage/index'));
		exit;
	}
	
	// 小e管家用户地址
	public function get_eservice_address($from_user, $category_id, $sub_id){
		global $_W, $_GPC;
		$addr_info = array();
		require_once IA_ROOT . '/framework/library/xiaoe/xiaoe.class.php';
		$xiaoe = new xiaoe($_W['config']);
		$frequently_address = $xiaoe->address_default(array('from_user' => $from_user));
		if(empty($frequently_address['error_code'])) {
			$addr_info = $frequently_address;
			$addr_info['username'] = $frequently_address['user_name'];
			$addr_info['address'] = $frequently_address['details'];
			$addr_info['address_id'] = $frequently_address['id'];
		}
		$call_back_url = HTTP_TYPE.$_SERVER['HTTP_HOST'].'/mobile.php?m=wap&act=homepage&do=order_place&sub_id='.$sub_id.'&mark=eservice&category_id='.$category_id;
		$call_back_url .= empty($_GPC['price_read']) ? '' : '&price_read='.$_GPC['price_read'];
		$addr_info['select_addr_url'] = $_W['config']['xiaoe']['url'].'/address/sel?from=partner&appid=xiyi&redirect_url='.urlencode($call_back_url);
		return $addr_info;
	}
	//	爆品
	public function hot_sale(){
		global $_W,$_GPC;
		$share_url = HTTP_TYPE.$_SERVER['HTTP_HOST'].'/'.create_url('homepage/hot_sale');
		$is_from_weixin = is_from_weixin();
		$city_id = isset($_GPC['city_id']) ? $_GPC['city_id'] : '';
		if($is_from_weixin){
			require_once IA_ROOT.'/framework/library/wxshare/include.php';
			if(empty($city_id)){
				$city = get_user_city(); 
				$city_id = $city['city_id'];	
			}
		}
		switch ($city_id) {
			case '16':
				$city['city_name'] = '无锡';
				break;
			case '3':
				$city['city_name'] = '深圳';
				break;
			case '10':
				$city['city_name'] = '苏州';
				break;
			default:
				$city['city_name'] = '深圳,无锡,苏州';
				break;
		}
	
		$order_place = create_url(
			'homepage/order_place',
			array(
				'category_id' => 2,
				'price_read' => 1,
				));
		include $this->template('hot_sale');
	}

	protected function getShareUrl()
	{
		global $_GPC;
		return  HTTP_TYPE.$_SERVER['HTTP_HOST'].'/mobile.php?m=wap&act=homepage&do=order_place&category_id=1&price_read=1&replace_order='.$_GPC['replace_order'].'&daf='.$this->getShareActiveId().'&duf='.$this->encryptionUserId().'&depth='.($this->getDepth()+1);
	}

	//首页嵌入蚁匠联合登陆(于2017-07-27下架，屏蔽入口)
	/*
	public function yj_bridge(){
		global $_W;
 		$form_user = $this->user_info['from_user'] ?: 'edaixi';
 		$data = array();
 		$data['fromuser'] = md5($from_user);
		$data['mobile'] = $this->user_info['is_login'];
		$data['sign'] = $this->generate_signature($data);

		$yj_url = $_W['config']['yijiang_link'] . '?' .  http_build_query($data);
		header('Location:' . $yj_url);
		exit;
	}

	//蚁匠签名
	private function generate_signature($param){
		sort($param);
		reset($param);
		$salt = 'Lg65Xde2F';
		$str = urldecode(http_build_query($param));
		return md5($str . $salt);
	}*/
}