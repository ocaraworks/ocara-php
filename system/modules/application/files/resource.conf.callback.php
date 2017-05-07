<?php
/*
 * ----------------------------------------------------------------------------
 * 开发级配置-智能回调
 * ----------------------------------------------------------------------------
 */

/*
 * 错误输出回调
 */
$CONF['CALLBACK']['error'] = array(
	'output'      => '', // 输出错误日志
	'write_log'   => '', // 记录错误日志
);

/*
 * 权限检测回调
 */
$CONF['CALLBACK']['auth'] = array(
	'check_error'   => '', //权限控制检测错误回调
	'invalid_error' => '', //无权限错误回调
);

/*
 * 表单使用回调
 */
$CONF['CALLBACK']['form'] = array(
	'check_error'    => '', //表单检测失败时的回调
	'generate_token' => '', //表单令牌加密算法的回调
);

/*
 * 数据库相关回调
 */
$CONF['CALLBACK']['database'] = array(
	'get_config' => '', //数据库配置的回调
	'execute_sql' => array(
		'before' => '', //执行SQL语句前的回调，适合于写SQL语句日志
		'after'	 => '', //执行SQL语句完成后的回调，适合于写SQL语句结果日志
	)
);

/*
 * 数据模型Model相关回调
 */
$CONF['CALLBACK']['model'] = array(
	//Model查询缓存的回调
	'query' => array(
		'save_cache_data' => '', //保存为缓存数据的回调
		'get_cache_data' => '',  //获取缓存数据的回调
	)
);

/*
 * die()或exit()函数回调
 */
$CONF['CALLBACK']['oc_die'] = '';