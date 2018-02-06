<?php
defined('IN_IA') or exit('Access Denied');
$config = array();
$config['db']['host'] = '127.0.0.1';
$config['db']['username'] = 'root';
$config['db']['password'] = '';
$config['db']['port'] = '3306';
$config['db']['database'] = 'api_server2';
$config['db']['charset'] = 'utf8';
$config['db']['pconnect'] = 0;
$config['db']['tablepre'] = 'ims_';
// --------------------------  CONFIG COOKIE  --------------------------- //
$config['cookie']['pre'] = 'f150_';
$config['cookie']['dome'] = '';
$config['cookie']['path'] = '/';
// --------------------------  CONFIG SETTING  --------------------------- //
$config['setting']['charset'] = 'utf-8';
$config['setting']['cache'] = 'mysql';
$config['setting']['timezone'] = 'Asia/Shanghai';
$config['setting']['memory_limit'] = '256M';
$config['setting']['filemode'] = 0644;

$config['setting']['authkey'] = 'fc45165fca7b817b_';
$config['setting']['development'] = '51';
$config['setting']['assets_host'] = '';
//是否在测试环境下
$config['setting']['test'] = true;

// 开启验证码
$config['captcha']['weixin'] = true;
$config['captcha']['web'] = true;

// 开启数据统计
$config['statistics']['baidu'] = false;
$config['statistics']['piwik'] = false;
$config['piwik']['setting'] = 0;

if(filter_var($_SERVER['HTTP_HOST'], FILTER_VALIDATE_IP)) {
  $config['setting']['ts'] = '/weixin';
}else {
  $config['setting']['ts'] = '';
}

$config['site']['static']['html'] = 'http://www07.edaixi.cn:81/';

$config['memcache']['host'] = '127.0.0.1';
$config['memcache']['port'] = '11211';
$config['redis']['host'] = '127.0.0.1';
$config['redis']['port'] = '6379';

$config['upload']['attachdir'] = 'resource/attachment/';
$config['upload']['attachurl'] = 'http://assets0-edaixi.qiniudn.com/resource/attachment/';


$config['app']['appid'] = 'wx1defeb5ede48566f';
$config['app']['secret'] = '1a498b40985a5e52bd2260de8a06c40d';

//$config['oauth']['url'] = 'http://oauth07.edaixi.cn';
$config['oauth']['url'] = 'http://oauth.javed.cn';
$config['site']['root'] = 'http://weixin.javed.cn/';
$config['pay']['domain'] = 'http://payment07.edaixi.cn/';

// $config['edaixi']['open_server'] = 'http://open17.edaixi.cn:81';
$config['edaixi']['open_server'] = 'http://open07.edaixi.cn:81';

// $config['edaixi']['api_server'] = 'http://wx17.edaixi.cn:3329';
$config['edaixi']['api_server'] = 'http://wx07.edaixi.cn:3329';

// 积分商城服务
$config['edaixi']['sw_server'] = 'http://swapi07.edaixi.cn:81';
//$config['edaixi']['sw_server'] = 'http://127.0.0.1:9501';
$config['sw_server']['key'] = '3JdahN78n62xuEemEBmDSA1fKEKr';
$config['sw_server']['secret'] = '3tN8wHhgYABn9cSrfTSYSUadzhmw';

//蚁匠link，用于联合登录
$config['yijiang_link'] = 'http://weixin.hunjia168.com/kt_service/index';

// --------------------------  CONFIG UPLOAD  --------------------------- //

$config['payment'] = array (
  'credit' => 
  array (
    'switch' => false,
  ),
  'alipay' => 
  array (
    'switch' => true,
    'account' => '1059813699@qq.com',
    'partner' => '2088512143837202',
    'secret' => 'zkbnm6ix1372upaogugp40gabjibl5ew',
  ),
  'wechat' => 
  array (
    'switch' => true,
    'appid' => 'wxeff9858c74dc72a2',
    'secret' => '2fa502f1cb673757a2c14053cb1d597e',
    'signkey' => 'zm0GsynFhVuhMAjZs0w4CTc0ICmQDCZwjux5nK6ttnA4OHnBaKZ8t35ZsJFTG1KPkAI399lXQKjsKxUf6cGmz4cP9czE38iH4l42hchqfWJlOYTJYUqmOXwKYTclRkTK',
    'partner' => '1218098401',
    'key' => '50a2a512fdeb4e65175ddf7c5c887539',
  ),
  'wechat_open' => 
  array (
    'switch' => true,
    'key' => '501a5db5c054acf3434bf5e5d29b04d5',
  ),
  'psd_zbt' =>
  array(
    'app_id' => '10254',
    'app_secret' => '62laas9wk8x1cwj3cq36wjw2hyy8r41r',
    'order_url' => 'https://spd.jzjbeauty.com/api',
    'pay_url' => 'https://spd.jzjbeauty.com/bts/PFPayment/payorder'
  )
);

$config['payment']['wechat_v4'] = array(
  'switch' => true,
  'appid' => 'wx1defeb5ede48566f',
  'partner' => '1232523402',
  'key' => 'dc7fb6cff4a92a23c8bb7b3276c49509',
  );

$config['payment']['wechat_h5'] = array(
  'switch' => true,
  'appid' => 'wxeff9858c74dc72a2',
  'partner' => '1345297201',
  'key' => '50a2a512fdeb4e65175ddf7c5c887539',
  'user_type' => '20'
  );

//衣物品类的图片配置　品类id => 图片　０是默认
$config['category_images'] = array(
    0   =>  '/framework/style/images/xi_cloth.png',
    1   =>  '/framework/style/images/xi_cloth.png',
    2   =>  '/framework/style/images/xi_shoes.png',
    3   =>  '/framework/style/images/chuanglian.png',
    4   =>  '/framework/style/images/shechipin.png',
    5   =>  '/framework/style/images/piyi.png',
    7   =>  '/framework/style/images/daixi.png',
    17   =>  '/framework/style/images/kuaixi_c.png',
    60   =>  '/framework/style/images/caiyi.png',
    61   =>  '/framework/style/images/caiyi.png',
);

//支付配置
$config['pay_config'] = array(
        //微信支付
        2 => array(
            //html中id名称
            'name' => 'weixin_pay',
            //展示的名称
            'show_name' => '微信支付',
            //支付的显示图标
            'img_url' => '/framework/style/images/wechat_pay.png',
            //支付方式是否可用
            'is_usable' => true
          ),
        //现金支付
        3 => array(
            'name' => 'cash_pay',
            'img_url' => '/framework/style/images/Cash_payment.png',
            'show_name' => '现金支付',
            'is_usable' => true
          ),
        //支付宝支付
        6 => array(
            'name' => 'ali_pay',
            'img_url' => '/framework/style/images/zhifubao_pay.png',
            'show_name' => '支付宝支付',
            'is_usable' => true
          ),
        //百度支付
        11 => array(
            'name' => 'baidu_pay',
            'img_url' => '/framework/style/images/baidu.png',
            'show_name' => '百度支付',
            'is_usable' => true
          ),
        //浦发周边通支付
        17 => array(
            'name' => 'psd_zbt_pay',
            'img_url' => '/framework/style/images/pufa.png',
            'show_name' => '浦发周边通支付',
            'is_usable' => true
          ),
        //渤海银行支付
        18 => array(
            'name' => 'bohai_pay',
            'img_url' => '/framework/style/images/bohai.png',
            'show_name' => '渤海银行支付',
            'is_usable' => true
          ),
        //糯米支付
        19 => array(
            'name' => 'nuomi_pay',
            'img_url' => '/framework/style/images/baidu.png',
            'show_name' => '糯米支付',
            'is_usable' => true
          ),
        //招行一网通支付
        20 => array(
            'name' => 'cmb_pay',
            'img_url' => '/framework/style/images/cmb.png',
            'show_name' => '一网通银行卡支付',
            'is_usable' => true
          ),
        //银联云闪付
        25 => array(
            'name' => 'union_pay',
            'img_url' => '/framework/style/images/union_pay_icon.png',
            'show_name' => '银联云闪付',
            'is_usable' => true
          ),
      );
  // 前端url
$config['web_url_config'] = array(
    'index_url' =>  '/new_weixin/index.html', // 首页的url
    'washing_prices_url' => '/new_weixin/view/washing_prices.html', // 价目页
    'comm_price_url'  =>  '/new_weixin/view/washing_prices.html',  // 普洗价目页的url
    'comm_order_place'  =>  'comm_order_place.html',  // 普洗下单页
    'select_datetime'  =>  'select_datetime.html',  // 时间控件页面
    'recharge_online_url'  =>  '/new_weixin/view/recharge_online.html',  // 在线充值
    'recharge_cardno_url'  =>  '/new_weixin/view/recharge_cardno.html',  // 充值卡充值
  );

$config['payment']['nuomi'] = array(
  'appkey' => 'MMM4SF',
  'appid' => '10160',
  'dealid' => '3842404606',
  'return_url' => 'http://wx.rongchain.com/mobile.php',
  //'create_url' => 'http://comout.nuomi.com/component/nuomi_cashier/order_create/order_create.html?tpData=',
  'create_url' => 'http://m.nuomi.com/component/nuomi_cashier/order_create/order_create.html?tpData=',
  'prikey' => '-----BEGIN RSA PRIVATE KEY-----
MIICXgIBAAKBgQChrJs8wnkQaOHTVhobGpbmpTpKUnRE2oCsj8+NpbIJNjuINCLX
kGC+ANtCdgrrzceHDp4tD9EfOCk4GaUxsD4AnFJNVBGXiBpZK5iqBNLs7HXld4hD
uR1JUY6olT93sbr9u7qi4coV1GxAYkfUCjjjDq/Uggwopq/1nsID+aJh1wIDAQAB
AoGAGLar3DPWEb4WlxmYUABWhNdI+8dy4nuMI9Sv43Uqq+HQY9ekY9M8Fo9G9Pfa
X4VLNMf1QeojC2SoWF0DNX89WthQVsQAfl8To6+QEvaTevIVE9T65wYVZt3U1rRS
+FPdeiohdi8/I8s2eR3BAN0V+TM2Kru93h1TBM4PCtfi45ECQQDR1DzroUEDDpCg
WG1UihvmmmqznpWg0QO141IMWjl/pquJbToecQNKaxYeglXVnDNKnVsqynP3CHrj
8XhQkEq1AkEAxT/JdhqEoU74jgRTNeuoqKLAeaJVS4xjX16PY2P5YgYPqfStfDkN
Xj/QqqYYzhFo83cofYVtdlVLYE5mya812wJBAKjZw9RRxUEyfUurHP/Ey8L8TBUS
7pGk4PmDXkk2acLBWpSSYwyQIPfHyNrwQyZMNjI7Alwi4sLN/iZj8E/UbWkCQQCS
PXmTC5QbuF4FhOVgi8iuxoOnapiMe5hNY+ExUjTZ3R6N5i8dc3sazntZme24EiZa
o5Ssg1klq5VH4LjKfRGTAkEAiuZHe6qnL6zh2nVgtNm8u8UJRN+io99HFL85SIl5
A6o7xGHrhFISakaLJ7WZX3jQhFZS7/AzDeIMShT1AhJdvw==
-----END RSA PRIVATE KEY-----'
  );
