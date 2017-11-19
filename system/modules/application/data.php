<?php
$dirs = array(
	'application' => array(
		'controller', 
		'model/Main',
		'view/default/helper', 
		'view/default/part', 
		'view/default/layout', 
		'view/default/template'
	), 
	'public' => array(
		'css/default', 
		'images/default', 
		'js', 
		'attachments',
		'static'
	), 
	'resource' => array(
		'conf/control', 
		'conf/model/main',
		'conf/fields/main',
		'data', 
		'lang/zh_cn/control', 
		'lang/zh_cn/model/main',
	),
	'service' => array(
		'functions', 
		'library',
		'support'
	)
);

$files = array(
	'application' => array(
		'controller/CommonController.php',
		'controller/RedisController.php',
		'model/MainBase.php',
		'model/MemcacheBase.php',
		'model/RedisBase.php',
		'view/default/layout/layout.php'
	),
	'resource' 	  => array(
		'conf/application.php', 
		'conf/develop.php', 
		'conf/database.php',
		'conf/cache.php',
		'conf/event.php',
		'conf/callback.php',
		'conf/static.php',
	)
);