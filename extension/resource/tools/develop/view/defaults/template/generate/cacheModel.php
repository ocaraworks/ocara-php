<div class="section">
<div class="location">当前位置 > 缓存模型(CacheModel)</div>
<div class="section-title">添加缓存模型</div>
<div class="section-body">
<form id="" action="<?php echo ocUrl(array('generate', 'action'), array('target' => 'cacheModel'));?>" method="post">

<div>
    <span class="left-span">模块类型</span>
    <input type="radio" value="" name="mdltype" id="mdltype1" checked /> 默认全局&nbsp;
    <input type="radio" value="modules" name="mdltype" id="mdltype2" /> 普通模块
    <input type="radio" value="console" name="mdltype" id="mdltype3" /> 命令模块
    <input type="radio" value="assist" name="mdltype" id="mdltype4" /> 工具模块
</div>

<div>
    <span class="left-span">模块名称</span>
    <input type="text" value="" name="mdlname" id="mdlname">
</div>

<div>
    <span class="left-span">键名前缀</span>
    <input type="text" value="" name="prefix" id="prefix">
</div>

<div>
<span class="left-span">缓存连接配置名：</span>
<input type="text" name="connect" id="connect" value="<?php echo \Ocara\Core\DatabaseFactory::getDefaultServer();?>">
</div>

<div>
    <span class="left-span">模型名称</span>
    <input type="text" value="" name="model" id="model">
</div>

<div>
<span class="left-span">&nbsp;</span>
<input type="submit" value="提交" name="submit" />
</div>
</form>
</div>
</div>