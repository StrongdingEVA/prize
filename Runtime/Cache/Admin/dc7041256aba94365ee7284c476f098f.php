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
            

            
    <div class="main-title">
        <h2>抽奖管理</h2>
    </div>

    <div class="cf">
        <a class="btn" href="<?php echo U('prizeEdit');?>">新 增</a>
        <button class="btn ajax-post confirm" url="<?php echo U('prizeDel');?>" target-form="ids">删 除</button>

        <!-- 高级搜索 -->
        <div class="search-form fr cf">
            <div class="sleft">
                <select name="type" id="prize_type">
                    <option value="-1">选择发布类型</option>
                    <option value="0" <?php if(($prize_type) == "0"): ?>selected<?php endif; ?>>所有</option>
                    <option value="1" <?php if(($prize_type) == "1"): ?>selected<?php endif; ?>>赞助商发布</option>
                    <option value="2" <?php if(($prize_type) == "2"): ?>selected<?php endif; ?>>用户发布</option>
                </select>
            </div>

            <div class="sleft">
                <select name="type" id="type">
                    <option value="-1">选择抽奖类型</option>
                    <option value="0" <?php if(($type) == "0"): ?>selected<?php endif; ?>>所有</option>
                    <option value="1" <?php if(($type) == "1"): ?>selected<?php endif; ?>>实物</option>
                    <option value="2" <?php if(($type) == "2"): ?>selected<?php endif; ?>>红包</option>
                </select>
            </div>

            <div class="sleft">
                <select name="open_type" id="open_type">
                    <option value="-1">选择开奖方式</option>
                    <option value="0" <?php if(($open_type) == "0"): ?>selected<?php endif; ?>>所有</option>
                    <option value="1" <?php if(($open_type) == "1"): ?>selected<?php endif; ?>>按时间自动开奖</option>
                    <option value="2" <?php if(($open_type) == "2"): ?>selected<?php endif; ?>>按人数自动开奖</option>
                    <option value="3" <?php if(($open_type) == "3"): ?>selected<?php endif; ?>>手动开奖</option>
                    <option value="4" <?php if(($open_type) == "4"): ?>selected<?php endif; ?>>现场</option>
                </select>
            </div>

            <div class="sleft">
                <select name="status" id="status">
                    <option value="-1">选择开奖状态</option>
                    <option value="0" <?php if(($status) == "0"): ?>selected<?php endif; ?>>所有</option>
                    <option value="1" <?php if(($status) == "1"): ?>selected<?php endif; ?>>未开奖</option>
                    <option value="2" <?php if(($status) == "2"): ?>selected<?php endif; ?>>开奖成功</option>
                    <option value="3" <?php if(($status) == "3"): ?>selected<?php endif; ?>>开奖失败</option>
                    <option value="4" <?php if(($status) == "4"): ?>selected<?php endif; ?>>不完全开奖</option>
                </select>
            </div>
        </div>
    </div>

    <div class="data-table table-striped">
        <table>
            <thead>
            <tr>
                <th class="row-selected">
                    <input class="checkbox check-all" type="checkbox">
                </th>
                <th>发起人</th>
                <th>抽奖类型</th>
                <th>图片</th>
                <th>奖品名称</th>
                <th>奖品个数</th>
                <th>开奖方式</th>
                <th>创建时间</th>
                <th>状态</th>
                <th>操作</th>
            </tr>
            </thead>
            <tbody>
            <?php if(!empty($list)): if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><tr>
                        <td><input class="ids row-selected" type="checkbox" name="id[]" value="<?php echo ($vo["id"]); ?>"></td>
                        <td><?php echo ($vo["user_id"]); ?></td>
                        <td>
                            <?php if($vo['type'] == 1): ?>实物
                                <?php else: ?>
                                红包<?php endif; ?>
                        </td>
                        <td>
                            <img src="<?php echo ($vo["thumb"]); ?>" width="150px" alt="">
                        </td>
                        <td>
                            <?php if($vo['type'] == 1): echo ($vo["name"]); ?>
                                <?php else: ?>
                                红包<?php endif; ?>
                        </td>
                        <td><?php echo ($vo["num"]); ?></td>
                        <td>
                            <?php if($vo['open_type'] == 1): ?>按时间自动开奖
                                <?php elseif($vo['open_type'] == 2): ?>
                                按人数自动开奖
                                <?php elseif($vo['open_type'] == 3): ?>
                                手动开奖
                                <?php else: ?>
                                现场开奖<?php endif; ?>
                        </td>
                        <td><?php echo (time_format($vo["create_time"])); ?></td>
                        <td>
                            <?php if($vo['status'] == 1): ?>未开奖
                                <?php elseif($vo['status'] == 2): ?>
                                开奖成功
                                <?php elseif($vo['status'] == 3): ?>
                                过期开奖失败
                                <?php else: ?>
                                不完全开奖<?php endif; ?>
                        </td>
                        <td>
                            <a title="编辑" href="<?php echo U('prizeEdit',array('id'=>$vo['id']));?>">编辑</a>
                            <a title="查看人数" href="<?php echo U('prizeShowJoin',array('id'=>$vo['id']));?>">查看人数</a>
                            <a class="confirm ajax-get" title="删除" href="<?php echo U('prizeDel',array('id'=>$vo['id']));?>">删除</a>
                        </td>
                    </tr><?php endforeach; endif; else: echo "" ;endif; ?>
                <?php else: ?>
                <td colspan="12" class="text-center"> aOh! 暂时还没有内容! </td><?php endif; ?>
            </tbody>
        </table>
        <!-- 分页 -->
        <div class="page">
            <?php echo ($_page); ?>
        </div>
    </div>

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

    <script type="text/javascript">
        //导航高亮
        highlight_subnav("<?php echo U('prizeList');?>");

        $(function () {
            $('select[name=sel_filter]').change(function () {
                location.href = cleara(SELF) + "&a=pos&sel_filter=" + $(this).val();
            });

            $('#prize_type').change(function(){
                var thisVal = $(this).val();
                doUrl();
            })

            $('#type').change(function(){
                var thisVal = $(this).val();
                doUrl();
            })

            $('#open_type').change(function(){
                var thisVal = $(this).val();
                doUrl();
            })

            $('#status').change(function(){
                var thisVal = $(this).val();
                doUrl();
            })

            function doUrl(){
                var prize_type = $('#prize_type').val();
                var type_ = $('#type').val();
                var open_type = $('#open_type').val();
                var status = $('#status').val();
                window.location.href = '<?php echo U("prizeList");?>' + '&prize_type=' + prize_type + '&type=' + type_ + '&open_type=' + open_type + '&status=' + status;
            }
        });
    </script>

</body>
</html>