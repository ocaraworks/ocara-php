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
 * 开始执行时间
 */
defined('OC_EXECUTE_START_TIME') OR define('OC_EXECUTE_START_TIME', microtime(true));

/*
 * 基本常量
 */
//空格
defined('OC_SPACE') or define('OC_SPACE', chr(32));

//空字符串
defined('OC_EMPTY') or define('OC_EMPTY', (string)false);

//目录分隔符（反斜杠）
defined('OC_DIR_SEP') or define('OC_DIR_SEP', '/');

//命名空间分隔符（顺斜杠）
defined('OC_NS_SEP') or define('OC_NS_SEP', "\\");

//英文单引号
defined('OC_QUOTE') or define('OC_QUOTE', "'");

//Ocara SQL标记
defined('OC_SQL_TAG') or define('OC_SQL_TAG', '{oc_sql_tag}');

//TRUE整型值
defined('OC_TRUE') or define('OC_TRUE', 1);

//FALSE整型值
defined('OC_FALSE') or define('OC_FALSE', 0);

/*
 * 服务器信息常量
 */
//是否是Windows系统
defined('OC_IS_WIN') or define('OC_IS_WIN', strstr(PHP_OS, 'WIN'));

//当前文件名
defined('OC_PHP_SELF') or define('OC_PHP_SELF', basename($_SERVER['PHP_SELF']));

//当前主机或域名
defined('OC_HOST') or define('OC_HOST', ocGet('HTTP_HOST', $_SERVER));

//协议类型
defined('OC_PROTOCOL') or define('OC_PROTOCOL', strtolower(ocGet('HTTPS', $_SERVER)) == 'on' ? 'https' : 'http');

//当前URL
defined('OC_REQ_URI') or define('OC_REQ_URI', ocCommPath(ocGet('REQUEST_URI', $_SERVER)));

/*
 * 框架常量
 */
//框架系统目录
defined('OC_SYS') or define('OC_SYS', OC_PATH . 'system/');

//框架扩展目录
defined('OC_EXT') or define('OC_EXT', OC_PATH . 'extension/');

//框架类库目录
defined('OC_LIB') or define('OC_LIB', OC_SYS . 'library/');

//框架系统处理类目录
defined('OC_CORE') or define('OC_CORE', OC_SYS . 'library/Core/');

//框架系统服务目录
defined('OC_SERVICE') or define('OC_SERVICE', OC_SYS . 'library/Service/');

//是否外部引入
defined('OC_INVOKE') or define('OC_INVOKE', false);

//是否命令模块
defined('OC_CONSOLE_MODULE') or define('OC_CONSOLE_MODULE', false);

/*
 * 应用程序常量
 */
//应用根目录不带斜杠
defined('OC_ROOT') or define('OC_ROOT', ocDir(ocCommPath(dirname(dirname(realpath($_SERVER['SCRIPT_FILENAME']))))));

//应用根目不带斜杠
defined('OC_APP_ROOT') or define('OC_APP_ROOT', ocDir(OC_ROOT));

//WEB根目录
defined('OC_WEB_ROOT') or define('OC_WEB_ROOT', ocCommPath(OC_APP_ROOT . 'public' . OC_DIR_SEP));

//程序根目录
defined('OC_APPLICATION_PATH') or define('OC_APPLICATION_PATH', OC_APP_ROOT . 'application/');

//模块目录
defined('OC_MODULE_PATH') or define('OC_MODULE_PATH', OC_EMPTY);

//模块命名空间常量
defined('OC_MODULE_NAMESPACE') or define('OC_MODULE_NAMESPACE', OC_EMPTY);

//默认模块
defined('OC_DEFAULT_MODULE') OR define('OC_DEFAULT_MODULE', OC_EMPTY);


