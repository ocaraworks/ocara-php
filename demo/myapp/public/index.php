<?php
/**
 * 应用首页
 */
use Ocara\Core\Ocara;

//程序执行开始时间
define('OC_EXECUTE_START_TIME', microtime(true));

/**
 * 加载框架
 * 请修改和确定以下路径
 */
//require_once dirname(dirname(__DIR__)) . '/ocara/system/library/Core/Ocara.php';
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

/**
 * 运行应用
 */
Ocara::create();