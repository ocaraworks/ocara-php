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

		$moduleModelDir = "{$this->_mdlname}/privates/model/{$this->_connectName}/";
        $entityModelDir = "{$this->_mdlname}/privates/entities/{$this->_connectName}/";

        if ($this->_dbdir) {
            $moduleModelDir .= "{$this->_database}/";
            $entityModelDir .= "{$this->_database}/";
        }

        switch($this->_mdltype)
        {
            case 'modules':
                $rootNamespace = "app\\modules\\{$this->_mdlname}\\privates\\model";
                $entityRootNamespace = "app\\modules\\{$this->_mdlname}\\privates\\entities";
                $modelPath = ocPath('application', 'modules/' . $moduleModelDir);
                $entityPath = ocPath('application', 'modules/' . $entityModelDir);
                break;
            case 'console':
                $rootNamespace = "app\console\\{$this->_mdlname}\\privates\\model";
                $entityRootNamespace = "app\console\\{$this->_mdlname}\\privates\\entities";
                $modelPath = ocPath('application', 'console/' . $moduleModelDir);
                $entityPath = ocPath('application', 'modules/' . $entityModelDir);
                break;
            case 'assist':
                $rootNamespace = "app\\assist\\model";
                $entityRootNamespace = "app\\assist\\entities";
                $modelPath = ocPath('assist', $moduleModelDir);
                $entityPath = ocPath('assist', $entityModelDir);
                break;
            default:
                $rootNamespace = "app\\dal\\model";
                $entityRootNamespace = "app\\dal\\entities";
                $modelPath = ocPath('model', $this->_connectName . OC_DIR_SEP);
                $entityPath = ocPath('entities', $this->_connectName . OC_DIR_SEP);
        }

        if ($this->_dbdir) {
            $namespace = ocNamespace($rootNamespace, $this->_connectName) . $this->_database;
            $entityNamespace = ocNamespace($entityRootNamespace, $this->_connectName) . $this->_database;
        } else {
            $namespace = ocNamespace($rootNamespace) . $this->_connectName;
            $entityNamespace = ocNamespace($entityRootNamespace) . $this->_connectName;
        }

		$modelName = ucfirst($this->_model);
        $entityName = ucfirst($this->_model) . 'Entity';
        $modelClass = $namespace . OC_NS_SEP . $modelName;

		if (empty($this->_table)) {
			$this->showError('请填写表名！');
		}

		if (empty($this->_primaries)) {
			$connect = DatabaseFactory::create($this->_connectName);
			$fields = $connect->getFields($this->_table);
			$primaryFields = array();
			foreach ($fields as $fieldName => $fieldInfo) {
				if ($fieldInfo['isPrimary']) {
					$primaryFields[] = $fieldName;
				}
			}
			if ($primaryFields) {
				$this->_primaries = implode(',', $primaryFields);
			}
			if (empty($this->_primaries)) {
				$this->showError('系统找不到该数据表的主键，请填写主键字段！');
			}
		}

        ocCheckPath($modelPath);
        if (ocFileExists($path = $modelPath .  "{$modelName}.php")) {
            $this->showError('Model文件已存在，请先手动删除！');
        }

        ocCheckPath($entityPath);
        if (ocFileExists($entityPath = $entityPath .  "{$entityName}.php")) {
            $this->showError('Entity文件已存在，请先手动删除！');
        }

        //新建模型
		$content = "<?php\r\n";
		$content .= "namespace {$namespace};\r\n";
		$content .= "use Base\\{$modelBase};\r\n";
		$content .= "\r\n";
		$content .= "class {$modelName} extends {$modelBase}\r\n";
		$content .= "{\r\n";

		if ($this->_connectName != 'defaults') {
            $content .= "\tprotected \$_connectName = '{$this->_connectName}';\r\n";
        }

		if ($this->_mdltype && $this->_mdlname) {
            $content .= "\tprotected \$_module = '{$this->_mdlname}';\r\n";
        }

		$content .= "\tprotected \$_database = '{$this->_database}';\r\n";
		$content .= "\tprotected \$_table = '{$this->_table}';\r\n";
		$content .= "\tprotected \$_primary = '{$this->_primaries}';\r\n";
		$content .= "\r\n";
		$content .= "\t/**\r\n";
		$content .= "\t * 初始化模型\r\n";
		$content .= "\t */\r\n";
		$content .= "\tprotected function _model()\r\n\t{}\r\n";
		$content .= "}";

        ocService()->file->createFile($path, 'wb');
        ocService()->file->writeFile($path, $content);

        //新建实体模型
        $content = "<?php\r\n";
        $content .= "namespace {$entityNamespace};\r\n";
        $content .= "use {$modelClass};\r\n";
        $content .= "\r\n";
        $content .= "class {$entityName} extends {$modelName}\r\n";
        $content .= "{\r\n";
        $content .= "}";

        ocService()->file->createFile($entityPath, 'wb');
        ocService()->file->writeFile($entityPath, $content);

        $model = new $modelClass();
        $paths = $model->getConfigPath();

        if (!empty($this->_mdltype)) {
            $configPath = $paths['moduleConfig'];
            $langPath = $paths['moduleLang'];
        } else {
            $paths = $model->getConfigPath();
            $configPath = $paths['config'];
            $langPath = $paths['lang'];
        }

		//新建字段配置
		$fileCache = new FileCache();
		$modelFile = lcfirst($modelName);

		$fileCache->setData(array(), "CONF['MAP']", '字段别名映射');
		$fileCache->format();
		$fileCache->save($configPath);

		$fileCache->setData(array(), "CONF['VALIDATE']", '字段验证规则');
		$fileCache->format();
		$fileCache->save($configPath, true);

		$fileCache->setData(array(), "CONF['JOIN']", '表关联');
		$fileCache->format();
		$fileCache->save($configPath, true);

		//新建字段数据文件
        $fieldsService = new FieldsService();
        $fieldsService->add(array(
            'model' => $modelClass
        ));

		//新建语言文件
		$fileCache->setData(array(), null, $namespace . "\\{$modelName} language config");
		$fileCache->format();
		$fileCache->save($langPath);

        echo("添加成功！");
	}

	public function createCacheModel()
	{
		$cacheType = ucfirst(strtolower(ocConfig(array('CACHE', $this->_connectName, 'type'))));

		if (!in_array($cacheType, array('Redis', 'Memcache'))) {
			$this->showError('缓存配置类型非法！');
		}

		if ($cacheType == 'Redis') {
			$this->_database = ocService()->request->getPost('database', 0);
		}

		$namespace = OC_NS_SEP . $cacheType;
		$baseModel = $cacheType . 'Base';

		$modelName = ucfirst($this->_model);

		$content = "<?php\r\n";
		$content .= "namespace Model{$namespace};\r\n";
		$content .= "use Model\\{$baseModel};\r\n";
		$content .= "\r\n";
		$content .= "class {$modelName} extends {$baseModel}\r\n";
		$content .= "{\r\n";
		$content .= "\tprotected \$_connectName = '{$this->_connectName}';\r\n";

		if ($cacheType != 'Memcache') {
			$content .= "\tprotected \$_database = '{$this->_database}';\r\n";
		}

		$content .= "\r\n";
		$content .= "\t/**\r\n";
		$content .= "\t * 初始化模型\r\n";
		$content .= "\t */\r\n";
		$content .= "\tprotected function _model()\r\n\t{}\r\n";
		$content .= "}";

		if (!is_dir($modelPath = OC_APPLICATION_PATH . "model/{$cacheType}/")) {
			@mkdir($modelPath);
		}

		$modelPath = $modelPath;
		if (ocFileExists($path = $modelPath . "{$modelName}.php")) {
			$this->showError('Model文件已存在，如果需要覆盖，请先手动删除！');
		}

        ocService()->file->createFile($path, 'wb');
        ocService()->file->writeFile($path, $content);

        echo("添加成功！");
	}
}

?>