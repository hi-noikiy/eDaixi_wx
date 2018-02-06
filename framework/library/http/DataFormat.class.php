<?php
/**
 * 格式化请求数据
 */
class DataFormat {
	public function is_utf8($text) {
		$e = mb_detect_encoding ( $text, array (
				'UTF-8',
				'GBK'
		) );
		switch ($e) {
			case 'UTF-8' : // 如果是utf8编码
				return true;
			case 'GBK' : // 如果是gbk编码
				return false;
		}
	}

	public function arrayRecursive(&$array, $function, $apply_to_keys_also = false) {
		foreach ( $array as $key => $value ) {
			if (is_array ( $value )) {
				$this->arrayRecursive ( $array [$key], $function, $apply_to_keys_also );
			} else {
				$array [$key] = $function ( $value );
			}
				
			if ($apply_to_keys_also && is_string ( $key )) {
				$new_key = $function ( $key );
				if ($new_key != $key) {
					$array [$new_key] = $array [$key];
					unset ( $array [$key] );
				}
			}
		}
	}

	public function JSON($array) {
		$this->arrayRecursive ( $array, 'urlencode', true );
		$json = json_encode ( $array );
		return urldecode ( $json );
	}

	public function array2xml($arr, $level = 1, $ptagname = '') {
		$s = $level == 1 ? "<xml>" : '';
		foreach ( $arr as $tagname => $value ) {
			if (is_numeric ( $tagname )) {
				$tagname = $value ['TagName'];
				unset ( $value ['TagName'] );
			}
			if (! is_array ( $value )) {
				$s .= "<{$tagname}>" . (! is_numeric ( $value ) ? '<![CDATA[' : '') . $value . (! is_numeric ( $value ) ? ']]>' : '') . "</{$tagname}>";
			} else {
				$s .= "<{$tagname}>" . self::array2xml ( $value, $level + 1 ) . "</{$tagname}>";
			}
		}
		$s = preg_replace ( "/([\x01-\x08\x0b-\x0c\x0e-\x1f])+/", ' ', $s );
		return $level == 1 ? $s . "</xml>" : $s;
	}
}
