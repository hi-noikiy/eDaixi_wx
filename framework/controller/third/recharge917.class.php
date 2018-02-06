<?php
/**
 *  917充值余额送暴风TV
 *  活动日期：2016年09月17日00：00：00～2016年10月29日00：00：00
 */
class Recharge917 extends BaseModule {

	//渠道埋点，用mark区分
	private $mark_talkingdata = array(
		'1474941685jVLo1fEB' => '暴风',
		'1475135794PYSKUyZt' => '中信',
		'1475980669FTOBedcA' => '直营店',
		'1476441619WXIKg3Su' => '1015短推'
		);

	function __construct() {
		global $_W,$_GPC;
		$this->model_name = 'recharge917';
			//配置表直接写死
		$this->recharge_items = array(
			'7500' => array(
					'money' => '7500',
					'img' => assets_link('/framework/style/images/charge7500.png'),
					'points' => '3360000',
					'size' => '55吋',
					'title' => '暴风超体电视',
					'piece' => '一台',
					'url' => 'http://www.duiba.com.cn/mobile/appItemDetail?appItemId=489172'
					),
			'5000' => array(
					'money' => '5000',
					'img' => assets_link('/framework/style/images/charge5000.png'),
					'points' => '2950000',
					'size' => '45吋',
					'title' => '暴风超体电视',
					'piece' => '一台',
					'url' => 'http://www.duiba.com.cn/mobile/appItemDetail?appItemId=489133'
					),
			'3500' => array(
					'money' => '3500',
					'img' => assets_link('/framework/style/images/charge3500.png'),
					'points' => '2500000',
					'size' => '40吋',
					'title' => '暴风超体电视',
					'piece' => '一台',
					'url' => 'http://www.duiba.com.cn/mobile/appItemDetail?appItemId=489127'
					),
			'2000' => array(
					'money' => '2000',
					'img' => assets_link('/framework/style/images/charge2000.jpg'),
					'points' => '2100000',
					'size' => '',
					'title' => '手机或净化器',
					'piece' => '一台',
					'url' => 'http://www.duiba.com.cn/mobile/appItemDetail?appItemId=493378'
					),
			'1000' => array(
					'money' => '1000',
					'img' => assets_link('/framework/style/images/charge1000.png'),
					'points' => '1680000',
					'size' => '',
					'title' => '小米旅行箱',
					'piece' => '一个',
					'url' => 'http://www.duiba.com.cn/mobile/appItemDetail?appItemId=493368'
					),
			'500' => array(
					'money' => '500',
					'img' => assets_link('/framework/style/images/charge500.png'),
					'points' => '1260000',
					'size' => '',
					'title' => '小米蓝牙音响',
					'piece' => '一部',
					'url' => 'http://www.duiba.com.cn/mobile/appItemDetail?appItemId=493273'
					),
			'200' => array(
					'money' => '200',
					'img' => assets_link('/framework/style/images/charge200.png'),
					'points' => '840000',
					'size' => '',
					'title' => 'e袋洗洗衣液',
					'piece' => '一瓶',
					'url' => 'http://www.duiba.com.cn/mobile/appItemDetail?appItemId=493373'
					),
			'100' => array(
					'money' => '100',
					'img' => assets_link('/framework/style/images/charge100.png'),
					'points' => '420000',
					'size' => '',
					'title' => '余额10元',
					'piece' => '',
					'url' => ''
					)	
		);
		parent::__construct();
		//deadline
		$deadline = strtotime('2016-10-29');
		if(time() > $deadline){
			$new_page = create_url('activity/index', array(), 'third');
			include $this->template('deadline_page');
			exit;       
		}
		$this->open_server = new OpenServer($_W['config'], $this->user_info);
	}
	
	// 活动页面(在线充值页)
	public function recharge_page(){
		global $_W,$_GPC;
		$user_type = $this->user_info['user_type'];
		$is_from_weixin = (1 == $user_type);
		// 分享活动页到微信朋友圈
		if($is_from_weixin){
			require IA_ROOT . '/framework/library/wxshare/include.php';
			$share_url = HTTP_TYPE .'wx.rongchain.com/mobile.php?m=third&act=recharge917&do=recharge_page';
			$title = '充值送电视手机，金秋大洗礼';
			$desc = '多档充值好礼免费拿，电视、手机送不停，充值e袋洗，让生活多份自在';
			$img_url = assets_link('/resource/image/baofengtv917.jpg');
			$callback_url = create_url('recharge917/share_success', array(), 'third');
		}
		// 当用户处于登录状态时，获取用户可兑换的商品
		$exchange_items = array();
		if(!empty($this->user_info['user_id'])){
			require_once IA_ROOT . '/framework/library/edaixi/SwServer.class.php';
			$sw_server = new SwServer($this->user_info['user_id']);
			$exchange_items = $sw_server->get_exchange917_items();
			$exchange_history = $sw_server->get_exchange917_items('', 1);
		}
		// 获取免登陆兑吧url
		$nologin_url = $this->recharge_items;
		foreach ($this->recharge_items as $fee => $val) {
			$nologin_url[$fee]['url'] = ($fee == 100) ? '' : $this->build_duiba_nologin_url($val['url']);
		}
		// 根据mark值判断来源
		$mark = get_mark();
		$mark_source = '其他';
		if($mark && isset($this->mark_talkingdata[$mark])){
			$mark_source = $this->mark_talkingdata[$mark]; 
		}
			
		// 支付页面 url
		$payment_page_url = create_url('recharge917/payment_page', array(), 'third');
		// 兑换活动充值卡 url
		$cardno_page_url = create_url('recharge917/recharge_card_page', array(), 'third');

		include $this->template('recharge_page');			
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
		$icard_charge_url = create_url('recharge917/ajax_cardno_recharge', array(), 'third');
		// 充值卡充值页面url
		$cardno_page_url = create_url('recharge917/recharge_card_page', array(), 'third');
		//在线充值页面url
		$online_charge_url = create_url('recharge917/recharge_page', array(), 'third');

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
        	//本次活动的特殊id
        	$business_id = 100;
        	$open_ret = $this->open_server->bind_recharge($user_id, $sncode, '', $business_id);
            if(isset($open_ret['ret']) && $open_ret['ret']){
                $result['state'] = 1;
                $data = $open_ret['data'];
                $result['msg'] = $data['content'];
                //此处存session
                $_SESSION['recharge917_fee'] = intval($data['fee']);
                $result['success_url'] = create_url('recharge917/recharge_success', array(), 'third');
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
			$fee = isset($_SESSION['recharge917_fee']) ? $_SESSION['recharge917_fee'] : error_report('充值金额有误');
		}else{
			$_SESSION['recharge917_fee'] = $fee;
		}
		$recharge_item = $this->get_recharge_title($fee, 1);
		/*
		$is_wx_browser = 0;
		if(false !== strpos( ',' . $_W['account']['payment']['wechat_h5']['user_type'] . ',', ',' . $this->user_info['user_type'] . ',') ) {
			$is_wx_browser = 1;	
		}*/
		// 建立交易单号 url
		$build_tradeno_url = create_url('recharge917/build_tradeno', array(), 'third');
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
		//icard_recharge增加一个默认参数business_id=100，作为本次活动的标识
		$business_id = 100;
		$res = $this->open_server->icard_recharge($user_id, $paytype, $fee, '', $city_id, $mark, $business_id);
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
			error_report('支付出错啦', create_url('recharge917/recharge_page', array(), 'third'));
		}	
	}
	
	// 调起第三方支付
	private function call_pay($params, $pay_type) {
		global $_W, $_GPC;
        $ret = $result = array();
        $return_url = urlencode(rtrim($_W['config']['site']['root'], '/').create_url('recharge917/recharge_success', array(), 'third'));
        if (!empty($params['tid']) && !empty($params['fee']) && !empty($pay_type)) {
            $ret = $this->open_server->payment_sign($params['user_id'], $params['user_type'], $pay_type, $params['tid'], $params['fee'], 1, $return_url, $params['from_user']);
        }
        $result = $ret['data'];
        if(empty($result)){
        	error_report('支付出错啦!', create_url('recharge917/recharge_page', array(), 'third'));
        }
        $result['success_url'] = $return_url;
        return $result;
	}

	//充值成功的页面
	public function recharge_success(){
		$fee = isset($_SESSION['recharge917_fee']) ? $_SESSION['recharge917_fee'] : 0;
		$recharge_item = $this->get_recharge_title($fee);
		$request_url = create_url('recharge917/get_exchange_url', array(), 'third');
		include $this->template('recharge_success');
	}

	//ajax动态获取【去兑换】按钮链接(此处需要定时轮询)
	public function get_exchange_url(){
		$fee = isset($_SESSION['recharge917_fee']) ? $_SESSION['recharge917_fee'] : 0;
		$exchange_items = $result = array();
		require_once IA_ROOT . '/framework/library/edaixi/SwServer.class.php';
		$sw_server = new SwServer($this->user_info['user_id']);
		$exchange_items = $sw_server->get_exchange917_items($fee);
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
			if($fee == 100){
				$desp = '送' . $this->recharge_items[$fee]['title'];
				if(empty($flag)){
					$desp = '充值' . $fee . $desp;
				}
				$desp = '充值' . $fee . '送' . $this->recharge_items[$fee]['title'];
			}else{
				$desp = '可兑换' . $this->recharge_items[$fee]['size'] . $this->recharge_items[$fee]['title'] . $this->recharge_items[$fee]['piece'];
				if(empty($flag)){
					$desp = '充值' . $fee . '送' . $this->recharge_items[$fee]['points'] . '积分'. $desp;
				}
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
		$redis->incr('share917');
		$redis->hMset('share917:' . time(), array(
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
