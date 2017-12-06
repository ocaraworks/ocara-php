<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架    自定义缓存插件FileCache
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Service;
use Ocara\ServiceBase;

class FileCache extends ServiceBase
{
	private $_format = true;
	private $_content;
	private $_data;
	
	/**
	 * 设置数据
	 * @param string|array $data
	 * @param string $name
	 * @param string $decription
	 */
	public function setData($data, $name = false, $decription = false)
	{
		$content = false;
		
		if ($decription) {
			$content .= "/**\r\n * {$decription}\r\n */\r\n";
		}
		
		$content .= ($name ? "\${$name} = " : "return ");
		$content .= "%s;\r\n";

		$this->_content = $content;
		$this->_data = $data;
	}

	/**
	 * 是否格式化数组
	 * @param bool $format
	 */
	public function format($format = true)
	{
		$this->_format = $format ? true : false;
	}

	/**
	 * 保存数据
	 * @param $filePath
	 * @param bool $append
	 * @param integer $perm
	 */
	public function save($filePath, $append = false, $perm = false)
	{
		$content = false;
		
		if (is_string($this->_data)) {
			$content = '"' . $this->_data . '"';
		} elseif (is_array($this->_data)) {
			if ($this->_format) {
				$content = "array(\r\n";
				$content .= $this->_writeArray($this->_data);
				$content .= ")";
			} else {
				$content = var_export($this->_data, true);
			}
		} 

		$content = sprintf($this->_content, $content);
		$content = ($append ? "\r\n" : "<?php\r\n") . $content;
		
		ocWrite($filePath, $content, $append, $perm);
	}

	/**
	 * 读取缓存内容
	 * @param string $filePath
	 * @param string $name
	 */
	public function read($filePath, $name = false)
	{
		if ($filePath = ocFileExists($filePath, true)) {
			$result = include ($filePath);
			if ($name) {
				if (array_key_exists($name, get_defined_vars())) {
					return $$name;
				}
			} else {
				return $result;
			}
		}
		
		return false;
	}

	/**
	 * 内部函数-写入数组
	 * @param array $array
	 * @param integer $tNum
	 */
	protected function _writeArray($array, $tNum = 0)
	{
		$tNum = $tNum + 1;
		$tab = str_repeat("\t", $tNum);
		$str = OC_EMPTY;
		$index = 0;
		$lastIndex = count($array) - 1;
		
		foreach ($array as $key => $value) {
			$str .= $tab;
			if ($this->_isAssoc($array)) {
				$str .= "'$key' => ";
			}
			if (is_array($value)) {
				$str .= "array(\r\n";
				
				$str .= $this->_writeArray($value, $tNum);
				$str .= $tab . ')';
				if ($index != $lastIndex) {
					$str .= ',';
				}
				$str .= "\r\n";
			} else {
				$value = gettype($value) == 'string' ? "'" . addcslashes($value, "'\\") . "'" : $value;
				$str .= "$value";
				if ($index != $lastIndex) {
					$str .= ',';
				}
				$str .= "\r\n";
			}
			$index++;
		}
		
		return $str;
	}

	/**
	 * 内部函数-判断是否为关联数组
	 * 如果使用数字型键作关联数组，第一条记录键值必须是1而不是0
	 * 因为通常数字型数组都是从0开始的.
	 * @param array $array
	 */
	protected function _isAssoc($array)
	{
		return array_keys($array) !== range(0, count($array) - 1);
	}
}
