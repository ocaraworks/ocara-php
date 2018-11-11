<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   默认配置 《请勿直接修改本文件》
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/

defined('OC_PATH') or exit('Forbidden!');

return array(
    /*
     * 系统默认配置
     */
    'LANGUAGE' 				=> 'zh_cn',
    'SYS_MODEL' 			=> '',
    'DEV_SIGN' 				=> '',
    'ROUTE_PARAM_NAME'	    => 'r', //路由参数名称
    
    'DEFAULT_CONTROLLER' 		=> 'home', //默认控制器
    'DEFAULT_ACTION' 			=> 'index', //默认动作
    'COOKIE_DOMAIN' 			=> '',

    /*
     * 时间格式设置
     */
    'DATE_FORMAT' => array(
        'timezone' 	=> 'PRC', //时区设置
        'date'     	=> 'Y-m-d', //日期格式
        'datetime' 	=> 'Y-m-d H:i:s', //日期时间格式
        'time' 	  	=> 'H:i:s', //时间格式
    ),
    
    /*
     * HTTP请求方法
     */
    'ALLOWED_HTTP_METHODS' => array(
        'GET', 		'POST', 	'HEAD', 	'PUT',		'DELETE',
        'PATCH', 	'TRACE', 	'CONNECT',	'OPTIONS',	'MOVE',
        'COPY',		'LINK',		'UNLINK',	'UNLINK',
    ),
    
    /*
     * session配置
     */
    'SESSION' => array(
        'handler' => 'Ocara\Sessions\SessionFile',
        'options' => array(
            'save_time'	 => 0, //session的最长存活时间（单位为秒）
            'server'     => '', //服务器名称，数据库服务器名称或Redis服务器名
            'location'	 => 'session', //保存位置，文件目录名、数据库表名或缓存前缀
        ),
    ),
    
    /*
     * 应用配置
     */
    'STATIC' => array(
        'open' 			=> 0, 	  //是否开启静态生成
        'file_type'		=> 'html' //静态文件扩展名，html或htm
    ),
    
    /*
     * 将字段的描述作为字段文本
     */
    'USE_FIELD_DESC_LANG' => 1,
    
    /*
     * 系统服务类（单例模式）
     */
    'SYSTEM_SINGLETON_SERVICE_CLASS' => array(
        'app'               => 'Ocara\Core\Application',
        'request' 		    => 'Ocara\Core\Request',
        'response' 		    => 'Ocara\Core\Response',
        'ajax'              => 'Ocara\Core\Ajax',
        'error' 		    => 'Ocara\Core\Error',
        'filter' 		    => 'Ocara\Core\Filter',
        'url' 			    => 'Ocara\Core\Url',
        'lang' 			    => 'Ocara\Core\Lang',
        'cookie' 		    => 'Ocara\Core\Cookie',
        'session' 		    => 'Ocara\Core\Session',
        'route'			    => 'Ocara\Core\Route',
        'transaction'	    => 'Ocara\Core\Transaction',
        'pager' 		    => 'Ocara\Service\Pager',
        'validator' 	    => 'Ocara\Service\Validator',
        'file'			    => 'Ocara\Service\File',
        'font'			    => 'Ocara\Core\Font',
        'staticPath'	    => 'Ocara\Core\StaticPath',
        'globals'           => 'Ocara\Core\Globals',
        'formToken'         => 'Ocara\Core\FormToken',
        'formManager'       => 'Ocara\Core\FormManager',
        'exceptionHandler'  => 'Ocara\Core\ExceptionHandler',
        'errorOutput' 	    => 'Ocara\Service\ErrorOutput',
    ),
    
    /*
     * 系统服务类（非单例模式）
     */
    'SYSTEM_SERVICE_CLASS' => array(
        'event'             => 'Ocara\Core\Event',
        'log'	            => 'Ocara\Core\Log',
        'form'              => 'Ocara\Core\Form',
        'html'              => 'Ocara\Core\Html',
    ),
    
    /*
     * 控制器服务类
     */
    'CONTROLLER_SERVICE_CLASS' => array(
        'Common' => array(
            'view' => 'Ocara\Views\Common',
        ),
        'Rest' => array(
            'view' => 'Ocara\Views\Api',
        ),
    ),
    
    /*
     * 数据库类型映射
     */
    'DATABASE_TYPE_MAP' => array(
        'Mysql' => 'Mysqli',
    ),
    
    /*
     * Restful路由规则
     */
    'CONTROLLERS' => array(
        'rest' => array(
    
            'id_param'     => 'id', //ID参数名称
            'content_type' => 'json', //返回数据类型

            //路由映射
            'action_map' => array(
                'GET' 			=> 'index', //获取记录列表
                'GET/id' 		=> 'view', //获取记录详情
                'POST' 			=> 'create', //添加记录
                'PUT/id' 		=> 'update', //更新记录
                'PATCH/id' 		=> 'update', //更新记录
                'HEAD' 			=> 'index', //获取记录列表
                'HEAD/id' 		=> 'view', //获取记录详情
                'DELETE/id'		=> 'delete', //删除记录
            ),
            //请求成功返回状态码
            'success_code_map' => array(
                'index' 	=> 200, //获取记录列表成功
                'create' 	=> 201, //添加记录成功
                'view' 		=> 200, //获取记录详情成功
                'update' 	=> 200, //更新记录成功
                'delete' 	=> 204, //删除记录成功
            ),
        )
    ),
    
    /*
     * 应用路径信息
     */
    'APP_PATH_INFO' => array(
    
        /*目录映射*/
        'maps' => array(),
    
        /*所属目录*/
        'belongs' => array(
            'commands'		=> 'application',
            'modules'  	    => 'application',
            'view'  	  	=> 'application',
            'dal'           => 'application',
            'entities'  	=> 'application/dal',
            'models'  	  	=> 'application/dal',
            'values'  	  	=> 'application/dal',
    
            'config'        => 'resource',
            'lang'          => 'resource',
            'data'          => 'resource',
            'docs'          => 'resource/data',
            'table'         => 'resource/data',
    
            'library'	    => '',
            'pass'			=> '',
    
            'attachments' 	=> 'public',
            'css'  		  	=> 'public/src',
            'images'  	  	=> 'public/src',
            'js'  		  	=> 'public/src',
            'static'  	  	=> 'public',
    
            'cache'         => 'runtime',
            'logs'          => 'runtime',
            'sessions'      => 'runtime',
        ),
        'remote_belongs'     => array(
            'attachments' 	=> '',
            'css'  		  	=> 'src',
            'images'  	  	=> 'src',
            'js'  		  	=> 'src',
            'static'  	  	=> '',
        ),
    ),
    
    /*
     * 自动加载映射
     */
    'AUTOLOAD_MAP' => array(
        /*Ocara框架*/
        'Ocara\\' 								=> OC_SYS . 'library/',
        'Ocara\Functions\\'     				=> OC_SYS . 'functions/',
        'Ocara\Develop\\' 						=> OC_SYS . 'modules/develop/library/',
    
        /*Ocara框架插件*/
        'Ocara\Service\\' 						=> OC_SYS . 'library/Service/',
    
        /*Ocara框架扩展插件*/
        'Ocara\Extension\Service\\'   			=> OC_EXT . 'library/',
        'Ocara\Extension\Functions\\'           => OC_EXT . 'functions/',
    ),
    
    /*
     * 应用自动加载映射
     */
    'APP_AUTOLOAD_MAP' => array(
        'app\console\\' 							=> OC_ROOT . 'application/console/',
        'app\modules\\' 							=> OC_ROOT . 'application/modules/',
        'app\view\\' 								=> OC_ROOT . 'application/view/',
        'app\dal\\' 							    => OC_ROOT . 'application/dal/',
    ),
    
    /*
     * 服务组件配置
     */
    'SERVICE' => array(
        'validator' => '\Ocara\Core\Validator',
        'validate' => '\Ocara\Service\Validate'
    ),
    
    
    /*
     * 要向模板引擎注册的函数
     */
    'DEFAULT_VIEW_ENGINE_FUNCTIONS' => array(
        'ocGlobal', 	'ocPath', 	'ocFile', 		'ocRealUrl',
        'ocSimpleUrl', 	'ocUrl', 	'ocConfig',  	'ocGet',
        'ocSet', 		'ocDel',	'ocKeyExists',	'ocFileExists',
        'ocPrint', 		'ocDump',
    ),
    
    /*
     * 要向模板引擎注册的自定义函数
     */
    'VIEW_ENGINE_FUNCTIONS' => array(),
    
    /*
     * JS事件
     */
    'JS_EVENTS' => array(
        'click', 	'dbclick',    'change', 	'load', 	 'focus',
        'mouseout', 'mouseover',  'mousedown', 	'mousemove', 'mouseup',
        'submit',	'keyup', 	  'keypress',   'keydown',  'error',
        'abort', 	'resize', 	  'reset', 	  	'select', 	 'unload'
    ),
    
    /*
     * MIME类型配置
     */
    'MINE_TYPES' => array(
        'chm' 		=> 'application/octet-stream',
        'ppt' 		=> 'application/vnd.ms-powerpoint',
        'xls' 		=> 'application/vnd.ms-excel',
        'doc' 		=> 'application/msword',
        'exe' 		=> 'application/octet-stream',
        'rar' 		=> 'application/octet-stream',
        'js' 		=> 'javascript/js',
        'css' 		=> 'text/css',
        'hqx' 		=> 'application/mac-binhex40',
        'bin' 		=> 'application/octet-stream',
        'oda' 		=> 'application/oda',
        'pdf' 		=> 'application/pdf',
        'ai' 		=> 'application/postsrcipt',
        'eps' 		=> 'application/postsrcipt',
        'es' 		=> 'application/postsrcipt',
        'rtf' 		=> 'application/rtf',
        'mif' 		=> 'application/x-mif',
        'csh' 		=> 'application/x-csh',
        'dvi' 		=> 'application/x-dvi',
        'hdf' 		=> 'application/x-hdf',
        'nc' 		=> 'application/x-netcdf',
        'cdf' 		=> 'application/x-netcdf',
        'latex' 	=> 'application/x-latex',
        'ts' 		=> 'application/x-troll-ts',
        'src' 		=> 'application/x-wais-source',
        'zip' 		=> 'application/zip',
        'bcpio' 	=> 'application/x-bcpio',
        'cpio' 		=> 'application/x-cpio',
        'gtar' 		=> 'application/x-gtar',
        'shar' 		=> 'application/x-shar',
        'sv4cpio' 	=> 'application/x-sv4cpio',
        'sv4crc' 	=> 'application/x-sv4crc',
        'tar' 		=> 'application/x-tar',
        'ustar' 	=> 'application/x-ustar',
        'man' 		=> 'application/x-troff-man',
        'sh' 		=> 'application/x-sh',
        'tcl' 		=> 'application/x-tcl',
        'tex' 		=> 'application/x-tex',
        'texi' 		=> 'application/x-texinfo',
        'texinfo' 	=> 'application/x-texinfo',
        't' 		=> 'application/x-troff',
        'tr' 		=> 'application/x-troff',
        'roff' 		=> 'application/x-troff',
        'shar' 		=> 'application/x-shar',
        'me' 		=> 'application/x-troll-me',
        'ts' 		=> 'application/x-troll-ts',
        'gif' 		=> 'image/gif',
        'jpeg' 		=> 'image/pjpeg',
        'jpg' 		=> 'image/pjpeg',
        'jpe' 		=> 'image/pjpeg',
        'ras' 		=> 'image/x-cmu-raster',
        'pbm' 		=> 'image/x-portable-bitmap',
        'ppm' 		=> 'image/x-portable-pixmap',
        'xbm' 		=> 'image/x-xbitmap',
        'xwd' 		=> 'image/x-xwindowdump',
        'ief' 		=> 'image/ief',
        'tif' 		=> 'image/tiff',
        'tiff' 		=> 'image/tiff',
        'pnm' 		=> 'image/x-portable-anymap',
        'pgm' 		=> 'image/x-portable-graymap',
        'rgb' 		=> 'image/x-rgb',
        'xpm' 		=> 'image/x-xpixmap',
        'txt' 		=> 'text/plain',
        'c' 		=> 'text/plain',
        'cc' 		=> 'text/plain',
        'h' 		=> 'text/plain',
        'html' 		=> 'text/html',
        'htm' 		=> 'text/html',
        'htl' 		=> 'text/html',
        'rtx' 		=> 'text/richtext',
        'etx' 		=> 'text/x-setext',
        'tsv' 		=> 'text/tab-separated-values',
        'mpeg' 		=> 'video/mpeg',
        'mpg' 		=> 'video/mpeg',
        'mpe' 		=> 'video/mpeg',
        'avi' 		=> 'video/x-msvideo',
        'qt' 		=> 'video/quicktime',
        'mov' 		=> 'video/quicktime',
        'moov' 		=> 'video/quicktime',
        'movie' 	=> 'video/x-sgi-movie',
        'au' 		=> 'audio/basic',
        'snd' 		=> 'audio/basic',
        'wav' 		=> 'audio/x-wav',
        'aif' 		=> 'audio/x-aiff',
        'aiff' 		=> 'audio/x-aiff',
        'aifc' 		=> 'audio/x-aiff',
        'swf' 		=> 'application/x-shockwave-flash',
        'json'      => 'application/json',
        'plain'     => 'text/plain',
        'xml'       => 'text/xml',
        'gz'		=> 'application/x-gzip',
    ),
    
    /*
     * HTTP状态码
     */
    'HTTP_STATUS' => array(
        100 => "HTTP/1.1 100 Continue",
        101 => "HTTP/1.1 101 Switching Protocols",
        200 => "HTTP/1.1 200 OK",
        201 => "HTTP/1.1 201 Created",
        202 => "HTTP/1.1 202 Accepted",
        203 => "HTTP/1.1 203 Non-Authoritative Information",
        204 => "HTTP/1.1 204 No Content",
        205 => "HTTP/1.1 205 Reset Content",
        206 => "HTTP/1.1 206 Partial Content",
        300 => "HTTP/1.1 300 Multiple Choices",
        301 => "HTTP/1.1 301 Moved Permanently",
        302 => "HTTP/1.1 302 Found",
        303 => "HTTP/1.1 303 See Other",
        304 => "HTTP/1.1 304 Not Modified",
        305 => "HTTP/1.1 305 Use Proxy",
        307 => "HTTP/1.1 307 Temporary Redirect",
        400 => "HTTP/1.1 400 Bad Request",
        401 => "HTTP/1.1 401 Unauthorized",
        402 => "HTTP/1.1 402 Payment Required",
        403 => "HTTP/1.1 403 Forbidden",
        404 => "HTTP/1.1 404 Not Found",
        405 => "HTTP/1.1 405 Method Not Allowed",
        406 => "HTTP/1.1 406 Not Acceptable",
        407 => "HTTP/1.1 407 Proxy Authentication Required",
        408 => "HTTP/1.1 408 Request Time-out",
        409 => "HTTP/1.1 409 Conflict",
        410 => "HTTP/1.1 410 Gone",
        411 => "HTTP/1.1 411 Length Required",
        412 => "HTTP/1.1 412 Precondition Failed",
        413 => "HTTP/1.1 413 Request Entity Too Large",
        414 => "HTTP/1.1 414 Request-URI Too Large",
        415 => "HTTP/1.1 415 Unsupported Media Type",
        416 => "HTTP/1.1 416 Requested range not satisfiable",
        417 => "HTTP/1.1 417 Expectation Failed",
        500 => "HTTP/1.1 500 Internal Server Error",
        501 => "HTTP/1.1 501 Not Implemented",
        502 => "HTTP/1.1 502 Bad Gateway",
        503 => "HTTP/1.1 503 Service Unavailable",
        504 => "HTTP/1.1 504 Gateway Time-out",
        505 => "HTTP/1.1 505 HTTP Version not supported",
    ),
);