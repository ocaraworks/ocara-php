<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   全局日志处理类Log
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;
use Ocara\Ocara;
use \ReflectionClass;

defined('OC_PATH') or exit('Forbidden!');

class Log extends Base
{
    protected $_name;

    /**
     * 初始化
     * Log constructor.
     * @param string $logName
     */
    public function __construct($logName = null)
    {
        $this->_name = $logName ? : 'common';
        $class = ocConfig('Log.engine', '\Ocara\Service\FileLog');
        $this->_plugin = new $class();
        $this->_plugin->setOption(ocConfig('Log.root', 'logs'));
    }

    /**
     *
     * @param $message
     * @param $traceInfo
     * @param string $type
     */
    public function write($message, array $traceInfo = array(), $type = 'info')
    {
        $time = date(ocConfig('DATE_FORMAT.datetime'), time());

        if (!$this->_plugin->has($this->_name)) {
            $this->_plugin->create($this->_name);
        }

        if (!ocScalar($message)) {
            $message .= ocJsonEncode($message);
        }

        $format = ocConfig('LOG.format', '[{type}]|{time}|{message}', true);
        $message = trim($message);
        $content = ocSprintf($format, compact('type', 'time', 'message'));

        if ($traceInfo) {
            $content .= PHP_EOL . self::getTraceString($traceInfo);
        }

        try {
            $this->_plugin->write($this->_name, $content);
        } catch (\Exception $e)
        {}
    }

    /**
     * 信息日志
     * @param $content
     * @param array $traceInfo
     */
    public function info($content, array $traceInfo = array())
    {
        $this->write($content, $traceInfo, 'info');
    }

    /**
     * 调试日志
     * @param $content
     * @param array $traceInfo
     */
    public function debug($content, array $traceInfo = array())
    {
        $this->write($content, $traceInfo, 'debug');
    }

    /**
     * 错误日志
     * @param $content
     * @param array $traceInfo
     */
    public function error($content, array $traceInfo = array())
    {
        $this->write($content, $traceInfo, 'error');
    }

    /**
     * 警告日志
     * @param $content
     * @param array $traceData
     */
    public function warning($content, array $traceData = array())
    {
        $this->write($content, $traceData, 'warning');
    }

    /**
     * 获取Trace字符串
     * @param array $traceInfo
     * @return string
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

        return implode(PHP_EOL, $content);
    }

    /**
     * 获取一行Trace数据
     * @param integer $index
     * @param string $format
     * @param array $row
     * @return string
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
     * @param array $args
     * @return string
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
