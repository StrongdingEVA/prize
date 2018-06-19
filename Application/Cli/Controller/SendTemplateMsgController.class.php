<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/8 0008
 * Time: 11:14
 */
namespace Cli\Controller;
use Think\Controller;

class SendTemplateMsgController extends Controller {

    public $accessToken;

    public $appid;

    public $appsecret;

    public $template_id;

    public $key_token;

    public function __construct(array $opt){
        $wechatConfig = C('WXXCX_CONFIG');
        $this->appid = $opt['appid'] ? $opt['appid'] : $wechatConfig['appid'];
        $this->appsecret = $opt['appSecret'] ? $opt['appSecret'] : $wechatConfig['secret'];
    }

    public function getTemplateId($key){
         switch ($key){
             case 1:
                 return '1111111';
             break;
             default:
                 return '1111111';
             break;
         }
    }


    //获取acces_token

    public function getToken(){
        $key = $this->key_token;

        $data = Yii::$app->redis->get($key); //数据缓存，定为60s

        if (!empty($data)) {

            $this->accessToken = $data;

            return $data;

        } else {

            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->appid}&secret={$this->appsecret}";

            $result = $this->wxHttpsRequest($url);



            $json = json_decode($result, true);

            if (!empty($json['access_token'])) {

                $this->accessToken = $json['access_token'];

                Yii::$app->redis->setex($key, $json['expires_in'] - 300, $this->accessToken); //单位秒数据缓存(提前300秒)

                return $this->accessToken;

            }else{

                throw new Exception('错误'.$json['errcode']);

            }

        }

        return '';

    }



    /*

     * 发送模板消息

     */

    public function sendTemplateMsg($openid,$form_id,$data){
        $d['touser']=$openid;
        $d['template_id']=$this->template_id;
        $d["page"]="pages/index/index";
        $d["form_id"]=$form_id;
        $d['data']=$data;
        $d['emphasis_keyword']='';
        $_data=json_encode($d,JSON_UNESCAPED_UNICODE);
        //return ['errcode'=>0];
        $json=null;

        for($index=0;$index<=1;$index++) {///两次机会，如accessToken错误时再给一次机会
            $this->getToken();
            $posturl="https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=".$this->accessToken;
            $result = $this->wxHttpsRequest($posturl, $_data);
            $json = json_decode($result, true);

            if(empty($json)){
                $json=null;
                $this->clearToken();
                if($index==1){
                    throw new Exception('微信授权失败，请检查 APP ID 和 APP Secret');
                    return;
                }
                continue;
            }

            if(!empty($json['errcode']) && $json['errcode']=='40001'){
                $this->clearToken();
                $json=null;
                continue;
            }
            break;
        }
        return $json;
    }



    //微信提交API方法，返回微信指定JSON

    public function wxHttpsRequest($url, $data = null){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }



    /**

     * 清除缓存

     */

    public function clearToken(){

        Yii::$app->redis->del($this->key_token);

    }



    //格式化消息data
    public function formatMsgData($info){
        $data = array(
            'keyword1' => array('value' => $info['start_time']),//活动开始时间
            'keyword2' => array('value' => $info['name']),//活动名
            'keyword3' => array('value' => $info['description']),//活动说明....
        );
        return $data;
    }





}