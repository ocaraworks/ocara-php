<?php
/*
 * ----------------------------------------------------------------------------
 * 开发级配置-基本配置
 * ----------------------------------------------------------------------------
 */
return array(
    /*
     * 框架配置
     */
    'DEFAULT_CONTROLLER' => 'home', //默认控制器
    'DEFAULT_ACTION' => 'index', //默认动作

    /*
     * 系统模式配置
     * 分为开发模式和运营模式两种
     * 配置为develop时是开发模式，其他均为运营模式
     */
    'SYSTEM_RUN_MODE' => 'develop',

    /*
     * 服务组件配置
     */
    'SERVICE' => array(
        'validate' => '\Ocara\Service\Validate'
    ),

    /*
     * session配置
     */
    'SESSION' => array(
        //session处理器。file表示文件，database表示数据库，cache表示缓存，其他的直接写完整类名
        'handler' => 'file',

        //选项设置，不同的处理器选项不同
        'options' => array(
            'save_time'	 => 0, //session的最长存活时间（单位为秒）
            'server'     => '', //服务器名称，数据库服务器名称或Redis服务器名
            'location'	 => 'session', //保存位置，文件目录名、数据库表名或缓存前缀
        ),
    ),

    /*
     * cookie配置
     */
    'COOKIE' => array(
        'path'		=>  '/', //有效路径
        'domain' 	=> '', //有效域名
        'secure' 	=> 0, //是否使用Https来传输cookie
        'httponly' 	=> 1, //是否禁止Javascript使用该cookie
    ),

    /*
     * 安全配置
     */
    'SQL_FILTER_KEYWORDS' => 0, //是否过滤SQL关键词

    /*
     * 日志配置
     */
    'LOG' => array(
        'engine' => '\Ocara\Service\FileLog', //日志插件
        'root' => 'logs', //日志保存根路径，文件日志会放在resource/data目录下面
        'format' => '[{type}]|{time}|{message}', //日志格式
    ),

    /*
     * API配置
     */
    'API' => array(
        'send_header_code' => 1, //API渲染时是否在HTTP头部返回HTTP错误码
    ),

    /*
     * 表单配置
     */
    'FORM' => array(
        'token_tag'             => 'form_token_name', //表单令牌隐藏域名称
        'check_repeat_submit'   => 1, // 表单重复提交检查
    ),

    /*
     * 模板配置
     */
    'TEMPLATE' => array(
        'file_type'		 => 'php', //模板文件名
        'engine'		 => '', //模板引擎（如果使用默认的Smarty模板引擎，填Ocara\Service\Smarty）
        'default_style'  => 'defaults', //默认模板风格目录
        'default_layout' => 'layout', //默认的layout名称
    ),

    /*
     * Smarty模板配置
     * 可自由选择是否使用smarty模板
     */
    'SMARTY' => array(
        'use_cache'	 => 0, //是否使用缓存
        'left_sign'	 => '<{', //模板中左标记
        'right_sign' => '}>', //模板中右标记
    ),

    /*
     * 分页配置
     */
    'PAGE' => array(
        'class_name' => '', //分页类名称，默认是Ocara\Service\Pager
        'page_param' => 'page', //URL查询字符串中分页参数名称
        'per_page'	 => 10, //每页显示多少条记录
        'per_show'   => 10, //一次显示多少页
    ),

    /*
     * 安全过滤配置
     */
    'FILTERS' => array(
        'sql_keyword_add_char' => '#', //SQL关键字首尾增加字符
        'script_keyword_add_char' => '#', //Javascript等关键字首尾增加字符
    ),

    /*
     * 验证码在$SESSION中的保存名称
     */
    'VERIFYCODE_NAME' => 'OCSESSCODE',

    /*
     * 默认文档类型
     */
    'DEFAULT_CONTENT_TYPE' => 'html', //默认页面文档类型
    'DEFAULT_AJAX_CONTENT_TYPE' => 'json', //默认Ajax返回文档类型

    /*
     * 默认字体（用于图片处理等）
     */
    'DEFAULT_FONT' => 'simhei',

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
     * 自定义错误处理
     */
    'ERROR_HANDLER' => array(
        'except_error_list' => array(),
    )
);