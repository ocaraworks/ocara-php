<div class="section">
<div class="location">当前位置 > 字段更新</div>
<div class="section-title">字段更新</div>
<div class="section-body">
<form action="<?php echo ocUrl(array(OC_MODULE_NAME, 'generate', 'action'), array('target' => 'fields'));?>" method="post">

<div>
<span class="left-span">模型类名：</span>
<input type="text" value="" name="model" id="model">
    <span class="right-span">包括命名空间</span>
</div>

<div>
<span class="left-span">&nbsp;</span>
<input type="submit" value="更新字段" name="submit" />
</div>
</form>
</div>
</div>