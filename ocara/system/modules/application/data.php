<?php
$dirs = array(
	'application' => array(
		'controller', 
		'model', 
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
		'conf/model', 
		'data', 
		'lang/zh_cn/control', 
		'lang/zh_cn/model', 
	),
	'service' => array(
		'functions', 
		'library',
		'support'
	)
);

$files = array(
	'application' => array(
		'controller/Controller.php',
		'view/default/layout/layout.php'
	),
	'resource' 	  => array(
		'conf/application.php', 
		'conf/develop.php', 
		'conf/database.php',
		'conf/cache.php',
		'conf/callback.php',
		'conf/static.php',
	)
);