<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/16 0016
 * Time: 15:53
 */
namespace Api\Logic;

class ImgLogic{
    public $result = array('status' => 0,'msg' => '成功');
    public $font = "static/fonts/simsun.ttc";
    public $smallQr = 'static/smallQr.jpg';
    public $yellow = 'static/yellow.png';
    public $defaultWin = 'static/defaultWin.jpg';

    //画分享图片
    public function makeSharePic($prizeInfo,$qrPath){
        $font = $this->font;
        if(!is_file($qrPath)){
            $this->result['msg'] = '小程序码不存在';
            return $this->result;
        }
        if(!is_file($this->font)){
            $this->result['msg'] = '字体不存在';
            return $this->result;
        }
//        $prizeInfo['thumb'] = 'Uploads/smallQr/small.jpg';
        $prizeInfo['thumb'] = $this->downLoadTolocal($prizeInfo['thumb']);
        //整张图片宽高
        $width = 700;
        $height = 850;
        //上下红边高
        $udBorder = 28;
        //左右红边宽
        $lrBorder = 15;
        //内部宽高
        $widthIn = $width - ($udBorder * 2);
        $heightIn = $height - ($lrBorder * 2);
        //商品图片宽
        $thumbWidth = 670;
        //商品图片高
        $thumbHeight = 312;
        //商品名称距离图片
        $shopTop = 40;
        //商品名称距离内部变宽
        $shopleft = 20;
        //商品名称字体
        $shopFontSize = $this->getSize(32);
        //商品名称颜色
        $shopColor = array(45,45,45);
        //开奖字体颜色
        $prizeColor = array(146,146,146);
        //开奖字体
        $prizeFontSize = $this->getSize(24);
        //线条距离开奖
        $lineTop = 41;
        //二维码大小
        $qrSize = 190;
        //二维码距离线条
        $qrTop = 41;
        //二维码距离长按文字
        $qrBootm = 30;
        $bottomFontSize =  $this->getSize(28);

        //把商品图片画进内部图片
        $backgroundIn = $this->createColora($widthIn,$heightIn,255,255,255);
        //商品图片资源
        $thumbInfo = $this->getImgHandleInfo($prizeInfo['thumb']);
        if($thumbInfo['w'] < $thumbWidth){
            $w_ = $thumbInfo['w'];
            $h_ = $thumbInfo['w'] / ($thumbWidth / $thumbHeight);
        }elseif($thumbInfo['h'] < $thumbHeight){
            $h_ = $thumbInfo['h'];
            $w_ = $thumbInfo['h'] / ($thumbHeight / $thumbWidth);
        }else{
            $w_ = $thumbInfo['w'];
            $h_ = $thumbInfo['w'] / ($thumbWidth / $thumbHeight);
        }
        $res1 = imagecopyresampled($backgroundIn, $thumbInfo['source'], 0, 0, 0, 0,$thumbWidth,$thumbHeight,$w_, $h_);
        if(!$res1){
            $this->result['msg'] = '失败1';
            return $this->result;
        }
        //imagecopymerge($backgroundIn, $qrInfo, 0, 0, 0, 0, 670, 312, 1);
        imagedestroy($thumbInfo['source']);
        if($prizeInfo['type'] == 1){
            $word = '奖品：' . $prizeInfo['name'];
        }else{
            $word = '奖品：红包 * ' . $prizeInfo['num'];
        }


//        $info = $this->getTtfInfo($word,$shopFontSize);
//        $limitWidth = $widthIn * 0.6;
//        $wordWidth = $info[2] - $info[0];
        $pen = $this->getPen($backgroundIn,$shopColor);
        $wordWidth = 1;
        $limitWidth = 2;
        if($wordWidth > $limitWidth){
            $a = ceil($wordWidth / $limitWidth);
            $len = mb_strlen($word);
            $arr = array();
            $start = 0;
            $limit = $len / $a;
            for ($i=0;$i<$a;$i++){
                if(($i + 1) == $a){
                    $arr[] = mb_substr($word,$start);
                }else{
                    $arr[] = mb_substr($word,$start,$limit);
                    $start += $limit;
                }
            }
            $yOffset = $thumbHeight + $shopTop + $shopFontSize;
            foreach($arr as $key => $val){
                $shopTtf = imagefttext($backgroundIn, $shopFontSize, 0,$shopleft,$yOffset, $pen, $font,$val);
                $yOffset = $shopTtf[1] + $shopFontSize;
            }
        }else{
            $shopTtf = imagefttext($backgroundIn, $shopFontSize, 0,$shopleft,$thumbHeight + $shopTop + $shopFontSize, $pen, $font,$word);
            $yOffset = $shopTtf[1];
        }


        $prizeOffset = $yOffset + ($prizeFontSize * 3);
        //写开奖时间
        if ($prizeInfo['open_type'] == 1){
            $word = date('m月d日 H:i',$prizeInfo['open_time']) . '自动开奖';
        }elseif($prizeInfo['open_type'] == 2){
            $word = '参与人数' . $prizeInfo['open_num'] . '人自动开奖';
        }elseif($prizeInfo['open_type'] == 3){
            $word = '手动开奖';
        }elseif($prizeInfo['open_type'] == 4){
            $word = '现场开奖';
        }

        $penPrize = $this->getPen($backgroundIn,$prizeColor);
        $prizeTtf = imagefttext($backgroundIn, $prizeFontSize, 0,$shopleft,$prizeOffset, $penPrize, $font,$word);

        $lineOffset = $prizeTtf[1] + $lineTop;
        //划线
        $res2 = imagerectangle($backgroundIn,0,$lineOffset,$widthIn,$lineOffset,$penPrize);
        if(!$res2){
            $this->result['msg'] = '失败2';
            return $this->result;
        }

        //画小程序码
        $qrOffset = $lineOffset + $qrTop;
        $qrInfo = $this->getImgHandleInfo($qrPath);
        $qrx = ($widthIn - $qrSize) / 2;
        $res3 = imagecopyresampled($backgroundIn, $qrInfo['source'], $qrx, $qrOffset, 0, 0,$qrSize,$qrSize,$qrInfo['w'], $qrInfo['h']);
        if(!$res3){
            $this->result['msg'] = '失败3';
            return $this->result;
        }
        imagedestroy($qrInfo['source']);

        //长按识别
        $boomOffset = $qrOffset + $qrSize + $qrBootm + $bottomFontSize;
        $word = '长按识别小程序，参与抽奖';
        imagefttext($backgroundIn, $bottomFontSize, 0,140,$boomOffset, $penPrize, $font,$word);

        //外框
        $background = $this->createColora($width,$height,241,67,67);
        $res4 = imagecopyresampled($background, $backgroundIn, $udBorder, $lrBorder, 0, 0,$widthIn,$heightIn,$widthIn, $heightIn);
        if(!$res4){
            $this->result['msg'] = '失败4';
            return $this->result;
        }
        $res5 = $this->imagejpgNow($background,'jpg');
        if(!$res5){
            $this->result['msg'] = '失败5';
            return $this->result;
        }
        $this->result['status'] = 1;
        $this->result['file'] = $res5;

        if(strpos($prizeInfo['thumb'],'img.xmyunyou.com')){
            unlink($prizeInfo['thumb']);
        }
        return  $this->result;
    }

    //画中奖图片
    public function makeWinnerPic($uid){
        if(!$uid){
            $this->result['msg'] = '参数错误';
        }
        $uInfo = D('Common/Member')->getById($uid);
        if(!$uInfo){
            $this->result['msg'] = '用户信息不存在';
            return $this->result;
        }
        if($uInfo['local_url']){
            $thumb = $uInfo['local_url'];
            if(!is_file($thumb)){
                $thumb = $this->downLoadTolocal($uInfo['thumb']);
            }
        }else{
            if(!$uInfo['thumb']){
                $thumb = 'static/defaultHead.jpg';
            }else{
                $thumb = $this->downLoadTolocal($uInfo['thumb']);
            }
        }

        $font = $this->font;
        if(!is_file($this->font)){
            $this->result['msg'] = '字体不存在';
            return $this->result;
        }

        $yellowPic = $this->yellow;
        if(!is_file($yellowPic)){
            $this->result['msg'] = 'yellow.png 不存在';
            return $this->result;
        }

        $smallQr = $this->smallQr;
        if(!is_file($smallQr)){
            $this->result['msg'] = '二维码不存在';
            return $this->result;
        }
        //整张图片宽高
        $width = 750;
        //头像宽高
        $headSize = 200;
        //头像距离顶部距离
        $headTop = 101;
        //黄色图片尺寸
        $yellowWidth = 454;
        $yellowHeight = 70;
        //黄色图片距离顶部距离
        $yellowTop = 270;
        //昵称字号
        $nicknameFontSize = $this->getSize(32);
        //昵称颜色
        $nicknameColor = array(42,42,42);

        $baseFile = $this->defaultWin;
        if(!is_file($baseFile)){
            $this->result['msg'] = 'defaultWin.jpg不存在';
            return $this->result;
        }
        $imageInfo = $this->getImgHandleInfo($baseFile);
        $image = $imageInfo['source'];
        //画头像 头像得是圆的
        $head = $this->cricularPic($thumb);
        if(!$head){
            $this->result['msg'] = '绘制头像失败';
            return $this->result;
        }
        $res1 = imagecopyresampled($image,$head['img'],275,$headTop,0,0,$headSize,$headSize,$head['w'],$head['h']);
        imagedestroy($head['img']);
        if(!$res1){
            $this->result['msg'] = '合并头像失败';
            return $this->result;
        }

        //画黄色图片
        $yellowPicInfo = $this->getImgHandleInfo($yellowPic);
        $yellowXoffset = ($width - $yellowPicInfo['w']) / 2;
        $res2 = imagecopyresampled($image,$yellowPicInfo['source'],$yellowXoffset,$yellowTop,0,0,$yellowWidth,$yellowHeight,$yellowPicInfo['w'],$yellowPicInfo['h']);
        imagedestroy($yellowPicInfo['img']);
        if(!$res2){
            $this->result['msg'] = '合并图片失败';
            return $this->result;
        }
        //写昵称
        $info = $this->getTtfInfo($uInfo['nickname'],$nicknameFontSize);
        $nicknameXoffset = ($width - ($info[2] - $info[0])) / 2;
        $nicknameYoffset = $yellowTop + $nicknameFontSize + (($yellowHeight - $nicknameFontSize - 14) / 2);
        $nicknamePen = $this->getPen($image,$nicknameColor);
        $res3 = imagettftext($image,$nicknameFontSize,0,$nicknameXoffset,$nicknameYoffset,$nicknamePen,$font,$uInfo['nickname']);
        if(!$res3){
            $this->result['msg'] = '写昵称失败';
            return $this->result;
        }
        $res4 = $this->imagejpgNow($image,'jpg');
        if(!$res4){
            $this->result['msg'] = '失败5';
            return $this->result;
        }
        $this->result['status'] = 1;
        $this->result['file'] = $res4;
        $this->result['logo'] = $thumb;
        return $this->result;
    }

    /**
     * @param $width
     * @param $height
     * @param int $c1
     * @param int $c2
     * @param int $c3
     * @return resource
     * 创建一张带颜色的画布
     */
    function createColora($width,$height,$c1 = 255,$c2 = 255,$c3 = 255){
        //创建一个空白的画布  往上面写字
        $image = imagecreatetruecolor($width, $height);
        $pen = imagecolorallocate($image, $c1,$c2,$c3);
        imagefill($image,0,0,$pen);
        return $image;
    }

    /**
     * 创建画笔
     * @param $image
     * @param $co1
     * @param $co2
     * @param $co3
     * @return int
     */
    public function getPen($image,array $colorInfo){
        return imagecolorallocate($image, $colorInfo[0],$colorInfo[1],$colorInfo[2]);//白色的画笔
    }

    public function getImgHandleInfo($fileName){
        if(!empty($fileName) && file_exists($fileName)){
            $ground_info = getimagesize($fileName);
            $ground_w = $ground_info[0];//取得背景图片的宽
            $ground_h = $ground_info[1];//取得背景图片的高
            switch($ground_info[2]){//取得背景图片的格式
                case 1:$ground_im = imagecreatefromgif($fileName);break;
                case 2:$ground_im = imagecreatefromjpeg($fileName);break;
                case 3:$ground_im = imagecreatefrompng($fileName);break;
                default:return false;
            }
            $arr["w"] = $ground_w;
            $arr["h"] = $ground_h;
            $arr["source"] = $ground_im;
            return $arr;
        }else{
            return false;
        }
    }

    public function getSize($key = 12){
        $fontPoints = array(
            12 => 8,
            13 => 8.5,
            14 => 9,
            15 => 10,
            16 => 11,
            17 => 12,
            18 => 13,
            19 => 14,
            20 => 14.5,
            21 => 15,
            22 => 16,
            23 => 17,
            24 => 17.5,
            25 => 18,
            26 => 19,
            27 => 20,
            28 => 21,
            29 => 22,
            30 => 23,
            31 => 23.5,
            32 => 24,
            33 => 25,
            34 => 26,
            35 => 27,
            36 => 28,
            37 => 28.5,
            38 => 29,
            39 => 30,
            40 => 31,
            41 => 32,
            42 => 32.5,
            43 => 33,
            44 => 34,
            45 => 34.5,
            46 => 35,
            47 => 36,
            48 => 37,
            49 => 38,
            50 => 39,
        );
        return $fontPoints[$key];
    }

    /**
     * @param $text
     * @return array
     * 获取生成文字所占的长宽
     */
    public function getTtfInfo($text,$font_size = 30){
        $font_size = $font_size ? $font_size : 30;
        $font = $this->font;
        $image = imagecreatetruecolor(1, 1);
        $pen = imagecolorallocate($image, 255,255,255);//白色的画笔
        $result = imagefttext($image, $font_size, 0,0, 0, $pen, $font,$text);
        imagedestroy($image);
        return $result;
    }

    //下载图片
    public function downLoadTolocal($file){
        if(strpos($file,'img.xmyunyou.com')){ //七牛云图片
            $ext = substr($file,strrpos($file,'.'));
        }elseif (strpos($file,'wx.qlogo.cn')){ //微信头像
            $ext = '.jpg';
        }else{
            return $file;
        }
        $date = date('Y-m-d',time());
        $basePath = 'Uploads/' . $date;
        if(!is_dir($basePath)){
            @mkdir($basePath);
        }

        $source = file_get_contents($file);
        $file = $basePath . '/' . uniqid() . $ext;
        $hand = fopen($file,'w');
        fwrite($hand,$source);
        fclose($hand);
        return $file;
    }

    //返回一个圆形图片资源和图片的宽高
    public function cricularPic($imgpath){
        if(!is_file($imgpath)){
            return false;
        }
        $src_img = null;
        $ground_info = $this->getImgHandleInfo($imgpath);
        $src_img = $ground_info['source'];
        $w   = $ground_info['w'];
        $h   = $ground_info['h'];
        $w   = min($w, $h);
        $h   = $w;
        $img = imagecreatetruecolor($w, $h);
        //这一句一定要有
        imagesavealpha($img, true);
        //拾取一个完全透明的颜色,最后一个参数127为全透明
        $bg = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefill($img, 0, 0, $bg);
        $r   = $w / 2; //圆半径
        //画个圆框
        $pen = $this->getPen($img,array(205,205,205));
//        imageantialias($img,true); //抗锯齿对透明图无效
        imagearc($img,$r,$r,$w,$w,0,360,$pen);

        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $rgbColor = imagecolorat($src_img, $x, $y);
                if (((($x - $r) * ($x - $r) + ($y - $r) * ($y - $r)) < ($r * $r))) {
                    imagesetpixel($img, $x, $y, $rgbColor);
                }
            }
        }
        return array('img' => $img,'w' => $ground_info['w'],'h' => $ground_info['h']);
    }

    public function imagejpgNow($image,$type = 'jpg'){
        if(!$image){
            return false;
        }
        $dir = 'Uploads/' . date('Y-m-d',time());
        if(!is_dir($dir)){
            @mkdir($dir);
        }
        $fileName = $dir . '/' . uniqid() . '.jpg';
        switch ($type){
            case 'jpg':
                $res = imagejpeg($image,$fileName);
                break;
            case 'jpeg':
                $res = imagejpeg($image,$fileName);
                break;
            case 'png':
                $res = imagepng($image,$fileName);
                break;
            case 'gif':
                $res = imagegif($image,$fileName);
                break;
            default :
                $res = imagejpeg($image,$fileName);
                break;
        }
        if($res){
            return $fileName;
        }
        return false;
    }
}