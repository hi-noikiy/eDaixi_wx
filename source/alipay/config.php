<?php
$config = array (
		'alipay_public_key_file' => dirname ( __FILE__ ) . "/config/alipay_rsa_public_key.pem",
		'merchant_private_key_file' => dirname ( __FILE__ ) . "/config/rsa_private_key.pem",
		'merchant_public_key_file' => dirname ( __FILE__ ) . "/config/rsa_public_key.pem",		
		'charset' => "GBK",
		'gatewayUrl' => "https://openapi.alipay.com/gateway.do",
		//local
		'app_id' => "2014090300010563",
		//线上的
		// 'app_id' => "2014080500007429",
		'aliauthurl' => 'https://openauth.alipay.com/oauth2/publicAppAuthorize.htm', 
		'weixin' => array(
			
				'text_gatewayUrl'  => 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=',
				'template_gatewayUrl'  => 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=',
			
		),
);