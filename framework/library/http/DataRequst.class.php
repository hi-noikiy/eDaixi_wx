<?php
/**
 * 请求数据
 */
class DataRequst {
	public static function sendPostRequst($url, $data) {
		if(is_array($data)){
			$data = http_build_query($data);
		}
		$opts = array (
				'http' => array (
						'method' => 'POST',
						'header' => 'Content-Type: application/x-www-form-urlencoded',
						'content' => $data
				)
		);
		$context = stream_context_create ( $opts );
		$result = file_get_contents ( $url, false, $context );
		return $result;
	}

	public static function sendGetRequst($url) {
		$result = file_get_contents ( $url);
		return $result;
	}

	public static function getRequest($key) {
		$request = null;
		if (isset ( $_GET [$key] ) && ! empty ( $_GET [$key] )) {
			$request = $_GET [$key];
		} elseif (isset ( $_POST [$key] ) && ! empty ( $_POST [$key] )) {
			$request = $_POST [$key];
		}
		return $request;
	}
}