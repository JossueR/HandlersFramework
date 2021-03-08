<?php


namespace Handlers\data_access;


class QueryInfo
{
    public $result = null;

    public $total = null;

    public $new_id = null;

    public $allRows = null;

    public $errorNo = null;

    public $error = null;

    public $inArray = null;

    public $inAssoc = null;

    public $sql;

    private $connectionName;

    /**
     * QueryInfo constructor.
     * @param $connectionName
     */
    public function __construct($connectionName=null)
    {
        $this->connectionName = $connectionName;
        $this->inArray = true;
        $this->inAssoc = true;
    }

    /**
     * @return mixed|null
     */
    public function getConnectionName()
    {
        return $this->connectionName;
    }


}