<?php
$dirs = array(
	'application' => array(
	    'config/env',
	    'console',
        'controller',
        'model/cache',
        'model/database',
        'model/entity/database',
        'model/entity/logic',
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
        'static'
	),
	'data' => array(
	    'docs',
        'fields'
    ),
	'runtime' => array(
        'logs',
        'session',
        'temp'
    ),
    'tools' => array(
        'dev/controller/generate',
        'dev/privates/config'
    ),
    'tests' => array(),
);

$files = array(
    'application' => array(
        'config/application',
        'config/system',
        'config/database',
        'config/cache',
        'config/env',
        'config/resource',
        'config/events',
        'config/static',
        'config/env/develop',
        'config/env/local',
        'config/env/production',
        'config/env/alpha',
        'controller/Module',
        'lang/zh_cn/base',
        'service/BaseService',
        'view/defaults/layout/layout',
    ),
	'support' => array(
        'Base/Service/CommonService',
        'Base/Controller/ApiController',
		'Base/Controller/CommonController',
        'Base/Controller/RestController',
        'Base/Controller/TaskController',
        'Base/Model/CacheModel',
        'Base/Model/DatabaseModel',
        'Base/Model/DatabaseEntity',
        'Base/Model/LogicEntity',
	),
    'public' => array(
        'pass/tools/index'
    ),
    'tools' => array(
        'dev/controller/DevModule',
        'dev/controller/generate/ActionAction',
        'dev/controller/generate/Controller',
        'dev/controller/generate/ErrorAction',
        'dev/controller/generate/IndexAction',
        'dev/controller/generate/LoginAction',
        'dev/controller/generate/LogoutAction',
        'dev/privates/config/base',
    ),
    'tests' => array(
        'bootstrap'
    ),
    'ocara'
);