<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  开发者中心模型管理类model_admin
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Develop;
use Ocara\Ocara;
use Ocara\Request;
use Ocara\Develop;
use Ocara\Service\File;
use Ocara\Service\FileCache;

class model_admin
{
	private $_modelType;
	private $_server;
	private $_table;
	private $_model;
	private $_database;
	private $_primarys;

	public function add()
	{
		$this->_modelType = Request::getPost('modelType');
		$this->_server = Request::getPost('server', 'main');
		$this->_table = Request::getPost('table');
		$this->_model = Request::getPost('model');
		$this->_primarys = Request::getPost('primarys');

		if ($this->_modelType == 'Database') {
			if (empty($this->_model)) {
				$this->_model = ocHump($this->_table);
			}
			$this->_database = Request::getPost('database', ocConfig('DATABASE.main.name'));
			$this->createDatabaseModel();
		} elseif ($this->_modelType == 'Cache') {
			if (empty($this->_model)) {
				$this->_model = $this->_server;
			}
			$this->createCacheModel();
		}
	}

	public function createDatabaseModel()
	{
		$server = ucfirst($this->_server);
		$serverBase = $server . 'Base';
		$database = ucfirst($this->_database);

		$serverPath = $server . OC_DIR_SEP;
		$isDefaultDatabase = $this->_database == ocConfig('DATABASE.main.name');
		$dbPath = $isDefaultDatabase ? OC_EMPTY : $database . OC_DIR_SEP;

		$namespace = OC_NS_SEP . $server . ($isDefaultDatabase ? OC_EMPTY : OC_NS_SEP . $database);
		$modelName = ucfirst($this->_model);

		$content = "<?php\r\n";
		$content .= "namespace Model{$namespace};\r\n";
		$content .= "use Model\\{$serverBase};\r\n";
		$content .= "\r\n";
		$content .= "class {$modelName} extends {$serverBase}\r\n";
		$content .= "{\r\n";

		$content .= "\tprotected \$_server = '{$this->_server}';\r\n";
		$content .= "\tprotected \$_database = '{$this->_database}';\r\n";
		$content .= "\tprotected \$_table = '{$this->_table}';\r\n";
		$content .= "\tprotected \$_primary = '{$this->_primarys}';\r\n";
		$content .= "\r\n";
		$content .= "\t/**\r\n";
		$content .= "\t * 初始化模型\r\n";
		$content .= "\t */\r\n";
		$content .= "\tprotected function _model()\r\n\t{}\r\n";
		$content .= "}";

		$appDir = 'application';

		if (!is_dir($modelPath = OC_ROOT . "{$appDir}/model/")) {
			@mkdir($modelPath);
		}

		if (empty($this->_table) || empty($this->_primarys)) {
			Develop::error(Develop::back('请填满信息！'));
		}

		$modelPath = $modelPath . $serverPath;
		if (ocFileExists($path = $modelPath . "{$dbPath}/{$modelName}.php")) {
			Develop::error(Develop::back('Model文件已存在，如果需要覆盖，请先手动删除！'));
		}

		File::createFile($path, 'wb');
		File::writeFile($path, $content);

		//新建字段配置
		$fileCache = new FileCache();
		$modelFile = lcfirst($modelName);
		$path = OC_ROOT . 'resource/conf/model/' . $serverPath . $modelFile . '.php';
		$fileCache->setData(array(), "CONF['MAP']", '字段别名映射');
		$fileCache->format();
		$fileCache->save($path);

		$fileCache->setData(array(), "CONF['VALIDATE']", '字段验证规则');
		$fileCache->format();
		$fileCache->save($path, true);

		$fileCache->setData(array(), "CONF['JOIN']", '表关联');
		$fileCache->format();
		$fileCache->save($path, true);

		//新建语言文件
		$modelClass = 'Model' . $namespace . OC_NS_SEP . $modelName;

		$model = new $modelClass();
		$fields = $model->getFields();

		$content = "<?php\r\n";
		$content .= "return array(\r\n";
		foreach ($fields as $row) {
			if ($row['desc']) {
				$desc = $row['desc'];
			} else {
				$name = ocHump($row['name'], OC_SPACE);
				$desc = $name;
			}
			$desc = addslashes($desc);
			$content .= "\t'{$row['name']}' => '{$desc}',\r\n";
		}
		$content .= ");";
		$path = OC_ROOT . "resource/lang/"
			. Ocara::language()
			. '/model/'
			. $serverPath
			. $modelFile
			. '.php';

		File::createFile($path, 'wb');
		File::writeFile($path, $content);

		//新建字段信息配置
		$path = OC_ROOT . 'resource/conf/fields/'
			. $serverPath
			. $modelFile
			. '.php';

		FileCache::build();
		$fileCache->setData($fields, null, $modelClass . ' Fields');
		$fileCache->save($path);

		die("添加成功！");
	}

	public function createCacheModel()
	{
		$cacheType = ucfirst(strtolower(ocConfig(array('CACHE', $this->_server, 'type'))));

		if (!in_array($cacheType, array('Redis', 'Memcache'))) {
			Develop::error(Develop::back('缓存配置类型非法！'));
		}

		if ($cacheType == 'Redis') {
			$this->_database = Request::getPost('database', 0);
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
		$content .= "\tprotected \$_server = '{$this->_server}';\r\n";

		if ($cacheType != 'Memcache') {
			$content .= "\tprotected \$_database = '{$this->_database}';\r\n";
		}

		$content .= "\r\n";
		$content .= "\t/**\r\n";
		$content .= "\t * 初始化模型\r\n";
		$content .= "\t */\r\n";
		$content .= "\tprotected function _model()\r\n\t{}\r\n";
		$content .= "}";

		$appDir = 'application';

		if (!is_dir($modelPath = OC_ROOT . "{$appDir}/model/{$cacheType}/")) {
			@mkdir($modelPath);
		}

		$modelPath = $modelPath;
		if (ocFileExists($path = $modelPath . "{$modelName}.php")) {
			Develop::error(Develop::back('Model文件已存在，如果需要覆盖，请先手动删除！'));
		}

		File::createFile($path, 'wb');
		File::writeFile($path, $content);

		die("添加成功！");
	}
}

?>