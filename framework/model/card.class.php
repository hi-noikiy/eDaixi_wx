<?php
namespace Edx\Model;
defined('IN_IA') or exit('Access Denied');
/**
 * e卡/充值卡模型
 */
class card extends edxModel {

    public function __construct($params)
    {
        parent::__construct($params);
    }

    /**
     * 获取用户e卡列表
     * @param int user_id 用户id
     * @param int user_type 用户类型
     * @param array order_group_ids e卡支付选卡时订单索引数组[1,2,3]
     * @param int coupon_id 优惠券模板id 
     */
    public function userEcardList($user_id, $user_type,
                                    $order_group_ids = "[]", $coupon_id = 0)
    {
        $ecard_list = array();
        $open_server = $this->open_server;
        $data = array(
                'user_id' => $user_id,
                'user_type' => $user_type,
                'order_group_ids' => $order_group_ids,
                'coupon_id' => $coupon_id
            );
        $open_ret = $open_server->user_ecard_list($data);
        if (!empty($open_ret)) {
            $ecard_list = $open_ret;
        }
        return $ecard_list;
    }

    /**
     * 充值卡/e卡充值
     * @param $user_id 用戶id
     * @param $sncode e卡/充值卡的密碼
     * @return array(
     *            'state' => 0/1,
     *            'msg' => ''
     *         )
     */
    public function cardCharge($user_id, $sncode, $tel='')
    {
        $result = array();
        $open_server = $this->open_server;
        $sncode = str_replace(' ', '', $sncode);
        if (empty($user_id) ||
            empty($sncode)) {
            $result['state'] = 0;
            $result['msg'] = '请输入卡密';
        } else {
            $open_ret = $open_server->bind_recharge($user_id, $sncode, $tel);
            if(isset($open_ret['ret']) &&
               $open_ret['ret']){
                $result['state'] = 1;
                $data = $open_ret['data'];
                $result['msg'] = $data['content'];
                $result['card_type'] = $data['card_type'];
            }else {
                $result['state'] = 0;
                $result['msg'] = empty($open_ret['error']) ?
                                 '出错了，请稍后再试！' : $open_ret['error'];
            }
        }
        return $result;
    }

    /**
     * 用户钱包接口
     */
    public function userWallet($user_id)
    {
        $result = array(
            'coupon_num' => '暂无信息',
            'ecard_num' => '暂无信息',
            'icard_amount' => '暂无信息',
            'point' => '暂无信息'
            );
        if (!empty($user_id)) {
            $open_server = $this->open_server;
            $data = array(
                    'user_id' => $user_id
                );
            $open_ret = $open_server->user_wallet($data);
            if (!empty($open_ret)) {
                foreach ($open_ret as $key => $value) {
                    if (is_numeric($value) || $value === '点击查看') {
                        $result[$key] = $value;
                    }
                }
            }
        }
        return $result;
    }
}