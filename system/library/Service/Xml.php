<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  XML处理插件Xml
 * Copyright (c) http://www.ocara.cn All rights reserved.
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
	 * @var $xmlParser 解析器资源句柄
	 * @var $xmlObj SimpleXMLElement对象
	 * @var $xmlData XML数据数组
	 */
	public $xmlParser;
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
		$this->xmlParser = xml_parser_create($encoding);
		$this->encoding  = $encoding;
	}

    /**
     * 重新初始化
     * @param $type
     * @param $xmlSource
     * @throws Exception
     */
	public function setData($type, $xmlSource)
	{
		if ($type == 'file') {
			if (!$this->parseXml($xmlSource, 'file')) {
				$this->showError('failed_xml_parse');
			}
			if (!pathinfo($xmlSource, PATHINFO_EXTENSION)) {
				$xmlSource = $xmlSource . '.xml';
			}
			$this->xmlPath = $xmlSource;
			$this->xmlObj = simplexml_load_file($xmlSource);
			$this->xmlData = ocRead($xmlSource);
		} elseif ($type == 'string') {
			$this->loadString($xmlSource);
		} elseif ($type == 'array') {
			$this->loadArray($xmlSource);
		} else {
			$this->showError('fault_xml_source');
		}
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
		
		echo $this->xmlData;
	}

    /**
     * 获取处理后的XML内容
     * @return mixed
     */
	public function getContent()
	{
		return $this->xmlData;
	}

    /**
     * 生成XML数组
     * @return array
     */
	public function makeArray()
	{
		xml_parse_into_struct($this->xmlParser, $this->xmlData, $values, $index);
		return array($index, $values);
	}

    /**
     * 打印XML
     */
	public function display()
	{
		if (is_object($this->xmlObj)) {
			header("Content-Type: text/xml;encoding={$this->encoding};");
			echo $this->xmlData;
		}
	}

	/**
	 * 清空
	 */
	public function destroy()
	{
		$this->xmlObj 	 = 
		$this->xmlParser = 
		$this->xmlData 	 = null;
		
		@xml_parser_free($this->xmlParser);
	}

    /**
     * 加载并解析XML字符串
     * @param $xmlSource
     * @throws Exception
     */
	protected function loadString($xmlSource)
	{
		if (!($this->parseXml($xmlSource, 'string'))) {
			$this->showError('failed_xml_parse');
		}
		
		$this->xmlObj = simplexml_load_string($xmlSource);
		$this->xmlData = $xmlSource;
	}

    /**
     * 加载并解析XML数组
     * @param array $xmlSource
     * @throws Exception
     */
    protected function loadArray(array $xmlSource)
    {
        $xmlData = [];
        $root = ocGet(0, $xmlSource);

        if (!is_string($root) || empty($root)) {
            $this->showError('need_root_node');
        }

        $list = ocGet(1, $xmlSource);
        $hasStatement = ocGet(2, $xmlSource, true);

        if ($hasStatement) {
            $xmlData[] = "<?xml version=\"1.0\" encoding=\"{$this->encoding}\"?>";
        }

        $xmlData[] = "<{$root}>" ;

        if (is_array($list)) {
            $xmlData[] = $this->makeXml($list);
        }

        $xmlData[]= "</{$root}>";
        $xmlData = implode(PHP_EOL, $xmlData);

        $this->loadString($xmlData);
    }

    /**
     * 解析XML文件
     * @param $xmlSource
     * @param string $type
     * @return bool|int
     * @throws Exception
     */
	protected function parseXml($xmlSource, $type = 'file')
	{
		$xmlParser = xml_parser_create($this->encoding);
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
			$error 	= xml_get_error_code($xmlParser);
			$line 	= xml_get_current_line_number($xmlParser);
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
	protected function makeXml(array $xmlArray)
	{
		$xmlData = null;
		
		foreach ($xmlArray as $xmlKey => $xmlVal) {
            $xmlString = "<{$xmlKey}>";
			if (is_array($xmlVal) && $xmlVal) {
                $xmlString .= "\t" . $this->makeXml($xmlVal, $xmlData);
			} else {
                $xmlString .= "{$xmlVal}";
			}
            $xmlString .= "</{$xmlKey}>";
			$xmlData[] = $xmlString;
		}

        $xmlData = implode(PHP_EOL, $xmlData);
		return $xmlData;
	}
}
