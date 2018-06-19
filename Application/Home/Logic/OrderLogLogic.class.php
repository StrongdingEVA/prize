<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/11 0011
 * Time: 9:45
 */
namespace Home\Logic;

class OrderLogLogic extends BaseLogic{
    public function saveData($param,$type = 0){
        $arr = array(
            'remark' => $type == 0 ? '支付回调' : '退款回调',
            'return_code' => $param['return_code'] ? $param['return_code'] : 'no value',
            'result_code' => $param['result_code'] ? $param['result_code'] : 'no value',
            'type' => $type,
            'order_no' => 'out_trate_no'
        );
        return D('Common/OrderLog')->saveData($arr);
    }
}