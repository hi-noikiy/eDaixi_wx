<?php
namespace Edx\Model;
defined('IN_IA') or exit('Access Denied');
/**
 * 订单模型
 */
class order extends edxModel {

    public function __construct($params)
    {
        parent::__construct($params);
    }

    /**
     * 订单实付款详情
     * @param int user_id 用户id
     * @param int order_id 订单id
     */
    public function payInfoDetail($user_id, $order_id)
    {
        $detail_info = array();
        if ($user_id && $order_id) {
            $open_server = $this->open_server;
            $data = array(
                'user_id' => $user_id,
                'order_id' => $order_id
            );
            $open_ret = $open_server->order_detail_paid_info($data);
            if (!empty($open_ret)) {
                $detail_info = $open_ret;
            }
        }
        return $detail_info;
    }

    /**
     * 订单衣物实付款详情
     * @param int user_id 用户id
     * @param int order_id 订单id
     */
    public function payClothInfoDetail($user_id, $order_id)
    {
        $cloth_detail = array();
        if ($user_id && $order_id) {
            $open_server = $this->open_server;
            $data = array(
                'user_id' => $user_id,
                'order_id' => $order_id
            );
            $open_ret = $open_server->order_detail_clothes_paid_info($data);
            if (!empty($open_ret)) {
                $cloth_detail = $open_ret;
            }
        }
        return $cloth_detail;
    }
}