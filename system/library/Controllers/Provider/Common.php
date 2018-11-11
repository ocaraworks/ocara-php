<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   普通控制器提供器类Common
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Controllers\Provider;

use Ocara\Interfaces\Event;
use Ocara\Models\Database as DatabaseModel;
use Ocara\Controllers\Provider\Base;
use Ocara\Core\Response;

defined('OC_PATH') or exit('Forbidden!');

class Common extends Base
{
    /**
     * @var $_isSubmit 是否POST提交
     * @var $_checkForm 是否检测表单
     */
    const EVENT_AFTER = '_after';
    const EVENT_AFTER_CREATE_FORM = 'afterCreateForm';

    /**
     * 初始化设置
     */
    public function init()
    {
        $this->session->boot();
        $this->setAjaxResponseErrorCode(false);
        $this->event(self::EVENT_AFTER_CREATE_FORM)->append(array($this, 'afterCreateForm'));
        $this->_plugin = $this->view;
    }

    /**
     * 获取动作执行方式
     */
    public function getDoWay()
    {
        return 'common';
    }

    /**
     * 设置AJAX返回格式（回调函数）
     * @param $result
     * @return mixed
     */
    public function formatAjaxResult($result)
    {
        if ($result['status'] == 'success') {
            $this->response->setStatusCode(Response::STATUS_OK);
            return $result;
        } else {
            if (!$this->response->getOption('statusCode')) {
                $this->response->setStatusCode(Response::STATUS_SERVER_ERROR);
            }
            return $result;
        }
    }
}