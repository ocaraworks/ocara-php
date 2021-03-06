<?php
/**
 * 表单类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use Ocara\Exceptions\Exception;

class Form extends Base
{
    /**
     * @var $tokenInfo 表单令牌信息
     * @var $lang 表单字段名语言
     * @var $maps 表单字段名映射规则
     */
    protected $plugin = null;

    private $name;
    private $tokenInfo;

    private $models = array();
    private $lang = array();
    private $maps = array();
    private $attributes = array();
    private $elements = array();

    /**
     * 初始化
     * Form constructor.
     * @param string $name
     * @throws Exception
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->setPlugin(ocService()->html);

        $this->init();
    }

    /**
     * 生成表单
     * @param string $action
     * @param array $attributes
     * @return $this
     */
    public function init($action = null, array $attributes = array())
    {
        $initAttributes = array(
            'id' => $this->name,
            'name' => $this->name,
            'action' => $action ?: '#',
        );

        $this->attributes = array_merge($this->attributes, $initAttributes, $attributes);
        $this->method('POST');

        return $this;
    }

    /**
     * 设置表单的提交方式
     * @param string $method
     * @return $this
     */
    public function method($method = 'POST')
    {
        $method = strtolower($method) == 'get' ? 'GET' : 'POST';
        $this->attributes['method'] = $method;
        return $this;
    }

    /**
     * 设置上传表单
     * @return $this
     */
    public function upload()
    {
        $this->attributes['enctype'] = 'multipart/form-data';
        return $this;
    }

    /**
     * 获取表单属性
     * @param $attr
     * @return mixed|null
     */
    public function getAttr($attr)
    {
        return array_key_exists($attr, $this->attributes) ? $this->attributes[$attr] : null;
    }

    /**
     * 获取表单标识
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 设置表单令牌
     * @param array $tokenInfo
     */
    public function setToken(array $tokenInfo)
    {
        $this->tokenInfo = $tokenInfo;
    }

    /**
     * 获取表单令牌
     * @return mixed
     */
    public function getToken()
    {
        return $this->tokenInfo;
    }

    /**
     * 删除表单令牌
     * @return null
     */
    public function deleteToken()
    {
        return $this->tokenInfo = null;
    }

    /**
     * 表单开始
     * @return string
     * @throws Exception
     */
    public function begin()
    {
        $plugin = $this->plugin();
        $tokenElement = null;

        if ($this->tokenInfo) {
            $tokenElement = $plugin->input(
                'hidden',
                $this->tokenInfo['name'],
                $this->tokenInfo['value']
            );
        }

        $formElement = $plugin->createElement('form', $this->attributes, null);
        $begin = $formElement . PHP_EOL . "\t" . $tokenElement;

        return $begin . PHP_EOL;
    }

    /**
     * 表单结束
     * @return string
     * @throws Exception
     */
    public function end()
    {
        return $this->plugin()->createEndHtmlTag('form') . PHP_EOL;
    }

    /**
     * 添加关联Model
     * @param string $class
     * @param string $alias
     * @return $this
     * @throws Exception
     */
    public function model($class, $alias = null)
    {
        if (!class_exists($class)) {
            $class = 'app\model\database\\' . ltrim($class, '\\');
            if (!class_exists($class)) {
                ocService()->error->show('not_exists_model_class', array($class));
            }
        }

        if (!$alias) {
            $modelClass = substr($class, strrpos($class, OC_NS_SEP) + 1);
            $alias = lcfirst(ocStripTail($modelClass, ocConfig('MODEL_SUFFIX')));
        }

        $this->lang = array_merge($this->lang, $class::getConfig('LANG'));
        $this->maps = array_merge($this->maps, $class::getConfig('MAPS'));
        $this->models[$alias] = $class;

        return $this;
    }

    /**
     * 获取或修改字段语言
     * @param string $field
     * @param string $value
     * @return array|bool|mixed|null
     */
    public function lang($field, $value = null)
    {
        $lang = $this->fieldConfig('lang', $field, $value);
        return empty($lang) ? $field : $lang;
    }

    /**
     * 获取或修改字段映射
     * @param string $field
     * @param string $value
     * @return array|bool|mixed|null
     */
    public function map($field, $value = null)
    {
        return $this->fieldConfig('map', $field, $value);
    }

    /**
     * 获取或修改设置
     * @param string $type
     * @param string $field
     * @param null $value
     * @return array|bool|mixed|null
     */
    protected function fieldConfig($type, $field, $value = null)
    {
        $property = $type;
        $config = $this->$property;

        if (isset($value)) {
            return $config[$field] = $value;
        }

        $fields = explode('.', $field);
        if (!isset($fields[1])) {
            return isset($config[$fields[0]]) ? $config[$fields[0]] : null;
        }

        $field = $fields[1];
        if (isset($this->models[$fields[0]])) {
            $model = $this->models[$fields[0]];
        } else {
            $model = $fields[0]();
        }

        $result = $model::getConfig(strtoupper($type), $field);
        return $result;
    }

    /**
     * 获取绑绑定的模型
     * @return array
     */
    public function getModels()
    {
        return $this->models ?: array();
    }

    /**
     * 获取表单元素
     * @param string $name
     * @return array|mixed|null
     */
    public function element($name = null)
    {
        if (isset($name)) {
            $element = null;
            if (isset($this->maps[$name])) {
                $name = $this->maps[$name];
            }
            if (!empty($this->elements[$name])) {
                $element = $this->elements[$name];
                if (is_array($element)) {
                    if (count($element) == 1) {
                        $element = $element[0];
                    }
                }
            }
            return $element;
        }

        return $this->elements;
    }

    /**
     * 用未定义的方法
     * @param string $name
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    public function __call($name, $params)
    {
        $plugin = $this->plugin(false);

        if (is_object($plugin) && method_exists($plugin, $name)) {
            $html = call_user_func_array(array(&$plugin, $name), $params);
            if ($id = reset($params)) {
                $id = is_array($id) && $id ? reset($id) : $id;
                $this->elements[$id][] = $html;
            }
            return $html;
        }

        return parent::__call($name, $params);
    }
}