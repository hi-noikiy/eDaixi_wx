<?php
/** 荣昌 OpenServer
* ____________________________________ */

class OpenServer {

  private $key = 'FO5Z6BIWV9';
  private $user_info;
  private $ip;
  // 本地调试
  // private $host = 'http://192.168.31.130:3003';

  private $host = 'http://open.edaixi.cn';
  // 测试环境
  // private $host = 'http://open.edaixi.cn';
  
  // 生产环境
  // private $host = 'http://open.edaixi.com';
  private $get_product_list = '/client/v1/get_product_list';    #never use
  private $get_product = '/client/v1/get_product';              #never use
  private $get_address_list = '/client/v5/get_address_list';
  private $get_order_list = '/client/v5/get_order_list';
  private $order_cancel_reasons = '/client/v5/order_cancel_reasons';
  //v1
  private $get_banner_list_v1 = '/client/v5/get_banner_list';    #never use
  //v2
  private $get_banner_list = '/client/v5/get_banner_list';
  private $get_func_button_list = '/client/v5/get_func_button_list';
  
  private $comment_text_list = '/client/v5/comment_text_list';   #never use
  //评价内容列表新
  private $comment_text_list_new = '/client/v5/comment_text_list';
  // 获取改衣评价选项
  private $tailor_comment_opts = '/client/v5/comment_text_list';
  private $get_order = '/client/v5/get_order';
  
  // 订单支付信息新接口
  private $order_pay_info = '/client/v5/order_pay_info';      #never use
  
  private $order_clothing = '/client/v5/order_clothing';
  private $order_delivery_status_list = '/client/v5/order_delivery_status_list';
  private $cancel_order = '/client/v5/cancel_order';
  private $delete_address = '/client/v5/delete_address';
  private $pay_order2 = '/client/v5/pay_order2';      #never use
  private $icard_recharge = '/client/v5/icard_recharge';
  private $icard_details = '/client/v5/icard_details';
 
  private $get_coupon_info = '/client/v5/get_coupon_info';
  private $get_coupons = '/client/v5/get_coupons';      
  private $get_baidu_coupons = '/client/v5/get_baidu_coupons';   
  private $get_two_coupons = '/client/v5/get_two_coupons';
  private $bind_coupon = '/client/v5/bind_coupon';
  private $get_coupon_outdated_time = '/client/v1/get_coupon_outdated_time';   #never use
  private $bind_member_card = '/client/v5/bind_member_card';
  private $bind_recharge = '/client/v5/bind_recharge';
  private $get_icard = '/client/v5/get_icard';
  private $get_extra_accounts = '/client/v1/get_extra_accounts';
  private $create_order = '/client/v5/create_order';
  private $create_qrcode_order = '/client/v5/create_qrcode_order';
  private $create_order_comment = '/client/v5/create_order_comment';
  private $create_address = '/client/v5/create_address';
  private $update_address = '/client/v5/update_address';
  private $verify_address = '/client/v5/verify_address';
  private $get_usable_address = '/client/v5/get_lanshou_address';
  private $get_kuaixi_area = '/client/v5/get_can_kuaixi_list';     #已废弃
  private $order_envelope_is_share = '/client/v5/order_envelope_is_share';

  private $recharge_settings = '/client/v5/recharge_settings';
  private $cities_options = '/client/v5/cities_options';
  private $get_paylog = '/client/v1/get_paylog';
  
  // 短信验证码
  private $send_sms = '/client/v5/send_sms';
  // 语音验证码
  private $send_voice_sms = '/client/v5/send_voice_sms';
  
  private $bind_user = '/client/v5/bind_user';
  private $get_clients = '/client/v5/get_clients';    #never use
  private $get_citys = '/client/v5/cities';
  private $bind_http_user = '/client/v5/bind_http_user';  
  private $get_city_delivery_fee = '/client/v1/get_city_delivery_fee';    #never use
  
  // 品类列表（老接口）
  private $get_banner_button_list = '/client/v5/get_banner_button_list';   #never use
  // 品类列表（新接口）
  private $get_category_buttons = '/client/v5/get_category_buttons';
  private $get_order_complain = '/client/v5/get_order_complaints';
  private $set_order_complain = '/client/v5/set_order_complaints';    #may never use
  //投诉细类（new接口）
  private $get_order_complain_category = '/client/v5/get_complaints';
  private $submit_order_complain = '/client/v5/submit_complaint';
  private $complain_supplement = '/client/v5/complaint_supplement';
  //获取万象优图token
  private $get_ci_upload_token = '/client/v5/get_upload_image_params';
  //获取积分商城URL
  private $get_mall_url = '/client/v5/integral_mall';
  //获取支付充值活动信息（百度支付满减等） 
  private $activity_promotions = '/client/v1/activity_promotions';   #已废弃
  //个人中心 客服 标语 
  private $user_center_info = '/client/v5/user_center_info';
  // 获取最近未计价的订单信息
  private $get_quick_order_info = '/client/v5/get_quick_order_info';    #never use
  // 获取最近未计价的订单信息
  private $get_qrcode_order_info = '/client/v5/get_qrcode_order_info';
  // 下单信息（新接口） -- 是否新用户/有无可用优惠券/其他品类推荐/运费说明
  private $create_order_page = '/client/v5/create_order_page';
  // 获取服务时段
  private $get_service_time = '/client/v5/get_service_time';
  // 获取可用优惠券信息
  private $usable_coupon_info = '/client/v5/get_order_available_coupons_count';
  // 获取选中支付方式
  private $get_default_paytype = '/client/v4/get_default_paytype';
  // 获取个人中心优惠券列表描述
  private $get_coupon_description = '/client/v4/get_coupon_description';   #never use
  // 获取酒店（列表）
  private $search_hotel = '/client/v5/search_hotel';
  // 获取获取意见反馈项
  private $get_feedback_types = '/client/v5/get_feedback_types';
  // 提交意见反馈信息
  private $set_feedback = '/client/v5/set_feedback';
  // 获取好评列表
  private $get_favourable_comments = '/client/v5/get_customer_praise';
  // 下单投保提示
  private $get_insurance_claims_info = '/client/v5/get_insurance_claims_info';
  // 订单支付引导充值
  private $order_discount = '/client/v5/get_recharge_amount';
  // 充值页区分新老用户
  private $recharge_prompt = '/client/v5/recharge_prompt';
  // 修改订单时间
  private $update_order_time = '/client/v5/change_delivery_time';
  // 获取折扣券订单价格
  private $discount_coupon_price = '/client/v5/caclulate_order_price';
  //获取某用户的所有可投诉订单（微信机器人-投诉）
  private $get_user_complain_orders = '/client/v5/get_complaint_orders';
  //获取用户e卡列表
  private $user_ecard_list = '/client/v5/user_ecard_list';
  //获取用户钱包接口
  private $user_wallet = '/client/v5/wallet';
  //获取用户待支付订单列表
  private $settlements_list = '/client/v5/settlements_list';
  //合并支付计算订单运费
  private $caclulate_delivery_fee = '/client/v5/caclulate_delivery_fee';
  //获取合并支付信息
  private $get_pay_info = '/client/v5/get_pay_info';
  //获取运费说明信息
  private $delivery_fee_info = '/client/v5/delivery_fee_info';
  //计算订单付款价格
  private $caclulate_order_price = '/client/v5/caclulate_order_price';
  //获取用户优惠券v5 已无用
  private $get_coupons_v5 = '/client/v5/get_coupons';      #think before think
  //订单支付接口
  private $pay_order_v5 = '/client/v5/pay_order';
  //订单实付款详情
  private $order_detail_paid_info = '/client/v5/order_detail_paid_info';
  //获取衣物实付款信息
  private $order_detail_clothes_paid_info = '/client/v5/order_detail_clothes_paid_info';
  //订单取消支付接口
  private $cancel_pay = '/client/v5/cancel_pay';
  // 首页获取爆品接口
  private $get_promotional_offers = '/client/v5/get_promotional_offers';
  // 普洗价目页接口
  private $get_normal_categories_price = '/client/v5/get_normal_categories_price';
  // 原create_order_page接口升级版,支持多个分类
  private $hybrid_order_page = '/client/v5/hybrid_order_page';
  // 新的获取充值优惠的接口
  private $get_recharge_info = '/client/v5/get_recharge_info';
  // 为他人充值充值记录
  private $recharge_details_for_others = '/client/v5/recharge_details_for_others';
  // 检查手机号码是否可以充值
  private $can_recharge_for_tel = '/client/v5/can_recharge_for_tel';
  // 开发票接口
  private $create_invoice = '/client/v5/create_invoice';
  // 获取可开发票列表
  private $get_invoice_details = '/client/v5/get_invoice_details';
  // 开发票页面
  private $get_invoice_page = '/client/v5/get_invoice_page';
  // 开发票记录
  private $invoice_history = '/client/v5/invoice_history';

  // 酒店搜索
  private $search_office_building = '/client/v5/search_office_building';
  // 写字楼快洗下单说明
  private $create_office_order = '/client/v5/create_order';
  // 写字楼快洗价目页
  private $get_price_by_category_id = '/client/v5/get_price_by_category_id';
  // 写字楼快洗下单页
  private $get_order_page = '/client/v5/get_order_page';

  //第三方支付api
  private $payment_sign = '/client/v5/payment_sign';

  //通过android和app的session_id，获取用户信息，用于app联合登录h5
  private $get_user_by_sessionid = '/client/v5/get_user_session_info';

  // 获取洗衣液销量库存
  private $get_good_stock_count = '/client/v5/get_good_stock_count';
  // 获取洗衣液下单页
  private $get_physical_order_page = '/client/v5/get_physical_order_page';
  // 获取洗衣液下单
  private $create_physical_order = '/client/v5/create_physical_order';
  // 取消洗衣液订单
  private $cancel_physical_order = '/client/v5/cancel_physical_order';
  // 洗衣液订单支付
  private $pay_physical_order = '/client/v5/pay_physical_order';
  // 洗衣液确认收货
  private $physical_order_confirm_receipt = '/client/v5/physical_order_confirm_receipt';
  // 洗衣液订单列表
  private $get_physical_order_lists = '/client/v5/get_physical_order_lists';
  // 洗衣液订单详情
  private $get_physical_order_info = '/client/v5/get_physical_order_info';

  //在线支付方式获取
  private $get_online_paytype = '/client/v5/online_charge_pay_info';

  //根据order_id获取该次交易流水号
  private $get_trade_fee_by_orderid = '/client/v5/combine_orders_pay_info';

  //根据fan_id获取用户之前的企业发票信息，方便auto_complete出纳税人识别号和抬头
  private $get_invoice_taxid_history = '/client/v5/companies';
  
  function __construct($config,$user_info = null) {
    $this->host = $config['edaixi']['open_server'];
    $this->user_info = $user_info;
    $this->ip = getip();
  }

  /**
   * 洗衣液订单详情
   */
  public function get_physical_order_info($user_id, $physical_order_id) {
    $params = array(
      'user_id' => $user_id,
      'physical_order_id' => $physical_order_id,
      );
    $params = $this->sign($params);
    $param_str = http_build_query($params);
    $resp = ihttp_get($this->host.$this->get_physical_order_info.'?'.$param_str);
    return $this->handle_response($resp);
  }    
  /**
   * 洗衣液订单列表
   # 0：未完成，1：已完成，2：all
   */
  public function get_physical_order_lists($user_id, $type, $page, $per_page) {
    $params = array(
      'user_id' => $user_id,
      'type' => $type,
      'page' => $page,
      'per_page' => $per_page,
      );
    $params = $this->sign($params);
    $param_str = http_build_query($params);
    $resp = ihttp_get($this->host.$this->get_physical_order_lists.'?'.$param_str);
    return $this->handle_response($resp);
  }  
  /**
   * 洗衣液确认收货
   */
  public function physical_order_confirm_receipt($user_id, $physical_order_id) {
    $params = array(
      'user_id' => $user_id,
      'physical_order_id' => $physical_order_id,
      );
    $params = $this->sign($params);
    $resp = ihttp_post($this->host . $this->physical_order_confirm_receipt, $this->urlEncode($params));
    return $this->handle_response($resp);
  }  

  /**
   * 洗衣液订单支付
   */
  public function pay_physical_order($user_id, $physical_order_id, $pay_type) {
    $params = array(
      'user_id' => $user_id,
      'physical_order_id' => $physical_order_id,
      'pay_type' => $pay_type,
      );
    $params = $this->sign($params);
    $resp = ihttp_post($this->host . $this->pay_physical_order, $this->urlEncode($params));
    return $this->handle_response($resp);
  }  

  /**
   * 取消洗衣液订单
   */
  public function cancel_physical_order($user_id, $physical_order_id) {
    $params = array(
      'user_id' => $user_id,
      'physical_order_id' => $physical_order_id,
      );
    $params = $this->sign($params);
    $resp = ihttp_post($this->host . $this->cancel_physical_order, $this->urlEncode($params));
    return $this->handle_response($resp);
  }  

  /**
   * 洗衣液下单
   */
  public function create_physical_order($city_id, $user_id, $user_type, $address_id, $count, $good_id=1, $remark='') {
    $params = array(
      'city_id' => $city_id,
      'user_id' => $user_id,
      'user_type' => $user_type,
      'address_id' => $address_id,
      'count' => $count,
      'good_id' => $good_id,
      'remark' => $remark,
      );
    $params = $this->sign($params);
    $resp = ihttp_post($this->host . $this->create_physical_order, $this->urlEncode($params));
    return $this->handle_response($resp);
  }

  /**
   * 洗衣液下单页
   */
  public function get_physical_order_page($user_id, $user_type, $city_id, $good_id=1) {
    $params = array(
      'user_id' => $user_id,
      'user_type' => $user_type,
      'city_id' => $city_id,
      'good_id' => $good_id,
      );
    $params = $this->sign($params);
    $param_str = http_build_query($params);
    $resp = ihttp_get($this->host.$this->get_physical_order_page.'?'.$param_str);
    return $this->handle_response($resp);
  }

  /**
   * 洗衣液价格和库存
   */
  public function get_good_stock_count($good_id=1) {
    $params = array(
      'good_id' => $good_id,
      );
    $params = $this->sign($params);
    $param_str = http_build_query($params);
    $resp = ihttp_get($this->host.$this->get_good_stock_count.'?'.$param_str);
    return $this->handle_response($resp);
  }

  /**
   * 洗衣液订单详情
   */
  public function get_online_paytype($user_type, $user_id='', $activity_id='') {
    $params = array(
      'user_type' => $user_type,
      'user_id' => $user_id,
      'activity_id' => $activity_id
      );
    $params = $this->sign($params);
    $param_str = http_build_query($params);
    $resp = ihttp_get($this->host.$this->get_online_paytype.'?'.$param_str);
    return $this->handle_response($resp);
  } 

  /**
   * 写字楼快洗价目页
   * category_id Integer true  写字楼快洗为17
   */
  public function get_order_page($user_id, $city_id, $user_type, $category_id) {
    $params = array(
      'user_id' => $user_id,
      'city_id' => $city_id,
      'user_type' => $user_type,
      'category_id' => $category_id,
      );
    $params = $this->sign($params);
    $param_str = http_build_query($params);
    $resp = ihttp_get($this->host.$this->get_order_page.'?'.$param_str);
    return $this->handle_response($resp);
  }

  /**
   * 写字楼快洗下单页
   * category_id Integer true  写字楼快洗为17
   */
  public function get_price_by_category_id($user_id, $city_id, $user_type, $category_id) {
    $params = array(
      'user_id' => $user_id,
      'city_id' => $city_id,
      'user_type' => $user_type,
      'category_id' => $category_id,
      );
    $params = $this->sign($params);
    $param_str = http_build_query($params);
    $resp = ihttp_get($this->host.$this->get_price_by_category_id.'?'.$param_str);
    return $this->handle_response($resp);
  }

  /**
   * 写字楼快洗下单
   * category_id 17
   */
  public function create_office_order($user_id, $user_type, $category_id, $time_range, $order_date, $order_time, $office_building_id, $tel, $user_name, $room, $comment='', $mark='') {
    $params = array(
      'user_id' => $user_id,
      'user_type' => $user_type,
      'category_id' => $category_id,
      'time_range' => $time_range,
      'order_date' => $order_date,
      'order_time' => $order_time,
      'office_building_id' => $office_building_id,
      'tel' => $tel,
      'user_name' => $user_name,
      'room' => $room,
      'comment' => $comment,
      'mark' => $mark,
      );
    $params = $this->sign($params);
    $resp = ihttp_post($this->host . $this->create_office_order, $this->urlEncode($params));
    return $this->handle_response($resp);
  }

  /**
   * 写字楼快洗搜索
   *  search_type integer 0获取定位地址的写字楼，1为文字搜索，2获取附近7个写字楼和该城市所有写字楼
   */
  public function search_office_building($user_id, $city_id, $user_type, $search_type, $page='', $per_page='', $search_text='', $lat='', $lng='') {
    $params = array(
      'user_id' => $user_id,
      'city_id' => $city_id,
      'user_type' => $user_type,
      'search_type' => $search_type,
      'page' => $page,
      'per_page' => $per_page,
      'search_text' => $search_text,
      'lat' => $lat,
      'lng' => $lng,
      );
    $params = $this->sign($params);
    $param_str = http_build_query($params);
    $resp = ihttp_get($this->host.$this->search_office_building.'?'.$param_str);
    return $this->handle_response($resp);
  }

  /**
   * 开发票接口
   */
  public function create_invoice($user_id, $details, $invoice_type, $company_name, $invoice_content, $receiver_name, $receiver_tel, $tax_id, $province, $city, $area, $address, $comment='', $city_id='', $mark='') {
    $params = array(
      'user_id' => $user_id,
      'details' => $details,
      'invoice_type' => $invoice_type,
      'company_name' => $company_name,
      'invoice_content' => $invoice_content,
      'receiver_name' => $receiver_name,
      'receiver_tel' => $receiver_tel,
      'tax_id' => $tax_id,
      'province' => $province,
      'city' => $city,
      'area' => $area,
      'address' => $address,
      'comment' => $comment,
      'city_id' => $city_id,
      'mark' => $mark,
      );
    $params = $this->sign($params);
    $resp = ihttp_post($this->host . $this->create_invoice, $this->urlEncode($params));
    return $this->handle_response($resp);
  }    
  /**
   * 开发票记录
   */
  public function invoice_history($user_id, $city_id='', $mark='') {
    $params = array(
      'user_id' => $user_id,
      'city_id' => $city_id,
      'mark' => $mark,
      );
    $params = $this->sign($params);
    $param_str = http_build_query($params);
    $resp = ihttp_get($this->host.$this->invoice_history.'?'.$param_str);
    return $this->handle_response($resp);
  }     
  /**
   * 开发票页面
   */
  public function get_invoice_page($user_id, $city_id='', $mark='') {
    $params = array(
      'user_id' => $user_id,
      'city_id' => $city_id,
      'mark' => $mark,
      );
    $params = $this->sign($params);
    $param_str = http_build_query($params);
    $resp = ihttp_get($this->host.$this->get_invoice_page.'?'.$param_str);
    return $this->handle_response($resp);
  }      
  /**
   * 获取可开发票列表
   */
  public function get_invoice_details($user_id, $city_id='', $mark='') {
    $params = array(
      'user_id' => $user_id,
      'city_id' => $city_id,
      'mark' => $mark,
      );
    $params = $this->sign($params);
    $param_str = http_build_query($params);
    $resp = ihttp_get($this->host.$this->get_invoice_details.'?'.$param_str);
    return $this->handle_response($resp);
  }      
  /**
   * 检查手机号码是否注册
   */
  public function can_recharge_for_tel($user_id, $tel) {
    $params = array(
      'user_id' => $user_id,
      'tel' => $tel,
      );
    $params = $this->sign($params);
    $param_str = http_build_query($params);
    $resp = ihttp_get($this->host.$this->can_recharge_for_tel.'?'.$param_str);
    return $this->handle_response($resp);
  }  
  /**
   * 为他人充值充值记录
   */
  public function recharge_details_for_others($user_id, $page, $per_page, $city_id='', $mark='') {
    $params = array(
      'user_id' => $user_id,
      'page' => $page,
      'per_page' => $per_page,
      'city_id' => $city_id,
      'mark' => $mark,
      );
    $params = $this->sign($params);
    $param_str = http_build_query($params);
    $resp = ihttp_get($this->host.$this->recharge_details_for_others.'?'.$param_str);
    return $this->handle_response($resp);
  }  
  /**
   * 原create_order_page接口升级版,支持多个分类
   * category_id 普洗传-1
   */
  public function get_recharge_info($city_id = '') {
    $params = array('city_id' => $city_id);
    $params = $this->sign($params);
    $param_str = http_build_query($params);
    $resp = ihttp_get($this->host.$this->get_recharge_info.'?'.$param_str);
    return $this->handle_response($resp);
  }  
  /**
   * 原create_order_page接口升级版,支持多个分类
   * category_id 普洗传-1
   */
  public function hybrid_order_page($city_id, $user_type, $category_id = -1, $user_id = '') {
    $params = array('city_id' => $city_id, 'user_type' => $user_type, 'category_id' => $category_id, 'user_id' => $user_id);
    $params = $this->sign($params);
    $param_str = http_build_query($params);
    $resp = ihttp_get($this->host.$this->hybrid_order_page.'?'.$param_str);
    return $this->handle_response($resp);
  }
  /**
   * 首页获取爆品接口
   */
  public function get_promotional_offers($city_id, $user_type, $page, $per_page, $user_id = '') {
    $params = array('city_id' => $city_id, 'user_type' => $user_type, 'page' => $page, 'per_page' => $per_page, 'user_id' => $user_id);
    $params = $this->sign($params);
    $param_str = http_build_query($params);
    $resp = ihttp_get($this->host.$this->get_promotional_offers.'?'.$param_str);
    return $this->handle_response($resp);
  }

  /**
   * 普洗价目页接口
   */
  public function get_normal_categories_price($city_id, $user_type, $user_id = '', $mark = '') {
    $params = array('city_id' => $city_id, 'user_type' => $user_type, 'mark' => $mark, 'per_page' => $per_page, 'user_id' => $user_id);
    $params = $this->sign($params);
    $param_str = http_build_query($params);
    $resp = ihttp_get($this->host.$this->get_normal_categories_price.'?'.$param_str);
    return $this->handle_response($resp);
  }
  
  // 获取折扣券订单价格
  function discount_coupon_price($user_id, $order_id, $paytype, $coupon_id, $activity_info_id = 0, $city_id = '',$mark = ''){
  	$para = array(
  			'user_id' => $user_id, 
  			'order_id' => $order_id, 
  			'paytype' => $paytype, 
  			'coupon_id' => $coupon_id, 
  			'activity_info_id' => $activity_info_id, 
  			'city_id' => $city_id, 
  			'mark'=>$mark
  	);
  	$para = $this->sign($para);
  	$param_str = $this->createLinkstringUrlencode($para);
  	$resp = ihttp_get($this->host . $this->discount_coupon_price . '?' . $param_str);
  	return $this->handle_response($resp);
  	
  }
  
  // 修改订单时间
  function update_order_time($user_id, $order_id, $date, $time, $soon, $flag='qu'){
  	$para = $this->sign(array(
  			'user_id' => $user_id,
  			'order_id' => $order_id,
  			'new_date' => $date,
  			'new_time' => $time,
  			'asap' => $soon,
  			'direction' => $flag
  	));
  	
  	$resp = ihttp_post($this->host . $this->update_order_time, $this->urlEncode($para));
  	return $this ->handle_operate($resp);
  }
  
  // 充值页区分新老用户
  function recharge_prompt($user_id){
  	$para = array('user_id' => $user_id);
  	$para = $this->sign($para);
  	$param_str = $this->createLinkstringUrlencode($para);
  	$resp = ihttp_get($this->host . $this->recharge_prompt . '?' . $param_str);
  	return $this->handle_response($resp);
  }
  
  // 订单支付引导充值
  function order_discount($order_price){
  	$para = array('money' => $order_price);
  	$para = $this->sign($para);
  	$param_str = $this->createLinkstringUrlencode($para);
  	$resp = ihttp_get($this->host . $this->order_discount . '?' . $param_str);
  	return $this->handle_response($resp);
  }
  
  // 下单投保提示
  function get_insurance_claims_info($city_id){
  	$para = array('city_id' => $city_id);
  	$para = $this->sign($para);
  	$param_str = $this->createLinkstringUrlencode($para);
  	$resp = ihttp_get($this->host . $this->get_insurance_claims_info . '?' . $param_str);
  	return $this->handle_response($resp);
  }
  
  // 获取好评列表
  function get_favourable_comments($random=true, $page=1, $per_page=3, $city_id=''){
  	$para = array('random' => intval($random), 'page' => $page, 'per_page' => $per_page, 'city_id' => $city_id);
  	$para = $this->sign($para);
  	$param_str = $this->createLinkstringUrlencode($para);
  	$resp = ihttp_get($this->host . $this->get_favourable_comments . '?' . $param_str);
  	return $this->handle_response($resp);
  }
  
  // 获取获取意见反馈项
  function get_feedback_types($user_id, $user_type, $city_id='', $mark=''){
  	$para = array('user_id' => $user_id, 'user_type' => $user_type, 'city_id' => $city_id, 'mark'=>$mark);
  	$para = $this->sign($para);
  	$param_str = $this->createLinkstringUrlencode($para);
  	$resp = ihttp_get($this->host . $this->get_feedback_types . '?' . $param_str);
  	return $this->handle_response($resp);
  }
  
  // 提交意见反馈信息
  function set_feedback($user_id, $user_type, $feedback_type, $feedback_content, $city_id='', $mark=''){
    $para = $this->sign(array(
	    		'user_id' => $user_id, 
	    		'user_type' => $user_type, 
	    		'feedback_type' => $feedback_type, 
	    		'feedback_content' => $feedback_content, 
	    		'city_id' => $city_id, 
	    		'mark'=>$mark
    		));
  	$resp = ihttp_post($this -> host . $this->set_feedback, $this->urlEncode($para));
  	return $this ->handle_operate($resp);
  }
  
  // 获取酒店（列表）
  function search_hotel($user_id, $user_type, $city_id, $search_type, $page=1, $per_page=1, $search_text='', $lat='', $lng=''){
      if(!($user_id || $user_type || $city_id || $search_type)){
          header('Location: ' . create_url('homepage/index'));
          exit;
      }
	  $para = array(
          'user_id' => $user_id,
          'user_type' => $user_type,
          'city_id' => $city_id,
          'search_type' => $search_type,
          'page' => $page,
          'per_page' => $per_page,
          'search_text' => $search_text,
          'lat' => $lat,
          'lng' => $lng
      );
      $para = $this->sign($para);
      $param_str = $this->createLinkstringUrlencode($para);
      
      //dump($this->host . $this->search_hotel . '?' . $param_str);
      
      $resp = ihttp_get($this->host . $this->search_hotel . '?' . $param_str);
      return $this->handle_response($resp);
  }
  
  // 获取个人中心优惠券列表描述
  function get_coupon_description($user_id){
  	if(empty($user_id)){
  		header('Location: ' . create_url('homepage/index'));
  		exit;
  	}
  	$para = array('user_id' => $user_id);
  	$para = $this->sign($para);
  	$param_str = $this->createLinkstringUrlencode($para);
  	$resp = ihttp_get($this->host . $this->get_coupon_description . '?' . $param_str);
  	return $this->handle_response($resp);
  }

  // 获取可用优惠券信息
  function usable_coupon_info($user_id, $user_type, $order_id=-1){
  	if(empty($user_id) || empty($user_type)){
  		header('Location: ' . create_url('homepage/index'));
  		exit;
  	}
  	$para = array('user_id' => $user_id, 'user_type' => $user_type, 'order_id' => $order_id);
  	$para = $this->sign($para);
  	$param_str = $this->createLinkstringUrlencode($para);
  	$resp = ihttp_get($this->host . $this->usable_coupon_info . '?' . $param_str);
  	return $this->handle_response($resp);
  }
  
  // 获取选中支付方式
  function get_default_paytype($user_id, $user_type, $order_id, $category_id, $order_price, $coupon_id=''){
  	if(empty($user_id) || empty($user_type) || empty($order_id) || empty($category_id) || !isset($order_price)){
  		header('Location: ' . create_url('homepage/index'));
  		exit;
  	}
  	$para = array('user_id' => $user_id, 'user_type' => $user_type, 'category_id' => $category_id, 'order_price' => $order_price, 'coupon_id' => $coupon_id);
  	$para = $this->sign($para);
  	$param_str = $this->createLinkstringUrlencode($para);
  	$resp = ihttp_get($this->host . $this->get_default_paytype . '?' . $param_str);
  	return $this->handle_response($resp);

  }
  
  // 获取服务时段
  function get_service_time($category_id, $city_id, $area='', $order_id='', $area_id='', $sub_category_ids = ''){
      if(empty($category_id) || empty($city_id)){
          header('Location: ' . create_url('homepage/index'));
          exit;
      }
      $para = array( 'category_id' => $category_id, 'city_id' => $city_id, 'area' => $area, 'order_id' => $order_id, 'area_id' => $area_id, 'sub_category_ids' => $sub_category_ids);
      $para = $this->sign($para);
      $param_str = $this->createLinkstringUrlencode($para);
      $resp = ihttp_get($this->host . $this->get_service_time . '?' . $param_str);
      return $this->handle_response($resp);
  }
  
  // 下单信息（新接口） -- 是否新用户/有无可用优惠券/其他品类推荐/运费说明
  function create_order_page($city_id, $user_type, $category_id, $user_id, $sub_id='', $address_id='', $order_id=''){
    if(empty($city_id) || empty($user_type) || empty($category_id) || empty($user_id) ){
      header('Location: ' . create_url('homepage/index'));
      exit;
    }
    if(!$address_id){ // 传 $order_id 参数时,必须同时传入 $address_id
    	$order_id = '';
    }
  	$para = array('city_id' => $city_id, 'user_type' => $user_type, 'category_id' => $category_id, 'sub_id' => $sub_id, 'user_id' => $user_id, 'address_id'=>$address_id, 'order_id' => $order_id);
  	$para = $this->sign($para);
  	$param_str = $this->createLinkstringUrlencode($para);
  	$resp = ihttp_get($this->host . $this->create_order_page . '?' . $param_str);
  	return $this->handle_response($resp);
  }
  
  /**
   * 获取继续下单填充信息:
   * 	填充地址--参照按钮所在订单
   * 	填充时间--最近未计价的订单
   * 参数说明:
   * 	user_id		用户ID
   * 	order_id    "继续下单"按钮所在订单的订单ID
   * 	category_id "继续下单"按钮所在订单的品类ID
   * 	city_id		首页城市ID(与"继续下单"按钮所在订单无关)
   * */
  function get_quick_order_info($user_id, $order_id, $category_id, $city_id){
  	$para = array('user_id' => $user_id, 'order_id' => $order_id, 'category_id' => $category_id, 'city_id' => $city_id);
  	$para = $this->sign($para);
  	$param_str = $this->createLinkstringUrlencode($para);
  	$resp = ihttp_get($this->host . $this->get_quick_order_info . '?' . $param_str);
  	return $this->handle_response($resp);
  }
  
  // 获取最近未计价的订单信息
  function get_qrcode_order_info($user_id, $qrid){
  	$para = array('user_id' => $user_id, 'qrcode_order_id' => $qrid);
  	$para = $this->sign($para);
  	$param_str = $this->createLinkstringUrlencode($para);
  	$resp = ihttp_get($this->host . $this->get_qrcode_order_info . '?' . $param_str);
  	return $this->handle_response($resp);
  }
  
  //个人中心 客服 标语 
  function user_center_info($user_id, $user_type){
  	$para = array('user_id' => $user_id, 'user_type' => $user_type);
  	$para = $this->sign($para);
  	$param_str = $this->createLinkstringUrlencode($para);
  	$resp = ihttp_get($this->host . $this->user_center_info . '?' . $param_str);
  	return $this->handle_response($resp);
  }
  
  //获取支付充值活动信息（百度支付满减等） 
  function activity_promotions($user_id,$user_type, $types){
  	$para = array('user_id' => $user_id, 'user_type'=>$user_type,'types' => $types);
  	$para = $this->sign($para);
  	$param_str = $this->createLinkstringUrlencode($para);
  	$resp = ihttp_get($this->host . $this->activity_promotions . '?' . $param_str);
  	return $this->handle_response($resp);
  }
  
  // 获取积分商城URL
  function get_mall_url($user_id){
  	$para = array('user_id' => $user_id);
  	$para = $this->sign($para);
  	$param_str = $this->createLinkstringUrlencode($para);
  	$resp = ihttp_get($this->host . $this->get_mall_url . '?' . $param_str);
  	return $this->handle_response($resp);
  }
  
  // 取消订单原因
  function order_cancel_reasons(){
    $para = array();
    $para = $this->sign($para);
    $param_str = $this->createLinkstringUrlencode($para);
    $resp = ihttp_get($this->host.$this->order_cancel_reasons.'?'.$param_str);
    return $this->handle_response($resp);
  } 
  function get_product_list(){
    $para = array();
    $para = $this->sign($para);
    $param_str = $this->createLinkstringUrlencode($para);
    $resp = ihttp_get($this->host.$this->get_product_list.'?'.$param_str);
    return $this->handle_response($resp);
  }
function get_city_delivery_fee($city_id,$category_id){
       $para = array('city_id' => $city_id,'category_id' => $category_id);
       $para = $this->sign($para);
       $param_str = $this->createLinkstringUrlencode($para);
       $resp = ihttp_get($this->host.$this->get_city_delivery_fee.'?'.$param_str);
       return $this->handle_response($resp);
  }
function comment_text_list(){
    $para = array();
    $para = $this->sign($para);
    $param_str = $this->createLinkstringUrlencode($para);
    $resp = ihttp_get($this->host.$this->comment_text_list.'?'.$param_str);
    return $this->handle_response($resp);
  }
  function comment_text_list_new(){
    $para = array();
    $para = $this->sign($para);
    $param_str = $this->createLinkstringUrlencode($para);
    $resp = ihttp_get($this->host.$this->comment_text_list_new.'?'.$param_str);
    return $this->handle_response($resp);
  }
  
  // 获取改衣评价选项
  function get_tailor_comment_opts($category_id){
    $para = array('category_id' => $category_id);
  	$para = $this->sign($para);
  	$param_str = $this->createLinkstringUrlencode($para);
  	$resp = ihttp_get($this->host. $this->tailor_comment_opts . '?' . $param_str);
  	return $this->handle_response($resp);
  }
  
  function get_citys(){
    $para = array();
    $para = $this->sign($para);
    $param_str = $this->createLinkstringUrlencode($para);
    $resp = ihttp_get($this->host.$this->get_citys.'?'.$param_str);
    return $this->handle_response($resp);
  }

  function get_product($product_id){
    $para = array('product_id' => $product_id);
    $para = $this->sign($para);
    $param_str = $this->createLinkstringUrlencode($para);
    $resp = ihttp_get($this->host.$this->get_product.'?'.$param_str);
    return $this->handle_response($resp);
  }

  function recharge_settings($user_type){
    $para = array('product_id' => $user_type);
    $para = $this->sign($para);
    $param_str = $this->createLinkstringUrlencode($para);
    $resp = ihttp_get($this->host.$this->recharge_settings.'?'.$param_str);
    return $this->handle_response($resp);
  }

  // 获取当前城市可快洗的区域
  function get_kuaixi_area($city_id){
    if(empty($city_id) || !is_numeric($city_id) || $city_id < 0 ){
      header('Location: ' . create_url('homepage/index'));
      exit;
    }
    $para = array('city_id' => $city_id);
    $para = $this->sign($para);
    $param_str = $this->createLinkstringUrlencode($para);
    $resp = ihttp_get($this->host . $this->get_kuaixi_area . '?' . $param_str);
    $resp['content'] = UrlDecode($resp['content']);
    return $this->handle_response($resp);
  }
  
  function  get_banner_list_v1()
  {
    $para = array();
    $para = $this->sign($para);
    $param_str = $this->createLinkstringUrlencode($para);
    $resp = ihttp_get($this->host.$this->get_banner_list_v1.'?'.$param_str);
    return $this->handle_response($resp);
  }
  
  function  get_banner_list($banner_width,$banner_height,$user_type,$mark,$city_id,$user_id)
  {
    $para = array('banner_width' => $banner_width,'banner_height'=>$banner_height,'user_type'=>$user_type,'mark'=>$mark,'city_id'=>$city_id,'user_id' =>$user_id);
    $para = $this->sign($para);
    $param_str = $this->createLinkstringUrlencode($para);
    $resp = ihttp_get($this->host.$this->get_banner_list.'?'.$param_str);
    return $this->handle_response($resp);
  }

  function  get_func_button_list($banner_width,$banner_height,$user_type,$mark,$city_id,$user_id)
  {
    $para = array('banner_width' => $banner_width,'banner_height'=>$banner_height,'user_type'=>$user_type,'mark'=>$mark,'city_id'=>$city_id,'user_id' =>$user_id);
    $para = $this->sign($para);
    $param_str = $this->createLinkstringUrlencode($para);
    $resp = ihttp_get($this->host.$this->get_func_button_list.'?'.$param_str);
    return $this->handle_response($resp);
  }
  
  // 品类列表（老接口）
  function  get_banner_button_list($banner_width, $banner_height, $user_type, $mark, $city_id, $user_id, $quick_order=false){
    $para = array('banner_width' => $banner_width, 'banner_height'=>$banner_height, 'user_type'=>$user_type, 'mark'=>$mark, 'city_id'=>$city_id, 'user_id'=>$user_id, 'quick_order'=>$quick_order);
    $para = $this->sign($para);
    $param_str = $this->createLinkstringUrlencode($para);
    $resp = ihttp_get($this->host.$this->get_banner_button_list.'?'.$param_str);
    return $this->handle_response($resp);
  }
  
  // 品类列表（新接口）
  function  get_category_buttons($city_id, $user_type, $mark, $user_id='', $quick_order=false){
  	$para = array('city_id'=>$city_id, 'user_type'=>$user_type, 'mark'=>$mark, 'user_id'=>$user_id, 'quick_order'=>$quick_order);
  	$para = $this->sign($para);
  	$param_str = $this->createLinkstringUrlencode($para);
  	$resp = ihttp_get($this->host . $this->get_category_buttons . '?' . $param_str);
  	return $this->handle_response($resp);
  }

  // 优惠券总数/过期提醒（未开通）
  function get_coupon_info($user_id){
    $para = $this->sign(array('user_id' => $user_id));
    $param_str = $this->createLinkstringUrlencode($para);
    $resp = ihttp_get($this->host . $this->get_coupon_info . '?' . $param_str);
    return $this->handle_response($resp);
  }
  
  // 获取页优惠券列表
  function get_coupons($user_id, $user_type, $order_group_ids='', $status='', $coupons_count=''){
    $para = array(
        'user_id' => $user_id,
        'user_type' => $user_type,
        'order_group_ids' => $order_group_ids,
        'status' => $status,
        'coupons_count' => $coupons_count,
        );
    $para = $this->sign($para);
    $param_str = $this->createLinkstringUrlencode($para);
    if($user_type == 13){
    	$resp = ihttp_get($this->host . $this->get_baidu_coupons . '?' . $param_str);
    }else{
    	$resp = ihttp_get($this->host . $this->get_coupons . '?' . $param_str);
    }
    return $this->handle_response($resp);
  }
  
  // 获取符合条件的两张优惠券，引导用户下单
  function get_two_coupons($user_id, $user_type, $city_id, $count=2){
    $para = array('user_id' => $user_id, 'user_type' => $user_type, 'city_id' => $city_id, 'coupon_count'=>$count);
    $para = $this->sign($para);
    $param_str = $this->createLinkstringUrlencode($para);
    $resp = ihttp_get($this->host . $this->get_two_coupons . '?' . $param_str);
    return $this->handle_response($resp);
  }
    
  function get_coupon_outdated_time(){
      $para = array();
      $para = $this->sign($para);
      $param_str = $this->createLinkstringUrlencode($para);
      $resp = ihttp_get($this->host.$this->get_coupon_outdated_time.'?'.$param_str);
      return $this->handle_response($resp);
  }

  // 交易（余额）详情
  function get_icard_details($user_id, $page){
    $page = max(1, $page ? $page : 1);
    $para = $this->sign(array(
            'user_id' => $user_id,
            'page' => $page
        )
    );
    $param_str = $this->createLinkstringUrlencode($para);
    $resp = ihttp_get($this->host. $this->icard_details . '?' . $param_str);
    return $this->handle_response($resp);
  }
  
  //获取订单投诉状态
  function get_order_complain($order_id){
    $para = $this->sign(array(
            'order_id' => $order_id
        )
    );
    $param_str = $this->createLinkstringUrlencode($para);
    $resp = ihttp_get($this->host. $this->get_order_complain . '?' . $param_str);
    return $this->handle_response($resp);
  }

  //获取订单投诉细类的分类
  function get_order_complain_category($order_id){
    $para = $this->sign(array(
            'order_id'=>$order_id
            )
    );
    $param_str = $this->createLinkstringUrlencode($para);
    $resp = ihttp_get($this->host. $this->get_order_complain_category . '?' . $param_str);
    return $this->handle_response($resp);
  }

  //设置订单投诉细类的投诉状态
  function set_order_complain_category($user_id,$order_id,$terminal,$entrance,$parent_complain_id,$complain_id,$category_content,$imgs){
    // 校验订单操作权限
    $this->order_operate_permission($user_id, $order_id);
    $para = $this->sign(array(
            'order_id' => $order_id,
            'terminal' => $terminal,
            'entrance' => $entrance,
            'parent_complaint_id' => $parent_complain_id,
            'complaint_id' => $complain_id,
            'content' => $category_content,
            'images' => $imgs
      ),false
    );
    $resp = ihttp_post($this -> host . $this->submit_order_complain, $this->urlEncode($para));
    return $this->handle_operate($resp);
  }

  //投诉细类补充
  function order_complain_supplement($user_id,$order_id,$complain_id, $content){
    // 校验订单操作权限
    $this->order_operate_permission($user_id, $order_id);
    $para = $this->sign(array(
            'order_complaint_id' => $complain_id,
            'content' => $content
      )
    );
    $resp = ihttp_post($this -> host . $this->complain_supplement, $this->urlEncode($para));
    return $this->handle_operate($resp);
  }

  //获取腾讯万象优图token
  function get_ci_token($type='complaint'){
    $para = $this->sign(array(
            'kind' => $type
            )
    );
    $param_str = $this->createLinkstringUrlencode($para);
    $resp = ihttp_get($this->host. $this->get_ci_upload_token . '?' . $param_str);
    return $this->handle_response($resp);
  }
  
  //设置订单投诉状态
  function set_order_complain($user_id, $order_id, $complaint){
    $para = $this->sign(array(
            'user_id' => $user_id,
            'order_id' => $order_id,
            'complaint' => $complaint

        )
    );
    $resp = ihttp_post($this -> host . $this->set_order_complain, $this->urlEncode($para));
    return $this ->handle_operate($resp);
  }
  
  function get_clients($user_id){
    $para = array('user_id' => $user_id);
    $para = $this->sign($para);
    $param_str = $this->createLinkstringUrlencode($para);
    $resp = ihttp_get($this->host.$this->get_clients.'?'.$param_str);
    return $this->handle_response($resp);
  }

 function bind_http_user($from_user, $user_type){
    $para = array('user_type' => $user_type, 'from_user' => $from_user); 
    $para = $this->sign($para);
    $resp = ihttp_post($this->host . $this->bind_http_user, $this->urlEncode($para));  
    return $this->handle_operate($resp);
  }

  function get_order_list($user_id, $order_type, $page, $per_page){
    $para = array('user_id' => $user_id, 'order_type' => $order_type, 'page' => $page, 'per_page' => $per_page);
    $para = $this->sign($para);
    $param_str = $this->createLinkstringUrlencode($para);
    $resp = ihttp_get($this->host.$this->get_order_list.'?'.$param_str);
    return $this->handle_response($resp); 
  }

  function get_order($user_id, $order_id){
    // 校验订单操作权限
    $order_id = intval($order_id);
    //$this->order_operate_permission($user_id, $order_id);
    
    $para = array('user_id' => $user_id, 'order_id' => $order_id);
    $para = $this->sign($para);
    $param_str = $this->createLinkstringUrlencode($para);
    $resp = ihttp_get($this->host.$this->get_order.'?'.$param_str);
    $res = $this->handle_response($resp); 
    if(empty($res)){
      $_SESSION['user_info'] = array();
      header('Location: ' . create_url('homepage/index'));
      exit;   
    }
    return $res; 
  }
  
  // 订单支付信息新接口
  function order_pay_info($user_id, $order_id){
  	// 校验订单操作权限
	  $this->order_operate_permission($user_id, $order_id);
	
  	$para = array('user_id' => $user_id, 'order_id' => $order_id);
  	$para = $this->sign($para);
  	$param_str = $this->createLinkstringUrlencode($para);
  	$resp = ihttp_get($this->host . $this->order_pay_info . '?' . $param_str);
  	$res = $this->handle_response($resp);
  	if(empty($res)){
  		$_SESSION['user_info'] = array();
  		header('Location: ' . create_url('homepage/index'));
  		exit;
  	}
  	return $res;
  }
  
  function get_icard($user_id){
    $para = array('user_id' => $user_id);
    $para = $this->sign($para);
    $param_str = $this->createLinkstringUrlencode($para);

    $resp = ihttp_get($this->host.$this->get_icard.'?'.$param_str);
    return $this->handle_response($resp);
  }
  
  // 奢侈品年卡
  function get_extra_accounts($user_id){
      $para = array('user_id' => $user_id);
      $para = $this->sign($para);
      $param_str = $this->createLinkstringUrlencode($para);
      
      $resp = ihttp_get($this->host . $this->get_extra_accounts . '?' . $param_str);
      return $this->handle_response($resp);
  }
  
  // 衣物详情 
  function order_clothing($user_id,$order_id){
    $para = array('user_id' => $user_id,'order_id' => $order_id);
    $para = $this->sign($para);
    $param_str = $this->createLinkstringUrlencode($para);

    $resp = ihttp_get($this->host.$this->order_clothing.'?'.$param_str);
    return $this->handle_response($resp);
  }
  
  // 物流详情
  function order_delivery_status_list($user_id,$order_id){
    $para = array('user_id' => $user_id,'order_id' => $order_id);
    $para = $this->sign($para);
    $param_str = $this->createLinkstringUrlencode($para);

    $resp = ihttp_get($this->host.$this->order_delivery_status_list.'?'.$param_str);
    return $this->handle_response($resp);
  }
  
  function bind_coupon($user_id,$sncode){
    $para = array('user_id' => $user_id, 'sncode' => $sncode );  
    $para = $this -> sign($para);
    $resp = ihttp_post($this -> host.$this->bind_coupon, $this->urlEncode($para));  
    return $this ->handle_operate($resp);
  }

  function pay_order2($user_id, $order_id, $paytype, $coupon_id, $activity_info_id = 0, $city_id = '',$mark = ''){
    $para = array('user_id' => $user_id, 'order_id' => $order_id, 'paytype' => $paytype, 'coupon_id' => $coupon_id, 'activity_info_id' => $activity_info_id, 'city_id' => $city_id, 'mark'=>$mark);
    $para = $this->sign($para);

    $resp = ihttp_post($this->host. $this->pay_order2, $this->urlEncode($para));
    return $this->handle_operate($resp);     
  }
  
  function icard_recharge($user_id, $paytype, $fee, $tel='', $city_id = '', $mark = '', $business_id = ''){
    $para = array(
        'user_id' => $user_id, 
        'paytype' => $paytype, 
        'fee' => $fee, 
        'tel' => $tel,
        'city_id' => $city_id,
        'mark' => $mark,
        'business_id' => $business_id
        );
    $para = $this->sign($para);
    $resp = ihttp_post($this->host. $this->icard_recharge, $this->urlEncode($para));
    return $this->handle_operate($resp);     
  }

  function cancel_order($user_id, $order_id,$reason,$city_id = '',$mark = ''){
    $para = array('user_id' => $user_id, 'order_id' => $order_id,'reason' => $reason,'city_id' => $city_id,'mark'=>$mark);
    $para = $this->sign($para);

    $resp = ihttp_post($this->host.$this->cancel_order, $this->urlEncode($para));
    return $this->handle_operate($resp);     
  }
  function order_envelope_is_share($order_id){
    $para = array('order_id' => $order_id);
    $para = $this->sign($para);

    $resp = ihttp_post($this->host.$this->order_envelope_is_share, $this->urlEncode($para));
    return $this->handle_response($resp);     
  }
  
  // 一般品类创建订单
  // function create_order($user_id, $user_type, $category_id, $address_id, $order_date, $order_time, $time_range, $categories='',
  function create_order($user_id, $user_type, $time_range, $order_date, $order_time, $category_id, $categories='', $address_id='', $comment='', $client_id='', $mark=''){
    $para = array(
        'user_id' => $user_id,
        'user_type' => $user_type,
        'time_range' => $time_range,
        'order_date' => $order_date,
        'order_time' => $order_time,
        'category_id' => $category_id,
        'categories' => $categories,
        'address_id' => $address_id,
        'comment' => $comment,
        'mark' => $mark,
        // 'city_id' =>$city_id,
        // 'take_soon' => $take_soon
    );
    if(!empty($client_id)){
      $para['client_id'] = $client_id;
    }
    $para = $this->sign($para);
    $resp = ihttp_post($this->host . $this->create_order, $this->urlEncode($para));
    return $this->handle_operate($resp);     
  }
  
  // 一般品类扫码揽收创建订单
  function create_qrcode_order($user_id, $user_type, $qrid, $category_id, $address_id, $order_date, $order_time,
  		$back_type=1, $city_id='', $mark='', $dak_id=''){
  	$para = array(
  			'user_id' => $user_id,
  			'user_type' => $user_type,
  			'qrcode_order_id' => $qrid,
  			'category_id' => $category_id,
  			'order_date' => $order_date,
  			'order_time' => $order_time,
  			'back_type' => $back_type,
  			'city_id' =>$city_id,
  			'mark' => $mark,
        'address_id' => $address_id
  	);
  	if(2 === $back_type){
      //扫码自提订单, 不需要传地址id, 传驿站id
      unset($para['address_id']);
      $para['dak_id'] = $dak_id;
  	}
  	$para = $this->sign($para);
  	$resp = ihttp_post($this->host . $this->create_qrcode_order, $this->urlEncode($para));
  	return $this->handle_operate($resp);
  }
  
  // 酒店快洗创建订单
  function create_hotel_order($user_id, $user_type, $category_id, $hotel_id, $room, $tel, $order_date, $order_time, $time_range,
  		$user_name='', $comment='', $city_id = '',$mark = ''){
  	$para = array(
  			'user_id' => $user_id,
  			'user_type' => $user_type,
  			'category_id' => $category_id,
  			'hotel_id' => $hotel_id,
  			'room' => $room,
  			'tel' => $tel,
  			'order_date' => $order_date,
  			'order_time' => $order_time,
        'time_range' => $time_range,
  			'user_name' => $user_name,
  			'comment' => $comment,
  			'city_id' => $city_id,
  			'mark' => $mark
  	);
  	$para = $this->sign($para);
  	$resp = ihttp_post($this->host . $this->create_order, $this->urlEncode($para));
  	return $this->handle_operate($resp);
  }
  
  // 酒店快洗扫码揽收创建订单
  function create_qrhotle_order($user_id, $user_type, $qrid, $category_id, $hotel_id, $room, $tel, $order_date, $order_time, 
      $user_name='', $city_id = '', $mark = ''){
  	$para = array(
  			'user_id' => $user_id,
  			'user_type' => $user_type,
  			'qrcode_order_id' => $qrid,
  			'category_id' => $category_id,
  			'hotel_id' => $hotel_id,
  			'room' => $room,
  			'tel' => $tel,
  			'order_date' => $order_date,
  			'order_time' => $order_time,
  			'user_name' => $user_name,
  			'city_id' =>$city_id,
  			'mark' => $mark
  	);
  	$para = $this->sign($para);
  	$resp = ihttp_post($this->host . $this->create_qrcode_order, $this->urlEncode($para));
  	return $this->handle_operate($resp);
  }
  
  // 改衣服务创建订单
  function create_tailor_order($user_id, $user_type, $category_id, $order_place, $order_date, $order_time, $time_range, $clothes_ids, $categories_ids,
  		$comment='', $city_id='', $mark='', $take_soon=''){
  	$para = array(
  			'user_id' => $user_id,
  			'user_type' => $user_type,
  			'category_id' => $category_id,
  			'address_id' => $order_place,
  			'order_date' => $order_date,
  			'order_time' => $order_time,
        'time_range' => $time_range,
  			'clothes_ids' => $clothes_ids,
  			'categories_ids' => $categories_ids,
  			'comment' => $comment,
  			'city_id' =>$city_id,
  			'mark' => $mark,
  			'take_soon' => $take_soon
  	);
  	$para = $this->sign($para);
  	$resp = ihttp_post($this->host . $this->create_order, $this->urlEncode($para));
  	return $this->handle_operate($resp);
  }
  
  function create_order_comment($user_id, $order_id, $total_score, $washing_score, $logistics_score, $service_score, $comment, $comment_fetch_option, $comment_carry_option, $city_id = '', $mark = '', $images='', $imgs_wh=''){
    // 校验订单操作权限
    $this->order_operate_permission($user_id, $order_id);
    $para = array(
    		'user_id' => $user_id,
    		'order_id' => $order_id,
    		'total_score' => $total_score,
    		'washing_score' => $washing_score,
    		'logistics_score' => $logistics_score,
    		'service_score' => $service_score,
    		'comment' => $comment,
    		'city_id' => $city_id,
    		'comment_fetch_option' => $comment_fetch_option,
    		'comment_carry_option' => $comment_carry_option,
    		'mark' => $mark,
        'images' => $images,
        'images_sizes' => $imgs_wh
    );
    $para = $this->sign($para);
    $resp = ihttp_post($this->host.$this->create_order_comment, $this->urlEncode($para));
    return $this->handle_operate($resp);     
  }
  
  // 改衣订单评价
  function create_tailor_comment($user_id, $order_id, $total_score, $quality_score, $tailor_score, $sender_score, 
      $tailor_comment, $sender_comment, $comment_text, $city_id, $mark, $images='', $imgs_wh=''){
      $para = array(
          'user_id' => $user_id,
          'order_id' => $order_id,
          'total_score' => $total_score,
          'washing_score' => $quality_score,
          'logistics_score' => $tailor_score,
          'service_score' => $sender_score,
          'comment_fetch_option' => $tailor_comment,
          'comment_carry_option' => $sender_comment,
          'comment' => $comment_text,
          'city_id' => $city_id,
          'mark' => $mark,
          'images' => $images,
          'images_sizes' => $imgs_wh
      );
      $para = $this->sign($para);
      $resp = ihttp_post($this->host . $this->create_order_comment, $this->urlEncode($para));
      return $this->handle_operate($resp);
  }
  
  // 获取当前用户地址列表
  function get_address_list($user_id, $category_id='', $sub_category_ids=''){
    $para = array(
        'user_id' => $user_id, 
        'category_id' => $category_id,
        'sub_category_ids' => $sub_category_ids,
        );
    $para = $this->sign($para);
    $param_str = $this->createLinkstringUrlencode($para);
    $resp = ihttp_get($this->host . $this->get_address_list . '?' . $param_str);
    $resp['content'] = UrlDecode($resp['content']);
    return $this->handle_response($resp);
  }
  
  // 获取一条可用地址(或默认地址)
  function get_usable_address($user_id, $address_id='', $category_id='', $city_id='', $area_id=''){
  	$para = array(
  			'user_id' => $user_id, 
  			'address_id' => $address_id,
  			'category_id' => $category_id,
  			'city_id' => $city_id,
  			'area_id' => $area_id
  	);
  	$para = $this->sign($para);
  	$param_str = $this->createLinkstringUrlencode($para);
  	$resp = ihttp_get($this->host . $this->get_usable_address . '?' . $param_str);
  	$resp['content'] = UrlDecode($resp['content']);
  	return $this->handle_response($resp);
  }

  //获取当前用户的所有可投诉订单
  function get_user_complain_orders($user_id){
    $para = array('user_id' => $user_id);
    $para = $this->sign($para);
    $param_str = $this->createLinkstringUrlencode($para);
    $resp = ihttp_get($this->host . $this->get_user_complain_orders . '?' . $param_str);
    $resp['content'] = UrlDecode($resp['content']);
    return $this->handle_response($resp);
  }
  
   //添加地址
   function create_address($para){
    $para = $this->sign($para);
    $resp = ihttp_post($this->host . $this->create_address, $this->urlEncode($para));
    return $this->handle_operate($resp);
   }
  
  //修改地址
  function update_address($para){
    # 验证地址操作权限
    $this->address_operate_permission($para['user_id'], $para['address_id']);

    $para = $this->sign($para);
    $resp = ihttp_post($this->host . $this->update_address, $this->urlEncode($para));
    return $this->handle_operate($resp);
  }
  
  //删除地址
  function delete_address($user_id, $address_id){
    # 验证地址操作权限
    $this->address_operate_permission($user_id, $address_id);

    $para = array('user_id' => $user_id, 'address_id' => $address_id);
    $para = $this->sign($para);
    $resp = ihttp_post($this->host. $this->delete_address, $this->urlEncode($para));
    return $this->handle_operate($resp);
  }
  
  //验证地址是否在服务范围
  function verify_address($para){
    $para = $this->sign($para);
    $param_str = $this->createLinkstringUrlencode($para);
    $resp = ihttp_get($this->host . $this->verify_address . '?' . $param_str);
    $resp['content'] = UrlDecode($resp['content']);
    return $this->handle_operate($resp);
  }
  
  //校验地址操作权限
  function address_operate_permission($user_id, $address_id){
    if(empty($user_id) || empty($address_id)){
        header('Location: ' . create_url('homepage/index'));
  		exit;
    }
    $permission = false;
    $permission = redis()->sismember('address:' . $user_id, $address_id);
    if(!$permission){
        $list = $this->get_address_list($user_id);
        foreach ($list as $key => $value) {
            if($address_id == $value['address_id']){
                $permission = true;
                break;
            }
        }
        if(!$permission){
            header('Location: ' . create_url('homepage/index'));
            exit;
        }
    }
  }
  
  //校验订单操作权限
  function order_operate_permission($user_id, $order_id){
    if(empty($user_id) || empty($order_id)){
  		header('Location: ' . create_url('homepage/index'));
  		exit;
  	}
  	$is_my_order = false;
  	$is_my_order = redis()->sismember(md5($user_id.'_@_orders'), $order_id);
  	if(!$is_my_order){
  		$list = $this->get_order_list($user_id, 0, 1, 100);
  		foreach ($list as $key => $value) {
  			if($value['order_id'] == $order_id){
  				$is_my_order = true;
  				break;
  			}
  		}
  		$list = $this->get_order_list($user_id, 1, 1, 100);
  		foreach ($list as $key => $value) {
  			if($value['order_id'] == $order_id){
  				$is_my_order = true;
  				break;
  			}
  		}
  		if(!$is_my_order){
  			header('Location: ' . create_url('homepage/index'));
  			exit;
  		}
  	}
  }
  
  function bind_member_card($user_id, $sn_code, $sn_password){
    $para = array('user_id' => $user_id, 'sn_code' => $sn_code, 'sn_password' => $sn_password);
    $para = $this->sign($para);
    $resp = ihttp_post($this->host.$this->bind_member_card, $this->urlEncode($para));
    return $this->handle_operate($resp);     
  }

  function bind_recharge($user_id, $sncode, $tel='', $business_id=''){
    $para = array(
          'user_id' => $user_id,
          'sncode' => $sncode,
          'tel' => $tel,
          'business_id' => $business_id
          );
    $para = $this->sign($para);
    $resp = ihttp_post($this->host.$this->bind_recharge, $this->urlEncode($para));
    return $this->handle_operate($resp);     
  }

  function cities_options(){
    $para = array();
    $para = $this->sign($para);
    $param_str = $this->createLinkstringUrlencode($para);
    $resp = ihttp_get($this->host.$this->cities_options.'?'.$param_str);
    return $this->handle_response($resp);
  }

  function get_paylog($id){
    $para = array('id' => $id);
    $para = $this->sign($para);
    $param_str = $this->createLinkstringUrlencode($para);
    $resp = ihttp_get($this->host.$this->get_paylog.'?'.$param_str);
    return $this->handle_response($resp);
  }

  // 发送短信验证码
  function send_sms($phone, $user_id){
  	$phone = str_replace(PHP_EOL, '', $phone);
    $para = array('phone' => $phone,'user_id' => $user_id);
    $para = $this->sign($para);
    $resp = ihttp_post($this->host . $this->send_sms, $this->urlEncode($para));
    return $this->handle_operate($resp);     
  }
  
  // 发送语音验证码
  function send_voice_sms($phone, $user_id=''){
  	$phone = str_replace(PHP_EOL, '', $phone);
  	$para = array('phone' => $phone,'user_id' => $user_id);
  	$para = $this->sign($para);
  	$resp = ihttp_post($this->host . $this->send_voice_sms, $this->urlEncode($para));
  	return $this->handle_operate($resp);
  }

  // 获取真实用户ID，绑定用户手机，设置登录状态
  function bind_user($phone, $code, $user_type, $user_id='', $city_id='', $mark='', $skip_check=false){
  	$phone = str_replace(PHP_EOL, '', $phone);
  	$code = str_replace(PHP_EOL, '', $code);
    $para = array(
    		'phone' => $phone, 
    		'code' => $code, 
    		'user_type' => $user_type, 
    		'user_id' => $user_id,
    		'city_id' => $city_id,
    		'mark' => $mark,
    		'skip_check' => $skip_check
    );
    $para = $this->sign($para);
    $resp = ihttp_post($this->host . $this->bind_user, $this->urlEncode($para));
    return $this->handle_operate($resp);
  }

  /**
   * 获取用户e卡列表
   * @param int user_id 用户id
   * @param int user_type 用户类型
   * @param array order_group_ids 选卡支付时的订单id, [1,2,3]
   * @param int coupon_id 优惠券模板id
   */
  public function user_ecard_list($data)
  {
    $params = $this->sign($data);
    $param_str = http_build_query($params);
    $resp = ihttp_get($this->host.$this->user_ecard_list.'?'.$param_str);
    return $this->handle_response($resp);
  }

  /**
   * 用户钱包接口
   * @param int user_id 用户id
   */
  public function user_wallet($data)
  {
    $params = $this->sign($data);
    $param_str = http_build_query($params);
    $resp = ihttp_get($this->host.$this->user_wallet.'?'.$param_str);
    return $this->handle_response($resp);
  }

  /**
   * 获取用户待支付订单列表
   *　@param int user_id 用户id
   * @param int user_type 用户id
   * @param int order_id 订单id
   */
  public function settlements_list($data)
  {
    $params = $this->sign($data);
    $param_str = http_build_query($params);
    $resp = ihttp_get($this->host.$this->settlements_list.'?'.$param_str);
    return $this->handle_response($resp);
  }

  /**
   * 合并支付计算合并订单运费
   *　@param int user_id 用户id
   * @param int orders 合并的订单json
   */
  public function caclulate_delivery_fee($data)
  {
    $params = $this->sign($data);
    $param_str = http_build_query($params);
    $resp = ihttp_get($this->host.$this->caclulate_delivery_fee.'?'.$param_str);
    return $this->handle_response($resp);
  }

  /**
   * 合并支付进入支付页面接口
   */
  public function get_pay_info($data)
  {
    $params = $this->sign($data);
    $param_str = http_build_query($params);
    $resp = ihttp_get($this->host.$this->get_pay_info.'?'.$param_str);
    return $this->handle_response($resp);
  }

  /**
   * 获取运费说明信息
   */
  public function delivery_fee_info($data) {
    $params = $this->sign($data);
    $param_str = http_build_query($params);
    $resp = ihttp_get($this->host.$this->delivery_fee_info.'?'.$param_str);
    return $this->handle_response($resp);
  }

  /**
   * 获取订单支付价格
   */
  public function caclulate_order_price($data) {
    $params = $this->sign($data);
    $resp = ihttp_post($this->host . $this->caclulate_order_price, $params);
    return $this->handle_response($resp);
  }

  /**
   * 订单支付接口
   */
  public function pay_order_v5($data) {
    $params = $this->sign($data);
    $resp = ihttp_post($this->host . $this->pay_order_v5, $params);
    return $this->handle_operate($resp);
  }

  /**
   * 获取订单实付款详情
   */
  public function order_detail_paid_info($data) {
    $params = $this->sign($data);
    $param_str = http_build_query($params);
    $resp = ihttp_get($this->host.$this->order_detail_paid_info.'?'.$param_str);
    return $this->handle_response($resp);
  }

  /**
   * 获取订单衣物实付款详情
   */
  public function order_detail_clothes_paid_info($data) {
    $params = $this->sign($data);
    $param_str = http_build_query($params);
    $resp = ihttp_get($this->host.$this->order_detail_clothes_paid_info.'?'.$param_str);
    return $this->handle_response($resp);
  }

  /**
   * 订单取消支付接口
   */
  public function cancel_pay($data) {
    $params = $this->sign($data);
    $resp = ihttp_post($this->host . $this->cancel_pay, $params);
    return $this->handle_operate($resp);
  }

  /**
   * 获取用户优惠券列表 
   * 已无用
   */
  public function get_coupons_v5($data) {
    $params = $this->sign($data);
    $param_str = http_build_query($params);
    $resp = ihttp_get($this->host.$this->get_coupons_v5.'?'.$param_str);
    return $this->handle_response($resp);
  }

  //第三方支付修改为接口
  function payment_sign($user_id, $user_type, $pay_type, $trade_no, $fee, $is_recharge=0, $return_url='', $from_user='', $tel=''){
    $para = $this->sign(array(
          'user_id' => $user_id,
          'user_type' => $user_type,
          'pay_type' => $pay_type, 
          'trade_no' => $trade_no, 
          'fee' => $fee, 
          'subject' => $is_recharge ? 'e袋洗用户充值:'.$fee : 'e袋洗订单支付:'.$fee,
          'return_url' => $return_url,
          'from_user' => $from_user,
          'tel' => $tel,
        ));
    $resp = ihttp_post($this -> host . $this->payment_sign, $this->urlEncode($para));
    return $this ->handle_operate($resp);
  }

  //根据app的session_id获取用户信息
  function get_user_by_sessionid($session_id, $user_type){
    $params = $this->sign(array(
            'open_session_id' => $session_id,
            'user_type' => $user_type
        ));
    $param_str = http_build_query($params);
    $resp = ihttp_get($this->host.$this->get_user_by_sessionid.'?'.$param_str);
    return $this->handle_response($resp);
  }

  //根据order_id获取交易总额
  function get_trade_fee_by_orderid($order_id){
    $params = $this->sign(array(
            'order_id' => $order_id
        ));
    $param_str = http_build_query($params);
    $resp = ihttp_get($this->host.$this->get_trade_fee_by_orderid.'?'.$param_str);
    return $this->handle_response($resp);
  }
  
  // 处理 get 返回
  private function handle_response($resp){
    global $_W;
    if ($_W['config']['setting']['development']) {
      file_put_contents('/tmp/open.log', var_export($resp, true));
    }
    $ret = json_decode($resp['content'], true);
    if($ret['ret']){
      if(is_array($ret['data'])){
      	return $ret['data'];
      }else{
      	return json_decode($ret['data'], true);
      }
    }else{
      $this->open_error_log($ret['error']);
      /**
       * 接口验证签名错误
       * */
      if($ret['error_code'] == 40001){
        // 清除相关 session 数据
	      $_SESSION['user_info']['user_id'] = '';
        $_SESSION['user_info']['user_token'] = '';
        $_SESSION['user_info']['is_login'] = '';
        // 重新发送请求（过程中会再次生成签名 token 等数据）
        if(is_ajax()){
            $ret['error'] = '网络问题，请稍后重试';
        	return $ret;
        }else{
          $request_uri = $_SERVER['REQUEST_URI'];
          $now = time();
          if(strpos($request_uri,'?') === false){
            $request_uri .= "?ts={$now}";
          } else {
            $request_uri .= "&ts={$now}";
          }
          header('Location: ' . HTTP_TYPE . $_SERVER['HTTP_HOST'] . $request_uri);
        }
        exit;
      }
      
      /**
       * 处理接口返回的异常信息
       * */
      if(is_ajax()){
      	return $ret;
      }else{
      	return array();
      }
    }
  }
  
  // 处理 post 返回
  private function handle_operate($resp){
    global $_W;
    if ($_W['config']['setting']['development']) {
      file_put_contents('/tmp/open.log', var_export($resp, true));
    }
    $ret = json_decode($resp['content'], true);
    if(! $ret['ret']){
    	$this->open_error_log($ret['error']);
    	if($ret['error_code'] == 40001){
	        $_SESSION['user_info']['user_id'] = '';
	        $_SESSION['user_info']['user_token'] = '';
	        $_SESSION['user_info']['is_login'] = '';
          $ret['error'] = '网络问题，请稍后重试';
    	}
    	if(is_ajax()){
    	    return $ret;
    	}else{
    	    header('Location: ' . create_url('homepage/index'));
    	}
    	exit;
    }
    return $ret;
  }

  /**
   * 获取用户的企业发票历史，方便自动填充
   */
  public function get_invoice_taxid_history($fan_id) {
    $params = array(
      'user_id' => $fan_id,
      );
    $params = $this->sign($params);
    $param_str = http_build_query($params);
    $resp = ihttp_get($this->host.$this->get_invoice_taxid_history.'?'.$param_str);
    return $this->handle_response($resp);
  }
 
  private function createLinkstringUrlencode($para) {
    $arg = http_build_query($para);
    return $arg;
  }

  // ？？？没看懂有什么用  >_<|||
  private function urlEncode($para){
    while (list ($key, $val) = each ($para)) {
      $para[$key] = $val;
    }
    return $para; 
  }

  private function createLinkstring($para) {
    $str = http_build_query($para);
    $str = urldecode($str);
    return $str;
  }

  private function paraFilter($para) {
    $para_filter = array();
    while (list ($key, $val) = each ($para)) {
      if($key == "sign" || $val === "")
      	continue;
      else  
      	$para_filter[$key] = $para[$key];
    }
    return $para_filter;
  }

  private function argSort($para) {
    ksort($para);
    reset($para);
    return $para;
  }

  //为防止过滤掉为空的必需接口参数，加一个默认参数$filter
  private function sign($para, $filter=true){
  	$para['ip'] = $this->ip;
    $para['app_key'] = 'http_client';
    $para_filter = $filter ? $this->paraFilter($para) : $para;
    $para_sort = $this->argSort($para_filter);
    $prestr = $this->createLinkstring($para_sort);
    if(!empty($para['user_id'])){
        $sign = md5($prestr . $this->key . $this->user_info['user_token']); 
    }else{
        $sign = md5($prestr . $this->key);
    }

    $para_sort['sign'] = $sign;
    return $para_sort;
  }
  
  // 记录 open_server 请求错误日志
  private function open_error_log($error=''){
  	$logkey = 'OpenServer 请求错误';
  	global $_GPC;
  	$backtrace = debug_backtrace();
  	$traceinfo = $backtrace[2];
  	$logval['错误信息'] = $error ? $error : '无返回数据';
  	$logval['文件位置'] = array_pop(explode('/', $traceinfo['file'])) . ' 第' . $traceinfo['line'] . '行';
  	$logval['调用方法'] = $_GPC['act'] . '/' . $_GPC['do'];
  	$logval['open主机'] = $this->host;
  	$logval['open接口'] = $traceinfo['function'] . '(' . implode(' , ', $traceinfo['args']) . ')';
  	$logval['用户信息'] = var_export($this->user_info, true);
  	logging($logkey, $logval, 'a+', 'open_error');
  }
  
  // 记录 open_server 正确请求日志
  private function open_server_log(){
  	$logkey = 'OpenServer 请求';
  	global $_GPC;
  	$backtrace = debug_backtrace();
  	$traceinfo = $backtrace[2];
  	$logval['文件位置'] = array_pop(explode('/', $traceinfo['file'])) . ' 第' . $traceinfo['line'] . '行';
  	$logval['调用方法'] = $_GPC['act'] . '/' . $_GPC['do'];
  	$logval['open主机'] = $this->host;
  	$logval['open接口'] = $traceinfo['function'] . '(' . implode(' , ', $traceinfo['args']) . ')';
  	$logval['用户信息'] = var_export($this->user_info, true);
  	logging($logkey, $logval, 'a+', 'open_server');
  }
  
}
?>