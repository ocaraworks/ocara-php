<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 数据库驱动类接口Driver
 * @Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/

namespace Ocara\Interfaces;

defined('OC_PATH') or exit('Forbidden!');

/**
 * 数据库对象接口
 * @author Administrator
 */
interface Driver
{
    /**
     * 连接数据库
     */
    public function connect();

    /**
     * 执行SQL语句
     * @param string $sql
     * @param integer $resultmode
     */
    public function query($sql, $resultmode = null);

    /**
     * 执行SQL语句
     * @param string $sql
     * @param integer $resultmode
     */
    public function query_sql($sql, $resultmode = null);

    /**
     * 关闭数据库
     */
    public function close();

    /**
     * 从结果集中取得一行作为关联数组，或数字数组，或二者兼有
     */
    public function fetch_array();

    /**
     * 从结果集中取得一行作为关联数组
     */
    public function fetch_assoc();

    /**
     * 从结果集中取得一行作为数字数组
     */
    public function fetch_row();

    /**
     * 释放结果集
     */
    public function free_result();

    /**
     * 获取记录数
     */
    public function num_rows();

    /**
     * 游标移动到指定记录
     * @param integer $num
     */
    public function data_seek($num = 0);

    /**
     * 取得前一次 操作所影响的记录行数
     */
    public function affected_rows();

    /**
     * 获取最后一条错误信息
     */
    public function error_no();

    /**
     * 获取最后一条错误信息
     */
    public function error();

    /**
     * 获取错误信息列表
     */
    public function error_list();

    /**
     * 转义字符串
     * @param string $str
     */
    public function real_escape_string($str);

    /**
     * 预处理
     * @param string $sql
     */
    public function prepare($sql);

    /**
     * 预处理
     * @param string $sql
     */
    public function prepare_sql($sql);

    /**
     * 绑定参数
     * @param string $parameter
     * @param mixed $variable
     */
    public function bind_param($parameter, &$variable);

    /**
     * 执行
     */
    public function execute();

    /**
     * 执行
     */
    public function execute_sql();

    /**
     * 获取所有结果集数据
     * @param string $dataType
     * @param bool $queryRow
     */
    public function get_all_result($dataType, $queryRow = false);
}