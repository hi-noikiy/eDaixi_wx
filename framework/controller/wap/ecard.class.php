<?php
defined('IN_IA') or exit('Access Denied');
use Edx\Model\ModelLoader as Model;
/**
 * e卡页面
 */
class Ecard extends BaseModule {

    function __construct(){
        global $_W;
        parent::__construct();
        $this->model_name = 'icard';
    }

    public function index()
    {
        $user_info = $this->user_info;
        //e卡模型
        $ecard_model = Model::get('card', $user_info);
        $user_id = $user_info['user_id'];
        //$user_id = 106;
        $user_type = $user_info['user_type'];
        //用户e卡列表
        $user_ecard_list = $ecard_model->userEcardList($user_id, $user_type);
        $data_info = array(
                'ecard_list' => $user_ecard_list,
                'is_pay' => false,
                'exchange_url' => create_url('ecard/exchange')
            );
        //加载模板
        include $this->template('e-card');
    }

    /**
     * e卡兑换
     * 接收参数: get
     *  sncode: e卡或充值卡密码
     * 返回数据(json)
     * 成功时响应:
     *  {
     *    'state': 1,
     *    'msg' : '兑换成功信息',
     *    'card_type' : '1'
     *  }
     * 失败时响应:
     *  {
     *    'state' : 0,
     *    'msg' : '错误信息'
     *  } 
     */
    public function exchange()
    {
        $user_info = $this->user_info;
        //e卡模型
        $ecard_model = Model::get('card', $user_info);
        $user_id = $user_info['user_id'];
        $sncode = isset($_POST['sncode']) ? $_POST['sncode'] : null;
        $ret = $ecard_model->cardCharge($user_id, $sncode);
        $this->retJson($ret);
    }
}
