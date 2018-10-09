<?php
namespace Ocara\Service\Models;

use Ocara\Models\Database;
use Ocara\Service\Interfaces\Model\Session as SessionInerface;

class Session extends Database implements SessionInerface
{
	protected $_table = 'sessions';
	protected $_primary = 'session_id';

	/**
	 * 初始化模型
	 */
	protected function _model()
	{}

	/**
	 * 读取session
	 * @param $sessionId
	 * @return null
	 */
	public function read($sessionId)
	{
		$where = array('session_id' => $sessionId);

		$result = $this
			->where($where)
			->getRow();

		return $result ? $result['session_data'] : null;
	}

	/**
	 * 写入session
	 * @param $data
	 */
	public function write($data)
	{
		$where = array(
			'session_id' => $data['session_id']
		);

		$sessionInfo = $this->getRow($where);
		if ($sessionInfo) {
			$data['modify_time'] = time();
			$this->where($where)->update($data);
		} else {
			$data['create_time'] = time();
			$data['modify_time'] = time();
			$this->create($data);
		}
	}

	/**
	 * 删除session
	 * @param $sessionId
	 */
	public function destroy($sessionId)
	{
		$where = array(
			'session_id' => $sessionId
		);

		$sessionInfo = $this->getRow($where);
		if ($sessionInfo) {
			$this->select($sessionInfo['id'])->delete();
		}
	}

	/**
	 * 清理过期session
	 */
	public function gc()
	{
		$where = array(
			'session_expire_time' => date(ocConfig('DATE_FORMAT.datetime'))
		);

		$this->cWhere('<', $where)->delete();
	}
}