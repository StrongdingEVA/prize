<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/4 0004
 * Time: 11:40
 */
namespace Api\Controller;
use Api\Logic\ApiLogic;
use Api\Logic\ImgLogic;
use Api\Logic\WechatLogic;
use Common\Logic\LuckLogic;
use Think\Model;

class LuckController extends BaseController {

    /**
     * 获取首页赞助商抽奖商品
     */
    public function getSponsor(){
        $this->checkLogin();
        $http_request_mode = 'request';
        $page = I($http_request_mode . '.page_no', 1, 'intval');
        $pageSize = I($http_request_mode . '.page_size', 10, 'intval');
        $time = strtotime(date('Y-m-d'),time());
        $model = new Model();
        $result = $model->query('select group_concat(id) as ids from luck_prize where user_id=0 and status=1 and (open_time>'.$time.' or open_type in(2,3,4))');
        if(!$result || !$result[0]['ids']){
            $this->coverRs(array('status' => 1,'data' => array('list' => array(),'total' => 0)));exit;
        }

        $idsArr = explode(',',$result[0]['ids']);
        $total = count($idsArr);
        $start = ($page - 1) * $pageSize;
        $finalArr = array_slice($idsArr,$start,$pageSize);
        $map = array(
            'where' => array(
                'id' => array('in',$finalArr)
            ),
            'field' => 'id,thumb,name,num,open_type,open_time,open_num,sponsor,status,team_enable',
            'order' => 'sort asc,create_time desc',
        );
        $list = D('Common/Prize')->getListRows($map,false);
        $list = LC('Api')->sponsorFormat($list,$this->uid);
        $this->coverRs(array('status' => 1,'data' => array('list' => $list,'total' => $total)));
    }

    /**
     * 获取抽奖详情
     * @param $pid 抽奖id
     */
    public function showPrize(){
        $this->checkLogin();
        $http_request_mode = 'request';
        $id = I($http_request_mode . '.pid', 0, 'intval');
        if(!$id){
            $this->coverRs(array('status' => 0,'info' => '参数错误'));exit;
        }
        $apiLogic = new ApiLogic();
        $info = D('Common/Prize')->getById($id);
        $info = $apiLogic->prizeFormat($info,$this->uid);

        $result = $apiLogic->getJoinList($id);
        $info['totalJoin'] = $result['total'];
        $info['teamId'] = 0;
        $luckList = D('Common/GetIn')->getLucklyById($id); //获取中奖的用户
        $teamList = array();
        if($info['team_enable'] == 1){ //开启拼团 //获取我所在的团的成员
            $teamList = D('Common/Team')->getTeamUser($info['id'],$this->uid);
            $info['teamId'] = $apiLogic->getTeamId($teamList);
        }

        $this->coverRs(array('status' => 1,'data' => array('info' => $info,'luckList' => $luckList,'joinList' => $result['list'],'teamList' => $teamList)));
    }

    /**
     * 获取参与抽奖的用户
     * @param int $pid 抽奖ID
     * @param int $page_no 页码 默认1
     * @param int $page_size 大小 默认10
     */
    public function getJoinList(){
        $this->checkLogin();
        $http_request_mode = 'request';
        $id = I($http_request_mode . '.pid', 0, 'intval');
        $page = I($http_request_mode . '.page_no', 1, 'intval');
        $pageSize = I($http_request_mode . '.page_size', 10, 'intval');

        if(!$id){
            $this->coverRs(array('status' => 0,'info' => '参数错误'));exit;
        }
        //获取参与抽奖的人
        $result = LC('Api')->getJoinList($id,$page,$pageSize);
        $joinList = $result['list'];
        $joinTotal = $result['total'];

        $this->coverRs(array('status' => 1,'data' => array('joinList' => $joinList,'joinTotal' => $joinTotal)));
    }

    /**
     * 获取推荐广告
     *
     */
    public function getExtension(){
        $map = array(
            'where' => array('obj_id' => 1,'status' => 1),
            'field' => 'id,image,title,url'
        );
        $pushed = S('is_extension_' . $this->uid);
        $result = D('Common/Advs')->getListRows($map,false);
        foreach($result as $key => $val){
            if(!in_array($val['id'],$pushed)){
                $info = $val;
            }
        }
        if(!$info){
            $info = $result ? $result[0] : array();
        }

        $this->coverRs(array('status' => 1,'data' => $info));
    }

    /**
     * 发布一个抽奖活动
     * @param $id int 编辑的时候传此参数 不传默认发布新的抽奖
     * @param $type int 1 实物 2 红包
     * @param $oid int 订单ID 默认空 如果是红包抽奖此值不能为空
     * @param $thumb string 奖品图片
     * @param $name string 奖品名称
     * @param $num int 奖品数量 不能超过100个
     * @param $open_type 开奖方式 1按时间自动开奖 2 按人数自动开奖 3手动开奖 4现场开奖
     * @param $open_time string 非必填  open_type 为1时为必填
     * @param $open_num int 非必填  open_type 为2时为必填
     * @param $package float 非必填  type 为2时为必填
     */
    public function editPrize(){
        $this->checkLogin();
        $http_request_mode = 'request';

        $data['id'] = I($http_request_mode . '.id', 0,'intval');
        $data['type'] = I($http_request_mode . '.type', 1,'intval');
        $data['oid'] = I($http_request_mode . '.oid', 0,'intval');
        $data['thumb'] = I($http_request_mode . '.thumb', 'http://img.xmyunyou.com/2018-06-04_5b151155343b8.png'); //图片
        $data['name'] = I($http_request_mode . '.name', ''); //奖品名称
        $data['num'] = I($http_request_mode . '.num', 1,'intval'); //奖品个数
        $data['open_type'] = I($http_request_mode . '.open_type', 0, 'intval'); //开奖方式 1 ，2 ， 3 ，4
        $data['open_time'] = I($http_request_mode . '.open_time', ''); //开奖时间
        $data['open_num'] = I($http_request_mode . '.open_num', 0, 'intval'); //开奖人数
        $data['package'] = I($http_request_mode . '.package', 0, 'intval'); //单个红包金额
        $data['team_enable'] = I($http_request_mode . '.team_enable', 0, 'intval'); //是否允许组团
        $data['user_id'] = $this->uid;
        $model = D('Common/Prize');
        //如果是编辑 要判断是否已经有人参与了抽奖
        if($data['id']){
            $list = D('Common/GetIn')->getByPid($data['id'],'id');
            if($list){
                $this->coverRs(array('status' => 1,'info' => '已经有人参与的抽奖不能编辑哦~'));exit;
            }
        }

        $res = $model->saveData($data);
        if(!$res){
            $this->coverRs(array('status' => 0,'info' => $model->getError()));exit;
        }
        $id = $data['id'] ? $data['id'] : $res;
        $this->coverRs(array('status' => 1,'info' => '成功','data' => $id));
    }

    /**
     * 获取基本信息
     */
    public function getMyInfo(){
        $uInfo = D('Common/Member')->getByUid($this->uid);
        $apiLogic = new ApiLogic();
        $temp = array(
            'uid' => $uInfo['uid'],
            'nickname' => $uInfo['nickname'],
            'thumb' => $uInfo['thumb'],
            'amount' => $uInfo['amount'],
            'all' => $apiLogic->luckListCount($this->uid),
            'launch' => $apiLogic->prizeLaunchCount($this->uid),
            'win' => $apiLogic->luckListCount($this->uid,1),
        );
        $this->coverRs(array('status' => 1,'data' => $temp));
    }

    /**
     * 获取抽奖记录、中奖记录
     * @param $is_luckly int 获取所有参与抽奖记录  1 获取中奖记录
     * @param $page_on int
     * @param $page_size 默认5
     */
    public function lucklyList(){
        $this->checkLogin();
        $http_request_mode = 'request';
        $is_luckly = I($http_request_mode . '.is_luckly', 0,'intval');
        $page_no = I($http_request_mode . '.page_no', 1,'intval');
        $page_size = I($http_request_mode . '.page_size',5,'intval');

        $prizeInfo = LC('Api')->lucklyList($this->uid,$is_luckly,$page_no,$page_size);
        $this->coverRs(array('status' => 1,'data' => $prizeInfo));
    }

    /**
     * 我发起的抽奖
     * @param $page_on int
     * @param $page_size 默认5
     *
     */
    public function prizeLaunch(){
        $this->checkLogin();
        $http_request_mode = 'request';
        $page_no = I($http_request_mode . '.page_no', 1,'intval');
        $page_size = I($http_request_mode . '.page_size',5,'intval');
        $prizeInfo = LC('Api')->prizeLaunch($this->uid,$page_no,$page_size);
        $this->coverRs(array('status' => 1,'data' => $prizeInfo));
    }

    /**
     * 参与一个抽奖活动
     * @param $pid int 抽奖id
     * @param $tid int 组团id 默认为空
     *
     */
    public function joinPrize(){
        $this->checkLogin();
        $http_request_mode = 'request';
        $pid = I($http_request_mode . '.pid', 0,'intval');
        $tid = I($http_request_mode . '.tid', 0,'intval');
        $logic = new ApiLogic();
        $result = $logic->joinPrize($pid,$this->uid,$tid);
        $this->coverRs($result);
    }


    /**
     * 设置中奖人收货地址
     * @param $pid 抽奖ID
     * @param $province string 省
     * @param $city string 市
     * @param $area string 区
     * @param $address 收货地址详情
     * @param $mobile string
     * @param $accept_name string 收货人姓名
     */
    public function editAddress(){
        $this->checkLogin();
        $http_request_mode = 'request';

        $where['p_id'] = I($http_request_mode . '.pid', '');
        $where['user_id'] = $this->uid;
        $data['accept_name'] = I($http_request_mode . '.accept_name', '');
        $data['address'] = I($http_request_mode . '.address', '');
        $data['province'] = I($http_request_mode . '.province', '');
        $data['city'] = I($http_request_mode . '.city', '');
        $data['area'] = I($http_request_mode . '.area', '');
        $data['mobile'] = I($http_request_mode . '.mobile', '');
        $data['is_set'] = 1;

        if(!$data['accept_name']){
            $this->coverRs(array('status' => 0,'info' => '收货人不能为空'));exit;
        }

        if(!$data['address'] || !$data['province'] || !$data['city'] || !$data['area'] || !$data['mobile']){
            $this->coverRs(array('status' => 0,'info' => '地址不能为空'));exit;
        }elseif(!$where['p_id']){
            $this->coverRs(array('status' => 0,'info' => '参数错误'));exit;
        }

        $model = D('Common/Address');
        $model->saveData($data,$where);
        $this->coverRs(array('status' => 1,'info' => '设置地址成功'));
    }

    /**
     * 查看中奖人地址信息
     * @param $pid int 抽奖ID
     */
    public function showLuckAddr(){
        $this->checkLogin();
        $http_request_mode = 'request';

        $pid = I($http_request_mode . '.pid', '');
        if(!$pid){
            $this->coverRs(array('status' => 0,'info' => '参数错误'));exit;
        }
        $apiLogic = new ApiLogic();
        list($list,$isSet,$total) = $apiLogic->getLuckAddr($pid);
        $this->coverRs(array('status' => 1,'info' => '获取成功','data' => array('list' => $list,'isSet' => $isSet,'total' => $total)));
    }

    /**
     * 地址详情
     * @param $id int 地址ID
     */
    public function addressDetail(){
        $this->checkLogin();
        $http_request_mode = 'request';

        $id = I($http_request_mode . '.id', '');
        if(!$id){
            $this->coverRs(array('status' => 0,'info' => '参数错误'));exit;
        }

        $info = D('Common/Address')->getById($id);
        $this->coverRs(array('status' => 0,'info' => '获取地址成功','data' => $info));exit;
    }

    /**
     * 手动开奖
     * @param $pid int 抽奖ID
     */
    public function unAutomatic(){
        $this->checkLogin();
        $http_request_mode = 'request';
        $pid = I($http_request_mode . '.pid', 0,'intval');
        if(!$pid){
            $this->coverRs(array('status' => 0,'info' => '参数错误'));exit;
        }
        $prizeInfo = D('Common/Prize')->getById($pid);
        if(!$prizeInfo || $prizeInfo['status'] != 1){
            $this->coverRs(array('status' => 0,'info' => '该活动不存在或已经结束'));exit;
        }
        if($prizeInfo['user_id'] != $this->uid){
            $this->coverRs(array('status' => 0,'info' => '只有发起人才可以开奖'));exit;
        }
        if(!in_array($prizeInfo['open_type'],array(3,4))){
            $this->coverRs(array('status' => 0,'info' => '操作错误'));exit;
        }
        $luckLogic = new LuckLogic();
        $result = $luckLogic->findLuckly($prizeInfo);
        $this->coverRs(array('status' => $result['status'],'info' => $result['info']));exit;
    }

    /**
     * 小程序统一下单
     * @param $amount float 订单金额
     */
    public function unifiedorder(){
        $this->checkLogin();
        $http_request_mode = 'request';
        $amount = I($http_request_mode . '.amount', 0,'float');
        if(!$amount){
            $this->coverRs(array('status' => 0,'info' => '订单金额不能为0'));exit;
        }

        //判断是否重复下单
        $result = LC('Api')->getNoPayOrder($this->uid);
        if($result){
            $this->coverRs(array('status' => 1,'info' => '您已经有未支付的订单了哦~','data' => $result));exit;
        }
        $wechatLogic = new WechatLogic();
        $result = $wechatLogic->unifiedorder($this->uid,$amount);
        if($result['status'] == 0){
            $this->coverRs(array('status' => 0,'info' => $result['info']));exit;
        }else{
            $this->coverRs(array('status' => 1,'info' => $result['info'],'data' => $result['data']));exit;
        }
    }

    /**
     * 设置form_id
     * @param $form_id string
     */
    public function setFormId(){
        $this->checkLogin();
        $http_request_mode = 'request';
        $form_id = I($http_request_mode . '.form_id', '');
        if(!$form_id){
            $this->coverRs(array('status' => 0,'info' => '参数错误'));exit;
        }
        if($form_id == 'the formId is a mock one'){
            $this->coverRs(array('status' => 1,'info' => '请求成功'));exit;
        }
        if(D('Common/FormId')->saveData(array('user_id' => $this->uid,'form_id' => $form_id))){
            $this->coverRs(array('status' => 1,'info' => '请求成功'));exit;
        }else{
            $this->coverRs(array('status' => 0,'info' => '请求失败'));exit;
        }
    }

    /**
     * 获取帮助列表
     * @param $page_no int 页码
     * @param $page_size 页大小
     */
    public function questions(){
        $this->checkLogin();
        $http_request_mode = 'request';
        $page_no = I($http_request_mode . '.page_no', 1,'intval');
        $page_size = I($http_request_mode . '.page_size', 10,'intval');
        $result = LC('Api')->helps($page_no,$page_size);
        $this->coverRs(array('status' => 1,'data' => array('list' => $result['list'],'total' => $result['total'])));
    }

    /**
     * 获取小程序推荐
     * @param $page_no int 页码
     * @param $page_size 页大小
     */
    public function getRecommend(){
        $this->checkLogin();
        $http_request_mode = 'request';
        $page_no = I($http_request_mode . '.page_no', 1,'intval');
        $page_size = I($http_request_mode . '.page_size', 5,'intval');
        $result = LC('Api')->getRecommend($page_no,$page_size);
        $this->coverRs(array('status' => 1,'data' => array('list' => $result['list'],'total' => $result['total'])));
    }

    /**
     * 申请提现
     * @param $amount float 金额
     */
    public function withdraw(){
        $this->checkLogin();
        $http_request_mode = 'request';
        $amount = I($http_request_mode . '.amount', 0,'float');
        if(!$amount){
            $this->coverRs(array('status' => 0,'info' => '提现金额不能为0'));
        }
        $uInfo = D('Common/Member')->getByUid($this->uid);
        if($uInfo['amount'] < $amount){
            $this->coverRs(array('status' => 0,'info' => '余额不足'));exit;
        }
        //查询为处理的提现记录
        $totalAmount = LC('Api')->withdrawCount($this->uid,1);
        if(($uInfo['amount'] - $totalAmount) < $amount){
            $this->coverRs(array('status' => 0,'info' => '提现冻结' . $totalAmount . ' 余额不足,请等候处理...'));exit;
        }
        if(D('Common/Withdraw')->saveData(array('user_id' => $this->uid,'amount' => $amount))){
            $this->coverRs(array('status' => 1,'info' => '申请提现成功，请等候处理'));exit;
        }
        $this->coverRs(array('status' => 0,'info' => '申请提现失败'));
    }

    /**
     * 交易记录
     * @param $page_no int 页码
     * @param $page_size 页大小
     */
    public function amountRecord(){
        $this->checkLogin();
        $http_request_mode = 'request';
        $page_no = I($http_request_mode . '.page_no', 1,'intval');
        $page_size = I($http_request_mode . '.page_size', 5,'intval');
        $logic = new ApiLogic();
        list($list,$total) = $logic->getAmountList($this->uid,$page_no,$page_size);
        $list = $logic->amountListFormat($list);
        $this->coverRs(array('status' => 1,'data' => array('list' => $list,'total' => $total)));
    }

    /**
     * 上传图片
     */
    public function uploadImg(){
        $this->checkLogin();
        $res = D('Common/File','Logic')->upload($_FILES);
        if($res['status'] == 0){
            $this->coverRs(array('status' => 0,'info' => $res));exit;
        }else{//上传成功
            $path = '';
            foreach($res['data'] as $key => $val){
                $path = $val['url'];
            }
            $this->coverRs(array('status' => 1,'data' => $path));exit;
        }
    }

    /**
     * 申请成为赞助商
     * @param $content string 内容
     */
    public function sponsorApply(){
        $this->checkLogin();
        $http_request_mode = 'request';
        $content = I($http_request_mode . '.content', '');
        if(!$content){
            $this->coverRs(array('status' => 0,'info' => '申请内容不能为空'));exit;
        }
        $res = D('Common/Apply')->saveData(array('content' => $content));
        if($res){
            $this->coverRs(array('status' => 1,'info' => '您的申请我们已经收到咯~'));exit;
        }
        $this->coverRs(array('status' => 0,'info' => '申请失败'));
    }

    /**
     * 生成分享图片
     * @param $pid int 抽奖ID
     */
    public function makePic(){
        $this->checkLogin();
        $http_request_mode = 'request';
        $pid = I($http_request_mode . '.pid', '');
        if(!$pid){
            $this->coverRs(array('status' => 0,'info' => '参数错误'));exit;
        }
        $prizeInfo = D('Common/Prize')->getById($pid);
        $imgLogic = new ImgLogic();
        $weLogic = new WechatLogic();
        $qrPath = $weLogic->getSmallProgramQr($pid); //小程序码

        if($prizeInfo['share_pic']){
            $this->coverRs(array('status' => 1,'info' => '成功','data' => $prizeInfo['share_pic']));exit;
        }
        $result = $imgLogic->makeSharePic($prizeInfo,$qrPath);
        if($result['status'] == 1){
            //上传到云
            $path = $_SERVER['SERVER_NAME'] . '/' .$result['file'];
            $res = D('Common/File','Logic')->uploadRemote($path);
            if($res){
                //更新抽奖
                unlink($result['file']);
                $res = str_replace('http','https',$res);
                D('Common/Prize')->saveData(array('share_pic' => $res,'id' => $pid));
                $this->coverRs(array('status' => 1,'info' => '成功','data' => $res));
            }else{
                D('Common/Prize')->saveData(array('share_pic' => $res,'id' => $pid));
                $this->coverRs(array('status' => 1,'info' => '成功','data' => $result['file']));
            }
        }else{
            $this->coverRs(array('status' => 0,'info' => $result['msg']));
        }
    }

    /**
     * 生成中奖图片
     * @param $gid int
     */
    public function makeWinnerPic(){
        $this->checkLogin();
        $http_request_mode = 'request';
        $gid = I($http_request_mode . '.gid', '');
        $getModel = D('Common/GetIn');
        $info = $getModel->getById($gid);
        if($info['status'] != 1){
            $this->coverRs(array('status' => 0,'info' => '您本次并没有中奖'));exit;
        }elseif($info['win_share']){
            $this->coverRs(array('status' => 1,'info' => '成功','data' => $info['win_share']));exit;
        }else{
            $imgLogic = new ImgLogic();
            $result = $imgLogic->makeWinnerPic($this->uid);
            if(isset($result['status']) && $result['status'] == 1){
                //上传到云
                $path = $_SERVER['SERVER_NAME'] . '/' .$result['file'];
                $res = D('Common/File','Logic')->uploadRemote($path);
                if($res) {
                    $res = str_replace('http','https',$res);
                    $finalPath = $res;
                    $getModel->saveData(array('win_share' => $res),array('id' => $gid));
                }else{
                    $finalPath = $result['file'];
                    $getModel->saveData(array('win_share' => $result['file']),array('id' => $gid));
                }
                D('Common/Member')->saveData(array('local_url' => $result['logo'],'uid' => $this->uid));
                $this->coverRs(array('status' => 1,'info' => '成功','data' => $finalPath));
            }else{
                $this->coverRs(array('status' => 0,'info' => $result['msg']));
            }
        }
    }

    /**
     * 修改个人信息
     * @param $nickname string 昵称
     * @param $thumb string 头像
     * @param $sex int 1男 0女
     */
    public function editInfo(){
        $this->checkLogin();
        $http_request_mode = 'request';
        $data['nickname'] = I($http_request_mode . '.nickname', '');
        $data['thumb'] = I($http_request_mode . '.thumb', '');
        $data['sex'] = I($http_request_mode . '.sex', 1,'intval');
        $data['uid'] = $this->uid;
        D('Common/Member')->saveData($data);
        $this->coverRs(array('status' => 1,'info' => '修改成功'));exit;
    }

    public function test(){
        $token = '111';
        $_data = '222';
        $result = '333';
        $time = time();
        $model = new Model();
        $model->execute("insert into luck_sendtemp_log (`access_token`,`content`,`res`,`create_time`) values ('{$token}','{$_data}','{$result}',{$time})");
        exit;
        $formId = '1528103000318';
        $msg = array(
            '红包',
            '红包金额不足，开奖失败'
        );
        $wechatLogic = new WechatLogic();
        $result = $wechatLogic->sendTemplateMsg(164,12, $formId, $msg, 1);//发送模板消息
        $this->coverRs(array('status' => 1,'info' => $result));
    }
}