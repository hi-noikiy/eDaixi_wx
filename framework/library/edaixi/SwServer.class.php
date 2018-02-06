<?php
include IA_ROOT . '/framework/library/http/DataRequst.class.php';
include IA_ROOT . '/framework/library/http/DataFormat.class.php';
class SwServer{
	private $user_id;
	private $host = 'http://swapi03.edaixi.cn';
	private $app_key = '3JdahN78n62xuEemEBmDSA1fKEKr';
	private $app_secret = '3tN8wHhgYABn9cSrfTSYSUadzhmw';
	
	private $incr_points = '/usercredit/add_points';				# 发放积分
	private $decr_points = '';										# 扣减积分
	private $points_total  = '/usercredit/get_user_credit/fid-';	# 用户积分总额
	private $points_detail = '/usercredit/get_credit_log/fid-';		# 用户积分明细
	private $get_rcmd_url = '/hongbao/get_recommend_uuid/';			# 生成推荐有奖地址
	private $activeShareRecode = '/user/activeShareRecode/';		# 生成分享记录地址
	//生成余额商城免登陆地址
	private $get_balance_mall_url = '/youcity/genurl/';
	private $get_rcmd_res = '/hongbao/get_recomment_recode';		# 获取推荐有奖记录
	private $getReplaceOrderPageConf = '/activePage/getPageConfByUrl';
	private $replaceOrderNotice = '/activePage/replaceOrderNoticeByAddressId';
    private $getOrderInviteHtml = '/activePage/getOrderInviteTemplateHtml';     #  获取推荐好友订单页面配置
    private $getTemplate = '/hongbao/get_template';

    //917活动获取用户可兑换的礼品链接
    private $get_exchange917_items = '/usercredit/duiba_duihuan_url_917';
    //2017 717活动，获取用户可兑换的礼品及历史记录
    private $get_exchange717_items = '/usercredit/duiba_duihuan_url_717';
    //可配置活动获取用户可兑换的礼品链接
    private $active_duihuan_url = '/usercredit/active_duihuan_url';
    //从可配置活动，获取活动信息
    private $get_active_detail_by_id = '/usercredit/get_active_detail_by_id';
    //可配置活动，获取当前正在进行的活动信息（供过期页使用）
    private $get_online_events = '/usercredit/get_online_events';
    //普通充值页下端【兑换活动充值码】链接
    private $get_recommend_event = '/usercredit/get_recommend_event';

    //零彩宝活动，用于存储订单彩票信息
    private $set_lingcaibao_lottery_url = '/lottery/set_lingcaibao_lottery';
    //零彩宝活动，用于统计用户累计获奖金额
    private $get_lingcb_total_prize = '/lottery/get_lottery_sum';

    //银行充值营销，用于获取活动信息
    private $get_bankcharge_item = '/usercredit/get_bankcharge_by_id';

    //2017年首次使用一网通的用户
    private $is_first_cmbpay_since_2017 = '/recharge/is_first_cmbpay_since_2017';


    public function __construct($user_id){
		global $_W;
		$this->user_id = $user_id;
		$config = $_W['config'];
		$this->host = $config['edaixi']['sw_server'];
		$this->app_key = $config['sw_server']['key'];
		$this->app_secret = $config['sw_server']['secret'];
	}

    public function getOrderInviteHtml($page)
    {
        $url = $this->host . $this->getOrderInviteHtml.'?page='. $page;
        // var_dump($url);exit;
        $resp = DataRequst::sendGetRequst($url);
        return $this->handle_response($resp);
    }

    public function getTemplateById($template_id, $filename)
    {
        $url = $this->host . $this->getTemplate.'?filename='. $filename. '&template_id='. $template_id;
        $resp = DataRequst::sendGetRequst($url);
        return $this->handle_response($resp);
    }

    public function isFirstCmbpaySince2017(){
        $url = $this->host . $this->is_first_cmbpay_since_2017 . '?fan_id=' . $this->user_id;
        $resp = DataRequst::sendGetRequst($url);
        return $this->handle_response($resp);
    }

    public function getReplaceOrderPageConf($url)
    {
        $url = $this->host . $this->getReplaceOrderPageConf . '?url='.$url;
        $resp = DataRequst::sendGetRequst($url);
        return $this->handle_response($resp);
    }

    public function replaceOrderNotice($address_id, $url, $user_name, $message, $tel, $order_id)
    {
        $data = array('address_id' => $address_id, 'url' => $url, 'user_name' => $user_name, 'message' => $message, 'tel' => $tel, 'order_id' => $order_id);
        $url = $this->host . $this->replaceOrderNotice . '?'.http_build_query($data);
        $resp = DataRequst::sendGetRequst($url);
        return $this->handle_response($resp);
    }
	
	// 每日上线积分
	public function daily_points() {
		if($this->user_id){
			$key = 'daily_points_' . $this->user_id . '_' . date('Ymd');
			if(! mcache()->get($key)){
				$resp = $this->add_points('login');
				if(is_numeric($resp['code']) && $resp['code'] == 0){
					$var = '1';
					$expire = strtotime(date('Y-m-d', strtotime('+1 day')));	# 失效时间：次日零时
					mcache()->set($key, $var, $expire - time());
				}
				return $resp;
			}
		}
	}
	
	// 订单评论积分
	public function comment_points() {
		return $this->add_points('pinglun');
	}
	
	// 余额支付积分
	public function icard_pay_points($real_pay) {
		return $this->add_points('yuezhifu', $real_pay);
	}
	
	// 非余额支付积分
	public function other_pay_points($real_pay) {
		return $this->add_points('feiyuezhifu', $real_pay);
	}
	
	//	分享记录
	public function activeShareRecode($data)
	{
		$url = $this->host . $this->activeShareRecode;
		$resp = DataRequst::sendPostRequst($url, $data);
		return $this->handle_response($resp);
	}
	// 增加用户积分
	public function add_points($type, $real_pay=0){
		if($this->user_id && $type){
			$url = $this->host . $this->incr_points;
			$data = array();
			$data['fid'] = $this->user_id;
			$data['action'] = $type;
			$data['parameters'] = $real_pay ? array('price' => $real_pay) : array();
			$Format = new DataFormat();
			$poststr = $Format->JSON($data);
			$resp = DataRequst::sendPostRequst($url ,$poststr);
			return json_decode($resp, true);
		}
	}
	
	// 获取积分总额
	public function get_points_total() {
		$url = $this->host . $this->points_total . $this->user_id;
    	$resp = DataRequst::sendGetRequst($url);
    	$res = $this->handle_response($resp);
    	return $res['credit'];
	}
	
	// 获取明细积分
	public function get_points_detail(){
		if($this->user_id){
			$url = $this->host . $this->points_detail . $this->user_id;
			$resp = DataRequst::sendGetRequst($url);
			return $this->handle_response($resp);
		}
	}
	
    /**  
     * 生成自动登录积分商城地址
     * 用户免登录进入积分商城
     **/
    public function build_login_url(){
    	$points_total = $this->get_points_total();
    	$login_url = 'http://www.duiba.com.cn/autoLogin/autologin?';
    	$timestamp = time() * 1000 . '';
    	$data = array(
    			'uid' 		=> $this->user_id, 
    			'appKey'	=> $this->app_key, 
    			'appSecret'	=> $this->app_secret, 
    			'credits' 	=> $points_total,
    			'timestamp'	=> $timestamp
    	);
    	$sign = $this->sign($data);
    	$login_url .= ('timestamp=' . $timestamp .'&uid=' . $this->user_id . '&credits=' . $points_total 
    			. '&appKey=' . $this->app_key . '&sign=' . urlencode($sign)) ; 
    	return $login_url;
    }
    
    /**
     * 获取推荐链接
     * @param user_id 用户id
     **/
    public function get_recommend_url(){
    	if($this->user_id){
			$url = $this->host . $this->get_rcmd_url;
			$resp = DataRequst::sendPostRequst($url,array('user_id' => $this->user_id));
			return $this->handle_response($resp);
		}
    }

    /**
     * 获取推荐记录
     * @param user_id 用户id
     **/
    public function get_recommend_res(){
    	if($this->user_id){
    		$url = $this->host . $this->get_rcmd_res;
    		$resp = DataRequst::sendPostRequst($url,array('user_id' => $this->user_id));
    		return $this->handle_response($resp);
    	}
    }

    /**
     * 917活动获取已登录用户的可兑换装备链接
     * $fee=='' 为首页获取，不为空时，为充值成功页面所取
     * $used=='' 为1时，获取历史记录
     * $event_id=='' 为空时，默认为917活动，否则，为其他活动代号(1是2016双11活动)
     **/
    public function get_exchange917_items($fee='', $used='', $event_id=''){
        if($this->user_id){
            $data = array('user_id' => $this->user_id);
            if(!empty($fee)){
                $data['money'] = $fee;
            }
            if($used == 1){
                $data['used'] = $used;
            }
            if(!empty($event_id)){
                $data['event_id'] = $event_id;
            }
            $url = $this->host . $this->get_exchange917_items;
            $resp = DataRequst::sendPostRequst($url,$data);
            return $this->handle_response($resp);
        }
    }

    /**
     * 717活动获取已登录用户的可兑换装备链接
     * $fee=='' 为首页获取，不为空时，为充值成功页面所取
     * $used=='' 为1时，获取历史记录
     * $event_id 默认为717活动
     **/
    public function get_exchange717_items($fee='', $used='', $event_id=''){
        if($this->user_id){
            $data = array('user_id' => $this->user_id);
            if(!empty($fee)){
                $data['money'] = $fee;
            }
            if($used == 1){
                $data['used'] = $used;
            }
            if(!empty($event_id)){
                $data['event_id'] = $event_id;
            }
            $url = $this->host . $this->get_exchange717_items;
            $resp = DataRequst::sendPostRequst($url,$data);
            return $this->handle_response($resp);
        }
    }

    /**
     * 917活动获取已登录用户的可兑换装备链接
     * $fee=='' 为首页获取，不为空时，为充值成功页面所取
     * $used=='' 为1时，获取历史记录
     * $event_id=='' 为空时，默认为917活动，否则，为其他活动代号(1是2016双11活动)
     **/
    public function active_duihuan_url($fee='', $used='', $event_id=''){
        if($this->user_id){
            $data = array('user_id' => $this->user_id);
            if(!empty($fee)){
                $data['money'] = $fee;
            }
            if($used == 1){
                $data['used'] = $used;
            }
            if(!empty($event_id)){
                $data['event_id'] = $event_id;
            }
            $url = $this->host . $this->active_duihuan_url;
            $resp = DataRequst::sendPostRequst($url,$data);
            return $this->handle_response($resp);
        }
    }

    /**
     * 获取可配置活动的信息
     * @param business_id 活动ID
     **/
    public function get_active_detail_by_id($business_id){
        $url = $this->host . $this->get_active_detail_by_id . '?event_id=' . $business_id;
        $resp = DataRequst::sendGetRequst($url);
        return $this->handle_response($resp);
    }

    /**
     * 获取银行营销活动的信息
     * @param business_id 活动ID
     **/
    public function get_bankcharge_item($business_id){
        $url = $this->host . $this->get_bankcharge_item . '?event_id=' . $business_id;
        $resp = DataRequst::sendGetRequst($url);
        return $this->handle_response($resp);
    }

    /**
     * 获取当前正在进行的活动信息
     **/
    public function get_online_events(){
        $url = $this->host . $this->get_online_events;
        $resp = DataRequst::sendGetRequst($url);
        return $this->handle_response($resp);
    }

    /**
     * 普通充值页下方【兑换活动充值码】链接
     **/
    public function get_recommend_event(){
        $url = $this->host . $this->get_recommend_event;
        $resp = DataRequst::sendGetRequst($url);
        return $this->handle_response($resp);
    }

     /**
     * 生成余额商城免登录地址
     * @param user_id 用户id
     * @param user_type 用户类型 (0:微信, 1:IOS, 2:android )
     * @param mark 用户来源渠道
     * @param balance 用户账户余额
     **/
    public function get_balance_mall_url($user_id, $user_type, $mark, $balance){
        $url = $this->host . $this->get_balance_mall_url . '?uid=' . $user_id . '&utype=' . $user_type . '&mark=' . $mark . '&balance=' . $balance;
        $resp = DataRequst::sendGetRequst($url);
        return $this->handle_response($resp);
    }

    /**
     * 零彩宝活动，用于存储零彩宝订单彩票信息
     **/
    public function set_lingcaibao_lottery($params){
        if($this->user_id){
            $url = $this->host . $this->set_lingcaibao_lottery_url;
            $resp = DataRequst::sendPostRequst($url, $params);
            return $this->handle_response($resp);
        }
    }

    /**
     * 零彩宝活动，用于统计用户累计获奖金额
     **/
    public function get_lingcb_total_prize(){
        if($this->user_id){
            $url = $this->host . $this->get_lingcb_total_prize.'?fan_id='. $this->user_id;
            $resp = DataRequst::sendGetRequst($url);
            return $this->handle_response($resp);
        }
    }
    
    // 处理返回结果
    private function handle_response($resp) {
    	$res = json_decode($resp, true);
    	if($res['data']){
    		return $res['data'];
    	}else{
    		return array();
    	}
    }
    
    // 生成签名
    private function sign($array){
    	ksort($array);
    	$string = '';
    	while(list($key, $val) = each($array)){
    		$string .= $val ;
    	}
    	return md5($string);
    }
	
}
