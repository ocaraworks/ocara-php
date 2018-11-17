<?php
$dirs = array(
	'application' => array(
		'commands/make',
        'commands/tasks',
        'dal/entities',
        'dal/model/defaults',
        'dal/values',
        'modules/index/controllers',
        'modules/index/logic',
        'modules/index/private',
        'modules/index/view/defaults/helper',
        'modules/index/view/defaults/part',
        'modules/index/view/defaults/layout',
        'modules/index/view/defaults/template',
        'view/defaults/helper',
        'view/defaults/part',
        'view/defaults/layout',
	), 
	'public' => array(
		'attachments',
		'resource/css/defaults',
		'resource/images/defaults',
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
		'conf/model/defaults',
    ),
	'lang' => array(
		'zh_cn/control',
		'zh_cn/model/defaults',
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
		'view/defaults/layout/layout.php'
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