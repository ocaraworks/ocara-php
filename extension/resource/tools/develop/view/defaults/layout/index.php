<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Ocara框架开发者中心</title>
<?php ocImport($this->getViewPath('css/index.php'));?>
<style type="text/css">
#logo{float:left;}
#member{float:right;font-size:12px;width:300px;top: 15px;position: relative;margin-right:15px;}
#member span, #member a{display:block;float:right;height:25px;line-height:25px;padding:0;margin:0;}
#member a{padding-left:5px;font-weight:bold;}
</style>
<?php ocImport($this->getViewPath('js/jquery-3.4.1.min.js.php'));?>
<script language="JavaScript" type="text/javascript">
$(document).ready(function() {
    $(".left-menu").children("li").each(function () {
        //定义链接
        $(this).children('a').each(function () {
            $(this).bind('click', function () {
                var url = $(this).attr('url');

                $("#iframe-main").attr('src', url);

                $(this).closest("ul").children("li").each(function () {
                    $(this).removeClass('current-menu');
                })

                $(this).parent().addClass('current-menu');
            })
        });

        //修改样式
        $(this).hover(
            function () {
                $(this).addClass('hover-menu');
            },
            function () {
                $(this).removeClass('hover-menu');
            },
        );
    });
});

function gotoUrl(url, object) {
    $("#iframe-main").attr('src', url);
    $(object).closest("ul").children("li").each(function () {
        $(this).removeClass('current-menu');
    })
    $(object).parent().addClass('current-menu');
}
</script>
</head>
<body>
<div>
<div id="header">
<div id="logo">Ocara框架开发者中心</div>
<div id="member">
<?php if($isLogin) {?>
<a href="<?php echo ocUrl(array('generate','logout'));?>">登出</a>
<span>您好 <font><?php echo $_SESSION['OC_DEV_USERNAME'];?></font>，欢迎使用本系统！ </span>
<?php } ?>
</div>
</div>
<div id="sep"></div>
<div id="main">
<div id="left-nav">
<ul class="left-menu">
<?php
    $urls = array(
        'model' => ocUrl(array('generate', 'action'), array('target' => 'model')),
        'cacheModel' => ocUrl(array('generate', 'action'), array('target' => 'cacheModel')),
        'action' => ocUrl(array('generate', 'action'), array('target' => 'action')),
        'controller' => ocUrl(array('generate', 'action'), array('target' => 'controller')),
        'module' => ocUrl(array('generate', 'action'), array('target' => 'module')),
        'fields' => ocUrl(array('generate', 'action'), array('target' => 'fields')),
        'users' => ocUrl(array('generate', 'action'), array('target' => 'users')),
    );
?>
	<li class="current-menu"><a href="javascript:;" url="<?php echo $urls['model'];?>">模型(Model)</a></li>
    <li><a href="javascript:;" url="<?php echo $urls['cacheModel'];?>">缓存模型(CacheModel)</a></li>
    <li><a href="javascript:;" url="<?php echo $urls['action'];?>">动作(Action)</a></li>
	<li><a href="javascript:;" url="<?php echo $urls['controller'];?>">控制器(Controller)</a></li>
	<li><a href="javascript:;" url="<?php echo $urls['module'];?>">模块(Module)</a></li>
	<li><a href="javascript:;" url="<?php echo $urls['fields'];?>">字段更新</a></li>
	<?php if($isLogin && $_SESSION['OC_DEV_USERNAME'] == 'root') {?>
	<li><a href="javascript:;" url="<?php echo $urls['users'];?>">账号管理</a></li>
	<?php } ?>
</ul>
</div>
<div id="right">
<iframe src="<?php echo ocUrl(array('generate','action'), array('target' => 'model'));?>" frameborder="no" scrolling="no" id="iframe-main" name="iframe-main"></iframe>
</div>
</div>
<div id="footer"></div>
</div>
</body>
</html>