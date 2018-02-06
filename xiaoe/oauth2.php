<?php 
define('IA_ROOT', str_replace("\\", '/', dirname(__FILE__)));
require IA_ROOT . '/config.php';
require IA_ROOT.'/http.php';
require IA_ROOT.'/oauth.php';
$config = new Config();
$oauth = new Oauth($config);

//获取api的access token
// $access_token = $oauth->getApiToken();
//获取完access　token 以后，一个小时之内用同一个token 请求小ｅ管家的api
// var_dump($access_token); 


//获取跳转的location
// $redirectUrl = 'http://xiaoe.edaixi.cn/';
// $state = 1;
// $location = $oauth->getXiaoeLocation($redirectUrl,$state);
// echo $location;
// header("Location: ".$location);

// if(isset($_GET['state']) and  ($_GET['state'] == 1 ) and isset($_GET['code'])  ){

// $code = $_GET['code'];


$code = '13f930dd85ef6f4859ce4245e736bf05e33d5763';
$redirectUrl = 'http://xiaoe.edaixi.cn/';
$userinfo = $oauth->getTokenByCode($redirectUrl,$code);
var_dump($userinfo);


/**

object(stdClass)#3 (7) {
  ["access_token"]=>
  string(40) "d2f2d70a739062b0395f7288c067e15035d0cbce"  //
  ["expires_in"]=>
  int(3600)
  ["token_type"]=>
  string(6) "Bearer"
  ["scope"]=>
  NULL
  ["openid"]=>
  string(32) "11ddbaf3386aea1f2974eee984542152"
  ["tel"]=>
  string(12) "118911111111"
  ["refresh_token"]=>
  string(40) "3b485201f786064b1f38297360307e270bdee2d4"
}

// }

*/





