<?php
namespace Edx\Model;
use Edx\Helper\Common;
defined('IN_IA') or exit('Access Denied');
/**
 * 支付模型
 */
class pay extends edxModel {

    //微信支付
    const WEIXIN_PAY = 2;
    //现金支付
    const XIANJIN_PAY = 3;
    //支付宝支付
    const ZHIFUBAO_PAY = 6;
    //百度支付
    const BAIDU_PAY = 11;
    
    //支付相关配置
    private $pay_config = array();

    public function __construct($params)
    {
        parent::__construct($params);
        global $_W;
        //支付配置
        $this->pay_config = $_W['config']['pay_config'];
    }

    /**
     * 获取用户待支付订单列表(返回和传入order_id相同城市的订单)
     * @param int $user_id 用户id
     * @param int $order_id 订单id 
     */
    public function payList($user_id, $user_type, $order_id)
    {
        global $_W;
        $category_images = $_W['config']['category_images'];
        $pay_list = array();
        $open_server = $this->open_server;
        $data = array(
                'user_id' => $user_id,
                'user_type' => $user_type,
                'order_id' => $order_id
            );
        $ret = $open_server->settlements_list($data);
        if (!empty($ret)) {
            $total_money = 0;
            //计算订单总金额（包括运费）
            foreach ($ret as &$order_group) {
                $total_money = $total_money + 
                    $order_group['delivery_fee_info']['delivery_fee'];
                $i = 0;
                $length = count($order_group['orders']);
                foreach ($order_group['orders'] as &$order) {
                    $i++;
                    $order_price = $order['money_without_delivery_fee'];
                    $total_money = $total_money + $order_price;
                    $order['img_url'] = 
                       isset($category_images[$order['category_id']]) ?
                       $category_images[$order['category_id']] :
                       $category_images[0];
                    $order['order_sn_show'] = 
                      Common::formatOrderSn($order['ordersn']);
                    if ($i == $length) {
                        $order['is_last'] = true;
                    } else {
                        $order['is_last'] = false;
                    }
                }
                if (empty($order_group['delivery_fee_info']['tips'])) {
                    $order_group['tip-display'] = 'style="display:none;"';
                    $order_group['delivery_fee_info']['tips'] = array(
                            'tag' => '',
                            'text' => ''
                        );
                } else {
                    $order_group['tip-display'] = '';
                }
            }
            $pay_list = array(
                    'group' => $ret,
                    'total_money' => Common::round($total_money)
                );
        }
        return $pay_list;
    }

    /**
     * 合并支付查询订单运费
     */
    public function orderDeliveryFee($user_id, $orders, $city_id)
    {
        $delivery_list = array();
        if (is_array($orders) &&
            !empty($orders)) {
            $order_data = array();
            foreach ($orders as $group_id => $group_info) {
                $value = array(
                        'category_group_id' => (string)$group_id,
                        'ids' => array()
                    );
                foreach ($group_info as $order_id) {
                    if (!empty($order_id)) {
                        $value['ids'][] = (int)$order_id;
                    }
                }
                $order_data[] = $value;
            }
            $open_server = $this->open_server;
            $order_data = json_encode($order_data);
            $data = array(
                    'user_id' => $user_id,
                    'orders' => $order_data,
                    'city_id' => $city_id
                );
            $open_ret = $open_server->caclulate_delivery_fee($data);
            if (is_array($open_ret) &&
                !isset($open_ret['ret'])) {
                foreach ($open_ret as &$delivery) {
                    if (empty($delivery['tips'])) {
                        //运费优惠提示
                        $delivery['tips'] = (object)array();
                    }
                }
                $delivery_list = $open_ret;
            }
        }
        return $delivery_list;
    }

    /**
     * 获取合并支付付款信息
     */
    function getPayInfo($user_id, $user_type, $order_group_ids, $in_weixin)
    {
        $pay_info = array();
        if (!empty($user_id) &&
            !empty($order_group_ids)) {
            $open_server = $this->open_server;
            $pay_data = array(
                    'user_id' => $user_id,
                    'user_type' => $user_type,
                    'order_group_ids' => $order_group_ids
                );
            $open_ret = $open_server->get_pay_info($pay_data);
            if (!empty($open_ret)) {
                $pay_info = $open_ret;
                //格式化订单编号
                if (is_array($pay_info['orders_info'])) {
                    foreach ($pay_info['orders_info'] as &$order_info) {
                        $order_info['ordersn'] = 
                            Common::formatOrderSn($order_info['ordersn']);
                    }
                }
                //整理第三方支付方式
                $pay_config = $this->pay_config;
                $third_pay = array();
                if (is_array($pay_info['third']['all'])) {
                  foreach ($pay_info['third']['all'] as $pay_type) {
                    //支付方式可用
                    if (!empty($pay_config[$pay_type]['is_usable'])) {
                      /*if ($pay_type == 2 && !$in_weixin) {
                        //非微信环境调用微信支付
                        continue;
                      }*/
                      $third_pay[$pay_type] = $pay_config[$pay_type];
                      if (!in_array($pay_type, $pay_info['third']['usable'])) {
                        $third_pay[$pay_type]['is_usable'] = false;
                      }
                    }
                  }
                }
                $pay_info['third_pay'] = $third_pay;
            }
        }
        return $pay_info;
    }
    /**
    * 洗衣液支付信息
    */
    public function getDetergentPayInfo($user_id, $order_id)
    {   
        $pay_info = array();
        $order = $this->open_server->get_physical_order_info($user_id, $order_id);
        $pay_config = $this->pay_config;
        // 订单总额
        $pay_info['order_amount'] = number_format($order['total_price'], 2); 
        // 支付方式
        foreach ($pay_config as $key => $value) {
            if (in_array($key, array(2, 11))) {
                $pay_info['third']['usable'][] = $key; 
                $pay_info['third_pay'][$key] = $value;
            }
        }
        $pay_info['third_amount'] = $pay_info['order_amount'];
        return $pay_info;

    }

    /**
     * 计算订单付款金额
     * @param int user_id 用户id
     * @param bool choose_icard 是否选择e卡
     * @param json string order_group_ids 多个订单id json字符串
     * @param json string ecard_ids 多个e卡id json字符串
     * @param int coupon_id 优惠券id
     */
    public function caclulateOrderPrice($user_id, $user_type, $choose_icard,
      $order_group_ids, $ecard_ids, $coupon_id, $action)
    {
      $pay_price = array();
      if ($user_id &&
          $order_group_ids) {
        $open_server = $this->open_server;
        $data = array(
            'user_id' => $user_id,
            'user_type' => $user_type,
            'choose_icard' => $choose_icard,
            'order_group_ids' => $order_group_ids,
            'action' => $action
          );
        if (!empty($ecard_ids)) {
          $data['ecard_ids'] = $ecard_ids;
        }
        if (is_numeric($coupon_id)) {
          $data['coupon_id'] = $coupon_id;
        }
        $open_ret = $open_server->caclulate_order_price($data);
        if (!empty($open_ret)) {
          $pay_price = $open_ret;
        }
      }
      return $pay_price;
    }

    /**
     * 获取运费说明信息
     * @param int $order_city_id 订单的城市id
     */
    public function deliveryInfo($order_city_id)
    {
        $delivery_info = array();
        if ($order_city_id) {
            $open_server = $this->open_server;
            $data = array(
                    'city_id' => $order_city_id
                );
            $open_ret = $open_server->delivery_fee_info($data);
            if (!empty($open_ret)) {
                $delivery_info = $open_ret;
            }
        }
        return $delivery_info;
    }

    /**
     * 订单支付接口
     */
    public function payOrder($pay_info, $user_id, $user_type)
    {
        $pay_result = array();
        $open_server = $this->open_server;
        $data = array(
                'order_group_ids' => $pay_info['order_list'],
                'pay_type' => $pay_info['paytype'],
                'total_price' => $pay_info['third_price'],
                'coupon_fee' => $pay_info['coupon_fee'],
                'ecard_fee' => $pay_info['ecard_fee'],
                'icard_fee' => $pay_info['icard_fee'],
                'choose_icard' => $pay_info['choose_icard'],
                'ecard_ids' => $pay_info['ecard_list'],
                'coupon_id' => $pay_info['coupon_id'],
                'user_id' => $user_id,
                'user_type' => $user_type,
                'is_physical' => $pay_info['is_physical'],
                'physical_order_id' => $pay_info['physical_order_id'],
            );
        $open_ret = $open_server->pay_order_v5($data);
        if (!empty($open_ret)) {
            $pay_result = $open_ret;
        }
        return $pay_result;
    }

    /**
     * 订单取消支付
     * @param int $user_id 用户id
     * @param int $order_id 订单id
     */
    function cancelPay($user_id, $order_id)
    {
        $cancel_pay = array(
                'status' => false,
                'msg' => '取消支付失败啦'
            );
        $open_server = $this->open_server;
        if ($user_id && $order_id) {
            $data = array(
                    'user_id' => $user_id,
                    'order_id' => $order_id
                );
            $open_ret = $open_server->cancel_pay($data);
            if (isset($open_ret['ret']) &&
                $open_ret['ret']) {
                $cancel_pay['status'] = true;
                $cancel_pay['msg'] = '';
            } else {
                if (!empty($open_ret['error'])) {
                    $cancel_pay['msg'] = $open_ret['error'];
                }
            }
        }
        return $cancel_pay;
    }

    /**
     * 调用第三方支付(支付宝/微信/百度/浦发周边通)
     * @param array thirdPay 支付参数
     *  array(
     *     'tid' => '23resafd', //支付的流水号
     *     'order_id' => '2323', //订单id
     *     'user' => // from_user
     *     'fee' => '23.33', //订单支付金额，单位元
     *     'title' => '支付时的订单名称'
     *  ) 
     */
    public function thirdPay($params, $pay_type) {
        global $_W, $_GPC;
        $pay_data = array();
        $user_type = isset($params['user_type']) ? $params['user_type'] : 0;
        unset($params['user_type']);
        if (!empty($params['tid']) &&
            !empty($params['fee']) &&
            !empty($pay_type)) {
            require_once IA_ROOT . '/framework/model/payment.mod.php';
            if($pay_type == 2){  # 微信支付
                $sl = base64_encode(json_encode($params));
                $auth = sha1($sl . $_W['weid'] . $_W['config']['setting']['authkey']);
                
                if( false === strpos( ',' . $_W['account']['payment']['wechat_h5']['user_type'] . ',', ',' . $user_type . ',')  ) { //微信浏览器支付    
                    // $pay_data['url'] = "{$_W['config']['site']['root']}payment/wechat/pay.php?weid={$_W['weid']}&auth={$auth}&ps={$sl}";
                    //$params['fee'] = number_format($params['fee'], 2, '.', '');
                    $sql = 'SELECT * FROM ' . tablename('paylog') . ' WHERE `tid`=:tid';
                    $log = pdo_fetch($sql, array(':tid' => $params['tid']));
                    $params['fee'] = number_format($log['fee'], 2, '.', '');
                    $pay_data['html'] = wechat_build_v4($params, $_W['account']['payment']['wechat_v4']);
                } else { //非微信浏览器支付
                    $pay_data['success_url'] = "{$_W['config']['site']['root']}payment/wechat/payh5.php?weid={$_W['weid']}&auth={$auth}&ps={$sl}";
                    $wxh5 = wechat_build_h5($params, $_W['account']['payment']['wechat_h5']);
                    $pay_data['pay_url']     = $wxh5['url'];
                }
            }else if ($pay_type == 6) {  # 支付宝支付
                $ret = alipay_build($params, $_W['account']['payment']['alipay']);
                if ($ret['url']) {
                    $pay_data['url'] = $ret['url'];
                }
            } else if($pay_type == 11){  # 百度支付
                $ret = baidu_build($params, $_W['account']['payment']['baidu']);
                if ($ret) {
                    $pay_data['html'] = $ret;
                }
            }else if($pay_type == 17){  # 浦发周边通
                $params['open_userid'] = $this->user_info['from_user'];
                $url = psd_zbt_build($params, $_W['config']['payment']['psd_zbt']);
                $pay_data['url'] = $url;
            }else if($pay_type == 19){  # 糯米收银台
                //$params['open_userid'] = $this->user_info['from_user'];
                $html = nuomi_build($params, $_W['config']['payment']['nuomi']);
                $pay_data['html'] = $html;
            }else if($pay_type == 24){  # 雅居乐
                //$params['open_userid'] = $this->user_info['from_user'];
                $html = yajule_build($params, $_W['config']['payment']['yajule']);
                $pay_data['html'] = $html;
            }
            else if($pay_type == 20){ #招行一网通支付
                if(isset($_SESSION['cmb_online_recharge'])){
                    unset($_SESSION['cmb_online_recharge']);
                    $jump_str = 'mobile.php?m=wap&act=icard&do=icard_charge&status=1';
                }else{
                    $jump_str = 'mobile.php?m=wap&act=order&do=order_list';
                }
                $return_url = $_W['config']['site']['root'] . $jump_str;
                $ret = $this->open_server->payment_sign($params['user'], $user_type, $pay_type, $params['tid'], $params['fee'], 0, urlencode($return_url));
                if(!empty($ret['data'])){
                    echo $ret['data']['html'];
                    exit;
                }else{
                    error_report('支付失败，请稍后再试');
                }
            }else if($pay_type == 18){  # 渤海银行
                $return_url = json_encode(return_url_by_pay_species());
                $is_recharge = 0;
                if(false !== stripos($params['title'], "充值")){
                    $is_recharge = 1;
                }
                $res = $this->open_server->payment_sign($this->user_info['user_id'], $user_type, $pay_type, $params['tid'], $params['fee'], $is_recharge, $return_url, $this->user_info['from_user'], $this->user_info['is_login']);
                if ($res['ret'] == true && $res['data']['url']){
                    $pay_data['url'] = $res['data']['url'];
                }else{
                    $pay_data['html'] = '预创建订单失败';
                }
            }else if($pay_type == 25){  # 银联云闪付
                $return_url = urlencode(return_url_by_pay_species());
                $is_recharge = 0;
                if(false !== stripos($params['title'], "充值")){
                    $is_recharge = 1;
                }
                $res = $this->open_server->payment_sign($this->user_info['user_id'], $user_type, $pay_type, $params['tid'], $params['fee'], $is_recharge, $return_url, $this->user_info['from_user'], $this->user_info['is_login']);
                if ($res['ret'] == true && $res['data']){
                    $pay_data['html'] = $res['data'];
                }else{
                    $pay_data['html'] = '预创建订单失败';
                }
            }
        }
        return $pay_data;
    } //end of thirdPay() function
}