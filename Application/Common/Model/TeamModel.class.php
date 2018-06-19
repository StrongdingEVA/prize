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
class TeamModel extends CommonModel {
    protected $_auto = array(
        array('create_time', NOW_TIME),
    );

    private $_cacheKey = 'team_{id}';

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
            $rs = $this->save($data);
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

    public function getByid($id,$fileds = '*'){
        $data = array();
        if ($id) {
            $data = S($this->getCacheKey($id));
            if (!$data) {
                $data = $this->field($fileds)->where('id="' . $id . '"')->select();
                S($this->getCacheKey($id), $data, 60 * 60);
            }
        }
        return $data;
    }

    public function getTeamUser($pid,$user_id){
        $info = $this->where(array('p_id' => $pid,'user_id' => $user_id))->find();
        if(!$info){
            return array();
        }
        $wid = $info['sib_id'] ? $info['sib_id'] : $info['id'];
        return $this->join('LEFT JOIN __MEMBER__ ON __TEAM__.user_id=__MEMBER__.uid')
            ->field('luck_member.nickname,luck_member.thumb,luck_team.id,luck_team.g_id,luck_team.user_id')
            ->where("luck_team.sib_id={$wid} OR id={$wid}")
            ->select();
    }
}
