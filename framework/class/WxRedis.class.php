<?php
class WxRedis {
	private static $_instance = null;
	private static $_redis = null;
	
	private function __construct(){}
	
	public static function getInstance(){
		if(!(self::$_instance instanceof self)){
			self::$_instance = new self;
			self::$_redis = self::connectRedis();
		}
		return self::$_instance;
	}
	
	public static function connectRedis(){
		global $_W;
		$host = $_W['config']['redis']['host'];
		$port = $_W['config']['redis']['port'];
		$password = $_W['config']['redis']['password'];
		$connect = $_W['config']['redis']['connect_type'] == 'connect' ? 'connect' : 'pconnect';
		if(!self::$_redis){
			self::$_redis = new Redis();
		}
		try {
			self::$_redis->$connect($host, $port);
			if($password){
				self::$_redis->auth($password);
			}
		} catch (Exception $e) {
			logging('Redis Exception', $e->getMessage() . "\nRedis Host：" . $host, 'a+', 'Redis_Exception');
		}
		return self::$_redis;
	}
	
	public function __call($method, $args){
		if(method_exists(self::$_redis, $method)){
			try {
				return call_user_func_array(array(self::$_redis, $method), $args);
			} catch (Exception $e) {
				logging('Redis Exception', $e->getMessage(), 'a+', 'Redis_Exception');
				self::$_redis = self::connectRedis();
			}
		}else{
			logging('Redis Exception', "Call to undefined method Redis::{$method}", 'a+', 'Redis_Exception');
		}
	}
	
	public function __clone(){}
}

/*
// 获取 Redis 实例
function redis(){
	global $_W;
	include_once IA_ROOT . '/framework/class/WxRedis.class.php';
	return WxRedis::getInstance();
}

// 测试 Redis 类
function test_redis(){
	$redis = redis();
	 do{
		 static $n = 0;
		 echo str_pad(" ", 4096);
		 $n += 1;
		 ob_flush();
		 flush();
		 echo $redis->ping(), $n, '<br />';
		 sleep(1);
	 }while(true);
}
test_redis();
//*/