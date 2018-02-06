<?php
/**
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 * $sn: htdocs/payment/wechat/native.php : v 1ced5ce4bed8 : 2014/03/19 08:35:31 : veryinf $
 */
define('IN_MOBILE', true);
$_POST['weid'] = 5;
require '../../source/bootstrap.inc.php';
include_once("WxPayHelper.php");
require_once IA_ROOT . '/source/library/edaixi/open_server.class.php';
require_once IA_ROOT . '/source/library/edaixi/api_server.class.php';

$input = file_get_contents('php://input');

$obj = simplexml_load_string($input, 'SimpleXMLElement', LIBXML_NOCDATA);
if($obj instanceof SimpleXMLElement) {
  $commonUtil = new CommonUtil();
  $wxPayHelper = new WxPayHelper();

  $data = array(
    'openid' => trim($obj->OpenId),
    'appid' => trim($obj->AppId),
    'productid' => trim($obj->ProductId),
    'timestamp' => trim($obj->TimeStamp),
    'noncestr' => trim($obj->NonceStr),
    'issubscribe' => trim($obj->IsSubscribe)
    );

  $sign = $wxPayHelper->get_biz_sign($data);
  if($sign == trim($obj->AppSignature)){
    global $_W;
    $wechat = $_W['account']['payment']['wechat'];

    $plid = $data['productid'];
    if($plid == 'kaika'){
      $fan = pdo_fetch("SELECT * FROM ".tablename('fans')." WHERE weid = :weid and from_user = :from_user", array(':weid' => $_W['weid'], ':from_user' => $data['openid']));
      if(!$fan){
        $insert=array(
          'weid'=>$_W['weid'],
          'from_user'=>$data['openid'],
          'user_type' => 1,
          'follow'=>0,
          'createtime'=>time(),
          );
        $temp=pdo_insert("fans",$insert);
        $fan_id = pdo_insertid();
      }else{
        $fan_id = $fan['id'];
      }

      $client = pdo_fetch("SELECT * FROM ".tablename('clients')." WHERE openid = :openid", array(':openid' => $data['openid']));
      if(!$client){
        $insert=array(
          'openid'=>$data['openid'],
          'user_type' => 1,
          'fan_id' => $fan_id,
          'createtime'=>time(),
          );
        $temp=pdo_insert("clients",$insert);
        $user_id = $fan_id;
      }else{
        $user_id = $client['fan_id'];
      }

      $api_server = new ApiServer($_W['config']);
      $paylog = $api_server->icard_kaika($user_id, 50, 2);

      if(!empty($paylog)){
        $wxPayHelper->setParameter("bank_type", "WX");
        $wxPayHelper->setParameter("body", "荣昌e袋洗充50送50活动");
        $wxPayHelper->setParameter("partner", $wechat['partner']);
        $wxPayHelper->setParameter("out_trade_no", $paylog['data']['trade_no']);
        $wxPayHelper->setParameter("total_fee", 5000);
        $wxPayHelper->setParameter("fee_type", "1");
        $wxPayHelper->setParameter("notify_url", "http://wx.rongchain.com/payment/wechat/notify.php");
        $wxPayHelper->setParameter("spbill_create_ip", "127.0.0.1");
        $wxPayHelper->setParameter("input_charset", "UTF-8");

        echo $wxPayHelper->create_native_package();
      }else{
        echo $wxPayHelper->create_native_package(3, '网络错误');
      }
    }else{
      $open_server = new OpenServer($_W['config']);

      $paylog = $open_server->get_paylog($plid);
      if(!empty($paylog) && $paylog['data']['module'] == 'washing' && $paylog['data']['status'] == 0){
        $wxPayHelper->setParameter("bank_type", "WX");
        $wxPayHelper->setParameter("body", "荣昌e袋洗商城订单:".$paylog['data']['order_sn']);
        $wxPayHelper->setParameter("partner", $wechat['partner']);
        $wxPayHelper->setParameter("out_trade_no", $paylog['data']['plid']);
        $wxPayHelper->setParameter("total_fee", $paylog['data']['fee']*100);
        $wxPayHelper->setParameter("fee_type", "1");
        $wxPayHelper->setParameter("notify_url", "http://wx.rongchain.com/payment/wechat/notify.php");
        $wxPayHelper->setParameter("spbill_create_ip", "127.0.0.1");
        $wxPayHelper->setParameter("input_charset", "UTF-8");

        echo $wxPayHelper->create_native_package();  
      }else{
        echo $wxPayHelper->create_native_package(1, '该订单已支付');
      }
    }
  }else{
    echo $wxPayHelper->create_native_package(2, '签名错误');
  }
}

?>
