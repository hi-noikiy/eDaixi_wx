<?php
/** 荣昌 OpenServer
* ____________________________________ */

class ApiServer {

  private $host = 'http://api.edaixi.cn';
  // 测试环境
  // private $host = 'http://open.edaixi.cn';
  // 生产环境
  // private $host = 'http://open.edaixi.com';
  private $coupon_lists = '/api/v1/coupon_lists';
  private $get_coupon = '/api/v1/coupon_lists/id/';
  private $paylog_success = '/api/v1/paylogs/id/';
  private $icard_kaika = '/api/v1/customers/id/';

  private $acquire_kaquan = '/api/v1/coupon_lists/acquire_kaquan';
  private $acquire_coupon = '/api/v1/coupon_lists/id/';
  private $luxury_send_code = '/api/v2/activity_info/luxury_vip/send_code/';
  private $luxury_create_pay = '/api/v2/activity_info/luxury_vip/';
  private $create_order = '/api/v1/orders';
  
  // 奢侈品代金券购买接口
  private $luxury_coupon_status  = '/api/v2/activity_info/luxury_coupon/status';
  private $luxury_coupon_captcha = '/api/v2/activity_info/luxury_coupon/send_code';
  private $luxury_coupon 		 = '/api/v2/activity_info/luxury_coupon/';
  private $delivery_fee_settings = '/api/v1/delivery_fee_settings';  
  // 三八妇女节充值活动接口
  private $charge_by_mobile = '/api/v1/customers/charge_by_mobile';
  // 校验手机号是否在服务范围
  private $mobile_in_service = '/api/v1/customers/mobile_in_service';
  
  function __construct($config) {
    
    $this->host = $config['edaixi']['api_server'];
  }

  function getDeliveryFeeSettings($city_id, $category_id)
  {
    $para = array('city_id' => $city_id, 'category_id' => $category_id);
    $param_str = $this->createLinkstringUrlencode($para);
    $resp = ihttp_get($this->host.$this->delivery_fee_settings.'?'.$param_str);
    return $this->handle_response($resp);
  }

  function create_order($para){
        $resp = ihttp_post($this -> host.$this->create_order, $para);
        return $this->handle_response($resp);
  }
  
  function luxury_send_code($mobile){
  	$mobile = str_replace(PHP_EOL, '', $mobile);
    $para = array('mobile' => $mobile);
    $resp = ihttp_post($this ->host.$this->luxury_send_code, $para);

    return $this->handle_response($resp);  
  }
  function luxury_create_pay($mobile,$code,$paytype,$name){
  	$mobile = str_replace(PHP_EOL, '', $mobile);
  	$code = str_replace(PHP_EOL, '', $code);
    $para = array('mobile' => $mobile,'code'=>$code,'paytype'=>$paytype,'name'=>$name);
    $resp = ihttp_post($this ->host.$this->luxury_create_pay, $para);

    return $this->handle_response($resp);  
  }
  
  /**
   * 奢侈品代金券购买接口
   */
  function luxury_coupon_status(){
      $resp = ihttp_post($this->host . $this->luxury_coupon_status);
      return $this->handle_response($resp);
  }
  function luxury_coupon_captcha($mobile){
  	  $mobile = str_replace(PHP_EOL, '', $mobile);
  	  $para = array('mobile' => $mobile);
  	  $resp = ihttp_post($this->host . $this->luxury_coupon_captcha, $para);
  	  return $this->handle_response($resp);
  }
  function luxury_coupon_pay($data){
      $resp = ihttp_post($this->host . $this->luxury_coupon, $data);
      return $this->handle_response($resp);
  }
  
  // 三八妇女节充值
  function charge_by_mobile($username, $mobile, $message, $fee, $paytype, $skipserv=true){
  	$para = array(
  			'user_name' => $username, 
  			'mobile' => str_replace(PHP_EOL, '', $mobile), 
  			'fee' => $fee, 
  			'paytype' => $paytype,
  			'content' => $message,
  			'skip_mobile_check' => $skipserv,
  			'ip' => getip()
  	);
  	$resp = ihttp_post($this->host. $this->charge_by_mobile, $para);
  	return $this->handle_response($resp);
  }
  // 校验手机号是否在服务范围
  function mobile_in_service($mobile){
  	$para = array('mobile' => $mobile, 'ip' => getip());
  	$param_str = $this->createLinkstringUrlencode($para);
  	$resp = ihttp_get($this->host . $this->mobile_in_service . '?' . $param_str);
  	return $this->handle_response($resp);
  }
  
  function coupon_lists($page){
    $para = array('page' => $page);
    $param_str = $this->createLinkstringUrlencode($para);
    $resp = ihttp_get($this->host.$this->coupon_lists.'?'.$param_str);
    return $this->handle_response($resp);
  }

  function get_coupon_list($id){
    $resp = ihttp_get($this->host.$this->get_coupon.'/'.$id);
    return $this->handle_response($resp);
  }

  function paylog_success($plid){
    $para = array('plid' => $plid);  
    $resp = ihttp_post($this ->host.$this->paylog_success.$plid.'/success', $para);

    return $this->handle_response($resp);
  }

  function icard_kaika($user_id, $fee, $paytype){
    $para = array('user_id' => $user_id, 'fee' => $fee, 'paytype' => $paytype);
    $resp = ihttp_post($this ->host.$this->icard_kaika.$user_id.'/kaika', $para);

    return $this->handle_response($resp);  
  }

  function acquire_coupon($cid, $from_user, $user_type){
    $para = array('cid' => $cid, 'from_user' => $from_user, 'user_type' => $user_type);
    $resp = ihttp_post($this ->host.$this->acquire_coupon.$cid.'/acquire', $para);

    return $this->handle_response($resp);  
  }

  function acquire_kaquan($card_id, $from_user, $sncode, $friend_from_user){
    $para = array('card_id' => $card_id, 'from_user' => $from_user, 'sncode' => $sncode, 'friend_from_user' => $friend_from_user);
    $resp = ihttp_post($this ->host.$this->acquire_kaquan, $para);

    return $this->handle_response($resp);
  }

  private function handle_response($resp){
    $ret = json_decode($resp['content'], true);
    return $ret;
  }

  private function handle_operate($resp){
    $ret = json_decode($resp['content'], true);
    return $ret;
  }

  private function createLinkstringUrlencode($para) {
    $arg  = "";
    while (list ($key, $val) = each ($para)) {
      $arg.=$key."=".urlencode($val)."&";
    }
    $arg = substr($arg,0,count($arg)-2);

    if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}

    return $arg;
  }

  private function urlEncode($para){
    while (list ($key, $val) = each ($para)) {
      $para[$key] = $val;
    }

    return $para; 
  }
}

?>
