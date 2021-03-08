<?php


namespace Handlers\data_access;


use Handlers\components\CacheHManager;

use Handlers\models\ConfigVarDAO;

class ConfigVarRepo extends CacheHManager
{
    /**
     * @var ConfigVarRepo
     */
    private static $instance;

    /**
     * ConfigVarRepo constructor. privado de clase singleton
     */
    protected  function __construct()
    {
        parent::__construct("CONF");
    }

    /**
     * @return ConfigVarRepo
     */
    public static function getInstance(){
        if(self::$instance == null){
            self::$instance = new ConfigVarRepo();
        }

        return self::$instance;
    }


    protected function loadUnCachedVar($key)
    {
        $value = null;

        $dao = new ConfigVarDAO();
        $dao->getById(array("var"=>$key));
        $row = $dao->get();

        if(isset($row["var"])){
            $value = $row["val"];

        }
        return $value;
    }
}