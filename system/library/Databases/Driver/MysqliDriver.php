<?php
/**
 * Mysql数据库原生驱动类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Databases\Driver;

use \mysqli;
use Ocara\Exceptions\Exception;
use Ocara\Core\DriverBase;
use Ocara\Interfaces\Driver as DriverInterface;

class MysqliDriver extends DriverBase implements DriverInterface
{
    /**
     * PDO绑定参数类型映射
     */
    protected $paramTypesMap = array(
        'integer' => 'i',
        'string' => 's',
        'binary' => 's',
        'boolean' => 'i',
    );

    /**
     * 初始化配置
     * @param array $config
     */
    public function init($config)
    {
        $this->config = $config;
    }

    /**
     * 连接数据库
     * @return mixed
     * @throws Exception
     */
    public function connect()
    {
        $this->baseConnect();

        if (empty($this->connection)) {
            $this->errNo = $this->instance->connect_errno;
            $this->error = $this->instance->connect_error;
            $error = array(
                $this->config['name'], $this->errNo, $this->error
            );
            ocService()->error->show('failed_db_connect', $error);
        }

        return $this->connection;
    }

    /**
     * 获取驱动类型
     * @return string
     */
    public function driveType()
    {
        return 'mysql';
    }

    /**
     * 使用mysqli类连接
     * @throws Exception
     */
    protected function baseConnect()
    {
        $service = ocService();
        $host = ($this->pConnect ? 'p:' : OC_EMPTY) . $this->config['host'];
        $args = array(
            $host, $this->config['username'],
            $this->config['password'], $this->config['name'],
            $this->config['port'], $this->config['socket'],
        );

        if (!class_exists('mysqli', false)) {
            $service->error->show('not_exists_class', array('mysqli'));
        }

        $limitConnect = ocConfig('DATABASE_LIMIT_CONNECT_TIMES', 3);

        for ($i = 1; $i <= $limitConnect; $i++) {
            try {
                $this->instance = new mysqli();
                if (empty($this->instance)) {
                    ocService()->error->show('failed_db_init');
                }

                $timeout = $this->config['timeout'];
                if ($timeout) {
                    $result = $this->instance->options(MYSQLI_OPT_CONNECT_TIMEOUT, $timeout);
                    if (!$result) {
                        $service->error->show('failed_db_set_timeout');
                    }
                }

                error_reporting(0);
                $connect = call_user_func_array(array($this->instance, 'real_connect'), $args);
                if ($connect) {
                    $this->connection = $this->instance;
                    $this->stmt = $this->instance->stmt_init();
                }
            } catch (\Exception $exception) {
                if ($i < $limitConnect) continue;
                $this->errNo = $exception->getCode();
                $this->error = $exception->getMessage();
                $error = array(
                    $this->config['name'], $this->errNo, $this->error
                );
                $service->error->show('failed_db_connect', $error);
            }
            break;
        }
    }

    /**
     * 服务器是否断开连接
     * @return bool
     */
    public function is_not_active()
    {
        return $this->error_no() == '2006';
    }

    /**
     * 唤醒连接
     * @throws Exception
     */
    public function wake_up()
    {
        $this->baseConnect();
    }

    /**
     * 获取连接句柄
     * @return mixed
     */
    public function connection()
    {
        return $this->connection;
    }

    /**
     * 选择数据库
     * @param $name
     * @return mixed
     */
    public function select_db($name)
    {
        return $this->connection->select_db($name);
    }

    /**
     * 获取Statement对象
     * @return mixed
     */
    public function stmt()
    {
        return $this->stmt;
    }

    /**
     * @param string $sql
     * @param int|null $resultmode
     * @return mixed
     */
    public function query($sql, $resultmode = MYSQLI_STORE_RESULT)
    {
        return $this->connection->query($sql);
    }

    /**
     * @param string $sql
     * @param int|null $resultmode
     * @return mixed
     */
    public function query_sql($sql, $resultmode = MYSQLI_STORE_RESULT)
    {
        $this->recordSet = $this->query($sql);
        return $this->recordSet;
    }

    /**
     * @return mixed
     */
    public function close()
    {
        if ($this->prepared) {
            return $this->stmt->close();
        }
        $this->stmt->close();
        $this->connection->close();
    }

    /**
     * 开始一个事务
     * @return mixed
     */
    public function begin_transaction()
    {
        if (method_exists($this->connection, 'begin_transaction')) {
            return $this->connection->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
        } else {
            return $this->query('start transaction');
        }
    }

    /**
     * 提交事务
     * @return mixed
     */
    public function commit()
    {
        return $this->connection->commit();
    }

    /**
     * 回退事务
     * @return mixed
     */
    public function rollback()
    {
        return $this->connection->rollback();
    }

    /**
     * 设置是否自动提交事务
     * @param bool $autocommit
     * @return mixed
     */
    public function autocommit($autocommit = true)
    {
        return $this->connection->autocommit($autocommit);
    }

    /**
     * @return array
     */
    public function fetch_array()
    {
        return $this->recordSet ? $this->recordSet->fetch_array(MYSQLI_ASSOC) : array();
    }

    /**
     * 取一条Object结果
     * @return array
     */
    public function fetch_object()
    {
        return $this->recordSet ? $this->recordSet->fetch_object() : array();
    }

    /**
     * @return array
     */
    public function fetch_assoc()
    {
        return $this->recordSet ? $this->recordSet->fetch_assoc() : array();
    }

    /**
     * 取所有记录
     * @param int $resultmode
     * @return array
     */
    public function fetch_all($resultmode = MYSQLI_ASSOC)
    {
        $result = array();

        if ($this->recordSet) {
            $result = $this->recordSet->fetch_all($resultmode);
        }

        return $result ?: array();
    }

    /**
     * @return array
     */
    public function fetch_row()
    {
        return $this->recordSet ? $this->recordSet->fetch_row() : array();
    }

    public function free_result()
    {
        if ($this->prepared) {
            $this->stmt->free_result();
        }
    }

    /**
     * @return int
     */
    public function num_rows()
    {
        return $this->recordSet ? $this->recordSet->num_rows : 0;
    }

    /**
     * @param int $num
     * @return bool
     */
    public function data_seek($num = 0)
    {
        return $this->recordSet ? $this->recordSet->data_seek($num) : false;
    }

    /**
     * @return int
     */
    public function affected_rows()
    {
        return $this->recordSet ? $this->connection->affected_rows : 0;
    }

    /**
     * @return mixed
     */
    public function error_no()
    {
        if ($this->prepared) {
            $errorCode = $this->stmt->errno;
        } else {
            $errorCode = $this->connection->errno;
        }

        return $errorCode;
    }

    /**
     * @return mixed
     */
    public function error()
    {
        if ($this->prepared) {
            return $this->stmt->error;
        }
        return $this->connection->error;
    }

    /**
     * @return mixed
     */
    public function error_list()
    {
        if ($this->prepared) {
            return $this->stmt->error_list;
        }
        return array($this->connection->error);
    }

    /**
     * 是否存在错误
     * @return bool
     */
    public function error_exists()
    {
        $errorNo = $this->error_no();
        return $errorNo > 0;
    }

    /**
     * @param string $str
     * @return mixed
     */
    public function real_escape_string($str)
    {
        return $this->connection->real_escape_string($str);
    }

    /**
     * @param $charset
     * @return mixed
     */
    public function set_charset($charset)
    {
        return $this->connection->set_charset($charset);
    }

    /**
     * @param $sql
     * @return mixed
     */
    public function show_fields($sql)
    {
        return $this->connection->query($sql);
    }

    /**
     * 预处理
     * @param string $sql
     * @return mixed
     */
    public function prepare($sql)
    {
        $result = $this->stmt->prepare($sql);
        return $result;
    }

    /**
     * 预处理
     * @param string $sql
     * @return mixed
     */
    public function prepare_sql($sql)
    {
        return $this->prepare($sql);
    }

    /**
     * 绑定参数
     * @param string $parameter
     * @param mixed $variable
     * @return mixed
     */
    public function bind_param($parameter, &$variable)
    {
        $args = func_get_args();

        if (isset($args[1])) {
            $args[1] = &$variable;
        }

        $result = call_user_func_array(array($this->stmt, 'bind_param'), $args);
        return $result;
    }

    /**
     * 参数
     * @return mixed
     */
    public function param_count()
    {
        return $this->stmt->param_count;
    }

    /**
     * @return mixed
     */
    public function next_result()
    {
        return $this->stmt->next_result();
    }

    /**
     * 绑定参数
     * @param $vars
     * @return mixed
     */
    public function bind_result(&$vars)
    {
        $result = call_user_func_array(array($this->stmt, 'bind_result'), func_get_args());
        return $result;
    }

    /**
     * @return mixed
     */
    public function get_result()
    {
        return $this->stmt->get_result();
    }

    /**
     * @return mixed
     */
    public function store_result()
    {
        return $this->stmt->store_result();
    }

    /**
     * 执行SQL
     */
    public function execute()
    {
        return $this->stmt->execute();
    }

    /**
     * 执行SQL
     */
    public function execute_sql()
    {
        $result = $this->execute();
        $this->recordSet = $this->get_result();
        $this->free_result();
        return $result;
    }

    /**
     * 未知方法调用的处理
     * @param string $name
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    public function __call($name, $params)
    {
        if ($this->instance && method_exists($this->instance, $name)) {
            return call_user_func_array(array($this->instance, $name), $params);
        }

        parent::__call($name, $params);
    }
}
