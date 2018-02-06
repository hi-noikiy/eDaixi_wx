<?php
/**
  * 2017-08-26 招行一网通购买余额活动 (business_id暂定为9998)
  * 活动规则：自2017年始，未在e袋洗使用一网通支付的用户，可享受5元购买20元余额的活动
  */
class Cmb_balance extends BaseModule {
	//活动id
	const BUSINESS_ID = 9998;
	//支付方式
	const PAY_TYPE = 20;
	//充值金额
	const FEE = 5;
	//活动截止时间
	const END_TIME = '2017-09-08 23:59:59';
	//模板名称
	protected $model_name = 'cmb_balance';

	function __construct(){
		global $_W;
		parent::__construct();
		$this->open_server = new OpenServer($_W['config'], $this->user_info);
		require_once IA_ROOT . '/framework/library/edaixi/SwServer.class.php';
		$this->sw_server = new SwServer($this->user_info['user_id']);
	}

	//引导页
	public function index(){
		//判断活动是否过期
		$end_time = strtotime(self::END_TIME);
		if(time() > $end_time){
			error_report('抱歉，本次活动已经结束');
		}
		//检测用户是否有购买权限
		$ret = $this->sw_server->isFirstCmbpaySince2017();
		if(empty($ret)){
			error_report('系统繁忙，请稍后再试');
		}else if($ret['is_new'] == false){
			error_report('抱歉，您当前没有可购买次数');
		}else{
			$this->build_tradeno();
		}
	}

	// 建立交易单号
	private function build_tradeno() {
		global $_W, $_GPC;
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$from_user = $this->user_info['from_user'];

		// 获取用户首页城市
		$user_city = get_user_city();
		$city_id = $user_city['city_id'];
		// URL mark
		$mark = get_mark();
		$res = $this->open_server->icard_recharge($user_id, self::PAY_TYPE, self::FEE, '', $city_id, $mark, self::BUSINESS_ID);
		$ret_data = array('status' => (isset($res['ret']) ? $res['ret'] : false),'msg' => ''); 
		if(!$res['ret']){
		    error_report($res['error']);
		}
		// 记录充值交易参数
		$data = $res['data'];
		$params['tid'] = $data['trade_no'];
		$params['user_id'] = $user_id;
		$params['from_user'] = $from_user;
		$params['user_type'] = $user_type;
		$third_pay = $this->call_pay($params);

		//页面跳转
		header("Cache-control: private");
		if (!empty($third_pay['url'])) {
			$this->jumpUrl($third_pay['url']);
		} else if (!empty($third_pay['html'])) {
			echo $third_pay['html'];
		} else {
			error_report('支付出错啦');
		}	
	}

	// 调起第三方支付
	private function call_pay($params) {
		global $_W, $_GPC;
        $ret = $result = array();
        $return_url = urlencode(rtrim($_W['config']['site']['root'], '/').create_url('cmb_balance/success', array(), 'third'));
        if (!empty($params['tid'])) {
            $ret = $this->open_server->payment_sign($params['user_id'], $params['user_type'], self::PAY_TYPE, $params['tid'], self::FEE, 1, $return_url, $params['from_user']);
        }
        $result = $ret['data'];
        if(empty($result)){
        	error_report('支付出错啦!');
        }
        $result['success_url'] = $return_url;
        return $result;
	}

	//充值成功的页面
	public function success(){
		$homepage_url = 'https://wx.rongchain.com';
		include $this->template('success');
	}

}