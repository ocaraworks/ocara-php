<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   文件上传插件Upload
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Service;
use Ocara\ServiceBase;
use Ocara\Config;

class Upload extends ServiceBase
{
	/**
	 * @var $rules 文件类型和大小规则数组
	 * @var $maxSize 系统允许上传的文件大小最大值
	 */
	public $rules;
	public $savePath;
	public $maxSize;

	private $_files;

	/**
	 * 析构函数
	 * @param string $savePath
	 * @param array $rules
	 */
	public function __construct($savePath = false, array $rules = array())
	{
		$this->maxSize = floatval(@ini_get('upload_max_filesize'));
		$this->setSavePath($savePath);
		$this->setRules($rules);
	}

	/**
	 * 设置上传路径
	 * @param string $savePath
	 * @param string $prefix
	 * @param integer $perm
	 */
	public function setSavePath($savePath, $prefix = false, $perm = 0777)
	{
		if (empty($savePath)) {
			$savePath = ocPath('attachments', 'upload');
		}

		if (!ocCheckPath($savePath, $perm)) {
			$this->setError('failed_set_dir');
			return false;
		}
		if (!is_writable($savePath)) {
			$this->setError('no_dir_write_perm');
			return false;
		}
		
		$this->savePath = ocDir($savePath);
		$this->prefix = $prefix;
		
		return true;
	}
	
	/**
	 * 设置允许上传的文件类型
	 * @param array $rules
	 */
	public function setRules(array $rules)
	{
		foreach ($rules as $value) {
			if (is_string($value) && $value){
				$info = explode(',', $value);
				$this->rules[$info[0]] = floatval(ocGet(1, $info, $this->maxSize)); 
			}
		}
	}
	
	/**
	 * 是否存在上传的文件
	 */
	public function existsFile()
	{
		return count($_FILES) > 0 ? true : false;
	}
	
	/**
	 * 上传文件
	 * @param bool $required
	 */
	public function upload($required = false)
	{
		if (!is_writable($this->savePath)) {
			return $this->setError('un_writable', array(false));
		}
		
		$this->_files = array();
		
		if (count($_FILES) <= 0) {
			if ($required) {
				return $this->setError('no_upload_file', array(false));
			} else {
				return array('save_path' => null);
			}
		}
		
		foreach ($_FILES as $index => $fileInfo) {
			if (is_array($fileInfo['name'])) {
				$count = count($fileInfo['name']);
				for ($i = 0;$i < $count;$i++) {
					$row = array(
						'name' 		=> $fileInfo['name'][$i], 
						'type' 		=> $fileInfo['type'][$i], 
						'tmp_name' 	=> $fileInfo['tmp_name'][$i], 
						'error' 	=> $fileInfo['error'][$i], 
						'size' 		=> $fileInfo['size'][$i]
					);
					$this->_files[$index][$i] = $this->_check($row);
					if (empty($this->_files[$index][$i])) {
						return false;
					}
				}
			} elseif (is_string($fileInfo['name'])) {
				$this->_files[$index] = $this->_check($fileInfo);
				if (empty($this->_files[$index])) {
					return false;
				}
			}
		}
		
		return $this->_uploadAllFile($_FILES);
	}

	/**
	 * 清理上传过的文件
	 * @param array $path
	 */
	public function clear($path)
	{
		$path = $path ? $path : $_FILES;
		
		if (is_array($path) && $path) foreach ($path as $row) {
			@unlink($row);
		}
	}
	
	/**
	 * 上传所有文件
	 * @param array $files
	 */
	private function _uploadAllFile($files)
	{
		if (!(is_array($files) && $files)) return false;
		
		$path = array();

		foreach ($files as $index => $fileInfo) {
			if (is_array($fileInfo['name'])) {
				$count = count($fileInfo['name']);
				for ($i = 0;$i < $count;$i++) {
					$row = $this->_files[$index][$i];
					if (!$this->_uploadFile($row, $path, $index, $i)) {
						return $this->setError('failed', array($row['name']));
					}
					$path[] = $row['save_path'];
				}
			} elseif (is_string($fileInfo['name'])) {
				$row = $this->_files[$index];
				if (false === $this->_uploadFile($row, $path, $index, false)) {
					return $this->setError('failed', array($row['name']));
				}
				$path[] = $row['save_path'];
			}
		}
		
		return $this->_files;
	}

	/**
	 * 上传某个文件
	 * @param array $row
	 * @param string $path
	 * @param integer $index
	 * @param integer|bool $i
	 */
	private function _uploadFile($row, $path, $index, $i)
	{
		$key = is_integer($i) ? $index . '.' . $i : $index;
		$save_path = $row['save_path'];

		if ($row['tmp_name'] && ocKeyExists($key, $this->_files)) {
			if (!move_uploaded_file($row['tmp_name'], $save_path)) {
				$this->clear($path);
				return false;
			}
			$row['save_path'] = str_replace(OC_ROOT, OC_DIR_SEP, $save_path);
			ocDel($row, 'tmp_name');
			if (is_integer($i)) {
				$this->_files[$index][$i] = $row;
			} else {
				$this->_files[$index] = $row;
			}
		}
		
		return true;
	}

	/**
	 * 检查文件的合法性
	 * @param string $file
	 */
	protected function _check($file)
	{
		extract($file);
		
		if (empty($name)) {
			$file['save_name'] = $file['save_path'] = null;
			return $file;
		}
		
		if ($tmp_name) {
			if (!is_uploaded_file($tmp_name)) {
				return $this->setError('invalid_upload_file', array($name));
			}
		} else {
			return $this->setError('empty_file', array($name));
		}
		
		$filenameInfo = explode('.', $name);
		$fileType = count($filenameInfo) >= 2 ? end($filenameInfo) : false;
		
		if (empty($fileType)) {
			return $this->setError('empty_file_type', array($name));
		}

		$fileNewName  = $this->prefix . md5(date(Config::DATETIME) . mt_rand(1, 999999)) . '.' . $fileType;
		
		if ($size == 0) {
			return $this->setError('not_exists_file', array($name));
		}
	
		if (is_array($this->rules)) {
			if (!array_key_exists($fileType, $this->rules)) {
				return $this->setError('forbidden_type', array(
					$name, implode(OC_DIR_SEP, array_keys($this->rules))
				));
			} 
			$allowSize = $this->rules[$fileType];
			if ($size / 1024 / 1024 > $allowSize) {
				return $this->setError('invalid_filesize', array($name, $allowSize . 'M'));
			}
		} else {
			if ($this->maxSize && $size / 1024 / 1024 > $this->maxSize) {
				return $this->setError('exceed_filesize', array($name, $this->maxSize . 'M'));
			}
		}
		
		$file['save_name'] = $fileNewName;
		$file['save_path'] = $this->savePath . $fileNewName;
		
		return $file;
	}
}
