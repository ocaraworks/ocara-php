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
     * @var string $controllerType
     */
    protected static $controllerType = 'Rest';

    /**
     * 渲染前置事件
     * @param $data
     * @param $message
     * @param $status
     * @throws Exception
     */
    public function beforeRender($data, $message, $status)
    {
        $this->result = $this->api->getResult($data, $message, $status);

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