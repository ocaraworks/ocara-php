<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/9
 * Time: 16:03
 */
namespace Ocara\Extension\Service\Tools\Develop\Generate;

use Ocara\Exceptions\Exception;

class BaseService
{
    public static $config =  array(
        'actions' => array(
            '_form'     => '表单生成',
            '_display'  => '输出模板',
            '_submit'   => '提交后处理',
            '_ajax'     => 'Ajax处理'
        ),
        'controller_actions' => array(
            'Common' => array(
                '_form',    '_display',
                '_submit',  '_ajax'
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
}