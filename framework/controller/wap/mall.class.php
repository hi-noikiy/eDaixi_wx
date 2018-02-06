<?php
defined('IN_IA') or exit('Access Denied');

class Mall extends BaseModule {

	function __construct(){
		global $_W;
		parent::__construct();
		$this->model_name = 'mall';
		$this->open_server = new OpenServer($_W['config'],$this->user_info);
	}
	// 洗衣液数据
	function detergent_price(){
		global $_W, $_GPC;
		$good_id = $_GPC['good_id'] ?: 1;
		$res = $this->open_server->get_good_stock_count($good_id);
		if($res['ret'] === false)
			echo_json(false);
		echo_json(true, $res);
	}
	// 洗衣液下单页
	function detergent_order_place(){
		global $_W, $_GPC;
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$city_id = intval($_GPC['city_id']) ?: get_user_city()['city_id'];
		$good_id = 1;
		$res = $this->open_server->get_physical_order_page($user_id, $user_type, $city_id, $good_id);
		if($res['ret'] === false)
			echo_json(false, '', array('message' => '网络出错', 'url'=>create_url('homepage/index')));
		
		 // 下单来源:
		$back_params = array(
				'city_id' => $city_id, 
				'mark' => $mark,
			);
		// 下单页-->地址列表页 URL
		$select_addr_url = create_url('address/order_address_list', array(
				'link_from'	=>	'detergent_order_place',
				'back'	=>	urlencode(add_params('/new_weixin/view/detergent_order.html', $back_params)),
		));
		$res['select_addr_url'] = $select_addr_url;
		$res['default_address'] = $res['default_address'] ? : (object)array();
		echo_json(true, $res);
	}

	// 洗衣液下单
	function detergent_do_order(){
		global $_W, $_GPC;
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$city_id = intval($_GPC['city_id']) ?: get_user_city()['city_id'];
		$good_id = 1;
		$count = intval($_GPC['count']);
		$address_id = intval($_GPC['address_id']);
		$remark = trim($_GPC['remark']);
		if(!$count || !$address_id){
			echo_json(false, '', array("message" => "下单信息不完整", ));
		}
		$res = $this->open_server->create_physical_order($city_id, $user_id, $user_type, $address_id, $count, $good_id, $remark);
		if($res['ret'] === false)
			echo_json(false, '', array("message" => $res['error']));
		$res['url'] = create_url('payment/platform', array('is_xiyiye' => true, 'order_id' => $res['order_id']));
		echo_json(true, $res);
	}

}

