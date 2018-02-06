<?php
/**
 *  微信活动（首页前后端分离版）
 *  2016双11始，ios和android，部分使用native
 *  活动日期：2016年10月27日00：00：00～...
 *	活动中business_id代表本次活动的唯一标识，(100=>'917活动', 110=>'双11活动', 120=>'第三期活动', 130=>'第四期活动')
 */
class Activity extends BaseModule {

	//渠道埋点，用mark区分
	private $mark_talkingdata = array(
			//'1479100639ZfamCYs8' => '亿街区'
		);

	function __construct() {
		global $_W,$_GPC;
		$this->model_name = 'activity';
		//本次活动ID
		$this->business_id = 130;
		//配置表直接写死
		$this->recharge_items = array(
			'19999' => array(
					'money' => '19999',
					'img' => assets_link('/framework/style/images/activity44_19999.jpg'),
					'points' => '3375000',
					'title' => '天天果园价值6888元充值卡礼包一份',
					'desc' => '6888元充值礼包',
					'url' => 'https://www.duiba.com.cn/mobile/appItemDetail?appItemId=693204',
					'remark' => false
					),
			'9999' => array(
					'money' => '9999',
					'img' => assets_link('/framework/style/images/activity44_9999.jpg'),
					'points' => '2965000',
					'title' => '天天果园价值2888元充值卡礼包一份',
					'desc' => '2888元充值礼包',
					'url' => 'https://www.duiba.com.cn/mobile/appItemDetail?appItemId=693197',
					'remark' => false
					),
			'6666' => array(
					'money' => '6666',
					'img' => assets_link('/framework/style/images/activity44_6666.jpg'),
					'points' => '2515000',
					'title' => '天天果园价值1888元充值卡礼包一份',
					'desc' => '1888元充值礼包',
					'url' => 'https://www.duiba.com.cn/mobile/appItemDetail?appItemId=693186',
					'remark' => false
					),
			'2999' => array(
					'money' => '2999',
					'img' => assets_link('/framework/style/images/activity44_2999.jpg'),
					'points' => '2115000',
					'title' => '天天果园价值888元充值卡一张',
					'desc' => '888元充值卡',
					'url' => 'https://www.duiba.com.cn/mobile/appItemDetail?appItemId=693181',
					'remark' => false
					),
			'1999' => array(
					'money' => '1999',
					'img' => assets_link('/framework/style/images/activity44_1999.jpg'),
					'points' => '1695000',
					'title' => '天天果园价值566元充值卡一张',
					'desc' => '566元充值卡',
					'url' => 'https://www.duiba.com.cn/mobile/appItemDetail?appItemId=693177',
					'remark' => false
					),
			'999' => array(
					'money' => '999',
					'img' => assets_link('/framework/style/images/activity44_999.jpg'),
					'points' => '1275000',
					'title' => '天天果园价值238元充值卡一张',
					'desc' => '238元充值卡',
					'url' => 'https://www.duiba.com.cn/mobile/appItemDetail?appItemId=693175',
					'remark' => false
					),
			'699' => array(
					'money' => '699',
					'img' => assets_link('/framework/style/images/activity44_699.jpg'),
					'points' => '855000',
					'title' => '天天果园价值168元充值卡一张',
					'desc' => '168元充值卡',
					'url' => 'https://www.duiba.com.cn/mobile/appItemDetail?appItemId=693173',
					'remark' => false
					),
			'399' => array(
					'money' => '399',
					'img' => assets_link('/framework/style/images/activity44_399.jpg'),
					'points' => '435000',
					'title' => '天天果园价值88元充值卡一张',
					'desc' => '88元充值卡',
					'url' => 'https://www.duiba.com.cn/mobile/appItemDetail?appItemId=693137',
					'remark' => false
					)
		);
		parent::__construct();
		$this->open_server = new OpenServer($_W['config'], $this->user_info);
	}

	//老活动过期入口
	public function homepage(){
		$new_page = create_url('activity/index', array(), 'third');
		include $this->template('deadline_page');
		exit;
	}

	//活动引导页
	public function index(){
		global $_W,$_GPC;
		$user_type = $this->user_info['user_type'];
		$is_from_weixin = (1 == $user_type);
		// 分享活动页到微信朋友圈
		if($is_from_weixin){
			require IA_ROOT . '/framework/library/wxshare/include.php';
			$share_url = 'https://wx.rongchain.com/mobile.php?m=third&act=activity&do=index';
			$title = '新年开心“洗”，越洗越有礼';
			$desc = '充值e袋洗，新年为您送好礼！';
			$img_url = assets_link('/resource/image/activity_share44.jpg');
			$callback_url = create_url('activity/share_success', array(), 'third');
		}
		include IA_ROOT . "/new_weixin/view/event_11.html";
	}
	
	// 活动页面接口(在线充值页)
	public function recharge_page(){
		global $_W,$_GPC;
		
		// 当用户处于登录状态时，获取用户可兑换的商品
		$exchange_items = array();
		$exchange_history = array();
		if(!empty($this->user_info['user_id'])){
			require_once IA_ROOT . '/framework/library/edaixi/SwServer.class.php';
			$sw_server = new SwServer($this->user_info['user_id']);
			$exchange_items = $sw_server->get_exchange917_items('','', $this->business_id);
			$exchange_history = $sw_server->get_exchange917_items('', 1, $this->business_id);
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
			
		$data = array(
			'exchange_items' => $exchange_items,
			'exchange_history' => $exchange_history,
			'recharge_items' => array_values($nologin_url),
			'mark_source' => $mark_source,
			'cardno_page' => create_url('activity/recharge_card_page', array(), 'third'),
			'payment_page' => create_url('activity/payment_page', array(), 'third')
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
		$icard_charge_url = create_url('activity/ajax_cardno_recharge', array(), 'third');
		// 充值卡充值页面url
		$cardno_page_url = create_url('activity/recharge_card_page', array(), 'third');
		//在线充值页面url
		$online_charge_url = create_url('activity/recharge_page', array(), 'third');

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
                $_SESSION['recharge44_fee'] = intval($data['fee']);
                $result['success_url'] = create_url('activity/recharge_success', array(), 'third');
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
			$fee = isset($_SESSION['recharge44_fee']) ? $_SESSION['recharge44_fee'] : error_report('充值金额有误');
		}else{
			$_SESSION['recharge44_fee'] = $fee;
		}
		$recharge_item = $this->get_recharge_title($fee, 1);

		// 建立交易单号 url
		$build_tradeno_url = create_url('activity/build_tradeno', array(), 'third');
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
		$params['tel'] = $this->user_info['is_login'];
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
			error_report('支付出错啦', create_url('activity/recharge_page', array(), 'third'));
		}	
	}
	
	// 调起第三方支付
	private function call_pay($params, $pay_type) {
		global $_W, $_GPC;
        $ret = $result = array();
        $return_url = urlencode(rtrim($_W['config']['site']['root'], '/').create_url('activity/recharge_success', array(), 'third'));
        if (!empty($params['tid']) && !empty($params['fee']) && !empty($pay_type)) {
            $ret = $this->open_server->payment_sign($params['user_id'], $params['user_type'], $pay_type, $params['tid'], $params['fee'], 1, $return_url, $params['from_user'], $params['tel']);
        }
        $result = $ret['data'];
        if(empty($result)){
        	error_report('支付出错啦!', create_url('activity/recharge_page', array(), 'third'));
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
			$request_url = create_url('activity/get_exchange_url', array('fee'=>$fee, 'from'=>'app'), 'third');
		}else{
			$fee = isset($_SESSION['recharge44_fee']) ? $_SESSION['recharge44_fee'] : 0;
			$request_url = create_url('activity/get_exchange_url', array('fee' => $fee), 'third');
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
			$fee = isset($_SESSION['recharge44_fee']) ? $_SESSION['recharge44_fee'] : 0;
		}
		$exchange_items = $result = array();
		require_once IA_ROOT . '/framework/library/edaixi/SwServer.class.php';
		$sw_server = new SwServer($this->user_info['user_id']);
		$exchange_items = $sw_server->get_exchange917_items($fee, '', $this->business_id);
		if(empty($exchange_items)){
			$result['state'] = 0;
		}else{
			$result['state'] = 1;
			$result['url'] = $exchange_items['url'];
		}
		message($result, '', 'ajax');
	}
	
	//显示充值页“充值XX送XX积分可换取XXX”
	public function get_recharge_title($fee, $flag=''){
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
		$redis->incr('share33');
		$redis->hMset('share33:' . time(), array(
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
}