<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  开发者中心模型管理类model_admin
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Develop;

use Ocara\Core\Ocara;
use Ocara\Core\Develop;
use Ocara\Service\File;
use Ocara\Service\FileCache;
use Ocara\Core\DatabaseFactory;
use Ocara\Core\Container;

class model_admin
{
	private $_modelType;
	private $_connectName;
	private $_table;
	private $_model;
	private $_database;
	private $_primaries;

	public function add()
	{
	    $defaultServer = DatabaseFactory::getDefaultServer();
		$request = ocService()->request;
		$this->_modelType = $request->getPost('modelType');
		$this->_connectName = $request->getPost('connect', $defaultServer);
		$this->_table = $request->getPost('table');
		$this->_model = $request->getPost('model');
		$this->_primaries = $request->getPost('primaries');

		if ($this->_modelType == 'Database') {
			if (empty($this->_model)) {
				$this->_model = ocHump($this->_table);
			}
			$this->_database = ocService()->request->getPost('database', ocConfig('DATABASE.'.$defaultServer.'.name'));
			$this->createDatabaseModel();
		} elseif ($this->_modelType == 'Cache') {
			if (empty($this->_model)) {
				$this->_model = $this->_connectName;
			}
			$this->createCacheModel();
		}
	}

	public function createDatabaseModel()
	{
		$connect = ucfirst($this->_connectName);
		$connectBase = $connect . 'Base';
		$connectPath = $connect . OC_DIR_SEP;

		$namespace = OC_NS_SEP . $connect;
		$modelName = ucfirst($this->_model);

		if (empty($this->_table)) {
			Develop::error(Develop::back('请填写表名！'));
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
				Develop::error(Develop::back('系统找不到该数据表的主键，请填写主键字段！'));
			}
		}

		$content = "<?php\r\n";
		$content .= "namespace Model{$namespace};\r\n";
		$content .= "use Model\\{$connectBase};\r\n";
		$content .= "\r\n";
		$content .= "class {$modelName} extends {$connectBase}\r\n";
		$content .= "{\r\n";

		$content .= "\tprotected \$_connectName = '{$this->_connectName}';\r\n";
		$content .= "\tprotected \$_database = '{$this->_database}';\r\n";
		$content .= "\tprotected \$_table = '{$this->_table}';\r\n";
		$content .= "\tprotected \$_primary = '{$this->_primaries}';\r\n";
		$content .= "\r\n";
		$content .= "\t/**\r\n";
		$content .= "\t * 初始化模型\r\n";
		$content .= "\t */\r\n";
		$content .= "\tprotected function _model()\r\n\t{}\r\n";
		$content .= "}";

		if (!is_dir($modelPath = OC_APPLICATION_PATH . "model/")) {
			@mkdir($modelPath);
		}

		$modelPath = $modelPath . $connectPath;
		if (ocFileExists($path = $modelPath . "{$modelName}.php")) {
			Develop::error(Develop::back('Model文件已存在，如果需要覆盖，请先手动删除！'));
		}

        ocService()->file->createFile($path, 'wb');
        ocService()->file->writeFile($path, $content);

		//新建字段配置
		$fileCache = new FileCache();
		$modelFile = lcfirst($modelName);
		$path = OC_ROOT . 'config/model/' . $connectPath . $modelFile . '.php';
		$fileCache->setData(array(), "CONF['MAP']", '字段别名映射');
		$fileCache->format();
		$fileCache->save($path);

		$fileCache->setData(array(), "CONF['VALIDATE']", '字段验证规则');
		$fileCache->format();
		$fileCache->save($path, true);

		$fileCache->setData(array(), "CONF['JOIN']", '表关联');
		$fileCache->format();
		$fileCache->save($path, true);

		//新建字段数据文件
		$modelClass = 'Model' . $namespace . OC_NS_SEP . $modelName;

		$model = new $modelClass();
		$fields = $model->getFields();

		$path = OC_ROOT . "resource/data/"
			. '/fields/'
			. $connectPath
			. $modelFile
			. '.php';

		$fileCache->setData($fields, null, "Model\\Main\\{$modelName} Fields");
		$fileCache->format();
		$fileCache->save($path);

		//新建语言文件
		$path = OC_ROOT . "lang/"
			. ocService()->app->getLanguage()
			. '/model/'
			. $connectPath
			. $modelFile
			. '.php';
		$lang = array();
		$fileCache->setData($lang, null, "Model\\Main\\{$modelName} 语言配置");
		$fileCache->format();
		$fileCache->save($path);

		die("添加成功！");
	}

	public function createCacheModel()
	{
		$cacheType = ucfirst(strtolower(ocConfig(array('CACHE', $this->_connectName, 'type'))));

		if (!in_array($cacheType, array('Redis', 'Memcache'))) {
			Develop::error(Develop::back('缓存配置类型非法！'));
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
			Develop::error(Develop::back('Model文件已存在，如果需要覆盖，请先手动删除！'));
		}

        ocService()->file->createFile($path, 'wb');
        ocService()->file->writeFile($path, $content);

		die("添加成功！");
	}
}

?>