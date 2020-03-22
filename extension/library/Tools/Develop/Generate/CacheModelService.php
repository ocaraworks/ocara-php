<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  开发者中心模型管理类model_admin
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/

namespace Ocara\Extension\Tools\Develop\Generate;

use Ocara\Core\Develop;
use Ocara\Service\FileCache;
use Ocara\Core\CacheFactory;
use Ocara\Exceptions\Exception;
use Ocara\Extension\Tools\Develop\Generate\BaseService;

class CacheModelService extends BaseService
{
    private $_mdltype;
    private $_mdlname;
    private $_connectName;
    private $_prefix;
    private $_model;
    private $_database;

    public function add()
    {
        $defaultServer = CacheFactory::getDefaultServer();
        $request = ocService()->request;

        $this->_mdltype = $request->getPost('mdltype');
        $this->_mdlname = $request->getPost('mdlname');
        $this->_connectName = $request->getPost('connect', $defaultServer);
        $this->_prefix = $request->getPost('prefix');
        $this->_model = $request->getPost('model');

        if (empty($this->_model)) {
            $this->showError('请输入缓存模型名称！');
        }

        $this->createCacheModel();
    }

    public function createCacheModel()
    {
        $connect = ucfirst($this->_connectName);
        $modelBase = 'CacheModel';
        $connectPath = $this->_connectName . OC_DIR_SEP;
        $moduleModelDir = "{$this->_mdlname}/model/cache/";

        $cacheType = ucfirst(strtolower(ocConfig(array('CACHE', $this->_connectName, 'type'), OC_EMPTY)));
        if (!in_array($cacheType, array('Redis', 'Memcache'))) {
            $this->showError('缓存服务器名称不存在！请检查缓存配置是否存在。');
        }

        if ($cacheType == 'Redis') {
            $this->_database = ocService()->request->getPost('database', 0);
        }

        switch ($this->_mdltype) {
            case 'modules':
                $rootNamespace = "app\\modules\\{$this->_mdlname}\\model\\cache";
                $modelPath = ocPath('application', 'modules/' . $moduleModelDir);
                break;
            case 'console':
                $rootNamespace = "app\console\\{$this->_mdlname}\\model\\cache";
                $modelPath = ocPath('application', 'console/' . $moduleModelDir);
                break;
            case 'tools':
                $rootNamespace = "app\\tools\\model\\cache";
                $modelPath = ocPath('tools', $moduleModelDir);
                break;
            default:
                $rootNamespace = "app\\model\\cache";
                $modelPath = ocPath('model', 'cache/');
        }

        $namespace = $rootNamespace;
        $modelName = ucfirst($this->_model) . ocConfig('MODEL_SUFFIX');
        $modelClass = $namespace . OC_NS_SEP . $modelName;

        if (empty($this->_prefix)) {
            $this->showError('请填写前缀名！');
        }

        ocCheckPath($modelPath);
        if (ocFileExists($path = $modelPath . "{$modelName}.php")) {
            $this->showError('缓存Model文件已存在，请先手动删除！');
        }

        $content = "<?php\r\n";
        $content .= "namespace {$namespace};\r\n";
        $content .= "use Base\\Model\\{$modelBase};\r\n";
        $content .= "\r\n";
        $content .= "class {$modelName} extends {$modelBase}\r\n";
        $content .= "{\r\n";
        $content .= "\tprotected \$connectName = '{$this->_connectName}';\r\n";
        $content .= "\tprotected \$prefix = '{$this->_prefix}';\r\n";

        if ($cacheType == 'Redis') {
            $content .= "\tprotected \$database = '{$this->_database}';\r\n";
        }

        $content .= "\r\n";
        $content .= "\t/**\r\n";
        $content .= "\t * 初始化模型\r\n";
        $content .= "\t */\r\n";
        $content .= "\tpublic function __model()\r\n\t{}\r\n";
        $content .= "}";

        $fileService = ocService()->file;
        $fileService->createFile($path, 'wb');
        $fileService->writeFile($path, $content);
    }
}

?>