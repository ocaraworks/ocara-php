<div class="section">
<div class="location">当前位置 > 模型(Model)</div>
<div class="section-title">添加模型</div>
<div class="section-body">
<form id="" action="<?php echo ocUrl(array('generate', 'action'), array('target' => 'model'));?>" method="post">

<div>
    <span class="left-span">模块类型</span>
    <input type="radio" value="" name="mdltype" id="mdltype1" checked /> 全局控制器（默认）
    <input type="radio" value="modules" name="mdltype" id="mdltype2" /> 普通模块（modules）
    <input type="radio" value="console" name="mdltype" id="mdltype3" /> 命令模块（console）
    <input type="radio" value="tools" name="mdltype" id="mdltype4" /> 工具模块（tools）
</div>

<div>
    <span class="left-span">模块名称</span>
    <input type="text" value="" name="mdlname" id="mdlname">
</div>

<div>
<span class="left-span">数据库服务器名称：</span>
<input type="text" name="connect" id="connect" value="<?php echo \Ocara\Core\DatabaseFactory::getDefaultServer();?>">
</div>

<div>
<span class="left-span">数据表名</span>
<input type="text" value="" name="table" id="table">
</div>

<div>
    <span class="left-span">模型名称</span>
    <input type="text" value="" name="model" id="model">
    <span class="right-span">不填默认为表名</span>
</div>

<div>
<span class="left-span">主键字段默认</span>
<input type="text" value="" name="primaries" id="primaries">
<span class="right-span">1.复合主键请求英文半角逗号“,”分隔；<br/>2.为空时自动从数据库取。</span>
</div>

<div>
<span class="left-span">&nbsp;</span>
<input type="submit" value="提交" name="submit" />
</div>
</form>
</div>
</div>