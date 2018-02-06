<?php
namespace Edx\Model;
defined('IN_IA') or exit('Access Denied');
/**
 * 基础模型
 */
class edxModel {

    public $api_server;
    public $open_server;
    public $swoole_server;

    protected static $server = array();

    public function __construct($user_info)
    {
        global $_W;
        $config = $_W['config'];
        $this->open_server = new \OpenServer($config, $user_info);
        $this->user_info = $user_info;
    }
}