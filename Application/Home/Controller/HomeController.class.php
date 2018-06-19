<?php

// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Home\Controller;

use Think\Controller;

/**
 * 前台公共控制器
 * 为防止多分组Controller名称冲突，公共Controller名称统一使用分组名称
 */
class HomeController extends Controller
{

    protected function _initialize() {
        /* 读取站点配置 */
        $config = api('Config/lists');
        C($config); //添加配置

        if (!C('WEB_SITE_CLOSE')) {
            $this->error('站点已经关闭，请稍后访问~');
        }
    }

    /**
     *  空操作，用于输出404页面 
     */
    public function _empty() {
        header("HTTP/1.0 404 Not Found"); //使HTTP返回404状态码 
        $this->display("Home@Common:404");
        exit;
    }
    
    /**
     *  用于登录校验
     */
    public function _login() {
        $uid = is_login();
        if (!$uid) {
            if(IS_AJAX){
                $ret['status'] = 2;
                $ret['info'] = '请先登录';
                $this->ajaxReturn(array('status' => 2, 'info' => '请先登录！'));
            } else {
                $this->redirect('/login');
            }
        }
        return $uid;
    }
}
