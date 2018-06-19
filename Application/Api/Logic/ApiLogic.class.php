<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/11 0011
 * Time: 9:58
 */
namespace Api\Logic;

use Think\Model;

class ApiLogic{
    //首页赞助商
    public function sponsorFormat($list,$uid){
        $modelGetIn = D('Common/GetIn');
        foreach($list as $key => $val){
            $list[$key]['open_time'] = date('Y-m-d H:i:s',$val['open_time']);
            //判断当前用户是否参与
            $list[$key]['isJoin'] = $modelGetIn->isJoin($val['id'],$uid);
        }
        return $list;
    }

    public function prizeFormat($info,$uid){
        $info['open_time'] = date('Y-m-d H:i:s',$info['open_time']);
        $info['isSponsor'] = $info['user_id'] ? false : true; //是否是赞助商发布
        $info['isUrl'] = $info['sponsor_url'] ? true : false; //是否支持小程序跳转
        $info['isJoin'] = D('Common/GetIn')->isJoin($info['id'],$uid);
        $info['isWinner'] = $this->isWinner($info['id'],$uid);
        $info['isOwn'] = $info['user_id'] == $uid ? true : false; //是否是自己发布
        return $info;
    }

    //判断当前用户是否中奖
    public function isWinner($pid,$uid){
        return D('Common/GetIn')->isWinner($pid,$uid);
    }

    //获取参与抽奖用户
    public function getJoinList($pid,$page = 1,$pageSize = 7){
        $modelGetIn = D('Common/GetIn');
        $memberModel = D('Common/Member');
        $start = ($page - 1) * $pageSize;
        $map = array(
            'where' => array('p_id' => $pid),
            'field' => 'user_id',
            'limit' => "{$start},{$pageSize}",
            'order' => 'id desc'
        );
        list($list,$total) = $modelGetIn->getAllowList($map,true); //获取参与抽奖的人
        foreach($list as $key => $val){
            $uInfo = $memberModel->getByUid($val['user_id']);
            $list[$key]['nickname'] = $uInfo['nickname'];
            $list[$key]['thumb'] = $uInfo['thumb'];
        }
        return array('list' => $list,'total' => $total);
    }

    //获取用户的抽奖 或 中奖的抽奖
    public function lucklyList($uid,$isLuckly = false,$page = 1,$pageSize = 10){
        $start = ($page - 1) * $pageSize;
        $map = array(
            'where' => array('user_id' => $uid),
            'field' => 'p_id,status,create_time',
            'order' => 'id desc',
            'limit' => "{$start},{$pageSize}",
        );

        if($isLuckly){//中奖记录
            $map['where']['status'] = 1;
            list($list,$total) = D('Common/GetIn')->getAllowList($map,true);
            $model = D('Common/Prize');
            $prizeInfo = array('isEnd' => array(),'isNotEnd' => array(),'total' => $total);
            foreach($list as $key => $val){
                $info = $model->getById($val['p_id'],'id,type,thumb,name,num,open_type,open_time,open_num,sponsor,status,team_enable,end_time');
                $info['end_time'] = $info['end_time'] ? date('m月d日',$info['end_time']) : '';
                $info['open_time'] = $info['open_time'] ? date('m月d日 H:i:s',$info['open_time']) : '';
                //结束了和没结束的分开
                if($info['status'] == 1){ //结束
                    $prizeInfo['isNotEnd'][] = $info;
                }else{ //未结束
                    $prizeInfo['isEnd'][] = $info;
                }
            }
        }else{//查询全部
            $model = new Model();
            //查询所有发布的抽奖 和 参与的抽奖 合并
            $field = 'id,type,thumb,name,num,open_type,open_time,open_num,sponsor,status,team_enable,end_time';
            $allList  = $model->query('select p_id as id from luck_get_in where user_id='. $uid .' UNION select id from luck_prize where user_id='.$uid);
            $temp = array();
            foreach($allList as $k => $v){
                $temp[] = $v['id'];
            }
            rsort($temp);
            $prizeInfo['total'] = count($temp);
            $finalArr = array_slice($temp, $start, $pageSize);
            $finalIds = implode(',',$finalArr);
            if(!$finalIds){
                $prizeInfo['isNotEnd'] = array();
                $prizeInfo['isEnd'] = array() ;
            }else{
                $result = $model->query('select ' . $field . ' from luck_prize where id in ('. $finalIds .') order by id desc');
                foreach ($result as $key => $val){
                    $val['end_time'] = $val['end_time'] ? date('m月d日',$val['end_time']) : '';
                    $val['open_time'] = $val['open_time'] ? date('m月d日 H:i:s',$val['open_time']) : '';
                    if($val['status'] == 1){ //结束
                        $prizeInfo['isNotEnd'][] = $val;
                    }else{ //未结束
                        $prizeInfo['isEnd'][] = $val;
                    }
                }
            }
        }
        return $prizeInfo;
    }

    //获取用户发布的抽奖
    public function prizeLaunch($uid,$page = 1,$pageSize = 10){
        $start = ($page - 1) * $pageSize;
        $map = array(
            'where' => array('user_id' => $uid),
            'field' => 'id,type,thumb,name,num,open_type,open_time,open_num,sponsor,status,team_enable,end_time',
            'order' => 'id desc',
            'limit' => "{$start},{$pageSize}",
        );
        list($list,$total) = D('Common/Prize')->getListRows($map,true);

        $prizeInfo = array('isEnd' => array(),'isNotEnd' => array(),'total' => $total);
        foreach($list as $key => $val){
            $val['end_time'] = $val['end_time'] ? date('m月d日',$val['end_time']) : '';
            $val['open_time'] = $val['open_time'] ? date('m月d日 H:i:s',$val['open_time']) : '';
            //结束了和没结束的分开
            if($val['status'] != 1){ //结束
                $prizeInfo['isEnd'][] = $val;
            }else{ //未结束
                $prizeInfo['isNotEnd'][] = $val;
            }
        }
        return $prizeInfo;
    }

    //统计用户中奖的次数
    public function luckListCount($uid,$isLuckly = false){
        $where = array('user_id' => $uid);
        if($isLuckly){
            $where['status'] = 1;
            $getIn = M('GetIn')->where($where)->field('count(id) as c')->find();
            $total = $getIn['c'] ? $getIn['c'] : 0;
        }else{
            //查询所有参与的抽奖
            $model = new Model();
            $result  = $model->query('select p_id as id from luck_get_in where user_id='. $uid .' UNION select id from luck_prize where user_id='.$uid);
            $total = count($result);
        }
        return $total;
    }

    //统计用户发布抽奖次数
    public function prizeLaunchCount($uid){
        $result = M('Prize')->where(array('user_id' => $uid))->field('count(id) as c')->find();
        return $result['c'] ? $result['c'] : 0;
    }

    //获取团Id
    public function getTeamId($teamList){
        if(empty($teamList) || !$teamList){
            return 0;
        }
        foreach($teamList as $item){
            if($item['sib_id'] == 0){
                return $item['id'];
            }
        }
        return 0;
    }

    //帮助列表
    public function helps($page = 1,$pageSize){
        $start = ($page - 1) * $pageSize;
        $map = array(
            'where' => array(
                'status' => 1
            ),
            'order' => 'listorder asc',
            'field' => 'title,content',
            'limit' => "{$start},{$pageSize}",
        );
        list($list,$total) = D('Common/Article')->getListRows($map);
        return array('list' => $list,'total' => $total);
    }

    //推荐小程序
    public function getRecommend($page = 1,$pageSize = 5){
        $start = ($page - 1) * $pageSize;
        $map = array(
            'where' => array('status' => 1),
            'order' => 'sort asc',
            'field' => 'name,icon,descript,url,appid',
            'limit' => "{$start},{$pageSize}"
        );
        list($list,$total) = D('Common/Recommend')->getListRows($map,true);
        return array('list' => $list,'total' => $total);
    }

    //获取交易记录
    public function getAmountList($uid,$page = 1,$pageSize = 5){
        $start = ($page - 1) * $pageSize;
        $map = array(
            'where' => array(
                'user_id' => $uid,
            ),
            'order' => 'id desc',
            'field' => 'amount,note,des,create_time',
            'limit' => "{$start},{$pageSize}",
        );
        return D('Common/Transfer')->getAllowList($map,true);
    }

    public function amountListFormat($list){
        foreach($list as $key => $val){
            $list[$key]['create_time'] = date('Y-m-d H:i:s',$val['create_time']);
        }
        return $list;
    }

    //统计提现金额
    public function withdrawCount($uid,$status = 1){
        $result = M('Withdraw')->where(array('user_id' => $uid,'status' => $status))->field('sum(amount) as c')->find();
        return $result['c'] ? $result['c'] : 0;
    }

    //获取未支付的订单
    public function getNoPayOrder($uid){
        $result = D('Common/Order')->getByUid($uid);
        $arr = array('prepay_id' => $result['wx_no'],'oid' => $result['id']);
        return $arr;
    }

    //获取中奖者收货地址
    public function getLuckAddr($pid){
        if(!$pid){
            return array(array(),0,0);
        }
        $result = D('Common/GetIn')->getLucklyById($pid);
        $addrModel = D('Common/Address');
        $memberModel = D('Common/Member');
        $isSetCount = 0;
        foreach ($result as $key => &$val){
            $res = $addrModel->getByPidUid($val['uid'],$pid);
            $uInfo = $memberModel->getByUid($val['uid']);
            $val['address'] = $res['address'];
            $val['province'] = $res['province'];
            $val['city'] = $res['city'];
            $val['area'] = $res['area'];
            $val['mobile'] = $res['mobile'];
            $val['accept_name'] = $res['accept_name'];
            $val['nickname'] = $uInfo['nickname'];
            $val['thumb'] = $uInfo['thumb'];
            $res['is_set'] && $isSetCount++ ;
        }
         return array($result,$isSetCount,count($result));
    }

    public function joinPrize($pid,$uid,$tid = 0){
        $arr = array('status' => 0,'info' => '','data' => array());
        if(!$pid){
            $arr['info'] = '参数错误';
            return $arr;
        }
        $prizeInfo = D('Common/Prize')->getById($pid);
        if(!$prizeInfo || $prizeInfo['status'] != 1){
            $arr['info'] = '该活动不存在或已经结束';
            return $arr;
        }
        if($prizeInfo['open_type'] == 1 && $prizeInfo['open_time'] < time()){
            $arr['info'] = '该活动已经结束';
            return $arr;
        }
        if($prizeInfo['status'] != 1){
            $arr['info'] = '该活动已经结束';
            return $arr;
        }

        $getInModel = D('Common/GetIn');
        $joinList = $getInModel->getByPid($pid,'user_id');
        foreach($joinList as $key => $val){
            if ($val['user_id'] == $uid){
                $arr['info'] = '您已经参加了该活动哦';
                return $arr;
            }
        }

        $getInModel->startTrans();
        $res = $getInModel->saveData(array(
            'user_id' => $uid,
            'p_id' => $pid
        ));

        //如果开启组团 每个参与抽奖的人自动成为团队一员
        $res1 = true;
        if($prizeInfo['team_enable'] == 1){ //开启组团
            $teamModel = D('Common/Team');
            $param = array(
                'user_id' => $uid,
                'p_id' => $prizeInfo['id'],
                'g_id' => $res
            );
            if(!$tid){ //不是被邀请的
                $param['sib_id'] = 0;
            }else{ //被邀请的  判断团里人数
                $teamList = D('Common/Team')->getByid($tid);
                if(count($teamList) >= 3){
                    $arr['info'] = '参团人数达到上限';
                    return $arr;
                }
                $param['sib_id'] = $tid;
            }
            $res1 = $teamModel->saveData($param);
        }

        if($res && $res1){
            $getInModel->commit();
            //如果是按人数自动开奖  在此判断人数是否足够 足够了开奖
            if($prizeInfo['open_type'] == 2){
                $joinList = $getInModel->getByPid($pid,'user_id');
                if($prizeInfo['open_num'] <= count($joinList)){
                    $taskKey = C('AUTO_TASK_KEY');
                    //如果是按时间自动开奖就添加到任务队列
                    $taskList = S($taskKey);
                    $taskList = $taskList ? $taskList : array();
                    $isIn = false;
                    foreach($taskList as $k => $v){
                        if($v['pid'] == $prizeInfo['id']){
                            $isIn = true;
                        }
                    }
                    if(!$isIn){
                        $param = array(
                            'pid' => $prizeInfo['id']
                        );
                        $taskList[] = $param;
                        S($taskKey,$taskList);
                    }
                }
            }
            $arr['status'] = 1;
            $arr['info'] = '参与抽奖成功';
            return $arr;
        }else{
            $getInModel->rollback();
            $arr['info'] = '参与抽奖失败';
            return $arr;
        }
    }
}