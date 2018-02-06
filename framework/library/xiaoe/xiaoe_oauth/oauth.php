<?php
class Oauth{ 
        private $appid;
        private $appsecret;
        private $xiaoeurl;
        public function Oauth($config){
            $this->appid = $config->app['appid'];
            $this->appsecret = $config->app['secret'];
            $this->xiaoeurl = $config->app['url'];
        }
        public function getTokenByCode($redirectUrl,$code){
            // $code = $_GET['code'];
            if(isset($code)){
                    $Authorization = "Basic ".base64_encode($this->appid.':'.$this->appsecret);
                    $data = array(
                      'grant_type'=>'authorization_code',
                      'code'=>$code,
                      'redirect_uri'=>$redirectUrl
                    );
                    $url = $this->xiaoeurl.'token.php';
                     $resp = XEHttpClient::ihttp_post($url, $data,array("Authorization"=>$Authorization));
                     $ret = json_decode($resp['content']);
                     return $ret;
            }
        }
        public function getUserInfoByToken($access_token){
                    $data = array(
                      'access_token' =>   $access_token
                      );
                    $url = $this->xiaoeurl.'/'.'resource.php';
                    $resp = XEHttpClient::ihttp_post($url, $data,array());
                    $ret = json_decode($resp['content']);
                    return $ret;
        }
        public function getXiaoeLocation($redirectUrl,$scope = NULL){

          $action = 'authorize.php';
          if(!isset($state))
          {
              $state = 1;
          }
          $para = array(
              'response_type'=>'code',
              'client_id'=>$this->appid,
              'state'=>$state,
              'redirect_uri'=>$redirectUrl,
            );
          if(!empty($scope))
          {
            $para['scope'] = 'tel';
          }
          $query = http_build_query( $para);
          $location = $this->xiaoeurl . $action.'?'.$query.'#xiaoe_wechat_redirect';
          return $location;
        }

        public function getApiToken(){
          $data = array(
              'grant_type'=>'client_credentials'
            );
           $Authorization = "Basic ".base64_encode($this->appid.':'.$this->appsecret);
           $url = $this->xiaoeurl.'/'.'token.php';
           $resp = XEHttpClient::ihttp_post($url, $data,array("Authorization"=>$Authorization));
           // var_dump($resp['content']);
           return json_decode($resp['content']);
        }
}