<?php
/**
 * 登录
 */

namespace app\tools\dev\controller\generate;

use app\tools\dev\controller\generate\Controller;
use Ocara\Extension\Tools\Develop\Generate\LoginService;

class LoginAction extends Controller
{
    public function __action()
    {
        $this->view->setLayout('login');
    }

    public function display()
    {
    }

    public function submit()
    {
        $service = new LoginService();
        $service->login();
    }
}