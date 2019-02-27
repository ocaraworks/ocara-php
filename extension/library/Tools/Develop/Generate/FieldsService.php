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

	public function add($data = array())
	{
	    $data = $data ? : ocService()->request->getPost();
		$this->_model = $data['model'];
        $this->_ismodule = !empty($data['ismodule']);

		$this->updateModel();
	}

	public function updateModel()
	{
		if (empty($this->_model)) {
            $this->showError('Model类名不能为空！');
		}

		if (!class_exists($this->_model)) {
			$this->showError('Model类不存在，请重新输入！');
		}

		$model = new $this->_model();
		if ($this->_ismodule) {
            $paths = $model->getModuleConfigPath();
        } else {
            $paths = $model->getConfigPath();
        }

		$path = $paths['fields'];
		$model->loadFields(false);
		$fields = $model->getFields();

		$fileCache = FileCache::build();
		$fileCache->format();
		$fileCache->setData($fields, null, $this->_model . ' Fields');
		$fileCache->save($path);

        echo("更新成功！");
	}
}

?>