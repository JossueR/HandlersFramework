<?php


namespace Handlers\models;

use Handlers\data_access\AbstractBaseDAO;
class TrackLogDAO extends AbstractBaseDAO
{
    function __construct() {
        parent::__construct("tracklog", array("id"));
    }

    function getPrototype(){
        $prototype = array(
            "user"=>null,
            "ip"=>null,
            "get"=>null,
            "post"=>null,
            "_handler"=>null,
            "_do"=>null,
            "resp"=>null
        );
        $this->prototype = $prototype;
        return $prototype;
    }


    function getDBMap(){
        $prototype = array(
            "user"=>"user",
            "ip"=>"ip",
            "get"=>"get",
            "post"=>"post",
            "resp"=>"resp",
            "_handler"=>"_handler",
            "_do"=>"_do",
            "id"=>"id",
        );
        $this->map = $prototype;

        return $prototype;
    }

    function getBaseSelec(){
        $sql = "select id,`user`,ip,`get`, post, resp, _handler, _do from tracklog where ";
        $this->baseSelect = $sql;
        return $sql;
    }

    function &insert($searchArray, $putQuotesAndNull=true){
        $defaul["create_date"] = self::$SQL_TAG."now()";
        $defaul["date"] = self::$SQL_TAG."now()";

        $defaul = parent::putQuoteAndNull($defaul);

        $searchArray = array_merge($searchArray, $defaul);


        return parent::insert($searchArray, $putQuotesAndNull);

    }

    function &update($searchArray, $condicion, $putQuotesAndNull=true){
        $defaul["update_date"] = self::$SQL_TAG."now()";
        $defaul = parent::putQuoteAndNull($defaul);

        $searchArray = array_merge($searchArray, $defaul);
        return parent::update($searchArray, $condicion, $putQuotesAndNull);
    }
}