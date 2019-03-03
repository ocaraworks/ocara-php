<div class="section">
<div class="location">当前位置 > 模型(Model)</div>
<div class="section-title">添加模型</div>
<div class="section-body">
<form id="" action="<?php echo ocUrl(array('generate', 'action'), array('target' => 'model'));?>" method="post">

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
<span class="left-span">数据库连接配置名：</span>
<input type="text" name="connect" id="connect" value="<?php echo \Ocara\Core\DatabaseFactory::getDefaultServer();?>">
</div>

<div>
<span class="left-span">数据表名</span>
<input type="text" value="" name="table" id="table">
</div>

<div>
    <span class="left-span">模型名称（不填默认为表名）</span>
    <input type="text" value="" name="model" id="model">
</div>

<div>
<span class="left-span">主键字段</span>
<input type="text" value="" name="primaries" id="primaries">
</div>

<div>
    <span class="left-span">目录分库（模型文件放在库目录下面）</span>
    <input type="checkbox" value="1" name="dbdir" id="dbdir">
</div>

<div>
<span class="left-span">&nbsp;</span>
<input type="submit" value="提交" name="submit" />
</div>
</form>
</div>
</div>