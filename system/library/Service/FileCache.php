<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架    自定义缓存插件FileCache
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Service;

use Ocara\Core\ServiceBase;

class FileCache extends ServiceBase
{
	private $format = true;
	private $content;
	private $data;
	
	/**
	 * 设置数据
	 * @param string|array $data
	 * @param string $name
	 * @param string $description
	 */
	public function setData($data, $name = null, $description = null)
	{
		$content = null;
		
		if ($description) {
			$content .= "/**\r\n * {$description}\r\n */\r\n";
		}
		
		$content .= ($name ? "\${$name} = " : "return ");
		$content .= "%s;\r\n";

		$this->content = $content;
		$this->data = $data;
	}

	/**
	 * 是否格式化数组
	 * @param bool $format
	 */
	public function format($format = true)
	{
		$this->format = $format ? true : false;
	}

	/**
	 * 保存数据
	 * @param $filePath
	 * @param bool $append
	 * @param integer $perm
	 */
	public function save($filePath, $append = false, $perm = null)
	{
		$content = null;

		if (is_string($this->data)) {
			$content = '"' . $this->data . '"';
		} elseif (is_array($this->data)) {
			if ($this->format) {
				$content = "array(\r\n";
				$content .= $this->writeArray($this->data);
				$content .= ")";
			} else {
				$content = var_export($this->data, true);
			}
		} 

		$content = sprintf($this->content, $content);
		$content = ($append ? "\r\n" : "<?php\r\n") . $content;

		ocWrite($filePath, $content, $append, $perm);
	}

    /**
     * 读取缓存内容
     * @param $filePath
     * @param null $name
     * @return bool|mixed
     */
	public function read($filePath, $name = null)
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
     * @param $array
     * @param int $tNum
     * @return string
     */
	protected function writeArray($array, $tNum = 0)
	{
		$tNum = $tNum + 1;
		$tab = str_repeat("\t", $tNum);
		$str = OC_EMPTY;
		$index = 0;
		$lastIndex = count($array) - 1;
		
		foreach ($array as $key => $value) {
			$str .= $tab;
			if (ocAssoc($array)) {
				$str .= "'$key' => ";
			}
			if (is_array($value)) {
				$str .= "array(\r\n";
				
				$str .= $this->writeArray($value, $tNum);
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
}
