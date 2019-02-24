<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  开发者中心模块管理类module_admin
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Extension\Tools\Develop\Generate;

use Ocara\Service\FileCache;

class FieldsService extends BaseService
{
	private $_model;

	public function add()
	{
		$this->_model = ocService()->request->getPost('model');
		$this->updateModel();
	}

	public function updateModel()
	{
		if (empty($this->_model)) {
            $this->showError('Model类名不能为空！');
		}

		$modelClass = stripslashes($this->_model);
		$modelSubPath = ltrim(preg_replace('/^([\\\\]?Model)([\w\\\\]+)$/', '\2', $modelClass), OC_NS_SEP);

		if (!class_exists($modelClass)) {
			$this->showError('Model类不存在，请重新输入！');
		}

		$model = new $modelClass();
		$connectName = $model->getConnectName();
		$database = $model->getDatabase();

		$serverPath = $connectName . OC_DIR_SEP;
		$dbPath = $database ? $database . OC_DIR_SEP : OC_EMPTY;
		$modelFile = implode(OC_DIR_SEP, array_map('lcfirst', explode(OC_NS_SEP, $modelSubPath)));

		//新建字段信息配置
		$path = OC_ROOT . 'resource/data/fields/'
			. $serverPath
			. $dbPath
			. $modelFile
			. '.php';

		$model->loadFields(false);
		$fields = $model->getFields();

		$fileCache = FileCache::build();
		$fileCache->format();
		$fileCache->setData($fields, null, $modelClass . ' Fields');
		$fileCache->save($path);

		die("更新成功！");
	}
}

?>