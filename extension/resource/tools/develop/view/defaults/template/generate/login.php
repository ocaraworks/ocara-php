<?php
/**
 * Ocara开源框架 开发者中心登录模板
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */
?>
<div class="content">
    <form action="<?php echo ocUrl(array('generate', 'login')); ?>" method="post">
        <div><span class="left-span">用户名：</span><input type="text" value="" name="username" id="username"></div>
        <div><span class="left-span">密码：</span><input type="password" value="" name="password" id="password"></div>
        <div class="submit"><input type="submit" value="提交" name="submit"/></div>
    </form>
</div>