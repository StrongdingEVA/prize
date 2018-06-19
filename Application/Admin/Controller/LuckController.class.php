<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/4 0004
 * Time: 14:06
 */
namespace  Admin\Controller;
use Api\Logic\WechatLogic;

class LuckController extends AdminController {
    /**
     * 抽奖列表
     */
    public function prizeList(){
        $where = array();
        $type = I('type',0);
        $openType = I('open_type',0);
        $prizeType = I('prize_type',0);
        $status = I('status',0);
        if($type){
            $where ['type'] = $type;
        }
        if($openType){
            $where ['open_type'] = $openType;
        }
        if($status){
            $where ['status'] = $status;
        }

        if($prizeType == 1){
            $where ['user_id'] = 0;
        }elseif($prizeType == 2){
            $where ['user_id'] = array('neq',0);
        }

        $list = $this->lists('Prize', $where, 'id desc');

        foreach($list as $key => $val){
            $lucklyInfo = D('Common/GetIn')->getLucklyById($val['id']);
            $list[$key]['luckly'] = $lucklyInfo;
        }

        // 记录当前列表页的cookie
        Cookie('__forward__', $_SERVER['REQUEST_URI']);

        $this->assign("list",$list);
        $this->assign("type",$type);
        $this->assign("prize_type",$prizeType);
        $this->assign("open_type",$openType);
        $this->assign("status",$status);
        $this->display('prize_list');
    }

    /**
     * 编辑抽奖
     */
    public function prizeEdit(){
        $model = D('Common/Prize');
        $id = I('id',0);
        if(IS_POST){
            $rs = $model->saveData();
            if ($rs) {
                $this->success('操作成功', Cookie('__forward__'));
            } else {
                $this->error($model->getError() ? : '操作失败');
            }
        }else{
            $prize = $model->getById($id);
            $this->assign('prize',$prize);
        }
        $this->display('prize_edit');
    }

    /**
     * 删除
     */
    public function prizeDel(){
        $id = I('id',0);
        if (empty($id)) {
            $this->error('请选择要操作的数据!');
        }
        $rs = D('Common/Prize')->delById($id);
        if ($rs) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败！');
        }
    }

    /**
     * 查看参与人数
     */
    public function prizeShowJoin(){
        $id = I('id',0);
        $page = I('p',1);
        $listRows = 20;
        if(!$id){
            $this->error('不存在此抽奖活动');
        }
        $joinList = D('Common/GetIn')->getByPid($id,'luck_get_in.id');
        $total = count($joinList);

        $temp = array_slice($joinList,($page - 1) * $listRows,$listRows);
        $str = '';
        foreach ($temp as $key => $val){
            $str .= $val['id'] . ',';
        }
        $str = rtrim($str,',');

        $info = array();
        if($str){
            $info = M("GetIn")->join(array('__MEMBER__ ON __GET_IN__.user_id = __MEMBER__.uid'))
                ->where("luck_get_in.id in ($str)")
                ->field('luck_member.*,luck_get_in.status as status_')
                ->order('luck_get_in.id desc')
                ->select();
        }


        //分页
        $REQUEST = (array) I('request.');
        $page = new \Think\Page($total, $listRows, $REQUEST);
        if ($total > $listRows) {
            $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $p = $page->show();


        // 记录当前列表页的cookie
        Cookie('__forward__', $_SERVER['REQUEST_URI']);

        $this->assign("list",$info);
        $this->assign('_page', $p ? $p : '');
        $this->assign('_total', $total);
        $this->display('prize_show_join');
    }

    /**
     * 赞助商申请
     */
    public function sponsorApply(){
        $where = array();
        $status = I('status',0);
        if($status){
            $where ['status'] = $status;
        }

        $list = $this->lists('Apply', $where, 'id desc');

        // 记录当前列表页的cookie
        Cookie('__forward__', $_SERVER['REQUEST_URI']);

        $this->assign("list",$list);
        $this->assign("status",$status);
        $this->display('sponsor_apply');
    }

    /**
     * 改变状态
     * @param $id
     * @param int $value
     */
    public function changeSponApply() {
        $id = I('id',0);
        $value = I('value',1);
        if(!$id){
            $this->error('参数错误');
        }
        $rs = D('Common/Apply')->changeById($id, 'status', $value);
        if ($rs) {
            $this->redirect('sponsorApply');
        } else {
            $this->error('操作失败！');
        }
    }

    /**
     * 编辑申请
     */
    public function sponsorEdit(){
        $id = I('id',0);
        if(!$id){
            $this->error('参数错误');
        }
        $model = D('Common/Apply');
        if(IS_POST){
            $rs = $model->saveData();
            if ($rs) {
                $this->success('操作成功', Cookie('__forward__'));
            } else {
                $this->error($model->getError() ? : '操作失败');
            }
        }else{
            $info = $model->getById($id);
            $this->assign('info',$info);
        }
        $this->display('sponsor_edit');
    }

    /**
     * 删除
     */
    public function sponsorDel(){
        $id = array_unique((array) I('id', 0));
        if (empty($id)) {
            $this->error('请选择要操作的数据!');
        }
        $rs = D('Common/Apply')->delById($id);
        if ($rs) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败！');
        }
    }

    /**
     * 提现申请
     */
    public function withdraw(){
        $where = array();
        $status = I('status',0);
        if($status){
            $where ['status'] = $status;
        }

        $list = $this->lists('Withdraw', $where, 'id desc');
        $userModel = D('Common/Member');
        foreach($list as $key => $val){
            $list[$key]['nickname'] = $userModel->getById($val['user_id'])['nickname'];
        }
        // 记录当前列表页的cookie
        Cookie('__forward__', $_SERVER['REQUEST_URI']);

        $this->assign("list",$list);
        $this->assign("status",$status);
        $this->display('withdraw');
    }

    /**
     * 修改提现状态
     */
    public function changeWithdraw(){
        $id = I('id',0);
        $value = I('value',1);
        if(!$id){
            $this->error('参数错误');
        }
        $model = D('Common/Withdraw');

        $withInfo = $model->getById($id);
        if(!$withInfo){
            $this->error('不存在该提现记录');
        }
        if($withInfo['status'] != 1){
            $this->error('本次提现已被处理');
        }
        if($withInfo['amount'] <= 0){
            $this->error('提现金额不能小于0');
        }


        //提现成功 扣除用户余额  拒绝提现不做动作
        if($value == 2){
            $logic = new WechatLogic();
            $result = $logic->withdraw($withInfo['user_id'],$withInfo['amount'],$id);
            if($result['status'] == 1){
                $this->redirect('withdraw');
            }else{
                $this->error($result['info']);
            }
        }else{
            if($model->changeById($id, 'status', $value)){
                $this->redirect('withdraw');
            }
        }
        $this->error('操作失败！');
    }

    /**
     * 删除提现申请
     */
    public function withdrawDel(){
        $id = array_unique((array) I('id', 0));
        if (empty($id)) {
            $this->error('请选择要操作的数据!');
        }
        $rs = D('Common/Withdraw')->delById($id);
        if ($rs) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败！');
        }
    }

    /**
     * 推荐小程序
     */
    public function recommend(){
        $where = array();
        $status = I('status',0);
        $name = I('name','');
        if($status){
            $where ['status'] = $status;
        }
        if($name){
            $where['name'] = array('LIKE',"%{$name}%");
        }

        $list = $this->lists('Recommend', $where, 'id desc');

        // 记录当前列表页的cookie
        Cookie('__forward__', $_SERVER['REQUEST_URI']);

        $this->assign("list",$list);
        $this->assign("status",$status);
        $this->assign("name",$name);
        $this->display('recommend');
    }

    /**
     * 修改状态
     */
    public function changeRecommend(){
        $id = I('id',0);
        $value = I('value',1);
        if(!$id){
            $this->error('参数错误');
        }
        $rs = D('Common/Recommend')->changeById($id, 'status', $value);
        if ($rs) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败！');
        }
    }

    /**
     * 删除
     */
    public function recommendDel(){
        $id = array_unique((array) I('id', 0));
        if (empty($id)) {
            $this->error('请选择要操作的数据!');
        }
        $rs = D('Common/Recommend')->delById($id);
        if ($rs) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败！');
        }
    }

    /**
     * 编辑
     */
    public function recommendEdit(){
        $id = I('id',0);
        $model = D('Common/Recommend');
        if(IS_POST){
            $rs = $model->saveData();
            if ($rs) {
                $this->success('操作成功', Cookie('__forward__'));
            } else {
                $this->error($model->getError() ? : '操作失败');
            }
        }else{
            $info = $model->getById($id);
            $this->assign('info',$info);
        }
        $this->display('recommend_edit');
    }

    /**
     * 订单列表
     */
    public function orderList(){
        $where = array();
        $status = I('status',0);
        $type = I('type',0);
        $orderNo = I('order_no','');
        if($status){
            $where ['status'] = $status;
        }
        if($orderNo){
            $where['order_no'] = array('LIKE',"{$orderNo}%");
        }
        if($type){
            $where['type'] = $type;
        }

        $list = $this->lists('Order', $where, 'id desc');

        // 记录当前列表页的cookie
        Cookie('__forward__', $_SERVER['REQUEST_URI']);

        $this->assign("list",$list);
        $this->assign("status",$status);
        $this->assign("type",$type);
        $this->assign("order_no",$orderNo);
        $this->display('order_list');
    }

    public function orderDel(){
        $id = array_unique((array) I('id', 0));
        if (empty($id)) {
            $this->error('请选择要操作的数据!');
        }
        $rs = D('Common/Order')->delById($id);
        if ($rs) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败！');
        }
    }
}