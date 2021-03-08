<?php


namespace Handlers\data_access;




abstract class BDEngine
{
    public $verbose = false;
    public $debug_log = false;
    public $debug_tag = "";
    public $debug_table = "debug_log";
    /**
     * @var bool
     */
    protected $transaction_in_process = false;

    /**
     * @param $host
     * @param $bd
     * @param $usuario
     * @param $pass
     * @return Connection
     */
    abstract function connect($host, $bd, $usuario, $pass);

    /**
     * @param Connection $conection
     * @param $last_sql
     * @return mixed
     */
    abstract function storeDebugLog(Connection $conection, $last_sql);

    /**
     * @param Connection $connection
     * @param $sql
     * @param bool $isSelect
     * @param QueryDynamicParams|null $queryparams
     * @return QueryInfo
     */
    abstract function &execQuery(Connection $connection, $sql, $isSelect = true, QueryDynamicParams $queryparams = null);

    /**
     * @param QueryInfo $sumary
     * @return mixed
     */
    abstract function getNext(QueryInfo $sumary);

    /**
     * @param Connection $connection
     * @param string $str
     * @return string
     */
    abstract public function escape(Connection $connection, $str);

    abstract function StartTransaction(Connection $connectionName);

    abstract function CommitTransaction(Connection $connectionName);

    abstract function RollBackTransaction(Connection $connectionName);

    /**
     * @param $table
     * @param $searchArray
     * @param Connection $connectionName
     * @return QueryInfo
     */
    abstract function &_insert($table, $searchArray, Connection $connectionName);

    /**
     * @param string $table
     * @param array $searchArray
     * @param array $condition
     * @param Connection $connectionName
     * @param string $noToshTag
     * @return QueryInfo
     */
    abstract  function &_update($table, $searchArray, $condition, Connection $connectionName, $noToshTag);

    abstract function getSQLFilter($filterArray, $join, $noToshTag);

    /**
     * @param string $table
     * @param array $condition
     * @param Connection $connectionName
     * @param string $noToshTag
     * @return QueryInfo
     */
    abstract function &_delete($table, $condition, $connectionName, $noToshTag);

    abstract function valueNOW($noToshTag);

    abstract function valueISNULL($noToshTag);

    abstract function disableForeignKeyCheck(Connection $connectionName);

    abstract function enableForeignKeyCheck(Connection $connectionName);

    abstract function resetPointer(QueryInfo &$summary, $pos = 0);

    abstract function getNumFields(QueryInfo &$sumary);

    abstract function getFieldInfo(QueryInfo &$sumary, $i);

    /**
     * @param $sql
     * @param QueryDynamicParams $params
     * @return string
     */
    abstract  function addPagination($sql, QueryDynamicParams $params);

    /**
     * @param $sql
     * @param QueryDynamicParams $params
     * @return string
     */
    abstract function addOrder($sql, QueryDynamicParams $params);

    /**
     * @param $sql
     * @param QueryDynamicParams $params
     * @param Connection $connectionName
     * @param string $mergeTag
     * @return string
     */
    abstract function addFilters($sql, QueryDynamicParams $params, Connection $connectionName, $mergeTag);

    /**
     * @param $table
     * @param $searchArray
     * @param Connection $connectionName
     * @param $noToshTag
     * @return bool
     */
    abstract function existBy($table, $searchArray, Connection $connectionName, $noToshTag);
}