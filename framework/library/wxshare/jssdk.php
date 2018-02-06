<?php
class JSSDK {
  private $appId;
  private $appSecret;

  public function __construct($appId, $appSecret) {
    $this->appId = $appId;
    $this->appSecret = $appSecret;
  }

  public function getSignPackage($schema = 'http://') {
    $jsapiTicket = $this->getJsApiTicket();
    $url = $schema."$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $timestamp = time();
    $nonceStr = $this->createNonceStr();

    // 这里参数的顺序要按照 key 值 ASCII 码升序排序
    $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

    $signature = sha1($string);

    $signPackage = array(
      "appId"     => $this->appId,
      "nonceStr"  => $nonceStr,
      "timestamp" => $timestamp,
      "url"       => $url,
      "signature" => $signature,
      "rawString" => $string
    );
    return $signPackage; 
  }

  private function createNonceStr($length = 16) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $str = "";
    for ($i = 0; $i < $length; $i++) {
      $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
  }

  private function getJsApiTicket() {
    // jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
    $ticket_key = 'weixin:jsapi:ticket';
    $data = json_decode(mcache()->get($ticket_key));
    // $data = json_decode(file_get_contents("jsapi_ticket.json"));
    if ($data->expire_time < time()) {
      $accessToken = $this->getAccessToken();
      $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
      $res = json_decode($this->httpGet($url));
      $ticket = $res->ticket;
      if ($ticket) {
        $data->expire_time = time() + 7000;
        $data->jsapi_ticket = $ticket;
        mcache()->set($ticket_key,json_encode($data));
        // $fp = fopen("jsapi_ticket.json", "w");
        // fwrite($fp, json_encode($data));
        // fclose($fp);
      }
    } else {
      $ticket = $data->jsapi_ticket;
    }

    return $ticket;
  }

 private function sendPostRequst($url, $data) {
    $opts = array (
        'http' => array (
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded',
            'content' => $data
        ) 
    );
    $context = stream_context_create ( $opts );
    $result = file_get_contents ( $url, false, $context );

    return $result;
  }
 private function getAccessToken() {
    global $_W;
    $account['key'] = $_W['config']['app']['appid'];
    $account['secret'] = $_W['config']['app']['secret'];
    $access_token =  account_weixin_token($account);
    /* 
    $url = "http://wx.rongchain.com/mobile.php?act=module&name=washing&do=iosapi&weid=5";
    $ts = time();
    $params = array('op' =>'access_token', 'ts' => $ts, 'app_token' => 'rongchainapi', 'user_type' => 2);
    $ts .= 'rongchainapi'; 
    $params['sign'] = md5($ts);
    $res = $this->sendPostRequst($url, json_encode($params));
    $access_token = json_decode(json_decode($res)->data)->access_token;
	*/
    return $access_token;
  }

  private function httpGet($url) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 1000);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_URL, $url);

    $res = curl_exec($curl);
    curl_close($curl);

    return $res;
  }
}

