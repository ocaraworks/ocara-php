<?php
/**
 * 开发者中心模型生成类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Extension\Tools\Develop\Generate;

use Ocara\Service\FileCache;
use Ocara\Exceptions\Exception;
use Ocara\Extension\Tools\Develop\Generate\BaseService;

class CacheModelService extends BaseService
{
    private $_mdltype;
    private $_mdlname;
    private $_serverName;
    private $_prefix;
    private $_model;
    private $_database;

    public function add()
    {
        $defaultServer = ocService()->caches->getDefaultServer();
        $request = ocService()->request;

        $this->_mdltype = $request->getPost('mdltype');
        $this->_mdlname = $request->getPost('mdlname');
        $this->_serverName = $request->getPost('server', $defaultServer);
        $this->_prefix = $request->getPost('prefix');
        $this->_model = $request->getPost('model');

        if (empty($this->_model)) {
            $this->showError('请输入缓存模型名称！');
        }

        $this->createCacheModel();
    }

    public function createCacheModel()
    {
        $serverName = null;
        if ($this->_serverName && $this->_serverName != ocService()->caches->getDefaultServer()) {
            $serverName = $this->_serverName;
        }

        $serverNameDir = ($serverName ? $serverName . '/' : null);
        $serverNamespace = $serverName ? OC_NS_SEP . $serverName : null;
        $connect = ucfirst($this->_serverName);
        $modelBase = 'CacheModel';
        $connectPath = $this->_serverName . OC_DIR_SEP;
        $moduleModelDir = "{$this->_mdlname}/model/cache/";

        $cacheType = ucfirst(strtolower(ocConfig(array('CACHE', $this->_serverName, 'type'), OC_EMPTY)));
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

        $modelPath = $modelPath . $serverNameDir;
        $namespace = $rootNamespace . $serverNamespace;
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
        $content .= "\r\n";
        $content .= "namespace {$namespace};\r\n";
        $content .= "\r\n";
        $content .= "use Base\\Model\\{$modelBase};\r\n";
        $content .= "\r\n";
        $content .= "class {$modelName} extends {$modelBase}\r\n";
        $content .= "{\r\n";
        $content .= "    protected \$serverName = '{$this->_serverName}';\r\n";
        $content .= "    protected \$prefix = '{$this->_prefix}';\r\n";

        if ($cacheType == 'Redis') {
            $content .= "    protected \$database = '{$this->_database}';\r\n";
        }

        $content .= "\r\n";
        $content .= "    /**\r\n";
        $content .= "     * 初始化模型\r\n";
        $content .= "     */\r\n";
        $content .= "    public function __model()\r\n";
        $content .= "    {\r\n";
        $content .= "    }\r\n";
        $content .= "}";

        $fileService = ocService()->file;
        $fileService->createFile($path, 'wb');
        $fileService->writeFile($path, $content);
    }
}

?>