<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   表单生成类Form
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

use Ocara\Model\Database as DatabaseModel;

defined('OC_PATH') or exit('Forbidden!');

class Form extends Base
{
	/**
	 * @var $_id  表单标识
	 * @var $_token 表单令牌
	 * @var $_lang 表单字段名语言
	 * @var $_map 表单字段名映射规则
	 * @var $_validate 表单验证规则
	 */
	protected $_plugin = null;

	private $_sign;
	private $_token;
	private $_tokenTag;
	private $_validateForm = true;
	
	private $_modelInfo = array();
	private $_lang = array();
	private $_map = array();
	private $_attributes = array();
	private $_elements = array();

	/**
	 * 初始化
	 * @param string $name
	 */
	public function initialize($name)
	{
		$this->_sign = $name;
		$this->_validateForm = true;
		$this->_plugin = new Html();

		$this->init();
	}

	/**
	 * 生成表单
	 * @param string $action
	 * @param array $attributes
	 * @param string $method
	 */
	public function init($action = false, array $attributes = array())
	{
		$this->_attributes = array(
			'id'     => $this->_sign,
			'name'   => $this->_sign,
			'action' => $action ? $action : '#',
		);

		$this->method('POST');
		$this->_attributes = array_merge($this->_attributes, $attributes);

		return $this;
	}

	/**
	 * 设置表单的提交方式
	 * @param string $method
	 */
	public function method($method = 'POST')
	{
		$method = strtolower($method) == 'get' ? 'GET' : 'POST';
		$this->_attributes['method'] = $method;
		return $this;
	}

	/**
	 * 设置上传表单
	 */
	public function upload()
	{
		$this->_attributes['enctype'] = 'multipart/form-data';
		return $this;
	}

	/**
	 * 获取表单属性
	 * @param string $attr
	 */
	public function getAttr($attr)
	{
		return array_key_exists($attr, $this->_attributes) ? $this->_attributes[$attr] : null;
	}

	/**
	 * 获取表单标识
	 */
	public function getSign()
	{
		return $this->_sign;
	}

	/**
	 * 设置token
	 * @param string $tokenTag
	 * @param string $token
	 */
	public function setToken($tokenTag, $token)
	{
		$this->_tokenTag = $tokenTag;
		$this->_token = $token;
	}

	/**
	 * 表单开始
	 */
	public function begin()
	{
		$begin = $this->_plugin->createElement('form', $this->_attributes, null);
		$token = $this->_plugin->input('hidden', $this->_tokenTag, $this->_token);
		$begin = $begin . OC_ENTER . "\t" . $token;

		$this->loadModel();
		return $begin . OC_ENTER;
	}

	/**
	 * 加载Model的配置
	 */
	public function loadModel()
	{
		foreach ($this->_modelInfo as $key => $class) {
			$this->_lang = array_merge($this->_lang, DatabaseModel::getConfig('LANG', null, $class));
			$this->_map =array_merge($this->_map, DatabaseModel::getConfig('MAP', null, $class));
		}

		return $this;
	}

	/**
	 * 表单开始
	 */
	public function end()
	{
		return $this->end = $this->_plugin->createEndHtmlTag('form') . OC_ENTER;
	}

	/**
	 * 添加关联Model
	 * @param string $name
	 * @param string $server
	 * @param bool $required
	 */
	public function model($class, $alias = null)
	{
		$alias = $alias ? $alias : $class;
		$this->_modelInfo[$alias] = $class;
		return $this;
	}

	/**
	 * 获取或修改字段语言
	 * @param string $field
	 * @param string $value
	 */
	public function lang($field, $value = null)
	{
		return $this->_fieldConfig('lang', $field, $value);
	}

	/**
	 * 获取或修改字段映射
	 * @param string $field
	 * @param string $value
	 */
	public function map($field, $value = null)
	{
		return $this->_fieldConfig('map', $field, $value);
	}

	/**
	 * 获取或修改设置
	 * @param $type
	 * @param $field
	 * @param null $value
	 * @return array|null
	 */
	protected function _fieldConfig($type, $field, $value = null)
	{
		$property = '_' . $type;
		$config = $this->$property;

		if (isset($value)) {
			return $config[$field] = $value;
		}

		$fields = explode('.', $field);
		if (!isset($fields[1])) {
			return ocGet($field[0], $config);
		}

		$field = $fields[1];
		if (isset($this->_modelInfo[$fields[0]])) {
			$class = $this->_modelInfo[$fields[0]];
		} else {
			$class = $fields[0];
		}

		$result = DatabaseModel::getConfig(strtoupper($type), $field, $class);
		return $result;
	}

	/**
	 * 开启/关闭/检测表单验证功能
	 * @param bool|null $validate
	 */
	public function validateForm($validate = null)
	{
		if ($validate === null) {
			return $this->_validateForm;
		}
		$this->_validateForm = $validate ? true : false;
		return $this;
	}

	/**
	 * 表单验证
	 * @param $validator
	 * @param array $data
	 */
	public function validate(Validator &$validator, array $data)
	{
		$this->loadModel();
		foreach ($this->_modelInfo as $alias => $class) {
			if (!$validator->validate($data, $class)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * 获取表单元素
	 * @param string $name
	 */
	public function element($name = null)
	{
		if (func_num_args()) {
			$element = null;
			if (isset($this->_map[$name])) {
				$name = $this->_map[$name];
			}
			if (!empty($this->_elements[$name])) {
				$element = $this->_elements[$name];
				if  (is_array($element)){
					if (count($element) == 1) {
						$element = $element[0];
					}
				}
			}
			return $element;
		}

		return $this->_elements;
	}
	
	/**
	 * 魔术方法-调用未定义的方法
	 * @param string $name
	 * @param array $params
	 */
	public function __call($name, $params)
	{
		if (is_object($this->_plugin) && method_exists($this->_plugin, $name)) {
			$html = call_user_func_array(array(&$this->_plugin, $name), $params);
			if ($id = reset($params)) {
				$id = is_array($id) && $id ? reset($id) : $id;
				$this->_elements[$id][] = $html;
			}
			return $html;
		}

		Error::show('no_method', array($name));
	}
}