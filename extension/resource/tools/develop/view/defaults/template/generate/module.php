<div class="section">
<div class="location">当前位置 > 模块(Module)</div>
<div class="section-title">添加模块</div>
<div class="section-body">
<form action="<?php echo ocUrl(array('generate', 'action'), array('target' => 'module'));?>" method="post">

<div>
    <span class="left-span">模块类型</span>
    <input type="radio" value="modules" name="mdltype" id="mdltype1" checked /> 普通模块&nbsp;
    <input type="radio" value="console" name="mdltype" id="mdltype2" /> 命令模块
    <input type="radio" value="assist" name="mdltype" id="mdltype3" /> 工具模块
</div>

<div>
    <span class="left-span">控制器类型</span>
    <input type="radio" value="Common" name="controllerType" id="controllerType1" checked /> 普通控制器&nbsp;
    <input type="radio" value="Rest" name="controllerType" id="controllerType2" /> Restful控制器
</div>

<div>
<span class="left-span">模块名称：</span>
<input type="text" value="" name="mdlname" id="mdlname">
</div>

<div>
<span class="left-span">&nbsp;</span>
<input type="submit" value="提交" name="submit" />
</div>
</form>
</div>
</div>