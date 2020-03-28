<?php
/**
 * 开发者中心模型管理类model_admin
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Extension\Tools\Develop\Generate;

use Ocara\Sql\Generator;

class ModelService extends BaseService
{
    private $_mdltype;
    private $_mdlname;
    private $_serverName;
    private $_table;
    private $_model;
    private $_database;
    private $_primaries;

    public function add()
    {
        $defaultServer = ocService()->databases->getDefaultServer();
        $request = ocService()->request;
        $this->_mdltype = $request->getPost('mdltype');
        $this->_mdlname = $request->getPost('mdlname');
        $this->_serverName = $request->getPost('server', $defaultServer);
        $this->_table = $request->getPost('table');
        $this->_model = $request->getPost('model');
        $this->_primaries = $request->getPost('primaries');

        if (empty($this->_model)) {
            $this->_model = ocHump($this->_table);
        }

        $this->_database = ocService()->request->getPost('database');
        $this->createDatabaseModel();
    }

    public function createDatabaseModel()
    {
        $serverName = null;
        if ($this->_serverName && $this->_serverName != ocService()->databases->getDefaultServer()) {
            $serverName = $this->_serverName;
        }

        $serverNameDir = ($serverName ? $serverName . '/' : null);
        $serverNamespace = $serverName ? OC_NS_SEP . $serverName : null;
        $modelBase = 'DatabaseModel';

        $moduleModelDir = "{$this->_mdlname}/model/database/" . $serverNameDir;
        $entityModelDir = "{$this->_mdlname}/model/entity/database/" . $serverNameDir;

        if ($this->_mdltype) {
            if (empty($this->_mdlname)) {
                $this->showError('请填写模块名！');
            }
        }

        switch ($this->_mdltype) {
            case 'modules':
                $rootNamespace = "app\\modules\\{$this->_mdlname}\\model\\database";
                $entityRootNamespace = "app\\modules\\{$this->_mdlname}\\model\\entity\\database";
                $modelPath = ocPath('application', 'modules/' . $moduleModelDir);
                $entityPath = ocPath('application', 'modules/' . $entityModelDir);
                $modulePath = ocPath('modules');
                break;
            case 'console':
                $rootNamespace = "app\console\\{$this->_mdlname}\\model\\database";
                $entityRootNamespace = "app\console\\{$this->_mdlname}\\model\\entity\\database";
                $modelPath = ocPath('application', 'console/' . $moduleModelDir);
                $entityPath = ocPath('application', 'console/' . $entityModelDir);
                break;
            case 'tools':
                $rootNamespace = "app\\tools\\{$this->_mdlname}\\model\\database";
                $entityRootNamespace = "app\\tools\\{$this->_mdlname}\\model\\entity\\database";
                $modelPath = ocPath('tools', $moduleModelDir);
                $entityPath = ocPath('tools', $entityModelDir);
                $modulePath = ocPath('tools');
                break;
            default:
                $rootNamespace = "app\\model\\database";
                $entityRootNamespace = "app\\model\\entity\\database";
                $modelPath = ocPath('model', 'database/');
                $entityPath = ocPath('entity', 'database/');
                $modelPath = $modelPath . $serverNameDir;
                $entityPath = $entityPath . $serverNameDir;
                $moduleModelDir = ocPath('module');
                $modulePath = null;
        }

        $namespace = $rootNamespace . $serverNamespace;
        $entityNamespace = $entityRootNamespace . $serverNamespace;
        $modelName = ucfirst($this->_model) . ocConfig('MODEL_SUFFIX');
        $entityName = ucfirst($this->_model) . 'Entity';
        $entityClass = $entityNamespace . OC_NS_SEP . $entityName;
        $modelClass = $namespace . OC_NS_SEP . $modelName;

        if (empty($this->_table)) {
            $this->showError('请填写表名！');
        }

        if (empty($this->_primaries)) {
            $connect = ocService()->databases->make($this->_serverName);
            $generator = new Generator($connect);
            $sqlData = $generator->getShowFieldsSql($this->_table, $this->_database);
            $fieldsInfo = $connect->getFieldsInfo($sqlData);
            $fields = $fieldsInfo['list'];
            $primaryFields = array();
            foreach ($fields as $fieldName => $fieldInfo) {
                if ($fieldInfo['isPrimary']) {
                    $primaryFields[] = $fieldName;
                }
            }
            if ($primaryFields) {
                $this->_primaries = implode(',', $primaryFields);
            }
        } else {
            if (strstr($this->_primaries, ',')) {
                $this->_primaries = array_map('trim', explode(',', $this->_primaries));
            }
        }

        ocCheckPath($modelPath);

        if (ocFileExists($path = $modelPath . "{$modelName}.php")) {
            $this->showError('Model文件已存在，请先手动删除！');
        }

        ocCheckPath($entityPath);
        if (ocFileExists($entityPath = $entityPath . "{$entityName}.php")) {
            $this->showError('Entity文件已存在，请先手动删除！');
        }

        //新建模型
        $content = "<?php\r\n";
        $content .= "\r\n";
        $content .= "namespace {$namespace};\r\n";
        $content .= "\r\n";
        $content .= "use Base\\Model\\{$modelBase};\r\n";
        $content .= "\r\n";
        $content .= "class {$modelName} extends {$modelBase}\r\n";
        $content .= "{\r\n";

        if ($this->_serverName != 'defaults') {
            $content .= "    protected \$serverName = '{$this->_serverName}';\r\n";
        }

        if ($this->_mdltype && $this->_mdlname) {
            $content .= "    protected \$module = '{$this->_mdlname}';\r\n";
        }

        if ($this->_database) {
            $content .= "    protected static \$database = '{$this->_database}';\r\n";
        }

        $content .= "    protected static \$table = '{$this->_table}';\r\n";
        $content .= "    protected static \$primary = '{$this->_primaries}';\r\n";
        $content .= "    protected static \$entity = '{$entityClass}';\r\n";
        $content .= "\r\n";
        $content .= "    /**\r\n";
        $content .= "     * 初始化模型\r\n";
        $content .= "     */\r\n";
        $content .= "    public function __model()\r\n";
        $content .= "    {\r\n";
        $content .= "    }\r\n";
        $content .= "\r\n";
        $content .= "    /**\r\n";
        $content .= "     * 字段别名映射配置\r\n";
        $content .= "     * return array\r\n";
        $content .= "     */\r\n";
        $content .= "    public function fieldsMap()\r\n";
        $content .= "    {\r\n";
        $content .= "    }\r\n";
        $content .= "\r\n";
        $content .= "    /**\r\n";
        $content .= "     * 表间关联配置\r\n";
        $content .= "     * return array\r\n";
        $content .= "     */\r\n";
        $content .= "    public function relations()\r\n";
        $content .= "    {\r\n";
        $content .= "    }\r\n";
        $content .= "\r\n";
        $content .= "    /**\r\n";
        $content .= "     * 字段验证配置\r\n";
        $content .= "     * return array\r\n";
        $content .= "     */\r\n";
        $content .= "    public function rules()\r\n";
        $content .= "    {\r\n";
        $content .= "    }\r\n";
//        $content .= "\r\n";
//        $content .= "    /**\r\n";
//        $content .= "     * 查询结果行过滤\r\n";
//        $content .= "     */\r\n";
//        $content .= "    public function rowFilters()\r\n    {}\r\n";
        $content .= "}";

        $fileService = ocService()->file;
        $fileService->createFile($path, 'wb');
        $fileService->writeFile($path, $content);

        //新建实体模型
        $model = new $modelClass();

        $paths = $model->getConfigPath($modulePath);
        $fieldsInfo = $model->getFieldsInfo();

        $modelBase = 'DatabaseEntity';
        $content = "<?php\r\n";
        $content .= "\r\n";
        $content .= "namespace {$entityNamespace};\r\n";
        $content .= "\r\n";
        $content .= "use Base\\Model\\{$modelBase};\r\n";
        $content .= "\r\n";

        //显示注释
        $content .= "/**" . "\r\n";
        $content .= " * Class {$entityName}" . "\r\n";
        $content .= " * @package " . $entityNamespace . "\r\n";

        foreach ($fieldsInfo as $row) {
            $propertyDefine = implode(OC_SPACE, $model->connect()->getFieldDefinesData($row));
            $content .= OC_SPACE . "* @property {$propertyDefine}" . "\r\n";
        }

        $content .= " */" . "\r\n";

        $content .= "class {$entityName} extends {$modelBase}\r\n";
        $content .= "{\r\n";
        $content .= "    public function __entity()\r\n";
        $content .= "    {\r\n";
        $content .= "    }\r\n";
        $content .= "\r\n";
        $content .= "    public static function source()\r\n";
        $content .= "    {\r\n";
        $content .= "        return '{$modelClass}';\r\n";
        $content .= "    }\r\n";
        $content .= "}";

        $fileService->createFile($entityPath, 'wb');
        $fileService->writeFile($entityPath, $content);

        if (!empty($this->_mdltype)) {
            $langPath = $paths['moduleLang'];
        } else {
            $paths = $model->getConfigPath($modulePath);
            $langPath = $paths['lang'];
        }

        //新建字段配置
        $fileCache = ocService()->fileCache;

        //新建字段数据文件
        $fieldsService = new FieldsService();
        $fieldsService->add(array(
            'model' => $modelClass
        ));

        //新建语言文件
        $fileCache->setData(array(), null, $namespace . "\\{$modelName} language config");
        $fileCache->format();
        $fileCache->save($langPath);
    }
}

?>