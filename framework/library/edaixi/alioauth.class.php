<?php 


define ('ALI_PATH', IA_ROOT . '/framework/library/alipay');
require_once ALI_PATH . '/function.inc.php';
require_once ALI_PATH . '/AopSdk.php';
require_once ALI_PATH . '/HttpRequst.php';
require_once ALI_PATH . '/config.php';
require_once ALI_PATH . '/UserInfo.php';

class Alioauth{

      public function get_oauth_user(){

              global $_GPC,$_W;
              $auth_code = $_GPC['auth_code'];
              if(empty($auth_code)){
                      var_dump('empty auth_code!');
                      exit;
              }else{
                      $userinfo = new UserInfo();
                      $info = $userinfo->getUserInfo($auth_code);  
                      if($info){
                              $from_user = $info->user_id;
                              return $from_user;
                      }else{
                              var_dump('empty  user_info');
                              exit;
                      }
              } 
        }      
}
