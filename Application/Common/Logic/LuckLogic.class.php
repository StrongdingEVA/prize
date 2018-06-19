<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/10 0010
 * Time: 11:46
 */
namespace Common\Logic;

use Api\Logic\WechatLogic;

class LuckLogic{
    /**
     * 开奖
     */
    public function findLuckly($prizeInfo){
        if ($prizeInfo['status'] != 1) {
            return self::resultFormat('该商品已经开过奖了');
        }
        $wechatLogic = new WechatLogic();
        $msg = array(
            $prizeInfo['type'] == 1 ? $prizeInfo['name'] : '红包',
        );
        $num = $prizeInfo['num'];//红包个数或者奖品个数

        $getInModel = D('Common/GetIn');
        $joinList_ = $joinList = $getInModel->getByPid($prizeInfo['id']); //总的参与人数

        if (!$joinList) { //没人参与啊
            if(D('Common/Prize')->saveData(array('status' => 4,'end_time' => time(), 'id' => $prizeInfo['id']))){
                $msg[] = '你发起的抽奖未开奖，因参与人数为0';
                $formInfo = D('Common/FormId')->getByUid($prizeInfo['user_id']);//查询from_id
                if (!$formInfo) {
                    return self::resultFormat('你发起的抽奖未开奖，因参与人数为0,开奖失败');
                }
                $tempMsg = $wechatLogic->sendTemplateMsg($prizeInfo['id'],$prizeInfo['user_id'], $formInfo['form_id'], $msg, 1);//发送模板消息

                if($tempMsg['errcode'] != 0){
                    $this->addFail($prizeInfo['id'],$prizeInfo['user_id'],$formInfo['form_id'],$msg,1);
                    return self::resultFormat('你发起的抽奖未开奖，因参与人数为0,开奖失败');
                }
                D('Common/FormId')->saveData(array('status' => 1, 'id' => $formInfo['id']));
            }
            return self::resultFormat('你发起的抽奖未开奖，因参与人数为0,开奖失败');
        }

        $total = count($joinList);
        if ($prizeInfo['open_type'] == 2 && $prizeInfo['open_num'] < $total) { //人数不够 不开奖
            return self::resultFormat('人数不足，无法开奖');
        }

        //判断如果奖品个数比参与人数多 按参与人数发奖
        $num = $num > $total ? $total : $num;

        $min = 0;
        $luckList = array();
        $teamModel = D('Common/Team');
        for ($i = 0; $i < $num; $i++) {
            $total = count($joinList);
            $max = $total - 1;
            $number = rand($min, $max);
            $joinInfo = $joinList[$number];
            if ($prizeInfo['team_enable'] == 1) { //开启了组团 一个人中奖了要把团员也带上
                $teamList = $teamModel->getTeamUser($prizeInfo['id'], $joinInfo['user_id']);
                $joinListTemp = array();
                foreach ($joinList as $k1 => $v1) {
                    $joinListTemp[$v1['id']] = $v1;
                }
                $joinListKeys = array_keys($joinListTemp);
                foreach ($teamList as $ke => $va) {
                    $keyTemp = array_search($joinListKeys, $va['g_id']);
                    array_splice($joinList, $keyTemp, 1);
                    $luckList[] = array('id' => $va['g_id'], 'user_id' => $va['user_id']);
                }
            } else {
                $luckList[] = $joinInfo;
                array_splice($joinList, $number, 1);
            }
        }

        $luckTotal = count($luckList);
        $totalAmount = $luckTotal * $prizeInfo['package'];
        $totalAmount_ = $prizeInfo['package'] * $prizeInfo['num'];
        if ($totalAmount_ < $totalAmount) { //判断红包金额是否足够
            $msg[] = '红包金额不足，开奖失败';
            $formInfo = D('Common/FormId')->getByUid($prizeInfo['user_id']);//查询from_id
            if (!$formInfo) {
                return self::resultFormat('红包金额不足，开奖失败');
            }
            D('Common/Prize')->saveData(array('status' => 4,'end_time' => time(), 'id' => $prizeInfo['id']));
            $tempMsg = $wechatLogic->sendTemplateMsg($prizeInfo['id'],$prizeInfo['user_id'], $formInfo['form_id'], $msg, 1);//发送模板消息
            if($tempMsg['errcode'] != 0){
                $this->addFail($prizeInfo['id'],$prizeInfo['user_id'],$formInfo['form_id'],$msg,1);
                return self::resultFormat('红包金额不足，开奖失败');
            }
            D('Common/FormId')->saveData(array('status' => 1, 'id' => $formInfo['id']));
            return self::resultFormat('红包金额不足，开奖失败');
        }

        $modelPrize = D('Common/Prize');
        $modelPrize->startTrans();

        $resGetIn = true;
        $resAddr = true;
        $resMoney = true;
        $memberModel = D('Common/Member');
        $addrModel = D('Common/Address');
        $transModel = D('Common/Transfer');
        foreach ($luckList as $key => $val) {
            $resGetIn = $getInModel->saveData(array('status' => 1, 'id' => $val['id']));//修改抽奖记录状态
            $resAddr = $addrModel->saveData(array('user_id' => $val['user_id'],'p_id' => $prizeInfo['id'],'address' => '','province' => '','city' => '','area' => ''));
            //增加中奖者收货地址
            //判断如果是红包 增加用户余额
            if ($prizeInfo['type'] == 2) {
                $money = $prizeInfo['package']; //单个红包金额
                $uInfo = $memberModel->getByUid($val['user_id']);
                $resMoney = $memberModel->saveData(array('amount' => $uInfo['amount'] + $money, 'uid' => $val['user_id']));
                $resTrans = $transModel->savaData(array('user_id' => $val['user_id'],'note' => '红包中奖','amount' => $money));//增加金额变动记录
                if (!$resMoney || !$resTrans) {
                    break;
                }
            }
        }

        //只有红包不完全开奖需要处理
        $status = 2;
        if ($prizeInfo['type'] == 2 && $prizeInfo['num'] > $luckTotal) {
            $status = 4;
        }
        //修改抽奖状态
        $resPrize = $modelPrize->saveData(array('status' => $status,'end_time' => time(), 'id' => $prizeInfo['id'], 'package_num' => count($luckList)));

        if (!$resMoney || !$resPrize || !$resGetIn || !$resAddr) {
            $modelPrize->rollback();
            return false;
        }
        $modelPrize->commit();

        //服务号通知幸运儿
        $modelForm = D('Common/FormId');
        $modelMember = D('Common/Member');
        foreach ($joinList_ as $k => $v) {
            if (!$prizeInfo['user_id']) {//赞助商
                $msg[] = $prizeInfo['sponsor'] . ' 发起的抽奖正在开奖，点击查看中奖名单';
            } else { //非赞助商
                if ($v['user_id'] == $prizeInfo['user_id']) {
                    $msg[] = '你发起的抽奖正在开奖，点击查看中奖名单';
                } else {
                    $uInfo = $modelMember->getByUid($v['user_id']);
                    $msg[] = $uInfo['nickname'] . ' 发起的抽奖正在开奖，点击查看中奖名单';
                }
            }

            $formInfo = $modelForm->getByUid($v['user_id']);//查询from_id
            if (!$formInfo) {
                continue;
            }
            $result = $wechatLogic->sendTemplateMsg($prizeInfo['id'],$v['user_id'], $formInfo['form_id'], $msg, 1);//发送模板消息
            if ($result['errcode'] != 0) {
                $this->addFail($prizeInfo['id'],$v['user_id'],$formInfo['form_id'],$msg,1);
                continue;
            }
            $modelForm->saveData(array('status' => 1, 'id' => $formInfo['id']));//更改formid状态
        }
        return self::resultFormat('开奖完成','',1);
    }

    /**
     * 清理过期抽奖  给没领完的红包退款
     * @param $prizeInfo
     */
    public function clearPrize($prizeInfo){
        $msg = array();
        $formModel = D('Common/FormId');
        $orderModel = D('Common/Order');
        $prizeModel = D('Common/Prize');
        $wechatLogic = new WechatLogic();

        $time = time();
        $sevenDays = 3600 * 24 * 7;
        $threeDays = 3600 * 24 * 3;
        $subTime = $time - $prizeInfo['create_time'];
        $msg[] = $prizeInfo['type'] == 1 ? $prizeInfo['name'] : '红包';
        $msg[] = '本次抽奖已过期';

        if(in_array($prizeInfo['open_type'],array(3,4))){
            $push = false;
            if($prizeInfo['open_type'] == 3 && $subTime >= $sevenDays){//手动开奖7天过期
                if($prizeModel->saveData(array('status' => 3,'id' => $prizeInfo['id']))){
                    $push = true;
                }
            }

            if($prizeInfo['open_type'] == 4 && $subTime >= $threeDays){//现场开奖3天过期
                if($prizeModel->saveData(array('status' => 3,'id' => $prizeInfo['id']))){
                    $push = true;
                }
            }

            //通知发布人
            if($push){
                $formInfo = $formModel->getByUid($prizeInfo['user_id']);
                $tempMsg = $wechatLogic->sendTemplateMsg($prizeInfo['id'],$prizeInfo['user_id'],$formInfo['form_id'],$msg,1);//发送模板消息
                if($tempMsg['errcode'] != 0){
                    $this->addFail($prizeInfo['id'],$prizeInfo['user_id'],$formInfo['form_id'],$msg,1);
                }
                D('Common/FormId')->saveData(array('status' => 1,'end_time' => $time, 'id' => $formInfo['id']));
            }
        }

        if($prizeInfo['type'] == 2){ //申请退款
            //计算需要退款的金额
            $amount = ($prizeInfo['num'] * $prizeInfo['package']) - ($prizeInfo['package_num'] * $prizeInfo['package']);
            if(!$amount){
                return;
            }

            //查询订单
            $orderInfo = $orderModel->getById($prizeInfo['oid']);

            //查询是否有申请退款记录
            $refundInfo = $orderModel->getByPid($prizeInfo['id']);
            if($refundInfo){
                if($refundInfo['status'] == 1){ //已经处理成功
                    return;
                }
                $order_no = $refundInfo['order_no'];
            }else{
                $order_no = getOrderNo();
            }
            $param = array(
                'out_trade_no' => $orderInfo['order_no'],
                'out_refund_no' => $order_no,
                'body' => '退款',
                'total_fee' => $orderInfo['amount'] * 100,
                'refund_fee' => $amount * 100,
                'notify_url' => $_SERVER['SERVER_NAME'] . '/refund',
            );
            $result = $wechatLogic->refund($param); // 申请退款
            if($result['return_code'] == 'SUCCESS'){
                if($result['result_code'] == 'SUCCESS'){
                    $refund_id = $result['refund_id'];
                    if(!$refundInfo){
                        //生成退款订单
                        D('Common/Order')->saveData(array('order_no' => $order_no,'user_id' => $prizeInfo['user_id'],'amount' => $amount,'wx_no' => $refund_id,'type' => 2));
                    }
                }
            }
        }

    }

    //统一下单回调处理
    public function unifiedorderCall($data){
        $wechatLogic = new WechatLogic();
        $postSign = $data['sign'];
        unset($data['sign']);
        ksort($post_data);// 对数据进行排序
        $user_sign = $wechatLogic->getSign($post_data);
        if($postSign != $user_sign){
            return false;
        }
        if($post_data['return_code'] == 'SUCCESS'){
            $order_no = $post_data['out_trade_no'];
            $orderModel = D('Common/Order');
            $result = $orderModel->getByNo($order_no);
            if($result){
                if($post_data['result_code'] == 'SUCCESS'){//支付成功
                    if($result['status'] == 0){
                        M()->startTrans();
                        $res1 = $orderModel->saveData(array('status' => 1,array('order_no' => $order_no))); //修改订单状态
                        $res2 = D('Common/Transfer')->savaData(array('user_id' => $result['user_id'],'note' => '发起红包抽奖','amount' => (-1) * $result['amount'],'desc' => '由[微信钱包]支付'));
                        //新增金额变动记录
                        if($res1 && $res2){
                            M()->commit();
                            return true;
                        }else{
                            M()->rollback();
                            return false;
                        }
                    }else{
                        return false;
                    }
                }else{//支付失败
                    if($orderModel->saveData(array('status' => 2,array('order_no' => $order_no)))){
                        return true;
                    }else{
                        return false;
                    }
                }
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    //申请退款回调
    public function refundCall($data){
        $wechatLogic = new WechatLogic();
        $postSign = $data['sign'];
        unset($data['sign']);
        ksort($post_data);// 对数据进行排序
        $user_sign = $wechatLogic->getSign($post_data);
        if($postSign != $user_sign){
            return false;
        }

        if($post_data['return_code'] == 'SUCCESS'){
            $order_no = $post_data['out_trade_no'];
            $orderModel = D('Common/Order');
            $prizeModel = D('Common/Prize');
            $transModel = D('Common/Transfer');
            $result = $orderModel->getByNo($order_no);
            if($result){
                if($post_data['result_code'] == 'SUCCESS'){//退款成功
                    if($result['status'] == 0){
                        $orderModel->startTrans();
                        $res1 = $orderModel->saveData(array('status' => 1),array('order_no' => $order_no));//修改退款申请订单状态
                        $res2 = $prizeModel->saveData(array('status' => 2),array('oid' => $result['id']));//修改抽奖状态
                        $res3 = $transModel->savaData(array('user_id' => $result['user_id'],'note' => '红包抽奖退款','amount' => $result['amount'],'des' => '已退款至[微信钱包]'));//增加金额变动记录
                        if($res1 && $res2 && $res3){
                            $orderModel->commit();
                            //通知发布人
                            $formModel = D('Common/FormId');
                            $formInfo = $formModel->getByUid($result['user_id']);
                            $msg = array(
                                '您的红包未被领取完，已原路退还，请注意查收。',
                                "{$result['amount']}",
                                '全名抽奖'
                            );
                            $msgTemp = $wechatLogic->sendTemplateMsg(0,$result['user_id'],$formInfo['form_id'],$msg,2);//发送模板消息
                            if($msgTemp['errcode'] != 0){
                                $this->addFail(0,$result['user_id'],$formInfo['form_id'],$msg,2);
                            }
                            D('Common/FormId')->saveData(array('status' => 1, 'id' => $formInfo['id']));
                            return true;
                        }else{
                            $orderModel->rollback();
                            return false;
                        }
                    }else{
                        return false;
                    }
                }
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    public function addFail($pid,$user_id,$form_id,$msg,$key){
        //消息发送失败 //加入到发送失败记录
        $failList = S('SEND_TEMP_MSG_FAIL');
        $failList = $failList ? $failList : array();
        $temp = array('pid' => $pid,'user_id' => $user_id,'form_id' => $form_id,'msg' => $msg,'key' => $key,'count' => 1);
        $failList[] = $temp;
        S('SEND_TEMP_MSG_FAIL',$failList);
    }

    public static function resultFormat($info = '',$data = '',$status = 0){
        return array('status' => $status,'info' => $info,'data' => $data);
    }
}