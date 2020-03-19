<?php
/*
 * ----------------------------------------------------------------------------
 * 开发级配置-事件处理器中间件
 * ----------------------------------------------------------------------------
 */

return array(
    'EVENTS' => array(
        /*
         * 应用启动事件
         */
        'bootstrap' => array(
            'before_bootstrap' => array(), //应用启动前回调
        ),

        /*
         * 路由分发事件
         */
        'dispatch' => array(
            'before_dispatch' => array(), //路由分发前回调
            'after_dispatch' => array(), //路由分发后回调
        ),

        /*
         * 错误输出事件
         */
        'error' => array(
            'report' => array(), //日志打印回调
            'before_output' => array(), //打印错误前回调
            'output' => array(), //打印错误回调
            'after_output' => array(), //打印错误后回调
        ),

        /*
         * 数据库相关事件
         */
        'database' => array(
            'before_execute_sql' => array(), //执行SQL语句前回调，适合于写SQL语句日志
            'after_execute_sql' => array(), //执行SQL语句后回调，适合于写SQL执行结果日志
        ),

        /**
         * 安全过滤事件
         */
        'filters' => array(
            'sql_keywords_filter' => array(), //SQL关键字过滤回调，必须返回值
            'script_keywords_filter' => array(), //前端脚本关键字过滤回调，必须返回值
        ),

        /*
         * 数据模型Model相关事件
         */
        'model' => array(),
    )
);