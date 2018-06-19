<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/8 0008
 * Time: 9:17
 */
namespace Cli\Controller;
use Api\Logic\WechatLogic;
use \Common\Logic\LuckLogic;
use Think\Controller;

class TaskController extends Controller {
    //http://cj.xmyunyou.com/Home/Task/runTask  半小时执行一次   按时间开奖
    //http://cj.xmyunyou.com/Home/Task/runTaskOther  3分钟执行一次 按人数开奖
    //http://cj.xmyunyou.com/Home/Task/clearOverAndRefund 一天一次
    //http://cj.xmyunyou.com/Home/Task/failMsgResend 5分钟一次

    //定时任务  定时开奖   半小时执行一次  这里只执行安时间开奖的类型
    public function runTask(){
        $start = date('Y-m-d H:i:s',time());
        echo "定时开奖脚本开始执行：{$start}\n";
        $map = array(
            'where' => array(
                'open_time' => array('ELT',time()),
                'status' => 1,
                'open_type' => 1
            ),
            'field' => '*'
        );
        $list = D('Common/Prize')->getListRows($map,false);
        foreach($list as $key => $val){
            D('Common/Luck','Logic')->findLuckly($val);
        }
        echo '执行完成:' . date('Y-m-d H:i:s',time()) . "\n";
    }

    //定时任务 按人数开奖  人数够了就加到队列里开奖  x分钟执行一次
    public function runTaskOther(){
        $start = date('Y-m-d H:i:s',time());
        echo "按人数开奖脚本开始执行：{$start}\n";
        $taskKey = C('AUTO_TASK_KEY');
        $taskList = S($taskKey);
        echo "显示任务列表：\n";
        print_r($taskList);
        echo "任务列表结束\n";
        $modelPrize = D('Common/Prize');
        foreach ($taskList as $key => $val){
            $prizeInfo = $modelPrize->getById($val['pid']);
            $res = D('Common/Luck','Logic')->findLuckly($prizeInfo);
            print_r($res);
            echo "\n";
        }
        S($taskKey,null);
        echo '执行完成:' . date('Y-m-d H:i:s',time()) . "\n";
    }

    //发送失败的消息在这里继续发送  5次之后还失败则停止发送
    public function failMsgResend(){
        $failList = S('SEND_TEMP_MSG_FAIL');
        $wechatLogic = new WechatLogic();
        foreach($failList as $key => $val){
            $result = $wechatLogic->sendTemplateMsg($val['pid'],$val['user_id'], $val['form_id'], $val['msg'], $val['key']);//发送模板消息
            if($result['errcode'] != 1){
                $temp = $val['count'] + 1;
                if($temp > 5){
                    array_splice($failList,$key,1);
                }else{
                    $failList[$key]['count'] = $temp;
                }
            }
        }
        S('SEND_TEMP_MSG_FAIL',$failList);
        echo '完成';
    }

    //手动开奖和现场开奖过期自动清理  未完全开奖的红包要退款
    public function clearOverAndRefund(){
        $map = array(
            'where' => array(
                'open_type' => array('IN','3,4'),
                'status' => array('IN','1,4')
            ),
            'field' => '*'
        );
        $list = D('Common/Prize')->getListRows($map,false);
        foreach($list as $key => $val){
            D('Common/Luck','Logic')->clearPrize($val);
        }
        echo '完成';
    }
}