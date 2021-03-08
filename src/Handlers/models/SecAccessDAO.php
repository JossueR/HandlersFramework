<?php


namespace Handlers\models;

use Handlers\data_access\AbstractBaseDAO;
class SecAccessDAO extends AbstractBaseDAO
{
    function __construct() {
        parent::__construct("sec_access", array("invoker"));
    }

    function getPrototype(){
        return array(
            'invoker'=>null,
            'method' => null,
            'permission' => null,
            'description' => null
        );
    }


    function getDBMap(){
        return array(
            'invoker' => 'invoker',
            'method' => 'method',
            'permission' => 'permission',
            'description' => 'description',
            'active' => 'active'
        );
    }

    function getBaseSelec(){


        return "SELECT `sec_access`.`invoker`,
					    `sec_access`.`method`,
					    `sec_access`.`permission`,
					    `sec_access`.`description`,
					    `sec_access`.`create_date`,
					    `sec_access`.`create_user`,
					    `sec_access`.`update_date`,
					    `sec_access`.`update_user`,
					    `sec_access`.`active`
					FROM `sec_access`
					WHERE ";
    }


    function getActives(){
        $searchArray["sec_access.active"] = self::REG_ACTIVO_TX;
        $searchArray = self::putQuoteAndNull($searchArray, !self::REMOVE_TAG);
        $where = self::getSQLFilter($searchArray);

        $sql = $this->getBaseSelec() . $where;


        $this->find($sql);
    }

    function getMethodRules($method){
        $searchArray["sec_access.method"] = $method;
        $searchArray["sec_access.active"] = self::REG_ACTIVO_TX;
        $searchArray = self::putQuoteAndNull($searchArray, !self::REMOVE_TAG);
        $where = self::getSQLFilter($searchArray);

        $sql = $this->getBaseSelec() . $where;


        $this->find($sql);
    }





    function getInactives(){
        $searchArray["sec_access.active"] = self::REG_DESACTIVADO_TX;
        $searchArray = self::putQuoteAndNull($searchArray, !self::REMOVE_TAG);
        $where = self::getSQLFilter($searchArray);

        $sql = $this->getBaseSelec() . $where;


        $this->find($sql);
    }


/*

    function &insert($searchArray){
        $defaul["create_date"] = self::$SQL_TAG."now()";
        $defaul["create_user"] = self::getDataVar("USER_NAME");
        $defaul["active"] = self::REG_ACTIVO_TX;
        $defaul = parent::putQuoteAndNull($defaul);

        $searchArray = array_merge($searchArray, $defaul);


        return parent::insert($searchArray);

    }

    function &update($searchArray, $condicion){
        $defaul["update_date"] = self::$SQL_TAG."now()";
        $defaul["update_user"] = self::getDataVar("USER_NAME");
        $defaul = parent::putQuoteAndNull($defaul);

        $searchArray = array_merge($searchArray, $defaul);
        return parent::update($searchArray, $condicion);
    }
*/
    function getByUser($user_id){
        $searchArray["t1.active"] = self::REG_ACTIVO_TX;
        $searchArray = self::putQuoteAndNull($searchArray, !self::REMOVE_TAG);
        $where1 = self::getSQLFilter($searchArray);

        $searchArray = array();
        $searchArray["t2.user_id"] = $user_id;
        $searchArray = self::putQuoteAndNull($searchArray, !self::REMOVE_TAG);
        $where2 = self::getSQLFilter($searchArray);

        $sql = "SELECT t1.`invoker`, t1.method, t1.permission, t1.description
					from sec_access t1
					WHERE
					$where1
					and 
					(
						(t1.permission is NULL)
						or 
						(EXISTS (
								SELECT * 
								from user_permissions t2 
								WHERE 
								t2.permission = t1.permission 
								and $where2
							))
						or 
						(EXISTS (
								SELECT * 
								from group_permissions t3 
								join group_users t2 on t2.group_id=t3.group_id
								WHERE 
								t3.permission = t1.permission
								and $where2
							))
					)";


        $this->find($sql);
    }

    function getByRol($rol_id){
        $searchArray["t1.active"] = self::REG_ACTIVO_TX;
        $searchArray = self::putQuoteAndNull($searchArray, !self::REMOVE_TAG);
        $where1 = self::getSQLFilter($searchArray);

        $searchArray = array();
        $searchArray["t2.group_id"] = $rol_id;
        $searchArray = self::putQuoteAndNull($searchArray, !self::REMOVE_TAG);
        $where2 = self::getSQLFilter($searchArray);

        $sql = "SELECT t1.`invoker`, t1.method, t1.permission, t1.description
					from sec_access t1
					WHERE
					$where1
					and 
					(
						(t1.permission is NULL)
						or 
						(EXISTS (
								SELECT * 
								from group_permissions t2 
								WHERE 
								t2.permission = t1.permission
								and $where2
							))
					)";


        $this->find($sql);
    }
}