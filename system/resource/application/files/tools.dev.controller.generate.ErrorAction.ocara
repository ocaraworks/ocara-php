<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/13
 * Time: 0:29
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