<?php
defined('IN_IA') or exit('Access Denied');
use Edx\Model\ModelLoader as Model;
use Edx\Helper\Common;

class Payment extends BaseModule {
	function __construct() {
		global $_W;
		parent::__construct();
		$this->model_name = 'payment';
		$this->open_server = new OpenServer($_W['config'], $this->user_info);
		//载入支付模型
		$user_info = $this->user_info;
		$this->pay_model = Model::get('pay', $user_info);
	}
	
	/**
	 * 待支付订单列表
	 */
	public function pay_list() {
		global $_GPC;
		$user_info = $this->user_info;
		$user_id = $user_info['user_id'];
		$user_type = $user_info['user_type'];
		$order_city_id = empty($_GPC['order_city']) ? 0 : $_GPC['order_city'];
		$order_id = empty($_GPC['order_id']) ? 0 : $_GPC['order_id'];
		$pay_model = Model::get('pay', $user_info);
		// 洗衣等订单
		$pay_list = $pay_model->payList($user_id, $user_type, $order_id);
		if (empty($pay_list)) {
			$order_list = create_url('order/order_list');
			$this->jumpUrl($order_list);
		} else {
			//浦发周边通需要获取合并支付中所有订单的category_name
			if($user_type == 26){
				$psd_arr = array();
				foreach ($pay_list['group'] as $group) {
					foreach ($group['orders'] as $order) {
						$psd_arr[] = $order['category_name'];
					}
				}
				$psd_arr = array_unique($psd_arr);
				$_SESSION['psd_categoryname'] = implode(',', $psd_arr);
			}
			//运费说明信息
			$delivery_fee_info = $pay_model->deliveryInfo($order_city_id);
			//计算运费url
			$delivery_url = create_url('payment/get_delivery', 
				 array('order_city' => $order_city_id));
			//合单支付地址
			$pay_url = create_url('payment/platform', array(
					'order_city' => $order_city_id,
					'first_use_coupon'	=>	1,
					));
			include $this->template('waiting_pay_list');
		}	
	}

	/**
	 * 获取订单运费
	 */
	public function get_delivery() {
		global $_GPC;
		$user_info = $this->user_info;
		$user_id = $user_info['user_id'];
		$orders = isset($_GPC['order']) ? $_GPC['order'] : null;
		$order_city_id = isset($_GPC['order_city']) ?
		           $_GPC['order_city'] : null;
		$pay_model = Model::get('pay', $user_info);
		$delivery_data = $pay_model->orderDeliveryFee($user_id,
			                         $orders, $order_city_id);
		if (empty($delivery_data)) {
			$ret_data = array(
				'status' =>  false,
				'data' => $delivery_data,
				'msg' => '暂无最新运费信息'
			);
		} else {
			$ret_data = array(
				'status' =>  true,
				'data' => $delivery_data,
				'msg' => ''
			);
		}
		return $this->retJson($ret_data);
	}

	/**
	 * 支付平台
	 */
	public function platform()
	{
		global $_GPC;
		global $_W;
		$user_info = $this->user_info;
		$user_id = $user_info['user_id'];
		$user_type = $user_info['user_type'];
		// 是否是洗衣液
		$is_xiyiye = $_GPC['is_xiyiye']? : false;
		//order_list为json格式字符串，　不能从$_GPC变量里取(会被转义)
		$order_list = isset($_GET['order_list']) ?
					  $_GET['order_list'] : null;
		//继续支付传递单个订单id
		$order_id = isset($_GPC['order_id']) ?
					  $_GPC['order_id'] : null;
		if (!empty($order_id)) {
			$order_list = json_encode([(int)$order_id]);
		}
		$ecard_list = isset($_GET['ecard_list']) ?
		  			  $_GET['ecard_list'] : null;
		$ecard_list = @json_decode($ecard_list, true);
        if (!empty($ecard_list)) {
        	$ecard_list = json_encode($ecard_list);
        } else {
        	$ecard_list = null;
        }
        // 是否是点击结算进入的
        $first_use_coupon = intval($_GPC['first_use_coupon']);
		//优惠券id
		$coupon_id = isset($_GPC['coupon_id']) ? $_GPC['coupon_id'] : 0;
		//已经选择的第三方支付方式(点击充值跳回情况)
		$third_pay_type = isset($_GPC['third_pay_type']) ?
		                  $_GPC['third_pay_type'] : 0;
		$pay_model = Model::get('pay', $user_info);
		$in_weixin = is_from_weixin();
		//订单付款信息
		if($is_xiyiye){
			$pay_info = $pay_model->getDetergentPayInfo($user_id, $order_id);
		}else{
			$pay_info = $pay_model->getPayInfo($user_id, $user_type,
			$order_list, $in_weixin);
		}
		if (!empty($pay_info['order_group_ids'])) {
			//用于继续支付得到最初的多个订单id
			$order_list = json_encode($pay_info['order_group_ids']);
		}
		if (empty($pay_info)) {
			$cur_url = HTTP_TYPE.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			error_report('出错啦', $cur_url);
		}
		// 新增默认优惠券,首次点击结算时使用
		if($first_use_coupon && empty($coupon_id) && $pay_info['default_coupon']){
			$coupon_id = $pay_info['default_coupon']['id'];
		}

		//订单支付总金额
		$total_pay_money = isset($pay_info['order_amount']) ?
							$pay_info['order_amount'] : 0;
		//余额支付是否可用
		$icard_enable = true;
		//session中存储订单金额，e卡选择时判断用
		if (isset($pay_info['order_amount'])) {
			$_SESSION['pay']['order_amount'] = $pay_info['order_amount'];
		}
		// 0: 进入支付页面 1：继续支付进入页面
		$pay_status = $pay_info['type'];
		// 是否可以选择除第三方的支付方式
		$can_choose = $pay_info['choose'];
		if ($can_choose) {
			//能不能选优惠券、e卡、余额
			$can_click = '';
			if ($pay_info['icard_coin'] <= 0) {
				$icard_can_click = 'disabled="true"';
			} else {
				$icard_can_click = '';
			}
		} else {
			$can_click = 'disabled="true"';
			$icard_can_click = 'disabled="true"';
		}
		if ((!empty($coupon_id) ||
			!empty($ecard_list)) && 
			//可以选择除第三方外的支付方式情况
			$can_choose) {
			//使用优惠券的优惠价格
			$action = 1;
			if (!empty($ecard_list)) {
				$action = 2;
			}
			$choose_icard = 0;
			//计算订单付款信息
			$pay_price = $pay_model->caclulateOrderPrice($user_id, $user_type,
				$choose_icard, $order_list, $ecard_list, $coupon_id, $action);
			if (!empty($pay_price['total'])) {
				$total_pay_money = $pay_price['total'];
				$pay_info['third_amount'] = $pay_price['total'];
			}
			if ($pay_price['icard_enable'] == 0 ||
				$total_pay_money == 0) {
				$icard_enable = false;
			}
			//更新可用的第三方支付方式
			if (is_array($pay_price['third']['usable'])) {
				foreach ($pay_info['third_pay'] as $pay_type => $detail) {
					if (!in_array($pay_type, $pay_price['third']['usable'])) {
						$pay_info['third_pay'][$pay_type]['is_usable'] = false;
					}
				}
				$pay_info['third'] = $pay_price['third'];
			}
			//session中存储优惠券优惠后的价格，e卡选择用
			if (isset($pay_price['total'])) {
				if ($action == 1) {
					//优惠券优惠后的订单金额
					$_SESSION['pay']['order_amount_after_coupon'] = $pay_price['total'];
					//记录优惠券优惠的金额
					$_SESSION['pay']['coupon_fee_text'] = $pay_price['fee_text'];
					$_SESSION['pay']['coupon_amount'] = $pay_price['fee'];
					unset($_SESSION['pay']['ecard_fee_text']);
					unset($_SESSION['pay']['ecard_amount']);
				} else {
					//记录e卡优惠的金额
					$_SESSION['pay']['ecard_fee_text'] = $pay_price['fee_text'];
					$_SESSION['pay']['ecard_amount'] = $pay_price['fee'];
					if (empty($coupon_id)) {
						unset($_SESSION['pay']['coupon_fee_text']);
						unset($_SESSION['pay']['coupon_amount']);
						$_SESSION['pay']['order_amount_after_coupon'] = 0;
					}
				}	
			} else {
				unset($_SESSION['pay']['coupon_fee_text']);
				unset($_SESSION['pay']['ecard_fee_text']);
				unset($_SESSION['pay']['coupon_amount']);
				unset($_SESSION['pay']['ecard_amount']);
			}
		} else {
			$_SESSION['pay']['order_amount_after_coupon'] = 0;
			unset($_SESSION['pay']['coupon_fee_text']);
			unset($_SESSION['pay']['ecard_fee_text']);
			unset($_SESSION['pay']['coupon_amount']);
		    unset($_SESSION['pay']['ecard_amount']);
		}
		if (!empty($_SESSION['pay']['coupon_fee_text'])) {
			$pay_info['pay_text']['coupon_text'] = $_SESSION['pay']['coupon_fee_text'];
			$pay_info['coupon_amount'] = $_SESSION['pay']['coupon_amount'];
		}
		if (!empty($_SESSION['pay']['ecard_fee_text'])) {
			$pay_info['pay_text']['ecard_text'] = $_SESSION['pay']['ecard_fee_text'];
			$pay_info['ecard_amount'] = $_SESSION['pay']['ecard_amount'];
		}
		
        // 去充值--订单金额折扣提示
        $discount_show = false;
        if($_GPC['recharge_discount']){
        	$recharge_discount = json_decode(base64_decode($_GPC['recharge_discount']), true);
        	if(empty($recharge_discount["recharge_money"]) || empty($recharge_discount["gift_money"])){
        		$recharge_discount = $this->open_server->order_discount($pay_info['order_amount']);
        	}
        }else{
        	// 非洗衣液情况
        	if(!$is_xiyiye){
				$recharge_discount = $this->open_server->order_discount($pay_info['order_amount']);
			}
        }
        $recharge_url = create_url('icard/icard_charge', array('status' => '1'));
		if(isset($recharge_discount["recharge_money"])){
			$recharge_url = create_url('icard/icard_charge', array(
					'status' => '1', 
					'recharge_discount' => base64_encode(json_encode($recharge_discount))
			));
			$discount_show = true;
			$recharge_text = '充' . $recharge_discount["recharge_money"] . '送' . $recharge_discount["gift_money"];
			$discount_text = $recharge_discount["final_money"];
		}
		$recharge_callback = create_url('payment/platform',array(
				'order_list' => $order_list,
				'coupon_id' => $coupon_id,
				'ecard_list' => $ecard_list,
				'recharge_discount' => $recharge_discount
		));
		//默认选中第一个第三方支付
		$pay_name = '';
		$j = 0;
		//usable中第一个可用的第三方支付方式为默认选择支付方式
		foreach ($pay_info['third']['usable'] as $usable_pay) {
 		  if (isset($pay_info['third_pay'][$usable_pay]) &&
 		  	  $pay_info['third_pay'][$usable_pay]['is_usable']) {
 		  	$default = $usable_pay;
 		  	break;
 		  }
 		}
		foreach ($pay_info['third_pay'] as $key => $value) {
			//如果是继续支付， 隐藏不可用的支付方式
			if ($pay_status == 1 &&
				!$value['is_usable']) {
				unset($pay_info['third_pay'][$key]);
				continue;
			}
			reset($pay_info['third']['usable']);
 			if (!empty($third_pay_type)) {
 				$default = $third_pay_type;
 			}
			if ($default == $key &&
				$value['is_usable']) {
				$pay_info['third_pay'][$key]['checked'] = 'checked="checked"';
				$pay_name = ($key==20) ? '一网通支付' : $value['show_name'];
			} else {
				$pay_info['third_pay'][$key]['checked'] = "";
			}
			$j++;
		}
		//余额支付是否选中
		if ($pay_info['icard_amount'] > 0) {
			//继续支付余额扣钱时，设为选中
			$icard_checked = 'checked="checked"';
		} else {
			$icard_checked = '';
		}
		//组织支付按钮文案
		$btn_text = '完成支付';
		$btn_style = '';
		$btn_class = 'pay-btn';
		if ($pay_info['third_amount'] > 0) {
			if (empty($pay_name)) {
				$pay_name = '支付';
				$btn_style = 'disabled="disabled"';
				$btn_class .= ' gray-btn';
			}
			$total_pay_money = Common::round($pay_info['third_amount']);
			$btn_text = "{$pay_name} ￥{$total_pay_money}";
		}
		//选择优惠券地址
		$coupon_url = create_url('payment/pay_coupon_list',
			array(
					'order_list' => $order_list,
					'coupon_id' => $coupon_id
				)
			);
		//选择e卡地址
		$ecard_url = create_url('payment/choose_ecard',
			array(
					'order_list' => $order_list,
					'coupon_id' => $coupon_id,
					'ecard_list' => $ecard_list
				 )
			);
		//继续支付不可选情况
		if (!$can_choose) {
			$coupon_url = 'javascript:volid(0);';
			$ecard_url = 'javascript:volid(0);';
		}
		//点击余额支付，查询支付金额url
		$caclulate_url = create_url('payment/caclulate_price',
			array(
					'order_list' => $order_list,
					'coupon_id' => $coupon_id,
					'ecard_list' => $ecard_list
				 )
			);
		//支付方式冲突时跳转的默认都不选的url
		$default_url = create_url('payment/platform',
			array(
					'order_list' => $order_list,
				)
			);
		//支付url
		$pay_url = create_url('payment/deal_with_pay', array('order_list'=>$order_list, 't' => time()));

		//订单取消支付url
		$cancel_pay_url = create_url('payment/cancel_pay');
		$cancel_order_id = json_decode($order_list)[0];
		if (empty($cancel_order_id)) {
			$cancel_order_id = 0;
		}

		$pay_info['is_wx_browser'] = 0;

		if(false !== strpos( ',' . $_W['account']['payment']['wechat_h5']['user_type'] . ',', ',' . $this->user_info['user_type'] . ',') ) {
			$pay_info['is_wx_browser'] = 1;	
		}

		include $this->template('order_pay');
	}
	/**
	 * 支付时根据不同支付方式计算支付费用
	 */
	public function caclulate_price()
	{
		global $_GPC;
		global $_W;
		$user_info = $this->user_info;
		$user_id = $user_info['user_id'];
		$user_type = $user_info['user_type'];
		//order_list为json格式字符串，　不能从$_GPC变量里取(会被转义)
		$order_list = isset($_GET['order_list']) ?
					  $_GET['order_list'] : null;
		$ecard_list = isset($_GET['ecard_list']) ?
		  			  $_GET['ecard_list'] : null;
		//优惠券id
		$coupon_id = isset($_GPC['coupon_id']) ? $_GPC['coupon_id'] : 0;
		$pay_model = Model::get('pay', $user_info);
		//余额
		$action = 3;
		$choose_icard = 1;
		//计算订单付款信息
		$pay_price = $pay_model->caclulateOrderPrice($user_id, $user_type,
			$choose_icard, $order_list, $ecard_list, $coupon_id, $action);
		//替换余额支付金额的颜色为红色
		$pay_price['fee_text'] = str_replace($pay_price['fee'],
			'<money style="color:#ff6339;">' . $pay_price['fee'] . '</money>',
			$pay_price['fee_text']);
		if (!empty($pay_price)) {
			$ret_data = array(
					'status' => true,
					'data' => array(
							'icard_fee' => $pay_price['fee'],
							'icard_fee_text' => $pay_price['fee_text'],
							'pay_money' => $pay_price['total'],
							'third' => $pay_price['third']['usable']
						),
					'mes' => ''
				);
		} else {
			$ret_data = array(
					'status' => false,
					'data' => '',
					'msg' => '暂无余额信息'
				);
		}
		$this->retJson($ret_data);
	}

	/**
	 * 支付时选择e卡
	 */
	public function choose_ecard()
	{
		global $_GPC;
		$user_info = $this->user_info;
        //e卡模型
        $ecard_model = Model::get('card', $user_info);
        $user_id = $user_info['user_id'];
        //$user_id = 106;
        $user_type = $user_info['user_type'];
        //order_list为json格式字符串，　不能从$_GPC变量里取(会被转义)
        $order_list = isset($_GET['order_list']) ?
                      $_GET['order_list'] : null;
        //已经选择的e卡id之list
        $ecard_list = isset($_GET['ecard_list']) ?
                      $_GET['ecard_list'] : null;
        if (!empty($ecard_list)) {
        	$ecard_list = json_decode($ecard_list);
        } else {
        	$ecard_list = array();
        }
        //优惠券id
        $coupon_id = isset($_GPC['coupon_id']) ?
        			 $_GPC['coupon_id'] : 0;
        //用户e卡列表
        $user_ecard_list = $ecard_model->userEcardList($user_id, $user_type, $order_list);
		//订单总金额，e卡点选时判断用
		$total_money = empty($_SESSION['pay']['order_amount'])
					   ? 0 : $_SESSION['pay']['order_amount'];
	   	$after_coupon_money =
	   	  empty($_SESSION['pay']['order_amount_after_coupon'])
		  ? $total_money : $_SESSION['pay']['order_amount_after_coupon'];
		//优惠券与e卡冲突时返回重新选择的地址
		$back_url = create_url('payment/platform',
			array(
				'order_list' => $order_list
			));
		//选卡后跳回地址
		$jump_url = create_url('payment/platform',
			array(
				'order_list' => $order_list,
				'coupon_id' => $coupon_id
			));
        $data_info = array(
                'ecard_list' => $user_ecard_list,
                'is_pay' => true,
                'exchange_url' => create_url('ecard/exchange'),
                'total_money' => $total_money,
                'after_coupon_money' => $after_coupon_money,
                'coupon_id' => $coupon_id,
                'back_url' => $back_url,
                'jump_url' => $jump_url
            );
        //加载模板
        include $this->template('e-card', 'icard');
	}

	/**
	 * 订单取消支付
	 */
	public function cancel_pay()
	{
		global $_GPC;
		$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
		$pay_model = $this->pay_model;
		$user_id = $this->user_info['user_id'];
		$cancel_pay = $pay_model->cancelPay($user_id, $order_id);
		//取消支付成功后到订单列表
		if ($cancel_pay['status']) {
			$cancel_pay['url'] = create_url('order/order_list');
		}
		$this->retJson($cancel_pay);
	}
	
	// 订单可用优惠券列表
	public function pay_coupon_list() {
		global $_W, $_GPC;
		$user_info = $this->user_info;
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$coupon_id = $_GPC['coupon_id'] ?: '';
		$order_id = $_GPC['order_id'] ?: '';
		//order_list为json格式字符串，　不能从$_GPC变量里取(会被转义)
		$order_list = isset($_GET['order_list']) ? $_GET['order_list'] : null;
		$recharge_discount = $_GPC['recharge_discount'] ?: '';
		//订单付款页面
		$order_pay_url = create_url('payment/platform',array(
				'order_list' => $order_list,
				'recharge_discount' => $recharge_discount
		));
		$bind_coupon_url = create_url('payment/ajax_order_exchange_coupon', array(
				'coupon_id' => $coupon_id, 
				'order_list' => $order_list, 
				'recharge_discount' => $recharge_discount
		));
		//获取用户优惠券列表
		$coupon_list = $this->open_server->get_coupons($user_id, $user_type, $order_list);
		//组织可用和不可用优惠券列表
		$usable_coupons = array();
		$disable_coupons = array();
		if(is_array($coupon_list)) {
			foreach ($coupon_list as $coupon) {
				// 订单可用优惠券
				if ($coupon['usable']) {
					$usable_coupons[] = $coupon;
				}
				// 订单不可用优惠券
				else {
					$disable_coupons[] = $coupon;
				}
			}
		}else{
			$coupon_list = array();
		}
		include $this->template('pay_coupon_list');
	}

	public function deal_detergent_order_pay(){
		global $_W;
		 $open_ret = $this->open_server->pay_physical_order($user_id, $physical_order_id, $pay_type);
		        if (!empty($open_ret)) {
		            $pay_result = $open_ret;
		        }
        return $pay_result;
	}

	/*
	 * 进行订单支付
	 * 请求方式:post
	 * 请求参数: 
	 *   paytype => 1 (第三方支付方式)
	 *   coupon_id => 111 (支付使用的优惠券id)
	 *   order_list => '[1,2,3]' (要支付的订单ids，json编码字符串)
	 *   ecard_list => '[232,23,232]' (支付时选择的e卡ids， json编码字符串)
	 *   coupon_fee => '20.00' (优惠券抵扣的金额)
	 *   ecard_fee => '20.00' (e卡抵扣的金额)
	 *   icard_fee => '12.00' (余额支付的金额)
	 *   third_price => '23.00' (还需要第三方支付的金额)
	 *   choose_icard => '1' (0/1 是否使用了余额支付)
	 */
	public function deal_with_pay()
	{
		global $_W;
		//第三方支付方式
		$pay_data = array();
		$pay_data['paytype'] = isset($_POST['paytype']) ? $_POST['paytype'] : 0;
		//优惠券id
		$pay_data['coupon_id'] = isset($_POST['coupon_id']) ? $_POST['coupon_id'] : 0;
		// 是否是洗衣液
		$pay_data['is_physical'] = isset($_POST['is_xiyiye']) ? true : false;
		$pay_data['physical_order_id'] = isset($_POST['physical_order_id']) ? $_POST['physical_order_id'] : 0;
		//订单列表
		$pay_data['order_list'] = isset($_POST['order_list']) ?
		              $_POST['order_list'] : null;
		//e卡列表
		$pay_data['ecard_list'] = isset($_POST['ecard_list']) ?
		              $_POST['ecard_list'] : null;
		//优惠券优惠金额
		$pay_data['coupon_fee'] = isset($_POST['coupon_fee']) ? $_POST['coupon_fee'] : 0;
		//e卡抵扣金额
		$pay_data['ecard_fee'] = isset($_POST['ecard_fee']) ? $_POST['ecard_fee'] : 0;
		//余额支付的金额
		$pay_data['icard_fee'] = isset($_POST['icard_fee']) ? $_POST['icard_fee'] : 0;
		//还需要支付的金额
		$pay_data['third_price'] = isset($_POST['third_price']) ?
		               $_POST['third_price'] : 0;
		//是否选择余额支付
		$pay_data['choose_icard'] = isset($_POST['choose_icard']) ?
		               $_POST['choose_icard'] : 0;
		$user_info = $this->user_info;
		$user_id = $user_info['user_id'];
		$user_type = $user_info['user_type'];
		$pay_model = Model::get('pay', $user_info);
		$pay_result = $pay_model->payOrder($pay_data, $user_id, $user_type);
		$ret_data = array('status' => (isset($pay_result['ret']) ? $pay_result['ret'] : false),'msg' => '');
        
        $is_wx_browser = 0;//微信浏览器

        if(false !== strpos( ',' . $_W['account']['payment']['wechat_h5']['user_type'] . ',', ',' . $this->user_info['user_type'] . ',') ) {
            $is_wx_browser = 1;//非微信浏览器   
        }
		
        if (is_ajax()) {
			
		    if( $pay_data['paytype'] == 2 && $is_wx_browser == 1 &&
		    	//需要第三方支付情况
		    	$pay_data['third_price'] > 0) {	
				goto weixin_h5;
			}
			
			//不需要发起第三方支付
			if(get_mark() == 'eservice' || $user_type == 18) {
				$ret_data['url'] = $_W['config']['xiaoe']['url'].'/order';
			} else {
				$ret_data['url'] = create_url('order/order_list');
			}
			$this->retJson($ret_data);
		}

		weixin_h5:
		if ($pay_result['ret'] && !empty($pay_result['data']['trade_no']) && !empty($pay_result['data']['total'])) {
			//发起第三方支付
			$pay_type = $pay_data['paytype'];
			//支付流水号
			$trade_no = $pay_result['data']['trade_no'];
			//支付金额
			$money = $pay_result['data']['total'];
			//组织第三方支付参数
			$params['tid'] = $trade_no;
			$params['user'] = $user_id;
			$params['user_type'] = $user_type;
			$params['fee'] = number_format($money, 2, '.', '');
			$params['title'] = "e袋洗订单";
            #浦发周边通需要地址
            if($pay_type == 17){
                $order_list = json_decode($_GET['order_list'], true);
                rsort($order_list);
                $params['tid'] = $order_id = $order_list[0];       #取最大的订单号吧
                    $order = $this->open_server->get_order($user_id, $order_id);
                $params['title'] = isset($_SESSION['psd_categoryname']) ? $_SESSION['psd_categoryname'] : $order['good'];
                $params['city_id'] = $order['city_id'];
                $params['address'] = $order['city'].$order['area_qu'].$order['address_qu'];
            }
			//session中存储支付信息
			$_SESSION['pay_species'] = 'order';
			$third_pay = $pay_model->thirdPay($params, $pay_type);

			if( is_ajax() ) {
				$ret_data['pay_url']    = $third_pay['pay_url'];
				$ret_data['success_url'] = $third_pay['success_url'];
				$this->retJson($ret_data);
			} 

			header("Cache-control: private");
			if (!empty($third_pay['url'])) {
				$this->jumpUrl($third_pay['url']);
			} else if (!empty($third_pay['html'])) {
				echo $third_pay['html'];
			} else {
				error_report('支付出错啦', create_url('order/order_list'));
			}
		} else {

			if( is_ajax() ) {
				$ret_data['status'] = false;
				$ret_data['msg']    = '支付出错啦';
				$this->retJson($ret_data);
			} 
			
			error_report('支付出错啦', create_url('order/order_list'));
			
		}
		
	}

	// 订单支付兑换优惠券
	public function ajax_order_exchange_coupon() {
	    global $_W, $_GPC;
	    $user_id = $this->user_info['user_id'];
	    $user_type = $this->user_info['user_type'];
	    $client_id = $this->user_info['client_id'];
	    $sn_code = $_GPC['bind_sn_coupon']; # 优惠券兑换码
	    //order_list为json格式字符串，　不能从$_GPC变量里取(会被转义)
		$order_list = isset($_GET['order_list']) ? $_GET['order_list'] : null;
	    $coupon_id = $_GPC['coupon_id'];
	    
	    $recharge_discount = $_GPC['recharge_discount'] ?: '';
	    
	    $resp = $this->open_server->bind_coupon($user_id, $sn_code);
	    if ($resp['error']) {
	        $result['state'] = 0;
	        $result['msg'] = $resp['error'];
	    } else {
	        $result['state'] = 1;
            $result['msg'] = '兑换成功';
            $result['url'] = create_url('payment/pay_coupon_list', array(
            		'order_list' => $order_list, 
            		'coupon_id' => $coupon_id,
            		'recharge_discount' => $recharge_discount
            ));
	    }
	    message($result, '', 'ajax');
	}
	
	// 个人中心兑换优惠券
	public function ajax_exchange_coupon() {
		global $_W, $_GPC;
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$client_id = $this->user_info['client_id'];
		$sn_code = $_GPC['bind_sn_coupon'];
		$resp = $this->open_server->bind_coupon($user_id, $sn_code);
		if ($resp['error']) {
			$result['state'] = 0;
			$result['msg'] = $resp['error'];
			message($result, '', 'ajax');
		} else {
			$result['state'] = 1;
			$result['msg'] = '兑换成功';
			message($result, '', 'ajax');
		}
	}
	
	// 会员充值
	public function recharge_pay() {
		global $_W, $_GPC;
		$user_id = intval($this->user_info['user_id']);
		$user_type = $this->user_info['user_type'];
		$from_user = $this->user_info['from_user'];
		
		// 引导充值--订单金额折扣提示
		$recharge_discount = $_GPC['recharge_discount'] ?: '';
		$recharge_callback = $_GPC['recharge_callback'] ?: '';
		
		$fee_1 = floatval($_GPC['fee']);	# 可选充值金额
		$fee_2 = floatval($_GPC['fee_2']);  # 手输充值金额

		$mobile = $_GPC['mobile'];	// 为他人充值的手机号

		if(empty($fee_1) && empty($fee_2)){
			header("Location: " . create_url('icard/icard_charge', array('status' => 1)));
			exit;
		}
		if ($fee_1) {
			$fee = number_format($fee_1, 2, '.', '');
		} else if ($fee_2) {
			$fee = number_format($fee_2, 2, '.', '');
		}

		$recharge_settings = $this->open_server->recharge_settings($user_type);
		$money_give = 0.00;	# 平台赠送金额
		foreach ($recharge_settings as $key => $value) {
			if ($fee >= $value['min'] && $fee < $value['max']) {
				$money_give = number_format($value['money_give'], 2, '.', '');
				break;
			}
		}

		//充值--优惠活动规则
		$types = 'recharge';
		//$activity_promotions = $this->open_server->activity_promotions($user_id, $user_type,$types);
		//$pay_activity = $this->format_activity_promotions($activity_promotions, $fee, 0, $types);

		$is_wx_browser = 0;

		if(false !== strpos( ',' . $_W['account']['payment']['wechat_h5']['user_type'] . ',', ',' . $this->user_info['user_type'] . ',') ) {
			$is_wx_browser = 1;	
		}

		//获取在线支付方式列表
		$paytype_lists = $this->open_server->get_online_paytype($user_type, $user_id);
		foreach ($paytype_lists['pay_list'] as $key => $value) {
			$paytype_lists['pay_list'][$key]['img'] = $_W['config']['pay_config'][$value['pay_type']]['img_url'];
			$paytype_lists['pay_list'][$key]['pay_name'] = $_W['config']['pay_config'][$value['pay_type']]['name'];
		}

		include $this->template('recharge_pay');
	}
	
	// 会员充值，支付确认中心
	public function icard_pay_center() {
		global $_W, $_GPC;
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$from_user = $this->user_info['from_user'];
		$paytype = $_GPC['paytype'];
		$money_give = $_GPC['money_give'];
		$fee = $_GPC['fee'];
		
		// 引导充值--订单金额折扣提示
		$recharge_discount = $_GPC['recharge_discount'] ?: '';
		$recharge_callback = $_GPC['recharge_callback'] ?: '';
		$tel = $_GPC['mobile'];	// 为他人充值手机号

		//充值--优惠活动规则
		$types = 'recharge';
		//$activity_promotions = $this->open_server->activity_promotions($user_id,$user_type, $types);
		//$pay_activity = $this->format_activity_promotions($activity_promotions, $fee, 0, $types);
		// $activity_info_id = isset($pay_activity[$paytype]['activity_info_id']) ? $pay_activity[$paytype]['activity_info_id'] : '';
		// 获取用户首页城市
		$user_city = get_user_city();
		$city_id = $user_city['city_id'];
		// URL mark
		$mark = get_mark();
		$res = $this->open_server->icard_recharge($user_id, $paytype, $fee, $tel, $city_id, $mark);
		$ret_data = array('status' => (isset($res['ret']) ? $res['ret'] : false),'msg' => ''); 
		if (!$res['ret']) {
			if( is_ajax() ) {
			   $ret_data['msg'] = $res['error'];
			   $this->retJson($ret_data); 
			}
			error_report($res['error']);
		} else {
			if($recharge_discount){
				$_SESSION['pay_species'] = 'discount';
				$_SESSION['recharge_discount'] = $recharge_discount;
				$_SESSION['recharge_callback'] = $recharge_callback;
			}else{
				//unset($_SESSION['recharge_discount'], $_SESSION['recharge_callback']);
				$_SESSION['pay_species'] = 'icard';
			}
			$_SESSION['charge_money'] = bcadd($money_give, $fee, 2);
			$data = $res['data'];
			$_W['account']['payment']['credit']['switch'] = 0;
			$params['tid'] = $data['trade_no'];
			$params['user'] = $user_id;
			$params['fee'] = $fee;
			$params['user_type'] = $user_type;
			$params['money_give'] = $money_give;
			$params['money_total'] = bcadd($money_give, $fee, 2);
			$params['title'] = $_W['account']['name'] . "用户充值{$fee}";
			//此处存储session，方便第三方支付时进行跳转判断
			if($paytype == 20){
				$_SESSION['cmb_online_recharge'] = 1;
			}
			$pay_model = Model::get('pay', $this->user_info);
			$third_pay = $pay_model->thirdPay($params, $paytype);

			if( is_ajax() ) {
				$ret_data['pay_url']    = $third_pay['pay_url'];
				$ret_data['success_url'] = $third_pay['success_url'];
				$this->retJson($ret_data);
			} 

			header("Cache-control: private");
			if (!empty($third_pay['url'])) {
				$this->jumpUrl($third_pay['url']);
			} else if (!empty($third_pay['html'])) {
				echo $third_pay['html'];
			} else {
				error_report('支付出错啦', create_url('order/order_list'));
			}

			/*header("Location: " . create_url('payment/pay', array(
				'paytype' => $paytype,
				'params' => base64_encode(json_encode($params))
			)));*/
			exit;
		}
	}
	
	// 调用第三方支付
	public function pay() {
		global $_W, $_GPC;
		$paytype = $_GPC['paytype'] ?: '';
		$params = @json_decode(base64_decode($_GPC['params']), true);
		if (! in_array($paytype, array(2, 6, 11))) {
			error_report('支付方式错误');
		}
		if (empty($params)) {
			error_report('支付参数错误');
		}
		
		require_once IA_ROOT . '/framework/model/payment.mod.php';
		if($paytype == 2){	# 微信支付
			$sl = base64_encode(json_encode($params));
			$auth = sha1($sl . $_W['weid'] . $_W['config']['setting']['authkey']);
			header("Location: {$_W['config']['site']['root']}payment/wechat/pay.php?weid={$_W['weid']}&auth={$auth}&ps={$sl}");
			exit();
		}else if ($paytype == 6) {	# 支付宝支付
			$params['user'] = $this->user_info['user_id'];
			$ret = alipay_build($params, $_W['account']['payment']['alipay']);
			if ($ret['html']) {
				echo $ret['html'];
				exit();
			}
			if ($ret['url']) {
				header("Location: " . $ret['url']);
				exit();
			}
		} else if($paytype == 11){	# 百度支付
			$params['user'] = $this->user_info['user_id'];
			$ret = baidu_build($params, $_W['account']['payment']['baidu']);
			if ($ret) {
				echo $ret;
				exit();
			}
		}else if ($paytype == 19) {	# 支付宝支付
			$params['user'] = $this->user_info['user_id'];
			$ret = nuomi_build($params, $_W['account']['payment']['nuomi']);
			if ($ret['html']) {
				echo $ret['html'];
				exit();
			}
			if ($ret['url']) {
				header("Location: " . $ret['url']);
				exit();
			}
		}
	}
	
	// 订单支付成功/余额充值成功
	public function pay_success() {
		global $_W, $_GPC;
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		$from_user = $this->user_info['from_user'];
		$type = $_GPC['type'];
		
		if($type == 'discount'){
			$recharge_discount = $_SESSION['recharge_discount'];
			$recharge_callback = $_SESSION['recharge_callback'];
			unset($_SESSION['recharge_discount'], $_SESSION['recharge_callback']);
			header('Location: ' . ($recharge_callback . '&recharge_discount=' . $recharge_discount));
			exit;
		}else if ($type == 'order') {
			$order_sn = $_SESSION['order_sn'];
			$order_fee = $_SESSION['order_fee'];
			unset($_SESSION['order_fee'], $_SESSION['order_sn']);
			header('Location: ' . create_url('order/order_list'));
			exit;
		}else if ($type == 'icard_charge') {
			unset($_SESSION['charge_money']);
			$online_charge_url = create_url('icard/icard_charge',
				array(
					'status' => '1'
				)
			);
			header('Location: ' . $online_charge_url);
			exit;
		}
	}
	
	// 个人中心优惠券列表
	public function coupon_list() {
		global $_W, $_GPC;
		$user_id = $this->user_info['user_id'];
		$user_type = $this->user_info['user_type'];
		// 优惠券数量描述
		$usable_coupon_info = $this->open_server->usable_coupon_info($user_id, $user_type, -1);
		$coupon_usable_count = $usable_coupon_info['count'];
		$coupon_desc = $usable_coupon_info['text'];
		// 优惠券列表
		$coupon_list = $this->open_server->get_coupons($user_id, $user_type);
		if(false === $coupon_list['ret']){
			$coupon_list = array();
		}
		include $this->template('coupon_list');
	}
	
	// 格式化支付活动信息
	/*
	public function format_activity_promotions($activity_promotions, $yingfu, $coupon_money=0, $types){
		$pay_activity = array();
		if($activity_promotions){
			foreach ($activity_promotions as $key => $val){
				$coupon_enabled = $activity_promotions[$key]['coupon_enabled'];
				$man_sum = $activity_promotions[$key]['activity_promotion_least_money'];
				$jian_sum = $activity_promotions[$key]['activity_promotion_money'];
				if($types == 'order'){
					if(floatval($yingfu) <= $man_sum){
						unset($activity_promotions[$key]);
					}else{
						if(floatval($coupon_money) > 0){
							if(!$coupon_enabled){
								unset($activity_promotions[$key]);
							}
						}
					}
				}else if($types == 'recharge'){
					if(floatval($yingfu) < $man_sum){
						//unset($activity_promotions[$key]);
					}
				}
				if($activity_promotions[$key]){
					$pay_activity[$val['activity_info_pay_type']] = $activity_promotions[$key];
				}
			}
		}
		return $pay_activity;
	}*/
	
	// 格式化订单支付按钮文本及状态
	public function format_paybtn($yingfu, $real_sum, $icard_sum, $paytype=''){
		$array = array();
		$array['btn_text'] = '确认支付￥' . $real_sum;
		$array['btn_status'] = '';
		$array['btn_class'] = '';
		switch($paytype){
			case '1':  //余额支付
				if($real_sum <= 0){
					$array['btn_text'] = '确 定';
					$array['btn_status'] = '';
					$array['btn_class'] = '';
				}else{
					if($icard_sum < $yingfu){
						$array['btn_text'] = '余额不足 请充值';
						$array['btn_status'] = "disabled='disabled'";
						$array['btn_class'] = 'gray-btn';
					}else{
						$array['btn_text'] = '确认余额支付￥' . $real_sum;
						$array['btn_status'] = '';
						$array['btn_class'] = '';
					}
				}
				break;
			case '3':  //现金支付
				$array['btn_text'] = '确认现金支付￥' . $real_sum;
				$array['btn_status'] = '';
				$array['btn_class'] = '';
				break;
			case '2':  //微信支付
			case '6':  //支付宝支付
			case '10': //奢护年卡支付
			case '11': //百度支付
				$array['btn_text'] = '需要支付￥' . $real_sum;
				$array['btn_status'] = '';
				$array['btn_class'] = '';
				break;
			default:
				if($paytype){
					$array['btn_text'] = '确认支付￥' . $real_sum;
					$array['btn_status'] = '';
					$array['btn_class'] = '';
				}else{
					$array['btn_text'] = '请选择支付方式';
					$array['btn_status'] = "disabled='disabled'";
					$array['btn_class'] = 'gray-btn';
				}
				break;
		}
		return $array;
	}
	
}
