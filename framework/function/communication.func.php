<?php
/**
 * Http协议
 * 
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */
function ihttp_request($url, $post = '', $extra = array(), $timeout = 60) {
	$urlset = parse_url($url);
	// var_dump($url);
	// var_dump($post);

	if(empty($urlset['path'])) {
		$urlset['path'] = '/';
	}
	if(!empty($urlset['query'])) {
		$urlset['query'] = "?{$urlset['query']}";
	} else {
		$urlset['query'] = '';
	}
	if(empty($urlset['port'])) {
		$urlset['port'] = $urlset['scheme'] == 'https' ? '443' : '80';
	}
	if (strpos($url, 'https://') && !extension_loaded('openssl')) {
		if (!extension_loaded("openssl")) {
			error_report('请开启您PHP环境的openssl');
		}
	}
	if(function_exists('curl_init') && function_exists('curl_exec')) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $urlset['scheme']. '://' .$urlset['host'].($urlset['port'] == '80' ? '' : ':'.$urlset['port']).$urlset['path'].$urlset['query']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
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
			return ihttp_response_parse($data);
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
		return ihttp_response_parse($content, true);
	}
}

function ihttp_response_parse($data, $chunked = false) {
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
		$rlt['content'] = ihttp_response_parse_unchunk($split1[1]);
	} else {
		$rlt['content'] = $split1[1];
	}
	if($isgzip && function_exists('gzdecode')) {
		$rlt['content'] = gzdecode($rlt['content']);
	}

	$rlt['meta'] = $data;
	if($rlt['code'] == '100') {
		return ihttp_response_parse($rlt['content']);
	}
	return $rlt;
}

function ihttp_response_parse_unchunk($str = null) {
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


function ihttp_get($url) {
	return ihttp_request($url);
}

function ihttp_post($url, $data, $timeout = 60) {
	$headers = array('Content-Type' => 'application/x-www-form-urlencoded');
	return ihttp_request($url, $data, $headers, $timeout);
}

function ihttp_email($to, $subject, $body) {
	static $mailer;
	set_time_limit(0);
	
	if (empty($mailer)) {
		if (!class_exists('PHPMailer')) {
			require IA_ROOT . '/source/library/phpmailer/PHPMailerAutoload.php';
		}
		$mailer = new PHPMailer();
		$config = $GLOBALS['_W']['setting']['mail'];
		$config['charset'] = 'utf-8';
		if ($config['smtp']['type'] == '1') {
			$config['smtp']['server'] = 'ssl://smtp.qq.com';
			$config['smtp']['port'] = 465;
			$config['smtp']['authmode'] = 1;
		} elseif ($config['smtp']['type'] == '3') {
			$config['smtp']['server'] = 'smtp.163.com';
			$config['smtp']['port'] = 25;
		} else {
			if (!empty($config['smtp']['authmode'])) {
				$config['smtp']['server'] = 'ssl://' . $config['smtp']['server'];
			}
		}
		
		if (!empty($config['smtp']['authmode'])) {
			if (!extension_loaded('openssl')) {
				return error(1, '请开启 php_openssl 扩展！');
			}
		}
		$mailer->signature = $config['signature'];
		$mailer->isSMTP();
		$mailer->CharSet = $config['charset'];
		$mailer->Host = $config['smtp']['server'];
		$mailer->Port = $config['smtp']['port'];
		$mailer->SMTPAuth = true;
		$mailer->Username = $config['username'];
		$mailer->Password = $config['password'];
		!empty($config['smtp']['authmode']) && $mailer->SMTPSecure = 'ssl';
		
		$mailer->From = $config['username'];
		$mailer->FromName = $config['sender'];
		$mailer->isHTML(true);
	}
	if (!empty($mailer->signature)) {
		$body .= htmlspecialchars_decode($mailer->signature);
	}
	$mailer->Subject = $subject;
	$mailer->Body = $body;
	$mailer->addAddress($to);
	if($mailer->send()) {
		return true;
	} else {
		return error(1, $mailer->ErrorInfo);
	}
}

function account_weixin_token() {
	global $_W;
    $access_token = mcache()->get('access_token');
    if(!empty($access_token)){
    	return $access_token;
    }
    $appid = $_W['config']['app']['appid'];
    $appsecret = $_W['config']['app']['secret'];
    $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$appsecret;
    //logging('account_weixin_token request', $url);
    $content = ihttp_get($url);
    $token = @json_decode($content['content'], true);
    //logging('account_weixin_token return', $token);
    $access_token = $token['access_token'];
    mcache()->set('access_token', $access_token, time()+7200);
    //$access_token = 'qihroPvoNJXAzYPPKb8gMOHTvI4I0KEkrqcAK_JIcLPJndYno_LYBU_RdlKF5iHftWtI_T4OgD10UEqhWqLTXp8ZLeUO3X0CRGEflwAX2YA';
    return $access_token;
}

function access_xiaoe_token()
{
    return mcache()->get('access_xiaoe_token');
}

function set_access_xiaoe_token($access_xiao_token)
{
	return mcache()->set('access_xiaoe_token', $access_xiao_token, 3500);
}