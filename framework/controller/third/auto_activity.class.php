<?php
/**
 *  微信活动（首页前后端分离版）
 *  此处活动为后台可配置
 */
class Auto_activity extends BaseModule {
	//活动ID
	private $business_id;
	//活动名称
	private $activity_title;
	//SESSION 键名
	private $session_key;
	//模板名称
	protected $model_name = 'auto_activity';
	//商品排列(4=2x2, 6=3x2, 9=3x3) ps:列数x行数
	private $goods_layout;
	//商品列表
	private $recharge_items;
	//微信分享
	private $weixin_share;
	//活动规则
	private $rule;
	//活动协议
	private $protocol;
	//默认选中项
	private $default_num;
	//礼品截止兑换时间
	private $exchange_end_time;
	//渠道埋点，用mark区分
	private $mark_talkingdata = array(
			//'1479100639ZfamCYs8' => '亿街区'
		);

	function __construct() {
		global $_W,$_GPC;
		$this->business_id = isset($_GPC['act_id']) ? intval($_GPC['act_id']) : error_report('活动ID非法');
		$this->session_key = 'recharge' . $this->business_id . '_fee';
		
		parent::__construct();
		$this->open_server = new OpenServer($_W['config'], $this->user_info);
		require_once IA_ROOT . '/framework/library/edaixi/SwServer.class.php';
		$this->sw_server = new SwServer($this->user_info['user_id']);

		//判断是否需要从swoole获取活动信息
		if($this->is_need_activity_detail()){
			$ret = $this->sw_server->get_active_detail_by_id($this->business_id);
			if(empty($ret) || empty($ret['goods'])){
				error_report('获取活动配置信息失败！');
			}
			//首先判断，活动时间是否合法
			if(time() < $ret['start_time_int']){
				error_report('该活动ID不在有效范围内');
			}
			//活动过期，引导活动过期页
			if(time() > $ret['end_time_int']){
				$new_ret = $this->sw_server->get_online_events();
				if(empty($new_ret)){
					error_report('活动已过期');
				}
				$msg = "尊敬的用户，“" . $ret['active_name'] . "”活动已结束，如您有未兑换的礼品，请点击“兑奖处”进入“".$new_ret[0]['active_name']."”活动页面兑换，请务必于".date('m月d日', $ret['exchange_end_time_int'])."之前完成兑换。";
				$new_page = create_url('auto_activity/index', array('act_id'=>$new_ret[0]['id']), 'third');
				include $this->template('deadline_page');
				exit;
			}
			//整理数据
			$this->weixin_share = array(
				'share_url' => rtrim($_W['config']['site']['root'], '/').create_url('auto_activity/index', array('act_id'=>$this->business_id), 'third'),
				'title' => $ret['share_title'],
				'desc'=> $ret['share_describe'],
				'img_url' => $ret['share_ico_url'],
				'callback_url' => create_url('auto_activity/share_success', array('act_id'=>$this->business_id), 'third')
				);
			foreach ($ret['goods'] as $item) {
				$this->recharge_items[$item['recharge_money']] = array(
						'money' => $item['recharge_money'],
						'img' => $item['goods_img_url'],
						'points' => $item['points'],
						'title' => $item['goods_explain'],
						'desc' => $item['goods_name'],
						'url' => $item['goods_url'],
						'remark' => $item['show_hot']
					);
			}
			$this->default_num = $ret['default_num'] - 1;
			$this->goods_layout = $ret['goods_total'];
			$this->rule = $ret['active_rule'];
			$this->protocol = $ret['active_agreement'];
			$this->activity_title = $ret['active_name'];
			$this->exchange_end_time = date('Y年m月d日', $ret['exchange_end_time_int']);
		}
	}

	//活动引导页
	public function index(){
		global $_W,$_GPC;
		$user_type = $this->user_info['user_type'];
		$is_from_weixin = (1 == $user_type);
		// 分享活动页到微信朋友圈
		if($is_from_weixin){
			require IA_ROOT . '/framework/library/wxshare/include.php';
			@extract($this->weixin_share);
		}
		include IA_ROOT . "/new_weixin/view/event_auto.html";
	}
	
	// 活动页面接口(在线充值页)
	public function recharge_page(){
		global $_W,$_GPC;
		
		// 当用户处于登录状态时，获取用户可兑换的商品
		$exchange_items = array();
		$exchange_history = array();
		if(!empty($this->user_info['user_id'])){
			$exchange_items = $this->sw_server->active_duihuan_url('','', $this->business_id);
			//组织数据
			$exchange_arr = array();
			foreach ($exchange_items as $key => $value) {
				$exchange_arr[$key]['title'] = $value['goods_float'];
				$exchange_arr[$key]['url'] = $value['url'];
			}
			$exchange_history = $this->sw_server->active_duihuan_url('', 1, $this->business_id);
		}
		// 获取免登陆兑吧url
		$nologin_url = $this->recharge_items;
		foreach ($this->recharge_items as $fee => $val) {
			$nologin_url[$fee]['url'] = $this->build_duiba_nologin_url($val['url']);
		}
		// 根据mark值判断来源
		$mark = get_mark();
		$mark_source = '其他';
		if($mark && isset($this->mark_talkingdata[$mark])){
			$mark_source = $this->mark_talkingdata[$mark]; 
		}

		$homepage_backurl = rtrim($_W['config']['site']['root'], '/').create_url('auto_activity/index', array('act_id'=>$this->business_id), 'third');
		$cardno_backurl = rtrim($_W['config']['site']['root'], '/').create_url('auto_activity/recharge_card_page', array('act_id'=>$this->business_id), 'third');
		$data = array(
			'exchange_items' => $exchange_arr,
			'exchange_history' => $exchange_history,
			'recharge_items' => array_values($nologin_url),
			'mark_source' => $mark_source,
			'cardno_page' => create_url('auto_activity/recharge_card_page', array('act_id'=>$this->business_id, 'loginback_url'=>$cardno_backurl), 'third'),
			'payment_page' => create_url('auto_activity/payment_page', array('act_id'=>$this->business_id, 'loginback_url'=>$homepage_backurl), 'third')
			);
		echo_json(true, $data);
	}

	//充值卡充值页面
	public function recharge_card_page(){
		$user_id = $this->user_info['user_id'];
		$result = array();
        $data = array('user_id' => $user_id);
        $open_ret = $this->open_server->user_wallet($data);
        if(!empty($open_ret)) {
            foreach ($open_ret as $key => $value) {
                if (is_numeric($value)) {
                    $result[$key] = $value;
                }
            }
        }
        $user_icard_amount = isset($result['icard_amount']) ? $result['icard_amount'] : '暂无信息';
        // 充值卡充值提交url
		$icard_charge_url = create_url('auto_activity/ajax_cardno_recharge', array('act_id'=>$this->business_id), 'third');
		// 充值卡充值页面url
		$cardno_page_url = create_url('auto_activity/recharge_card_page', array('act_id'=>$this->business_id), 'third');
		//在线充值页面url
		$online_charge_url = create_url('auto_activity/recharge_page', array('act_id'=>$this->business_id), 'third');

		include $this->template('recharge_card_page');
	}

	//充值卡充值提交
	public function ajax_cardno_recharge(){
		global $_GPC;
		$user_id = $this->user_info['user_id'];
		$sncode = str_replace(' ', '', $_GPC['sncode']);
		$result = array();
		if (empty($user_id) || empty($sncode)) {
            $result['state'] = 0;
            $result['msg'] = '请输入卡密';
        }else{
        	$open_ret = $this->open_server->bind_recharge($user_id, $sncode, '', $this->business_id);
            if(isset($open_ret['ret']) && $open_ret['ret']){
                $result['state'] = 1;
                $data = $open_ret['data'];
                $result['msg'] = $data['content'];
                //此处存session
                $_SESSION[$this->session_key] = intval($data['fee']);
                $result['success_url'] = create_url('auto_activity/recharge_success', array('act_id'=>$this->business_id), 'third');
            }else {
                $result['state'] = 0;
                $result['msg'] = empty($open_ret['error']) ? '出错了，请稍后再试！' : $open_ret['error'];
            }
        }
        message($result, '', 'ajax');
	}
	
	// 在线充值支付页面
	public function payment_page() {
		global $_GPC;
		$user_type = $this->user_info['user_type'];
		$fee = $_GPC['fee'] ? intval($_GPC['fee']) : 0;
		if($fee <= 0 ){
			$fee = isset($_SESSION[$this->session_key]) ? $_SESSION[$this->session_key] : error_report('充值金额有误');
		}else{
			$_SESSION[$this->session_key] = $fee;
		}
		$recharge_item = $this->get_recharge_title($fee, 1);

		// 建立交易单号 url
		$build_tradeno_url = create_url('auto_activity/build_tradeno', array('act_id'=>$this->business_id), 'third');
		include $this->template('payment_page');
	}
	
	// 建立交易单号
	public function build_tradeno() {
		global $_W, $_GPC;
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$from_user = $this->user_info['from_user'];
		$paytype = $_GPC['paytype'];
		$fee = number_format($_GPC['fee'], 2, '.', '');
		// 获取用户首页城市
		$user_city = get_user_city();
		$city_id = $user_city['city_id'];
		// URL mark
		$mark = get_mark();
		$res = $this->open_server->icard_recharge($user_id, $paytype, $fee, '', $city_id, $mark, $this->business_id);
		$ret_data = array('status' => (isset($res['ret']) ? $res['ret'] : false),'msg' => ''); 
		if(!$res['ret']){
			if( is_ajax() ) {
			   $ret_data['msg'] = $res['error'];
			   $this->retJson($ret_data); 
			}
		    error_report($res['error']);
		}
		// 记录充值交易参数
		$data = $res['data'];
		$params['tid'] = $data['trade_no'];
		$params['user_id'] = $user_id;
		$params['from_user'] = $from_user;
		$params['fee'] = $fee;
		$params['user_type'] = $user_type;
		$third_pay = $this->call_pay($params, $paytype);

		if( is_ajax() ) {
			$ret_data['pay_url']    = $third_pay['url'];
			$ret_data['success_url'] = $third_pay['success_url'];
			$this->retJson($ret_data);
		}
		//页面跳转
		header("Cache-control: private");
		if (!empty($third_pay['url'])) {
			$this->jumpUrl($third_pay['url']);
		} else if (!empty($third_pay['html'])) {
			echo $third_pay['html'];
		} else {
			error_report('支付出错啦', create_url('auto_activity/recharge_page', array('act_id'=>$this->business_id), 'third'));
		}	
	}
	
	// 调起第三方支付
	private function call_pay($params, $pay_type) {
		global $_W, $_GPC;
        $ret = $result = array();
        $return_url = urlencode(rtrim($_W['config']['site']['root'], '/').create_url('auto_activity/recharge_success', array('act_id'=>$this->business_id), 'third'));
        if (!empty($params['tid']) && !empty($params['fee']) && !empty($pay_type)) {
            $ret = $this->open_server->payment_sign($params['user_id'], $params['user_type'], $pay_type, $params['tid'], $params['fee'], 1, $return_url, $params['from_user']);
        }
        $result = $ret['data'];
        if(empty($result)){
        	error_report('支付出错啦!', create_url('auto_activity/recharge_page', array('act_id'=>$this->business_id), 'third'));
        }
        $result['success_url'] = $return_url;
        return $result;
	}

	//充值成功的页面
	public function recharge_success(){
		$user_type = $this->user_info['user_type'];
		//app的fee用get传来的
		if(in_array($user_type, array('2','3'))){
			$fee = isset($_GET['fee']) ? intval($_GET['fee']) : 0;
			$request_url = create_url('auto_activity/get_exchange_url', array('fee'=>$fee, 'from'=>'app', 'act_id'=>$this->business_id), 'third');
		}else{
			$fee = isset($_SESSION[$this->session_key]) ? $_SESSION[$this->session_key] : 0;
			$request_url = create_url('auto_activity/get_exchange_url', array('fee' => $fee, 'act_id'=>$this->business_id), 'third');
		}
		$recharge_item = $this->get_recharge_title($fee);
		
		include $this->template('recharge_success');
	}

	//ajax动态获取【去兑换】按钮链接(此处需要定时轮询)
	public function get_exchange_url(){
		$user_type = $this->user_info['user_type'];
		//app的fee用get传来的
		if(in_array($user_type, array('2','3'))){
			$fee = isset($_GET['fee']) ? intval($_GET['fee']) : 0;
		}else{
			$fee = isset($_SESSION[$this->session_key]) ? $_SESSION[$this->session_key] : 0;
		}
		$exchange_items = $result = array();
		$exchange_items = $this->sw_server->active_duihuan_url($fee, '', $this->business_id);
		if(empty($exchange_items)){
			$result['state'] = 0;
		}else{
			$result['state'] = 1;
			$result['url'] = $exchange_items['url'];
		}
		message($result, '', 'ajax');
	}
	
	//显示充值页“充值XX送XX积分可换取XXX”
	private function get_recharge_title($fee, $flag=''){
		$fee = intval($fee);
		$desp = '';
		if(array_key_exists($fee, $this->recharge_items)){
			$desp = '可兑换' . $this->recharge_items[$fee]['title'];
			if(empty($flag)){
				$desp = '充值' . $fee . '送' . $this->recharge_items[$fee]['points'] . '积分'. $desp;
			}
		}
		return $desp;
	}
	
	// 活动分享成功回调
	public function share_success(){
		global $_GPC;
		$city_id = $_GPC['city_id'];
		$cid = $_GPC['cid'];
		$city = check_city_id($city_id) ?: '***';
		
		$redis = redis();
		$redis->incr($this->session_key);
		$redis->hMset($this->session_key . ':' . time(), array(
			'ip' => getip(),
			'city' => $city,
		    'date' => date('Y-m-d H:i:s'),
		    'cid' => $cid
		));
	}

	//生成免登陆url
	private function build_duiba_nologin_url($redirect){
		$uid = 'not_login';
    	$credits = 0;
    	$appKey = '3JdahN78n62xuEemEBmDSA1fKEKr' ;
    	$appSecret = '3tN8wHhgYABn9cSrfTSYSUadzhmw';
    	$url = "http://www.duiba.com.cn/autoLogin/autologin?";
    	$timestamp = time()*1000 . "";
    	$array=array("uid"=>$uid,"credits"=>$credits,"appSecret"=>$appSecret,"appKey"=>$appKey,"timestamp"=>$timestamp,"redirect"=>$redirect);
    	$sign= $this->duiba_sign($array);
    	$url = $url ."timestamp=" . $timestamp ."&uid=" . $uid . "&credits=" . $credits . "&appKey=" . $appKey . "&redirect=" . urlencode($redirect) . "&sign=" . urlencode($sign) ;
    	return $url;
	}

	//兑吧sign
	private function duiba_sign($array){
		ksort($array);
    	$string="";
    	while (list($key, $val) = each($array)){
        	$string = $string . $val ;
    	}
    	return md5($string);
	}

	//是否必须从swoole获取活动信息
	private function is_need_activity_detail(){
		global $_GPC;
		$do = isset($_GPC['do']) ? $_GPC['do'] : 'index';
		//must
		$must_arr = array(
			'index',
			'recharge_page',
			'payment_page',
			'recharge_success'
			);
		if(in_array($do, $must_arr)){
			return true;
		}
		return false;
	}
}