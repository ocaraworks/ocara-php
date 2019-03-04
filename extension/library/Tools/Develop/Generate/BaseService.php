<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/9
 * Time: 16:03
 */
namespace Ocara\Extension\Tools\Develop\Generate;

use Ocara\Exceptions\Exception;

class BaseService
{
    public static $config =  array(
        'actions' => array(
            'registerForms' => '表单注册',
            'display'       => '输出模板',
            'submit'        => '提交后处理',
            'ajax'          => 'Ajax处理'
        ),
        'controller_actions' => array(
            'Common' => array(
                'registerForms',    'display',
                'submit',  'ajax'
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

    public function getModuleRootPath($mdltype)
    {
        switch($mdltype)
        {
            case 'modules':
                $rootNamespace = "app\\modules";
                $rootModulePath = ocPath('modules') . OC_DIR_SEP;
                break;
            case 'console':
                $rootNamespace = "app\console";
                $rootModulePath = ocPath('console') . OC_DIR_SEP;
                break;
            case 'assist':
                $rootNamespace = "app\\assist";
                $rootModulePath = ocPath('assist') . OC_DIR_SEP;
                break;
            default:
                $rootNamespace = "app\\controller";
                $rootModulePath = OC_APPLICATION_PATH;
        }

        return compact('rootNamespace', 'rootModulePath');
    }
}