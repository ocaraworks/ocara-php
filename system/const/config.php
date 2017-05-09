<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   通过配置定义部分常量
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
use Ocara\Url;

defined('OC_PATH') or exit('Forbidden!');

//入口执行文件名
define('OC_INDEX_FILE', ocConfig('OC_INDEX_FILE', 'index.php'));

//开发者中心标志,URL中使用这个标志来访问开发者中心
define('OC_DEV_SIGN', ocConfig('DEV_SIGN', 'dev'));

//根目录URL
defined('OC_ROOT_URL') or define('OC_ROOT_URL',
    OC_PHP_SAPI == 'cli' ?
    OC_DIR_SEP :
    OC_PROTOCOL
    . '://'
    . ocDir(OC_HOST, ltrim(ocCommPath(dirname($_SERVER['SCRIPT_NAME'])), OC_DIR_SEP))
);

//URL路由类型
defined('OC_URL_ROUTE_TYPE') OR define(
  'OC_URL_ROUTE_TYPE', OC_PHP_SAPI == 'cli' ?  Url::DIR_TYPE : ocConfig('URL_ROUTE_TYPE')
);

