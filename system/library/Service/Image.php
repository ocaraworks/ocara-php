<?php
/**
 * 图片处理插件类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Service;

use Ocara\Core\ServiceBase;
use Ocara\Exceptions\Exception;

class Image extends ServiceBase
{
    /**
     * @var $srcName 源图片主文件名
     * @var $srcExtName 源图片扩展名
     * @var $srcObj 源图片资源句柄
     * @var $dstObj 目标图片资源句柄
     * @var $srcPath 源图片路径
     * @var $dstPath 目标图片路径
     */
    public $srcName;
    public $srcExtName;
    public $dstName;
    public $dstExtName;
    public $srcObj;
    public $dstObj;
    public $srcPath;
    public $dstPath;
    public $sysImageType;
    public $srcTemp;

    public $srcWidth;
    public $srcHeight;
    public $font;

    /**
     * 水印位置
     */
    public static $locationRule = array(
        'left-up',        //左上角
        'left-down',    //左下角
        'left-middle',    //左边垂直居中
        'right-up',    //右上角
        'right-down',    //右下角
        'right-middle', //右边垂直居中
        'up-center',    //顶部居中
        'down-center',    //底部居中
        'middle',        //水平垂直居中
    );

    /**
     * 图片格式
     */
    public static $imageTypes = array(
        'jpg' => array('imagejpeg', 'image/jpeg', IMG_JPG),
        'jpeg' => array('imagejpeg', 'image/jpeg', IMG_JPEG),
        'png' => array('imagepng', 'image/png', IMG_PNG),
        'gif' => array('imagegif', 'image/gif', IMG_GIF),
        'wbmp' => array('imagewbmp', 'image/vnd.wap.wbmp', IMG_WBMP)
    );

    /**
     * 析构函数
     * 检查GD扩展和系统支持的图像类型
     */
    public function __construct()
    {
        ocCheckExtension('gd');
        $this->sysImageType = imagetypes();
    }

    /**
     * 裁切图片
     * @param integer $clipWidth
     * @param integer $clipHeight
     * @param integer $clipLx
     * @param integer $clipLy
     * @param integer $dstWidth
     * @param integer $dstHeight
     * @param integer $dstLx
     * @param integer $dstLy
     * @return bool|int|void
     * @throws Exception
     */
    public function clipImage(
        $clipWidth,
        $clipHeight,
        $clipLx,
        $clipLy,
        $dstWidth = null,
        $dstHeight = null,
        $dstLx = null,
        $dstLy = null
    )
    {
        $this->checkResource();
        if (!$clipWidth) $this->showError('empty_width');

        if (!$clipHeight) $this->showError('empty_height');

        if (!$dstLx) $dstLx = 0;

        if (!$dstLy) $dstLy = 0;

        $remainWidth = $this->srcWidth - $clipLx;
        $remainHeight = $this->srcHeight - $clipLy;

        if ($remainWidth < $clipWidth) {
            $clipWidth = $remainWidth;
        }

        if ($remainHeight < $clipHeight) {
            $clipHeight = $remainHeight;
        }

        $toW = $clipWidth;
        $toH = $clipHeight;
        $this->dstObj = $this->createImage($toW, $toH);

        if ($dstWidth && $dstHeight) {
            imagecopyresized(
                $this->dstObj, $this->srcObj, $dstLx, $dstLy, $clipLx, $clipLy,
                $dstWidth, $dstHeight, $clipWidth, $clipHeight
            );
        } else {
            imagecopy($this->dstObj, $this->srcObj, $dstLx, $dstLy, $clipLx, $clipLy, $toW, $toH);
        }

        if (!$this->basePrint()) return false;

        $result = $this->baseSave();

        if ($result && $dstWidth && $dstHeight) {
            $srcPath = $this->srcPath;
            $this->setSrcImage($this->dstPath);
            $this->setDstImage($this->dstPath);
            $result = $this->clipImage($dstWidth, $dstHeight, 0, 0);
            $this->setSrcImage($srcPath);
        }

        return $result;
    }

    /**
     * 新建缩略图
     * @param array $proportion
     * @param string $dstDir
     * @return bool|int|void|null
     * @throws Exception
     */
    public function zoomImage($proportion, $dstDir = null)
    {
        $result = null;

        if (!(is_array($proportion) && !empty($proportion))) return false;

        if (!is_resource($this->srcObj)) {
            $this->showError('no_src_image');
        }

        $srcPath = $this->srcPath;
        $dstDir = $dstDir ?: dirname($srcPath);

        $thumb = new static();
        $thumb->setSrcImage($srcPath);

        foreach ($proportion as $key => $val) {
            if (!(is_array($val) && !empty($val))) continue;
            $suffix = $val[0];
            $w = array_key_exists(1, $val) && is_numeric($val[1]) && $val[1] ? $val[1] : $this->srcWidth;
            $h = array_key_exists(2, $val) && is_numeric($val[2]) && $val[2] ? $val[2] : $this->srcHeight;

            if ($w > $thumb->srcWidth) {
                $w = $thumb->srcWidth;
            }

            if ($h > $thumb->srcHeight) {
                $h = $thumb->srcHeight;
            }

            ocCheckPath($dstDir);

            $dstPath = ocDir($dstDir) . $thumb->srcName . $suffix . '.' . $thumb->srcExtName;
            $thumb->setDstImage($dstPath);
            $result = $thumb->clipImage($thumb->srcWidth, $thumb->srcHeight, 0, 0, $w, $h);
        }

        return $result;
    }

    /**
     * 设置源图片
     * @param string $srcPath
     * @return mixed
     * @throws Exception
     */
    public function setSrcImage($srcPath)
    {
        if (!ocFileExists($srcPath)) {
            $this->showError('need_src_image');
        }

        $this->checkImage($srcPath, 'src');

        $createFunc = 'imagecreatefrom' . ($this->srcExtName == 'jpg' ? 'jpeg' : $this->srcExtName);
        $this->srcObj = $createFunc($srcPath);
        $this->srcPath = $srcPath;

        $this->srcWidth = $this->getInfo($this->srcObj, 'w');
        $this->srcHeight = $this->getInfo($this->srcObj, 'h');

        return $this->srcObj;
    }

    /**
     * 设置目标图片
     * @param string $dstPath
     * @param null $perm
     * @throws Exception
     */
    public function setDstImage($dstPath, $perm = null)
    {
        $this->checkImage($dstPath, 'dst');
        $this->checkPath(dirname($dstPath), $perm);
        $this->dstPath = $dstPath;
    }

    /**
     * 添加水印
     * @param array $markInfo
     * @param string $suffix
     * @return array|bool|false
     * @throws Exception
     */
    public function addMark($markInfo, $suffix = null)
    {
        if (!(is_array($markInfo) && !empty($markInfo))) return false;

        if (!is_resource($this->srcObj)) {
            $this->showError('no_src_image');
        }

        $srcPath = $this->srcPath;

        $thumb = new static();
        $thumb->setSrcImage($srcPath);

        if (!array_key_exists(0, $markInfo) || !array_key_exists(1, $markInfo)) {
            $this->showError('fault_mark_param', $markInfo);
        }

        if ($suffix) {
            $dstPath = ocDir(dirname($srcPath)) . $thumb->srcName . $suffix . '.' . $thumb->srcExtName;
        } else {
            $dstPath = $this->getImagePath('dst');
        }

        $thumb->setDstImage($dstPath);
        $thumb->dstObj = $thumb->createImage($thumb->srcWidth, $thumb->srcHeight);

        @imagecopy(
            $thumb->dstObj, $thumb->srcObj, 0, 0, 0, 0, $thumb->srcWidth, $thumb->srcHeight
        );

        if ($thumb->basePrint()) {
            $thumb->baseSave();
        } else {
            $this->showError('failed_create_image');
        }

        $imgW = $this->getInfo($thumb->dstObj, 'w');
        $imgH = $this->getInfo($thumb->dstObj, 'h');

        if ($markInfo[0] == 'image') {
            return $this->imageMark($thumb, $markInfo[1], $imgW, $imgH);
        } elseif ($markInfo[0] == 'text') {
            return $this->textMark($thumb, $markInfo[1], $imgW, $imgH);
        }

        return false;
    }

    /**
     * 删除图片
     * @param string $path
     * @return bool
     */
    public function delImage($path)
    {
        return ($path = ocFileExists($path)) ? unlink($path) : true;
    }

    /**
     * 获取图片信息
     * @param string $image
     * @param string $infoName
     * @return false|int
     * @throws Exception
     */
    public function getInfo($image, $infoName)
    {
        $infoName = strtoupper($infoName);

        if ($infoName == 'W') {
            return imagesx($image);
        } elseif ($infoName == 'H') {
            return imagesy($image);
        }

        $this->showError('invalid_image_param');
    }

    /**
     * 检查路径
     * @param string $path
     * @param int $perm
     * @return bool
     * @throws Exception
     */
    protected function checkPath($path, $perm)
    {
        return ocCheckPath($path, $perm);
    }

    /**
     * 检查图片格式
     * @param string $path
     * @param string $type
     * @throws Exception
     */
    protected function checkImage($path, $type)
    {
        $str = $this->getMessage($type == 'src' ? 'src_image' : 'dst_image');

        if (!preg_match('/^.*[[:alnum:]_-]+\.[a-zA-Z0-9]{2,5}$/', $path)) {
            $this->showError('invalid_path', $str);
        }

        $filenameArray = explode('.', ocBasename($path));

        if ($type == 'src') {
            $this->srcName = $filenameArray[0];
            $this->srcExtName = $extName = $filenameArray[1];
        } else {
            $this->dstName = $filenameArray[0];
            $this->dstExtName = $extName = $filenameArray[1];
        }

        if (!key_exists($extName, self::$imageTypes)) {
            $this->showError('invalid_type', $str);
        }

        $imageType = self::$imageTypes[$extName];

        if ($type == 'src') {
            $sizeInfo = getimagesize($path);
            $mime = isset($imageType[1]) ? $imageType[1] : null;
            if (!($sizeInfo && $mime == $sizeInfo['mime'])) {
                $this->showError('invalid_image');
            }
        }

        $imageId = isset($imageType[2]) ? $imageType[2] : null;

        if (!($imageId && ($this->sysImageType & $imageId))) {
            $this->showError('invalid_filename', array($str, $extName));
        }
    }

    /**
     * 添加文字水印
     * @param object $thumb
     * @param array $markInfo
     * @param int $imgW
     * @param int $imgH
     * @return array|bool|false
     * @throws Exception
     */
    public function textMark(&$thumb, array &$markInfo, $imgW, $imgH)
    {
        extract($markInfo);

        $textMarkParams = array(
            'content', 'size', 'location', 'color'
        );

        foreach ($textMarkParams as $param) {
            if (!isset($$param)) {
                $this->showError('no_mark_param', array($param));
            }
        }

        $this->font = ocService()->font->get(isset($font) ? $font : false);

        $contentInfo = imagettfbbox($size, 0, $this->font, $content);
        $contentW = $contentInfo[4] - $contentInfo[6];
        $contentH = $contentInfo[3] - $contentInfo[5];

        if (!isset($location)) {
            $location = 'right-down';
        }

        $color = $this->parseColor($color);
        $color = @imagecolorallocate($thumb->dstObj, $color[0], $color[1], $color[2]);
        list($textLx, $textLy) = $this->getLocation($location, $imgW, $imgH, $contentW, $contentH);

        if (!$this->font) {
            $result = imagestring(
                $thumb->dstObj, $size, $textLx, $textLy, $content, $color
            );
        } else {
            $result = imagettftext(
                $thumb->dstObj, $size, 0, $textLx, $textLy, $color, $this->font, $content
            );
        }

        if ($result) {
            if ($result = $thumb->basePrint()) {
                $result = $thumb->baseSave();
            }
        }

        return $result;
    }

    /**
     * 添加图片水印
     * @param object $thumb
     * @param array $markInfo
     * @param float $imgW
     * @param float $imgH
     * @return bool
     * @throws Exception
     */
    public function imageMark(&$thumb, array &$markInfo, $imgW, $imgH)
    {
        extract($markInfo);
        $imageMarkParams = array('path', 'location');

        foreach ($imageMarkParams as $param) {
            if (!isset($$param)) {
                $this->showError('no_mark_param', array($param));
            }
        }

        if (!ocFileExists($path)) {
            $this->showError('no_mark_image');
        }

        $imageFile = explode('.', ocBasename($path));
        $createFunc = 'imagecreatefrom' . ($imageFile[1] == 'jpg' ? 'jpeg' : $imageFile[1]);
        $imageObj = function_exists($createFunc) ? $createFunc($path) : null;

        if (is_resource($imageObj)) {
            $w = $this->getInfo($imageObj, 'w');
            $h = $this->getInfo($imageObj, 'h');
            list($textLx, $textLy) = $this->getLocation($location, $imgW, $imgH, $w, $h);
            $pct = isset($transparent) ? $transparent : 100;
            $result = @imagecopymerge($thumb->dstObj, $imageObj, $textLx, $textLy, 0, 0, $w, $h, $pct);
            if ($result) {
                if ($result = $thumb->basePrint()) {
                    $result = $thumb->baseSave();
                }
            }
            return $result;
        }

        return false;
    }

    /**
     * 分析水印位置
     * @param string $location
     * @param float $imgW
     * @param float $imgH
     * @param float $contentW
     * @param float $contentH
     * @return array
     * @throws Exception
     */
    protected function getLocation($location, $imgW, $imgH, $contentW, $contentH)
    {
        if (!in_array(trim($location), self::$locationRule)) {
            $this->showError('fault_mark_param', array('location'));
        }

        $textLx = 0;
        $font = $this->font;
        $textLy = $font ? $contentW : 0;
        $locationInfo = explode('-', strtolower($location));

        if (in_array('left', $locationInfo)) {
            $textLx = 0;
            if (in_array('up', $locationInfo)) {
                $textLy = $font ? $contentH : 0;
            } elseif (in_array('down', $locationInfo)) {
                $textLy = $font ? $imgH : $imgH - $contentH;
            } elseif (in_array('middle', $locationInfo)) {
                $textLy = $font ? ($imgH - $contentH) / 2 + $contentH : ($imgH - $contentH) / 2;
            }
        } elseif (in_array('right', $locationInfo)) {
            $textLx = $imgW - $contentW;
            if (in_array('up', $locationInfo)) {
                $textLy = $font ? $contentH : 0;
            } elseif (in_array('down', $locationInfo)) {
                $textLy = $font ? $imgH : $imgH - $contentH;
            } elseif (in_array('middle', $locationInfo)) {
                $textLy = $font ? ($imgH - $contentH) / 2 + $contentH : ($imgH - $contentH) / 2;
            }
        } elseif (in_array('center', $locationInfo)) {
            $textLx = ($imgW - $contentW) / 2;
            if (in_array('up', $locationInfo)) {
                $textLy = $font ? $contentH : 0;
            } elseif (in_array('down', $locationInfo)) {
                $textLy = $font ? $imgH : $imgH - $contentH;
            }
        } elseif ($location == 'middle') {
            $textLx = ($imgW - $contentW) / 2;
            $textLy = ($imgH - $contentH) / 2;
            if ($font) {
                $textLy += $contentH;
            }
        }

        return array($textLx, $textLy);
    }

    /**
     * 水平翻转
     * @param string $suffix
     * @throws Exception
     */
    public function flipH($suffix = null)
    {
        $this->flip('h', $suffix);
    }

    /**
     * 垂直翻转
     * @param string $suffix
     * @throws Exception
     */
    public function flipV($suffix = null)
    {
        $this->flip('v', $suffix);
    }

    /**
     * 新建真彩色图片
     * @param float $width
     * @param float $height
     * @return false|resource
     * @throws Exception
     */
    public function createImage($width, $height)
    {
        if (!$result = @imagecreatetruecolor($width, $height)) {
            $this->showError('failed_new_image');
        }

        return $result;
    }

    /**
     * 获取图片地址
     * @param string $image
     * @return string
     * @throws Exception
     */
    protected function getImagePath($image = 'src')
    {
        if ($image == 'src') {
            $this->checkResource();
            $imagePath = $this->srcPath;
        } elseif ($image == 'dst') {
            $this->checkResource();
            $imagePath = $this->dstPath;
        } else {
            if (!ocFileExists($image)) {
                $this->showError('not_exists_image');
            }
            $imagePath = $image;
        }

        return $imagePath;
    }

    /**
     * 获取颜色值，十六进制转成十进制
     * @param string $color
     * @return array
     * @throws Exception
     */
    public function parseColor($color)
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
     * 检查图片资源
     * @throws Exception
     */
    protected function checkResource()
    {
        if (!is_resource($this->srcObj)) {
            $this->showError('no_src_image');
        }

        if (!$this->dstPath) {
            $this->showError('no_dst_image');
        }
    }

    /**
     * 输出图片
     * @param bool $isDestroy
     * @return bool|mixed
     * @throws Exception
     */
    protected function basePrint($isDestroy = false)
    {
        $contentType = isset(self::$imageTypes[$this->srcExtName][0]) ? self::$imageTypes[$this->srcExtName][0] : null;

        if (empty($contentType)) {
            $this->showError('invalid_content_type');
        }

        header("Content-Type:{$contentType};charset=utf-8");
        $path = ocDir(dirname($this->dstPath));

        if (!is_writable($path)) {
            if (!@chmod($path, 0777)) {
                $this->showError('no_write_access');
            }
        }

        $tempFile = $this->srcName . '_' . md5(time() * mt_rand(1, 10));
        $this->srcTemp = $path . $tempFile . '.' . $this->srcExtName;

        if (function_exists($contentType)) {
            $result = call_user_func($contentType, $this->dstObj, $this->srcTemp);
            if ($isDestroy) {
                imagedestroy($this->dstObj);
            }
            return $result;
        }

        return false;
    }

    /**
     * 保存图片文件
     * @return bool|false|int
     * @throws Exception
     */
    protected function baseSave()
    {
        if (!is_file($this->srcTemp)) {
            $this->showError('no_cache_file');
        }

        $content = ocRead($this->srcTemp);
        $result = ocWrite($this->dstPath, $content);
        if ($result) {
            $result = $this->delImage($this->srcTemp);
        }

        return $result;
    }

    /**
     * 图片翻转
     * @param string $type
     * @param string $suffix
     * @return bool|false|int|mixed|null
     * @throws Exception
     */
    protected function flip($type, $suffix = null)
    {
        $result = null;
        $type = strtoupper($type);

        if (!is_resource($this->srcObj)) {
            $this->showError('no_src_image');
        }

        $srcPath = $this->srcPath;
        $thumb = new Image();
        $thumb->setSrcImage($srcPath);

        if ($suffix) {
            $dstPath = ocDir(dirname($srcPath)) . $thumb->srcName . $suffix . '.' . $thumb->srcExtName;
        } else {
            $dstPath = $this->getImagePath('dst');
        }

        $thumb->setDstImage($dstPath);
        $thumb->dstObj = $this->createImage($thumb->srcWidth, $thumb->srcHeight);

        if ($type == 'H') {
            for ($i = 0; $i < $thumb->srcWidth; $i++) {
                $dstLx = $thumb->srcWidth - $i - 1;
                $result = imagecopy(
                    $thumb->dstObj, $thumb->srcObj, $i, 0, $dstLx, 0, 1, $thumb->srcHeight
                );
            }
        }

        if ($type == 'V') {
            for ($i = 0; $i < $thumb->srcHeight; $i++) {
                $dstLy = $thumb->srcHeight - $i - 1;
                $result = imagecopy(
                    $thumb->dstObj, $thumb->srcObj, 0, $i, 0, $dstLy, $thumb->srcWidth, 1
                );
            }
        }

        if ($result) {
            $result = $thumb->basePrint();
            if ($result) {
                $result = $thumb->baseSave();
            }
        }

        return $result;
    }
}