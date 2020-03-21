<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  XML处理插件Xml
 * @Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/

namespace Ocara\Service;

use Ocara\Core\ServiceBase;
use Ocara\Exceptions\Exception;

/**
 * 可进行操作：生成、导入、保存和导出（下载）XML
 * 支持的XML来源：file（XML文件）,string（XML字符串）,array（XML结点数组）
 * array来源，只支持最简单的不包含任何关联样式信息的XML处理
 */
class Xml extends ServiceBase
{
    /**
     * @var $xmlObj SimpleXMLElement对象
     * @var $xmlData XML数据数组
     */
    public $xmlObj;
    public $xmlPath;
    public $xmlData;
    public $encoding;

    /**
     * 析构函数
     * @param string $encoding
     */
    public function __construct($encoding = 'utf-8')
    {
        $this->encoding = $encoding;
    }

    /**
     * @return resource 新建XML解析器
     */
    public function createXmlParser()
    {
        $xmlParser = xml_parser_create($this->encoding);
        xml_parser_set_option($xmlParser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($xmlParser, XML_OPTION_SKIP_WHITE, 1);
        return $xmlParser;
    }

    /**
     * 保存XML文件
     * @param $filePath
     * @param null $perm
     * @return bool|int
     */
    public function save($filePath, $perm = null)
    {
        return ocWrite($filePath, $this->xmlData, false, $perm);
    }

    /**
     * 导出（下载 ）
     * @param $fileName
     */
    public function export($fileName)
    {
        if (!pathinfo($fileName, PATHINFO_EXTENSION)) {
            $fileName = $fileName . '.xml';
        }

        header("Content-Type: text/xml;encoding={$this->encoding};name={$fileName}");
        header("Content-Disposition: attachment; filename={$fileName}");
        header("Pragma: no-cache");
        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: 0");

        $response = ocService()->response;
        $response->setBody($this->xmlData);
        $response->send();
    }

    /**
     * 获取处理后的XML内容
     * @return mixed
     */
    public function getResult()
    {
        return $this->xmlData;
    }

    /**
     * 加载并解析XML字符串
     * @param $xmlString
     * @throws Exception
     */
    public function loadString($xmlString)
    {
        if (!($this->parseXml($xmlString, 'string'))) {
            $this->showError('failed_xml_parse');
        }

        $this->xmlObj = simplexml_load_string($xmlString);
        $this->xmlData = $xmlString;
    }

    /**
     * 加载并解析XML数组
     * @param array $xmlArray
     * @throws Exception
     */
    public function loadArray(array $xmlArray)
    {
        $xmlData = array();
        $root = isset($xmlArray[0]) ? $xmlArray[0] : null;

        if (!is_string($root) || empty($root)) {
            $this->showError('need_root_node');
        }

        $list = !empty($xmlArray[1]) ? $xmlArray[1] : array();
        $hasStatement = isset($xmlArray[2]) ? $xmlArray[2] : true;

        if ($hasStatement) {
            $xmlData[] = "<?xml version=\"1.0\" encoding=\"{$this->encoding}\"?>";
        }

        $xmlData[] = "<{$root}>";

        if ($list && is_array($list)) {
            $xmlData[] = $this->makeXml($list);
        }

        $xmlData[] = "</{$root}>";
        $xmlData = implode(PHP_EOL, $xmlData);

        $this->loadString($xmlData);
    }

    /**
     * 加载文件
     * @param $xmlFile
     * @throws Exception
     */
    public function loadFile($xmlFile)
    {
        if (!$this->parseXml($xmlFile, 'file')) {
            $this->showError('failed_xml_parse');
        }

        if (!pathinfo($xmlFile, PATHINFO_EXTENSION)) {
            $xmlFile = $xmlFile . '.xml';
        }

        $this->xmlPath = $xmlFile;
        $this->xmlObj = simplexml_load_file($xmlFile);
        $this->xmlData = ocRead($xmlFile);
    }

    /**
     * 解析XML文件
     * @param $xmlSource
     * @param string $type
     * @return bool|int
     * @throws Exception
     */
    public function parseXml($xmlSource, $type = 'file')
    {
        $xmlParser = $this->createXmlParser();
        $result = false;

        if ($type == 'file') {
            $fo = @fopen($xmlSource, 'rb');
            while ($xmlData = @fread($fo, 4096)) {
                if (!$result = @xml_parse($xmlParser, $xmlData, feof($fo))) break;
            }
        } elseif ($type == 'string') {
            $result = @xml_parse($xmlParser, $xmlSource, true);
        }

        if (!$result) {
            $error = xml_get_error_code($xmlParser);
            $line = xml_get_current_line_number($xmlParser);
            $column = xml_get_current_column_number($xmlParser);
            $this->showError('xml_parse_error', array($error, $line, $column));
        }

        xml_parser_free($xmlParser);
        return $result;
    }

    /**
     * 生成XML字符串
     * @param array $xmlArray
     * @return string|null
     */
    public function makeXml(array $xmlArray)
    {
        $xmlData = null;

        foreach ($xmlArray as $xmlKey => $xmlVal) {
            $xmlString = "<{$xmlKey}>";
            if (is_array($xmlVal) && $xmlVal) {
                $xmlString .= PHP_EOL . $this->makeXml($xmlVal, $xmlData) . PHP_EOL;
            } else {
                $xmlString .= "{$xmlVal}";
            }
            $xmlString .= "</{$xmlKey}>";
            $xmlData[] = $xmlString;
        }

        $xmlData = implode(PHP_EOL, $xmlData);
        return $xmlData;
    }

    /**
     * 转换成XML数组
     * @return array
     */
    public function toArray()
    {
        $result = array();
        $xmlParser = $this->createXmlParser();
        xml_parse_into_struct($xmlParser, $this->xmlData, $values, $index);

        foreach ($values as $row) {
            if ($row['type'] == 'complete') {
                $result[$row['tag']] = isset($row['value']) ? $row['value'] : null;
            }
        }

        xml_parser_free($xmlParser);

        return $result;
    }
}
