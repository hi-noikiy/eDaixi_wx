<?php
/* *
 * 功能：支付宝服务器异步通知页面
 * 版本：3.3
 * 日期：2012-07-23
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。


 *************************页面功能说明*************************
 * 创建该页面文件时，请留心该页面文件中无任何HTML代码及空格。
 * 该页面不能在本机电脑测试，请到服务器上做测试。请确保外部可以访问该页面。
 * 该页面调试工具请使用写文本函数logResult，该函数已被默认关闭，见alipay_notify_class.php中的函数verifyNotify
 * 如果没有收到该页面返回的 success 信息，支付宝会在24小时内按一定的时间策略重发通知
 */
error_reporting(0);
// define('IN_MOBILE', true);

require '../../source/bootstrap.inc.php';
require_once IA_ROOT . '/source/library/edaixi/api_server.class.php';

require_once("alipay.config.php");
require_once("lib/alipay_notify.class.php");

logResult($_POST['out_trade_no'].'请求');
//计算得出通知验证结果
$alipayNotify = new AlipayNotify($alipay_config);
$verify_result = $alipayNotify->verifyNotify();

if($verify_result) {//验证成功
  logResult($_POST['out_trade_no'].'-'.$_POST['trade_status'].'-'.$_POST['trade_no'].'-'.'校验成功');
	//商户订单号

  $out_trade_no = $_POST['out_trade_no'];

	//支付宝交易号

  $trade_no = $_POST['trade_no'];

	//交易状态
  $trade_status = $_POST['trade_status'];


  if($_POST['trade_status'] == 'TRADE_FINISHED' || $_POST['trade_status'] == 'TRADE_SUCCESS') {
    $plid = $out_trade_no;
    if(strlen($plid) < 8){
      $sql = 'SELECT * FROM ' . tablename('paylog') . " WHERE plid=:plid";  
    }else{
      $sql = 'SELECT * FROM ' . tablename('paylog') . " WHERE tid=:plid"; 
    }

    $params = array();
    $params[':plid'] = $plid;
    $log = pdo_fetch($sql, $params);
    if(!empty($log) && $log['status'] == '0') {
      $record = array();
      $record['status'] = '1';
      $tag = array();
      $tag['transaction_id'] = $trade_no;
      $record['tag'] = iserializer($tag);
      pdo_update('paylog', $record, array('plid' => $log['plid']));

      $api_server = new ApiServer($_W['config']);
      $ret = $api_server->paylog_success($log['plid']);
      echo 'success';
    }
  }
}
else {
  logResult($_POST['out_trade_no'].'校验失败');
  //验证失败
  echo "fail";
}
?>