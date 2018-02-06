<?php
class XEHttpClient{
	public static function ihttp_post($url, $data, $headers,$timeout = 60) {
		if(empty($headers)){
			$headers = array('Content-Type' => 'application/x-www-form-urlencoded');
		}else{
			$headers = array_merge($headers,array('Content-Type' => 'application/x-www-form-urlencoded'));
			
		}
		return XEHttpClient::ihttp_request($url, $data, $headers, $timeout);
	}

	public static function ihttp_get($url) {
		return XEHttpClient::ihttp_request($url);
	}
	private static function ihttp_request($url, $post = '', $extra = array(), $timeout = 60) {
		$urlset = parse_url($url);
		if(empty($urlset['path'])) {
			$urlset['path'] = '/';
		}
		if(!empty($urlset['query'])) {
			$urlset['query'] = "?{$urlset['query']}";
		}
		if(empty($urlset['port'])) {
			$urlset['port'] = $urlset['scheme'] == 'https' ? '443' : '80';
		}
		if (strpos($url, 'https://') && !extension_loaded('openssl')) {
			if (!extension_loaded("openssl")) {
				message('请开启您PHP环境的openssl');
			}
		}
		if(function_exists('curl_init') && function_exists('curl_exec')) {
			$ch = curl_init();
			$churl = $urlset['scheme']. '://' .$urlset['host'].($urlset['port'] == '80' ? '' : ':'.$urlset['port']).$urlset['path'];
			if(!empty($urlset['query'])){
				$churl.=$urlset['query'];
			}
			curl_setopt($ch, CURLOPT_URL, $churl);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 1);
			// curl_setopt($ch, CURLOPT_USERPWD, "testclient:testpass");
			if($post) {
				curl_setopt($ch, CURLOPT_POST, 1);
				if (is_array($post)) {
					$post = http_build_query($post);
				}
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
			}
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
			curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSLVERSION, 1);
			//curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:9.0.1) Gecko/20100101 Firefox/9.0.1');
			curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
			if (!empty($extra) && is_array($extra)) {
				$headers = array();
				foreach ($extra as $opt => $value) {
					if (strpos($opt, 'CURLOPT_')) {
						curl_setopt($ch, constant($opt), $value);
					} elseif (is_numeric($opt)) {
						curl_setopt($ch, $opt, $value);
					} else {
						$headers[] = "{$opt}: {$value}";
					}
				}
				if(!empty($headers)) {
					curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				}
			}

			$data = curl_exec($ch);
			$status = curl_getinfo($ch);
			$errno = curl_errno($ch);
			$error = curl_error($ch);
			curl_close($ch);
			if($errno || empty($data)) {
				return error(3, $error);
			} else {
				return XEHttpClient::ihttp_response_parse($data);
			}
		}
		$method = empty($post) ? 'GET' : 'POST';
		$fdata = "{$method} {$urlset['path']}{$urlset['query']} HTTP/1.1\r\n";
		$fdata .= "Host: {$urlset['host']}\r\n";
		if(function_exists('gzdecode')) {
			$fdata .= "Accept-Encoding: gzip, deflate\r\n";
		}
		$fdata .= "Connection: close\r\n";
		if (!empty($extra) && is_array($extra)) {
			foreach ($extra as $opt => $value) {
				if (!strpos($opt, 'CURLOPT_')) {
					$fdata .= "{$opt}: {$value}\r\n";
				}
			}
		}
		$body = '';
		if ($post) {
			if (is_array($post)) {
				$body = http_build_query($post);
			} else {
				$body = urlencode($post);
			}
			$fdata .= 'Content-Length: ' . strlen($body) . "\r\n\r\n{$body}";
		} else {
			$fdata .= "\r\n";
		}
		if($urlset['scheme'] == 'https') {
			$fp = fsockopen('ssl://' . $urlset['host'], $urlset['port'], $errno, $error);
		} else {
			$fp = fsockopen($urlset['host'], $urlset['port'], $errno, $error);
		}
		stream_set_blocking($fp, true);
		stream_set_timeout($fp, $timeout);
		if (!$fp) {
			return error(1, $error);
		} else {
			fwrite($fp, $fdata);
			$content = '';
			while (!feof($fp))
				$content .= fgets($fp, 512);
			fclose($fp);
			return XEHttpClient::ihttp_response_parse($content, true);
		}
	}
	private static function ihttp_response_parse($data, $chunked = false) {
		$rlt = array();
		$pos = strpos($data, "\r\n\r\n");
		$split1[0] = substr($data, 0, $pos);
		$split1[1] = substr($data, $pos + 4, strlen($data));
		
		$split2 = explode("\r\n", $split1[0], 2);
		preg_match('/^(\S+) (\S+) (\S+)$/', $split2[0], $matches);
		$rlt['code'] = $matches[2];
		$rlt['status'] = $matches[3];
		$rlt['responseline'] = $split2[0];
		@$header = explode("\r\n", $split2[1]);
		$isgzip = false;
		$ischunk = false;
		foreach ($header as $v) {
			$row = explode(':', $v);
			$key = trim($row[0]);
			$value = trim($row[1]);
			if (@is_array($rlt['headers'][$key])) {
				$rlt['headers'][$key][] = $value;
			} elseif (!empty($rlt['headers'][$key])) {
				$temp = $rlt['headers'][$key];
				unset($rlt['headers'][$key]);
				$rlt['headers'][$key][] = $temp;
				$rlt['headers'][$key][] = $value;
			} else {
				$rlt['headers'][$key] = $value;
			}
			if(!$isgzip && strtolower($key) == 'content-encoding' && strtolower($value) == 'gzip') {
				$isgzip = true;
			}
			if(!$ischunk && strtolower($key) == 'transfer-encoding' && strtolower($value) == 'chunked') {
				$ischunk = true;
			}
		}
		if($chunked && $ischunk) {
			$rlt['content'] = XEHttpClient::ihttp_response_parse_unchunk($split1[1]);
		} else {
			$rlt['content'] = $split1[1];
		}
		if($isgzip && function_exists('gzdecode')) {
			$rlt['content'] = gzdecode($rlt['content']);
		}

		$rlt['meta'] = $data;
		if($rlt['code'] == '100') {
			return XEHttpClient::ihttp_response_parse($rlt['content']);
		}
		return $rlt;
	}

	private static function ihttp_response_parse_unchunk($str = null) {
		if(!is_string($str) or strlen($str) < 1) {
			return false; 
		}
		$eol = "\r\n";
		$add = strlen($eol);
		$tmp = $str;
		$str = '';
		do {
			$tmp = ltrim($tmp);
			$pos = strpos($tmp, $eol);
			if($pos === false) {
				return false;
			}
			$len = hexdec(substr($tmp, 0, $pos));
			if(!is_numeric($len) or $len < 0) {
				return false;
			}
			$str .= substr($tmp, ($pos + $add), $len);
			$tmp  = substr($tmp, ($len + $pos + $add));
			$check = trim($tmp);
		} while(!empty($check));
		unset($tmp);
		return $str;
	}
}