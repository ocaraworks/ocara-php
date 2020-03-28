<?php
/**
 * 安全过滤类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use \ReflectionException;
use Ocara\Exceptions\Exception;

class Filter extends Base
{
    const EVENT_SQL_KEYWORDS_FILTER = 'sqlKeywordsFilter';
    const EVENT_SCRIPT_KEYWORDS_FILTER = 'scriptKeywordsFilter';

    protected $jsEvents = array();

    /**
     * 初始化
     * Filter constructor.
     */
    public function __construct()
    {
        $this->jsEvents = implode('|', ocConfig('JS_EVENTS', array()));
    }

    /**
     * 注册事件
     * @throws Exception
     */
    public function registerEvents()
    {
        parent::registerEvents();

        $this->event(self::EVENT_SQL_KEYWORDS_FILTER)
            ->append(ocConfig('EVENTS.filters.sqlKeywordsFilter', array($this, 'eventSqlKeywordsFilter')));

        $this->event(self::EVENT_SCRIPT_KEYWORDS_FILTER)
            ->append(ocConfig('EVENTS.filters.scriptKeywordsFilter', array($this, 'eventScriptKeywordsFilter')));
    }

    /**
     * 过滤SQL语句
     * @param string|array $content
     * @param bool $addSlashes
     * @param array $keywords
     * @param bool $equal
     * @return array|bool|mixed|string
     * @throws Exception
     * @throws ReflectionException
     */
    public function sql($content, $addSlashes = true, array $keywords = array(), $equal = false)
    {
        if (is_array($content)) {
            return array_map(__METHOD__, $content);
        } else {
            if ($keywords && ocConfig('DATABASE_FILTER_SQL_KEYWORDS', true)) {
                if ($equal) {
                    if (in_array(strtolower($content), $keywords)) return false;
                } else {
                    if ($this->event(self::EVENT_SQL_KEYWORDS_FILTER)->has()) {
                        $content = $this->fire(self::EVENT_SQL_KEYWORDS_FILTER, array($content, $keywords));
                    }
                }
            }
            return $addSlashes ? $this->addSlashes($content) : $content;
        }
    }

    /**
     * SQL过滤事件
     * @param string $content
     * @param array $keywords
     * @return string|string[]|null
     * @throws Exception
     */
    public function eventSqlKeywordsFilter($content, $keywords)
    {
        foreach ($keywords as $key => $value) {
            $keywords[$key] = "/{$value}/i";
        }

        $addChar = ocConfig('FILTERS.sql_keyword_add_char', '#');
        $content = preg_replace($keywords, $addChar . "\${0}" . $addChar, (string)$content);
        return $content;
    }

    /**
     * 过滤内容
     * @param $content
     * @return array|mixed|string
     * @throws Exception
     * @throws ReflectionException
     */
    public function content($content)
    {
        return $this->html($this->script($content));
    }

    /**
     * 过滤HTML
     * @param string|array $content
     * @return array|mixed|string
     */
    public function html($content)
    {
        if (is_array($content)) {
            return array_map(__METHOD__, $content);
        } else {
            if (function_exists('htmlspecialchars')) {
                return htmlspecialchars($content);
            }

            $search = array('&', '"', "'", '<', '>');
            $replace = array('&amp;', '&quot;', '&#039;', '&lt;', '&gt;');
            return str_replace($search, $replace, $content);
        }
    }

    /**
     * 过滤PHP标签
     * @param string|array $content
     * @return array|mixed
     */
    public function php($content)
    {
        if (is_array($content)) {
            return array_map(__METHOD__, $content);
        } else {
            return str_replace(
                array('<?', '?>'),
                array('&lt;?', '?&gt;'),
                $content
            );
        }
    }

    /**
     * 过滤脚本
     * @param string|array $content
     * @return array|mixed|string|string[]|null
     * @throws Exception
     * @throws ReflectionException
     */
    public function script($content)
    {
        if (is_array($content)) {
            return array_map(__METHOD__, $content);
        } else {
            $content = preg_replace('/<script[^>]*>.*<\/script>/i', OC_EMPTY, $content);
            $content = preg_replace('/<iframe[^>]*>.*<\/iframe>/i', OC_EMPTY, $content);
            $content = preg_replace('/<noframes[^>]*>.*<\/norame>/i', OC_EMPTY, $content);
            $content = preg_replace('/<object[^>]*>.*<\/object>/i', OC_EMPTY, $content);
            $content = preg_replace('/javascript:/i', OC_EMPTY, $content);

            $content = $this->fire(self::EVENT_SCRIPT_KEYWORDS_FILTER, array($content, $this->jsEvents));
            return $content;
        }
    }

    /**
     * 脚本关键字过滤事件处理
     * @param string $content
     * @return string|string[]|null
     * @throws Exception
     */
    public function eventScriptKeywordsFilter($content)
    {
        $addChar = ocConfig('FILTERS.sql_keyword_add_char', '#');
        $expression = '/(on(' . $this->jsEvents . '))|((' . $this->jsEvents . ')\((\s*function\()?)/i';
        $content = preg_replace($expression, $addChar . "\${1}" . $addChar, $content);
        return $content;
    }

    /**
     * 过滤Request来的数据
     * @param string|array $content
     * @return array|string
     * @throws Exception
     * @throws ReflectionException
     */
    public function request($content)
    {
        if (is_array($content)) {
            return array_map(__METHOD__, $content);
        } else {
            return addslashes($this->content($content));
        }
    }

    /**
     * 过滤掉空白字符
     * @param string|array $content
     * @return array|mixed
     */
    public function space($content)
    {
        if (is_array($content)) {
            return array_map(__METHOD__, $content);
        } else {
            return preg_replace('/\s+/', OC_EMPTY, $content);
        }
    }

    /**
     * 将空白字符全替换掉
     * @param string $str
     * @param string $replace
     * @return mixed
     */
    public function replaceSpace($str, $replace = OC_SPACE)
    {
        return preg_replace('/\s+/', $replace, $str);
    }

    /**
     * 清除UTF-8下字符串的BOM字符
     * @param string $content
     * @return array|string
     */
    public function bom($content)
    {
        if (is_array($content)) {
            return array_map(__METHOD__, $content);
        } else {
            if (substr($content, 0, 3) == chr(239) . chr(187) . chr(191)) {
                return ltrim($content, chr(239) . chr(187) . chr(191));
            }
            return $content;
        }
    }

    /**
     * 转义
     * @param string|array $content
     * @return array|string
     */
    public function addSlashes($content)
    {
        if (is_array($content)) {
            return array_map(__METHOD__, $content);
        } else {
            return addslashes($content);
        }
    }

    /**
     * 去除转义
     * @param string|array $content
     * @return array|string
     */
    public function stripSlashes($content)
    {
        if (is_array($content)) {
            return array_map(__METHOD__, $content);
        } else {
            return stripslashes($content);
        }
    }

    /**
     * 去除换行符
     * @param string|array $content
     * @return array|mixed
     */
    public function rn($content)
    {
        if (is_array($content)) {
            return array_map(__METHOD__, $content);
        } else {
            return str_replace(array("\r\n", "\r", "\n"), OC_EMPTY, $content);
        }
    }
}