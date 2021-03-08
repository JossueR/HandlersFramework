<?php


namespace Handlers\models;
use Handlers\data_access\AbstractBaseDAO;

class PermissionsDAO extends AbstractBaseDAO
{
    function __construct() {
        parent::__construct("permissions", array("permission"));
    }

    function getPrototype(){
        $prototype = array(
            'permission'=>null,
            'description'=>null
        );

        return $prototype;
    }

    function getPrototypeFull(){
        $prototype = array(

            'permission'=>null,
            'description'=>null,
            "public"=>null,
        );

        return $prototype;
    }


    function getDBMap(){
        $prototype = array(
            'permission'=>'permission',
            'public'=>'public',
            'description'=>'description'
        );

        return $prototype;
    }

    function getBaseSelec(){
        $sql = "SELECT `permissions`.`permission`,
					    `permissions`.`description`,
					    `permissions`.`public`,
					    CONCAT('[',`permission`,'] ',`description`) AS long_desc
					    
					FROM `permissions`
					WHERE ";

        return $sql;
    }


    function getActives(){



        $sql = $this->getBaseSelec() . "1=1";


        $this->find($sql);
    }

    function getPublicActives(){
        $searchArray["`permissions`.`public`"] = self::REG_ACTIVO_Y;
        $searchArray = self::putQuoteAndNull($searchArray, !self::REMOVE_TAG);
        $where = self::getSQLFilter($searchArray);

        $sql = $this->getBaseSelec() . $where;


        $this->find($sql);

    }




    public function loadPermissions($user_id, $permission_id=null)
    {
        $all_premissions = array();
        $sql_filter = "";

        if($permission_id){
            $searchArray["t1.permission"] = $permission_id;
            $searchArray = self::putQuoteAndNull($searchArray, !self::REMOVE_TAG);
            $sql_filter = self::getSQLFilter($searchArray);
        }


        $sql = "SELECT t1.permission
                from permissions t1
                left join user_permissions t2 on t2.permission=t1.permission 
                left join group_permissions t3 on t3.permission=t1.permission
                left join group_users t4 on t4.group_id=t3.group_id
                WHERE $sql_filter
                and (   t4.user_id='$user_id'
                    or 
                    t2.user_id='$user_id'
                )
                GROUP BY t1.permission";

        $this->find($sql);
        $permisos = $this->fetchAll();

        foreach ($permisos as $value) {
            $all_premissions[] = $value["permission"];
        }



        return $all_premissions;

    }
}