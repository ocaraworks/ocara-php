<?php

//根目录
defined('OC_PATH') OR define(
    'OC_PATH', str_replace("\\", DIRECTORY_SEPARATOR, realpath(dirname(dirname(dirname(__DIR__))))) . DIRECTORY_SEPARATOR
);

//加载基本类库
require_once (OC_PATH . 'system/functions/utility.php');
require_once (OC_PATH . 'system/functions/common.php');
require_once (OC_PATH . 'system/const/basic.php');
require_once (OC_CORE . 'Basis.php');
require_once (OC_CORE . 'Base.php');
require_once (OC_CORE . 'Container.php');
require_once (OC_CORE . 'Config.php');
require_once (OC_CORE . 'Loader.php');
require_once (OC_CORE . 'ExceptionHandler.php');

//加载框架类
require_once (OC_CORE . 'Ocara.php');