<?php


namespace Handlers\models;


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

    function getAsocToUser($user_id){

        $sql = "SELECT `permissions`.`permission`,
					    `permissions`.`description`
					    
					FROM `permissions`
					JOIN user_permissions up on up.permission=`permissions`.`permission`
					WHERE up.user_id=$user_id
					GROUP BY `permissions`.`permission`";


        $this->find($sql);
    }

    function getNotAsocToUser($user_id, $public=null){
        $sql_public = "";
        if(!$public){

            $sql_public = "and permissions.public = '".self::REG_ACTIVO_Y."'";
        }

        $sql = "SELECT `permissions`.`permission`,
					    `permissions`.`description`
					    
					FROM `permissions`
					WHERE `permissions`.`permission` not in (
						select  up.permission from user_permissions up WHERE up.user_id=$user_id
					)
					$sql_public";


        $this->find($sql);
    }

    function addToUser($user_id, $permission){

        $searchArray["user_id"] = $user_id;
        $searchArray["permission"] = $permission;
        $searchArray = parent::putQuoteAndNull($searchArray);

        $sumary = self::_insert("user_permissions", $searchArray);

        return ($sumary->errorNo == 0);
    }

    function delToUser($user_id, $permission){

        $searchArray["user_id"] = $user_id;
        $searchArray["permission"] = $permission;
        $searchArray = parent::putQuoteAndNull($searchArray);

        $sumary = self::_delete("user_permissions", $searchArray);

        return ($sumary->errorNo == 0);
    }

    function getAsocToRol($rol_id){

        $sql = "SELECT `permissions`.`permission`,
					    `permissions`.`description`
					    
					FROM `permissions`
					JOIN group_permissions gu on gu.permission=`permissions`.`permission`
					WHERE gu.group_id=$rol_id
					GROUP BY `permissions`.`permission`";


        $this->find($sql);
    }

    function getNotAsocToRol($rol_id, $public=null){
        $sql_public = "";
        if(!$public){

            $sql_public = "and permissions.public = '".self::REG_ACTIVO_Y."'";
        }

        $sql = "SELECT `permissions`.`permission`,
					    `permissions`.`description`
					    
					FROM `permissions`
					WHERE `permissions`.`permission` not in (
						select  gu.permission from group_permissions gu WHERE gu.group_id=$rol_id
					)
					$sql_public";


        $this->find($sql);
    }

    function addToRol($rol_id, $permission){

        $searchArray["group_id"] = $rol_id;
        $searchArray["permission"] = $permission;
        $searchArray = parent::putQuoteAndNull($searchArray);

        $sumary = self::_insert("group_permissions", $searchArray);

        return ($sumary->errorNo == 0);
    }

    function delToRol($rol_id, $permission){

        $searchArray["group_id"] = $rol_id;
        $searchArray["permission"] = $permission;
        $searchArray = parent::putQuoteAndNull($searchArray);

        $sumary = self::_delete("group_permissions", $searchArray);

        return ($sumary->errorNo == 0);
    }

    public function loadPermissions($user_id)
    {
        $all_premissions = array();

        $sql = "SELECT t1.permission
                from permissions t1
                left join user_permissions t2 on t2.permission=t1.permission 
                left join group_permissions t3 on t3.permission=t1.permission
                left join group_users t4 on t4.group_id=t3.group_id
                WHERE 
                t4.user_id='$user_id'
                or 
                t2.user_id='$user_id'
                GROUP BY t1.permission";

        $this->find($sql);
        $permisos = $this->fetchAll();

        foreach ($permisos as $value) {
            $all_premissions[] = $value["permission"];
        }



        return $all_premissions;

    }
}