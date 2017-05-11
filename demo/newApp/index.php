<?php
/**
 * 程序执行开始时间
 */
define('OC_EXECUTE_START_TIME', microtime(true));

/*
 * 加载框架或自动加载
 */
//require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';
require_once dirname(dirname(__DIR__)) . '/system/library/Ocara.php';

/*
 * 运行应用
 */
Ocara\Ocara::create();