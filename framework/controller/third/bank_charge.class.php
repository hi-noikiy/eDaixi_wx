<?php
/**
 *  @todo 充值营销工具，主要用于银行合作的充值活动
 *  此处活动为后台可配置
 */

class Bank_charge extends BaseModule {
	//活动ID （规定10000起，与可配置充值活动（500起）做区别）
	private $business_id;
	//模板名称
	protected $model_name = 'bank_charge';
	//活动信息
	private $charge_item;

	public function __construct(){
		global $_W,$_GPC;
		$this->business_id = isset($_GPC['act_id']) ? intval($_GPC['act_id']) : error_report('活动ID非法');

		parent::__construct();
		$this->open_server = new OpenServer($_W['config'], $this->user_info);
		require_once IA_ROOT . '/framework/library/edaixi/SwServer.class.php';
		$this->sw_server = new SwServer($this->user_info['user_id']);

		//判断是否需要从swoole获取活动信息
		if($this->is_need_activity_detail()){
			$charge_item = $this->sw_server->get_bankcharge_item($this->business_id);
			if(empty($charge_item)){
				error_report('获取活动配置信息失败！');
			}
			if($charge_item['end_time'] < time()){
				error_report('抱歉，活动已过期！');
			}
			$this->charge_item = $charge_item;
		}
	}

	//首页
	public function index(){
		//weixin渠道，分享信息
		$user_type = $this->user_info['user_type'];
		$is_from_weixin = (1 == $user_type);
		if($is_from_weixin){
			require IA_ROOT . '/framework/library/wxshare/include.php';
			$title = $this->charge_item['share_title'];
			$desc = $this->charge_item['share_desc'];
			$share_url = rtrim($_W['config']['site']['root'], '/').create_url('bank_charge/index', array('act_id'=>$this->business_id), 'third');
			$img_url = $this->charge_item['share_icon'];
		}
		
		$act_title = $this->charge_item['title'];
		$setting = json_decode($this->charge_item['setting'], true);
		$rule = $this->charge_item['rule'];
		$banner = $this->charge_item['banner_url'];

		$homepage = rtrim($_W['config']['site']['root'], '/').create_url('bank_charge/index', array('act_id'=>$this->business_id), 'third');
		$payment_url = create_url('bank_charge/payment_page', array('act_id'=>$this->business_id, 'loginback_url'=>$homepage), 'third');

		include $this->template('index');
	}

	//支付方式选择页面
	public function payment_page(){
		global $_W, $_GPC;
		$fee = $_GPC['fee'] ? $_GPC['fee'] : error_report('支付金额有误');
		//$fee = $_GPC['fee'] ? intval($_GPC['fee']) : error_report('支付金额有误');
		//获取允许的支付方式
		$paytypes = explode(',', $this->charge_item['paytypes']);
		if(count($paytypes) == 1){
			//只有一种支付方式，直接支付
			$paytype = $paytypes[0];
			$this->build_tradeno($paytype, $fee);
		}else{
			//加载支付方式页
			$pay_items = array();
			foreach ($paytypes as $val) {
				$pay_items[$val] = $_W['config']['pay_config'][$val];
			}
			$build_tradeno_url = create_url('bank_charge/charge_confirm', array('act_id'=>$this->business_id), 'third');
			include $this->template('payment_page');
		}
	}

	//支付方式页面提交跳转
	public function charge_confirm(){
		global $_GPC;
		$paytype = $_GPC['paytype'];
		$fee = $_GPC['fee'] ? $_GPC['fee'] : error_report('支付金额有误');
		//$fee = $_GPC['fee'] ? intval($_GPC['fee']) : error_report('支付金额有误');
		$this->build_tradeno($paytype, $fee);
	}

	//建立交易单号
	private function build_tradeno($paytype, $fee){
		//swoole获取充值金额
		//检查$_GPC['fee']是否在可充值金额范围内，不在则error_report();
		$setting = json_decode($this->charge_item['setting'], true);
		$fee_arr = array_column($setting, 'fee');
		if(!in_array($fee, $fee_arr)){
			error_report('支付金额有误');
		}

		$fee = number_format($fee, 2, '.', '');
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$from_user = $this->user_info['from_user'];
		$mobile = $this->user_info['is_login'];
		// 获取用户首页城市
		$user_city = get_user_city();
		$city_id = $user_city['city_id'];
		// URL mark
		$mark = get_mark();
		$res = $this->open_server->icard_recharge($user_id, $paytype, $fee, '', $city_id, $mark, $this->business_id);
		$ret_data = array('status' => (isset($res['ret']) ? $res['ret'] : false),'msg' => ''); 
		if(!$res['ret']){
		    error_report($res['error']);
		}

		// 记录充值交易参数
		$data = $res['data'];
		$params['tid'] = $data['trade_no'];
		$params['user_id'] = $user_id;
		$params['from_user'] = $from_user;
		$params['fee'] = $fee;
		$params['user_type'] = $user_type;
		$params['mobile'] = $mobile;
		$third_pay = $this->call_pay($params, $paytype);

		header("Cache-control: private");
		if (!empty($third_pay['url'])) {
			$this->jumpUrl($third_pay['url']);
		} else if (!empty($third_pay['html'])) {
			echo $third_pay['html'];
		} else {
			error_report('支付出错啦', create_url('bank_charge/index', array('act_id'=>$this->business_id), 'third'));
		}
	}

	// 调起第三方支付
	private function call_pay($params, $pay_type) {
		global $_W;
        $ret = $result = array();
        $return_url = urlencode(rtrim($_W['config']['site']['root'], '/').create_url('bank_charge/charge_success', array('act_id'=>$this->business_id), 'third'));
        if (!empty($params['tid']) && !empty($params['fee']) && !empty($pay_type)) {
            $ret = $this->open_server->payment_sign($params['user_id'], $params['user_type'], $pay_type, $params['tid'], $params['fee'], 1, $return_url, $params['from_user'], $params['mobile']);
        }
        $result['html'] = $ret['data'];
        if(empty($result['html'])){
        	error_report('支付出错啦!', create_url('bank_charge/index', array('act_id'=>$this->business_id), 'third'));
        }
        return $result;
	}

	//充值成功的页面
	public function charge_success(){
		include $this->template('charge_success');
	}

	//是否必须从swoole获取活动信息
	private function is_need_activity_detail(){
		global $_GPC;
		$do = isset($_GPC['do']) ? $_GPC['do'] : 'index';
		//must
		$must_arr = array(
			'index',
			'payment_page',
			'charge_confirm',
			);
		if(in_array($do, $must_arr)){
			return true;
		}
		return false;
	}

}