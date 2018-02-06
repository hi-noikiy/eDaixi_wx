<?php
/**
  * 2017 717充值活动 (business_id暂定为9999)
  * 由于市场部需要测试新积分商城的商品效果，故此次活动参数直接写死，且此次活动，一档充值金额，可对应多种商品
  */
class Static_charge717 extends BaseModule {
	//活动id
	private $business_id = 9999;
	//session key
	private $session_key = 'recharge_9999_fee';
	//活动名称
	private $activity_title = '甄选称心礼，充满小惊“洗”';
	//模板名称
	protected $model_name = 'static_charge717';
	//商品排列
	private $goods_layout = 6;
	//商品列表
	private $recharge_items = array();
	//活动规则
	//private $rule = '';
	//活动协议
	//private $protocol = '';
	//默认选中项
	private $default_num = 1;
	//礼品兑换截止时间
	private $exchange_end_time = '2017-08-30 23:59:59';
	//活动结束时间
	private $end_time = '2017-08-20 23:59:59';
	//渠道埋点，用mark区分
	private $mark_talkingdata = array();

	function __construct(){
		global $_W;
		parent::__construct();
		$this->open_server = new OpenServer($_W['config'], $this->user_info);
		require_once IA_ROOT . '/framework/library/edaixi/SwServer.class.php';
		$this->sw_server = new SwServer($this->user_info['user_id']);
		
		$end_time_int = strtotime($this->end_time);
		if($end_time_int < time()){
			error_report('活动已过期');
		}
		$this->recharge_items = array(
			'499' => array(
				'money' => '499',
				'img' => assets_link('/framework/style/images/charge717499.jpg'),
				'points' => '599999',
				'title' => '超值可选礼',
				'desc' => '超值可选礼一份',
				'url' => '',
				'remark' => false
			),
			'999' => array(
				'money' => '999',
				'img' => assets_link('/framework/style/images/charge717999.jpg'),
				'points' => '699999',
				'title' => '换享可选礼',
				'desc' => '换享可选礼一份',
				'url' => '',
				'remark' => false
				),
			'1999' => array(
				'money' => '1999',
				'img' => assets_link('/framework/style/images/charge7171999.jpg'),
				'points' => '799999',
				'title' => '品质可选礼',
				'desc' => '品质可选礼一份',
				'url' => '',
				'remark' => false
				),
			'3999' => array(
				'money' => '3999',
				'img' => assets_link('/framework/style/images/charge7173999.jpg'),
				'points' => '899999',
				'title' => '尊享可选礼',
				'desc' => '尊享可选礼一份',
				'url' => '',
				'remark' => true
				),
			'6999' => array(
				'money' => '6999',
				'img' => assets_link('/framework/style/images/charge7176999.jpg'),
				'points' => '999999',
				'title' => '豪华可选礼',
				'desc' => '豪华可选礼一份',
				'url' => '',
				'remark' => false
				)
			);
	}

	//引导页
	public function index(){
		$user_type = $this->user_info['user_type'];
		$is_from_weixin = (1 == $user_type);
		//微信分享
		if($is_from_weixin){
			require IA_ROOT . '/framework/library/wxshare/include.php';
			//微信分享params
			$share_url = rtrim($_W['config']['site']['root'], '/').create_url('static_charge717/index', array(), 'third');
			$title = '甄选称心礼，充满小惊“洗”';
			$desc = '充值免费选好礼，更有MK手袋、潘多拉手链等你来';
			$img_url = assets_link('/resource/image/charge717.jpg');
		}
		include IA_ROOT . "/new_weixin/view/static_717.html";
	}

	//首页接口
	public function recharge_page(){
		global $_W, $_GPC;

		//当用户处于登陆状态时，获取用户当前可兑换的商品
		$exchange_items = array();
		$exchange_history = array();
		if(!empty($this->user_info['user_id'])){
			$exchange_items = $this->sw_server->get_exchange717_items('','', $this->business_id);
			//组织数据
			$exchange_arr = array();
			foreach ($exchange_items as $key => $value) {
				$exchange_arr[$key]['title'] = $this->recharge_items[$key]['desc'];
				$exchange_arr[$key]['url'] = rtrim($_W['config']['site']['root'], '/').create_url('static_charge717/show_exchange_items', array('fee'=>$key), 'third');
			}
			$exchange_history = $this->sw_server->get_exchange717_items('', 1, $this->business_id);
		}
		//商品详情url
		$nologin_url = $this->recharge_items;
		foreach ($this->recharge_items as $fee => $val) {
			$nologin_url[$fee]['url'] = rtrim($_W['config']['site']['root'], '/').create_url('static_charge717/item_detail', array('fee'=>$fee), 'third');
		}
		// 根据mark值判断来源
		$mark = get_mark();
		$mark_source = '其他';
		if($mark && isset($this->mark_talkingdata[$mark])){
			$mark_source = $this->mark_talkingdata[$mark]; 
		}

		$homepage_backurl = rtrim($_W['config']['site']['root'], '/').create_url('static_charge717/index', array(), 'third');
		$cardno_backurl = rtrim($_W['config']['site']['root'], '/').create_url('static_charge717/recharge_card_page', array(), 'third');
		$data = array(
			'exchange_items' => $exchange_arr,
			'exchange_history' => $exchange_history,
			'recharge_items' => array_values($nologin_url),
			'mark_source' => $mark_source,
			'cardno_page' => create_url('static_charge717/recharge_card_page', array('loginback_url'=>$cardno_backurl), 'third'),
			'payment_page' => create_url('static_charge717/payment_page', array('loginback_url'=>$homepage_backurl), 'third')
			);
		echo_json(true, $data);
	}

	//商品详情页
	public function item_detail(){
		global $_GPC;
		$fee = $_GPC['fee'] ? intval($_GPC['fee']) : 0;
		$img_url = assets_link('/framework/style/images/detail717_' . $fee . '.png');
		include $this->template('item_detail');
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
		$icard_charge_url = create_url('static_charge717/ajax_cardno_recharge', array(), 'third');
		// 充值卡充值页面url
		$cardno_page_url = create_url('static_charge717/recharge_card_page', array(), 'third');
		//在线充值页面url
		$online_charge_url = create_url('static_charge717/recharge_page', array(), 'third');

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
                $result['success_url'] = create_url('static_charge717/recharge_success', array(), 'third');
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
		$build_tradeno_url = create_url('static_charge717/build_tradeno', array(), 'third');
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
			error_report('支付出错啦', create_url('static_charge717/recharge_page', array(), 'third'));
		}	
	}

	// 调起第三方支付
	private function call_pay($params, $pay_type) {
		global $_W, $_GPC;
        $ret = $result = array();
        $return_url = urlencode(rtrim($_W['config']['site']['root'], '/').create_url('static_charge717/recharge_success', array(), 'third'));
        if (!empty($params['tid']) && !empty($params['fee']) && !empty($pay_type)) {
            $ret = $this->open_server->payment_sign($params['user_id'], $params['user_type'], $pay_type, $params['tid'], $params['fee'], 1, $return_url, $params['from_user']);
        }
        $result = $ret['data'];
        if(empty($result)){
        	error_report('支付出错啦!', create_url('static_charge717/recharge_page', array(), 'third'));
        }
        $result['success_url'] = $return_url;
        return $result;
	}

	//充值成功的页面
	public function recharge_success(){
		$user_type = $this->user_info['user_type'];
		$fee = isset($_SESSION[$this->session_key]) ? $_SESSION[$this->session_key] : 0;
		$recharge_title = $this->get_recharge_title($fee);
		$exchange_items = array();
		if($fee > 0){
			$exchange_items = $this->sw_server->get_exchange717_items($fee, '', $this->business_id);
		}
		$recharge_item = $this->get_recharge_title($fee);
		$homepage_url = rtrim($_W['config']['site']['root'], '/').create_url('static_charge717/index', array(), 'third');
		include $this->template('recharge_success');
	}

	//可兑换礼品页
	public function show_exchange_items(){
		global $_W, $_GPC;
		$user_type = $this->user_info['user_type'];
		$fee = isset($_GPC['fee']) ? intval($_GPC['fee']) : 0;
		$exchange_items = array();
		if($fee > 0){
			$exchange_items = $this->sw_server->get_exchange717_items($fee, '', $this->business_id);
		}
		$homepage_url = rtrim($_W['config']['site']['root'], '/').create_url('static_charge717/index', array(), 'third');
		include $this->template('show_exchange_items');
	}

	//显示充值页“充值XX送XX积分可换取XXX”
	private function get_recharge_title($fee, $flag=''){
		$fee = intval($fee);
		$desp = '';
		if(array_key_exists($fee, $this->recharge_items)){
			$desp = '可兑换' . $this->recharge_items[$fee]['title'];
			if(empty($flag)){
				$desp = '充值' . $fee . '送' . $this->recharge_items[$fee]['points'] . '积分';
			}
		}
		return $desp;
	}

}