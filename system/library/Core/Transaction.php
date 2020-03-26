<?php
/**
 * 事务管理器
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

class Transaction extends Base
{
    private $count = 0;
    private $list = array();
    private $rollbackCount = 0;

    /**
     * 推入数据库
     * @param $database
     */
    public function push($database)
    {
        if ($this->hasBegan()) {
            $key = $database->getConnectName();
            if (!isset($this->list[$key])) {
                $database->beginTransaction();
                $this->list[$key] = $database;
            }
        }
    }

    /**
     * 取消事务
     * @param ModelBase $database
     */
    public function cancel($database)
    {
        $key = $database->getConnectName();
        if (isset($this->list[$key])) {
            ocDel($this->list, $key);
        }
    }

    /**
     * 是否已开始事务
     * @return bool
     */
    public function hasBegan()
    {
        return $this->count > 0;
    }

    /**
     * 事务开始
     */
    public function begin()
    {
        $this->count++;
    }

    /**
     * 事务提交
     */
    public function commit()
    {
        if ($this->count > 1) {
            $this->count--;
        } elseif ($this->count == 1) {
            $this->commitAll();
            $this->count = 0;
            $this->list = array();
        } else {
            ocService()->error->show('no_transaction_for_commit');
        }
    }

    /**
     * 事务回滚
     */
    public function rollback()
    {
        if ($this->count > 0 && $this->isRollback() === false) {
            $this->rollbackAll();
            $this->rollbackCount++;
        }
    }

    /**
     * 是否已标记回滚
     * @return bool
     */
    public function isRollback()
    {
        return $this->rollbackCount > 0;
    }

    /**
     * 提交所有事务
     */
    public function commitAll()
    {
        if ($this->isRollback() === false) {
            foreach ($this->list as $database) {
                $database->commit();
            }
        }
    }

    /**
     * 回滚所有事务
     */
    public function rollbackAll()
    {
        foreach ($this->list as $database) {
            $database->rollback();
        }
    }
}