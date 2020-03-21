<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 事务管理器Transaction
 * @Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/

namespace Ocara\Core;

defined('OC_PATH') or exit('Forbidden!');

class Transaction extends Base
{
    private $count = 0;
    private $list = array();

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
        if ($this->count == 1) {
            $this->commitAll();
            $this->count = 0;
            $this->list = array();
        } elseif ($this->count > 1) {
            $this->count--;
        } else {
            ocService()->error->show('no_transaction_for_commit');
        }
    }

    /**
     * 事务回滚
     */
    public function rollback()
    {
        if ($this->count > 0) {
            $this->count = 0;
            $this->rollbackAll();
            $this->list = array();
        }
    }

    /**
     * 提交所有事务
     */
    public function commitAll()
    {
        foreach ($this->list as $database) {
            $database->commit();
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