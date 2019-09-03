<?php
$dirs = array(
	'application' => array(
	    'console',
        'controller',
        'model/cache',
        'model/database',
        'model/entity/database',
        'lang/zh_cn',
        'lang/zh_cn/database',
        'modules',
        'service',
        'view/defaults/helper',
        'view/defaults/part',
        'view/defaults/layout',
        'view/defaults/template',
	),
	'public' => array(
		'attachments',
		'pass',
		'src/css/defaults',
		'src/images/defaults',
		'src/js',
	),
	'resource' => array(
	    'config/env',
	    'data/docs',
        'data/fields'
    ),
	'runtime' => array(
        'logs',
        'session',
    ),
    'tools' => array(
        'dev/controller/generate',
        'dev/private/config'
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
        'config/env/develop',
        'config/env/local',
        'config/env/production',
        'config/env/test',
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
        'Base/Model/DatabaseEntity',
	),
    'tools' => array(
        'dev/controller/DevModule',
        'dev/controller/generate/ActionAction',
        'dev/controller/generate/Controller',
        'dev/controller/generate/ErrorAction',
        'dev/controller/generate/IndexAction',
        'dev/controller/generate/LoginAction',
        'dev/controller/generate/LogoutAction',
        'dev/private/config/base',
    ),
);