<?php
/**
 * Ocara开源框架 通过配置定义部分常量
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

use Ocara\Core\Url;

defined('OC_PATH') or exit('Forbidden!');

$config = ocContainer()->config;
$webHost = $config->get('WEB_HOST', OC_EMPTY);

//是否外部引入模式
defined('OC_INVOKE') OR define('OC_INVOKE', false);

//系统语言
defined('OC_LANGUAGE') OR define('OC_LANGUAGE', ocService()->app->getLanguage());

//入口执行文件名
defined('OC_INDEX_FILE') OR define('OC_INDEX_FILE', $config->get('OC_INDEX_FILE', 'index.php'));

//根目录URL
defined('OC_ROOT_URL') or define('OC_ROOT_URL',
    PHP_SAPI == 'cli' || OC_INVOKE
        ? ocHost($webHost, false)
        : ocHost($webHost ?: OC_HOST) . ocDir(ltrim(ocCommPath(dirname($_SERVER['SCRIPT_NAME'])), OC_DIR_SEP))
);

//URL路由类型
defined('OC_URL_ROUTE_TYPE') OR define(
    'OC_URL_ROUTE_TYPE',
    PHP_SAPI == 'cli' || OC_INVOKE ?
        Url::ROUTE_TYPE_DIR
        :
        $config->get('URL_ROUTE_TYPE', Url::ROUTE_TYPE_DEFAULT)
);