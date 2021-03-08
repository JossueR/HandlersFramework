<?php


namespace Handlers\models;


use Handlers\data_access\AbstractBaseDAO;

class ConfigVarDAO extends AbstractBaseDAO
{


    private static $cashe_vars;

    function __construct() {
        parent::__construct("config_vars", array("var"));
    }

    function getPrototype(){
        $prototype = array(
            'var'=>null,
            'val'=>null
        );

        return $prototype;
    }


    function getDBMap(){
        $prototype = array(
            'var'=>'var',
            'val'=>'val'
        );

        return $prototype;
    }

    function getBaseSelec(){
        /** @noinspection SqlWithoutWhere */
        $sql = "SELECT `config_vars`.`var`,
					    `config_vars`.`val`
					    
					FROM `config_vars`
					WHERE ";

        return $sql;
    }


    function getActives(){
        $searchArray["config_vars.var"] = self::$SQL_TAG . " is not null";
        $searchArray = self::putQuoteAndNull($searchArray, !self::REMOVE_TAG);
        $where = self::getSQLFilter($searchArray);

        $sql = $this->getBaseSelec() . $where;


        $this->find($sql);
    }

    public function getVar($var, $force_reload=false)
    {
        if(self::$cashe_vars == null){
            self::$cashe_vars = array();
        }

        //si no esta en cache o si se fuerza la recarga
        if(!isset(self::$cashe_vars[$var]) || $force_reload){

            $this->getById(array("var"=>$var));
            $row = $this->get();

            if(isset($row["var"])){
                self::$cashe_vars[$var] = $row["val"];
            }

        }else{
            //var_dump("cashe: $var");
            $row["var"] = $var;
            $row["val"] = self::$cashe_vars[$var];
        }



        return (isset($row["var"]))?  $row["val"]: null;
    }

    public function setVar($var,$val)
    {
        $prototype = array();
        $prototype["var"]= $var;
        $prototype["val"]= $val;

        return $this->save($prototype);
    }
}