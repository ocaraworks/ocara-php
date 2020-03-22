<?php
/**
 * Excel处理插件类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Service;

use Ocara\Core\ServiceBase;

class Excel extends ServiceBase
{

    /**
     * 导出excel文件
     * @param string $fileName
     * @param string $content
     * @param string $charset
     */
    public function export($fileName, $content, $charset = 'gbk')
    {
        $charset = strtolower($charset);
        $charset = $charset ?: 'gbk';

        header("Content-Type: application/vnd.ms-execl;charset=$charset;name=$fileName");
        header("Content-Disposition: attachment; filename=$fileName");
        header("Pragma: no-cache");
        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: 0");

        $response = ocService()->response;
        $response->setBody($this->formatContent($content, $charset));
        $response->send();
    }

    /**
     * 保存为文件
     * @param string $filePath
     * @param string $content
     * @param string $charset
     * @param integer $perm
     * @return bool|int
     */
    public function save($filePath, $content, $charset = 'gbk', $perm = null)
    {
        $charset = strtolower($charset);
        $charset = $charset == 'utf-8' ? 'gbk' : $charset;
        $filePath = $this->convert($filePath, $charset);

        return ocWrite($filePath, $this->formatContent($content, $charset), false, $perm);
    }

    /**
     * 输出内容
     * @param string $content
     * @param string $charset
     * @return string|null
     */
    public function formatContent($content, $charset)
    {
        $result = null;

        if (is_array($content)) {
            foreach ($content as $row) {
                foreach ($row as $col) {
                    $result .= $this->convert($col, $charset) . "\t";
                }
                $result .= "\t\n";
            }
        } else {
            $result .= $this->convert($content, $charset) . "\t\n";
        }

        return $result;
    }

    /**
     * 编码转换
     * @param string $data
     * @param string $charset
     * @return false|string
     */
    public function convert($data, $charset)
    {
        $data = @iconv('utf-8', $charset, $data);

        if (!$data && function_exists('mb_convert_encoding')) {
            $data = @mb_convert_encoding($data, $charset, 'utf-8');
        }

        return $data;
    }
}
