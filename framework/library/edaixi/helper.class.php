<?php
namespace Edx\Helper;
defined('IN_IA') or exit('Access Denied');
/**
 * Common模型
 */
class Common {

    /**
     * 浮点数保留小数位数
     * @param string 输入的数字
     * @param int num_dights 保留小数位数
     * @param int model 进位方式
     * @return string 保留小数后的数字
     * 
     */
    public static function round($number, $num_digits = 2, $model = PHP_ROUND_HALF_UP)
    {
        return sprintf("%.{$num_digits}f",
                       round($number, $num_digits, $model));
    }

    /**
     * 格式化订单编号：　16051399800389　＝》　16051399　800389
     */
    public static function formatOrderSn($order_sn)
    {

        $order_sn_end = substr($order_sn, -6, 6);
        $order_sn_first = substr($order_sn, 0, count($order_sn) - 7);
        return $order_sn_first . ' ' . $order_sn_end;
    }
}