<?php
/** 荣昌 ThirdServer
*** 此处存放与第三方活动交互的接口
**/

class ThirdServer {
	private $config = array();
	public function __construct(){
		$this->config['lingcaibao'] = array(
				'appid' => 'ss2hlHpu',
    		'app_secret' => '2a7c462234db479fa9aa494d7b01a851',
    		//必中包
    		'mporder_url' => 'http://d.lingcaibao.com/open/lottery/edxMpOrder',
    		//3D彩票
    		'3dorder_url' => 'http://d.lingcaibao.com/open/lottery/edxOrder',
        //个人中心入口
        'entance_url' => 'http://account.lingcaibao.com/wap/oac/loginUId'
			);
	}

	//-----------------------零彩宝begin--------------------

	//必中包/3D彩票 下单
  public function get_lingcb_order($data, $is_3d=0){
  	$url = $is_3d ? $this->config['lingcaibao']['3dorder_url'] : $this->config['lingcaibao']['mporder_url'];
    $data['appId'] = $this->config['lingcaibao']['appid'];
  	$data['sign'] = (string)$this->lingcb_make_signature($data);
  	$param_str = http_build_query($data);
  	$resp = ihttp_get($url . '?' . $param_str);
    return json_decode($resp['content'], true);
  }

  //个人中心入口
  public function get_lingcb_entrance($data){
    $url = $this->config['lingcaibao']['entance_url'];
    $data['appId'] = $this->config['lingcaibao']['appid'];
    $data['sign'] = (string)$this->lingcb_make_signature($data);
    $param_str = http_build_query($data);
    return $url . '?' . $param_str;
  }

  //验签
  private function lingcb_make_signature($data){
  	require_once IA_ROOT . '/framework/library/java/Java.inc';
  	$util = new Java("com.edaixi.lcmd5.LCMD5");
  	return $util->sign($data, $this->config['lingcaibao']['app_secret']);
  }

  //-----------------------零彩宝end-----------------------



}

?>