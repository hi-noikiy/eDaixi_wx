<?php
namespace Edx\Model;
defined('IN_IA') or exit('Access Denied');
/**
 * 优惠券模型
 * 2016.8.9时废弃
 */
class coupon extends edxModel {

    public function __construct($params)
    {
        parent::__construct($params);
    }

    /**
     * 获取用户优惠券列表
     * @param int user_id 用户id
     * @param int user_type 用户类型
     * @param array order_group_ids e卡支付选卡时订单索引数组[1,2,3]
     * @param int coupon_id 优惠券模板id 
     */
    public function userCouponList($user_id, $user_type, $order_group_ids)
    {
        $user_coupon_list = array();
        $open_server = $this->open_server;
        $data = array(
                'user_id' => $user_id,
                'user_type' => $user_type,
                'order_group_ids' => $order_group_ids
            );
        $open_ret = $open_server->get_coupons_v5($data);
        if (!empty($open_ret)) {
            $user_coupon_list = $open_ret;
        }
        return $user_coupon_list;
    }

    
}