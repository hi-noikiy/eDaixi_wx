<?php

class Config
{
	public $app = array(
		'appid'=>'xiyi',
		'secret'=>'xiyi',
		'url'=>'http://oauth02.edaixi.cn/'
		);

	function __construct($config)
	{
		$this->app = array(
			'appid' => $config['xiaoe']['appid'],
			'secret' => $config['xiaoe']['secret'],
			'url' => $config['xiaoe_oauth']['url']
			);
	}
}

