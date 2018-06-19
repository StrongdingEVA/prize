<?php if (!defined('THINK_PATH')) exit();?><!doctype html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="referrer" content="never">
        <title>管理后台</title>
        <link href="/Public/favicon.ico" type="image/x-icon" rel="shortcut icon">
        <link rel="stylesheet" type="text/css" href="/Public/Admin/css/base.css" media="all">
        <link rel="stylesheet" type="text/css" href="/Public/Admin/css/common.css" media="all">
        <link rel="stylesheet" type="text/css" href="/Public/Admin/css/module.css">
        <link rel="stylesheet" type="text/css" href="/Public/Admin/css/style.css" media="all">
        <link rel="stylesheet" type="text/css" href="/Public/Admin/css/<?php echo (C("COLOR_STYLE")); ?>.css" media="all">
        <!--[if lt IE 9]>
       <script type="text/javascript" src="/Public/static/jquery-1.10.2.min.js"></script>
       <![endif]--><!--[if gte IE 9]><!-->
        <script type="text/javascript" src="/Public/static/jquery-2.0.3.min.js"></script>
        <script type="text/javascript" src="/Public/Admin/js/jquery.mousewheel.js"></script>
        <script type="text/javascript" src="/Public/static/layer/layer.js"></script>
        <script type="text/javascript" src="/Public/Admin/js/public.js"></script>
        <!--<![endif]-->
    
</head>
<body>
    <!-- 头部 -->
    <div class="header">
        <!-- Logo -->
        <span class="logo"></span>
        <!-- /Logo -->

        <!-- 主导航 -->
        <ul class="main-nav">
            <?php if(is_array($__MENU__["main"])): $i = 0; $__LIST__ = $__MENU__["main"];if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$menu): $mod = ($i % 2 );++$i;?><li class="<?php echo ((isset($menu["class"]) && ($menu["class"] !== ""))?($menu["class"]):''); ?>"><a href="<?php echo (U($menu["url"])); ?>"><?php echo ($menu["title"]); ?></a></li><?php endforeach; endif; else: echo "" ;endif; ?>
        </ul>
        <!-- /主导航 -->

        <!-- 用户栏 -->
        <div class="user-bar">
            <a href="javascript:;" class="user-entrance"><i class="icon-user"></i></a>
            <ul class="nav-list user-menu hidden">
                <li class="manager">你好，<em title="<?php echo session('user_auth.username');?>"><?php echo session('user_auth.username');?></em></li>
                <li><a href="<?php echo U('User/updatePassword');?>">修改密码</a></li>
                <li><a href="<?php echo U('User/updateNickname');?>">修改昵称</a></li>
                <li><a href="<?php echo U('Public/logout');?>">退出</a></li>
            </ul>
        </div>
    </div>
    <!-- /头部 -->

    <!-- 边栏 -->
    <div class="sidebar">
        <!-- 子导航 -->
        
            <div id="subnav" class="subnav">
                <?php if(!empty($_extra_menu)): ?>
                    <?php echo extra_menu($_extra_menu,$__MENU__); endif; ?>
                <?php if(is_array($__MENU__["child"])): $i = 0; $__LIST__ = $__MENU__["child"];if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$sub_menu): $mod = ($i % 2 );++$i;?><!-- 子导航 -->
                    <?php if(!empty($sub_menu)): if(!empty($key)): ?><h3><i class="icon icon-unfold"></i><?php echo ($key); ?></h3><?php endif; ?>
                        <ul class="side-sub-menu">
                            <?php if(is_array($sub_menu)): $i = 0; $__LIST__ = $sub_menu;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$menu): $mod = ($i % 2 );++$i;?><li>
                                    <a class="item" href="<?php echo (U($menu["url"])); ?>"><?php echo ($menu["title"]); ?></a>
                                </li><?php endforeach; endif; else: echo "" ;endif; ?>
                        </ul><?php endif; ?>
                    <!-- /子导航 --><?php endforeach; endif; else: echo "" ;endif; ?>
            </div>
        
        <!-- /子导航 -->
    </div>
    <!-- /边栏 -->

    <!-- 内容区 -->
    <div id="main-content">
        <div id="top-alert" class="fixed alert alert-error" style="display: none;">
            <button class="close fixed" style="margin-top: 4px;">&times;</button>
            <div class="alert-content">这是内容</div>
        </div>
        <div id="main" class="main">
            
                <!-- nav -->
                <?php if(!empty($_show_nav)): ?><div class="breadcrumb">
                        <span>您的位置:</span>
                        <?php $i = '1'; ?>
                        <?php if(is_array($_nav)): foreach($_nav as $k=>$v): if($i == count($_nav)): ?><span><?php echo ($v); ?></span>
                                <?php else: ?>
                                <span><a href="<?php echo ($k); ?>"><?php echo ($v); ?></a>&gt;</span><?php endif; ?>
                            <?php $i = $i+1; endforeach; endif; ?>
                    </div><?php endif; ?>
                <!-- nav -->
            

            
    <script src="/Public/Admin/js/jquery.datetimepicker.full.min.js"></script>
    <link rel="stylesheet" href="/Public/Admin/css/jquery.datetimepicker.css">
    <div class="main-title">
        <h2><?php echo ($info['id']?'编辑':'新增'); ?>抽奖</h2>
    </div>
    <form action="<?php echo U();?>" method="post" class="form-horizontal">
        <input type="hidden" name="user_id" value="0">
        <div class="form-item">
            <label class="item-label">奖品名称<span class="check-tips"></span></label>
            <div class="controls">
                <input type="text" class="text input-large" name="name" value="<?php echo ($prize["name"]); ?>">
            </div>
        </div>

        <div class="form-item">
            <label class="item-label"> 奖品图片<span class="check-tips"></span></label>
            <input type="hidden" name="thumb" id="pics" value="<?php echo ($prize["thumb"]); ?>">
            <img class="modelspic" src="<?php echo ($prize["thumb"]); ?>" alt="">
            <div class="controls" id="fileuploader" style="width: 100%;">
            </div>
        </div>


        <div class="form-item">
            <label class="item-label">奖品数量<span class="check-tips"></span></label>
            <div class="controls">
                <div class="controls">
                    <input type="text" class="text input-large" name="num" value="<?php echo ($prize["num"]); ?>">
                </div>
            </div>
        </div>

        <div class="form-item">
            <label class="item-label">开奖方式<span class="check-tips"></span></label>
            <div class="controls">
                <select name="open_type" id="open_type">
                    <option value="1" <?php if(($prize["open_type"]) == "1"): ?>selected="selected"<?php endif; ?>>按时间自动开奖</option>
                    <option value="2" <?php if(($prize["open_type"]) == "2"): ?>selected="selected"<?php endif; ?>>按人数自动开奖</option>
                    <option value="3" <?php if(($prize["open_type"]) == "3"): ?>selected="selected"<?php endif; ?>>手动开奖</option>
                    <option value="4" <?php if(($prize["open_type"]) == "4"): ?>selected="selected"<?php endif; ?>>现场开奖</option>
                </select>
            </div>
        </div>

        <div class="form-item" id="open_time">
            <label class="item-label">开奖时间<span class="check-tips"></span></label>
            <div class="controls">
                <div class="controls">
                    <input type="text" class="text input-large" name="open_time" id="timeOpen" value="<?php echo ($prize["open_time"]); ?>">
                </div>
            </div>
        </div>

        <div class="form-item" id="open_num" style="display: none">
            <label class="item-label">开奖人数<span class="check-tips"></span></label>
            <div class="controls">
                <div class="controls">
                    <input type="text" class="text input-large" name="open_num" value="<?php echo ($prize["open_num"]); ?>">
                </div>
            </div>
        </div>

        <div class="form-item">
            <label class="item-label">赞助商<span class="check-tips"></span></label>
            <div class="controls">
                <div class="controls">
                    <input type="text" class="text input-large" name="sponsor" value="<?php echo ($prize["sponsor"]); ?>">
                </div>
            </div>
        </div>

        <div class="form-item">
            <label class="item-label">赞助商描述<span class="check-tips"></span></label>
            <div class="controls">
                <div class="controls">
                    <textarea name="sponsor_des" cols="60" rows="5"><?php echo ($prize["sponsor_des"]); ?></textarea>
                </div>
            </div>
        </div>

        <div class="form-item">
            <label class="item-label">赞助商短语<span class="check-tips"></span></label>
            <div class="controls">
                <div class="controls">
                    <textarea name="sponsor_word" cols="60" rows="5"><?php echo ($prize["sponsor_word"]); ?></textarea>
                </div>
            </div>
        </div>

        <div class="form-item">
            <label class="item-label">商品描述<span class="check-tips"></span></label>
            <div class="controls">
                <div class="controls">
                    <textarea name="prize_des" cols="60" rows="5"><?php echo ($prize["prize_des"]); ?></textarea>
                </div>
            </div>
        </div>

        <div class="form-item">
            <label class="item-label">商品短语<span class="check-tips"></span></label>
            <div class="controls">
                <div class="controls">
                    <textarea name="prize_word" cols="60" rows="5"><?php echo ($prize["prize_word"]); ?></textarea>
                </div>
            </div>
        </div>

        <div class="form-item">
            <label class="item-label">小程序跳转路径<span class="check-tips"></span></label>
            <div class="controls">
                <div class="controls">
                    <input type="text" class="text input-large" name="sponsor_url" value="<?php echo ($prize["sponsor_url"]); ?>">
                </div>
            </div>
        </div>

        <div class="form-item">
            <label class="item-label">排序<span class="check-tips"></span></label>
            <div class="controls">
                <div class="controls">
                    <input type="text" class="text input-large" name="sort" value="<?php echo ($prize["sort"]); ?>">
                </div>
            </div>
        </div>

        <?php if($prize): ?><div class="form-item">
                <label class="item-label">状态<span class="check-tips"></span></label>
                <div class="controls">
                    <?php if($prize["status"] == 0): ?><span>未开奖</span>
                        <?php elseif($prize["status"] == 1): ?>
                        <span>开奖成功</span>
                        <?php else: ?>
                        <span>过期开奖失败</span><?php endif; ?>
                </div>
            </div><?php endif; ?>

        <div class="form-item">
            <input type="hidden" name="type" value="1">
            <input type="hidden" name="id" value="<?php echo ((isset($prize["id"]) && ($prize["id"] !== ""))?($prize["id"]):''); ?>">
            <button class="btn submit-btn ajax-post" id="submit" type="submit" target-form="form-horizontal">确 定</button>
            <button class="btn btn-return" onclick="javascript:history.back(-1);return false;">返 回</button>
        </div>
    </form>

        </div>
        <div class="cont-ft">
            <div class="copyright">
                <div class="fl">管理平台</div>
                <div class="fr"></div>
            </div>
        </div>
    </div>
    <!-- /内容区 -->
    <script type="text/javascript">
        var URL = '/index.php?s=/Admin/Luck';
        var SELF = '<?php echo US();?>';
        (function () {
             //指定当前组模块URL地址 

            var ThinkPHP = window.Think = {
                "ROOT": "", //当前网站地址
                "APP": "/index.php?s=", //当前项目地址
                "PUBLIC": "/Public", //项目公共目录地址
                "DEEP": "<?php echo C('URL_PATHINFO_DEPR');?>", //PATHINFO分割符
                "MODEL": ["<?php echo C('URL_MODEL');?>", "<?php echo C('URL_CASE_INSENSITIVE');?>", "<?php echo C('URL_HTML_SUFFIX');?>"],
                "VAR": ["<?php echo C('VAR_MODULE');?>", "<?php echo C('VAR_CONTROLLER');?>", "<?php echo C('VAR_ACTION');?>"]
            }
        })();
    </script>
    <script type="text/javascript" src="/Public/static/think.js"></script>
    <script type="text/javascript" src="/Public/Admin/js/common.js"></script>
    <script type="text/javascript">
        +function () {
            var $window = $(window), $subnav = $("#subnav"), url;
            $window.resize(function () {
                $("#main").css("min-height", $window.height() - 130);
            }).resize();

            /* 左边菜单高亮 */
            url = window.location.pathname + window.location.search;
            url = url.replace(/(\/(p)\/\d+)|(&p=\d+)|(\/(id)\/\d+)|(&id=\d+)|(\/(group)\/\d+)|(&group=\d+)/, "");
            $subnav.find("a[href='" + url + "']").parent().addClass("current");

            /* 左边菜单显示收起 */
            $("#subnav").on("click", "h3", function () {
                var $this = $(this);
                $this.find(".icon").toggleClass("icon-fold");
                $this.next().slideToggle("fast").siblings(".side-sub-menu:visible").
                        prev("h3").find("i").addClass("icon-fold").end().end().hide();
            });

            $("#subnav h3 a").click(function (e) {
                e.stopPropagation()
            });

            /* 头部管理员菜单 */
            $(".user-bar").mouseenter(function () {
                var userMenu = $(this).children(".user-menu ");
                userMenu.removeClass("hidden");
                clearTimeout(userMenu.data("timeout"));
            }).mouseleave(function () {
                var userMenu = $(this).children(".user-menu");
                userMenu.data("timeout") && clearTimeout(userMenu.data("timeout"));
                userMenu.data("timeout", setTimeout(function () {
                    userMenu.addClass("hidden")
                }, 100));
            });

            /* 表单获取焦点变色 */
            $("form").on("focus", "input", function () {
                $(this).addClass('focus');
            }).on("blur", "input", function () {
                $(this).removeClass('focus');
            });
            $("form").on("focus", "textarea", function () {
                $(this).closest('label').addClass('focus');
            }).on("blur", "textarea", function () {
                $(this).closest('label').removeClass('focus');
            });

            // 导航栏超出窗口高度后的模拟滚动条
            var sHeight = $(".sidebar").height();
            var subHeight = $(".subnav").height();
            var diff = subHeight - sHeight; //250
            var sub = $(".subnav");
            if (diff > 0) {
                $(window).mousewheel(function (event, delta) {
                    if (delta > 0) {
                        if (parseInt(sub.css('marginTop')) > -10) {
                            sub.css('marginTop', '0px');
                        } else {
                            sub.css('marginTop', '+=' + 10);
                        }
                    } else {
                        if (parseInt(sub.css('marginTop')) < '-' + (diff - 10)) {
                            sub.css('marginTop', '-' + (diff - 10));
                        } else {
                            sub.css('marginTop', '-=' + 10);
                        }
                    }
                });
            }
        }();
    </script>

    <script type="text/javascript" src="/Public/Admin/js/jquery.uploadfile.min.js"></script>
    <script type="text/javascript">
        //导航高亮
        highlight_subnav("<?php echo U('prizeList');?>");
        $(function(){
            $("#fileuploader").uploadFile({
                url:"Admin/File/upload",
                dragDropStr:'点击选择图片',
                uploadStr:'点击选择图片',
                allowedTypes:'jpg,png,jpeg,gif',
                extErrorStr:'只能上传以下格式的文件:',
                fileName:"myfile",
                maxFileCount:1,
                onSuccess:function(files, response, xhr, pd){
                    if(parseInt(response.status) == 1){ //上传成功
                        var file = response.data.myfile.thumb;
                        var pics = $('#pics').val();
                        pics += pics ? ',' + file : file;
                        $('#pics').val(pics);
                        $('#fileuploader').before('<img class="modelspic" src="'+ file +'" />');
                    }else{
                        alert('图片上传失败');
                    }
                }
            });

            $('#open_type').change(function(){
                if($(this).val() == 1){
                    $('#open_time').show();
                    $('#open_num').hide();
                }else if($(this).val() == 2){
                    $('#open_time').hide();
                    $('#open_num').show();
                }else{
                    $('#open_time').hide();
                    $('#open_num').hide();
                }
            })
            var allowTimes = getAllowTimes();
            $.datetimepicker.setLocale('en');
            $('#timeOpen').datetimepicker({
                yearStart:'2018',
                yearEnd:'2018',
                dayOfWeekStart : 1,
                lang:'ch',
                allowTimes:allowTimes,
                minDate:'NOW',
//                onChangeDateTime:function(date,ele,time){
//                    var time_ = parseInt(time.timeStamp / 1000);
//                    changeTime(timeToStr(time_));
//                },
//                onSelectTime:function(date,ele,time){
//                    var time_ = parseInt(time.timeStamp / 1000);
//                    changeTime(timeToStr(time_));
//                }
            });

            function changeTime(dateTime){
                if(dateTime.minute > 0 && dateTime.minute < 30){
                    var t = dateTime.year + "/" + dateTime.month + "/" + dateTime.date + ' ' + dateTime.hour + ':30';
                }else{
                    var t = dateTime.year + "/" + dateTime.month + "/" + dateTime.date + ' ' + parseInt(dateTime.hour + 1) + ':00';
                }
                $('#timeOpen').val(t);
            }

            function timeToStr(unixTime, timeZone) {
                if (typeof (timeZone) == 'number'){
                    unixTime = parseInt(unixTime) + parseInt(timeZone) * 60 * 60;
                }
                var time = new Date(unixTime * 1000);
                var ymdhis = {};
                var year = time.getFullYear();
                var month = time.getMonth()+1;
                var date = time.getDate();
                var hour = time.getHours();
                var minute = time.getMinutes();
                var sec = time.getSeconds();
                ymdhis = {
                    year:year,
                    month:month,
                    date:date,
                    hour:hour,
                    minute:minute,
                    sec:sec
                };
                return ymdhis;
            }

            function getAllowTimes(){
                var allowTimes = [];
                for (var i=0;i<24;i++){
                    var temp = i < 10 ? '0' + i : i;
                    var str1 = temp + ':00';
                    var str2 = temp + ':30';
                    allowTimes.push(str1)
                    allowTimes.push(str2)
                }
                return allowTimes;
            }
        })
    </script>

</body>
</html>