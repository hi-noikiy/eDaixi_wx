<?php
class Rout{
	public function __construct(){
	
	}

	public static function create_module($module, $controller, $method){
		global $_GPC;
		$is_old_link  = in_array($controller, array('alioauth','oauth','oauth2'));
		if($is_old_link){
			$eid = $_GPC['eid'];
			Rout::conversion_ole_link($eid);
		}
		if($module && $controller && $method){
			$base_file = IA_ROOT . "/framework/controller/{$module}/base.class.php";
			$class_file = IA_ROOT . "/framework/controller/{$module}/{$controller}.class.php";
			if(file_exists($base_file) && file_exists($class_file)) {
				try{
					require $base_file;
					require $class_file;
					if(class_exists($controller)){
						$class = new $controller();
						if(method_exists($class, $method)){
							$class->$method();
							exit;
						}
					}
					error_report('您请求的地址不存在');
				}catch(Exception $e){
					trigger_error('Route error!', E_USER_ERROR);
				}
			}else{
				error_report('您请求的地址不存在');
			}
		}else{
			//self::back_previous();
			self::go_homepage();
		}
	}

	public static function conversion_ole_link($eid){
		global $_GPC;
		$ruidl = array(
			'458' => 'order/order_list',
			'463' => 'homepage/index',
			'465' => 'homepage/index',
			'471' => 'homepage/index',
			'483' => 'icard/my_icard',
			'484' => 'thirdparty/game',             //游戏接入 
			'485' => 'thirdparty/kaola', 	//考拉社区入口
			'486' => 'order/order_details',
			'487' => 'order/delivery_details',
			'488' => 'payment/order_pay',
			'489' => 'payment/coupon_list',
			'490' => 'icard/icard_charge',
			'492' => 'thirdparty/gameLogin' ,	//逻辑思维游戏登陆
			'493' => 'thirdparty/game2',	//逻辑思维领券
			'494' => 'homepage/order_place',
			);

		//mobile.php?act=oauth&eid=496&weid=5&uuid= ... => mobile.php?type=ordercoupon&eid=200&uuid=
		if($eid == 496){
			$url = 'hongbao.edaixi.com/mobile.php?type=ordercoupon&eid=200&uuid='.$_GPC['uuid'];
			header("Location: http://".$url);
			exit;
		}

		foreach ($_GPC as $key => $value) {
			if(!in_array($key, array('act', 'weid', 'from_user', '__auth', '__msess', 'code', 'state'))){
				$arr[$key] = $value;  
			}
		}

		if(in_array($eid,array('484','485','492','493'))){
			$url = create_url($ruidl[$eid],$arr,'third');	
		}else{
			$url = create_url($ruidl[$eid],$arr);
		}
		$raw_request = HTTP_TYPE.$_SERVER['HTTP_HOST'].$_SERVER["REQUEST_URI"];
		header("Location: " .HTTP_TYPE.$_SERVER['HTTP_HOST'].'/'.$url);
	}
	
	// 返回上一页
	public static function back_previous(){
		header('Location: ' . HTTP_TYPE . $_SERVER['HTTP_HOST'] . $_SERVER["REQUEST_URI"]);
		exit;
	}
	
	
	// 返回首页
	public static function go_homepage(){
	    $module = $_GPC['m'] = 'wap';
	    $controller = $_GPC['act'] = 'homepage';
	    $method = $_GPC['do'] = 'index';
        header('Location: ' . create_url('homepage/index'));
        exit;
	}
}