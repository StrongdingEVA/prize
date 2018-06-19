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
class PrizeModel extends CommonModel {
    protected $_validate = array(
        array('type',array(1,2),'抽奖类型错误',self::MUST_VALIDATE,'in',self::MODEL_INSERT),
        array('num',array(1,100),'奖品数量最多100个',self::MUST_VALIDATE,'between',self::MODEL_INSERT),
        array('open_type',array(1,2,3,4),'奖品数量最多100个',self::MUST_VALIDATE,'in',self::MODEL_INSERT),
    );

    protected $_auto = array(
        array('create_time', NOW_TIME,),
        array('open_time','transTime',self::MODEL_INSERT,'callback'),
    );

    private $_cacheKey = 'sponsor_info_{id}';

    public function getCacheKey($id) {
        return str_replace('{id}', $id, $this->_cacheKey);
    }

    /**
     * 根据ID获取抽奖
     * @param $id
     * @param string $fields
     * @return array|mixed
     */
    public function getById($id,$fields = '*'){
        $data = array();
        if ($id) {S($this->getCacheKey($id),null);
            $data = S($this->getCacheKey($id));
            if (!$data) {
                $data = $this->field($fields)->where('id="' . $id . '"')->find();
                S($this->getCacheKey($id), $data, 60 * 60);
            }
        }
        return $data;
    }

    public function getByOid($oid,$fields = '*'){
        $data = array();
        if ($oid) {
            $data = $this->field($fields)->where('oid="' . $oid . '"')->find();
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

    public function getListRows($map, $return_total = TRUE) {
        !isset($map['field']) && $map['field'] = 'id';
        $lists = $this->getList($map, $return_total);
        return $return_total ? array($lists[0], $lists[1]) : $lists;
    }

    public function transTime(){
        $postTime = I('open_time');
        if($postTime){
            preg_match('/(.*)\s(.*)/',$postTime,$matches);
            if(!$matches[1] || !$matches[2]){
                return 0;
            }

            $timeStr = $matches[1] . str_replace('-',':',$matches[2]);
            return strtotime($timeStr);
        }
        return 0;
    }
}
