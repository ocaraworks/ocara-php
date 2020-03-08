<?php
/**
 * 退出登录
 */
namespace app\tools\dev\controller\generate;

use app\tools\dev\controller\generate\Controller;
use Ocara\Extension\Tools\Develop\Generate\LoginService;

class LogoutAction extends Controller
{
	/**
	 * 初始化
	 */
	protected function __action()
	{}

	/**
	 * 输出模板
	 */
	public function display()
	{
        if ($this->isLogin()) {
            $service = (new LoginService());
            $service->logout();
        }

        $this->response->jump('generate/index');
    }
}