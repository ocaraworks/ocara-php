<div class="section">
<div class="location">当前位置 > 控制器(Controller)</div>
<div class="section-title">添加控制器</div>
<div class="section-body">
<form id="" action="<?php echo ocUrl(array(OC_DEV_SIGN, 'home', 'adminControl'));?>" method="post">

<div>
    <span class="left-span">控制器类型</span>
    <input type="radio" value="Controller" name="controllerType" id="controllerType1" checked /> 普通控制器&nbsp;
    <input type="radio" value="RestController" name="controllerType" id="controllerType2" /> Restful控制器
</div>
    
<div>
<span class="left-span">控制器名称：</span>
<input type="text" name="cname" id="cname" />
<span class="right-span">有模块时：模块名/控制器名。</span>
</div>

<div>
<span class="left-span">是否新建模板</span>
<input type="checkbox" name="createview" id="createview" checked />
</div>

<div>
<span class="left-span">页面类型</span>
<input type="radio" value="1" name="vtype" id="vtype1" checked />单页面&nbsp;
<input type="radio" value="2" name="vtype" id="vtype2" />分页面
</div>

<div>
<span class="left-span">模板类型</span>
<input type="text" name="ttype" id="ttype" value="default" checked />
</div>

<div><span class="left-span">&nbsp;</span><input type="submit" value="提交" name="submit" /></div>
	
</form>
</div>
</div>