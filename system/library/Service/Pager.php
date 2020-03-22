<?php
/**
 * 分页插件类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Service;

use Ocara\Core\ServiceBase;
use Ocara\Exceptions\Exception;

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
    public $previousPage;
    public $nextPage;
    public $startPage;
    public $endPage;
    public $lastPage;

    public $actClass = 'current';

    /**
     * 析构函数
     * Pager constructor.
     * @throws Exception
     */
    public function __construct()
    {
        $pageParam = ocConfig(array('PAGE', 'page_param'), null);
        $perPage = ocConfig(array('PAGE', 'per_page'), null);
        $perShow = ocConfig(array('PAGE', 'per_show'), null);

        $this->config($pageParam, $perPage, $perShow);
    }

    /**
     * 分页设置
     * @param string $pageParam
     * @param int $perPage
     * @param int $perShow
     * @return $this
     */
    public function config($pageParam = null, $perPage = null, $perShow = null)
    {
        if ($pageParam) {
            $this->setPageParam($pageParam);
        }

        if ($perPage) {
            $this->setPerPage($perPage);
        }

        if ($perShow) {
            $this->setPerShow($perShow);
        }

        return $this;
    }

    /**
     * 设置当前页
     * @param int $page
     * @return $this
     */
    public function setPage($page)
    {
        $this->page = $page;
        return $this;
    }

    /**
     * 设置分页传递参数名称
     * @param string $pageParam
     * @return $this
     */
    public function setPageParam($pageParam)
    {
        $this->pageParam = $pageParam ?: 'page';
        return $this;
    }

    /**
     * 设置每页显示多少条记录
     * @param $perPage
     * @return $this
     */
    public function setPerPage($perPage)
    {
        $this->perPage = $perPage ?: 10;
        return $this;
    }

    /**
     * 设置每次显示多少页
     * @param $perShow
     * @return $this
     */
    public function setPerShow($perShow)
    {
        $this->perShow = $perShow ?: 10;
        return $this;
    }

    /**
     * 设置分页URL
     * @param $url
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * 设置当前页CSS样式
     * @param $class
     * @return $this
     */
    public function setActiveClass($class)
    {
        $this->actClass = $class;
        return $this;
    }

    /**
     * 形成页码的主函数
     * @param $total
     * @param $url
     * @param array $attr
     * @return $this|bool
     * @throws Exception
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
     * @return array
     */
    public function getInfo()
    {
        if (!is_numeric($this->page)) {
            $this->page = $this->getPage();
        }

        $this->page = $this->page ?: 1;
        $recordEnd = $this->perPage * $this->page - 1;
        $recordStart = $recordEnd - ($this->perPage - 1);

        $result = array('offset' => $recordStart, 'rows' => $this->perPage);
        return $result;
    }

    /**
     * 生成HTML
     * @return string
     * @throws Exception
     */
    protected function setPageHtml()
    {
        $html = null;

        if ($this->page > 1) {
            if ($this->startPage > 1) {
                $html .= $this->getLink(1, self::getMessage('first_page'));
            }
            $this->previousPage = $this->page - 1;
            $html .= $this->getLink($this->previousPage, self::getMessage('previos_page'));
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
     * @return string|null
     */
    protected function getPages()
    {
        $str = null;

        for ($i = $this->startPage; $i <= $this->endPage; $i++) {
            $str .= $this->getLink($i, $i, $i == $this->page ? $this->actClass : false);
        }

        return $str;
    }

    /**
     * 获取超链接HTML
     * @param $page
     * @param $text
     * @param null $class
     * @return string
     */
    public function getLink($page, $text, $class = null)
    {
        $attr = $this->attr;

        if ($class) {
            $attr['class'] = $class;
        }

        $attr['href'] = $this->getUrl($page);
        $attr['pid'] = $page;

        $str = null;
        foreach ($attr as $key => $value) {
            $str = $str . OC_SPACE . $key . '="' . $value . '"';
        }

        return sprintf('<a%s>%s</a>', $str, $text);
    }

    /**
     * 获取页码链接URL
     * @param $pageNumber
     * @return mixed|string|null
     */
    protected function getUrl($pageNumber)
    {
        if (is_string($this->url)) {
            return $this->url;
        } elseif (is_array($this->url)) {
            $extParams = isset($this->url[1]) ? $this->url[1] : array();
            $extParams = array_merge($extParams, array(
                $this->pageParam => $pageNumber
            ));
            return ocUrl(reset($this->url), $extParams);
        }

        return null;
    }

    /**
     * 获取当前页
     * @return int
     */
    public function getPage()
    {
        $page = ocService()->request->getGet($this->pageParam, null);
        $page = $page ?: 1;
        return (int)$page;
    }
}
