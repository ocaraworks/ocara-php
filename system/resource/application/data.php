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
    'resource' => array(
        'config/application',
        'config/develop',
        'config/database',
        'config/cache',
        'config/env',
        'config/resource',
        'config/static',
    ),
    'application' => array(
        'lang/zh_cn/base',
        'view/defaults/layout/layout'
    ),
	'library' => array(
        'Base/Service/BaseService',
        'Base/Controller/ApiController',
		'Base/Controller/CommonController',
        'Base/Controller/RestController',
        'Base/Model/CacheModel',
        'Base/Model/DatabaseModel',
        'Base/Entities/CacheEntity',
        'Base/Entities/DatabaseModel',
	),
);