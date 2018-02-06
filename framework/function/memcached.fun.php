<?php
	
function mcache(){
	global $_W;
	static $mcache;
	if(empty($mcache)) {
		if(class_exists('Memcached')){
			$mcache = new Memcached();
		}else{
			$mcache = new Memcache();
		}
		$host = $_W['config']['memcache']['host'];
		$port = $_W['config']['memcache']['port'];
		$mcache->addServer($host, $port);
	}
	return $mcache;
}

class WxRedis{
	private $_dirver;
	// 获取redis实例
	public function getDirver(){
		if(empty($this->_dirver)){
			$this->setDriver();
		}
		return $this->_dirver;
	}
	// 设置redis实例
	public function setDriver(){
		$this->_dirver = $this->connectRedis();
	}
	// 连接redis服务
	public function connectRedis(){
		global $_W;
		$host = $_W['config']['redis']['host'];
		$port = $_W['config']['redis']['port'];
		try {
			$redis = new Redis();
			if($_W['config']['setting']['development']){
				$redis->connect($host, $port);
			}else{
				$redis->pconnect($host, $port);
			}
			
			if($_W['config']['redis']['password']) {
				$redis->auth($_W['config']['redis']['password']);
			}
		} catch (Exception $e) {
			logging('Redis Exception', $e->getMessage() . "\nRedis Host：" . $host, 'a+', 'Redis_Exception');
		}
		return $redis;
	}
	// 动态调用phpRedis方法,设置重连机制
	function __call($method, $args){
		$phpRedis = $this->getDirver();
		if(method_exists($phpRedis, $method)){
			try{
				return call_user_func_array(array($phpRedis, $method), $args);
			}catch(Exception $e){
				$this->setDriver();
				logging('Redis Exception', 'Exception：' . $e->getMessage() . "\nMethod：" . $method . "\nArgs：" . var_export($args, TRUE), 'a+', 'Redis_Exception');
				return false;
			}
		}else{
			logging('Redis Exception', "Call to undefined method Redis::{$method}", 'a+', 'Redis_Exception');
		}
	}

}

function redis(){
	static $WxRedis;
	if(empty($WxRedis)){
		$WxRedis = new WxRedis();
		$WxRedis->setDriver();
	}
	return $WxRedis;
}