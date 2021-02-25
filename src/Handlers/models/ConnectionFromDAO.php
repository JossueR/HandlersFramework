<?php


namespace Handlers\models;


class ConnectionFromDAO extends AbstractBaseDAO
{
    function __construct() {
        parent::__construct("connection_from", array("id"));
    }

    function getPrototype(){
        $prototype = array(
            'url'=>null,
            'ip'=>null,
            'user'=>null,
            'token'=>null,
            'PHPSESSID'=>null,
            'customer_id'=>null
        );

        return $prototype;
    }


    function getDBMap(){
        $prototype = array(
            'id' => 'id',
            'url' => 'url',
            'ip'=>'ip',
            'user'=>'user',
            'token'=>'token',
            'lifetime'=>'lifetime',
            'last'=>'last',
            'PHPSESSID' => 'PHPSESSID',
            'customer_id' => 'customer_id',
            'active' => 'active'
        );

        return $prototype;
    }

    function getBaseSelec(){
        $sql = "SELECT `connection_from`.`id`,
					    `connection_from`.`url`,
					    `connection_from`.`ip`,
					    `connection_from`.`user`,
					    `connection_from`.`token`,
					    `connection_from`.`lifetime`,
					    `connection_from`.`last`,
					    `connection_from`.`PHPSESSID`,
					    `connection_from`.`customer_id`,
					    `connection_from`.`create_date`,
					    `connection_from`.`create_user`,
					    `connection_from`.`update_date`,
					    `connection_from`.`update_user`,
					    `connection_from`.`active`,
					    u.uid as user_id
					FROM `connection_from`
					LEFT JOIN users u on u.active=1 and u.username=`connection_from`.`user`
					WHERE ";

        return $sql;
    }


    function getActives(){
        $searchArray["connection_from.active"] = self::REG_ACTIVO_TX;
        $searchArray = self::putQuoteAndNull($searchArray, !self::REMOVE_TAG);
        $where = self::getSQLFilter($searchArray);

        $sql = $this->getBaseSelec() . $where;


        $this->find($sql);
    }



    function getInactives(){
        $searchArray["connection_from.active"] = self::REG_DESACTIVADO_TX;
        $searchArray = self::putQuoteAndNull($searchArray, !self::REMOVE_TAG);
        $where = self::getSQLFilter($searchArray);

        $sql = $this->getBaseSelec() . $where;


        $this->find($sql);
    }

    function isValidToken($token, $ip=null){
        $searchArray["connection_from.active"] = self::REG_ACTIVO_TX;
        $searchArray["connection_from.token"] = $token;

        if($ip){
            $searchArray["connection_from.ip"] = $ip;
        }


        $searchArray = self::putQuoteAndNull($searchArray, !self::REMOVE_TAG);


        $where = self::getSQLFilter($searchArray);
        $where .=" AND NOW() < DATE_ADD(connection_from.last,INTERVAL connection_from.lifetime HOUR_SECOND)";

        $sql = $this->getBaseSelec() . $where;


        $this->find($sql);
        return $this->get();
    }

    function getValidToken($user, $ip=null){
        $searchArray["connection_from.active"] = self::REG_ACTIVO_TX;
        $searchArray["connection_from.user"] = $user;

        if($ip){
            $searchArray["connection_from.ip"] = $ip;
        }


        $searchArray = self::putQuoteAndNull($searchArray, !self::REMOVE_TAG);


        $where = self::getSQLFilter($searchArray);
        $where .=" AND NOW() < DATE_ADD(connection_from.last,INTERVAL connection_from.lifetime HOUR_SECOND)";

        $sql = $this->getBaseSelec() . $where;


        $this->find($sql);
    }

    function getByToken($token, $user, $ip=null){
        $searchArray["connection_from.active"] = self::REG_ACTIVO_TX;
        $searchArray["connection_from.user"] = $user;
        $searchArray["connection_from.token"] = $token;

        if($ip){
            $searchArray["connection_from.ip"] = $ip;
        }



        $searchArray = self::putQuoteAndNull($searchArray, !self::REMOVE_TAG);


        $where = self::getSQLFilter($searchArray);
        $sql = $this->getBaseSelec() . $where;


        $this->find($sql);
    }

    function getByCustomer($customer_id){
        $searchArray["connection_from.active"] = self::REG_ACTIVO_TX;
        $searchArray["connection_from.customer_id"] = $customer_id;
        $searchArray = self::putQuoteAndNull($searchArray, !self::REMOVE_TAG);
        $where = self::getSQLFilter($searchArray);

        $sql = $this->getBaseSelec() . $where;


        $this->find($sql);
    }


    function &insert($searchArray){
        $defaul["create_date"] = self::$SQL_TAG."now()";
        $defaul["create_user"] = "SERVER";
        $defaul["active"] = self::REG_ACTIVO_TX;
        $defaul = parent::putQuoteAndNull($defaul);

        $searchArray = array_merge($searchArray, $defaul);


        return parent::insert($searchArray);

    }

    function &update($searchArray, $condicion){
        $defaul["update_date"] = self::$SQL_TAG."now()";
        $defaul["update_user"] = "SERVER";
        $defaul = parent::putQuoteAndNull($defaul);

        $searchArray = array_merge($searchArray, $defaul);
        return parent::update($searchArray, $condicion);
    }

    function updateLast($condicion){
        $searchArray["last"] = self::$SQL_TAG."now()";


        $searchArray = parent::putQuoteAndNull($searchArray);
        $this->update($searchArray, $condicion);
    }

    function inactivateOthersTokens($token, $username){
        $searchArray["active"] = self::REG_DESACTIVADO_TX;

        $condicion["active"] = self::REG_ACTIVO_TX;
        $condicion["user"] = $username;
        $condicion["token"] = self::$SQL_TAG . "<> '$token'";


        $this->update(
            self::putQuoteAndNull($searchArray),
            self::putQuoteAndNull($condicion,self::NO_REMOVE_TAG)
        );
    }
}