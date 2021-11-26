<?php

namespace Keperis\Eloquent;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;

class DB implements Database
{

    const DISTINCT = false;

    /**
     * @var Database|null
     */
    private static $singleton = null;


    /**
     * @var \Illuminate\Database\MySqlConnection
     */
    private $connection;

    private function __construct()
    {
        /**
         * @return \PDO
         */
        $pdo = static function () {
            $c = container()->connection->getPdo();
            $c->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $c->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_NATURAL);
            $c->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            $c->exec("SET time_zone = '" . date('P') . "'");
            $c->exec('SET names utf8');
            return $c;
        };


        $this->connection = \container()->connection->setPdo($pdo());
    }

    public function getQueryBuilder()
    {
        return new Builder($this->connection);
    }

    /**
     * @return \PDO
     */
    public function getConnection(): \PDO
    {
        return $this->getQueryBuilder()->getConnection()->getPdo();
    }

    /**
     * Call query
     * @param string $query
     * @return \PDOStatement|false
     */
    public function querySql(string $query): ?\PDOStatement
    {
        try {
            return $this->getQueryBuilder()->getConnection()->getPdo()->query($query);
        } catch (\PDOException $exception) {
            error_log($exception->getMessage());
            die($exception->getMessage());
        }
    }


    /**
     * @param string|Model $table
     * @return Builder|\Illuminate\Database\Eloquent\Model
     */
    private function tableToBuilder($table)
    {
        if ($table instanceof Model) {
            return $table;
        }


        return $this->getQueryBuilder()->fromRaw(self::raw($table));
    }

    /**
     * Insert to model or by query data [column => value]
     * @param string|null $table
     * @param array $values
     * @return mixed
     */
    public function insertOrUpdateSql(?string $table, array $values)
    {
        $table =
            $this->tableToBuilder($table)
                ->upsert($values, array_keys($values));


        return $table->getPdo()->lastInsertId();

    }

    /**
     * The same that a {insertOrUpdateSql} but for second virtual array
     * @param string|null $table
     * @param array $values
     * @return mixed
     */
    public function insertOrUpdateSqlMany(?string $table, array $values)
    {
        foreach ($values as $value) {
            $table = $this->insertOrUpdateSql($table, $values);
        }
        return $table->getPdo()->lastInsertId();
    }

    /**
     * Prepare and execute query
     * @param string $query
     * @return array
     */
    public function selectSqlPrepared(string $query): array
    {
        return collect($this->getQueryBuilder()->getConnection()->select($query))->map(function ($row) {
            return (array)$row;
        })->toArray();

    }

    /**
     * @param string $query
     * @return Expression
     */
    public static function raw(string $query)
    {
        return new Expression($query);
    }

    /**
     * Delete from table add data by conditional [keys of values =   values];
     * @param string|null $table
     * @param array $values
     * @return mixed
     */
    public function deleteSql(?string $table, array $values)
    {
        $table =
            $this->tableToBuilder($table)
                ->whereRaw(implode(" ", array_map(function ($key, $value) {
                    return "$key = $value";
                }, array_keys($values), $values)));

        $table->delete();

        return $table->count();
    }

    /**
     * Remove many rows....
     * @param string|null $table
     * @param array $values
     * @return mixed
     */
    public function deleteManyRowsSql(?string $table, array $values)
    {
        foreach ($values as $value) {
            $c = $this->deleteSql($table, $value);
        }
        return $c;
    }

    /**
     * @param string|Model $table
     * @param string $select
     * @param string $where
     * @param string $order
     * @param string $group
     * @param string $limit
     * @param string $join
     * @return []|null
     */
    public function selectSql(
        ?string $table,
        string $select = '*',
        string $where = '',
        string $order = '',
        string $group = '',
        string $limit = '',
        string $join = ''
    ) {

        $builder = $this->tableToBuilder($table);

        $builder->selectRaw($select);

        if ($where) {

            $builder->whereRaw($where);
        }

        if ($order) {
            $builder->orderByRaw($order);
        }

        if ($group) {
            $builder->groupByRaw($group);
        }


        try {
            $fetch = $builder->get()->map(function ($value) {
                return (array)$value;
            })->toArray();

        } catch (\Exception $exception) {
            if (boolval(env('APP_DEBUG')) === true) {
                debug($exception->getMessage());
            }
        }


        return $fetch;
    }

    /**
     * @param string|null $table
     * @param array $db_array
     * @param false $ignore
     * @return mixed
     */
    public function addSql(?string $table, array $db_array, bool $ignore = false)
    {
        $insert = $this
            ->tableToBuilder($table)
            ->insert($db_array);

        if (!$insert) {
            throw new \PDOException("Cant save data");
        }
        return $insert;
    }

    /**
     * @param string|null $table
     * @param array $values
     * @param $where
     * @param false $returnCount
     * @return mixed
     */
    public function updateSql(?string $table, array $values, $where, bool $returnCount = false)
    {

        return $this->tableToBuilder($table)
            ->whereRaw($where)
            ->update($values);
    }

    protected function getColumns(string $table): array
    {
        return $this->connection->getSchemaBuilder()->getColumnListing($table);
    }

    /**
     * Valid columns names
     * @param string|null $table
     * @param array $values
     * @return array
     */
    public function valid(?string $table, array $values): array
    {

        $columns = $this->getColumns($this->getTableAsString($table),
            $this->tableToBuilder($table)->getConnection());
        $r = [];

        foreach ($columns as $column) {
            if (array_key_exists($column, $values)) {
                $r[$column] = $values[$column];
            }
        }
        return $r;
    }

    /**
     * Get string table name for build scheme functionals
     * @param string|null $table
     * @return string
     */
    private function getTableAsString(?string $table)
    {
        if ($table instanceof \Illuminate\Database\Eloquent\Model) {
            return $table->getTable();
        }

        if (!is_string($table)) {
            throw new \TypeError(sprintf("Invalid type for table [%s]", gettype($table)));
        }
        return $table;
    }

    /**
     * Long valid
     * @param string|null $table
     * @param $values
     * @return array
     */
    public function databaseValidation(?string $table, $values): array
    {


        $scheme = $this->connection->getDoctrineConnection()->query($this->connection
            ->getDoctrineSchemaManager()
            ->getDatabasePlatform()->getListTableColumnsSQL($table))->fetchAll(\PDO::FETCH_ASSOC);


        $columns = array_column($scheme, 'Type', 'Field');

        $max = 0;

        foreach ($values as $column => &$value) {


            if (!array_key_exists($column, $columns)) {
                unset($values[$column]);
                if (self::DISTINCT) {
                    throw new \Exception(sprintf("Undefined column key [%s] in table [%s]", $column,
                        $this->getTableAsString($table)));
                }

                continue;
            }

            $type = $columns[$column];

            if (preg_match('/[a-z]+\([0-9]{1,}\)/', $type)) {
                [$type, $max] = preg_split('/\(|\)/', $type);
            }

            $value = $this->toType($this->getColumnDataTypeCallback($type), $value);

        }

        return $values;

    }

    /**
     * Return callback name for convert to type value
     * @param string $column
     * @return string
     */
    private function getColumnDataTypeCallback(string $type): string
    {
        switch ($type) {

            case 'mediumtext':
            case 'longtext':
            case 'text':
            case 'char' :

            case 'varchar' :
                $callback = 'strval';
                break;

            case 'tinyint':
            case 'bigint':
            case 'int':
            case 'integer':
                $callback = 'intval';
                break;
            case 'float':
            case 'double':
            case 'decimal':
                $callback = 'floatval';
                break;
            case 'json':
                $callback = 'json_encode';
                break;
            case 'date':
            case 'datetime' :
                $callback = 'date';
                break;
            default:

                $callback = null;
                break;
        }

        return $callback;
    }

    /**
     * Convert value to type
     * @param $type
     * @param $value
     * @return float|int
     */
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


    /**
     * Insert with valid
     * @param string|null $table
     * @param array $values
     * @param string $method
     * @param string $where
     * @return bool
     */
    public function dynamicInsert(
        ?string $table,
        array $values,
        string $method = 'insertOrUpdateSql',
        string $where = ''
    ): bool {

        $builder =
            $this->tableToBuilder($table);

        try {
            $values = $this->databaseValidation($table, $values);
        } catch (\Exception $exception) {
            error_log($exception->getMessage());
        }

        return $builder->updateOrInsert($values);
    }

    /**
     * Pattern singleton
     * @return Database|static|null
     */
    public static function getInstance()
    {
        if (is_null(self::$singleton)) {
            self::$singleton = new static();
        }

        return self::$singleton;
    }
}
