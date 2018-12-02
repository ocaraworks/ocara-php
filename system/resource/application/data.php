<?php
$dirs = array(
	'application' => array(
		'console/make',
        'console/tasks',
        'controller',
        'dal/entities',
        'dal/model/defaults',
        'dal/values',
        'service',
        'view/defaults/helper',
        'view/defaults/part',
        'view/defaults/layout',
        'view/defaults/template',
        'resource/config/control',
        'resource/config/model',
        'resource/fields',
        'resource/lang/control',
        'resource/lang/model',
	),
	'public' => array(
		'attachments',
		'src/css/defaults',
		'src/images/defaults',
		'src/js',
		'static',
	),
	'data' => array(
	    'docs',
    ),
	'runtime' => array(
        'cache',
        'logs',
        'sessions',
    ),
);

$files = array(
    'application' => array(
        'resource/config/application.php',
        'resource/config/develop.php',
        'resource/config/database.php',
        'resource/config/cache.php',
        'resource/config/event.php',
        'resource/config/env.php',
        'resource/config/resource.php',
        'resource/config/static.php',
        'view/defaults/layout/layout.php'
    ),

	'library' => array(
        'Base/BaseService.php',
		'Base/CommonController.php',
        'Base/RestController.php',
        'Base/CacheModel.php',
        'Base/DatabaseModel.php',
	),
);