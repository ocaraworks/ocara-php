<?php
/**
 * 错误输出
 */
namespace app\tools\dev\controller\generate;

use app\tools\dev\controller\generate\Controller;

class ErrorAction extends Controller
{
    public function __action()
    {

    }

    public function display()
    {
        $this->assign('msg', urldecode($this->request->getGet('content')));
    }
}