<?php
namespace Ocara\Service\Models;

use Ocara\Exceptions\Exception;
use Ocara\Models\DatabaseModel;
use Ocara\Service\Interfaces\Model\Session as SessionInterface;

class Session extends DatabaseModel implements SessionInterface
{
	protected $_table = 'sessions';
	protected $_primary = 'session_id';

	/**
	 * 初始化模型
	 */
	public function __model()
	{}

    /**
     * 读取session
     * @param $sessionId
     * @return null
     * @throws Exception
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
     * @throws Exception
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
     * @throws Exception
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
			'session_expire_time' => date(ocConfig(array('DATE_FORMAT', 'datetime')))
		);

		$this->cWhere('<', $where)->delete();
	}
}