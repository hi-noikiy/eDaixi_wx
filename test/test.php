<?php
class Session{
	const SESSION_STARTDD = TRUE;
	const SESSION_NOT_STARTEND = FALSE;

	private $sessionState = self::SESSION_NOT_STARTEND;
	private static $instance;

	private function __construct(){
	}

	public static function getInstance(){
		if(!isset(self::$instance)){
			self::$instance = new self;
		}
		self::$instance->startSession();
		return self::$instance;
	}

	public function startSession(){
		if($this->sessionState == self::SESSION_NOT_STARTEND){
			$this->sessionState = session_start();
		}
		return $this->sessionState;
	}

	public function __set($name,$value){
		$_SESSION[$name] = $value;
	}

	public function __get($name){
		if(isset($_SESSION[name])){
			return $_SESSION[$name];
		}
	}

	public function __isset($name){
		return isset($_SESSION[$name]);
	}

	public function __unset($name){
		unset($_SESSION[$name]);
	}

	public function destory(){
		if($this->sessionState == self::SESSION_STARTDD){
			$this->sessionState = !session_destroy();
			unset($_SESSION);
			return !$this->sessionState;
		}
		return FALSE;
	}
}

$data = Session::getInstance();
$data->nickname = 'Someone';
$data->age= 18;

printf('<p>My name is %s and I\'m %d years old.</p>',$data->$nickname,$data->age);
printf('<pre>%s</pre>',print_r($_SESSION,TRUE));
var_dump(isset($data->nickname));
$data->destory();
var_dump(isset($data->nickname));


