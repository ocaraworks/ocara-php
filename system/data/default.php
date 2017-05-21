<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   默认配置 《请勿修改》
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/

defined('OC_PATH') or exit('Forbidden!');

/*
 * 系统默认配置
 */
$OC_CONF['LANGUAGE'] 				= 'zh_cn';
$OC_CONF['SYS_MODEL'] 				= '';
$OC_CONF['DEV_SIGN'] 				= '';

$OC_CONF['DEFAULT_CONTROLLER'] 		= 'home'; //默认控制器
$OC_CONF['COOKIE_DOMAIN'] 			= '';

$OC_CONF['MODEL_QUERY_DATA_TYPE'] 	= 'array';

/**
 * 时间格式设置
 */
$OC_CONF['DATE_FORMAT'] = array(
	'timezone' 	=> 'PRC', //时区设置
	'date'     	=> 'Y-m-d', //日期格式
	'datetime' 	=> 'Y-m-d H:i:s', //日期时间格式
	'time' 	  	=> 'H:i:s', //时间格式
);

/**
 * HTTP请求方法
 */
$OC_CONF['ALLOWED_HTTP_METHODS'] = array(
	'GET', 		'POST', 	'HEAD', 	'PUT',		'DELETE',
	'PATCH', 	'TRACE', 	'CONNECT',	'OPTIONS',	'MOVE',
	'COPY',		'LINK',		'UNLINK',	'UNLINK',
);

/*
 * session配置
 */
$CONF['SESSION'] = array(
	'save_type'			=> 1, 				 //session保存方式，1为自定义文件类型，2为数据库类型
	'save_time'			=> 0,				//session的最长存活时间（单位为秒）
	'file_path'			=> 'cache/sessions',//session文件保存路径，上级目录强制在resource/data
	'db_server' 		=> 'default',		//数据库服务器名称
	'db_table'			=> 'ocsess'			//数据表名
);

/*
 * 应用配置
 */
$OC_CONF['STATIC'] = array(
	'open' 			=> 0, 	  //是否开启静态生成
	'file_type'		=> 'html' //静态文件扩展名，html或htm
);

/**
 * 控制器类型
 */
$OC_CONF['CONTROLLER_FEATURE_CLASS'] = array(
	'Common', 'Rest'
);

/**
 * 系统服务类
 */
$OC_CONF['SYSTEM_SERVICE_CLASS'] = array(
	'Request' 		=> 'Ocara\Request',
	'Response' 		=> 'Ocara\Response',
	'Error' 		=> 'Ocara\Error',
	'Filter' 		=> 'Ocara\Filter',
	'Path' 			=> 'Ocara\Path',
	'Url' 			=> 'Ocara\Url',
	'Lang' 			=> 'Ocara\Lang',
	'Cookie' 		=> 'Ocara\Cookie',
	'Session' 		=> 'Ocara\Session',
);

/**
 * Restful路由规则
 */
$OC_CONF['CONTROLLERS']['rest'] = array(

	'id_param'     => 'id',
	'content_type' => 'json',

	//路由映射
	'action_map' => array(
		'GET' 			=> 'index',
		'GET/id' 		=> 'view',
		'POST' 			=> 'create',
		'PUT/id' 		=> 'update',
		'PATCH/id' 		=> 'update',
		'HEAD' 			=> 'index',
		'HEAD/id' 		=> 'view',
		'DELETE/id'		=> 'delete',
	),
	//请求成功返回状态码
	'success_code_map' => array(
		'index' 	=> 200,
		'create' 	=> 201,
		'view' 		=> 200,
		'update' 	=> 200,
		'delete' 	=> 204
	),
);

/**
 * 应用路径信息
 */
$OC_CONF['APP_PATH_INFO'] = array(
	/*所属目录*/
	'belong' => array(
		'controller'  	=> 'application',
		'model'  	  	=> 'application',
		'view'  	  	=> 'application',

		'css'  		  	=> 'public',
		'images'  	  	=> 'public',
		'js'  		  	=> 'public',
		'static'  	  	=> 'public',
		'attachments' 	=> 'public',

		'conf'			=> 'resource',
		'data'			=> 'resource',
		'lang'			=> 'resource',

		'functions'		=> 'service',
		'library'		=> 'service',
		'support'		=> 'service',
	),

	/*目录映射*/
	'map' => array(
		'action' 	  => 'controller',
		'function'	  => 'service/functions',
		'image'		  => 'public/images',
		'attachment'  => 'public/attachments',
	)
);

/**
 * 自动加载路径配置
 */
$OC_CONF['AUTOLOAD_MAP'] = array(
	/*应用程序*/
	'Controller\\' 							=> OC_ROOT . 'application/controller/',
	'Model\\' 								=> OC_ROOT . 'application/model/',
	'View\\' 								=> OC_ROOT . 'application/view/',
	'View\Helper\\' 						=> OC_ROOT . 'application/view/helper/',

	/*Ocara框架*/
	'Ocara\\' 								=> OC_SYS . 'library/',
	'Ocara\Controller\\' 					=> OC_LIB . 'Controller/',
	'Ocara\Model\\' 						=> OC_LIB . 'Model/',
	'Ocara\View\\' 							=> OC_LIB . 'View/',
	'Ocara\Cache\\' 						=> OC_LIB . 'Cache/',
	'Ocara\Database\\' 						=> OC_LIB . 'Database/',
	'Ocara\Interfaces\\' 					=> OC_LIB . 'Interfaces/',
	'Ocara\Feature\\' 						=> OC_LIB . 'Feature/',
	'Ocara\Functions\\'     				=> OC_SYS . 'Functions/',
	'Ocara\Develop\\' 						=> OC_SYS . 'modules/library/',
	'Ocara\Session\\' 						=> OC_LIB . 'Session/',

	/*Ocara框架插件*/
	'Ocara\Service\\' 						=> OC_SYS . 'service/library/',
	'Ocara\Service\Interfaces\\' 			=> OC_SYS . 'service/library/Interfaces/',
	'Ocara\ervice\Functions\\'   			=> OC_SYS . 'service/functions/',

	/*Ocara框架扩展插件*/
	'Ocara\Extension\Service\\'   			=> OC_EXT . 'service/library/',
	'Ocara\Extension\Service/Functions\\'   => OC_EXT . 'service/functions/',
	'Ocara\Extension\Service/Interfaces\\'  => OC_EXT . 'service/library/Interfaces/',
);

/**
 * MIME类型配置
 */
$OC_CONF['MINE_TYPES'] = array(
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
);

/**
 * HTTP状态码
 */
$OC_CONF['HTTP_STATUS'] = array (
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
	504 => "HTTP/1.1 504 Gateway Time-out"
);
