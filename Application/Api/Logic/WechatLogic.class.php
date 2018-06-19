<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/11 0011
 * Time: 15:50
 */
namespace  Api\Logic;
use Think\Model;

class WechatLogic{
    //统一下单
    public function unifiedorder($uid,$amount){
        $memberModel = D('Common/SyncLogin');
        $open_id = $memberModel->getOpenIdByUidAndType($uid,'wxxcx');
        if(!$open_id){
            return array('status' => 0,'info' => '获取唯一标识错误');
        }

        if(!$amount){
            return array('status' => 0,'info' => '订单金额不能为0');
        }
        $rate = 0.02; //%2的手续费
        $serviceCharge = $amount * $rate;
        $order_no = getOrderNo();
        $param['order_no'] = $order_no;
        $param['body'] = '抽奖助手';
        $param['total_fee'] = ($amount + $serviceCharge) * 100;
        $param['ip'] = get_client_ip();
        $param['notify_url'] = $_SERVER['SERVER_NAME'] . '/unifiedorder';
        $param['trade_type'] = 'JSAPI';
        $param['openid'] = $open_id;
        $result = $this->doUnfiedOrder($param);

        if($result['return_code'] == 'SUCCESS'){
            if($result['result_code'] == 'SUCCESS'){
                $prepay_id = $result['prepay_id'];
                //生成订单
                $res = D('Common/Order')->saveData(array('order_no' => $order_no,'user_id' => $uid,'amount' => $amount,'service_charge' => $serviceCharge,'wx_no' => $prepay_id,'type' => 1));
                if($res){
                    return array('status' => 1,'info' => '下单成功','data' => array('prepay_id' => $prepay_id,'oid' => $res));
                }else{
                    return array('status' => 0,'info' => '生成订单失败');
                }
            }else{
                return array('status' => 0,'info' => getRrroMsg($result['err_code']));
            }
        }else{
            return array('status' => 0,'info' => $result['return_msg']);
        }
    }
    //统一下单
    public function doUnfiedOrder($param){
        $wechatConfig = C('WXXCX_CONFIG');
        $data['appid'] = $wechatConfig['appid'];
        $data['mch_id'] = $wechatConfig['mch_id'];
        $data['nonce_str'] = create_rand(15);
        $data['body'] = $param['body'];
        $data['out_trade_no'] = $param['order_no'];
        $data['total_fee'] = $param['total_fee'];
        $data['spbill_create_ip'] = $param['ip'];
        $data['notify_url'] = $param['notify_url']; //回调地址
        $data['trade_type'] = $param['trade_type'];
        $sign = $this->getSign($data);
        if(!$sign){
            return false;
        }
        $data['sign'] = $sign;

        $xml = $this->ToXml($data);

        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $result = $this->wxHttpsRequest($url,$xml,false);
        return $this->FromXml($result);
    }

    //企业付款到用户  （提现）
    public function withdraw($uid,$amount,$withId){
        $memberModel = D('Common/SyncLogin');
        $orderModel = D('Common/Order');
        $memberInfoModel = D('Common/Member');
        $transModel = D('Common/Transfer');
        $withModel = D('Common/Withdraw');
        $open_id = $memberModel->getOpenIdByUidAndType($uid,'wxxcx');
        $uInfo = $memberInfoModel->getByUid($uid);
        if(!$open_id){
            return array('status' => 0,'info' => '获取唯一标识错误');
        }

        if(!$amount){
            return array('status' => 0,'info' => '订单金额不能为0');
        }

        if(!$uInfo){
            return array('status' => 0,'info' => '获取真实姓名错误');
        }
        $order_no = getOrderNo();
        $param['order_no'] = $order_no;
        $param['openid'] = $open_id;
        $param['re_user_name'] = $uInfo['nickname'];
        $param['amount'] = $amount * 100;
        $param['desc'] = '理赔';
        $param['ip'] = get_client_ip();

        $arrOrder = array('order_no' => $order_no,'user_id' => $uid,'amount' => $amount,'wx_no' => '','type' => 3);
        $orderRes = D('Common/Order')->saveData($arrOrder);//生成订单
        if(!$orderRes){
            return array('status' => 0,'info' => '生成订单失败');
        }

        $result = $this->doWithdraw($param);

        if($result['return_code'] == 'SUCCESS'){
            if($result['result_code'] == 'SUCCESS'){
                $prepay_no = $result['payment_no']; //微信订单号
                $orderModel->startTrans();
                //修改订单状态
                $resOrder = $orderModel->saveData(array('wx_no' => $prepay_no,'status' => 1,'id' => $orderRes));
                //修改账户金额
                $resMember = $memberInfoModel->saveData(array('amount' => $uInfo['amount'] - $amount,'uid' => $uid));
                //增加金额变动记录
                $resTrans = $transModel->saveData(array('user_id' => $uid,'note' => '余额提现','amount' => (-1) * $amount));
                //修改提现状态
                $resWith = $withModel->changeById($withId, 'status', 2);
                if($resOrder && $resMember && $resTrans && $resWith){
                    $orderModel->commit();
                    return array('status' => 1,'info' => '转账成功');
                }else{
                    $orderModel->rollback();
                    return array('status' => 0,'info' => '转账成功，修改订单状态失败');
                }
            }else{
                //当状态为FAIL时，存在业务结果未明确的情况，所以如果状态FAIL，请务必再请求一次查询接口
                $searchResult = $this->searchWithdraw($order_no);
                if($searchResult['return_code'] == 'SUCCESS'){
                    if($searchResult['result_code'] == 'SUCCESS'){
                        $prepay_no = $searchResult['detail_id']; //微信订单号
                        $orderModel->startTrans();
                        //修改订单状态
                        $resOrder = $orderModel->saveData(array('wx_no' => $prepay_no,'status' => 1,'id' => $orderRes));
                        //修改账户金额
                        $resMember = $memberInfoModel->saveData(array('amount' => $uInfo['amount'] - $amount,'uid' => $uid));
                        //增加金额变动记录
                        $resTrans = $transModel->saveData(array('user_id' => $uid,'note' => '余额提现','amount' => (-1) * $amount));
                        //修改提现状态
                        $resWith = $withModel->changeById($withId, 'status', 2);
                        if($resOrder && $resMember && $resTrans && $resWith){
                            $orderModel->commit();
                            return array('status' => 1,'info' => '转账成功');
                        }else{
                            $orderModel->rollback();
                            return array('status' => 0,'info' => '转账成功，修改订单状态失败');
                        }
                    }else{
                        //修改订单状态
                        $orderModel->saveData(array('status' => 2,'id' => $orderRes));//修改订单状态
                        return array('status' => 0,'info' => getRrroMsg($searchResult['err_code']));
                    }
                }else{
                    //修改订单状态
                    $orderModel->saveData(array('status' => 2,'id' => $orderRes));//修改订单状态
                    return array('status' => 0,'info' => $searchResult['return_msg']);
                }
            }
        }else{
            //修改订单状态
            $orderModel->saveData(array('status' => 2,'id' => $orderRes));//修改订单状态
            return array('status' => 0,'info' => $result['return_msg']);
        }
    }

    public function doWithdraw($param){
        $wechatConfig = C('WXXCX_CONFIG');
        $data['mch_appid'] = $wechatConfig['appid'];
        $data['mchid'] = $wechatConfig['mch_id'];
        $data['nonce_str'] = create_rand(15);
        $data['partner_trade_no'] = $param['order_no'];
        $data['openid'] = $param['openid'];
        $data['check_name'] = 'FORCE_CHECK';
        $data['re_user_name'] = $param['re_user_name'];
        $data['amount'] = $param['amount'];
        $data['desc'] = $param['desc'];
        $data['spbill_create_ip'] = $param['ip'];
        $sign = $this->getSign($data);
        if(!$sign){
            return false;
        }
        $data['sign'] = $sign;

        $xml = $this->ToXml($data);

        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
        $result = $this->wxHttpsRequest($url,$xml,false);
        return $this->FromXml($result);
    }
    //查询企业付款结果
    public function searchWithdraw($orderNo){
        $wechatConfig = C('WXXCX_CONFIG');
        $data['appid'] = $wechatConfig['appid'];
        $data['mch_id'] = $wechatConfig['mch_id'];
        $data['nonce_str'] = create_rand(15);
        $data['partner_trade_no'] = $orderNo;
        $sign = $this->getSign($data);
        if(!$sign){
            return false;
        }
        $data['sign'] = $sign;

        $xml = $this->ToXml($data);
        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/gettransferinfo';
        $result = $this->wxHttpsRequest($url,$xml,false);
        return $this->FromXml($result);
    }

    //发送模板消息
    public function sendTemplateMsg($pid,$uid,$form_id,$param,$tempKey){
        $memberModel = D('Common/SyncLogin');
        $open_id = $memberModel->getOpenIdByUidAndType($uid,'wxxcx');
//        $open_id = 'oOf9n5Or_XxMruurhJu6Q1X6YhVw';
        if(!$open_id){
            return false;
        }
        $data = $this->formatMsgData($param);
        $d['touser'] = $open_id;
        $d['template_id'] = $this->getTemplateId($tempKey);
        if(!$pid){
            $d["page"] = "pages/main/main/";
        }else{
            $d["page"] = "pages/lotterydetail_no/lotterydetail?pid={$pid}&isShare=1";
        }
        $d["form_id"] = $form_id;
        $d['data'] = $data;

        $_data = json_encode($d,JSON_UNESCAPED_UNICODE);
        $json = null;

        $token = $this->getAccessToken();

        $posturl="https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=" . $token;
        $result = $this->wxHttpsRequest($posturl, $_data);
        $json = json_decode($result, true);

        $time = time();
        $model = new Model();
        $model->execute("insert into luck_sendtemp_log (`access_token`,`content`,`res`,`create_time`) values ('{$token}','{$_data}','{$result}',{$time})");

        return $json;
    }

    //格式化消息data
    public function formatMsgData($info){
        $data = array();
        foreach($info as $key => $val){
            $k = 'keyword' . ($key + 1);
            $data[$k] = array('value' => $val);
        }
        return $data;
    }

    public function wxHttpsRequest($url, $data = null,$isCert = false){
        $curl = curl_init();
        curl_setopt($curl,CURLOPT_TIMEOUT,30);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        if($isCert){
            curl_setopt($curl,CURLOPT_SSLCERT,STATIC_PATH.'cert.pem');
            curl_setopt($curl,CURLOPT_SSLKEY,STATIC_PATH.'private.pem');
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

    public function getTemplateId($key){
        switch ($key){
            case 1:
                return 'EhDeQWWK0OYuGJ3PBp1YYha0Wn1Gn5-ZdN4H_Z6eOl4'; //开奖
                break;
            case 2:
                return 'wAhNPGcjFv4zWCElHEOqkSxx_cMOAhqtnOJ3lRDX07o'; //退款
                break;
            default:
                return 'EhDeQWWK0OYuGJ3PBp1YYha0Wn1Gn5-ZdN4H_Z6eOl4';
                break;
        }
    }

    //微信小程序退款
    public function refund($param){
        $wechatConfig = C('WXXCX_CONFIG');
        $data['appid'] = $wechatConfig['appid'];
        $data['mch_id'] = $wechatConfig['mch_id'];
        $data['nonce_str'] = create_rand(15);
        $data['transaction_id'] = $param['transaction_id'];
        $data['out_refund_no'] = $param['out_refund_no'];
        $data['body'] = $param['body'];
        $data['total_fee'] = $param['total_fee'];
        $data['refund_fee'] = $param['refund_fee'];
        $data['notify_url'] = $param['notify_url']; //回调地址
        $sign = $this->getSign($data);
        if(!$sign){
            return false;
        }
        $data['sign'] = $sign;
        $url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
        $result = $this->wxHttpsRequest($url,$data,true);
        return json_decode($result,1);
    }

    public function getSign($param){
        if(!$param){
            return false;
        }
        ksort($param);
        $str = '';
        foreach($param as $key => $val){
            if(!empty($val)){
                $str .= "{$key}={$val}&";
            }
        }
        $str = rtrim($str,'&');
        $wechatConfig = C('WXXCX_CONFIG');
        if(!$wechatConfig){
            return false;
        }
        $str .= "&key={$wechatConfig['key']}";
        $tempString = strtoupper(md5($str));
        return $tempString;
    }

    public function getAccessToken(){
        $token = S('access_token');
        if(!$token){
            $wechatConf = C('WXXCX_CONFIG');
            if(!$wechatConf){
                return false;
            }
            $appid = $wechatConf['appid'];
            $secret = $wechatConf['secret'];
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='. $appid .'&secret=' . $secret;
            $result = $this->wxHttpsRequest($url);
            $arr = json_decode($result,1);
            S('access_token',$arr['access_token'],7000);
            $token = $arr['access_token'];
        }
        return $token;
    }

    public function ToXml($param){
        if(!is_array($param) || count($param) <= 0){
            return false;
        }

        $xml = "<xml>";
        foreach ($param as $key => $val){
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }

    public function FromXml($xml){
        if(!$xml){
            return false;
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $result = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $result;
    }

    //下载小程序码
    public function getSmallProgramQr($pid){
        $data = array();
        $data['scene'] = $pid.';1';
        $data['page'] = 'pages/lotterydetail_no/lotterydetail';
        $token = $this->getAccessToken();
        $url = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token='.$token;
        $jpg = $this->https_curl_json($url, $data, 'json');

        if(empty($jpg) || json_decode($jpg)){ //获取失败使用默认图
            return 'static/smallQr/small.jpg';
        }

        //生成图片
        $imgDir = 'Uploads/smallQr/' . date('Y-m-d',time()) . '/';
        if(!is_dir($imgDir)){
            @mkdir($imgDir,0777,true);
        }

        $filename = uniqid() . ".png";///要生成的图片名字
        $filePath = $imgDir . $filename;

        $file = fopen($filePath, "w");//打开文件准备写入
        fwrite($file, $jpg);//写入
        fclose($file);//关闭

        //图片是否存在
        if(!file_exists($filePath)){ // 图片保存失败则使用默认图
            return 'static/smallQr/small.jpg';
        }
        return $filePath;
    }

    public function https_curl_json($url, $data, $type = 'json'){
        if($type=='json'){//json $_POST=json_decode(file_get_contents('php://input'), TRUE);
            $headers = array("Content-type: application/json;charset=UTF-8","Accept: application/json","Cache-Control: no-cache", "Pragma: no-cache");
            $data=json_encode($data);
        }
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS,$data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers );
        $output = curl_exec($curl);
        if (curl_errno($curl)) {
            echo 'Errno'.curl_error($curl);
        }
        curl_close($curl);
        return $output;
    }
}