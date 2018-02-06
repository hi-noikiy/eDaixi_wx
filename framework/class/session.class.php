<?php
class WeSession {
	public static $expire;

	public static function start() {
		global $_W;
		$sess = new WeSession();
		session_set_save_handler(array(&$sess, 'open'), array(&$sess, 'close'), array(&$sess, 'read'), array(&$sess, 'write'), array(&$sess, 'destroy'), array(&$sess, 'gc'));
		//修改app免登陆h5的访问越权问题
		if(!empty($_COOKIE['session']) && !in_array($_COOKIE['session'], array('_2', '_3'))){
            session_id($_COOKIE['session']);
        }

		session_start();

		if(empty(session_id())){
            if(isset($_SESSION['user_info'])){
            	unset($_SESSION['user_info']);
            }
			session_regenerate_id(true);
        }
		setcookie('session',session_id(),time()+432000,'/',$_W['config']['cookie']['dome']);
	}
	public function open() {
		return true;
	}
	public function close() {
		return true;
	}
	public function read($sessionid) {
		$res = mcache()->get($sessionid);
		return $res;
	}
	public function write($sessionid, $data) {
		global $_W;
		$res = mcache()->set($sessionid,$data,432000);
		return true;
	}
	public function destroy($sessionid) {
		$res = mcache()->delete($sessionid);
		return $res;
	}
	public function gc($expire) {
		return true;
	}
}
