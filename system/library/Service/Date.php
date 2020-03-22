<?php
/**
 * 日期处理插件类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Service;

use Ocara\Core\ServiceBase;
use Ocara\Exceptions\Exception;

class Date extends ServiceBase
{
    protected $maps = array(
        'year' => 'year',
        'month' => 'mon',
        'day' => 'mday',
        'hour' => 'hours',
        'minute' => 'minutes',
        'second' => 'seconds',
    );

    /**
     * 获取日期信息
     * @param string|numeric $time
     * @return array|string
     * @throws Exception
     */
    public function getDateInfo($time)
    {
        $dateInfo = array();

        if (is_string($time)) {
            $dateInfo = $this->baseGetDateInfo($time, 'ymd');
            $dateInfo = array_merge($this->maps, $dateInfo);
        } elseif (is_numeric($time)) {
            $data = getdate($time);
            foreach ($this->maps as $key => $value) {
                $dateInfo[$key] = $data[$value];
            }
        }

        return $dateInfo;
    }

    /**
     * 设置时间参数
     * @param string|numric $time
     * @param integer $number
     * @param string $type
     * @return bool|false|string
     * @return bool|false|string
     * @throws Exception
     */
    public function set($time, $number, $type)
    {
        $dateInfo = $this->getDateInfo($time);

        if ($dateInfo) {
            if (array_key_exists($type, $this->maps)) {
                $dateInfo[$type] = abs($number);
                $this->checkDate($dateInfo);
            }
            return $this->getDate($dateInfo);
        }

        return false;
    }

    /**
     * 获取时间参数
     * @param string|numric|array $time
     * @param string $type
     * @return array|bool|int|mixed|null
     * @throws Exception
     */
    public function get($time, $type)
    {
        $dateInfo = $this->getDateInfo($time);

        if (array_key_exists($type, $this->maps)) {
            return array_key_exists($type, $dateInfo) ? $dateInfo[$type] : 0;
        }

        return 0;
    }

    /**增加时间
     * @param string|numric|array $time
     * @param integer $number
     * @param string $type
     * @param string $format
     * @return bool|false|string
     */
    public function add($time, $number, $type, $format = null)
    {
        $time = $this->getDate($time, $format);

        if ($time) {
            if (array_key_exists($type, $this->maps)) {
                $sign = $number < 0 ? '-' : '+';
                $number = abs($number);
                return $this->getDate(strtotime("{$time} {$sign} {$number} {$type}"), $format);
            }
        }

        return false;
    }

    /**
     * 获取时间字符串
     * @param string|numeric|array $time
     * @param string $format
     * @return false|string
     */
    public function getDate($time, $format = null)
    {
        $timestamp = $this->getTimestamp($time);
        $format = $format == 'mdy' ? 'm-d-Y' : 'Y-m-d';

        return date($format . ' H:i:s', $timestamp);
    }

    /**
     * 获取时间间隔
     * @param string|numric $startTime
     * @param string|numric $endTime
     * @param string $type
     * @return array
     */
    public function getInterval($startTime, $endTime, $type = null)
    {
        $start = $this->getTimestamp($startTime);
        $end = $this->getTimestamp($endTime);

        if ($start && $end) {
            $diff = $end - $start;
            $days = floor($diff / (3600 * 24));

            $diff = $diff % (3600 * 24);
            $hours = floor($diff / 3600);

            $diff = $diff % 3600;
            $minutes = floor($diff / 60);
            $seconds = $diff % 60;
        } else
            list($days, $hours, $minutes, $seconds) = array_fill(0, 4, 0);

        if (array_key_exists(rtrim($type, 's'), $this->maps)) {
            return $$type;
        } else {
            return compact('days', 'hours', 'minutes', 'seconds');
        }
    }

    /**
     * 生成时间戳
     * @param string|numric|array $time
     * @return false|int
     */
    public function getTimestamp($time)
    {
        if (is_numeric($time)) {
            return $time;
        } elseif (is_string($time)) {
            return strtotime($time);
        } elseif (is_array($time)) {
            return mktime(
                $time['hour'], $time['minute'], $time['second'],
                $time['month'], $time['day'], $time['year']
            );
        }

        return 0;
    }

    /**
     * 是否是闰年
     * @param integer $year
     * @return bool
     */
    public function isYun($year)
    {
        return $year % 4 == 0 && $year % 100 != 0 || $year % 400 == 0;
    }

    /**
     * 内部函数-根据时间字符串获取时间参数
     * @param string $string
     * @param string $format
     * @return array|mixed
     * @throws Exception
     */
    protected function baseGetDateInfo($string, $format = null)
    {
        if (!is_string($string)) return $string;

        if ($format == 'mdy') {
            $regStr = '/^(\d{1,2})-(\d{1,2})-(\d{4})\s(\d{1,2}):(\d{1,2}):(\d{1,2})$/';
        } else {
            $regStr = '/^(\d{4})-(\d{1,2})-(\d{1,2})\s(\d{1,2}):(\d{1,2}):(\d{1,2})$/';
        }

        if (is_string($string) && preg_match($regStr, $string, $mt)) {
            array_shift($mt);
            if ($format == 'mdy') {
                list($month, $mday, $year, $hour, $minute, $second) = $mt;
            } else {
                list($year, $month, $day, $hour, $minute, $second) = $mt;
            }
            $dateInfo = compact('hour', 'minute', 'second', 'month', 'day', 'year');
            return $this->checkDate($dateInfo);
        }

        return array();
    }

    /**
     * 检测日期
     * @param array $dateInfo
     * @return mixed
     * @throws Exception
     */
    public function checkDate(array $dateInfo)
    {
        extract($dateInfo);

        $msg = 'fault_time_number';

        if ($month > 12 || $day > 31 || $hour > 24 || $minute > 60 || $second > 60) {
            $this->showError($msg);
        }

        if ($this->isYun($year)) {
            if ($month == 2 && $day > 29) {
                $this->showError($msg);
            }
        } else {
            if ($month == 2 && $day > 28) {
                $this->showError($msg);
            }
        }

        return $dateInfo;
    }
}
