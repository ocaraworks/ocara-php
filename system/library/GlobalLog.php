<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   全局日志处理类GlobalLog
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;
use Ocara\Service\Log;

defined('OC_PATH') or exit('Forbidden!');

final class GlobalLog extends Base
{
    protected static $_logRoot;
    protected static $_log;

    /**
     * 单例模式
     */
    private static $_instance = null;

    private function __clone(){}
    private function __construct(){}

    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
            self::initialze();
        }
        return self::$_instance;
    }

    /**
     * 初始化
     */
    public static function initialze()
    {
        self::$_logRoot = 'logs/global';
        self::$_log = new Log();
        self::$_log->setOption(self::$_logRoot);
    }

    /**
     * 写日志
     * @param string $logName
     * @param integer $time
     * @param string|array $content
     * @param string $traceString
     * @param array $traceInfo
     * @param string $type
     */
    public function write($logName, $time, $content, $traceString = null, $traceInfo = array(), $type = 'info')
    {
        self::getInstance();
        $datetime = date(ocConfig('DATE_FORMAT.datetime'), $time);

        if (!self::$_log->exists($logName)) {
            self::$_log->create($logName);
        }

        if (!ocScalar($content)) {
            $content = $content . ocJsonEncode($content);
        }

        $content  = '['.$datetime.']['.$type.']' . $content;
        if ($traceString) {
            $content = $content . OC_ENTER . $traceString;
        }

        try {
            self::$_log->write($logName, $content);
        } catch (\Exception $e)
        {}
    }

    /**
     * 信息日志
     */
    public function info($logName, $time, $content, $traceString = null, $traceInfo = array())
    {
        $this->write($logName, $time, $content, $traceString, $traceInfo, 'info');
    }

    /**
     * 调试日志
     */
    public function debug($logName, $time, $content, $traceString = null, $traceInfo = array())
    {
        $this->write($logName, $time, $content, $traceString, $traceInfo, 'debug');
    }

    /**
     * 错误日志
     */
    public function error($logName, $time, $content, $traceString = null, $traceInfo = array())
    {
        $this->write($logName, $time, $content, $traceString, $traceInfo, 'error');
    }

    /**
     * 获取Trace字符串
     * @param array $traceInfo
     */
    public static function getTraceString(array $traceInfo)
    {
        $content = array();
        $len = count($traceInfo);

        for ($i = 0, $index = 0; $i < $len; $i++) {
            $row = $traceInfo[$i];
            $format = "#%d %s%s%s%s%s(%s)";
            $str = self::_getTraceRow($index, $format, $row);
            if ($str) {
                $content[] = $str;
                $index++;
            }
        }

        return implode(OC_ENTER, $content);
    }

    /**
     * 获取一行Trace数据
     * @param integer $index
     * @param string $format
     * @param array $row
     */
    private static function _getTraceRow($index, $format, $row)
    {
        $content = OC_EMPTY;

        if (isset($row['function']) && $row['function']) {
            $file     = isset($row['file']) && $row['file'] ? $row['file'] : false;
            $line     = isset($row['line']) && $row['line'] ? "({$row['line']})" : false;

            $class    = isset($row['class']) && $row['class'] ? $row['class'] : false;
            $type     = isset($row['type']) && $row['type'] ? $row['type'] : false;

            $function = isset($row['function']) && $row['function'] ? $row['function'] : false;
            $args     = isset($row['args']) && $row['args'] ? self::_getTraceArgs($row['args']) : false;

            if ($file) {
                $line = $file ? $line . ': ' : $line;
                $content = sprintf($format, $index, $file, $line, $class, $type, $function, $args);
            }
        }

        return $content;
    }

    /**
     * 获取Trace参数字符串
     * @param $args
     */
    private static function _getTraceArgs(array $args)
    {
        $content = array();

        foreach ($args as $value)  {
            if (is_scalar($value)) {
                $value = var_export($value, true);
            } else {
                $type = gettype($value);
                if ($type == 'array') {
                    $value = 'Array';
                } elseif ($type == 'resource')  {
                    $value = get_resource_type($value);
                } elseif ($type == 'object') {
                    $reflection = new ReflectionClass($value);
                    $value = $reflection->getName();
                    $value = "Object($value)";
                } else {
                    $value = $type;
                }
            }
            $content[] = $value;
        }

        $result = $content ? implode(',', $content) : OC_EMPTY;
        return $result;
    }
}
