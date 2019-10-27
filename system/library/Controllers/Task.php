<?php
namespace Ocara\Controllers;

use Ocara\Core\ControllerBase;
use Ocara\Interfaces\Controller as ControllerInterface;

class Task extends ControllerBase implements ControllerInterface
{
    /**
     * 获取控制器类型
     */
    public static function controllerType()
    {
        return self::$controllerType ? ucfirst(self::$controllerType): 'Task';
    }

    /**
     * 执行动作
     * @param string $actionMethod
     */
    public function doAction($actionMethod)
    {
        if ($actionMethod == '__action') {
            $this->doClassAction();
        } else {
            $this->$actionMethod();
        }
    }

    /**
     * 执行Action类
     */
    public function doClassAction()
    {
        if (method_exists($this, '__action')) {
            $this->__action();
        }
    }
}