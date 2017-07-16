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

		$this->_server = Request::getPost('server');
		$this->_database = Request::getPost('database');
		$this->_table = Request::getPost('table');
		$this->_model = Request::getPost('model');
		$this->_primarys = Request::getPost('primarys');

		if ($this->_server == 'default') {
			$this->_server = OC_EMPTY;
		}
		if ($this->_database == 'default') {
			$this->_database = OC_EMPTY;
		}

		if (empty($this->_model)) {
			$this->_model = ocStandardName($this->_table);
		}

		if ($this->_modelType == 'Database') {
			$this->createDatabaseModel();
		} elseif ($this->_modelType == 'Cache') {
			$this->createCacheModel();
		}
	}

	public function createDatabaseModel()
	{
		$server = ucfirst($this->_server);
		$database = ucfirst($this->_database);

		$serverPath = $this->_server ? $server . OC_DIR_SEP : OC_EMPTY;
		$dbPath = $this->_database ? $database . OC_DIR_SEP : OC_EMPTY;
		$namespace = $this->_server ? OC_NS_SEP . $server : OC_EMPTY;

		if ($this->_database) {
			$namespace = $namespace . OC_NS_SEP . $database;
		}

		$modelName = ucfirst($this->_model);

		$content = "<?php\r\n";
		$content .= "namespace Model{$namespace};\r\n";
		$content .= "use Ocara\\Model\\Database;\r\n";
		$content .= "\r\n";
		$content .= "class {$modelName} extends Database\r\n";
		$content .= "{\r\n";

		if ($this->_server) {
			$content .= "\tprotected \$_server = '{$this->_server}';\r\n";
		}
		if ($this->_database) {
			$content .= "\tprotected \$_database = '{$this->_database}';\r\n";
		}
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

		$modelPath = $modelPath . $serverPath . $dbPath;

		if (ocFileExists($path = $modelPath . "{$modelName}.php")) {
			Develop::error(Develop::back('Model文件已存在，如果需要覆盖，请先手动删除！'));
		}

		File::createFile($path, 'wb');
		File::writeFile($path, $content);

		//新建字段配置
		$fileCache = new FileCache();
		$modelFile = lcfirst($modelName);
		$path = OC_ROOT . 'resource/conf/model/' . $serverPath . $dbPath . $modelFile . '.php';
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
		$content .= "\$LANG = array(\r\n";
		foreach ($fields as $row) {
			if ($row['desc']) {
				$desc = $row['desc'];
			} else {
				$name = ocStandardName($row['name'], OC_SPACE);
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
			. $dbPath
			. $modelFile
			. '.php';

		File::createFile($path, 'wb');
		File::writeFile($path, $content);

		//新建字段信息配置
		$path = OC_ROOT . 'resource/conf/fields/'
			. $serverPath
			. $dbPath
			. $modelFile
			. '.php';

		FileCache::build();
		$fileCache->setData($fields, null, $modelClass . ' Fields');
		$fileCache->save($path);

		die("添加成功！");
	}

	public function createCacheModel()
	{
		$server = ucfirst($this->_server);
		$database = ucfirst($this->_database);

		$serverPath = $this->_server ? $server . OC_DIR_SEP : OC_EMPTY;
		$dbPath = $this->_database ?  $database . OC_DIR_SEP : OC_EMPTY;
		$namespace = OC_NS_SEP . 'Cache';

		if ($this->_server) {
			$namespace = $namespace . OC_NS_SEP . $server;
		}

		if ($this->_database) {
			$namespace = $namespace . OC_NS_SEP . $database;
		}

		$modelName = ucfirst($this->_model);

		$content = "<?php\r\n";
		$content .= "namespace Model{$namespace};\r\n";
		$content .= "use Ocara\\Model\\Cache;\r\n";
		$content .= "\r\n";
		$content .= "class {$modelName} extends Cache\r\n";
		$content .= "{\r\n";

		if ($this->_server) {
			$content .= "\tprotected \$_server = '{$this->_server}';\r\n";
		}
		if ($this->_database) {
			$content .= "\tprotected \$_database = '{$this->_database}';\r\n";
		}

		$content .= "\r\n";
		$content .= "\t/**\r\n";
		$content .= "\t * 初始化模型\r\n";
		$content .= "\t */\r\n";
		$content .= "\tprotected function _model()\r\n\t{}\r\n";
		$content .= "}";

		$appDir = 'application';

		if (!is_dir($modelPath = OC_ROOT . "{$appDir}/model/Cache/")) {
			@mkdir($modelPath);
		}

		$modelPath = $modelPath . $serverPath . $dbPath;

		if (ocFileExists($path = $modelPath . "{$modelName}.php")) {
			Develop::error(Develop::back('Model文件已存在，如果需要覆盖，请先手动删除！'));
		}

		File::createFile($path, 'wb');
		File::writeFile($path, $content);

		die("添加成功！");
	}
}

?>