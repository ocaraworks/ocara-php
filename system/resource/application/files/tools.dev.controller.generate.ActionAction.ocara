<?php
/**
 * 添加动作
 */
namespace app\tools\dev\controller\generate;

use app\tools\dev\controller\generate\Controller;
use Ocara\Extension\Tools\Develop\Generate\ActionService;

class ActionAction extends Controller
{
    protected $_target;
    protected $_behavior;

    public function __action()
    {
        $this->_target = $this->request->getGet('target');
        $this->_behavior = $this->request->getGet('behavior');
    }

    public function registerForms()
    {
        $this->form('action_form');
    }

    public function display()
    {
        $this->renderFile($this->_target);
    }

    public function submit()
    {
        $class = '\Ocara\Extension\Tools\Develop\Generate\\'
            . ucfirst($this->_target)
            . 'Service';
        $method = $this->_behavior ? : 'add';

        $service = new $class();
        $service->$method();
    }

    public function afterAction()
    {
        if ($this->isFormSubmit()) {
            echo '操作成功！';
        }
    }
}