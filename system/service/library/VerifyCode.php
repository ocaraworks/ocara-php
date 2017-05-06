<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   验证码生成插件VerifyCode
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Service;
use Ocara\ServiceBase;
use Ocara\Font;

class VerifyCode extends ServiceBase
{
	protected $_width;
	protected $_height;
	protected $_imgObj;
	protected $_extName;
	protected $_sessionName;
	
	public $mimes = array(
		'jpg' => 'image/jpeg', 
		'jpeg' => 'image/jpeg', 
		'png' => 'image/png', 
		'gif' => 'image/gif', 
		'wbmp' => 'image/vnd.wap.wbmp'
	);
	
	public $functions = array(
		'jpg' 	=> 'imagejpeg', 
		'jpeg' 	=> 'imagejpeg', 
		'png' 	=> 'imagepng', 
		'gif' 	=> 'imagegif', 
		'wbmp' 	=> 'imagewbmp'
	);

	/**
	 * 析构函数
	 * @param string $name
	 */
	public function __construct($name = false)
	{
		ocCheckExtension('gd');

		if (is_string($name) && $name) {
			$this->_sessionName = $name;
		} else {
			$this->_sessionName = ocConfig('VERIFYCODE_NAME', 'OCSESSCODE');
		}
	}

	/**
	 * 画背景
	 * @param @figure $width
	 * @param @figure $height
	 * @param string $source
	 */
	public function drawBack($width, $height, $source = false)
	{
		$this->_width = $width;
		$this->_height = $height;
		
		$this->_imgObj = imagecreatetruecolor($width, $height);
		
		if ($path = ocFileExists($source, true)) 
		{
			$fileInfo = explode('.', ocBasename($path));
			$this->_extName = $fileInfo[1];
			if (!key_exists($this->_extName, $this->mimes)) {
				$this->showError('invalid_image_extname');
			}
			$createFunc = 'imagecreatefrom' . ($this->_extName == 'jpg' ? 'jpeg' : $this->_extName);
			$this->srcObj = $createFunc($path);
			imagecopy($this->_imgObj, $this->srcObj, 0, 0, 0, 0, $this->_width, $this->_height);
		} 
		else {
			if (!preg_match('/^#[0-9A-Fa-f]{6}$/i', $source, $mt)) {
				$source = '#000000';
			}
			$this->_extName = 'jpg';
			$color = $this->_parseColor($source);
			$color = @imagecolorallocate($this->_imgObj, $color[0], $color[1], $color[2]);
			imagefilledrectangle($this->_imgObj, 0, 0, $width, $height, $color);
		}
	}

	/**
	 * 画线条
	 * @param string $color
	 * @param @figure $startX
	 * @param @figure $startY
	 * @param @figure $endX
	 * @param @figure $endY
	 */
	public function drawLine($color, $startX, $startY, $endX = false, $endY = false)
	{
		if (!is_resource($this->_imgObj)) return false;
		$color = $this->_parseColor($color);
		$color = @imagecolorallocate($this->_imgObj, $color[0], $color[1], $color[2]);
		
		if (empty($endX)) {
			$endX = $this->_width - $startX;
		}
		
		if (empty($endY)) {
			$endY = $startY;
		} 
		
		return imageline($this->_imgObj, $startX, $startY, $endX, $endY, $color);
	}
	
	/**
	 * 画文字
	 * @param @figure $length
	 * @param array $format
	 * @param array $left
	 */
	public function drawText($length, $format = array(), array $left = array())
	{
		if (!is_array($format)) {
			if ($format) {
				$format = array('color' => $format);
			} else {
				$format = array();
			}
		}

		$type       = ocGet('type', $format, 'both');
		$filter     = ocGet('filter', $format);
		$verifyCode = Code::getCaptcha($type, $length, $filter);

		$this->_addText($verifyCode, $format, $left);
		$_SESSION[$this->_sessionName] = $verifyCode;
	}
	
	/**
	 * 输出图片
	 */
	public function output()
	{
		if (!key_exists($this->_extName, $this->functions)) {
			$this->showError('invalid_output_type');
		}
		
		header('Content-Type:' . $this->mimes[$this->_extName]);
		
		$imageFunc = $this->functions[$this->_extName];
		
		echo $imageFunc($this->_imgObj);
	}
	
	/**
	 * 获取验证码
	 */
	public function getCode()
	{
		if (array_key_exists($this->_sessionName, $_SESSION) == false) {
			$this->showError('lost_code');
		}
		
		return $_SESSION[$this->_sessionName];
	}

	/**
	 * 检查验证码是否正确
	 * @param string $code
	 */
	public function checkCode($code)
	{
		return strtolower($this->getCode()) == strtolower($code);
	}

	/**
	 * 添加文字
	 * @param string $string
	 * @param array $fontInfo
	 * @param array $location
	 */
	protected function _addText($content, array $fontInfo = array(), array $location = array())
	{
		$size 	 = ocGet('size', $fontInfo, 12);
		$color 	 = ocGet('color', $fontInfo, '#FFFFFF');
		$font 	 = Font::get(ocGet('font', $fontInfo));

		$contentInfo = imagettfbbox($size, 0, $font, $content);
		$contentW 	 = $contentInfo[4] - $contentInfo[6];
		$contentH 	 = $contentInfo[3] - $contentInfo[5];
		
		if ($location && count($location) >= 2) {
			list($startX, $startY) = $location;
		} else {
			$startX = ($this->_width - $contentW) / 2;
			$startY = ($this->_height - $contentH) / 2;
			if ($font) $startY += $contentH;
		}

		$color = $this->_parseColor($color);
		$color = @imagecolorallocate($this->_imgObj, $color[0], $color[1], $color[2]);

		if (!$font) {
			imagestring(
				$this->_imgObj, $size, $startX, $startY, $content, $color
			);
		} else {
			imagettftext(
				$this->_imgObj, $size, 0, $startX, $startY, $color, $font, $content
			);
		}
	}

	/**
	 * 获取颜色值信息
	 * @param string $color
	 */
	protected function _parseColor($color)
	{
		if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
			$this->showError('fault_color');
		}
		
		$colorArray = array();
		$valueArray = explode(',', rtrim(chunk_split(substr($color, 1), 2, ','), ','));
		
		for ($i = 0;$i < count($valueArray);$i++) {
			$colorArray[$i] = base_convert($valueArray[$i], 16, 10);
		}
		
		return $colorArray;
	}

	/**
	 * 清除验证码
	 */
	public function clear()
	{
		ocDel($_SESSION, $this->_sessionName);
		imagedestroy($this->_imgObj);
	}
}
