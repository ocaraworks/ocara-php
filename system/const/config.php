<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   通过配置定义部分常量
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
use Ocara\Core\Url;

defined('OC_PATH') or exit('Forbidden!');

$config = ocContainer()->config;

defined('OC_INVOKE') OR define('OC_INVOKE', false);

//系统执行模式
defined('OC_SYS_MODEL') OR define('OC_SYS_MODEL', $config->get('SYS_MODEL', 'application'));

//系统语言
defined('OC_LANGUAGE') OR define('OC_LANGUAGE', ocService()->app->getLanguage());

//入口执行文件名
defined('OC_INDEX_FILE') OR define('OC_INDEX_FILE', $config->get('OC_INDEX_FILE', 'index.php'));

//开发者中心标志,URL中使用这个标志来访问开发者中心
defined('OC_DEV_SIGN') OR define('OC_DEV_SIGN', $config->get('DEV_SIGN', '_dev'));

//根目录URL
defined('OC_ROOT_URL') or define('OC_ROOT_URL',
    PHP_SAPI == 'cli' || OC_INVOKE ?
    OC_DIR_SEP :
    OC_PROTOCOL
    . '://'
    . ocDir(OC_HOST, ltrim(ocCommPath(dirname($_SERVER['SCRIPT_NAME'])), OC_DIR_SEP))
);

//URL路由类型
defined('OC_URL_ROUTE_TYPE') OR define(
  'OC_URL_ROUTE_TYPE', PHP_SAPI == 'cli' || OC_INVOKE ?  Url::ROUTE_TYPE_DIR : $config->get('URL_ROUTE_TYPE', 1)
);

