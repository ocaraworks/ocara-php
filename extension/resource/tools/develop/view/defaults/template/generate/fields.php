<?php
/**
 * Ocara开源框架 开发者中心字段更新模板
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */
?>
<div class="section">
    <div class="location">当前位置 > 更新字段缓存</div>
    <div class="section-title">更新字段缓存</div>
    <div class="section-body">
        <form action="<?php echo ocUrl(array(OC_MODULE_NAME, 'generate', 'action'), array('target' => 'fields')); ?>"
              method="post">

            <div>
                <span class="left-span">模型类名：</span>
                <input type="text" value="" name="model" id="model">
                <span class="right-span">包括命名空间</span>
            </div>

            <div>
                <span class="left-span">&nbsp;</span>
                <input type="submit" value="开始更新" name="submit"/>
            </div>
        </form>
    </div>
</div>