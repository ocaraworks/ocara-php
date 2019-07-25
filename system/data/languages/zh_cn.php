<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   框架提示中文简体版《请勿修改》
 * Copyright (c) http://www。ocara。cn All rights reserved。
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163。com>
 ************************************************************************************************/

defined('OC_PATH') or exit('Forbidden！');

return array(
	//异常提示
	'unknow_exception' 				=> array(1100, 'Ocara异常提示'),
	'program_error'					=> array(1101, '程序错误：'),
	'exception_error'				=> array(1102, '异常错误：'),

	//出错或错误
	'error_class_extends' 			=> array(1200, '类实例化出错，请检查该类及其父类的继承情况：%s。'),

	//参数
	'param_array' 					=> array(1300, '第%s个参数应该是数组%s{field}！'),
	'param_array_object' 			=> array(1301, '第%s个参数应该是数组或对象！'),
	'param_empty' 					=> array(1302, '第%s个参数应该为空！'),
	
	//未定义
	'undefined_interface' 			=> array(1400, '接口未定义：%s。'),
	
	//禁止
	'unallowed_develop'				=> array(1500, '非开发模式禁止进入开发者中心！'),

	//不能
	'not_null'						=> array(1700, '%s不能为空。'),
	'not_write_session_path'        => array(1701, 'Session目录没有写权限。'),

	//空的
	'null_database' 				=> array(1800, '数据库对象是空的。'), 
	'null_controller' 				=> array(1801, '控制器是空的。'), 
	'null_param' 					=> array(1802, '参数值不能为空！'),
	'null_controller_param' 		=> array(1803, '参数值不能为空！'),
	'null_model_param' 				=> array(1804, '参数值不能为空！'),
	'null_action_param' 			=> array(1805, '参数值不能为空！'),
	'null_receiver' 				=> array(1806, '邮件Socket发送没有提供接收方！'),
	'null_template_engine'			=> array(1807, '模板插件是空的！'),
    'null_route'                    => array(1808, '路由为空或错误！'),

	//非法
	'invalid_path' 					=> array(1900, '文件路径非法：%s。'),
	'invalid_debug'					=> array(1901, '非开发模式禁止显示调试SQL语句！您可以使用读入文件的方式。'),
	'invalid_exception'				=> array(1902, '指定的Exception不存在或非法！'),
	'invalid_field_name'			=> array(1903, '字段名必须是字符串。'),
	'invalid_class_static_method' 	=> array(1904, '%s缺少静态方法%s()！请使用类对象代替类名传递。'),
	'invalid_entity_database'       => array(1905, '实体的数据表模型不正确！'),
    'invalid_entity_cache'          => array(1905, '实体的缓存模型不正确！'),

	//必需
	'need_condition' 				=> array(2100, '查询条件不存在或为空。'),
	'need_radio_desc' 				=> array(2101, '必须为单选框提供文字描述！'),
	'need_checkbox_desc' 			=> array(2102, '必须为复选框提供文字描述！'),
	'need_short_open_tag'			=> array(2103, '请开启php的short_open_tag配置'),
	'need_not_array_object'			=> array(2104, '%s第%s个参数不能是数组或对象！'),
	'need_array_to_set'				=> array(2105, '指定的数组值不是数组类型，无法新增或设置它的数组元素！'),
	'need_array_value'				=> array(2106, '参数值必须是数组。'),
	'need_string_field'				=> array(2107, '字段值必须是字符串或数字。'),
	'need_scalar_condition'			=> array(2108, '查询条件值必须为字符串或数字。'),
	'need_string_table_name'		=> array(2109, '联接表名必须是字符串。'),
	'need_primary_value'			=> array(2110, '主键数据不能为空。'),
    'need_find_value'               => array(2111, '查询字段值不能为空！'),
	'need_create_primary_data'      => array(2112, '缺少主键数据无法添加记录！'),
	'need_entity_class'             => array(2113, '缺少实体类'),

	//失败
	'failed_db_init'				=> array(2200, '数据库初始化失败。'),
	'failed_db_set_timeout'			=> array(2201, '数据库超时设置失败。'),
	'failed_db_connect' 			=> array(2202, '数据库%s连接失败: [%s]%s。'),
	'failed_cache_connect'			=> array(2203, '缓存扩展加载失败: %s。'),
	'failed_new_table' 				=> array(2204, '数据表创建失败: %s。'),
	'failed_email_socket_connect' 	=> array(2205, '邮件Socket连接失败。邮件Socket连接失败。Error message:[%s] %s。'),
	'failed_curl_return' 			=> array(2206, 'PHP curl扩展执行出错：%s。'),
	'failed_load_extension' 		=> array(2207, '加载扩展失败：%s。'),
	'failed_validate_token' 		=> array(2208, '表单不存在或来源非法。'),
	'failed_select_database'		=> array(2209, '要切换的数据库不存在或已删除！'),
	'failed_file_lock'				=> array(2210, '文件锁定失败'),
	'failed_make_dir'				=> array(2211, '文件或目录新建失败'),
	'failed_database_create'		=> array(2211, '数据库新建记录失败'),

	//有误
	'fault_filename' 				=> array(2300, '文件名有误。'),
	'fault_callback_validate' 		=> array(2301, '字段%s的回调验证配置有误！'),
	'fault_debug_param'				=> array(2302, '调试参数不正确！'),
	'fault_static_field'			=> array(2303, '静态生成的参数字符或分隔符不正确。'),
	'fault_static_route'			=> array(2304, '静态生成路由配置有误，应包含{c}控制器，{a}动作，{p}查询字符串，并且以斜杠“/”或横杠“-”分隔，如：{c}/{a}-{p}。'),
	'fault_url'						=> array(2305, '抱歉，您输入的网址有误！'),
	'fault_primary_num'				=> array(2306, '主键与值数目不匹配。'),
	'fault_save_data'				=> array(2307, '保存数据为空，请检查是否存在字段或别名！'),
	'fault_cond_sign'				=> array(2310, '条件运算符不存在或有误！'),
	'fault_redis_password'			=> array(2311, '缓存系统密码验证失败！'),
	'fault_validate_callback_error' => array(2312, '表单回调验证返回数组不正确。'),
	'fault_pdo_param'				=> array(2313, 'PDO参数设置不正确。'),
	'fault_primary_value_format'    => array(2314, '指定的主键数据格式错误。'),
	'fault_route_path'				=> array(2315, 'MVC路径错误'),
	'fault_session_table'			=> array(2316, 'Session表名配置有误'),
	'fault_relate_config'			=> array(2317, '关联配置有误'),
	'fault_method_param'			=> array(2318, '函数或方法参数缺失或格式错误'),
    'fault_find_value'              => array(2319, '查询字段值格式不正确！'),

	//不存在或找不到
	'not_exists_part' 				=> array(2400, '指定的part文件不存在：{file}.php。'),
	'not_exists_layout' 			=> array(2401, '指定的layout文件不存在：%s.php。'), 
	'not_exists_helper'				=> array(2402, '指定的helper不存在：%s。'), 
	'not_exists_helper_func'		=> array(2403, '指定的helper（%s）的方法函数不存在。'), 
	'not_exists_file' 				=> array(2404, '指定的文件不存在：%s。'),
	'not_exists_class' 				=> array(2405, '指定的类不存在：%s。请检查该类是否存在以及类的继承是否正确！'),
	'not_exists_method' 			=> array(2406, '指定的类方法不存在：%s。'),
	'not_exists_function' 			=> array(2407, '指定的函数不存在：%s。'),
	'not_exists_key' 				=> array(2408, '数组键[\'%s\']不存在！'),
	'not_exists_template_file'		=> array(2409, '指定的模板文件不存在：%s。'),
	'not_exists_template_var'		=> array(2410, '指定的模板变量不存在:%s。'),
	'not_exists_form'				=> array(2411, '表单不存在或已过期'),
	'not_writable_htaccess'			=> array(2412, '.htaccess无法写入，请检查目录权限是否可写。'),
	'not_exists_database' 			=> array(2413, '数据库不存在或连接失败。'),
	'not_exists_cache'				=> array(2414, '缓存不存在。'),
	'not_exists_font'				=> array(2415, '字体不存在'),
	'not_exists_function_file'		=> array(2416, '函数库文件不存在'),
	'not_exists_key'				=> array(2417, '键名“%s”不存在'),
	'not_exists_template'			=> array(2418, '指定的模板目录%s不存在'),
	'not_exists_http_content_type'	=> array(2419, 'HTTP返回类型不正确'),
	'not_exists_http_status'		=> array(2420, 'HTTP状态码不正确。'),
	'not_exists_const'				=> array(2421, '缺少常量'),
	'not_exists_dependence_set' 	=> array(2422, '依赖不存在'),
    'not_exists_find_field'         => array(2423, '查询字段不存在！'),

	//没有
	'no_config'						=> array(2501, '找不到该配置：%s。'),
	'no_controller' 		        => array(2502, '找不到控制器%s。'),
	'no_action'                     => array(2503, '找不到控制器动作%s。'),
	'no_function' 					=> array(2504, '找不到函数：%s。'),
	'no_method' 					=> array(2506, '找不到方法：%s。'),
	'no_property'					=> array(2506, '找不到属性：$%s。原因是：%s。'),
	'no_primaries' 					=> array(2507, '没有设置主键。'),
	'no_file' 						=> array(2508, '文件不存在。'),
	'no_form' 						=> array(2509, '表单不存在。'),
	'no_access' 					=> array(2510, '很抱歉，您无权限访问该模块！'),
	'no_param' 						=> array(2511, '没有%s参数！'),
	'no_special_file' 				=> array(2512, '找不到%s文件: %s。 '),
	'no_special_class' 				=> array(2513, '找不到%s类: %s。 '),
	'no_the_special_class' 			=> array(2514, '找不到%s类文件！ '),
	'no_result_id_field' 			=> array(2515, '结果集中没有该ID字段。'),
	'no_fields' 					=> array(2516, '无法从数据库获取表%s的字段。'),
	'no_extension'					=> array(2517, '无法加载%s扩展，请检查该扩展是否安装和启动！'),
	'no_action_return'				=> array(2518, '指定的action类没有可返回函数，无法返回值！'),
	'no_open_service_config'		=> array(2519, '%s扩展或服务被禁止使用，请在应用的配置文件中开启。'),
	'no_session_save_time'			=> array(2520, 'Session保存时间未设置！'),
	'no_rewrite_default_file'		=> array(2521, '伪静态默认配置文件丢失！'),
	'no_fields'						=> array(2522, '无法获取字段信息，请检查数据表及其权限是否存在'),
	'no_plugin'						=> array(2523, '类插件不存在或为空！'),
	'no_session_path'				=> array(2524, 'Session目录不存在或缺少目录新建权限！'),
	'no_dir_write_perm'				=> array(2525, '目录没有写权限！'),
	'no_file_write_perm'		    => array(2526, '文件没有写权限！'),
	'no_file_read_perm'		    	=> array(2527, '文件没有读权限！'),
	'no_service'					=> array(2528, '服务不存在：%s'),
	'no_old_field'                  => array(2529, '找不到旧值。'),
    'no_changed_field'              => array(2530, '找不到已改变值。'),
    'no_curl_extension' 			=> array(2531, '找不到Curl扩展，并且没有开启allow_url_open！'),
	'no_relate_primary_key'         => array(2532, '关联主键不存在或为null！'),

	//已存在
	'exists_dir'		    		=> array(2601, '已存在同名的目录。'),
    'exists_singleton_object'       => array(2602, '容器中已存在该单例对象。'),
    'exists_bind_class'             => array(2603, '容器中已存在绑定类。'),
    'exists_singleton_bind_class'   => array(2604, '容器中已存在绑定的单例类。'),
    'exists_service'                => array(2605, '服务已存在：%s。')
);
