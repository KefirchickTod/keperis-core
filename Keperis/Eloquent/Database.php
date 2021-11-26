<?php

namespace Keperis\Eloquent;

interface Database
{

    /**
     * @return \PDO
     */
    public function getConnection() : \PDO;
    /**
     * Call query
     * @param string $query
     * @return \PDOStatement|false
     */
    public function querySql(string $query);


    /**
     * Insert to model or by query data [column => value]
     * @param string|null $table
     * @param array $values
     * @return mixed
     */
    public function insertOrUpdateSql(?string $table, array $values);

    /**
     * The same that a {insertOrUpdateSql} but for second virtual array
     * @param string|null $table
     * @param array $values
     * @return mixed
     */
    public function insertOrUpdateSqlMany(?string $table, array $values);

    /**
     * Prepare and execute query
     * @param string $query
     * @return array
     */
    public function selectSqlPrepared(string $query) : array;


    /**
     * Delete from table add data by conditional [keys of values =   values];
     * @param string|null $table
     * @param array $values
     * @return mixed
     */
    public function deleteSql(?string $table, array $values);

    /**
     * Remove many rows....
     * @param string|null $table
     * @param array $values
     * @return mixed
     */
    public function deleteManyRowsSql(?string $table, array $values);

    /**
     * @param string|Model $table
     * @param string $select
     * @param string $where
     * @param string $order
     * @param string $group
     * @param string $limit
     * @param string $join
     * @return mixed
     */
    public function selectSql(
        ?string $table,
        string $select = '*',
        string $where = '',
        string $order = '',
        string $group = '',
        string $limit = '',
        string $join = ''
    );

    /**
     * @param string|null $table
     * @param array $db_array
     * @param false $ignore
     * @return mixed
     */
    public function addSql(?string $table, array $db_array, bool $ignore = false);


    /**
     * @param string|null $table
     * @param array $values
     * @param $where
     * @param false $returnCount
     * @return mixed
     */
    public function updateSql(?string $table, array $values, $where, bool $returnCount = false);

    /**
     * Valid columns names
     * @param string|null $table
     * @param array $values
     * @return array
     */
    public function valid(?string $table, array $values): array;


    /**
     * Long valid
     * @param string|null $table
     * @param array $values
     * @return array
     */
    public function databaseValidation(?string $table, array $values): array;

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
    ): bool;

}
