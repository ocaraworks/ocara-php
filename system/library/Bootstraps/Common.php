<?php
/**
 * 普通启动器类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Bootstraps;

use \ReflectionException;
use Ocara\Exceptions\Exception;
use Ocara\Interfaces\Bootstrap as BootstrapInterface;
use Ocara\Core\BootstrapBase;

class Common extends BootstrapBase implements BootstrapInterface
{
    /**
     * 初始化
     * @throws Exception
     */
    public function init()
    {
        parent::init();

        if (!ocFileExists(OC_WEB_ROOT . '.htaccess')) {
            self::createHtaccess();
        }
    }

    /**
     * 运行访问控制器
     * @param array $route
     * @param array $params
     * @param string $moduleNamespace
     * @return bool|mixed
     * @throws Exception
     * @throws ReflectionException
     */
    public function start($route = array(), $params = array(), $moduleNamespace = null)
    {
        $service = ocService();
        $moduleNamespace = $moduleNamespace ?: OC_MODULE_NAMESPACE;
        $service->dispatcher->dispatch($route, $moduleNamespace, $params);
        return $service->response->send();
    }

    /**
     * 生成伪静态文件
     * @param string $moreContent
     * @throws Exception
     */
    public static function createHtaccess($moreContent = OC_EMPTY)
    {
        $file = OC_WEB_ROOT . '.htaccess';
        $htaccess = ocImport(OC_SYS . 'data/rewrite/apache.php');

        if (empty($htaccess)) {
            ocService()->error->show('no_rewrite_default_file');
        }

        if (is_writeable(OC_WEB_ROOT)) {
            $htaccess = sprintf($htaccess, $moreContent);
            ocWrite($file, $htaccess);
        } else {
            ocService()->error->show('not_writeable_htaccess');
        }
    }
}