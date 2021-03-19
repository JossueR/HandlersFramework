<?php


namespace Handlers\data_access;


class HistoryRepo extends \Handlers\components\CacheHManager
{
    /**
     * @var HistoryRepo
     */
    private static $instance;

    /**
     * @return HistoryRepo
     */
    public static function getInstance(){
        if(self::$instance == null){
            self::$instance = new HistoryRepo();
        }

        return self::$instance;
    }

    /**
     * ConfigVarRepo constructor. privado de clase singleton
     */
    protected  function __construct()
    {
        parent::__construct("HISTORY");
    }

    /**
     * @inheritDoc
     */
    protected function loadUnCachedVar($key)
    {
        // TODO: Implement loadUnCachedVar() method.
    }
}