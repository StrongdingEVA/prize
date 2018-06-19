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
class AddressModel extends CommonModel {

    protected $_validate = array(
        array('address','require','省市区不能为空',self::MODEL_UPDATE),
    );

    protected $_auto = array(
        array('create_time', NOW_TIME,),
    );

    private $_cacheKey = 'address_{id}';

    public function getCacheKey($id) {
        return str_replace('{id}', $id, $this->_cacheKey);
    }

    /**
     * 根据ID获取地址详情
     * @param $id
     * @param string $fields
     * @return array|mixed
     */
    public function getById($id,$fields = '*'){
        $data = array();
        if ($id) {
            $data = S($this->getCacheKey($id));
            if (!$data) {
                $data = $this->field($fields)->where('id="' . $id . '"')->find();
                S($this->getCacheKey($id), $data, 60 * 60);
            }
        }
        return $data;
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
        if ($where) {
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

    public function getListRows($map, $return_total = TRUE) {
        !isset($map['field']) && $map['field'] = 'id';
        $lists = $this->getList($map, $return_total);
        return $return_total ? array($lists[0], $lists[1]) : $lists;
    }

    public function getByPidUid($uid,$pid){
        if(!$pid || !$uid){
            return false;
        }
        return $this->where(array('user_id' => $uid,'p_id' => $pid))->field('province,city,area,address,is_set,mobile,accept_name')->find();
    }
}
