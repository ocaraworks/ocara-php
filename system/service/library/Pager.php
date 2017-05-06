<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  分页处理类Pager
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Service;
use Ocara\ServiceBase;

defined('OC_PATH') or exit('Forbidden!');

class Pager extends ServiceBase
{
	/**
	 * @var $pageParam 页码参数名称
	 * @var $perShow   每次显示多少页
	 * @var $perPage   每页显示多少条记录
	 */
	public $pageParam;
	public $perShow;
	public $perPage;
	
	public $url;
	public $html;
	public $attr;
	
	public $page;
	public $previosPage;
	public $nextPage;
	public $startPage;
	public $endPage;
	public $lastPage;
	
	public $actClass = 'current';

	/**
	 * 析构函数
	 * @param string  $pageParam
	 * @param integer $perPage
	 * @param integer $perShow
	 */
	public function __construct()
	{
		$this->pageParam = ocConfig('PAGE.page_param', null);
		$this->perPage 	 = ocConfig('PAGE.per_page', null);
		$this->perShow 	 = ocConfig('PAGE.per_show', null);
	}

	/**
	 * 分页设置
	 * @param string $pageParam
	 * @param integer $perPage
	 * @param integer $perShow
	 */
	public function config($pageParam = false, $perPage = false, $perShow = false)
	{
		if ($pageParam) {
			$this->pageParam = $pageParam;
		}
		
		if ($perPage) {
			$this->perPage = $perPage;
		}
		
		if ($perShow) {
			$this->perShow = $perShow;
		}
		
		return $this;
	}

	/**
	 * 设置当前页
	 * @param integer $page
	 */
	public function setPage($page)
	{
		$this->page = $page;
		return $this;
	}

	/**
	 * 设置每页显示多少条记录
	 * @param integer $perPage
	 */
	public function setPerPage($perPage)
	{
		$this->perPage = $perPage;
		return $this;
	}

	/**
	 * 设置分页传递参数名称
	 * @param string $pageParam
	 */
	public function setPageParam($pageParam)
	{
		$this->pageParam = $pageParam;
		return $this;
	}

	/**
	 * 设置每次显示多少页
	 * @param integer $perShow
	 */
	public function setPerShow($perShow)
	{
		$this->perShow = $perShow;
		return $this;
	}

	/**
	 * 设置分页URL
	 * @param string|array $url
	 */
	public function setUrl($url)
	{
		$this->url = $url;
		return $this;
	}

	/**
	 * 设置当前页CSS样式
	 * @param string $class
	 */
	public function setActClass($class)
	{
		$this->actClass = $class;
		return $this;
	}

	/**
	 * 形成页码的主函数
	 * @param integer $total
	 * @param string|array $url
	 * @param array $attr
	 */
	public function setHtml($total, $url, array $attr = array())
	{
		if ($total <= $this->perPage) {
			$this->lastPage = $total ? 1 : 0;
			return false;
		}
		
		if (empty($this->page) || $this->page <= 0) {
			$this->page = 1;
		}
		
		$this->attr = $attr;
		$this->url = $url ? $url : $this->url; //URL配置
		$this->lastPage = intval($total / ($this->perPage)); //总页数和最后一页

		if ($total % $this->perPage) {
			$this->lastPage++;
		}
		
		if ($this->page > $this->lastPage) { //当前页大于最后一页页码时，改为最后一页
			$this->page = $this->lastPage;
		}
		
		$j = intval($this->perShow / 2); //设置要显示的开始页
		$this->startPage = $this->page - $j;
		
		if ($this->lastPage - $this->page < $j) { //当前页与最后一页的差值小于$this->perShow的一半时
			$this->startPage = $this->startPage - ($j - ($this->lastPage - $this->page)) + 1;
		}
		
		if ($this->startPage <= 0) {
			$this->startPage = 1;
		}
		
		$this->endPage = $this->perShow + $this->startPage - 1; //设置要显示的结束页
		
		if ($this->endPage < $this->page) { //结束页小于当前要显示的页时的纠正
			$this->endPage = $this->page;
		}
		
		if ($this->endPage >= $this->lastPage) {
			$this->endPage = $this->lastPage;
		}
		
		$this->setPageHtml();
		
		return $this;
	}

	/**
	 * 计算分页信息
	 */
	public function getInfo()
	{
		$this->page || $this->page = 1;
		$recordEnd   = $this->perPage * $this->page - 1;
		$recordStart = $recordEnd - ($this->perPage - 1);
		
		return array($recordStart,$this->perPage);
	}

	/**
	 * 生成HTML
	 */
	protected function setPageHtml()
	{
		$html = false;
		
		if ($this->page > 1) {
			if ($this->startPage > 1) {
				$html .= $this->getLink(1, self::getMessage('first_page'));
			}
			$this->previosPage = $this->page - 1;
			$html .= $this->getLink($this->previosPage, self::getMessage('previos_page'));
		}
		
		$html .= $this->getPages();
		
		if ($this->page < $this->lastPage) {
			$this->nextPage = $this->page + 1;
			$html .= $this->getLink($this->nextPage, self::getMessage('next_page'));
		}
		
		if ($this->endPage < $this->lastPage) {
			$html .= $this->getLink($this->lastPage, self::getMessage('last_page'));
		}
		
		return $this->html = $html;
	}

	/**
	 * 生成数字页码HTML
	 */
	protected function getPages()
	{
		$str = false;
		
		for ($i = $this->startPage;$i <= $this->endPage;$i++) {
			$str .= $this->getLink($i, $i, $i == $this->page ? $this->actClass : false);
		}
		
		return $str;
	}

	/**
	 * 获取超链接HTML
	 * @param integer $page
	 * @param string $text
	 * @param string $class
	 */
	public function getLink($page, $text, $class = false)
	{
		$attr = $this->attr;
		
		if ($class) {
			$attr['class'] = $class;
		}
		
		$attr['href'] = $this->getUrl($page);
		$attr['pid'] = $page;
		
		$str = false;
		foreach ($attr as $key => $value) {
			$str = $str . OC_SPACE . $key . '="' . $value . '"';
		}
		
		return sprintf('<a%s>%s</a>', $str, $text);
	}

	/**
	 * 获取页码链接URL
	 * @param integer $pageNumber
	 */
	protected function getUrl($pageNumber)
	{
		if (is_string($this->url)) {
			return $this->url;
		} elseif (is_array($this->url)) {
			$extParams = ocGet(1, $this->url, array());
			$extParams = array_merge($extParams, array(
				$this->pageParam => $pageNumber
			));
			return ocUrl(reset($this->url), $extParams);
		}
		
		return null;
	}
}
