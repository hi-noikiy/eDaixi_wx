<?php
/**
 * 三八妇女节充值活动
 *  活动日期：2016年03月08日00：00：00～?
 */
class Recharge38 extends BaseModule {
	function __construct() {
		global $_W,$_GPC;
		parent::__construct();
		$this->model_name = 'recharge38';
		$this->cid = isset($_GPC['cid']) ? $_GPC['cid'] : 0;
	}
	
	// 充值页面
	public function recharge_page(){
		global $_W,$_GPC;
		$user_type = $this->user_info['user_type'];
		$is_from_weixin = (1 == $user_type);
		// 分享活动页到微信朋友圈
		if($is_from_weixin){
			require IA_ROOT . '/framework/library/wxshare/include.php';
			if($_GPC['cid'] == '77'){
				$share_url = HTTP_TYPE.$_SERVER['HTTP_HOST'].'/'.create_url('recharge38/recharge_page',array('cid' => 77), 'third');
				$title = '七夕“洗”欢你，为爱向前“充”！';
				$desc = '七夕为心爱的TA充值e袋洗';
				$img_url = assets_link('/framework/style/images/friend.png');
				$callback_url = create_url('recharge38/share_success', array('cid' => 77), 'third');
			}else if($_GPC['cid'] == '14'){
				$share_url = HTTP_TYPE.$_SERVER['HTTP_HOST'].'/'.create_url('recharge38/recharge_page',array('cid' => 14), 'third');
				$title = '314白色情人节，为TA的美腻充值！';
				$desc = '送花谁都会，为我洗衣才是真爱！';
				$img_url = assets_link('/framework/style/images/share_14_recharge.jpg');
				$callback_url = create_url('recharge38/share_success', array('cid' => 14), 'third');
			}else{
				$share_url = HTTP_TYPE .'wx.rongchain.com/mobile.php?m=third&act=recharge38&do=recharge_page';
				$title = '3.8女人节 为爱充值';
				$desc = '致敬女王，喜欢要表达，宠她要及时';
				$img_url = assets_link('/resource/image/payforlove200.jpg');
				$callback_url = create_url('recharge38/share_success', array(), 'third');
			}
		}
		// 支付页面 url
		if($_GPC['cid'] == 77){
			$payment_page_url = create_url('recharge38/payment_page', array('cid'=>77), 'third');
			include $this->template('77/recharge_page');
		}else if($_GPC['cid'] == 14){
			$payment_page_url = create_url('recharge38/payment_page', array('cid'=>14), 'third');
			include $this->template('14/recharge_page');
		}else{
			$payment_page_url = create_url('recharge38/payment_page', array(), 'third');
			include $this->template('recharge_page');			
		}
	}
	
	// 支付页面
	public function payment_page() {
		global $_W, $_GPC;
		$user_type = $this->user_info['user_type'];
		$username = $_GPC['username'] ?: '';
		$mobile = $_GPC['mobile'] ?: '';
		$message = $_GPC['message'] ?: '七夕快乐。洗欢宠爱你，从今天开始~';
		$fee = $_GPC['fee'] ?: '';
		$paytype = 'ignore';
		$skipserv = $_GPC['skipserv']; # 跳过服务范围验证
		// 充值返现（充 xxx 返 xxx） -- 取赠送金额,校验数据
		$recharge_items = $this->get_recharge_items();
		
		// 交易数据校验
		$result = $this->check_rechage($username, $mobile, $message, $fee, $paytype, $recharge_items, $skipserv);
		if(is_ajax()){
			message($result, '', 'ajax');
			exit;
		}else{
			// 失败回跳地址
			if (isset($_SERVER['HTTP_REFERER'])){
				$back_url = $_SERVER['HTTP_REFERER'];
			}else {
				$back_url = create_url('recharge38/recharge_page', array('cid' => $this->cid), 'third');
			}
			if(! $result['state']){
				header("Location:" . $back_url);
				exit;
			}
		}
		
		$git_money = 0.00;	# 平台赠送金额
		foreach ($recharge_items as $key => $value) {
			if ($fee >= $value['min'] && $fee < $value['max']) {
				$git_money = number_format($value['money_give'], 2, '.', '');
				break;
			}
		}
		$fee = number_format($_GPC['fee'], 2, '.', '') ?: '0.00';
		
		// 建立交易单号 url
		$build_tradeno_url = create_url('recharge38/build_tradeno', array('cid' => $this->cid), 'third');
		if($_GPC['cid'] == 77){
			include $this->template('77/payment_page');
		}else if($_GPC['cid'] == 14){
			include $this->template('14/payment_page');
		}else{
			include $this->template('payment_page');
		}
	}
	
	// 建立交易单号
	public function build_tradeno() {
		global $_W, $_GPC;
		$user_type = $this->user_info['user_type'];
		$paytype = $_GPC['paytype'];
		$username = $_GPC['username'];
		$mobile = $_GPC['mobile'];
		$message = $_GPC['message'] ?: '七夕快乐。洗欢宠爱你，从今天开始~';
		$fee = $_GPC['fee'];
		$skipserv = $_GPC['skipserv']; # 跳过服务范围验证
		
		// 校验交易数据
		$recharge_items = $this->get_recharge_items();  # 充值返现（充 xxx 返 xxx）
		$result = $this->check_rechage($username, $mobile, $message, $fee, $paytype, $recharge_items, $skipserv);
		if(! $result['state']){
		    error_report($result['msg']);
			header("Location:" . create_url('recharge38/recharge_page', array('cid' => $this->cid), 'third'));
			exit;
		}
		
		/*
			// === 测试金额 0.01 === //
			$fee = 0.01;
		//*/
		
		// 记录充值交易参数
		$params = array();
		$params['paytype'] = $paytype;
		$params['fee'] = $fee; # 注意：键为 'fee',否则会报签名错误
		$params['username'] = $username;
		$params['mobile'] = $mobile;
		$params['message'] = $message;
		$params['skipserv'] = $skipserv;
		

		// 生成交易单号
		// $skipserv = ('yes' == $skipserv);
		$skipserv = true; # 第三方接口不稳定,跳过归属地验证过程
		$res = $this->api_server->charge_by_mobile($username, $mobile, $message, $fee, $paytype, $skipserv);
		if($res['data']){ // 成功
			$_SESSION['pay_species'] = 'recharge38';
			$params['tid'] = $res['data']['trade_no'];
			$params['title'] = '用户充值 ￥' . $fee;
			/**
			 * 调起第三方支付
			 * */
			$this->call_pay($params);
		}else{  // 生成交易单号失败
			error_report($res['error'] ?: '网络错误,请稍后重试');
			//header("Location:" . create_url('recharge38/recharge_page', array(), 'third'));
			exit;
		}
	}
	
	// 调起第三方支付
	private function call_pay($params) {
		global $_W, $_GPC;
		$paytype = $params['paytype'];
		require_once IA_ROOT . '/framework/model/payment.mod.php';
		
		$_SESSION['pay_species'] = 'recharge38';
		$_SESSION['pay_cid'] = $this->cid ;
		if(2 == $paytype){	// 微信支付
			$sl = base64_encode(json_encode($params));
			$auth = sha1($sl . $_W['weid'] . $_W['config']['setting']['authkey']);
			header("Location: {$_W['config']['site']['root']}payment/wechat/pay.php?weid={$_W['weid']}&auth={$auth}&ps={$sl}");
			exit;
		}else if(6 == $paytype) {	// 支付宝支付
			$ret = alipay_build($params, $_W['account']['payment']['alipay']);
			if ($ret['html']) {
				exit($ret['html']);
			}
			if ($ret['url']) {
				header("Location: " . $ret['url']);
				exit;
			}
		}else if(11 == $paytype){	// 百度支付
			$ret = baidu_build($params, $_W['account']['payment']['baidu']);
			if ($ret) {
				exit($ret);
			}
		}
	}
	
	// 校验充值信息合法性
	public function check_rechage($username, $mobile, $message, $fee, $paytype, $recharge_items, $skipserv='no'){
		$result = array('state'	=> 1);
		if('ignore' != strval($paytype) && !in_array($paytype, array(2, 6, 11))){
			$result['state'] = '0';
			$result['msg'] = '请选择支付方式';
			return $result;
		}
		if($fee <= 0){
			$result['state'] = '0';
			$result['msg'] = '请选择充值金额';
			return $result;
		}else{
			$usable_recharge_item = false;
			foreach ($recharge_items as $recharge_item){
				if($fee == $recharge_item['min']){
					$usable_recharge_item = true;
				}
			}
			if(! $usable_recharge_item){
				$result['state'] = '0';
				$result['msg'] = '充值金额有误';
				return $result;
			}
		}
		if(! check_mobile($mobile)){
			$result['state'] = '0';
			$result['msg'] = '请输入正确手机号码';
			return $result;
		}
		if('no' == $skipserv && ! $this->mobile_in_service($mobile)){
			$result['state'] = '2';
			$result['msg'] = '您输入的手机号所在地不在e袋洗服务范围，是否继续充值';
			return $result;
		}
		if(wordlen($message) > 30){
			$result['state'] = '0';
			$result['msg'] = '留言长度不能超过30个字符';
			return $result;
		}
		if(wordlen($username) < 2 || wordlen($username) > 10){
			$result['state'] = '0';
			$result['msg'] = '姓名长度为2~10个字符';
			return $result;
		}
		return $result;
	}
	
	// 验证手机归属地是否在服务范围
	public function mobile_in_service($mobile){
		global $_GPC;
		$res = $this->api_server->mobile_in_service($mobile);
		return $res['data'];
	}
	
	// 获取并缓存（充 xxx 返 xxx）选项
	public function get_recharge_items(){
		/*
		$user_type = $this->user_info['user_type'];
		$redis_recharge_items = redis()->get('recharge_items');
		if($redis_recharge_items && 'null' != $redis_recharge_items){
			return json_decode($redis_recharge_items, true);
		}else{
			$recharge_items = $this->open_server->recharge_settings($user_type);
			redis()->setex('recharge_items', 24 * 3600, json_encode($recharge_items));
			return $recharge_items;
		}
		//*/
		
		// 七夕节充值活动期间,产品明确约定充值选项固定不变 -- 为提高效率不再做查询
		$recharge_items = array (
				0 => array (
						'id' => 18,
						'weid' => 5,
						'money_give' => 22,
						'min' => 177,
						'dateline' => 1456109567,
						'kind' => 'edaixi',
						'max' => 500,
				),
				1 => array (
						'id' => 13,
						'weid' => 5,
						'money_give' => 100,
						'min' => 500,
						'dateline' => 1406802641,
						'kind' => 'edaixi',
						'max' => 777,
				),
				2 => array (
						'id' => 24,
						'weid' => 5,
						'money_give' => 222,
						'min' => 777,
						'dateline' => 1456109586,
						'kind' => 'edaixi',
						'max' => 2000,
				),
				3 => array (
						'id' => 25,
						'weid' => 5,
						'money_give' => 600,
						'min' => 2000,
						'dateline' => 1456109594,
						'kind' => 'edaixi',
						'max' => 299999,
				),
		);
		return $recharge_items;
	}
	
	// 活动分享成功回调
	public function share_success(){
		global $_GPC;
		$city_id = $_GPC['city_id'];
		$cid = $_GPC['cid'];
		$city = check_city_id($city_id) ?: '***';
		
		$redis = redis();
		$redis->incr('share38');
		$redis->hMset('share38:' . time(), array(
			'ip' => getip(),
			'city' => $city,
		    'date' => date('Y-m-d H:i:s'),
		    'cid' => $cid
		));
	}
}
