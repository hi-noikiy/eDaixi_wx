<?php
defined('IN_IA') or exit('Access Denied');
use Edx\Model\ModelLoader as Model;

class Order extends BaseModule {
	function __construct(){
		global $_W;
		parent::__construct();
		$this->model_name = 'order';
		$this->open_server = new OpenServer($_W['config'],$this->user_info);
		//获取订单实付款详情
		$user_info = $this->user_info;
		$this->order_model = Model::get('order', $user_info);
	}
	public function order_list(){
		global $_W,$_GPC;
		require IA_ROOT.'/framework/library/wxshare/include.php';
		$did = 'order_list';
		$user_id = $this->user_info['user_id'];
		$mark = get_mark();
		// 是否来自小e管家
		$is_from_eservice = is_from_eservice();
		// 邀请好友个人中心url
		$url_icard_invite = create_url('icard/recommend');
		//	获取订单中心推荐好友模板配置url
		$url_order_invite_template = create_url('order/getOrderInviteHtml');
		if(empty($this->user_info['is_login'])){
			$denglu = 'no';
			include $this->template('order_list');
			exit;
		}
		$user_type = $this->user_info['user_type'];
		$order_id = $_GPC['order_id'] ?: '';
		$pindex = 1;
		
		$status = intval($_GPC['status']);
		if ($status == 2) {	# 已完成订单
			$order_type = '1';
			$psize = 10;
		} else { 			# 未完成订单
			$order_type = '0';
			$status = 1;
			$psize = 200;
		}
		$list = $this->open_server->get_order_list($user_id, $order_type, $pindex, $psize);
		// // 洗衣液订单
		// type #0：未完成，1：已完成，2：all
		$detergent_list = $this->open_server->get_physical_order_lists($user_id, $order_type, 1, $psize);
		$lottery = false;
		// 取分享领券信息
		foreach ($list as $key => $value) {
			if($value['order_can_share'] && $this->user_info['user_type'] == 1){
				$order_can_share =1;
				$share_coupon_total = $value['order_can_share'];
				$share_url = $value['share_url'];
				$share_img = $value['share_image_url']; 
				$share_desc = $value['share_content'];
				$share_title = $value['share_title'];
				$share_order_id = $value['order_id'];

				//此处增加0彩宝活动，获取彩票
				//此处做限制，在24日的晚上23:59:59的时候，停止发彩票
				if(time() < strtotime('2017-01-25')){
					$lottery = $this->start_lingcb_activity($share_order_id);
				}
				break;
			}
		}
		// 归纳物流状态
		$arr['0'] = array('-1','-2');			# 异常
		$arr['1'] = array('11','0');			# 1/4
		$arr['2'] = array('9','-11000','-1');	# 2/4
		$arr['3'] = array('8','1','4','5','6');	# 3/4
		$arr['4'] = array('2','7','15','-2');	# 4/4
		$arr['5'] = array('3','-11000');		# 完成
		$arr['6'] = array('10','-11000');  		# 取消
		$orders_count = count($list);
		for ($j=0; $j<$orders_count; $j++) {
			$qujian = explode(' ', $list[$j]['yuyue_qujian_time']);
			$list[$j]['qj_date'] = trim($qujian[0]);
			$list[$j]['qj_time'] = trim($qujian[1]);
			$list[$j]['order_sn_end'] = substr($list[$j]['order_sn'], -6, 6);
			$list[$j]['order_sn_first'] = substr($list[$j]['order_sn'], 0, count($list[$j]['order_sn']) - 7);
			$list[$j]['yingfu'] = number_format($list[$j]['yingfu'], 2, '.', '');
			for($i=1; $i<6; $i++){
				if(in_array($list[$j]['delivery_status'], $arr[$i])){
					$list[$j]['status'] = $list[$j]['delivery_status'];
					$list[$j]['delivery_status'] = $i;
					if($list[$j]['status'] == 9 && $list[$j]['can_be_paid']){
						$list_can_pay[] = $list[$j];
					}else{
						$list_not_can_pay[] = $list[$j];
					}
					break;
				}  
			}
			$user_order_key = md5($user_id . '_@_orders');
			redis()->sadd($user_order_key, $list[$j]['order_id']);
		}
		// 已完成订单每5条分页
		if(count($list) >= $psize && $status == 2){
			$show_more = 1; 
		}else{  
		// 未完成订单暂不用做分页（展示前200条）
			$show_more = 0;
		}
		
		// 对订单列表排序，可支付订单靠前显示
		$list = array();
		foreach ($list_not_can_pay as $key => $value) {
			$list_can_pay[] =  $value;
		}
		$list = $list_can_pay;
		if($status == 1 && !$list){
			// 引导用户下单的优惠券
			$coupon_list = $this->place_order_coupons(); # 后端过滤
		}
		
		// 时间控件返回地址
		$back_url = urlencode(create_url('order/order_list', array(
				// 'order_id'	=>	$order_id,
				'status'	=>	$status,
				'city_id'	=>	$_GPC['city_id'],
				'mark'	=>	$mark,
			)));

		if($status == 2){  # 已完成订单
			include $this->template('order_list_finished');
		}else{ # 未完成订单
			include $this->template('order_list');
		}
	}
/**
 * 获取推荐好友html
 * 
 * @return json 
 */
	public function getOrderInviteHtml()
	{
		require_once IA_ROOT . '/framework/library/edaixi/SwServer.class.php';
		$this->sw_server = new SwServer($this->user_info['user_id']);
		$source = $this->sw_server->getOrderInviteHtml();
		if(empty($source)){
			$result['state'] = 0;
			$result['msg'] = '失败';
		}else{
			$result['state'] = 1;
			$result['msg'] = $source;
		}
		message($result, '', 'ajax');
	}
	
	private function build_sorter($key) {
		return function ($a, $b) use ($key) {
			return strnatcmp($a[$key], $b[$key]);
		};
	}
	
	// 引导用户下单的优惠券
	public function place_order_coupons(){
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$user_city = get_user_city();
		$city_id = $user_city['city_id'];
		$city_name = $user_city['city_name'];
		$coupon_list = $this->open_server->get_two_coupons($user_id, $user_type, $city_id);
		$coupon_list = format_coupon($coupon_list);
		return $coupon_list;
	}
	
	// 洗衣液详情
	public function detergent_order_details(){
		global $_W,$_GPC;
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$client_id = $this->user_info['client_id'];
		$order_id = intval($_GPC['order_id']);
		#status 0：确认，1：待发货，2：已发货，3：已收货，-1：已取消
  		#pay_status 0：待支付，1：已支付，2：退款中，3：退款完成，4：退款失败
		$order = $this->open_server->get_physical_order_info($user_id, $order_id);
		// 订单编号 处理
		$order_sn = substr($order['order_sn'], 0, -6) . ' ' .substr($order['order_sn'], -6);
		// 取消按钮
		$cancel_order = in_array($order['status'], array(0,1)) ? true : false; 
		// 支付按钮
		$pay_order = $order['pay_status'] == 0 ? true : false;
		// 确认按钮
		$sure_order = $order['pay_status'] == 1 && $order['status'] == 2 ? true : false;
		// 支付url
		$pay_url = create_url('payment/platform',array('order_id' => $order_id, 'is_xiyiye' => true));
		// 确认url
		$sure_order_url = create_url('order/sure_detergent_order', array('order_id' => $order_id));
		// 取消url
		$cancel_url = create_url('order/cancel_detergent_order', array('order_id' => $order_id));
		include $this->template('detergent_order_details');
	}

	// 确认洗衣液
	public function sure_detergent_order(){
		global $_W,$_GPC;
		$user_id = $this->user_info['user_id'];
		$physical_order_id = intval($_GPC['order_id']);
		$res = $this->open_server->physical_order_confirm_receipt($user_id, $physical_order_id);
		if($res['ret'] !== false){
			$result['state'] = 1;
			$result['msg'] = '确认收货成功';
			message($result, create_url('order/order_list', array('status'=>2)),'ajax');
		}else{
			$result['state'] = 0;
			$result['msg'] = '确认收货失败,请稍后重试';
			message($result,create_url('order/order_list'),'ajax');
		}
	}

	// 订单（衣物）详情
	public function order_details(){
		global $_W,$_GPC;
		$is_login = $this->user_info['is_login'];
		// 是否来自小e管家
		$is_from_eservice = is_from_eservice();
		
		if(isset($_GPC['from']) &&
		   $_GPC['from'] == 'singlemessage'){
			$url = HTTP_TYPE.$_SERVER['HTTP_HOST'].'/mobile.php?m=wap&act=homepage&do=index';
            header('Location: '.$url);
            exit;
		}
		if(! $is_login){
			$title = '订单详情';
			$did = 'order_list';	# 页脚导航--点亮“订单”选项
			include $this->template('not_login');
			exit;
		}
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$client_id = $this->user_info['client_id'];
		$order_id = strval($_GPC['order_id']);
		// 判断是否来自微信机器人的投诉入口页(此页进入的订单详情，按钮应只有投诉)
		$is_robot = isset($_GPC['robot']) ? $_GPC['robot'] : 0;
		// 订单详情
		$order_tmp = $this->open_server->get_order($user_id, $order_id);
		$order = $this->format_order($order_tmp);

		// 最新物流信息
		$delivery_tmp = $this->open_server->order_delivery_status_list($user_id, $order_id);
		$delivery_list = $this->format_delivery($delivery_tmp);
		
		// 衣物分拣信息
		$clothing = $this->open_server->order_clothing($user_id, $order_id);
		$clothing = $this->formate_clothing($order_id, $clothing);
		$count = count($clothing);
		$more_count = max($count - 4, 0);
		if(in_array($order['delivery_status'], array(0, 11)) || $order['delivery_status'] == 9 && (!$order['can_be_paid'] || !$order['order_price'])){
			$show_clothing_info = false;
		}else{
			$show_clothing_info = true;
		}
		if($order['pay_status'] != 0){
			$show_clothing_info = true;
		}
		if(in_array($order['category_id'], array(60, 61))){
			$show_clothing_info = false;
		}
		// 支付信息
		if($order['pay_status'] == 0 || $order['is_fanxidan']){
			$show_pay_info = false;
		}else{
			$show_pay_info = true;
			//获取订单实付款详情
			$order_model = $this->order_model;
			$pay_detail = $order_model->payInfoDetail($user_id, $order_id);
			if (!empty($pay_detail)) {
				$pay_cloth_detail = $order_model->
			                    payClothInfoDetail($user_id, $order_id);
			} else {
				//对于合并支付上线之前的订单, 暂取不到支付信息, 先不显示
				$show_pay_info = false;
			}
		}
		
		// 增保信息
		$insurance_info = $order['insurance_info'];

		// 支付按钮
		$show_pay = $order['can_be_paid'] && (9 == $order['delivery_status']) && (! $order['is_fanxidan']);
		//继续支付状态
		if ($order['pay_in_process']) {
			$pay_url = create_url('payment/platform',
				array(
					'order_id'=>$order['order_id']
					));
		} else {
			$pay_url = create_url('payment/pay_list',
				array(
					'order_id'=>$order['order_id'],
					'order_city' => $order['city_id']
				));
		}
		// 催单按钮，此处增加一个category_id，用于区别奢侈品与普通的催单电话
		$reminder = $this->format_reminder($order['delivery_status'], $order['can_be_paid'], $order['courier_phone_qu'], $order['courier_phone_song'], $order['category_id']);
		// 取消订单按钮
		$can_cancel = $order['can_be_canceled'];
		// 取消原因
		$cancel_order_reason = $this->open_server->order_cancel_reasons();
		
		// 评价按钮
		$show_comment = (3 == $order['delivery_status']);
		if($order['already_commented']){
			$show_pay = false;
			$can_cancel = false;
			$reminder = false;
			$show_comment = false;
		}else{
			if(in_array($order['category_id'], array('60', '61'))){
				$comment_url = create_url('order/tailor_comment',array('order_id'=>$order['order_id']));
			}else{
				$comment_url = create_url('order/order_comment',array('order_id'=>$order['order_id'],'comment'=>'appraise'));
			}
		}
		// 分享订单
		if($order['order_can_share'] && $this->user_info['user_type'] == 1){
			$order_share = true;
			$this->getWxShareJs();
			$this->active_type = 3;
			$this->active_id = $order['active_id'];
			$share_data = $this->shareRecodeData();
			$share_data['url'] = $this->getShareUrl($order['share_url']);
			$share_data['title'] = $order['share_title'];
			$share_data['desc'] = $order['share_content'];
			$share_data['img'] = $order['share_image_url'];
		}
		// 投诉按钮
		$complain_status = '投诉';
		$show_complain = true;
		$data = $this->open_server->get_order_complain($order_id);
		# 投诉状态：-2:已超时 －1:未投诉 0:等待处理 1:处理中 2:处理完毕
		if(isset($data['status'])){
			if($data['status'] == -2){
				$complain_status = '';
				$show_complain = false;
			}else if($data['status'] == -1 || $data['status'] == 2){
				$complain_status = '投诉';
			}elseif($data['status'] == 0 || $data['status'] == 1){
				$complain_status = '处理中';
			}
		}
		if($is_from_eservice){
			$cancel_order_next = $url = $_W['config']['xiaoe']['url'].'/order?order_id='.$order_id;
		}else{
			$cancel_order_next = create_url('order/order_list');
		}
		
		//订单取消支付url
		$cancel_pay_url = create_url('payment/cancel_pay');
		
		// 时间控件返回url
		$back_url = urlencode(create_url('order/order_details', array(
				'order_id'	=>	$order_id,
				'city_id'	=>	$_GPC['city_id'],
				'mark'	=>	$_GPC['mark'],
				'robot'	=>	$robot,
			)));
		include $this->template('order_details');
	}

	// 格式化订单详情
	public function format_order($order){
		if(in_array($order['category_id'], array('60', '61'))){
			$progress_bar = array(
				'0' => assets_link('/framework/style/images/gaiyifu_detail_01.png'),
				'1' => assets_link('/framework/style/images/gaiyifu_detail_02.png'),
				'2' => assets_link('/framework/style/images/gaiyifu_detail_03.png'),
				'3' => assets_link('/framework/style/images/gaiyifu_detail_04.png'),
				'4' => assets_link('/framework/style/images/gaiyifu_detail_05.png')
			);
		}else{
			$progress_bar = array(
				'0' => assets_link('/framework/style/images/taking_e.png'),
				'1' => assets_link('/framework/style/images/go_shop.png')
			);
			if($order['cannot_wash']){ // 订单无法完成
				$progress_bar += array(
					'2' => assets_link('/framework/style/images/zhengdan_03.png')
				);
				if(2 === $order['is_self_pickup']){ // 自提订单
					$progress_bar += array(
						'3' => assets_link('/framework/style/images/zhengdan_04.png'),
						'4' => assets_link('/framework/style/images/zhengdan_05.png')
					);
				}else{ // 送件订单(设计未出图)
					$progress_bar += array(
						'3' => assets_link('/framework/style/images/zhengdan_04.png'),
						'4' => assets_link('/framework/style/images/zhengdan_05.png')
					);
				}
			}else{ // 订单正常完成
				$progress_bar += array(
					'2' => assets_link('/framework/style/images/clothes_cleaning.png')
				);
				if(2 === $order['is_self_pickup']){ // 自提订单
					$progress_bar += array(
						'3' => assets_link('/framework/style/images/dengdaiziti.png'),
						'4' => assets_link('/framework/style/images/zitisonghui.png')
					);
				}else{ // 送件订单
					$progress_bar += array(
						'3' => assets_link('/framework/style/images/go_back.png'),
						'4' => assets_link('/framework/style/images/receipt.png')
					);
				}
			}
		}
		$order['back_type'] = intval($order['back_type']); // 1-送件上门  2-用户自取
		$order['already_commented'] = $order['can_be_commented'];
		$order['order_sn'] = str_replace(substr($order['order_sn'], -6), ' ' . substr($order['order_sn'], -6), $order['order_sn']);
		$order['yuandingdan_sn'] = str_replace(substr($order['yuandingdan_sn'], -6), ' ' . substr($order['yuandingdan_sn'], -6), $order['yuandingdan_sn']);
		$order['pickup_time'] = $order['washing_date'] . ' '. $order['washing_time'];
		$order['take_soon'] = $order['tags'] ? '（' . $order['tags'] . '）' : '';
		$order['progress'] = $progress_bar[$order['delivery_status_group']['status']];
		if(bccomp($order['coupon_paid'], $order['order_price'], 2) >= 0){
			unset($order['pay_type']);
		}
		$order['without_carriage_price'] = number_format(($order['order_price'] - $order['delivery_fee']), 2, '.', '');
		$order['order_price'] = number_format($order['order_price'], 2, '.', '');
		$order['yingfu'] = bcsub($order['yingfu'], $order['discount_money'], 2);
		$order['delivery_fee'] = number_format($order['delivery_fee'], 2, '.', '');
		$order['coupon_paid'] = number_format($order['coupon_paid'], 2, '.', '');
		$order['discount_money'] = number_format($order['discount_money'], 2, '.', '');
		$qujian = explode(' ', $order['yuyue_qujian_time']);
		$order['qj_date'] = trim($qujian[0]);
		$order['qj_time'] = trim($qujian[1]);
		return $order;
	}
	
	// 格式化衣物信息
	public function formate_clothing($order_id, $clothing){
		if(! $clothing){
			return $clothing;
		}
		foreach ($clothing as $clothes_index => $clothes){
			if(! $clothes['xiaci_images']){
				continue;
			}
			// 查看衣物瑕疵图片url
			$clothing[$clothes_index]['blemish_photos_url'] = create_url('order/blemish_photos', array(
					'order_id' => $order_id,
					'clothes_index' => $clothes_index,
			));
		}
		return $clothing;
	}
	
	// 格式化催单状态
	public function format_reminder($delivery_status, $can_be_paid, $courier_phone_qu, $courier_phone_song, $category_id){
		$reminder = '';
		if (in_array($delivery_status, array('9')) && !$can_be_paid){
			//$reminder = '<a id="order_link" class=" order_link order_blue" href="tel:' . $courier_phone_qu . '">催单</a>';
		}else if(in_array($delivery_status, array('1', '4', '5', '6', '15'))){
			//奢侈品专用400电话
			if(in_array($category_id, array('4', '5'))){
				$reminder = '<a class="order_link order_blue" id="order_link" href="tel:400-852-7178">催单</a>';
			}else{
				$reminder = '<a class="order_link order_blue" id="order_link" href="tel:400-818-7171">催单</a>';
			}
		}else if (in_array($delivery_status, array('2', '-2'))){
			$reminder = '<a class="order_link order_blue" id="order_link" href="tel:' . $courier_phone_song . '">催单</a>';
		}
		return $reminder;
	}
	
	// 物流详情
	public function delivery_details(){
		global $_W,$_GPC;
		$is_login = $this->user_info['is_login'];
		if(! $is_login){
			$title = '物流详情';
			$did = 'order_list';	# 页脚导航--点亮“订单”选项
			include $this->template('not_login');
			exit;
		}
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$client_id = $this->user_info['client_id'];
		$order_id = $_GPC['order_id'];
		
		// 订单详情
		$order = $this->open_server->get_order($user_id, $order_id);
		
		// 物流详情
		$delivery_tmp = $this->open_server->order_delivery_status_list($user_id, $order_id);
		$delivery_list = $this->format_delivery($delivery_tmp);
		include $this->template('delivery_details');
	}

	// 格式化物流详情数据
	public function format_delivery($delivery_tmp){
		$count = count($delivery_tmp['delivery_status_list']);
		$pattern = '/1[0-9\s]{10,14}/';
		$match = '';
		for ($i=0; $i <$count; $i++) {
			$str = $delivery_tmp['delivery_status_list'][$i]['text'];
			preg_match($pattern, $str, $match);
			if($match){
				$count_mathch = strlen($match[0]);
				$temp_match = '';
				for($j=0; $j<$count_mathch; $j++){
					if($match[0][$j] == '') 
						continue;
					$temp_match .= $match[0][$j];
				}
				$new_match = intval($temp_match);
				$new_match = '<a href="tel:'.$new_match.'"><em style="color:#00dbf5">'.$new_match.'</em></a>';
				$delivery_tmp['delivery_status_list'][$i]['text'] = str_replace($match[0], $new_match, $delivery_tmp['delivery_status_list'][$i]['text']);
			}
		}
		return $delivery_tmp['delivery_status_list'];
	}

	public function cloth_details(){
		global $_GPC;
		$order_id = $_GPC['order_id'];
		$url = HTTP_TYPE.$_SERVER['HTTP_HOST'].'/mobile.php?m=wap&act=order&do=order_details&order_id='.$order_id;
        header('Location: '.$url);  
        exit;
	}
	
	public function add_order(){
		global $_W,$_GPC;
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$client_id = $this->user_info['client_id'];

		$order_id = $_GPC['order_id'];
		$pindex = intval($_GPC['pindex']);
		$psize = 5;
		$order_type = "1";
		
		$list = $this->open_server->get_order_list($user_id, $order_type, $pindex, $psize);
		$count = count($list);
		
		$str = '';
		$user_order_key = md5($user_id.'_@_orders');
		for ($i=0; $i<$psize && $i<$count; $i++){ 
			redis()->sadd($user_order_key, $list[$i]['order_id']);
			
			$list[$i]['order_sn_end'] = substr($list[$i]['order_sn'], -6, 6);
			$list[$i]['order_sn_first'] = substr($list[$i]['order_sn'], 0, count($list[$i]['order_sn']) - 7);
			
			$str .= '<li>
			  <a href="' . create_url('order/order_details',array('order_id' => $list[$i]['good']['order_id'])) .'" >
			    <div class="category_block">';
					if($list[$i]['category_id'] == 2){
						$str .=  '<div class="category_cloth">
						    <img src="' . assets_link('/framework/style/images/xi_shoes.png') . '">' . $list[$i]['good'] . 
						'</div>';
					}else if($list[$i]['category_id'] == 1){
						$str .=  '<div class="category_shoes">
						    <img src="' . assets_link('/framework/style/images/xi_cloth.png') . '">' . $list[$i]['good'] . 
						'</div>';
					}else if($list[$i]['category_id'] == 3){
						$str .=  '<div class="category_chuanglian">
						    <img src="' . assets_link('/framework/style/images/chuanglian.png') . '">' . $list[$i]['good'] . 
						'</div>';
					}else if($list[$i]['category_id'] == 4){
						$str .=  '<div class="category_shechipin">
						    <img src="' . assets_link('/framework/style/images/shechipin.png') . '">' . $list[$i]['good'] . 
						'</div>';
					}else if($list[$i]['category_id'] == 5){
						$str .=  '<div class="category_piyi">
						    <img src="' . assets_link('/framework/style/images/piyi.png') . '">' . $list[$i]['good'] . 
						'</div>';
					}else if($list[$i]['category_id'] == 6){
						$str .=  '<div class="category_shoes">
						    <img src="' . assets_link('/framework/style/images/xi_cloth.png') . '">' . $list[$i]['good'] . 
						'</div>';
					}else if($list[$i]['category_id'] == 13){
						$str .=  '<div class="category_kuaixi">
						    <img src="' . assets_link('/framework/style/images/kuaixi_c.png') . '">' . $list[$i]['good'] . 
						'</div>';
					}else if(in_array($list[$i]['category_id'], array('60', '61'))){
						$str .=  '<div class="category_tailor">
						    <img src="' . assets_link('/framework/style/images/caiyi.png') . '">' . $list[$i]['good'] .
						'</div>';
					}
				$str .='</div>  
		    </a>
	      </li>';
		  $str .= '<div class="borderD"></div>';
		  $str .= '<li class = "order_item mobile-font"> 
				<div class="item_list_money navlist">
					<a href = "'. create_url('order/order_details',array('usert_type' => $user_type,'order_id' => $list[$i]['order_id'],order_status => $list[$i]['order_status_text'])).'" >
						<div class="order_box">
							<div class="item_list_box">订单编号：'.$list[$i]['order_sn_first'].'   '.$list[$i]['order_sn_end'].'</div>
							<div class="item_list_box">服务时间：'.$list[$i]['yuyue_qujian_time'].'</div>
						</div>
					</a>
					<div class="item_list_box shifukuan_box">   
		                <div class="borderD"></div>';
	                    if ($list[$i]['is_fanxidan']){
	   						$str .= '<div class="check-original-order">
	                 			<a class="original-order-link" href="' . create_url('order/order_details',array(
	                 					'order_id' => $list[$i]['yuandingdan_id']
	                 			)) . '><font>查看原始订单</font></a>
	                  		</div>';
	                    }else{
								$str .= '<div class="orderList">实付款：' . number_format($list[$i]['yingfu'], 2, '.', '') . ' 元</div>';
	                    }
			            $str .= '<div class="item_list_btn">';
			                if($list[$i]['can_be_commented'] == 1){
			                	$str .= '<a href="' . create_url('order/order_comment',array(
			                			'order_sn' => $list[$i]['order_sn'],
			                			'order_id' => $list[$i]['order_id'],
			                			'comment' => 'show'
			                	)) . '" class="order_link comment-btn-over" >已评价</a>';
			                }
			             $str .= '</div>
		            </div>
				</div>
			    <div style="clear:both"></div>
		        <div class="borderD2"></div>
		        <div class="clearBoth"></div>
			</li>';
		}
		if($count){
			$result['state'] = 1;
			$result['add_order'] = $str;
			message($result,'','ajax');
		}else{
			$result['state'] = 0;
			$result['msg'] = '无更多订单';
			message($result,'','ajax');
		}
	}
	// 取消洗衣液订单
	public 	function cancel_detergent_order(){
		global $_W,$_GPC;
		$user_id = $this->user_info['user_id'];
		$detergent_order_id = intval($_GPC['order_id']);
		$res = $this->open_server->cancel_physical_order($user_id, $detergent_order_id);
		if ($res) {
			$result['state'] = 1;
			$result['msg'] = '订单取消成功！';
			message($result, create_url('order/order_list'),'ajax');
		}else{
			$result['state'] = 2;
			$result['msg'] = $res['error'] ? $res['error'] : '订单取消失败';
			message($result, create_url('order/order_list'),'ajax');
		}
	}

	public 	function cancel_order(){
		global $_W,$_GPC;

		$user_id = $this->user_info['user_id'];
		$order_id = intval($_GPC['order_id']);
		$reason = $_GPC['reason'];
		$order_type = '0';
		$mark = get_mark();

		// 获取用户首页城市
		$user_city = get_user_city();
		$city_id = $user_city['city_id'];
		$city_name = $user_city['city_name'];
		$res = $this->open_server->cancel_order($user_id, $order_id,$reason,$city_id,$mark);
		if ($res['ret']) {
			$result['state'] = 1;
			$result['msg'] = '订单取消成功！';
			redis()->srem(md5($user_id.'_@_orders'),$order_id);
			message($result,'','ajax');

		}else{
			$result['state'] = 2;
			$result['msg'] = $res['error'] ? $res['error'] : '订单取消失败';
			message($result,'','ajax');
		}
	}

	// 订单评价
	public function order_comment(){
		global $_W,$_GPC;
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$client_id = $this->user_info['client_id'];
		$order_id = $_GPC['order_id'];
		$comment = $_GPC['comment'];
		// 是否来自小e管家
		$is_from_eservice = is_from_eservice();
		if(empty($order_id)){
			$url = HTTP_TYPE.$_SERVER['HTTP_HOST'].'/'.create_url('order/order_list');
			header('Location: '.$url);
			exit;
		}
		//获取comments_text_list
		$comment_text_lists=$this->get_comment_text_list();
		/*
		foreach($comment_text_lists['option'] as $k=>$v){
			$comment_text_list[$v['id']]=$v['text'];
		}*/
		//仅仅将v5返回的数据格式调整为v1样式；如果连label都需要后台配置，那么前端html需要重写
		foreach ($comment_text_lists['subs'] as $k => $v) {
			foreach ($v['text'] as $key => $val) {
				$comment_text_list[$val['id']]=$val['text'];
			}
		}
		//获取顶层评价列表(如有点差劲、超赞等)
		foreach($comment_text_lists['level'] as $k=>$v){
			$top[$v['level']]=$v['text'];
		}

		if($comment == 'insert'){
			$general_comment = $_GPC['commentall'];
			$washing_quality = $_GPC['washing_quality'];
			$delivery_speed = $_GPC['delivery_speed'];
			$sever_attitude = $_GPC['sever_attitude'];
			$mark = get_mark();

			// 获取用户首页城市
			$user_city = get_user_city();
			$city_id = $user_city['city_id'];
			$city_name = $user_city['city_name'];
			
			if($general_comment>3){
				$comment_tmp = 'comment_0';
			}else{
				$comment_tmp = 'comment_1';
			}
			$appraise_detail = $_GPC['appraise_detail'];
			//取件人员comment
			$comment_fetch_option='';
			for ($i=1;$i<=4;$i++){
				$tmp='comment_'.$i;
				if($_GPC[$tmp]!=''){
					$comment_fetch_option .= "{$i},";
				}
			}
			//送件人员comment
			$comment_carry_option='';
			for ($i=5;$i<=8;$i++){
				$tmp='comment_'.$i;
				if($_GPC[$tmp]!=''){
					$comment_carry_option .= "{$i},";
				}
			}
			//上传图片
			$imgs = $_GPC['images'] ?: array();
			$imgs_count = count($imgs);
			if($imgs_count > 10){
				error_report('上传图片不能超过10张哦~');
			}
			$imgs = json_encode($imgs);
			//上传图片的宽高数组
			$imgs_wh = $_GPC['wh'] ?: array();
			if(count($imgs_wh) != $imgs_count){
				error_report('图片参数匹配异常~');
			}
			$imgs_wh = json_encode($imgs_wh);
			//调用open_server把评价写入
			$res = $this->open_server->create_order_comment(
					$user_id,
					$order_id,
					$general_comment,
					$washing_quality,
					$delivery_speed,
					$sever_attitude,
					$appraise_detail,
					$comment_fetch_option,
					$comment_carry_option,
					$city_id,
					$mark,
					$imgs,
					$imgs_wh
			);
			if($is_from_eservice){
				$order = $this->open_server->get_order($user_id, $order_id);
				require_once IA_ROOT . '/framework/library/xiaoe/xiaoe.class.php';
				$xiaoe = new xiaoe($_W['config']);
				$comment = $xiaoe->order_comment(
					array(
					'from_user' => $this->user_info['from_user'],
					'order_id' => $order_id,
					'order_sn' => $order['order_sn'],
					));
				$url = $_W['config']['xiaoe']['url'].'/order';
				header('Location: ' . $url);
				exit;
			}else{
				$url = create_url("order/order_list",array('status' =>'1' ));
			}
			if(!$res['ret']){
			 	error_report($res['error'] ? $res['error'] : '系统繁忙，请稍后再试', $url);
			}
			$img_0 = assets_link('/framework/style/images/star_hover.png');
			$img_0_0 = assets_link('/framework/style/images/icon_star.png');
			for ($i=0; $i <$general_comment; $i++) { 
				$general_comment_src[$i] = $img_0;
			}
			for ( ;$i <5 ; $i++) { 
				$general_comment_src[$i] = $img_0_0;
			}
			$order_info = $res['data'];
			$ordersn = $order_info['ordersn'];
			$money = $order_info['money'];
			
			//订单评价积分
			require_once IA_ROOT . '/framework/library/edaixi/SwServer.class.php';
			$points = new SwServer($user_id);
			$resp = $points->comment_points();
			if(is_numeric($resp['code']) && $resp['code'] == 0 && (get_mark() == 'eservice' || $this->user_info['user_type'] != 18)){
				$animation = true;
			}else{
				$animation = false;
			}
			include $this->template('order_comment_success');
			exit;
		}else if($comment == 'show'){
			/*
			$order_list = $this->open_server->get_order_list($user_id,1,1,1000);
			$count = count($order_list);
			//获取当前订单详情（包含评价详情）
			for($i =0; $i<$count; $i++){
				if($order_list[$i]['order_id'] == $order_id){
					$order = $order_list[$i];
					break;
				}
			}*/
			//此处优化,去除老旧接口get_order_list($user_id,1,1,1000)中遍历查找指定订单的代码
			$order = $this->open_server->get_order($user_id,$order_id);
			//整体评价
			$total_score_text = $top[$order['total_score']];
			//洗衣质量
			$washing_score_text = $top[$order['washing_score']];
			//取件人员
			$logistics_score_text = $top[$order['logistics_score']];
			//送件人员
			$service_score_text = $top[$order['service_score']];
			$com[1] = $order['washing_score'];
			$com[2] = $order['logistics_score'];
			$com[3] = $order['service_score'];
			$img_0 = assets_link('/framework/style/images/star_hover.png');
			$img_0_0 = assets_link('/framework/style/images/icon_star.png');
			$com_total = $order['total_score'];
			for ($i=0; $i <$com_total; $i++) { 
				$comment_src[0][$i] = $img_0;
			}
			for ( ;$i <5 ; $i++) { 
				$comment_src[0][$i] = $img_0_0;
			}
			$img_1 = assets_link("/framework/style/images/face_grey.png");
			$img_1_1 = assets_link("/framework/style/images/sad_hover.png");
			$img_2 = assets_link("/framework/style/images/face_grey.png");
			$img_2_2 = assets_link("/framework/style/images/unhappy_hover.png");
			$img_3 = assets_link('/framework/style/images/face_grey.png');
			$img_3_3 = assets_link("/framework/style/images/smile_hover.png");
			$img_4 = assets_link("/framework/style/images/face_grey.png");
			$img_4_4 = assets_link("/framework/style/images/happy_hover.png");
			$img_5 = assets_link("/framework/style/images/face_grey.png");
			$img_5_5 = assets_link("/framework/style/images/laugh_hover.png");
			foreach ($com as $key => $value) {
				switch ($value) {
					case '1':
						$comment_src[$key][0] = $img_1_1;
						$comment_src[$key][] = $img_2;
						$comment_src[$key][] = $img_3;
						$comment_src[$key][] = $img_4;
						$comment_src[$key][] = $img_5;
						break;
					case '2':
						$comment_src[$key][0] = $img_2_2;
						$comment_src[$key][] = $img_2_2;
						$comment_src[$key][] = $img_3;
						$comment_src[$key][] = $img_4;
						$comment_src[$key][] = $img_5;
						break;
					case '3':
						$comment_src[$key][] = $img_3_3;
						$comment_src[$key][] = $img_3_3;
						$comment_src[$key][] = $img_3_3;
						$comment_src[$key][] = $img_4;
						$comment_src[$key][] = $img_5;
						break;
					case '4':
						$comment_src[$key][0] = $img_4_4;
						$comment_src[$key][] = $img_4_4;
						$comment_src[$key][] = $img_4_4;
						$comment_src[$key][] = $img_4_4;
						$comment_src[$key][] = $img_5;
						break;
					case '5':
						$comment_src[$key][0] = $img_5_5;
						$comment_src[$key][] = $img_5_5;
						$comment_src[$key][] = $img_5_5;
						$comment_src[$key][] = $img_5_5;
						$comment_src[$key][] = $img_5_5;
						break;
				}
			}
			$order_comments=$order['order_comments'];
			$comment_imgs = isset($order['order_comment_images']) ? $order['order_comment_images'] : json_encode(array());
			$comment_imgs = json_decode($comment_imgs,true);
			$imgs_wh = isset($order['images_sizes']) ? $order['images_sizes'] : json_encode(array());
			$imgs_wh = json_decode($imgs_wh,true);
			$comment_fetch_option=array_flip(explode(',',$order['order_comments_fetch_option']));
			$comment_carry_option=array_flip(explode(',',$order['order_comments_carry_option']));
		}else{
			//获取ci token, 用于评价时上传图片
			$ci_token = $this->open_server->get_ci_token('order_comment');
			if(empty($ci_token)){
				error_report("无法获取上传token");
			}
			$upload_url = HTTP_TYPE.'web.image.myqcloud.com/photos/v2/'.$ci_token['appid'].'/'.$ci_token['bucket'].'/0?sign='.urlencode($ci_token['auth_token']);
			$order = $this->open_server->get_order($user_id,$order_id);
			if(empty($order) || $order['can_be_commented'] == 1 || empty($comment)){
				$url = HTTP_TYPE.$_SERVER['HTTP_HOST'].'/mobile.php?m=wap&act=homepage&do=index';
				header('Location: '.$url);
				exit;
			}
		}
		include $this->template('order_comment');
	}
	
	// 获取评价标签方法
	public function get_comment_text_list(){
		$comment_text_list = mcache()->get(md5('comment_text_list'));
		$comment_text_list = unserialize($comment_text_list);
		if(empty($comment_text_list)){
			$comment_text_list = $this->open_server->comment_text_list_new();
			mcache()->set(md5('comment_text_list'), serialize($comment_text_list), 7200);
		}
		return $comment_text_list;
	}
	
	public function share_order(){
		global $_GPC;
		$share_order_id = $_GPC['share_order_id'];
		if(empty($share_order_id)){
			exit;	
		}
		$this->open_server->order_envelope_is_share($share_order_id);
		exit;
	}

	//微信机器人投诉页面入口
	public function wx_robot_complain(){
		$user_id = $this->user_info['user_id'];
		//投诉地址前缀
		$com_url = create_url('order/enter_complain');
		//订单详情地址前缀
		$detail_url = create_url('order/order_details');
		$complain_orders = $this->open_server->get_user_complain_orders($user_id);
		foreach ($complain_orders as $k => $order) {
			$complain_orders[$k]['order_sn_first'] = substr($order['ordersn'],0,-6);
			$complain_orders[$k]['order_sn_end'] = substr($order['ordersn'],-6,6);
			switch($order['category_id']){
				case 1:
					$complain_orders[$k]['icon'] = assets_link('/framework/style/images/xi_cloth.png');
					break;
				case 2:
					$complain_orders[$k]['icon'] = assets_link('/framework/style/images/xi_shoes.png');
					break;
				case 3:
					$complain_orders[$k]['icon'] = assets_link('/framework/style/images/chuanglian.png');
					break;
				case 4:
					$complain_orders[$k]['icon'] = assets_link('/framework/style/images/shechipin.png');
					break;
				case 5:
					$complain_orders[$k]['icon'] = assets_link('/framework/style/images/piyi.png');
					break;
				case 13:
					$complain_orders[$k]['icon'] = assets_link('/framework/style/images/kuaixi_c.png');
					break;
				case in_array($order['category_id'], array(60,61)):
					$complain_orders[$k]['icon'] = assets_link('/framework/style/images/caiyi.png');
					break;
				case 7:
					$complain_orders[$k]['icon'] = assets_link('/framework/style/images/daixi.png');
					break;
				default:
					$complain_orders[$k]['icon'] = assets_link('/framework/style/images/xi_cloth.png');
					break;
			}
		}
		$this->getWxShareJs();
		include $this->template('complain_robot');
	}


	//图片上传页面
	public function upload_complain(){
		global $_W, $_GPC;
		$user_id = $this->user_info['user_id'];
		$order_id = $_GPC['order_id'];
		$cat_id = $_GPC['cat_id'] ?: '';
		$complain_content = $_GPC['question'] ? urldecode($_GPC['question']) : '';
		// 判断是否来自微信机器人的投诉入口页(投诉完毕，返回不一样)
		$is_robot = isset($_GPC['robot']) ? $_GPC['robot'] : 0;
		//获取ci token
		$ci_token = $this->open_server->get_ci_token();
		if(empty($ci_token)){
			error_report("无法获取上传token");
		}
		$upload_url = 'http://web.image.myqcloud.com/photos/v2/'.$ci_token['appid'].'/'.$ci_token['bucket'].'/0?sign='.urlencode($ci_token['auth_token']);
		$this->getWxShareJs();
		include $this->template('complain_upload');
	}

	//ajax提交订单投诉
	public function submit_complain(){
		global $_W, $_GPC;
		$user_id = $this->user_info['user_id'];
		$order_id = $_GPC['order_id'];
		$category_content = $_GPC['content'] ?: '';
		$imgs = $_GPC['images'] ?: array();
		if(count($imgs) > 10){
			$result['state'] = 0;
			$result['msg'] = '投诉时上传图片不能超过10张~';
			message($result, '', "ajax");
		}
		$imgs = json_encode($imgs);
		if(!isset($_GPC['cat_id'])){
			$parent_complain_id = $complain_id = 0;
		}else{
			$complain_ids = explode(',', $_GPC['cat_id']);
			$count = count($complain_ids);
			if(!empty($category_content)){
				//最后一级为输入框
				$parent_complain_id = $complain_ids[$count-1];
				$complain_id = 0;
			}else{
				//最后一级为按钮
				if($count == 1){
					$parent_complain_id = 0;
				}else{
					$parent_complain_id = $complain_ids[$count-2];
				}
				$complain_id = $complain_ids[$count-1];
			}
		}
		$terminal = "微信";
		$entrance = "订单详情";
		$resp = $this->open_server->set_order_complain_category($user_id,$order_id,$terminal,$entrance,$parent_complain_id,$complain_id,$category_content,$imgs);
		if($resp['ret']) {
			$result['state'] = 1;
			$result['url'] = create_url('order/complain_success', array('complain_id'=>$resp['data']['order_complaint_id'],'order_id'=>$order_id));
			message($result, '', "ajax");
		}else {
			$result['state'] = 0;
			$result['msg'] = $resp['error'] ? $resp['error'] : '操作失败，请重试';
			$result['url'] = create_url('order/order_details', array('order_id' => $order_id));
			message($result, '', "ajax");
		}
	}

	//投诉补充细类页面
	public function complain_success(){
		global $_W, $_GPC;
		$user_id = $this->user_info['user_id'];
		$complain_id = $_GPC['complain_id'];
		$order_id = $_GPC['order_id'];
		// 判断是否来自微信机器人的投诉入口页(投诉完毕，返回不一样)
		$is_robot = isset($_GPC['robot']) ? $_GPC['robot'] : 0;
		$this->getWxShareJs();
		include $this->template('complain_supplement');
	}

	//ajax提交投诉细类补充
	public function submit_complain_supplement(){
		global $_W, $_GPC;
		$user_id = $this->user_info['user_id'];
		$complain_id = $_GPC['complain_id'];
		$order_id = $_GPC['order_id'];
		$content = $_GPC['supplement'];
		$resp = $this->open_server->order_complain_supplement($user_id,$order_id,$complain_id,$content);
		if($resp['ret']){
			$result['state'] = 1;
			$result['msg'] = '您的投诉我们已经收到，会尽快处理';
			$result['url'] = create_url('order/order_details', array('order_id' => $order_id));
			message($result, '', "ajax");
		}else{
			$result['state'] = 0;
			$result['msg'] = $resp['error'] ? $resp['error'] : '操作失败，请重试';
			message($result, '', "ajax");
		}
	}

	//进入订单投诉新页面(先做着，再逐渐检测老旧代码的清理问题)
	public function enter_complain(){
		global $_W, $_GPC;
		$user_id = $this->user_info['user_id'];
		$order_id = $_GPC['order_id'];
		$category_path = isset($_GPC['path']) ? $_GPC['path'] : '';
		$category_id = isset($_GPC['cat_id']) ? $_GPC['cat_id'] : '';
		// 判断是否来自微信机器人的投诉入口页(投诉完毕，返回不一样)
		$is_robot = isset($_GPC['robot']) ? $_GPC['robot'] : 0;
		
		$complain_category = $this->open_server->get_order_complain_category($order_id);
		if(!empty($complain_category)){
			$complain_category = $this->format_complain_category($complain_category,$category_path);
			$this->getWxShareJs();
			include $this->template('complain');
		}else{
			error_report('无法获取投诉列表信息！');
			exit;
		}
	}

	//调整每一级的投诉分类列表
	private function format_complain_category($arr,$path=""){
		if($path === ''){
			return $arr;
		}else{
			$final_arr = array();
			$tmp_arr = $arr;
			$path_arr = explode(",", $path);
			foreach ($path_arr as $v) {
				$final_arr = $tmp_arr[$v]['child'];
				$tmp_arr = $final_arr;
			}
			return $final_arr ?: array();
		}
	}

	// ajax 设置订单投诉状态
	public function set_order_complain(){
		global $_W, $_GPC;
		$user_id = $this->user_info['user_id'];
		$order_id = intval($_GPC['order_id']);
		$complaint = $_GPC['complaint'];
		$resp = $this->open_server->set_order_complain($user_id, $order_id, $complaint);
		if($resp['ret']) {
			$result['state'] = 1;
			$result['msg'] = '';
			message($result, '', "ajax");
		}else {
			$result['state'] = 0;
			$result['msg'] = $resp['error'] ? $resp['error'] : '操作失败，请重试';
			$result['url'] = create_url('order/order_details', array('order_id' => $order_id));
			message($result, '', "ajax");
		}
	}
	
	// ajax 获取订单投诉状态
	public function get_order_complain(){
		global $_W, $_GPC;
		$user_id = $this->user_info['user_id'];
		$order_id = intval($_GPC['order_id']);
		$data = $this->open_server->get_order_complain($order_id);
		if(!empty($data)) {
			# 投诉状态：-2:已超时 －1:未投诉 0:等待处理 1:处理中 2:处理完毕
			if($data['status'] == -2){
				$result['complain_status'] = -1; # 投诉过期
			}if($data['status'] == -1 || $data['status'] == 2){
				$result['complain_status'] = 1; # 可以投诉
			}elseif($data['status'] == 0 || $data['status'] == 1){
				$result['complain_status'] = 0; # 正在处理
			}
			$result['state'] = 1;
			$result['msg'] = '';
			message($result, '', "ajax");
		}else {
			$result['state'] = 0;
			$result['msg'] = '操作失败，请重试';
			$result['url'] = create_url('order/order_details', array('order_id' => $order_id));
			message($result, '', "ajax");
		}
	}
	
	// 扫码下单
	public function qrcode_order(){
		global $_W,$_GPC;
		$user_id = $this->user_info['user_id'];
		$mobile = $this->user_info['is_login'];
		$from_user = $this->user_info['from_user'];
		$user_type = $this->user_info['user_type'];
		
		// 扫码下单标识（临时订单ID）
		$qrid = $_GPC['tmp_order'] ?: '';
		if(! $qrid){
			error_report('二维码信息错误');
		}
		// 扫码临时订单信息
		$order_info = $this->open_server->get_qrcode_order_info($user_id, $qrid);
		$back_type = (2 === $order_info['back_type']) ? 2 : 1;	// 1-送件上门  2-用户自取
		if(!$order_info || !in_array($order_info['ret_status'], array(0, 1 ,2))){
			error_report('无法获取订单信息');
			exit;
		}
		// 二维码失效（二维码过期、已生成他人订单）
		if($order_info['ret_status'] == 0){
			// error_report($order_info['message'] ?: '二维码已失效');
			// exit;
		}
		// 二维码已生成本人订单
		if($order_info['ret_status'] == 2 || $order_info['order_id']){
			$order_id = $order_info['order_id']; // 分派到模版
			header('Location: ' . create_url('order/order_list'));
			exit;
		}
		// 有效二维码
		$category_id = $_GPC['category_id'] ?: $order_info['category_id'];
		if($order_info['ret_status'] == 1){
			$sub_id = $order_info['sub_id'];
		}
		$sub_id = $order_info['sub_id'];
		
		// 获取用户首页城市
		$city_info = get_user_city();
		$city_id = $city_info['city_id'];
		$city_name = $city_info['city_name'];

		// 格式化扫码下单信息
		$qrcode_order = $this->format_qrcode_order($order_info, $city_id);

		// 用户信息
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$from_user = $this->user_info['from_user'];
		$mark = get_mark();
		
		// 准备订单地址
		if($category_id == 13){ // 酒店快洗
		    $hotel_name = $qrcode_order['hotel_name'];
		    $hotel_id = $qrcode_order['hotel_id'];
		    // 提交按钮状态
		    $btn_status = 'disabled="disabled"';
		    $btn_style = 'noBook';
		}else{
			if(2 === $back_type){	// 1-送件上门  2-用户自取
				// 填充自提地址
				$address_id = $qrcode_order['dak_id'];
				$dak_info = array(
					'name' => $qrcode_order['name'],
					'tel' => $qrcode_order['tel'],
					'address' => $qrcode_order['address'],
					'dak_id' => $qrcode_order['dak_id']
				);
			}else{
				// 填充下单地址
				$select_address = $_GPC['select_address'] ?: '';
				if($select_address){
					$addr_info = encrypt('DE', $select_address);
				}else{
					$address_id = '';
					$addr_info = $this->open_server->get_usable_address($user_id, $address_id, $category_id, $city_id);
				}
				$back_params = array(
						'address_id' => $addr_info['address_id'] ?: '',
						'category_id' => $category_id,
						'sub_id' => $sub_id,
						'tmp_order' => $qrid,
						'price_read' => 1,
						'mark' => $mark,
					);
				// 下单页-->地址列表页 URL
				$select_addr_url = create_url('address/order_address_list', array(
						'address_id' => $addr_info['address_id'] ?: '',
						'category_id' => $category_id,
						'tmp_order' => $qrid,
						'link_from'	=>	'qrcode_order',
						'back'	=>	urlencode(create_url('order/qrcode_order', $back_params))
				));
				$address_id = $addr_info['address_id'] ?: '';
				$area = $addr_info['area'] ?: '';
			}
			// 提交按钮状态
			$btn_status = $address_id ? '' : 'disabled="disabled"';
			$btn_style = $address_id ? 'canBook' : 'noBook';
		}
		
		// 准备时间信息
		$washing_date = $qrcode_order['washing_date'];
		$washing_time = $qrcode_order['washing_time'];

		//是否可以下单
		$order_enable = empty($qrcode_order['washing_time_pass']) ?
						'0' : '1';
		$qrcode_order_url = create_url('order/do_qrcode_order');
		if($category_id == 13){ // 酒店快洗
			include $this->template('qrcode_hotel_order');
		}else{
			include $this->template('qrcode_order');
		}		
	}
	
	// 提交扫码订单
	public function do_qrcode_order(){
		global $_W,$_GPC;
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$client_id = $this->user_info['client_id'];
		$from_user = $this->user_info['from_user'];
		$mark = get_mark();
		$qrid = $_GPC['qrid'];
		$category_id = $_GPC['category_id'];
		$back_type = intval($_GPC['back_type']);
		$washing_date = $_GPC['washing_date'];
		$washing_time = $_GPC['washing_time'];
		if($category_id == 13){
		    $hotel_id = $_GPC['hotel_id'];
		    $room = $_GPC['room'];
		    $user_name = $_GPC['uname'];
		    $mobile = $_GPC['mobile'];
		    if(empty($hotel_id) || empty($room)|| empty($user_name) || empty($mobile)){
		    	$result['msg'] = '请填写酒店信息、姓名和手机号';
		    	$result['state'] = 0;
		    	message($result,'',"ajax");
		    }
		}else{
			$address_id = $_GPC['address_id'];
	    	if(empty($address_id)){
	    		$result['msg'] = '请选择送件地址';
	    		$result['state'] = 0;
	    		message($result, '', "ajax");
	    	}
		}

		// 获取用户首页城市
		$user_city = get_user_city();
		$city_id = $user_city['city_id'];
		// 异常情况未获取city_id时，默认1（北京），暂不影响下单
		if(empty($city_id) || !is_numeric($city_id) || $city_id < 0){
			$city_id = 1;
		}
		if($category_id == 13){ // 酒店快洗
		    $res = $this->open_server->create_qrhotle_order($user_id, $user_type, $qrid, $category_id, $hotel_id, $room, $mobile, $washing_date, $washing_time, 
		        $user_name, $city_id, $mark);
		}else{
			if (2 === $back_type) {
				//扫码自提订单的驿站id
				$dak_id = $address_id;
			} else {
				$dak_id = '';
			}
		    $res = $this->open_server->create_qrcode_order($user_id, $user_type, $qrid, $category_id, $address_id, $washing_date, $washing_time, $back_type,
		        $city_id, $mark, $dak_id);
		}
		if(!$res['ret']) {
			$result['state'] = 0;
			$result['msg'] = $res['error'] ?: '系统繁忙,请稍后重试';
			message($result, '', "ajax");
		}else {
			$order_id = $res['data'];
			$result['state'] = '1';
			$result['msg'] = '正在跳转...';
			$result['timestamp'] = time();
			$result['order_id'] = $order_id;
			$result['url'] = create_url('payment/platform', array('order_id' => $order_id));
			message($result, '', "ajax");
		}
	}
	

	/**
	 * 从时间控件接口获取第一个可用时间
	 */
	protected function firstOrderTime($category_id = 1, $city_id = 1)
	{
		$first_order_time = array();
		$service_time = $this->open_server->get_service_time($category_id, $city_id);
		if (is_array($service_time)) {
			foreach ($service_time as $time) {
				foreach ($time['service_times'] as $serv_time) {
					if (isset($serv_time['is_available']) &&
						$serv_time['is_available'] &&
						isset($serv_time['is_passed']) &&
						!$serv_time['is_passed']) {
							$first_order_time['washing_date'] = $time['date'];
							$first_order_time['washing_time'] = $serv_time['text'];
							$first_order_time['washing_time_pass'] = $serv_time['text'];
							break;
					}
				}
				if (!empty($first_order_time)) {
						break;
				}
			}
		}
		//没有取得可用的服务时间，不计washing_time_pass
		if (empty($first_order_time)) {
			$first_order_time = array(
					'washing_date' => date('Y-m-d'),
					'washing_time' => date('H:i')
				);
		}
		return $first_order_time;
	}

	// 格式化扫码下单信息
	public function format_qrcode_order($order, $city_id = 1){
		$back_type = intval($order['back_type']);
		if(2 === $back_type){	// 1-送件上门  2-用户自取
			$qrcode_order['name'] = $order['name'];
			$qrcode_order['tel'] = $order['tel'];
			$qrcode_order['address'] = $order['address'];
			$qrcode_order['dak_id'] = empty($order['dak_id']) ?
			                          '': $order['dak_id'];
		}
		$qrcode_order['back_type'] = $back_type;
		$qrcode_order['category_id'] = $order['category_id'];
		$qrcode_order['sub_id'] = intval($order['sub_id']);
		$qrcode_order['category_desc'] = $order['category_name'];
		//获取最近的可取件时间
		$order_time = $this->firstOrderTime($order['category_id'], $city_id);
		$qrcode_order['washing_date'] = $order_time['washing_date'];
		//用于显示
		$qrcode_order['washing_time'] = $order_time['washing_time'];
		//用于传递
		$qrcode_order['washing_time_pass'] = $order_time['washing_time_pass'];
		foreach ($order['amount_list'] as $unit => $amount){
			$amount_desc .= '<em style="color: #f66627">' . $amount . '</em>' . $unit;
		}
		$qrcode_order['amount_desc'] = '共' . $amount_desc;
		$qrcode_order['price_desc'] = '<em style="color: #f66627">' . number_format($order['total_price'], 2, '.', '') . '</em>元';
		$qrcode_order['order_id'] = empty($order['order_id']) ? '' : $order['order_id'];
		if($order['category_id'] == 13){
			$qrcode_order['hotel_id'] = $order['hotel_id'];
			$qrcode_order['hotel_name'] = $order['hotel_title'];
			$qrcode_order['hotel_area'] = $order['hotel_area'];
			$qrcode_order['hotel_addr'] = $order['hotel_city'] . $order['hotel_area'] . $order['hotel_street'] . $order['hotel_address'];
		}
		return $qrcode_order;
	}
	
	// ajax 获取酒店--- 已废弃
	public function ajax_get_hotel(){
	    global $_GPC;
	    $user_id = $this->user_info['user_id'];
	    $user_type = $this->user_info['user_type'];
	    $city_id = $_GPC['city_id'];
	    // 搜索类型:0 定位酒店     1 搜索酒店      2 全部酒店
	    $search_type = $_GPC['search_type']; 
	    $page = $_GPC['page']; // 页码
	    $per_page = $_GPC['per_page']; // 偏移量
	    $keywords = $_GPC['keywords'];
	    $lat = $_GPC['lat'];  // 纬度
	    $lng = $_GPC['lng'];  // 经度
	    $resp = $this->open_server->search_hotel($user_id, $user_type, $city_id, $search_type, $page, $per_page, $keywords, $lat, $lng);
	    if($search_type == 0){ // 定位酒店
	    	$hotels = $resp['locate_hotels'];
	    }else if($search_type == 1){ // 搜索酒店
	    	$hotels = $resp['search_hotels'];
	    }else if($search_type == 2){ // 全部酒店下一页
	    	$hotels = $resp['hotels'];
	    }
	    $hcount = count($hotels);
	    if($hcount){
	    	$result['hcount'] = $hcount;
	    	$result['hotels'] = $hotels;
	    }else{
	    	$result['hcount'] = 0;
	    	$result['hotels'] = null;
	    }
	    message($result, '', 'ajax');
	}
	
	// 酒店列表 -- 下一页--- 已废弃
	public function ajax_next_hotel(){
	    global $_W,$_GPC;
	    $user_id = $this->user_info['user_id'];
	    $user_type = $this->user_info['user_type'];
	    $city_id = $_GPC['city_id'];
	    $search_type = 2; // 搜索类型(全部酒店列表)
	    $page = intval($_GPC['page']); // 页码
	    $per_page = intval($_GPC['per_page']); // 偏移量
	    $search_text = '';
	    $lat = '';   // 纬度
	    $lng = '';   // 经度
	    
	    $resp = $this->open_server->search_hotel($user_id, $user_type, $city_id, $search_type, $page, $per_page, $search_text, $lat, $lng);
	    $hotels = $resp['hotels'];
	    $rows_count = count($hotels);
	    
	    $html = '';
	    foreach ($hotels as $key => $item){
	        $html .= '<li class="position_list hotel-item" data-page="' . $page .'" data-hotel-id="' . $item['id'] .'" data-hotel-name="' . $item['title'] . '" data-hotel-area="' . $item['area'] .'">
		            <div class="address_img">
		               <img src="' . assets_link('/framework/style/images/address-position.png') . '">
		            </div>
		            <div class="detail_hotel" >
		               <p class="hotel_name">' . $item['title'] .'</p>
		               <p class="hotel_area">' . $item['city'] . $item['area'] . $item['street'] . $item['address'] . '</p>
		            </div>
		         </li>
		         <div class="borderD"></div>';
	    }
	    $result['rows_count'] = $rows_count;
	    $result['html'] = compress_html($html);
	    message($result, '', 'ajax');
	}
	
	// 裁剪评价页
	public function tailor_comment(){
		global $_W,$_GPC;
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$city_id = $_GPC['city_id'] ?: get_user_city()['city_id'];
		$order_id = $_GPC['order_id'];
		if(empty($order_id)){
			header('Location: ' . create_url('order/order_list'));
			exit;
		}
		// 获取裁剪评价项
		$tailor_comment_opts = $this->open_server->get_tailor_comment_opts(61);
		$tailor_comment = $this->format_comment_opts($tailor_comment_opts);
		$score_tip = $tailor_comment['score_tip'];
		$total = $tailor_comment['total'];
		$quality = $tailor_comment['quality'];
		$tailor = $tailor_comment['tailor'];
		$sender = $tailor_comment['sender'];
		// 获取改衣评论图片
		$tailor_icons = $this->get_comment_icons();
		$star = $tailor_icons['star'];
		$face = $tailor_icons['face'];
		//获取ci token, 用于评价时上传图片
		$ci_token = $this->open_server->get_ci_token('order_comment');
		if(empty($ci_token)){
			error_report("无法获取上传token");
		}
		$upload_url = 'http://web.image.myqcloud.com/photos/v2/'.$ci_token['appid'].'/'.$ci_token['bucket'].'/0?sign='.urlencode($ci_token['auth_token']);
		include $this->template('tailor_comment');
	}
	
	// 保存裁剪评价
	public function do_tailor_comment(){
		global $_W,$_GPC;
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$order_id = $_GPC['order_id'];
		$city_id = $_GPC['city_id'];
		$mark = get_mark();
		if(empty($order_id) || empty($user_id)){
			header('Location: ' . create_url('order/order_list'));
			exit;
		}
		//上传图片
		$imgs = $_GPC['images'] ?: array();
		$imgs_count = count($imgs);
		if($imgs_count > 10){
			error_report('上传图片不能超过10张哦~');
		}
		$imgs = json_encode($imgs);
		//上传图片的宽高数组
		$imgs_wh = $_GPC['wh'] ?: array();
		if(count($imgs_wh) != $imgs_count){
			error_report('图片参数匹配异常~');
		}
		$imgs_wh = json_encode($imgs_wh);

		$total_score = $_GPC['total_score'];
		$quality_score = $_GPC['quality_score'];
		$tailor_score = $_GPC['tailor_score'];
		$sender_score = $_GPC['sender_score'];
		$tailor_comment = $_GPC['tailor_comment'];
		$sender_comment = $_GPC['sender_comment'];
		$comment_text = $_GPC['comment_text'];
		//调用open_server把评价写入
		$res = $this->open_server->create_tailor_comment($user_id, $order_id, $total_score, $quality_score, $tailor_score, $sender_score,
				$tailor_comment, $sender_comment, $comment_text, $city_id, $mark, $imgs, $imgs_wh);
		$order_list_url = create_url("order/order_list", array('status' =>'1'));
		if(!$res['ret']){
			error_report($res['error'] ?: '系统繁忙，请稍后再试', $order_list_url);
		}else{
		    $order_info = $res['data'];
		    // 订单评价积分
		    require_once IA_ROOT . '/framework/library/edaixi/SwServer.class.php';
		    $points = new SwServer($user_id);
		    $resp = $points->comment_points();
		    // 展示评价总星
		    $score_star['0'] = assets_link('/framework/style/images/icon_star.png');
		    $score_star['1'] = assets_link('/framework/style/images/star_hover.png');
		    for($i=1; $i<=5; $i++){
		        $total_star[$i] = $i <= $total_score ? $score_star['1'] : $score_star['0'];
		    }
		    include $this->template('tailor_success');
		}
	}
	
	// 裁剪评价完成页
	public function tailor_comment_finish(){
		global $_W,$_GPC;
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$order_id = $_GPC['order_id'];
		if(empty($order_id)){
			header('Location: ' . create_url('order/order_list'));
			exit;
		}
		$order = $this->open_server->get_order($user_id, $order_id);
		// 获取裁剪评价项
		$tailor_comment_opts = $this->open_server->get_tailor_comment_opts(61);
		$tailor_comment = $this->format_comment_opts($tailor_comment_opts);
		$score_tip = $tailor_comment['score_tip'];
		$total = $tailor_comment['total'];
		$quality = $tailor_comment['quality'];
		$tailor = $tailor_comment['tailor'];
		$sender = $tailor_comment['sender'];
		// 获取改衣评论图片
		$tailor_icons = $this->get_comment_icons();
		$star = $tailor_icons['star'];
		$face = $tailor_icons['face'];
	    // 接口已评价信息
		$total_score = $order['total_score'];
		$quality_score = $order['washing_score'];
		$tailor_score = $order['logistics_score'];
		$sender_score = $order['service_score'];
		$tailor_comment = $order['order_comments_fetch_option'];
		$sender_comment = $order['order_comments_carry_option'];
		$comment_text = $order['order_comments'];
		$comment_imgs = isset($order['order_comment_images']) ? $order['order_comment_images'] : json_encode(array());
		$comment_imgs = json_decode($comment_imgs,true);
		$imgs_wh = isset($order['images_sizes']) ? $order['images_sizes'] : json_encode(array());
		$imgs_wh = json_decode($imgs_wh,true);
		include $this->template('tailor_finish');
	}
	
	// 格式化改衣评论选项接口数据
	public function format_comment_opts($opts){
	    $comment = array();
	    foreach($opts['level'] as $tip){
	        $comment['score_tip'][$tip['level']] = $tip['text'];
	    }
	    unset($tip);
        $comment['total']['item'] = $opts['total'][0]['label'];
	    $comment['quality']['item'] = $opts['subs'][0]['label'];
	    $comment['tailor']['item'] = $opts['subs'][1]['label'];
	    foreach($opts['subs'][1]['text'] as $key => $val){
	        $comment['tailor']['opts'][$val['id']] = $val['text'];
	    }
	    unset($key, $val);
	    $comment['sender']['item'] = $opts['subs'][2]['label'];
	    foreach($opts['subs'][2]['text'] as $key => $val){
	        $comment['sender']['opts'][$val['id']] = $val['text'];
	    }
	    unset($key, $val);
	    return $comment;
	}
	
	// 获取改衣评论图片
	public function get_comment_icons($icons='all', $score='all'){
	    $comment_icons = array();
	    $score_star['0'] = assets_link('/framework/style/images/icon_star.png');
	    $score_star['1'] = assets_link('/framework/style/images/star_hover.png');
	    $score_face['0'] = assets_link('/framework/style/images/face_grey.png');
	    $score_face['1'] = assets_link('/framework/style/images/sad_hover.png');
	    $score_face['2'] = assets_link('/framework/style/images/unhappy_hover.png');
	    $score_face['3'] = assets_link('/framework/style/images/smile_hover.png');
	    $score_face['4'] = assets_link('/framework/style/images/happy_hover.png');
	    $score_face['5'] = assets_link('/framework/style/images/laugh_hover.png');
	    $comment_icons['star'] = $score_star;
	    $comment_icons['face'] = $score_face;
	    if('all' == $icons){
	        return $comment_icons;
	    }else{
	        if('all' == $score){
	            return $comment_icons[$icons];
	        }else{
	            return $comment_icons[$icons][$score];
	        }
	    }
	}
	
	// 下单投保提示
	public function ajax_insurance_desc(){
		global $_W,$_GPC;
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$is_login = $this->user_info['is_login'];
		// 用户手动切换首页城市
		if($_GPC['city_id']){
			$city_id = $_GPC['city_id'];
		}else{
			// 获取用户首页城市
			$user_city = get_user_city();
			$city_id = $user_city['city_id'];
		}
		$data = $this->open_server->get_insurance_claims_info($city_id);
		if($data) {
			if($user_id && $is_login && $city_id){
				redis()->set('insurance:' . $user_id . ':' . $city_id, $_SERVER['REQUEST_TIME']);
			}
			$result['state'] = 1;
			$result['description'] = $data['description'];
			$result['insured_amount'] = $data['insurance_account'];
			message($result, '', "ajax");
		}else{
			$result['state'] = 0;
			$result['msg'] = '网络错误，请稍后重试';
			message($result, '', "ajax");
		}
	}
	
	// 衣物瑕疵图片
	public function blemish_photos(){
		global $_W,$_GPC;
		$user_id = $this->user_info['user_id'];
		$order_id = $_GPC['order_id'];
		$clothes_index = $_GPC['clothes_index'];
		
		if(!$order_id || !isset($clothes_index)){
			if (isset($_SERVER['HTTP_REFERER'])){
				header("Location:" . $_SERVER['HTTP_REFERER']);
				exit();
			}else {
				error_report('访问出错,请重试');
			}
		}
		// 从redis缓存读取
		$blemish_photos = get_blemish_photos($order_id, $clothes_index);
		if($blemish_photos){
			$clothes_name = $blemish_photos[0]['clothes_name'];
		}else{
			// 从open读取
			$clothing = $this->open_server->order_clothing($user_id, $order_id)[$clothes_index];
			if($clothing['xiaci_images']){
				// 图片最大宽度
				$max_width = 2048;
				foreach ($clothing['xiaci_images'] as $photo_index => $photo){
					$width = $photo['width'];
					$height = $photo['height'];

					// 2016年03月10日特别声明:
				    // 经过协商,推迟一次上线后,接口仍然不提供图片尺寸.只能使用PHP计算.
				    // PHP计算图片尺寸必须依赖GD库,并且要将图片下载到本地才能计算.因此,图片较大较多会造成用户长时间等待,甚至请求超时.
				    
					// 计算图片尺寸 -- 非常影响性能(可能请求超时)
					if(!$width || !$height){
						list($width, $height) = getimagesize($photo['image_url']);
					}
					
					// 限制图片最大宽度 -- 保证前端插件性能
					if($max_width){
						if($width > $max_width){
							$height = round($height * ($max_width / $width));
							$width = $max_width;
						}
					}
					$clothing['xiaci_images'][$photo_index]['width'] = $width;
					$clothing['xiaci_images'][$photo_index]['height'] = $height;
				}
			}
			$blemish_photos = $clothing['xiaci_images'];
			$clothes_name = $clothing['cloth_title'];
			// 缓存订单衣物瑕疵图片
			set_blemish_photos($order_id, $clothes_index, $clothes_name, $blemish_photos);
		}
		
		if(!$blemish_photos){
			if (isset($_SERVER['HTTP_REFERER'])){
				$url = $_SERVER['HTTP_REFERER'];
			}else {
				$url = create_url('order/order_details',array('order_id' => $order_id));
			}
			header("Location:" . $url);
			exit();
		}
		include $this->template('blemish_photos');
	}
	
	// 呼出下单时间插件
	public function _ajax_time_plugin($qj_cate_id, $qj_city_id, $qj_area, $qj_date, $qj_time, $qj_soon){
		global $_W,$_GPC;
		$qj_order_id = $_GPC['qj_order_id'];
		$qj_cate_id = $_GPC['qj_cate_id'];
		$qj_city_id = $_GPC['qj_city_id'];
		$qj_area = $_GPC['qj_area'];
		$qj_date = $_GPC['qj_date'];
		$qj_time = $_GPC['qj_time'];
		$qj_soon = $_GPC['qj_soon'];
		if(!$qj_cate_id || !$qj_city_id || !$qj_area){
			$result['state'] = 2;
			$result['msg'] = '订单信息错误';
			message($result, '', 'ajax');
			return;
		}
		// === 初始化下单日期时段 ===
		$service_time = $this->open_server->get_service_time($qj_cate_id, $qj_city_id, $qj_area, $qj_order_id);
		$service_time_arr = $this->format_service_time($service_time, $qj_cate_id);
		$service_date = $service_time_arr['dk'];
		$service_time_bucket = $service_time_arr['tk'];
		$service_time_usable = count($service_time_bucket) > 0 ? 1 : 0;
		if(! $service_time_usable){
			$result['state'] = 2;
			$result['msg'] = '网络错误,请稍候重试';
			message($result, '', 'ajax');
			return;
		}
		$time_plugin_html = $this->generate_time_plugin_html($qj_cate_id, $service_date, $service_time_bucket);
		if($time_plugin_html){
			$result['state'] = 1;
			$result['msg'] = '';
			$result['html'] = $time_plugin_html;
		}else{
			$result['state'] = 2;
			$result['msg'] = '网络错误,请稍候重试~';
		}
		message($result, '', 'ajax');
		
	}
	
	// 格式化服务时间数组
	public function format_service_time($service_time, $category_id){
		if(empty($service_time)){
			return null;
		}
		$format_arr = array();
		foreach ($service_time as $k => $v){
			if($v['selected']){
				$format_arr['selected_data'] = $v['date'];
			}
			$format_arr['dk'][$v['date']]['selected'] = $v['selected'] + 0;
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
				if($v1['selected']){
					$format_arr['selected_time'] = $v1['text'];
				}
				$format_arr['tk'][$v['date']][$v1['text']]['selected']  = $v1['selected'] + 0;
				$format_arr['tk'][$v['date']][$v1['text']]['time'] = $v1['text'];
				$format_arr['tk'][$v['date']][$v1['text']]['time_str'] = $v1['view_text'];
				$format_arr['tk'][$v['date']][$v1['text']]['time_text'] = $v1['view_text'];
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
	
	// 构建时间插件HTML
	public function generate_time_plugin_html($category_id, $service_date, $service_time_bucket){
		if(empty($service_date) || empty($service_time_bucket)){
			return '';
		}
		$is_from_eservice = is_from_eservice();
		$e_background = $is_from_eservice ? 'e_background' : '';
		$e_color = $is_from_eservice ? 'e_color' : '';
		$yuman = assets_link('/framework/style/images/yuemanbg.png');
		$selblue = assets_link('/framework/style/images/img_corner_blue.png');
		$selgrey = assets_link('/framework/style/images/img_corner_grey.png');
		
		$html = '';
		$html .= <<<EOT
<ajax_success_return>
<div class="wx_mask" id="wx_mask" style="display:none;"></div>
<div id="time_plugin_inner" class="wx_timeControl {$e_background}">
	<div class="time_title">
		<span class="time_cancel" onclick="cancelPlugin()">取消</span>
		<span class="time_finish {$e_color}" onclick="submitPlugin()">完成</span>
	</div>
	<ul class="time_date">
EOT;
		foreach($service_date as $key => $date_val):
			$selectable = $date_val['selectable'] == 1 ? "selectable='selectable'" : '';
			$html .= <<<EOT
		<li class="detail_date tab-current" id="date_li_{$key}" serv_date="{$key}" date_text="{$date_val['date_text']}" {$selectable}>
			<p>{$date_val['weekday']}</p><p>{$date_val['date_str']}</p>
		</li>
EOT;
		endforeach;
		$html .= <<<EOT
			<div class="borderD"></div>
			</ul>
			<div class="time_change">
			<!-- 随时可取 start -->
			<div id="tksoon_wrap" class="anytime_kequ">
				<div class="kequ">
					<input id="tksoon" type="checkbox" value="1" />
					<label for="tksoon"><b></b></label>
				</div>
				<label for="tksoon" class="take_word">下单后立即上门取件</label>
			</div>
			<!-- 随时可取 end -->
EOT;
		
		foreach($service_time_bucket as $date_key => $time_arr):
			$html .= <<<EOT
			<ul class="time_hour" id="time_ul_{$date_key}" style="display:none;">
EOT;
			foreach($time_arr as $time_key => $time_val):
				$baodan = $time_val['selectable'] ? '' : "baodan";
				$selable = $time_val['selectable'] == 1 ? "selectable='selectable'" : '';
				$quick_class = $time_val['quick_text'] ? "soon_take" : '';
				$quick_text_style = $time_val['quick_text'] ? "style='color:#c6c6c6;font-size:.7em;'" : '';
				$html .= <<<EOT
				<li class="time_li detail_hour {$baodan} {$quick_class}" id="time_li_{$date_key}_{$time_key}" serv_time="{$time_key}" quick_take="{$time_val['quick_take']}" 
				 time_text="{$time_val['time_str']}" back_text="{$time_val['back_text']}" {$selable}>
EOT;
				if($time_val['quick_text']):
				$html .= <<<EOT
					<p>{$time_val['quick_text']}</p>
EOT;
				endif;
				
				$html .= <<<EOT
            	<p {$quick_text_style}>{$time_val['time_str']}</p>
EOT;
				if($time_val['selected']):
				$xuanzhong = $time_val['selectable'] ? $selblue : $selgrey;
				$html .= <<<EOT
          		<span class="img-corner"><img src="{$xuanzhong}" /></span>
EOT;
				elseif(!$time_val['is_available']):
		        $html .= <<<EOT
          		<span><img src="{$yuman}" /><small class="yueman">约满</small></span>
EOT;
		        endif;
		        
				$html .= <<<EOT
				</li>
EOT;
		  	endforeach;
			$html .= <<<EOT
			</ul>
EOT;
		endforeach;
		$html .= <<<EOT
  </div>
EOT;
		if($category_id != '13'):
		$html .= <<<EOT
		    <div class="kongbai" id="kongbai"></div>
EOT;
		endif;
		$html .= <<<EOT
</div>
EOT;
		return compress_html($html);
	}
	
	// 修改取件时间
	public function update_qjtime(){
		global $_W,$_GPC;
		$user_id = $this->user_info['user_id'];
		$order_id = $_GPC['order'];
		$date = $_GPC['date'];
		$time = $_GPC['time'];
		$soon = $_GPC['soon'];
		$resp = $this->open_server->update_order_time($user_id, $order_id, $date, $time, $soon);
		
		if($resp['ret']){
			$result['state'] = 1;
			$result['msg'] = '';
		}else{
			$result['state'] = 0;
			$result['msg'] = $resp['error'] ?: '网络错误,请稍候重试~';
		}
		message($result, '', 'ajax');
	}

	protected function getShareUrl($url)
	{
		return $url.'&daf='.$this->getShareActiveId().'&duf='.$this->encryptionUserId().'&depth='.($this->getDepth()+1);
	}

	//零彩宝活动
	private function start_lingcb_activity($order_id){
		global $_W,$_GPC;
		$user_id = $this->user_info['user_id'];
		$mobile = $this->user_info['is_login'];
		//组合日志数组
		$log_arr = array(
			'user_id' => $user_id,
			'order_id' => $order_id,
			'mobile' => $mobile
			);

		$flag_3d = 0;
		$redis = redis();
		$redis_limit_key = 'lingcblimit_' . date("Ymd") . '_' . $user_id;
		//每天同一个用户超过3单不送
		$limit = $redis->get($redis_limit_key);
		if($limit > 2){
			$log_arr['err_msg'] = '当前单数：' . ($limit+1);
			logging('当日单数超限', $log_arr, 'a+', 'lottery');
			return false;
		}
		if(empty($limit)){
			$redis->setex($redis_limit_key, 3600*24, 1);
		}else{
			$redis->incr($redis_limit_key);
		}
		//如果是周二，前1000名送3D彩票(用set存储)
		if(date("w") == 2){
			$redis_3d_key = 'lingcaibao_' . date("YW");
			$count = $redis->scard($redis_3d_key);
			if(empty($count)){
				$redis->sadd($redis_3d_key, $user_id);
				$redis->expire($redis_3d_key, 3600*24*7);
				$flag_3d = 1;
			}else if($count < 1000){
				if($redis->sadd($redis_3d_key, $user_id)){
					$flag_3d = 1;
				}		
			}
		}
		//组织数据
		$data = array();
		$data['orderId'] = (string)$order_id;
		$data['mobile'] = substr_replace($mobile, '****', 3, 4);
		$data['uId'] = (string)$user_id;
		$data['remark'] = 'edaixi';
		$data['source'] = 'edaixi';

		//根据订单号获取该次用户一共支付的金额
		$ret_fee = $this->open_server->get_trade_fee_by_orderid($order_id);
		if(empty($ret_fee)){
			logging('获取交易金额失败', $log_arr, 'a+', 'lottery');
			$redis->decr($redis_limit_key);
			if($flag_3d == 1){
				$redis->srem($redis_3d_key, $user_id);
			}
			return false;
		}
		$fee = 0;
		foreach ($ret_fee['order_detail'] as $value) {
			$fee += $value['fee'];
		}
		$piece = 1;
		$data['money'] = '0.32';
		if($flag_3d == 0){
			//此处需要根据用户实际支付的金额进行彩票注数金额计算
			if($fee > 100){
				$data['money'] = '0.96';
				$piece = 3;
			}else if($fee > 50){
				$data['money'] = '0.64';
				$piece = 2;
			}
			$data['gameId'] = 'SSQ';
			$data['name'] = '';
			$data['cardNo'] = '';			
		}else{
			$data['gameId'] = '3D';
			$data['ballNo'] = '';
			$data['money'] = '2.00';
		}
		require IA_ROOT . '/framework/library/edaixi/third_server.class.php';
		$third_server = new ThirdServer();
		$ret = $third_server->get_lingcb_order($data, $flag_3d);
		if(empty($ret) || $ret['result'] != 'LC00000'){
			if(!empty($ret)){
				$log_arr['err_msg'] = $ret['mesg'];
				$log_arr['err_orderid'] = $ret['orderId'];
				$log_arr['err_code'] = $ret['result'];
			}
			logging('获取零彩宝订单失败', $log_arr, 'a+', 'lottery');
			$redis->decr($redis_limit_key);
			if($flag_3d == 1){
				$redis->srem($redis_3d_key, $user_id);
			}
			return false;
		}

		//组合数据，先全部扔swoole进行insert table
		$params = array();
		$params['lottery_content'] = serialize($ret);
		$params['mobile'] = $mobile;
		$params['order_id'] = $order_id;
		$params['game_id'] = ($flag_3d == 0) ? 'SSQ' : '3D';
		$params['fan_id'] = $user_id;
		$params['fee'] = number_format($fee, 2, '.', '');

		require_once IA_ROOT . '/framework/library/edaixi/SwServer.class.php';
		$this->sw_server = new SwServer($user_id);
		$source = $this->sw_server->set_lingcaibao_lottery($params);
		unset($params);

		//获取【点击查看更多】链接
		$data = array();
		$data['uId'] = (string)$user_id;
		$data['lcbOrderId'] = $ret['lcbOrder'];
		$data['errorUrl'] = '';
		$data['source'] = 'edaixi';
		$show_more = $third_server->get_lingcb_entrance($data);

		//组织数据，返回前端
		$lottery = array();
		$lottery['show_more'] = $show_more;
		$lottery['issue_no'] = $ret['issueNo'];
		$lottery['prize_time'] = date('Y年m月d日 H:i', strtotime($ret['prizeTime']));
		if($flag_3d == 0){
			$lottery['species'] = 0;
			$ball_str = str_replace(':', ',', $ret['ball']);
			$ball_arr = explode('|', $ball_str);
			$lottery['pieces'] = $piece;
			//只取两行
			$ball_arr = array_slice($ball_arr, 0, 2);
			foreach ($ball_arr as $key => $val) {
				$lottery['balls'][$key] = explode(',', $val);
			}
		}else{
			$lottery['species'] = 1;
			$lottery['balls'][0] = explode(',', $ret['ball']);
		}

		//此处增加零彩宝微信分享配置，避免污染order_list
		//微信分享文案
		$wx_share_conf = array(
			array(
				'title' => '“贺新年，洗晦气，送运气”',
				'desc' => '我参加了e袋洗下单送彩票活动，洗衣还能中大奖'
				),
			array(
				'title' => '“贺新年，洗晦气，送运气”',
				'desc' => '我参加了e袋洗下单送彩票活动，洗衣还能中大奖'
				),
			array(
				'title' => '“贺新年，洗晦气，送运气”',
				'desc' => '我在e袋洗参加下单送彩票活动，中了超级大奖'
				)
			);
		$key = 0;
		$ret = $this->sw_server->get_lingcb_total_prize();
		if(!empty($ret)){
			$ret = floatval($ret['total_money']);
			if($ret >= 10){
				$key = 2;
			}else{
				$key = 1;
			}
		}
		$lottery['wx_share'] = array(
			'desc' => $wx_share_conf[$key]['desc'],
			'title' => $wx_share_conf[$key]['title'],
			'share_img' => assets_link('/resource/image/lingcb_share.jpg'),
			//'share_url' => 'http://watest.lingcaibao.com/account/edx/shareLanding'
			'share_url' => rtrim($_W['config']['site']['root'], '/') . create_url('order/lingcb_share_page',array('user' => base64_encode($user_id)))
			);
		return $lottery;
	}

	//零彩宝分享页
	public function lingcb_share_page(){
		global $_W,$_GPC;
		$user_id = $_GPC['user'] ? base64_decode($_GPC['user']) : 0;
		
		//展示页文案
		$show_msg_conf = array(
			array(
				'我参加了e袋洗全民“贺新年，洗晦气，送运气”',
				'下单送彩票活动',
				'超级大奖我来啦!',
				''
				),
			array(
				'洗衣还能中大奖',
				'我参加了e袋洗全民“贺新年，洗晦气，送运气”',
				'下单送彩票活动',
				'中奖啦！'
				),
			array(
				'洗衣还能中大奖',
				'我参加了e袋洗全民“贺新年，洗晦气，送运气”',
				'下单送彩票活动',
				'中了超级大奖!'
				)
			);
		$key = 0;
		if($user_id){
			require_once IA_ROOT . '/framework/library/edaixi/SwServer.class.php';
			$this->sw_server = new SwServer($user_id);
			$ret = $this->sw_server->get_lingcb_total_prize();
			if(!empty($ret)){
				$ret = floatval($ret['total_money']);
				if($ret >= 10){
					$key = 2;
				}else{
					$key = 1;
				}
			}
		}
		$show_msg = $show_msg_conf[$key];
		$order_url = create_url('homepage/index', array('mark' => '1478678406mZJcuzpr'));
		include $this->template('lingcaibao_share');
	}
	
}
