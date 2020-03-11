<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   普通控制器类Rest
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Controllers;

use Ocara\Core\Response;
use Ocara\Exceptions\Exception;

class Rest extends Api
{
    /**
     * 获取控制器类型
     */
    public static function controllerType()
    {
        return static::$controllerType ? ucfirst(static::$controllerType): static::CONTROLLER_TYPE_REST;
    }

    /**
     * 渲染前置事件
     * @throws Exception
     */
    public function beforeRender()
    {
        if (!$this->response->getHeaderOption('statusCode')) {
            if ($this->result['status'] == 'success') {
                $successCode = strtr(
                    ocService()->app->getRoute('action'),
                    ocConfig('CONTROLLERS.rest.success_code_map')
                );
                $this->response->setStatusCode($successCode);
            } else {
                $this->response->setStatusCode(Response::STATUS_SERVER_ERROR);
            }
        }

        if (!ocConfig(array('API', 'send_header_code'), 0)) {
            $this->response->setStatusCode(Response::STATUS_OK);
            $this->result['status'] = $this->response->getHeaderOption('status');
        }
    }
}