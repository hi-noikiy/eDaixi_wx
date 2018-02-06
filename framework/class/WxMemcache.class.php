<?php
class WxMemcache {
	private static $_instance = null;
	public static function getInstance(){
		global $_W;
		$host = $_W['config']['redis']['host'];
		$port = $_W['config']['redis']['port'];
		
		if (null === self::$_instance) {
			if(class_exists('Memcached')){
				self::$_instance = new Memcached();
			}else{
				self::$_instance = new Memcache();
			}
			self::$_instance->addServer($host, $port);
		}
		return self::$_instance;
	}
}

/*
// 获取 Memcache 实例
function mcache(){
	include_once IA_ROOT . '/framework/class/WxMemcache.class.php';
	return WxMemcache::getInstance();
}
//*/