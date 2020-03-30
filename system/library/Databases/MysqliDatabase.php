<?php
/**
 * Mysql数据库类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Databases;

use \ReflectionException;
use Ocara\Core\DatabaseBase;
use Ocara\Core\Sql;
use Ocara\Interfaces\Database as DatabaseInterface;

use Ocara\Exceptions\Exception;
use Ocara\Sql\Generator;

class MysqliDatabase extends DatabaseBase implements DatabaseInterface
{
    /**
     * @var $pdoName pdo扩展名
     * @var $defaultPort 默认数据库端口
     */
    protected $pdoName = 'pdo_mysql';
    protected $defaultPort = '3306';

    /**
     * SQL过滤关键字
     * @var array
     */
    protected $keywords = array(
        'select', 'insert', 'update', 'replace',
        'join', 'delete', 'union', 'load_file',
        'outfile'
    );

    /**
     * 不加引号的字段类型
     * @var array
     */
    protected static $quoteBackList = array(
        'tinyint', 'smallint', 'int',
        'decimal', 'timestamp', 'float',
        'bigint', 'mediumint', 'double',
        'integer', 'year', 'bit',
        'bool', 'boolean',
    );

    /**
     * @param array $config
     * @return array
     */
    public function getPdoParams($config)
    {
        $config['port'] = $config['port'] ?: $this->defaultPort;
        $params = array(
            'dsn' => "mysql:dbname={$config['name']};host={$config['host']};port={$config['port']}",
            'username' => $config['username'],
            'password' => $config['password'],
            'options' => $config['options'],
            'name' => $config['name']
        );

        return $params;
    }

    /**
     * 获取字段
     * @param string $sqlData
     * @return array
     * @throws Exception
     */
    public function getFieldsInfo($sqlData)
    {
        $data = $this->query($sqlData);
        $fields = array('autoIncrementField' => OC_EMPTY, 'list' => array());
        $isComment = ocConfig('USE_FIELD_DESC_LANG', false);

        foreach ($data as $row) {
            $fieldRow = array();
            $fieldRow['name'] = $row['Field'];

            if (strstr($row['Type'], '(')) {
                $pos = strpos($row['Type'], '(', 0);
                $endPos = strpos($row['Type'], ')', 0);
                $fieldRow['type'] = strtolower(substr($row['Type'], 0, $pos));
                $fieldRow['length'] = (integer)substr($row['Type'], $pos + 1, $endPos - $pos - 1);
            } else {
                $fieldRow['type'] = $row['Type'];
                $fieldRow['length'] = 0;
            }
            if ($isComment && $row['Comment']) {
                $fieldRow['lang'] = $row['Comment'];
            } else {
                $fieldRow['lang'] = ocHump($fieldRow['name'], OC_SPACE);
            }

            $fieldRow['isNull'] = $row['Null'] == 'NO' ? OC_FALSE : OC_TRUE;
            $fieldRow['isPrimary'] = $row['Key'] == 'PRI' ? OC_TRUE : OC_FALSE;
            $fieldRow['defaultValue'] = (string)$row['Default'];
            $fieldRow['extra'] = $row['Extra'];
            $fields['list'][$row['Field']] = $fieldRow;

            if ($row['Extra'] == 'auto_increment') {
                $fields['autoIncrementField'] = $fieldRow['name'];
            }
        }

        return $fields;
    }

    /**
     * 加密字符串
     * @param string $content
     * @return mixed
     * @throws Exception
     */
    public function escapeString($content)
    {
        return $this->plugin()->real_escape_string($content);
    }

    /**
     * 选择数据库
     * @param string $name
     * @return bool|mixed|void
     * @throws Exception
     */
    public function baseSelectDatabase($name = null)
    {
        if ($this->isPdo()) {
            $generator = new Generator($this);
            $sql = $generator->getSelectDbSql($name);
            $result = $this->query($sql);
            return $result !== false;
        }

        return $this->plugin()->select_db($name);
    }

    /**
     * 单个字段值格式化为适合类型
     * @param array $fieldsData
     * @param string $field
     * @param string $value
     * @return mixed
     */
    public function formatOneFieldValue($fieldsData, $field, $value)
    {
        $type = 'string';

        if (isset($fieldsData[$field]) && in_array($fieldsData[$field]['type'], self::$quoteBackList)) {
            $type = $fieldsData[$field]['type'];
            if (in_array($type, array('float', 'decimal'))) {
                $type = 'float';
            } elseif ($type == 'double') {
                $type = 'double';
            } elseif (strstr($type, 'bool')) {
                $type = 'boolean';
            } else {
                $type = 'integer';
            }
        }

        if (!Sql::checkSqlTag($value)) {
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    settype($value[$k], $type);
                }
            } elseif (ocScalar($value)) {
                settype($value, $type);
            }
        }

        return $value;
    }

    /**
     * 获取字段定义描述
     * @param array $fieldInfo
     * @param array $exceptKeys
     * @return array
     */
    public function getFieldDefinesData($fieldInfo, $exceptKeys = array())
    {
        $data = array('$' . $fieldInfo['name']);

        $data['type'] = $fieldInfo['type'] . ($fieldInfo['length'] ? "({$fieldInfo['length']})" : OC_EMPTY);
        $data['isNull'] = ($fieldInfo['isNull'] ? 'null' : 'not null');

        if ($fieldInfo['isPrimary']) {
            $data['isPrimary'] = 'primary key';
        }

        if (!ocEmpty($fieldInfo['defaultValue'])) {
            $data['defaultValue'] = 'default ' . $fieldInfo['defaultValue'];
        }

        if ($fieldInfo['extra']) {
            $data['extra'] = $fieldInfo['extra'];
        }

        if ($fieldInfo['lang']) {
            $data['lang'] = $fieldInfo['lang'];
        }

        if ($exceptKeys) {
            $data = array_diff_key($data, array_fill_keys($exceptKeys, null));
        }

        return $data;
    }
}