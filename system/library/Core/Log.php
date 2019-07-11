<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   全局日志处理类Log
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use Ocara\Core\Base;
use \ReflectionClass;
use Ocara\Exceptions\Exception;

defined('OC_PATH') or exit('Forbidden!');

class Log extends Base
{
    protected $name;

    /**
     * 初始化
     * Log constructor.
     * @param null $logName
     * @throws Exception
     */
    public function __construct($logName = null)
    {
        $this->name = $logName ? : 'common';
        $plugin = $this->setPlugin(ocService('fileLog', true));
        $plugin->setOption(ocConfig(array('Log', 'root'), 'logs'));
    }

    /**
     * 初始化
     * @param $message
     * @param array $traceInfo
     * @param string $type
     * @throws Exception
     */
    public function write($message, array $traceInfo = array(), $type = 'info')
    {
        $time = date(ocConfig(array('DATE_FORMAT', 'datetime')), time());
        $plugin = $this->plugin();

        if (!$plugin->has($this->name)) {
            $plugin->create($this->name);
        }

        if (!ocScalar($message)) {
            $message .= ocJsonEncode($message);
        }

        $format = ocConfig(array('LOG', 'format'), '[{type}]|{time}|{message}', true);
        $message = trim($message);
        $content = ocSprintf($format, compact('type', 'time', 'message'));

        if ($traceInfo) {
            $content .= PHP_EOL . self::getTraceString($traceInfo);
        }

        try {
            $plugin->write($this->name, $content);
        } catch (\Exception $e)
        {}
    }

    /**
     * 信息日志
     * @param $content
     * @param array $traceInfo
     * @throws Exception
     */
    public function info($content, array $traceInfo = array())
    {
        $this->write($content, $traceInfo, 'info');
    }

    /**
     * 调试日志
     * @param $content
     * @param array $traceInfo
     * @throws Exception
     */
    public function debug($content, array $traceInfo = array())
    {
        $this->write($content, $traceInfo, 'debug');
    }

    /**
     * 错误日志
     * @param $content
     * @param array $traceInfo
     * @throws Exception
     */
    public function error($content, array $traceInfo = array())
    {
        $this->write($content, $traceInfo, 'error');
    }

    /**
     * 警告日志
     * @param $content
     * @param array $traceData
     * @throws Exception
     */
    public function warning($content, array $traceData = array())
    {
        $this->write($content, $traceData, 'warning');
    }

    /**
     * 获取Trace字符串
     * @param array $traceInfo
     * @return string
     * @throws Exception
     */
    public static function getTraceString(array $traceInfo)
    {
        $content = array();
        $len = count($traceInfo);

        for ($i = 0, $index = 0; $i < $len; $i++) {
            $row = $traceInfo[$i];
            $format = "#%d %s%s%s%s%s(%s)";
            $str = self::getTraceRow($index, $format, $row);
            if ($str) {
                $content[] = $str;
                $index++;
            }
        }

        return implode(PHP_EOL, $content);
    }

    /**
     * 获取一行Trace数据
     * @param $index
     * @param $format
     * @param $row
     * @return string
     * @throws Exception
     */
    private static function getTraceRow($index, $format, $row)
    {
        $content = OC_EMPTY;

        if (isset($row['function']) && $row['function']) {
            $file     = isset($row['file']) && $row['file'] ? $row['file'] : false;
            $line     = isset($row['line']) && $row['line'] ? "({$row['line']})" : false;

            $class    = isset($row['class']) && $row['class'] ? $row['class'] : false;
            $type     = isset($row['type']) && $row['type'] ? $row['type'] : false;

            $function = isset($row['function']) && $row['function'] ? $row['function'] : false;
            $args     = isset($row['args']) && $row['args'] ? self::getTraceArgs($row['args']) : false;

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
     * @throws Exception
     */
    private static function getTraceArgs(array $args)
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
                    try {
                        $reflection = new ReflectionClass($value);
                        $value = $reflection->getName();
                    } catch (\Exception $exception) {
                        throw new Exception($exception->getMessage(), $exception->getCode());
                    }
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
