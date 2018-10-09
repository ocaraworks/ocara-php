<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   基本常量定义
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/

defined('OC_PATH') or exit('Forbidden!');

/*
 * 基本常量
 */
//空格
define('OC_SPACE', chr(32));

//空字符串
define('OC_EMPTY', (string)false);

//目录分隔符（反斜杠）
define('OC_DIR_SEP', '/');

//命名空间分隔符（顺斜杠）
define('OC_NS_SEP', "\\");

//英文单引号
define('OC_QUOTE', "'");

//Ocara SQL标记
define('OC_SQL_TAG', '{oc_sql_tag}');

//TRUE整型值
define('OC_TRUE', 1);

//FALSE整型值
define('OC_FALSE', 0);

/*
 * 服务器信息常量
 */
//是否是Windows系统
define('OC_IS_WIN', strstr(PHP_OS, 'WIN'));

//当前文件名
defined('OC_PHP_SELF') or define('OC_PHP_SELF', basename($_SERVER['PHP_SELF']));

//当前PHP的运行模式
defined('OC_PHP_SAPI') or define('OC_PHP_SAPI', php_sapi_name());

//当前主机或域名
define('OC_HOST', ocGet('HTTP_HOST', $_SERVER));

//协议类型
define('OC_PROTOCOL', strtolower(ocGet('HTTPS', $_SERVER)) == 'on'? 'https' : 'http');

//当前URL
defined('OC_REQ_URI') or define('OC_REQ_URI', ocCommPath(ocGet('REQUEST_URI', $_SERVER)));

/*
 * 框架常量
 */
//框架系统目录
define('OC_SYS', OC_PATH . 'system/');

//框架扩展目录
define('OC_EXT', OC_PATH . 'extension/');

//框架系统处理类目录
define('OC_CORE', OC_SYS . 'library/Core/');

//框架系统服务目录
define('OC_SERVICE', OC_SYS . 'library/Service/');

//是否外部引入
defined('OC_INVOKE') OR define('OC_INVOKE', false);

/*
 * 应用程序常量
 */
//WEB根目录
defined('OC_WEB_ROOT') or define('OC_WEB_ROOT', ocCommPath(dirname(realpath($_SERVER['SCRIPT_FILENAME']))) . OC_DIR_SEP);

//应用根目录
defined('OC_ROOT') or define('OC_ROOT', dirname(OC_WEB_ROOT) . OC_DIR_SEP);

//程序根目录
define('OC_APPLICATION_PATH', OC_ROOT . 'application/');

