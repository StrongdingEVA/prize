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
class TransferModel extends CommonModel {
    protected $_auto = array(
        array('create_time', NOW_TIME),
    );


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
            unset($v);
        } else {
            $rs = $this->where(array('id' => $id))->delete();
        }
        return $rs;
    }

    public function getAllowList($map, $return_total = FALSE){
        !isset($map['field']) && $map['field'] = 'id';
        $lists = $this->getList($map, $return_total);
        return $return_total ? array($lists[0], $lists[1]) : $lists;
    }
}
