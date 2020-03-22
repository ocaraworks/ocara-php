<?php
/**
 * 开发者中心控制器管理类controller_admin
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */
namespace Ocara\Extension\Tools\Develop\Generate;

use Ocara\Exceptions\Exception;

class BaseService
{
    public static $config = array(
        'actions' => array(
            'registerForms' => '表单注册',
            'display' => 'GET输出',
            'submit' => '提交后处理',
            'api' => 'Ajax处理'
        ),
        'controller_actions' => array(
            'Common' => array(
                'registerForms', 'display', 'submit', 'api',
            ),
            'Task' => array(),
            'Api' => array(
                'registerForms', 'display', 'submit',
            ),
            'Rest' => array()
        )
    );

    /**
     * 错误返回
     * @param $msg
     * @throws Exception
     */
    public function showError($msg)
    {
        $back = ocService()->html->createElement('a', array(
            'href' => 'javascript:;',
            'onclick' => 'setTimeout(function(){history.back();},0)',
        ), '返回');

        throw new Exception($msg . $back);
    }

    /**
     * 获取模块根目录
     * @param $mdltype
     * @return array
     */
    public function getModuleRootPath($mdltype)
    {
        switch ($mdltype) {
            case 'modules':
                $rootNamespace = "app\\modules";
                $rootModulePath = ocPath('modules') . OC_DIR_SEP;
                break;
            case 'console':
                $rootNamespace = "app\console";
                $rootModulePath = ocPath('console') . OC_DIR_SEP;
                break;
            case 'tools':
                $rootNamespace = "app\\tools";
                $rootModulePath = ocPath('tools') . OC_DIR_SEP;
                break;
            default:
                $rootNamespace = "app\\controller";
                $rootModulePath = OC_APPLICATION_PATH;
        }

        return compact('rootNamespace', 'rootModulePath');
    }
}