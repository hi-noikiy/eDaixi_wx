<?php
require 'framework/bootstrap.inc.php';
// $sessionid = 'hn14d08s573d94cjcpibftkco0';
// session_id($sessionid);
error_reporting(2);

WeSession::$expire = 3600;
WeSession::start();

class WeSession {
	public static $expire;

	public static function start() {
		$sess = new WeSession();
		session_set_save_handler(array(&$sess, 'open'), array(&$sess, 'close'), array(&$sess, 'read'), array(&$sess, 'write'), array(&$sess, 'destroy'), array(&$sess, 'gc'));
		session_start();
	}
	public function open() {
		return true;
	}
	public function close() {
		return true;
	}
	public function read($sessionid) {
		$res = mcache()->get($sessionid);
		logging('session_data_get '.$res);
		return $res;
	}
	public function write($sessionid, $data) {
		logging('session_data'.$data);
		$res = mcache()->set($sessionid,$data,time()+$expire);
		logging('session_set_data_res'.$data);
		return true;
	}
	public function destroy($sessionid) {
		return true;
	}
	public function gc($expire) {
		return true;
	}
}
//$_SESSION['test'] = 'test';
var_dump($_SESSION);
$_SESSION['test02'] = array('test02','test03');
logging('11111',var_export($_SESSION));
var_dump(session_id());
