<?php


namespace Handlers\data_access;


class UserDAO extends AutoImplementedDAO
{
    const LOGIN_LDAP = 1;
    const LOGIN_LOCAL = 0;

    function __construct() {
        parent::__construct("users", array("uid"));
    }

    function getIdByUsername($username){
        $searchArray["username"] = $username;
        $searchArray["users.active"] = self::REG_ACTIVO;
        $searchArray = self::putQuoteAndNull($searchArray, !self::REMOVE_TAG);
        $where = self::getSQLFilter($searchArray);

        $sql = $this->getBaseSelec() . $where;


        $this->find($sql);
        $u = $this->get();

        return $u["uid"];
    }

    function getByUsername($username){
        $searchArray["username"] = $username;
        $searchArray["users.active"] = self::REG_ACTIVO;
        $searchArray = self::putQuoteAndNull($searchArray, !self::REMOVE_TAG);
        $where = self::getSQLFilter($searchArray);

        $sql = $this->getBaseSelec() . $where;


        $this->find($sql);

    }

    function validPass($id, $pass){
        $searchArray["users.active"] = self::REG_ACTIVO;
        $searchArray["users.uid"] = $id;
        $searchArray["users.password"] = md5($pass);
        $searchArray = self::putQuoteAndNull($searchArray, !self::REMOVE_TAG);


        return $this->existBy($searchArray);
    }
}