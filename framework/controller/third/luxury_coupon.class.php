<?php
/**
 * 奢侈品护理代金券购买活动
 *  活动日期：2015年07月08日00：00：00～2015年07月12日23：59:59
 *  活动规则：活动期间每位用户限购买1次，每日限78位用户购买，每日00:00:00将代金券数更新为78张
 *  支付方式：微信用户使用微信支付或支付宝支付，其他用户只能使用支付宝支付
 *  代金券使用：购买成功代金券发放至用户账户，在“个人中心--优惠券”查看使用 
 */
class luxury_coupon extends BaseModule{
	public function __construct(){
		global $_W;
		$this->api_server = new Apiserver($_W['config']);
		$this->model_name = 'luxury_coupon';
	}

	// 代金券活动页
	public function activity_page(){
		require IA_ROOT.'/framework/library/wxshare/include.php';
	    $res = $this->api_server->luxury_coupon_status();
	    $coupon_status = $res['data']['status'];
	    include $this->template('activity_page');
	}
	
	// 代金券购买页
	public function buy_page(){
	    $from_weixin = is_from_weixin();
		include $this->template('buy_page');
	}

	public function pay(){
	    
		include $this->template('pay');
	}

	// 获取验证码
	public function get_captcha(){
	    global $_GPC;
	    $tel = str_replace(PHP_EOL, '', $_GPC['tel']);
	    if(!check_mobile($tel)){
	    	$result['state'] = 0;
	    	$result['msg'] = '请正确填写手机号';
	    	message($result, '', 'ajax');
	    	exit;
	    }
	    
	    $res = $this->api_server->luxury_coupon_captcha($tel);
	    if($res['data']){
	        $result['state'] = 1;
	        $result['msg'] = '验证码发送成功';
	        message($result, '', 'ajax');
	    }else{
	        $result['state'] = 0;
	        $result['msg'] = '验证码发送失败';
	        message($result, '', 'ajax');
	    }
	}
	
	// 验证购买信息
	public function check_pay(){
	    global $_GPC;
	    $data = array(
	        'mobile' => str_replace(PHP_EOL, '', $_GPC['tel']),
	        'code' => str_replace(PHP_EOL, '', $_GPC['code']),
	        'name' => $_GPC['uname'],
	        'paytype' => $_GPC['paytype']
	    );
	    $res = $this->api_server->luxury_coupon_pay($data);
	    if(empty($res['data'])){
	        $result['state'] = '2';
	        $result['msg'] = $res['error'] ? $res['error'] : '支付失败';
	    }else{
	        $_SESSION['pay_species'] = 'luxury_coupon';
	        $uuid = md5(time().random(8));
	        
	        /*
	        // 测试金额
	        $res['data']['fee'] = 0.01;
	        //*/
	        
	        $params['tid'] = $res['data']['trade_no'];
	        $params['fee'] = $res['data']['fee'];
	        $params['title'] = '用户充值' . $params['fee'];
	        $params['user'] = $_GPC['tel'];
	        $params['paytype'] = $_GPC['paytype'];
	        mcache()->set($uuid, serialize($params), 1800);
	        
	        $result['state'] = '1';
	        $result['msg'] = '';
	        $result['url'] = create_url('luxury_coupon/do_pay', array('uuid' => $uuid), 'third');
	    }
	    message($result, '', 'ajax');
	}
	
	// 执行付款
	public function do_pay(){
	    global $_W, $_GPC;
	    if(empty($_GPC['uuid'])){
	        include $this->template('pay_failed');
	        exit;
	    }
        $params = unserialize(mcache()->get($_GPC['uuid']));
        if(empty($params)){
            include $this->template('pay_failed');
            exit;
        }
        
	    // 支付宝支付方式
	    if($params['paytype'] == 6){
	        $from_weixin = is_from_weixin();
	        # 如果微信用户选择支付宝支付
	        if($from_weixin){
	            # 重定向到支付宝支付页
	            $url = create_url('luxury_coupon/ali_pay', array('uuid' => $_GPC['uuid']), 'third');
	            header('Location: '.$url);
	            exit;
	        }
	        # 非微信用户选择支付宝支付
	        $_SESSION['pay_species'] = 'luxury_coupon'; # 重建 session，避免跨浏览器 session 丢失
	        require_once IA_ROOT . '/framework/model/payment.mod.php';
	        $ret = alipay_build($params, $_W['account']['payment']['alipay']);
	        if($ret['html']) {
	            echo $ret['html'];
	            exit();
	        }
	        if($ret['url']) {
	            header("location: " . $ret['url']);
	            exit();
	        }
	    }
	    // 微信支付方式
	    else if($params['paytype'] == 2){
	        require_once IA_ROOT . '/framework/model/payment.mod.php';
	        $sl = base64_encode(json_encode($params));
	        $auth = sha1($sl . $_W['weid'] . $_W['config']['setting']['authkey']);
	        header("location: {$_W['config']['site']['root']}payment/wechat/pay.php?weid={$_W['weid']}&auth={$auth}&ps={$sl}");
	        exit();
	    }
	}
	
	// 支付宝支付页
	public function ali_pay(){
	    $from_weixin = is_from_weixin();
	    if($from_weixin){
	    	include $this->template('ali_pay');
	    }else{
	    	$this->do_pay();
	    }
	}
	
	// 支付失败页
	public function pay_failed(){
		include $this->template('pay_failed');
	}

	// 支付成功页
	public function pay_success(){
		include $this->template('pay_success');
	}
    
}