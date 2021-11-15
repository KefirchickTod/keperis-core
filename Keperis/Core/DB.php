<?php

namespace Keperis\Core;

use PDO;
use PDOException;
use Keperis\Controller\Api\ErrorApi;
use Keperis\Core\Database\DatabaseAdapter;
use Keperis\Core\Database\DatabaseInfoScheme;

class DB
{
    private static $instance;
    public $error;
    private $connection;
    private $state;

    private function __construct($dsn = null, $username = null, $password = null, $options = [])
    {
        $dsn = $dsn ?? 'mysql:host=' . env('DB_HOST', '') . ';dbname=' . env('DB_NAME', '');
        $username = $username ?? env('DB_USER', '');
        $password = $password ?? env('DB_PASS', '');
        $options = $options ?? [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"];

        try {
            $this->connection = new PDO($dsn, $username, $password, $options);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->exec("SET time_zone = '" . date('P') . "'");
            $this->connection->exec('SET names utf8');
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }

    public static function getInstance(): DB
    {
        if (empty(self::$instance)) {
            self::$instance = new DB;
        }

        return self::$instance;
    }

    /*
     * @param array();
     * new function by kefir
     * */
    public static function selectSqlArray($paramArray, $instanceDB)
    {
        if (!is_array($paramArray)) {
            return false;
        }
        if (!isset($paramArray["table"]) || empty($paramArray["table"])) {
            return "BAD ARRAY";
        }
        if (!isset($paramArray["select"]) || empty($paramArray["select"])) {
            $paramArray["select"] = "*";
        }
        if (!isset($paramArray["where"]) || empty($paramArray["where"])) {
            $paramArray["where"] = "";
        }
        if (!isset($paramArray["sort"]) || empty($paramArray["sort"])) {
            $paramArray['sort'] = "ASC";
        }

        return $instanceDB->selectSQL($paramArray["table"], $paramArray["select"], $paramArray["where"],
            $paramArray["sort"]);
    }

    public function querySql($sql_string)
    {
        if (isset($sql_string)) {
            try {
                return $this->connection->query($sql_string);
            } catch (PDOException $e) {
                $this->error($e->getMessage(), $sql_string);
            }
        } else {
            $this->error('', '');
        }
    }

    public function error($error, $sql_string, $code = null)
    {
        //  echo $sql_string;

        if ($code) {
            $this->error[intval($code)] = $error . ' / ' . $sql_string;
        } else {

            $this->error[] = $error . ' / ' . $sql_string;
        }

        error_log($error . ' / ' . $sql_string);
        ErrorApi::add(compact('error', 'sql_string'));
    }

    public function isError()
    {
        return !empty($this->error);
    }

    public function addSqlMany($table, $db_array)
    {
        if (isset($db_array, $table) && is_array($db_array) && !empty($db_array)) {
            $db_keys = array_keys($db_array[0]);
            $sql_pre = 'INSERT INTO ' . $table . ' (' . implode(', ', $db_keys) . ') ';
            $sql_pre .= ' VALUES ';
            $sql_pre_temp = [];
            foreach ($db_array as $row) {
                $sql_pre_temp[] = "('" . implode("','", $row) . "')";
            }
            $sql_pre .= implode(', ', $sql_pre_temp);
            try {
                $this->connection->beginTransaction();
                $STH = $this->connection->query($sql_pre);
                $lastInsertId = $this->connection->lastInsertId();
                $this->connection->commit();

                return $lastInsertId;
            } catch (PDOException $e) {
                $this->connection->rollBack();
                $this->error($e->getMessage(), $STH->queryString);
            }
        }
    }

    public function replaceSql($table, $db_array)
    {
        if (isset($db_array, $table) && is_array($db_array)) {
            $db_keys = array_keys($db_array);
            $db_values = array_values($db_array);
            $sql_pre = 'REPLACE INTO ' . $table . ' (' . implode(', ', $db_keys) . ') ';
            $sql_pre .= ' values (' . implode(', ', array_fill(0, count($db_keys), '?')) . ')';
            try {
                $this->connection->beginTransaction();
                $STH = $this->connection->prepare($sql_pre);
                $STH->execute($db_values);
                $lastInsertId = $this->connection->lastInsertId();
                $this->connection->commit();

                return $lastInsertId;
            } catch (PDOException $e) {
                $this->connection->rollBack();
                $this->error($e->getMessage(), $STH->queryString);
            }
        }
    }

    public function insertOrUpdateSql($table, $db_array)
    {
        if (isset($db_array, $table) && is_array($db_array)) {
            $db_keys = array_keys($db_array);
            $db_values = array_values($db_array);
            $db_values = array_merge($db_values, $db_values);
            $sql_pre = 'INSERT INTO ' . $table . ' (' . implode(', ', $db_keys) . ') ';
            $sql_pre .= ' values (' . implode(', ', array_fill(0, count($db_keys), '?')) . ') ';
            $sql_pre .= ' ON DUPLICATE KEY UPDATE ';
            $sql_pre .= implode(' = ? , ', $db_keys) . ' = ? ';

            try {
                $this->connection->beginTransaction();
                $STH = $this->connection->prepare($sql_pre);


                $STH->execute($db_values);

                $lastInsertId = $this->connection->lastInsertId();
                if ($lastInsertId == 0) {
                    $lastInsertId = $STH->rowCount();
                }
                $this->connection->commit();

                return $lastInsertId;
            } catch (PDOException $e) {

                echo($e->getMessage());exit;
                $this->connection->rollBack();
                $this->error($e->getMessage(), $STH->queryString);
                return false;
            }
        }
        return false;
    }

    public function insertOrUpdateSqlMany($table, $db_array)
    {
        if (isset($db_array, $table) && is_array($db_array) && !empty($db_array)) {
            $db_keys = array_keys($db_array[0]);
            $sql_pre = 'INSERT INTO ' . $table . ' (' . implode(', ', $db_keys) . ') ';
            $sql_pre .= ' VALUES ';
            $sql_pre_temp = [];
            foreach ($db_array as $row) {
                $sql_pre_temp[] = "('" . implode("','", $row) . "')";
            }
            $sql_pre .= implode(', ', $sql_pre_temp);
            $sql_pre .= ' ON DUPLICATE KEY UPDATE ';
            $sql_pre_temp_key = [];
            foreach ($db_keys as $key) {
                $sql_pre_temp_key[] = ' ' . $key . '=VALUES(' . $key . ') ';
            }
            $sql_pre .= implode(', ', $sql_pre_temp_key);
            try {
                $this->connection->beginTransaction();
                $STH = $this->connection->query($sql_pre);
                $lastInsertId = $this->connection->lastInsertId();
                if ($lastInsertId == 0) {
                    $lastInsertId = $STH->rowCount();
                }
                $this->connection->commit();

                return $lastInsertId;
            } catch (PDOException $e) {
                $this->connection->rollBack();
                $this->error($e->getMessage(), $STH->queryString);
                return false;
            }
        }
        return false;
    }

    public function selectSqlPrepared($sql_pre)
    {
        try {
            $STH = $this->connection->prepare($sql_pre);
            $STH->execute();

            return $STH->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->error($e->getMessage(), $STH->queryString);
            return false;
        }
    }

    public function deleteSql($table, $db_array)
    {
        if (isset($db_array, $table) && is_array($db_array)) {
            $db_keys = array_keys($db_array);
            $db_values = array_values($db_array);
            $sql_pre = 'DELETE FROM ' . $table . ' WHERE ';
            $sql_pre .= implode('=? AND ', $db_keys) . '=? ';
            try {
                $this->connection->beginTransaction();
                $STH = $this->connection->prepare($sql_pre);
                $STH->execute($db_values);
                $this->connection->commit();

                return $STH->rowCount();
            } catch (PDOException $e) {
                $this->connection->rollBack();
                $this->error($e->getMessage(), $STH->queryString);

                return false;
            }
        }
        return false;
    }

    public function deleteManyRowsSql($table, $db_array)
    {
        if (isset($db_array, $table) && is_array($db_array)) {
            $where_array = [];
            foreach ($db_array as $field => $field_data) {
                if (is_array($field_data)) {
                    $where_array[] = ' ' . $field . " IN ('" . implode("','", $field_data) . "')";
                } else {
                    $where_array[] = ' ' . $field . "='" . $field_data . "' ";
                }
            }
            $sql_pre = 'DELETE FROM ' . $table . ' WHERE ' . implode(' AND ', $where_array);
            try {
                $this->connection->beginTransaction();
                $STH = $this->connection->query($sql_pre);
                $this->connection->commit();

                return $STH->rowCount();
            } catch (PDOException $e) {
                $this->connection->rollBack();
                $this->error($e->getMessage(), $STH->queryString);

                return false;
            }
        }
        return false;
    }

    public function insertOrUpdateSqlNoUnique($table, $db_array, $where, $return_field = '')
    {
        $row = $this->selectSql($table, '*', $where);
        if (!$row) {
            $result = $this->addSql($table, $db_array);
        } else {
            $this->updateSql($table, $db_array, $where, true);
            if ($return_field && isset($row[0][$return_field])) {
                $result = $row[0][$return_field];
            } else {
                $result = true;
            }
        }

        return $result;
    }

    public function selectSql($table, $select = '*', $where = '', $order = '', $group = '', $limit = '', $join = '')
    {
        $sql_pre = 'SELECT ' . $select . ' FROM ' . $table;
        if ($join) {
            $sql_pre .= ' ' . $join . ' ';
        }
        if ($where) {
            $sql_pre .= ' WHERE ' . $where;
        }
        if ($group) {
            $sql_pre .= ' GROUP BY ' . $group;
        }
        if ($order) {
            $sql_pre .= ' ORDER BY ' . $order;
        }
        if ($limit) {
            $sql_pre .= ' LIMIT ' . $limit;
        }
        // var_dump($sql_pre);exit;
        try {
            $STH = $this->connection->prepare($sql_pre);
            $STH->execute();

            return $STH->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            var_dump($e->getMessage());
            $this->error($e->getMessage() . $sql_pre, $STH->queryString);
        }
    }

    public function addSql($table, $db_array, $ignore = false)
    {
        if (isset($db_array, $table) && is_array($db_array)) {
            $db_keys = array_keys($db_array);
            $db_values = array_values($db_array);
            $sql_pre = 'INSERT ' . ($ignore ? 'IGNORE ' : '') . 'INTO ' . $table . ' (' . implode(', ',
                    $db_keys) . ') ';
            $sql_pre .= ' values (' . implode(', ', array_fill(0, count($db_keys), '?')) . ')';
            try {
                $this->connection->beginTransaction();
                $STH = $this->connection->prepare($sql_pre);
                $STH->execute($db_values);
                $lastInsertId = $this->connection->lastInsertId();
                $this->connection->commit();

                return $lastInsertId;
            } catch (PDOException $e) {
                $this->connection->rollBack();
                $this->error($e->getMessage(), $STH->queryString);
                return $e->getMessage();
            }
        }
        return false;
    }

    public function updateSql($table, $db_array, $where, $returnCount = false)
    {
        if (isset($db_array, $table) && is_array($db_array)) {
            $db_keys = array_keys($db_array);
            $db_values = array_values($db_array);
            $sql_pre = 'UPDATE ' . $table . ' SET ';
            $sql_pre .= implode(' = ? , ', $db_keys) . ' = ? ';
            if ($where) {
                $sql_pre .= ' WHERE ' . $where;
            }


            try {
                $this->connection->beginTransaction();
                $STH = $this->connection->prepare($sql_pre);



                $STH->execute($db_values);
                $this->connection->commit();
                if ($returnCount) {
                    return $STH->rowCount();
                }

                return true;
            } catch (PDOException $e) {

                var_dump($e->getMessage(), $db_array);exit;

                $this->connection->rollBack();
                $this->error($e->getMessage(), $STH->queryString, $e->getCode());
                return false;
            }
        }
        return false;
    }

    public function valid($table_name, $db_array)
    {
        try {
            $valid = [];
            $data = $this->connection->query("SELECT DATA_TYPE as type, COLUMN_NAME as name  FROM information_schema.COLUMNS WHERE TABLE_NAME='{$table_name}'")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($data as $schema) {
                if (array_key_exists($schema['name'], $db_array)) {
                    $value = $db_array[$schema['name']];
                    $valid[$schema['name']] = $this->toType($schema['type'], $value);
                }
            }
            return $valid;
        } catch (PDOException $exception) {
            $this->error($exception->getMessage(), $db_array);
            return [];
        }

    }

    private function toType($type, $value)
    {
        if ($type === 'int') {
            return intval($value);
        }
        if ($type === 'float') {
            return floatval($value);
        }
        if ($type === 'tinyint') {
            return intval($value);
        }
        return $value;
    }

    public function databaseValidation($table, $db_array)
    {
        if ($this->state && array_key_exists($table, $this->state)) {
            $schema = $this->state[$table];
        } else {
            $schema = new DatabaseInfoScheme($table,
                DatabaseAdapter::createDataBaseConnectionByPdo($this->getConnection()));
            $this->state[$table] = $schema;
        }
        $valid = [];
        foreach ($db_array as $name => $value) {

            if ($schema->isColumn($name)) {

                $callbackType = $schema->getColumnDataTypeCallback($name);

                $value = call_user_func($callbackType, $value);
                if ($callbackType === 'strval' && strlen($value) >= $schema->getColumnMaxLength($name)) {
                    $value = mb_substr($value, 0, $schema->getColumnMaxLength($name) / 2);

                }

                $valid[$name] = $value;
            }
        }

        return $valid;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    public function dynamicInsert($table_name, $db_array, $method = 'insertOrUpdateSql', $where = ''): bool
    {
        try {
            $list = $this->connection->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '{$table_name}'")->fetchAll(PDO::FETCH_ASSOC);
            $list = array_flip(array_map(function ($value) {
                return $value['COLUMN_NAME'];
            }, $list));
            $data = [];

            foreach ($db_array as $name => $value) {

                if (isset($list[$name])) {
                    $data[$name] = $value;
                }
            }
            return call_user_func([$this, $method], $table_name, $data, $where);
        } catch (PDOException $exception) {
            $this->connection->rollBack();
            $this->error($exception->getMessage(), '');
            error_log($exception->getMessage());
            debug($exception, __CLASS__, __LINE__);
            return false;
        }
    }
}
