<?php
/**
 
 * Ocara开源框架 全局日志处理类Log
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use \ReflectionClass;
use Ocara\Exceptions\Exception;

defined('OC_PATH') or exit('Forbidden!');

class Log extends Base
{
    protected $name;

    const EMERGENCY = 'emergency';
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';

    /**
     * 初始化
     * Log constructor.
     * @param null $logName
     * @throws Exception
     */
    public function __construct($logName = null)
    {
        $this->name = $logName ?: 'common';
        $plugin = $this->setPlugin(ocService('fileLog', true));
        $plugin->setOption(ocConfig(array('Log', 'root'), 'logs'));
    }

    /**
     * 写日志
     * @param $message
     * @param array $context
     * @param string $type
     * @throws Exception
     */
    public function write($message, array $context = array(), $type = 'info')
    {
        $time = date(ocConfig(array('DATE_FORMAT', 'datetime')), time());
        $plugin = $this->plugin();

        if (!$plugin->has($this->name)) {
            $plugin->create($this->name);
        }

        if (!ocScalar($message)) {
            $message = ocJsonEncode($message);
        }

        $format = ocConfig(array('LOG', 'format'), '[{type}]|{time}|{message}', true);
        $message = ocSprintf(trim($message), $context);
        $content = ocSprintf($format, compact('type', 'time', 'message'));

        try {
            $plugin->write($this->name, $content);
        } catch (\Exception $e) {
        }
    }

    /**
     * 信息日志
     * @param $content
     * @param array $context
     * @throws Exception
     */
    public function info($content, array $context = array())
    {
        $this->write($content, $context, self::INFO);
    }

    /**
     * 调试日志
     * @param $content
     * @param array $context
     * @throws Exception
     */
    public function debug($content, array $context = array())
    {
        $this->write($content, $context, self::DEBUG);
    }

    /**
     * 运行时错误不需要马上处理，
     * 但通常应该被记录和监控。
     * @param $content
     * @param array $context
     * @throws Exception
     */
    public function error($content, array $context = array())
    {
        $this->write($content, $context, self::ERROR);
    }

    /**
     * 警告日志
     * @param $content
     * @param array $context
     * @throws Exception
     */
    public function warning($content, array $context = array())
    {
        $this->write($content, $context, SELF::WARNING);
    }

    /**
     * 系统无法使用。
     * @param $content
     * @param array $context
     * @throws Exception
     */
    public function emergency($content, array $context = array())
    {
        $this->write($content, $context, SELF::EMERGENCY);
    }

    /**
     * 警告日志
     * @param $content
     * @param array $context
     * @throws Exception
     */
    public function alert($content, array $context = array())
    {
        $this->write($content, $context, SELF::ALERT);
    }

    /**
     * 正常但重要的事件.
     * @param $content
     * @param array $context
     * @throws Exception
     */
    public function notice($content, array $context = array())
    {
        $this->write($content, $context, SELF::NOTICE);
    }

    /**
     * 临界条件
     * 例如: 应用组件不可用，意外的异常。
     * @param $content
     * @param array $context
     * @throws Exception
     */
    public function critical($content, array $context = array())
    {
        $this->write($content, $context, SELF::CRITICAL);
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
            $file = isset($row['file']) && $row['file'] ? $row['file'] : false;
            $line = isset($row['line']) && $row['line'] ? "({$row['line']})" : false;

            $class = isset($row['class']) && $row['class'] ? $row['class'] : false;
            $type = isset($row['type']) && $row['type'] ? $row['type'] : false;

            $function = isset($row['function']) && $row['function'] ? $row['function'] : false;
            $args = isset($row['args']) && $row['args'] ? self::getTraceArgs($row['args']) : false;

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

        foreach ($args as $value) {
            if (is_scalar($value)) {
                $value = var_export($value, true);
            } else {
                $type = gettype($value);
                if ($type == 'array') {
                    $value = 'Array';
                } elseif ($type == 'resource') {
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
