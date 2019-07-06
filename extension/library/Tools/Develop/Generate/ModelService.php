<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  开发者中心模型管理类model_admin
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Extension\Tools\Develop\Generate;

use Ocara\Core\Develop;
use Ocara\Service\FileCache;
use Ocara\Core\DatabaseFactory;
use Ocara\Exceptions\Exception;
use Ocara\Extension\Tools\Develop\Generate\BaseService;
use Ocara\Extension\Tools\Develop\Generate\FieldsService;

class ModelService extends BaseService
{
    private $_dbdir;
	private $_mdltype;
	private $_mdlname;
	private $_connectName;
	private $_table;
	private $_model;
	private $_database;
	private $_primaries;

	public function add()
	{
	    $defaultServer = DatabaseFactory::getDefaultServer();
		$request = ocService()->request;
        $this->_dbdir = $request->getPost('dbdir');
		$this->_mdltype = $request->getPost('mdltype');
		$this->_mdlname = $request->getPost('mdlname');
		$this->_connectName = $request->getPost('connect', $defaultServer);
		$this->_table = $request->getPost('table');
		$this->_model = $request->getPost('model');
		$this->_primaries = $request->getPost('primaries');

        if (empty($this->_model)) {
            $this->_model = ocHump($this->_table);
        }

        $this->_database = ocService()->request->getPost('database', ocConfig('DATABASE.'.$defaultServer.'.name'));
        $this->createDatabaseModel();
	}

	public function createDatabaseModel()
	{
		$connect = ucfirst($this->_connectName);
		$modelBase = 'DatabaseModel';
		$connectPath = $this->_connectName . OC_DIR_SEP;

		$moduleModelDir = "{$this->_mdlname}/privates/model/database/";
        $entityModelDir = "{$this->_mdlname}/privates/entity/database/";

        switch($this->_mdltype)
        {
            case 'modules':
                $rootNamespace = "app\\modules\\{$this->_mdlname}\\privates\\model\\database";
                $entityRootNamespace = "app\\modules\\{$this->_mdlname}\\privates\\entity\\database";
                $modelPath = ocPath('application', 'modules/' . $moduleModelDir);
                $entityPath = ocPath('application', 'modules/' . $entityModelDir);
                break;
            case 'console':
                $rootNamespace = "app\console\\{$this->_mdlname}\\privates\\model\\database";
                $entityRootNamespace = "app\console\\{$this->_mdlname}\\privates\\entity\\database";
                $modelPath = ocPath('application', 'console/' . $moduleModelDir);
                $entityPath = ocPath('application', 'console/' . $entityModelDir);
                break;
            case 'tools':
                $rootNamespace = "app\\tools\\{$this->_mdlname}\\privates\\model\\database";
                $entityRootNamespace = "app\\tools\\{$this->_mdlname}\\privates\\entity\\database";
                $modelPath = ocPath('tools', $moduleModelDir);
                $entityPath = ocPath('tools', $entityModelDir);
                break;
            default:
                $rootNamespace = "app\\model\\database";
                $entityRootNamespace = "app\\entity\\database";
                $modelPath = ocPath('model', 'database/');
                $entityPath = ocPath('entity', 'database/');
        }

        $namespace = $rootNamespace;
        $entityNamespace = $entityRootNamespace;
		$modelName = ucfirst($this->_model);
        $entityName = ucfirst($this->_model) . 'Entity';
        $modelClass = $namespace . OC_NS_SEP . $modelName;

		if (empty($this->_table)) {
			$this->showError('请填写表名！');
		}

		if (empty($this->_primaries)) {
			$connect = DatabaseFactory::create($this->_connectName);
			$fields = $connect->getFields($this->_table);
			$fields = $fields['list'];
			$primaryFields = array();
			foreach ($fields as $fieldName => $fieldInfo) {
				if ($fieldInfo['isPrimary']) {
					$primaryFields[] = $fieldName;
				}
			}
			if ($primaryFields) {
				$this->_primaries = implode(',', $primaryFields);
			}
		}

        ocCheckPath($modelPath);
        if (ocFileExists($path = $modelPath .  "{$modelName}.php")) {
            //$this->showError('Model文件已存在，请先手动删除！');
        }

        ocCheckPath($entityPath);
        if (ocFileExists($entityPath = $entityPath .  "{$entityName}.php")) {
            //$this->showError('Entity文件已存在，请先手动删除！');
        }

        //新建模型
		$content = "<?php\r\n";
		$content .= "namespace {$namespace};\r\n";
		$content .= "use Base\\{$modelBase};\r\n";
		$content .= "\r\n";
		$content .= "class {$modelName} extends {$modelBase}\r\n";
		$content .= "{\r\n";

		if ($this->_connectName != 'defaults') {
            $content .= "\tprotected \$connectName = '{$this->_connectName}';\r\n";
        }

		if ($this->_mdltype && $this->_mdlname) {
            $content .= "\tprotected \$module = '{$this->_mdlname}';\r\n";
        }

		$content .= "\tprotected \$database = '{$this->_database}';\r\n";
		$content .= "\tprotected \$table = '{$this->_table}';\r\n";
		$content .= "\tprotected \$primary = '{$this->_primaries}';\r\n";
		$content .= "\r\n";
		$content .= "\t/**\r\n";
		$content .= "\t * 初始化模型\r\n";
		$content .= "\t */\r\n";
		$content .= "\tpublic function __model()\r\n\t{}\r\n";
		$content .= "}";

        $fileService = ocService()->file;
        $fileService->createFile($path, 'wb');
        $fileService->writeFile($path, $content);

        //新建实体模型
        $modelBase = 'DatabaseEntity';
        $content = "<?php\r\n";
        $content .= "namespace {$entityNamespace};\r\n";
        $content .= "\r\n";
        $content .= "use Base\\{$modelBase};\r\n";
        $content .= "\r\n";
        $content .= "class {$entityName} extends {$modelBase}\r\n";
        $content .= "{\r\n";
        $content .= "}";

        $fileService->createFile($entityPath, 'wb');
        $fileService->writeFile($entityPath, $content);

        $model = new $modelClass();
        $paths = $model->getConfigPath();

        if (!empty($this->_mdltype)) {
            $langPath = $paths['moduleLang'];
        } else {
            $paths = $model->getConfigPath();
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