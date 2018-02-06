<?php
defined('IN_IA') or exit('Access Denied');
use Edx\Model\ModelLoader as Model;
class Icard extends BaseModule {
	function __construct(){
		global $_W;
		parent::__construct();
		$this->model_name = 'icard';
		$this->open_server = new OpenServer($_W['config'],$this->user_info);
		require_once IA_ROOT . '/framework/library/edaixi/SwServer.class.php';
		$this->sw_server = new SwServer($this->user_info['user_id']);
	}
	
	// 获取获取意见反馈项(无需登录)
	public function feedback(){
		global $_W,$_GPC;
		$is_login = $this->user_info['is_login'] ? 1 : 0;
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$feedback_item = $this->open_server->get_feedback_types($user_id, $user_type);
		$feedback_submit = create_url('icard/do_feedback');
		$http_referer =  $_SERVER['HTTP_REFERER'] ?: create_url('homepage/index');
		$login_feedback = create_url('icard/login_feedback');
		include $this->template('feedback');
	}
	
	// 获取获取意见反馈项(需登录)
	public function login_feedback(){
		$this->feedback();
	}
	
	// 提交意见反馈信息
	public function do_feedback(){
		global $_W,$_GPC;
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$feedback_type = $_GPC['feedback_type']; 
		$feedback_content = $_GPC['feedback_content'];
		$resp = $this->open_server->set_feedback($user_id, $user_type, $feedback_type, $feedback_content);
		if($resp['ret']){
      		$result['state'] = 1;
	     	message($result, '', 'ajax');
		}else{
    		$result['state'] = 0;
	      	$result['msg'] = $resp['error'] ?: '系统繁忙,请稍后重试';
	     	message($result, '', 'ajax');
		}
	}
	
	// 个人中心
	public function my_icard(){
		global $_W,$_GPC;
		$did = 'myicard'; # 页脚导航--点亮“我的”选项
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$client_id = $this->user_info['client_id'];
		$from_user = $this->user_info['from_user'];
		// 个人中心 背景 LOGO 标语 客服
		$user_center_info = $this->open_server->user_center_info($user_id, $user_type);
		$user_center_info['center_info_back_group_img'] = $user_center_info['center_info_back_group_img']
		? $user_center_info['center_info_back_group_img']
		: assets_link('/framework/style/images/wecat_head.png');
		
		$user_center_info['center_info_avatar_img'] = $user_center_info['center_info_avatar_img']
		? $user_center_info['center_info_avatar_img']
		: assets_link('/framework/style/images/edaixi_logo.png');
		
		$user_center_info['center_info_slogan'] = $user_center_info['center_info_slogan']
		? $user_center_info['center_info_slogan']
		: '洗衣就用e袋洗 幸福生活每一天';
				
		$user_center_info['service_tel'] = $user_center_info['service_tel']
		? $user_center_info['service_tel']
		: '400-818-7171';
		//获取用户钱包信息
		$card_model = Model::get('card', $this->user_info);
		$user_wallet = $card_model->userWallet($user_id);

		$is_from_weixin = (1 == $user_type);
		$feedback_url = create_url('icard/feedback');
		if(empty($this->user_info['is_login'])){
			$denglu = 'no';
			$points_mall_url = create_url('icard/login_back', array('loginback_url' => create_url('icard/redirect_points_mall')));
			include $this->template('my_icard');
			exit;
		}else{
			$denglu = 'yes';
			$tel = $this->user_info['is_login'];
		}
		// 积分商城链接
		$points_mall_url = create_url('icard/redirect_points_mall');

		//零彩宝入口链接
		$lingcb_entrance = '';
		/*
		//貌似没用了，取消掉
		if(!empty($user_id)){
			//首先判断，该用户是否有彩票记录，有才显示入口
			$ret = $this->sw_server->get_lingcb_total_prize();
			if(!empty($ret)){
				require IA_ROOT . '/framework/library/edaixi/third_server.class.php';
				$third_server = new ThirdServer();
				$data = array();
				$data['uId'] = (string)$user_id;
				$data['lcbOrderId'] = $data['errorUrl'] = '';
				$data['source'] = 'edaixi';
				$lingcb_entrance = $third_server->get_lingcb_entrance($data);
			}
		}*/

		include $this->template('my_icard');
	}
	
	// 我的积分
	public function my_points(){
		global $_W,$_GPC;
		$did = 'myicard'; # 页脚导航--点亮“我的”选项
		$user_id = $this->user_info['user_id'];
		if(empty($this->user_info['is_login'])){
			$denglu = 'no';
			$points_mall_url = create_url('icard/login_back', array('loginback_url' => create_url('icard/my_icard')));
			include $this->template('my_icard');
			exit;
		}else{
			$tel = $this->user_info['is_login'];
		}
		// 积分商城链接
		$points_mall_url = create_url('icard/redirect_points_mall');
				
		// 积分明细
		$points_detail = $this->sw_server->get_points_detail();
		$points_detail = $this->format_points_detail($points_detail);
		
		$total = $points_detail['total']; 					# 总积分
		$exchange = $points_detail['duiba'];				# 已兑换积分 -
		$login_points = $points_detail['login'];			# 每日积分 +
		$icard_pay_points = $points_detail['yuezhifu'];		# 余额支付积分 +
		$other_pay_points = $points_detail['feiyuezhifu'];	# 其他支付积分 +
		$comment_points = $points_detail['pinglun'];		# 订单评论积分 +
		$order_points = $points_detail['xiadan'];			# 下订积分 +
		include $this->template('my_points');
	}
	// 格式化积分明细
	public function format_points_detail($data){
		$points_detail = array();
		$points_detail['total'] = $data['extcredits1'] ? $data['extcredits1'] : 0;
		foreach($data['actions'] as $action => $points){
			if($points){
				foreach($points as $key => $val){
					if(is_numeric($val['extcredits1'])){
						$points_detail[$action][$key] = $val;
						$points_detail[$action][$key]['create_date'] = date('Y.m.d H:i:s', $val['createtime']);
					}
				}
			}else{
				$points_detail[$action][0]['extcredits1'] = 0;
				$points_detail[$action][0]['create_date'] = '';
			}
		}
		return $points_detail;
	}
	// 交易（余额）明细
	public function balance(){
		global $_W,$_GPC;
		$did = 'myicard'; # 页脚导航--点亮“我的”选项
		$user_id = $this->user_info['user_id'];
		if(empty($this->user_info['is_login'])){
			$denglu = 'no';
			include $this->template('my_icard');
			exit;
		}else{
			$tel = $this->user_info['is_login'];
		}
		// 余额总额
		$icard = $this->open_server->get_icard($user_id);
		$icard_sum = number_format($icard['coin'], 2, '.', '');
		// 余额明细
		$icard_details = $this->open_server->get_icard_details($user_id, 1);
		$page = 1;
		$rows_count = count($icard_details) ? count($icard_details) : 0;
		//重定向到余额商城，免登录链接
		$balance_mall_url = create_url('icard/redirect_balance_mall');
		include $this->template('balance');
	}
	// 交易（余额）明细 -- 下一页
	public function next_balance(){
		global $_W,$_GPC;
		$user_id = $this->user_info['user_id'];
		$page = intval($_GPC['page']);
		$icard_details = $this->open_server->get_icard_details($user_id, $page);
		$rows_count = count($icard_details) ? count($icard_details) : 0;
		$html = '';
		foreach ($icard_details as $key => $icard_detail){
			$html .= "<div class='jifen_itle2 feature_block icard_row' page='{$page}' rows_count='{$rows_count}'>{$icard_detail['type']}
			 	<div class='add_jifen chongzhi_yue " . ($icard_detail['increase'] < 0 ? 'green_color' : '') . "'>" . $icard_detail['increase'] . "</div>
			    <div class='time2'>{$icard_detail['created_at']}<span>{$icard_detail['status']}</span></div>
			 </div>
			 <div class='borderD'></div>";
		}
		$result['rows_count'] = $rows_count;
		$result['html'] = $html;
		message($result, '', 'ajax');
	}

	// 验证手机号码是否注册
	public function check_user_by_tel(){
		global $_W, $_GPC;
		$user_id = $this->user_info['user_id'];
		$mobile = $_GPC['mobile'];
		$res = $this->open_server->can_recharge_for_tel($user_id, $mobile);
		if(false === $res['ret'])
			echo_json(false, '', array('message'=>'网络错误'));
		echo_json(true, $res);
	}

	// 个人中心余额充值页
	public function icard_charge(){
		global $_W,$_GPC;
		include IA_ROOT . "/new_weixin/view/recharge_online.html";
	}

	// 在线充值接口
	public function recharge_online_api(){
		global $_W,$_GPC;
		$user_id = $this->user_info['user_id'];
		// 用户当前首页城市
		if($_GPC['city_id']){
			$city_id = intval($_GPC['city_id']);
		}else{
			$city_info = get_user_city();
			$city_id = $city_info['city_id'];
		}
		// 引导充值--订单金额折扣提示
		$recharge_discount = $_GPC['recharge_discount'] ?: '';
		$recharge_callback = $_GPC['recharge_callback'] ?: '';
		//在线充充值url
		$loginback_url = add_params('/new_weixin/view/recharge_online.html',
				array(
					'recharge_discount' => $recharge_discount,
					'recharge_callback' => $recharge_callback
				)
			);
		// 用户未登录处理
		if(!$this->user_info['is_login']){
			echo_json(false, '', array(
				'url'	=>	create_url('icard/icard_charge', array(
						'loginback_url'	=>	$loginback_url,
					)),
				));
		}
		//获取用户钱包信息
		$card_model = Model::get('card', $this->user_info);
		$user_wallet = $card_model->userWallet($user_id);
		$user_icard_amount = $user_wallet['icard_amount'];

		//获取兑换活动充值码的链接
		$recommend_url = '';
		$ret = $this->sw_server->get_recommend_event();
		if(!empty($ret)){
			$recommend_url = create_url('auto_activity/index', array('act_id'=>$ret['id']), 'third');
			$active_name = $ret['active_name'];
		}
		
		// 充值信息
		$recharge_list = $this->open_server->get_recharge_info($city_id);
		// 在线充值提交url
		$online_charge_url = create_url('payment/recharge_pay',array('status' => 1));
		echo_json(true, array(
				'user_icard_amount'	=>	$user_icard_amount,
				'recharge_list'	=>	$recharge_list,
				'online_charge_url'	=>	$online_charge_url,
				'recommend_url' => $recommend_url,
				'recommend_name' => $active_name
			));
	}

	// 充值卡充值接口
	public function recharge_icard_api(){
		global $_W,$_GPC;
		// 用户未登录处理
		$user_id = $this->user_info['user_id'];
		$is_login = $this->user_info['is_login'];
		// 用户当前首页城市
		if($_GPC['city_id']){
			$city_id = intval($_GPC['city_id']);
		}else{
			$city_info = get_user_city();
			$city_id = $city_info['city_id'];
		}
		//获取用户钱包信息
		$card_model = Model::get('card', $this->user_info);
		$user_wallet = $card_model->userWallet($user_id);
		$user_icard_amount = $user_wallet['icard_amount'];
		// 引导充值--订单金额折扣提示
		$recharge_discount = $_GPC['recharge_discount'] ?: '';
		$recharge_callback = $_GPC['recharge_callback'] ?: '';
		//充值卡充值页面url
		$recharge_icard_url = add_params('/new_weixin/view/recharge_cardno.html',
				array(
					'recharge_discount' => $recharge_discount,
					'recharge_callback' => $recharge_callback
				)
			);
		//获取兑换活动充值码的链接
		$recommend_url = '';
		$ret = $this->sw_server->get_recommend_event();
		if(!empty($ret)){
			$recommend_url = create_url('auto_activity/index', array('act_id'=>$ret['id']), 'third');
			$active_name = $ret['active_name'];
		}
		// 充值卡充值提交url
		$icard_charge_url = create_url('icard/chongzhika_charge');
		//充值卡充值成功跳转url
		$charge_callback_url = $recharge_callback ?: $recharge_icard_url;

		echo_json(true, array(
				'user_icard_amount'	=>	$user_icard_amount,
				'icard_charge_url'	=>	$icard_charge_url,
				'charge_callback_url'	=>	$charge_callback_url,
				'recommend_url' => $recommend_url,
				'recommend_name' => $active_name
			));
	}

	// 为别人充值记录接口
	public function recharge_other_log_api(){
		global $_W,$_GPC;
		$user_id = $this->user_info['user_id'];
		$page = $_GPC['page'] ?: 1;
		$per_page = 20;
		$data = $this->open_server->recharge_details_for_others($user_id, $page, $per_page);
		if(false === $date['ret'])
			echo_json(false, '', array('message' => '网络错误'));
		echo_json(true, $data);
	}

	// 充值卡验证
	public function chongzhika_charge(){
		global $_W,$_GPC;
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$client_id = $this->user_info['client_id'];

		$result['state'] = 0;
		$sncode = str_replace(' ', '', $_GPC['sncode']);
		// 为他人充值的手机号
		$tel = trim($_GPC['tel']) ?: '';
		//卡模型
		$user_info = $this->user_info;
        $card_model = Model::get('card', $user_info);
        $resp = $card_model->cardCharge($user_id, $sncode, $tel);
	    message($resp, '', 'ajax');
	}
	public function bind_icard_show(){
		global $_W,$_GPC;
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$client_id = $this->user_info['client_id'];

		$card = $this->open_server->get_icard($user_id);

  		include $this->template('bind_icard_show');
	}
	public function bind_icard(){
		global $_W,$_GPC;
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$client_id = $this->user_info['client_id'];

		$sn_code = $_GPC['sn_code'];
		$sn_password = $_GPC['sn_password'];

		$resp = $this->open_server->bind_member_card($user_id, $sn_code, $sn_password);
		if($resp['ret']){
			$result['state'] = 1;
			$result['msg'] = '绑定成功';
			message($result, '', 'ajax');
		}else{
			$result['state'] = 0;
			$result['msg'] = $resp['error'];
			message($result, '', 'ajax');
		}
	}
	public function show_more(){
		global $_W,$_GPC;
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$client_id = $this->user_info['client_id'];
		include $this->template('show_more');
	}
	// 提取优惠券总数和过期信息
	public function get_coupon_info($coupon_list){
		if(false === $coupon_list['ret'] || $coupon_list['error']){
			return array();
		}
		if(! count($coupon_list)){
			return array();
		}
		$coupon_info = array();
		$today_timestamp = strtotime(date('Y-m-d')); //当天时间戳
		$coupon_count = count($coupon_list);
		$expire_remind = 0;
		foreach($coupon_list as $key => $coupon){
			if(!$coupon['used']){
				$expire_timestamp = strtotime($coupon['coupon_endtime']); 	//过期时间戳
				$remind_timestamp = strtotime('-3 days', $expire_timestamp); //提醒时间戳
				if($today_timestamp >= $remind_timestamp){
					++ $expire_remind;
				}
			}
		}
		$coupon_info['coupon_count'] = $coupon_count;
		$coupon_info['expire_remind'] = $expire_remind;
		return $coupon_info;
	}
	public function point_des(){
		global $_W;
		include $this->template('point_des');
	}
	// 推荐有奖
	public function recommend(){
		global $_W;
		require IA_ROOT.'/framework/library/wxshare/include.php';
		
		$user_id = $this->user_info['user_id'];
		$from_user = $this->user_info['from_user'];
		// 分享信息初始值
		$rcmd_url = 'https://mp.weixin.qq.com/s?__biz=MzA3NjA4OTkwNQ==&mid=202716551&idx=1&sn=d55833645fd4dc9810bb900b69fdabb4#rd';
		$title = '我在用e袋洗洗衣服，你也来吧';
		$desc = '我在用e袋洗洗衣服，你也来吧';
		$img_url = assets_link('/framework/style/images/share-icon-6.jpg');
		$callback_url = create_url('icard/recommend_callback');
		
		// 获取推荐记录
		$rcmd_records = $this->sw_server->get_recommend_res();
		if(!empty($rcmd_records)){
			foreach ($rcmd_records as $key => $value) {
				if(is_string($value['tel'])){
					$rcmd_records[$key]['tel'] = substr($value['tel'],0,3).'****'.substr($value['tel'],-4,4);
				}else{
					$first = (int)($value['tel']/10000000);
					$end = (int)(($value['tel']%10000000)/10000);	
					$rcmd_records[$key]['tel'] = $first.'****'.$end;
				}
			}	
		}	
		// 获取推荐链接信息
		$rcmd_urlifno = $this->sw_server->get_recommend_url();
		if($rcmd_urlifno['url']){
			//$rcmd_url = $rcmd_urlifno['url'] . '&from_open_id=' . $from_user . '&id=' . $user_id;
			//此处直接写死，预约取件的弹框链接（在swoole写死得了）
			$rcmd_url = $rcmd_urlifno['url'];
			//$rcmd_url = 'http://hongbao.rongchain.com/mobile.php?uuid=eab1625c98236b9c31423eb9c567abb5&mark=150422731859a8aff6f08a5';
			$title = $rcmd_urlifno['share_title'];
			$desc = $rcmd_urlifno['share_desc'];
			$img_url =  $rcmd_urlifno['share_img'];
			$active_id = $rcmd_urlifno['id'];
		}
		$tpl_main_file = IA_ROOT . "/data/tpl/icard/icard_invite.tpl.php";
		$tpl_footer_file = IA_ROOT . "/data/tpl/icard/footer.tpl.php";
		// if($rcmd_urlifno['template_is_update'] || !file_exists($compile)){
			$main_html = $this->sw_server->getTemplateById($rcmd_urlifno['template_id'], 'icard_invite');
			template_compile_from_str($main_html['icard_invite'], $tpl_main_file, true);
			$foot_html = $this->sw_server->getTemplateById($rcmd_urlifno['template_id'], 'footer');
			template_compile_from_str($foot_html['footer'], $tpl_footer_file, true);
		// }
		// echo '<pre>';
		// var_dump($main_html);
		// var_dump($foot_html);exit;
		include $tpl_main_file;
		include $tpl_footer_file;
	}
	// 推荐回调
	public function recommend_callback(){
		global $_GPC;
		$data['share_user_id'] = $this->user_info['user_id'];
		$data['share_active_id'] = $_GPC['fad'];
		$data['from_user_id'] = $this->user_info['user_id'];
		$data['from_active_id'] = $_GPC['fad'];
		$data['type'] = 4;
		$data['depth'] = 1;
		isset($_GPC['is_fail']) && $data['is_fail'] = $_GPC['is_fail'];
		$this->sw_server->activeShareRecode($data);
		return;
	}
	
	// 跳转积分商城URL
	public function redirect_points_mall(){
		$user_id = $this->user_info['user_id'];
		$mall_url = $this->open_server->get_mall_url($user_id);
		$points_mall_url = $mall_url['url'] ? $mall_url['url'] : create_url('icard/my_icard');
		header('Location: ' . $points_mall_url);
		exit;
	}

	// 重定向到余额商城URL
	public function redirect_balance_mall(){
		global $_GPC;
		if(empty($this->user_info['is_login'])){
			//用户未登录状态
			$balance_mall_url = create_url('icard/login_back', array('loginback_url' => create_url('icard/balance')));
		} else {
			//用户id
			$user_id = $this->user_info['user_id'];
			//用户类型， 0:微信, 1:IOS, 2:android
			$type = 0;
			//获取用户余额
			$icard = $this->open_server->get_icard($user_id);
			$balance = isset($icard['coin']) ? $icard['coin'] : '';
			$mall_url = $this->sw_server->get_balance_mall_url($user_id, $type, get_mark(), $balance);
			$balance_mall_url = isset($mall_url['url']) ? $mall_url['url'] : create_url('icard/balance');
		}
		header('Location: ' . $balance_mall_url);
		exit;
	}

}
