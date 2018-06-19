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
class ApplyModel extends CommonModel {
    protected $_auto = array(
        array('create_time', NOW_TIME),
    );

    private $_cacheKey = 'apply_{id}';

    public function getCacheKey($id) {
        return str_replace('{id}', $id, $this->_cacheKey);
    }

    /**
     * 根据ID获取申请信息
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

    /**
     * 修改状态
     * @param $id
     * @param string $field
     * @param int $value
     * @return bool
     */
    public function changeById($id, $field = 'status', $value = 2) {
        if (!$id) {
            return false;
        }
        $rs = $this->where(array('id' => $id))->setField($field, $value);
        if (!$rs) {
            return false;
        }
        S($this->getCacheKey($id), NULL);
        return $rs;
    }
}
