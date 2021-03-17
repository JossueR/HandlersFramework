<?php


namespace Handlers\models;

use Handlers\data_access\AutoImplementedDAO;

class ConnectionFromDAO extends AutoImplementedDAO
{
    function __construct() {
        parent::__construct("connection_from", array("id"));
    }




    function getActives(){
        $searchArray["connection_from.active"] = self::REG_ACTIVO_TX;
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


    function updateLast($condition){
        $searchArray["last"] = self::$SQL_TAG."now()";



        $this->update($searchArray, $condition);
    }

    function inactivateOthersTokens($token, $username){
        $searchArray["active"] = self::REG_DESACTIVADO_TX;

        $condition["active"] = self::REG_ACTIVO_TX;
        $condition["user"] = $username;
        $condition["token"] = self::$SQL_TAG . "<> '$token'";


        $this->update(
            self::putQuoteAndNull($searchArray),
            self::putQuoteAndNull($condition,self::NO_REMOVE_TAG),
            false
        );
    }
}