<?php
defined('IN_IA') or exit('Access Denied');
/**
 * 时间控件
 */
class Washtime extends BaseModule {

    function __construct(){
        global $_W;
        parent::__construct();
        $this->model_name = 'washtime';
        $this->open_server = new OpenServer($_W['config'],$this->user_info);
    }
    /**
    *   选择时间接口
    */
    public function select_time()
    {
        global $_W,$_GPC;
        $category_id = stripos($_GPC['category_id'], ',') ? -1 : $_GPC['category_id'];
        $sub_category_ids = stripos($_GPC['category_id'], ',') ? $_GPC['category_id']: '';
        $mark = $_GPC['mark'] ?: get_mark();
        $city_id = $_GPC['city_id'] ?: '';
        $area = $_GPC['area'] ?: '';
        $area_id = $_GPC['area_id'] ?: '';
        $order_id = $_GPC['order_id'] ?: '';
        // 用户当前首页城市
        $city_info = get_user_city();
        $city_id = $city_info['city_id'];
        $city_name = $city_info['city_name'];
        $error_url = create_url('homepage/index', array(
                'mark'  =>  $mark,
                'city_id'   =>  $city_id,
            ));
        if(!$city_id || !$city_name){
            echo_json(false, '', array(
                    'message'   =>  '城市信息错误',
                    'url'   =>  $error_url,
                ));
        }
        if(empty($category_id)){
            echo_json(false, '', array(
                    'message'   =>  '品类信息错误',
                    'url'   =>  $error_url,
                ));
        }
        // === 下单日期时段 ===
        $service_time = $this->open_server->get_service_time($category_id, $city_id, $area, $order_id, $area_id, $sub_category_ids);
        if( isset($service_time['ret']) && !$service_time['ret'] ){
            $query_data = array(
                    'category_id'   =>  $_GPC['category_id'],
                    'city_id'   =>  $city_id,
                    'price_read'   =>  $_GPC['price_read'],
                    'address_id'   =>  $_GPC['address_id'],
                    'select_address'   =>  $_GPC['select_address'],
                    'comment'   =>  $_GPC['comment'],
                    'mark'  =>  $mark
                );
            echo_json(false, '', array(
                    'message'   =>  $service_time['error'] ?: '网络错误',
                    'url'   =>  add_params('/new_weixin/view/order_place.html', $query_data)
                ));
        }
        echo_json(true, $service_time);
    }

}

