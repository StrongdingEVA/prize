<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/25 0025
 * Time: 17:20
 */
namespace Home\Controller;
use Common\Logic\LuckLogic;

class WetCallbackController extends HomeController {

    //微信小程序统一下单回调
    public function wechatCall(){
        $post_data = $_REQUEST;
        if($post_data==null){
            $post_data = file_get_contents("php://input");
        }
        if($post_data == null){
            $post_data = $GLOBALS['HTTP_RAW_POST_DATA'];
        }

        //记录回调表
        LC('OrderLog')->saveData($post_data,0);
        $luckLogic = new LuckLogic();
        return $luckLogic->unifiedorderCall($post_data);
    }

    //微信小程序申请退款回调
    public function refund(){
        $post_data = $_REQUEST;
        if($post_data==null){
            $post_data = file_get_contents("php://input");
        }
        if($post_data == null){
            $post_data = $GLOBALS['HTTP_RAW_POST_DATA'];
        }

        //记录回调表
        LC('OrderLog')->saveData($post_data,1);
        $luckLogic = new LuckLogic();
        return $luckLogic->refundCall($post_data);
    }
}