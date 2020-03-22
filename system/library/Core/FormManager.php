<?php
/**
 * Ocara开源框架 表单管理类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use Ocara\Exceptions\Exception;

defined('OC_PATH') or exit('Forbidden!');

class FormManager extends ServiceProvider
{
    protected $route;
    protected $form;
    protected $forms = array();

    /**
     * 注册服务
     */
    public function register()
    {
    }

    /**
     * 新建表单
     * @param $formName
     * @return Form[]|Form
     */
    public function create($formName)
    {
        if (!$this->hasForm($formName)) {
            $form = $this->createService('form', array($formName));
            $this->addForm($formName, $form);
        }

        return $this->getForm($formName);
    }

    /**
     * 获取表单
     * @param $name
     * @return Form[]|Form
     */
    public function getForm($name = null)
    {
        if (func_get_args()) {
            return array_key_exists($name, $this->forms) ? $this->forms[$name] : null;
        }
        return $this->forms;
    }

    /**
     * 设置表单
     * @param $formName
     * @param $form
     */
    public function addForm($formName, $form)
    {
        $this->forms[$formName] = $form;
    }

    /**
     * 是否存在表单
     * @param $name
     * @return array
     */
    public function hasForm($name)
    {
        return array_key_exists($name, $this->forms);
    }

    /**
     * 保存表单令牌
     * @throws Exception
     */
    public function bindToken()
    {
        foreach ($this->forms as $form) {
            $formName = $form->getName();
            $token = $this->formToken->generate($formName, $this->route);
            $this->saveToken($formName, $token);
            $form->setToken(array(
                'name' => $this->getTokenName(),
                'value' => $token
            ));
        }
    }

    /**
     * 清理表单令牌
     * @param string $formName
     * @throws Exception
     */
    public function clearToken($formName = null)
    {
        if (func_num_args()) {
            if ($formName) {
                ocService()->session->delete(array($this->getTokenSaveName(), $formName));
            }
        } else {
            ocService()->session->delete($this->getTokenSaveName());
        }
    }

    /**
     * 获取表单令牌
     * @param string $formName
     * @return array|mixed|null
     */
    public function getToken($formName = null)
    {
        if (func_num_args()) {
            $result = $this->hasForm($formName) ? $this->forms[$formName] : null;
        } else {
            $result = array();
            foreach ($this->forms as $form) {
                $tokenInfo = $form->getToken();
                $result[$tokenInfo['name']] = $tokenInfo['value'];
            }
        }

        return $result;
    }

    /**
     * 获取提交的表单
     * @param $requestToken
     * @return Form
     * @throws Exception
     */
    public function getSubmitForm($requestToken)
    {
        if (empty($requestToken)) {
            $this->error->show('failed_validate_token');
        }

        $tokens = $this->session->get($this->getTokenSaveName()) ?: array();
        $formName = array_search($requestToken, $tokens);

        if (ocConfig('FORM.check_repeat_submit', false)) {
            if ($formName === false || !$this->hasForm($formName)) {
                $this->error->show('not_exists_form');
            }
        }

        if ($formName) {
            $this->form = $this->getForm($formName);
        }

        return $this->form;
    }

    /**
     * 验证表单
     * @param $requestToken
     * @return mixed
     * @throws Exception
     */
    public function checkForm($requestToken)
    {
        return $this->getSubmitForm($requestToken);
    }

    /**
     * 获取TOKEN参数名称
     * @return string
     * @throws Exception
     */
    public static function getTokenName()
    {
        return '_oc_' . ocConfig(array('FORM', 'token_tag'), 'form_token_name');
    }

    /**
     * 获取TOKEN参数名称
     * @return string
     * @throws Exception
     */
    public function getTokenSaveName()
    {
        return $this->getTokenName() . '_list';
    }

    /**
     * 保存TOKEN
     * @param $formName
     * @param $token
     * @throws Exception
     */
    public function saveToken($formName, $token)
    {
        ocService()->session->set(array($this->getTokenSaveName(), $formName), $token);
    }

    /**
     * 设置路由
     * @param $route
     */
    public function setRoute($route)
    {
        $this->route = $route;
    }

    /**
     * 获取路由信息
     * @param string $name
     * @return array|null
     */
    public function getRoute($name = null)
    {
        if (isset($name)) {
            return isset($this->route[$name]) ? $this->route[$name] : null;
        }

        return $this->route;
    }
}