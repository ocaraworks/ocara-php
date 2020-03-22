<?php
/**
 * Ocara开源框架 开发者中心用户设置模板
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */
?>
<div class="section">
    <div class="location">当前位置 > >账号(Users)</div>
    <div class="section-title">账号管理</div>
    <div class="section-body">
        <form action="<?php echo ocUrl(array(OC_MODULE_NAME, 'generate', 'action'), array('target' => 'users')); ?>"
              method="post">

            <div>
                <span class="left-span">账号名：</span>
                <input type="text" value="" name="username" id="username">
            </div>

            <div>
                <span class="left-span">密码：</span>
                <input type="password" value="" name="password" id="password">
            </div>


            <div>
                <span class="left-span">&nbsp;</span>
                <input type="submit" value="提交" name="submit"/>
            </div>
        </form>
    </div>
</div>