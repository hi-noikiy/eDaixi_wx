<?php
defined('IN_IA') or exit('Access Denied');
/**
 * 开发票
 * zc
 */
class Invoice extends BaseModule {

    function __construct(){
        global $_W;
        parent::__construct();
        $this->model_name = 'invoice';
        $this->open_server = new OpenServer($_W['config'],$this->user_info);
    }

    /**
    *   创建发票
    */
    public function create_invoice()
    {
        global $_W,$_GPC;
        $user_id = $this->user_info['user_id'];
        // 用户当前首页城市
        if($_GPC['city_id']){
            $city_id = intval($_GPC['city_id']);
        }else{
            $city_info = get_user_city();
            $city_id = $city_info['city_id'];
        }
        $details = json_encode($_GPC['details']);
        $invoice_type = $_GPC['invoice_type'];
        $company_name = $_GPC['company_name'];
        $invoice_content = $_GPC['invoice_content'];
        $receiver_name = $_GPC['receiver_name'];
        $receiver_tel = $_GPC['receiver_tel'];
        $tax_id = $_GPC['tax_id'];
        $province = $_GPC['province'];
        $city = $_GPC['city'];
        $area = $_GPC['area'] ?: '';
        $address = $_GPC['address'];
        $comment = $_GPC['comment'] ? : '';

        $mark = get_mark();

        $res = $this->open_server->create_invoice($user_id, $details, $invoice_type, $company_name, $invoice_content, $receiver_name, $receiver_tel, $tax_id, $province, $city, $area, $address, $comment, $city_id, $mark);
        if($res)
            echo_json(true);
        else
            echo_json(false, '', array('message'=>'失败'));
    }

    /**
    *   获取可开发票列表
    */
    public function get_invoice_details()
    {
        global $_W,$_GPC;
        $user_id = $this->user_info['user_id'];
        // 用户当前首页城市
        if($_GPC['city_id']){
            $city_id = intval($_GPC['city_id']);
        }else{
            $city_info = get_user_city();
            $city_id = $city_info['city_id'];
        }
        $mark = get_mark();
        $res = $this->open_server->get_invoice_details($user_id, $city_id, $mark);

        if(false === $res['ret'])
            echo_json(false, '', array('message'=>'网络错误'));
        echo_json(true, $res);
    }

    /**
    *   开发票页面
    */
    public function get_invoice_page()
    {
        global $_W,$_GPC;
        $user_id = $this->user_info['user_id'];
        // 用户当前首页城市
        if($_GPC['city_id']){
            $city_id = intval($_GPC['city_id']);
        }else{
            $city_info = get_user_city();
            $city_id = $city_info['city_id'];
        }
        $mark = get_mark();
        $res = $this->open_server->get_invoice_page($user_id, $city_id, $mark);

        //获取用户历史发票抬头和纳税人识别号，便于aotu_complete
        $tax_res = $this->open_server->get_invoice_taxid_history($user_id);

        if(false === $res['ret']){
            echo_json(false, '', array('message'=>'网络错误'));
        }

        $res['tax_history'] = empty($tax_res) ? array() : $tax_res;

        echo_json(true, $res);
    }

    /**
    *   开发票记录
    */
    public function invoice_history()
    {
        global $_W,$_GPC;
        $user_id = $this->user_info['user_id'];
        // 用户当前首页城市
        if($_GPC['city_id']){
            $city_id = intval($_GPC['city_id']);
        }else{
            $city_info = get_user_city();
            $city_id = $city_info['city_id'];
        }
        $mark = get_mark();
        $res = $this->open_server->invoice_history($user_id, $city_id, $mark);

        if(false === $res['ret'])
            echo_json(false, '', array('message'=>'网络错误'));
        echo_json(true, (array)$res);
    }
}
































