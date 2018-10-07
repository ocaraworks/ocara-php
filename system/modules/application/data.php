<?php
$dirs = array(
	'application' => array(
		'console/make',
        'console/tasks',
        'entities',
        'model/main',
        'modules/index/controllers',
        'modules/index/logic',
        'modules/index/private',
        'modules/index/view/default/helper',
        'modules/index/view/default/part',
        'modules/index/view/default/layout',
        'modules/index/view/default/template',
        'values',
        'view/default/helper',
        'view/default/part',
        'view/default/layout',
	), 
	'public' => array(
		'attachments',
		'resource/css/default',
		'resource/images/default',
		'resource/js',
		'html',
	),
	'data' => array(
	    'docs',
        'runtime/cache',
        'runtime/logs',
        'runtime/sessions',
        'table/fields'
    ),
	'config' => array(
        'conf/control',
		'conf/model/main',
    ),
	'lang' => array(
		'zh_cn/control',
		'zh_cn/model/main',
	),
	'service' => array(
		'functions', 
		'library/Base',
		'support'
	)
);

$files = array(
	'application' => array(
		'library/Base/CommonController.php',
		'library/Base/RedisController.php',
		'library/Base/MainBase.php',
		'library/Base/MemcacheBase.php',
		'library/Base/RedisBase.php',
		'view/default/layout/layout.php'
	),
	'config' 	  => array(
		'application.php',
		'develop.php',
		'database.php',
		'cache.php',
		'event.php',
		'env.php',
		'callback.php',
		'static.php',
	)
);