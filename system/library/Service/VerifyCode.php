<?php
/**
 * 验证码生成插件类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Service;

use Ocara\Core\ServiceBase;
use Ocara\Exceptions\Exception;

class VerifyCode extends ServiceBase
{
    protected $width;
    protected $height;
    protected $imgObj;
    protected $extName;
    protected $sessionName;

    public $mimes = array(
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'wbmp' => 'image/vnd.wap.wbmp'
    );

    public $functions = array(
        'jpg' => 'imagejpeg',
        'jpeg' => 'imagejpeg',
        'png' => 'imagepng',
        'gif' => 'imagegif',
        'wbmp' => 'imagewbmp'
    );

    /**
     * 析构函数
     * VerifyCode constructor.
     * @param string $name
     * @throws Exception
     */
    public function __construct($name = null)
    {
        ocCheckExtension('gd');

        if (is_string($name) && $name) {
            $this->sessionName = $name;
        } else {
            $this->sessionName = ocConfig('VERIFY_CODE_NAME', 'OCSESSCODE');
        }
    }

    /**
     * 画背景
     * @param float $width
     * @param float $height
     * @param string $source
     * @throws Exception
     */
    public function drawBack($width, $height, $source = null)
    {
        $this->width = $width;
        $this->height = $height;
        $this->imgObj = imagecreatetruecolor($width, $height);

        if ($path = ocFileExists($source)) {
            $fileInfo = explode('.', ocBasename($path));
            $this->extName = $fileInfo[1];

            if (!key_exists($this->extName, $this->mimes)) {
                $this->showError('invalid_image_extname');
            }

            $createFunc = 'imagecreatefrom' . ($this->extName == 'jpg' ? 'jpeg' : $this->extName);
            $this->srcObj = $createFunc($path);

            imagecopy($this->imgObj, $this->srcObj, 0, 0, 0, 0, $this->width, $this->height);
        } else {
            if (!preg_match('/^#[0-9A-Fa-f]{6}$/i', $source, $mt)) {
                $source = '#000000';
            }

            $this->extName = 'jpg';
            $color = $this->parseColor($source);
            $color = @imagecolorallocate($this->imgObj, $color[0], $color[1], $color[2]);

            imagefilledrectangle($this->imgObj, 0, 0, $width, $height, $color);
        }
    }

    /**
     * 画线条
     * @param string $color
     * @param float $startX
     * @param float $startY
     * @param float $endX
     * @param float $endY
     * @return bool
     * @throws Exception
     */
    public function drawLine($color, $startX, $startY, $endX = null, $endY = null)
    {
        if (!is_resource($this->imgObj)) return false;
        $color = $this->parseColor($color);
        $color = @imagecolorallocate($this->imgObj, $color[0], $color[1], $color[2]);

        if (empty($endX)) {
            $endX = $this->width - $startX;
        }

        if (empty($endY)) {
            $endY = $startY;
        }

        return imageline($this->imgObj, $startX, $startY, $endX, $endY, $color);
    }

    /**
     * 画文字
     * @param int $length
     * @param array $format
     * @param array $left
     * @throws Exception
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

        $type = !empty($format['type']) ? $format['type'] : 'both';
        $filter = !empty($format['filter']) ? $format['filter'] : array();
        $verifyCode = ocService()->code->getRandCode($type, $length, $filter);

        $this->addText($verifyCode, $format, $left);
        $_SESSION[$this->sessionName] = $verifyCode;
    }

    /**
     * 输出图片
     * @throws Exception
     */
    public function output()
    {
        if (!key_exists($this->extName, $this->functions)) {
            $this->showError('invalid_output_type');
        }

        header('Content-Type:' . $this->mimes[$this->extName]);

        $imageFunc = $this->functions[$this->extName];

        ocService()->response->setBody($imageFunc($this->imgObj));
    }

    /**
     * 获取验证码
     * @return mixed
     * @throws Exception
     */
    public function getCode()
    {
        if (array_key_exists($this->sessionName, $_SESSION) == false) {
            $this->showError('lost_code');
        }

        return $_SESSION[$this->sessionName];
    }

    /**
     * 检查验证码是否正确
     * @param string $code
     * @return bool
     * @throws Exception
     */
    public function checkCode($code)
    {
        return strtolower($this->getCode()) == strtolower($code);
    }

    /**
     * 添加文字
     * @param string $content
     * @param array $fontInfo
     * @param array $location
     * @throws Exception
     */
    protected function addText($content, array $fontInfo = array(), array $location = array())
    {
        $size = !empty($fontInfo['size']) ? $fontInfo['size'] : 12;
        $color = !empty($fontInfo['color']) ? $fontInfo['color'] : '#FFFFFF';
        $font = array_key_exists('font', $fontInfo) ? $fontInfo['font'] : null;
        $font = ocService()->font->get($font);

        $contentInfo = imagettfbbox($size, 0, $font, $content);
        $contentW = $contentInfo[4] - $contentInfo[6];
        $contentH = $contentInfo[3] - $contentInfo[5];

        if ($location && count($location) >= 2) {
            list($startX, $startY) = $location;
        } else {
            $startX = ($this->width - $contentW) / 2;
            $startY = ($this->height - $contentH) / 2;
            if ($font) $startY += $contentH;
        }

        $color = $this->parseColor($color);
        $color = @imagecolorallocate($this->imgObj, $color[0], $color[1], $color[2]);

        if (!$font) {
            imagestring(
                $this->imgObj, $size, $startX, $startY, $content, $color
            );
        } else {
            imagettftext(
                $this->imgObj, $size, 0, $startX, $startY, $color, $font, $content
            );
        }
    }

    /**
     * 获取颜色值信息
     * @param string $color
     * @return array
     * @throws Exception
     */
    protected function parseColor($color)
    {
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            $this->showError('fault_color');
        }

        $colorArray = array();
        $valueArray = explode(',', rtrim(chunk_split(substr($color, 1), 2, ','), ','));

        for ($i = 0; $i < count($valueArray); $i++) {
            $colorArray[$i] = base_convert($valueArray[$i], 16, 10);
        }

        return $colorArray;
    }

    /**
     * 清除验证码
     */
    public function clear()
    {
        ocDel($_SESSION, $this->sessionName);
        imagedestroy($this->imgObj);
    }
}
