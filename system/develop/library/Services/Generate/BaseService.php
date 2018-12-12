<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/9
 * Time: 16:03
 */
class BaseService
{

    /**
     * 错误返回
     * @param $msg
     * @return string
     * @throws \Ocara\Exceptions\Exception
     */
    public function back($msg)
    {
        $back = ocService()->html->createElement('a', array(
            'href' => 'javascript:;',
            'onclick' => 'setTimeout(function(){history.back();},0)',
        ), '返回');

        return  $msg . $back;
    }
}