<?php
/**
 * 表单元素生成类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

class Html extends Base
{
    /**
     * input元素
     * @param string $type
     * @param string $name
     * @param array $attributes
     * @param bool $checked
     * @return string
     */
    public function input($type, $name, $attributes = null, $checked = false)
    {
        $attributes = $this->parseAttributes($attributes);
        $attributes['type'] = $type;

        if (($type == 'radio' || $type == 'checkbox') && $checked) {
            $attributes['checked'] = 'checked';
        }

        return $this->createHtmlTag('input', $name, $attributes, false);
    }

    /**
     * 解析属性数组
     * @param array $attributes
     * @return array
     */
    protected function parseAttributes($attributes)
    {
        if (!is_array($attributes)) {
            $attributes = array(
                'value' => $attributes
            );
        }
        return $attributes;
    }

    /**
     * 文本框
     * @param string $name
     * @param mixed $attributes
     * @return string
     */
    public function text($name, $attributes = null)
    {
        return $this->input('text', $name, $attributes);
    }

    /**
     * 密码框
     * @param string $name
     * @param mixed $attributes
     * @return string
     */
    public function password($name, $attributes = null)
    {
        return $this->input('password', $name, $attributes);
    }

    /**
     * 文本域
     * @param string $name
     * @param mixed $attributes
     * @return string
     */
    public function file($name, $attributes = null)
    {
        return $this->input('file', $name, $attributes);
    }

    /**
     * 隐藏域
     * @param string $name
     * @param mixed $attributes
     * @return string
     */
    public function hidden($name, $attributes = null)
    {
        return $this->input('hidden', $name, $attributes);
    }

    /**
     * 按钮
     * @param string $name
     * @param mixed $attributes
     * @return string
     */
    public function button($name, array $attributes = null)
    {
        $attributes = $this->parseAttributes($attributes);
        return $this->createHtmlTag('button', $name, $attributes, false);
    }

    /**
     * 单选框
     * @param string $name
     * @param string $desc
     * @param mixed $attributes
     * @param bool $checked
     * @return string
     */
    public function radio($name, $desc, $attributes = null, $checked = false)
    {
        return $this->input('radio', $name, $attributes, $checked) . $desc;
    }

    /**
     * 批量单选框
     * @param string $name
     * @param array $radios
     * @param array $checked
     * @param array $attributes
     * @return array
     */
    public function radioMulti($name, $radios, $checked = null, array $attributes = array())
    {
        return $this->getRadios($name, $radios, $checked, $attributes, 'radio');
    }

    /**
     * 复选框
     * @param string $name
     * @param string $desc
     * @param mixed $attributes
     * @param bool $checked
     * @return string
     */
    public function checkbox($name, $desc, $attributes = null, $checked = false)
    {
        return $this->input('checkbox', $name, $attributes, $checked) . $desc;
    }

    /**
     * 批量复选选框
     * @param string $name
     * @param array $checkboxes
     * @param array $checked
     * @param array $attributes
     * @return array
     */
    public function checkboxMulti($name, array $checkboxes, $checked = array(), array $attributes = array())
    {
        return $this->getRadios($name, $checkboxes, $checked, $attributes, 'checkbox');
    }

    /**
     * 批量生成单选或复选框
     * @param string $name
     * @param array $data
     * @param string|array $checked
     * @param mixed $attributes
     * @param string $method
     * @return array
     */
    protected function getRadios($name, $data, $checked, $attributes, $method)
    {
        $checked = (array)$checked;
        $boxes = array();
        $index = 1;
        $tagName = $method == 'checkbox' ? rtrim($name, '[]') . '[]' : $name;

        foreach ($data as $value => $row) {
            $attrs = $attributes;
            if (is_string($row)) {
                $desc = $row;
            } else {
                $desc = $row[0];
                if (is_array($row[1])) {
                    $attrs = array_merge($attrs, $row[1]);
                }
            }

            $attrs['id'] = $name . '_' . $index++;
            $attrs['value'] = $value;

            $params = array(
                $tagName, $desc, $attrs, in_array($value, $checked)
            );
            $boxes[] = call_user_func_array(array(__CLASS__, $method), $params);
        }

        return $boxes;
    }

    /**
     * 下拉框
     * @param string $name
     * @param array $options
     * @param mixed $attributes
     * @param bool $nullText
     * @param bool $optgroup
     * @return string
     */
    public function select($name, $options = array(), $attributes = null, $nullText = false, $optgroup = false)
    {
        $value = false;

        if (is_numeric($attributes) || is_string($attributes)) {
            $value = $attributes;
        } elseif (is_array($attributes)) {
            $value = isset($attributes['value']) ? $attributes['value'] : null;
        }

        $option = $this->options($options, $value, $nullText, $optgroup);
        $content = $option ?: true;
        $attributes = $attributes && is_array($attributes) ? $attributes : array();
        $attributes = $this->parseAttributes($attributes);
        $result = $this->createHtmlTag('select', $name, $attributes, $content);

        return $result;
    }

    /**
     * 分组下拉框
     * @param string $name
     * @param array $options
     * @param mixed $attributes
     * @param bool $nullText
     * @return string
     */
    public function selectGroup($name, $options, $attributes = null, $nullText = false)
    {
        return $this->select($name, $options, $attributes, $nullText, true);
    }

    /**
     * 下拉选项
     * @param array $options
     * @param mixed $value
     * @param string $nullText
     * @param bool $optgroup
     * @return string|null
     */
    public function options($options, $value = null, $nullText = null, $optgroup = false)
    {
        if ($nullText) {
            $attrs = array('value' => false);
            $nullText = $this->createHtmlTag('option', false, $attrs, $nullText ? $nullText : true);
        }

        if (is_array($options)) {
            $str = false;
            if ($optgroup) {
                foreach ($options as $row) {
                    if (isset($row['optgroup']) && $row['optgroup']) {
                        $str = $str . "<optgroup label=\"{$row['optgroup']}\">";
                        $str = $str . $this->getOptions($row['options'], $value);
                        $str = $str . "</optgroup>";
                    }
                }
            } else {
                $str = $str . $this->getOptions($options, $value);
            }
            return $nullText . $str;
        }

        return $nullText;
    }

    /**
     * 获取选项框
     * @param array $options
     * @param string $value
     * @return bool|string
     */
    protected function getOptions($options, $value)
    {
        $str = false;

        if (empty($options)) return false;

        foreach ($options as $key => $val) {
            $attrs = array('value' => $key);
            if ((string)$value == (string)$key) {
                $attrs['selected'] = 'selected';
            }
            $str = $str .
                $this->createHtmlTag(
                    'option', false, $attrs, $val ? $val : true
                );
        }

        return $str;
    }

    /**
     * 文本域textarea
     * @param string $name
     * @param mixed $attributes
     * @return string
     */
    public function textarea($name, $attributes = null)
    {
        if (is_array($attributes)) {
            $value = isset($attributes['value']) ? $attributes['value'] : null;
        } else {
            $value = $attributes;
            $attributes = array();
        }

        $content = $value ?: true;
        $result = $this->createHtmlTag('textarea', $name, $attributes, $content);

        return $result;
    }

    /**
     * 新增HTML标签
     * @param string $type
     * @param string $name
     * @param array $attributes
     * @param bool $content
     * @return string
     */
    protected function createHtmlTag($type, $name, array $attributes = array(), $content = true)
    {
        $type = strtolower($type);

        if ($name) {
            $array = is_array($name) ? $name : array($name, $name);
            $name = reset($array);
            $id = isset($array[1]) ? $array[1] : null;
            $array = $id ? compact('name', 'id') : array('name' => $name);
            $attributes = array_merge($array, $attributes);
        }

        $html = sprintf("<%s%s", $type, $this->getAttr($attributes));

        if ($content) {
            $content = $content === true ? false : $content;
            $content = ">{$content}</{$type}>";
        } else {
            $content = $content === false ? '/>' : '>';
        }

        return $html . $content;
    }

    /**
     * 新增HTML元素
     * @param string $type
     * @param array $attributes
     * @param bool $content
     * @return string
     */
    public function createElement($type, array $attributes = array(), $content = true)
    {
        return $this->createHtmlTag($type, false, $attributes, $content);
    }

    /**
     * 新增结束HTML标记
     * @param string $name
     * @return string
     */
    public function createEndHtmlTag($name)
    {
        return '</' . $name . '>';
    }

    /**
     * 属性连接串
     * @param array $attributes
     * @return bool|string
     */
    public function getAttr(array $attributes)
    {
        $str = false;
        foreach ($attributes as $key => $value) {
            $str = $str . OC_SPACE . "{$key}=\"$value\"";
        }
        return $str;
    }
}