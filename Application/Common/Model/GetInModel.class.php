<?php

// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Common\Model;

use User\Api\UserApi;
use Common\Model\CommonModel;

/**
 * 文档基础模型
 */
class GetInModel extends CommonModel {
    protected $_auto = array(
        array('create_time', NOW_TIME),
    );

    private $_cacheKey = 'join_{id}';

    public function getCacheKey($id) {
        return str_replace('{id}', $id, $this->_cacheKey);
    }


    /**
     * 保存
     * @param $data
     * @param array $where
     * @return bool|mixed|null
     */
    public function saveData($_data, $where = array()) {
        $data = $this->create($_data);
        if (!$data) {
            return false;
        }
        if ($data['id']) {
            $where = array('id' => $data['id']);
            unset($data['id']);
            $rs = $this->where($where)->save($data);
            if (!$rs) {
                return false;
            }
            S($this->getCacheKey($where['id']), NULL);
        }else if($where){
            $rs = $this->where($where)->save($data);
            if (!$rs) {
                return false;
            }
            S($this->getCacheKey($data['id']), NULL);
        } else {
            $rs = $this->add($data);
            if (!$rs) {
                return false;
            }
        }
        return $rs;
    }

    /**
     * 删除
     */
    public function delById($id){
        if(!$id){
            return false;
        }
        if (is_array($id)) {
            $rs = $this->where(array('id' => array('in', $id)))->delete();
            foreach ($id as &$v) {
                S($this->getCacheKey($v), NULL);
            }
            unset($v);
        } else {
            $rs = $this->where(array('id' => $id))->delete();
            S($this->getCacheKey($id), NULL);
        }
        return $rs;
    }

    /**
     * 根据抽奖ID获取参与的用户
     */
    public function getByPid($pid,$fileds = '*'){
        $data = array();
        if ($pid) {S($this->getCacheKey($pid),null);
            $data = S($this->getCacheKey($pid));
            if (!$data) {
                $data = $this->field('*')->where('p_id="' . $pid . '"')->select();
                S($this->getCacheKey($pid), $data, 60 * 60);
            }
        }
        return $data;
    }

    public function getLucklyById($pid){
        $where = array(
            'luck_get_in.status' => 1,
            'luck_get_in.p_id' => $pid
        );
        return $this->join(array('__MEMBER__ ON __GET_IN__.user_id = __MEMBER__.uid'))
            ->field('luck_member.thumb,luck_member.nickname,luck_member.uid')
            ->where($where)
            ->select();
    }

    public function getAllowList($map, $return_total = FALSE){
        !isset($map['field']) && $map['field'] = 'id';
        $lists = $this->getList($map, $return_total);
        return $return_total ? array($lists[0], $lists[1]) : $lists;
    }

    public function isJoin($pid,$user_id){
        $map = array(
            'where' => array(
                'p_id' => $pid,
                'user_id' => $user_id
            )
        );
        $list = $this->getAllowList($map,false);
        return count($list) ? true : false;
    }

    public function isWinner($pid,$user_id){
        $where = array(
            'p_id' => $pid,
            'user_id' => $user_id,
            'status' => 1
        );
        $info = $this->where($where)->field('id')->find();
        return count($info) ? $info['id'] : false;
    }
}
